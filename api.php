<?php
// Step 1: suppress PHP errors BEFORE any output
error_reporting(0);
ini_set('display_errors', '0');

// Step 2: set JSON header FIRST before anything else runs
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// Step 3: load config (connection function)
require_once __DIR__ . '/config.php';

// Step 4: wrap everything in try/catch so NO raw PHP errors escape
$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── DIAGNOSTIC (visit api.php?action=ping to test) ──
        case 'ping':
            ping();
            break;

        // ── Students ─────────────────────────────────────────
        case 'get_students':
            getStudents();
            break;

        case 'add_student':
            addStudent();
            break;

        // ── Courses ──────────────────────────────────────────
        case 'get_courses':
            getCourses();
            break;

        case 'add_course':
            addCourse();
            break;

        case 'delete_course':
            deleteCourse();
            break;

        // ── Enrollments ───────────────────────────────────────
        case 'enroll':
            enrollStudent();
            break;

        case 'drop':
            dropCourse();
            break;

        case 'get_enrollments':
            getEnrollments();
            break;

        case 'get_all_enrollments':
            getAllEnrollments();
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// ============================================================
//  DIAGNOSTIC — visit ?action=ping to check everything
// ============================================================
function ping(): void {
    $checks = [];

    // Check 1: OCI8 loaded?
    $checks['oci8_loaded'] = function_exists('oci_connect') ? 'YES' : 'NO - enable in php.ini!';

    // Check 2: Can we connect?
    if (function_exists('oci_connect')) {
        $conn = @oci_connect(OCI_USER, OCI_PASS, OCI_DSN, 'AL32UTF8');
        if ($conn) {
            $checks['oracle_connect'] = 'SUCCESS';
            // Check 3: Can we query?
            $stmt = @oci_parse($conn, 'SELECT COUNT(*) AS CNT FROM students');
            if ($stmt && @oci_execute($stmt)) {
                $row = oci_fetch_assoc($stmt);
                $checks['students_table'] = 'OK - row count: ' . ($row['CNT'] ?? '?');
                oci_free_statement($stmt);
            } else {
                $e2 = oci_error($conn);
                $checks['students_table'] = 'QUERY FAILED: ' . ($e2['message'] ?? 'unknown');
            }
            oci_close($conn);
        } else {
            $e2 = oci_error();
            $checks['oracle_connect'] = 'FAILED: ' . ($e2['message'] ?? 'unknown');
        }
    }

    $checks['php_version'] = PHP_VERSION;
    $checks['extensions']  = implode(', ', array_filter(get_loaded_extensions(), fn($e) => str_contains(strtolower($e), 'oci')));

    echo json_encode(['success' => true, 'diagnostic' => $checks], JSON_PRETTY_PRINT);
}

// ============================================================
//  STUDENTS
// ============================================================
function getStudents(): void {
    $db  = getDB();
    $sql = 'SELECT student_id, name, email, cgpa FROM students ORDER BY name';
    $stmt = oci_parse($db, $sql);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo json_encode(['success' => false, 'message' => $e['message']]);
        return;
    }
    $rows = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $rows[] = array_change_key_case($row, CASE_LOWER);
    }
    oci_free_statement($stmt);
    echo json_encode(['success' => true, 'data' => $rows]);
}

function addStudent(): void {
    $raw   = file_get_contents('php://input');
    $body  = json_decode($raw, true) ?? [];
    $name  = trim($body['name']  ?? '');
    $email = trim($body['email'] ?? '');
    $cgpa  = isset($body['cgpa']) ? (float)$body['cgpa'] : -1;

    if ($name === '' || $email === '' || $cgpa < 0 || $cgpa > 4) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data. Name, email required. CGPA must be 0-4.']);
        return;
    }

    $db  = getDB();
    $sql = 'INSERT INTO students (name, email, cgpa) VALUES (:name, :email, :cgpa) RETURNING student_id INTO :new_id';
    $stmt = oci_parse($db, $sql);
    oci_bind_by_name($stmt, ':name',   $name,  100);
    oci_bind_by_name($stmt, ':email',  $email, 150);
    oci_bind_by_name($stmt, ':cgpa',   $cgpa);
    $newId = 0;
    oci_bind_by_name($stmt, ':new_id', $newId, 20, SQLT_INT);

    if (!oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        $msg = (strpos($e['message'], 'UQ_STUDENT_EMAIL') !== false)
             ? 'This email is already registered.' : $e['message'];
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => $msg]);
        return;
    }
    oci_free_statement($stmt);
    echo json_encode(['success' => true, 'student_id' => (int)$newId, 'message' => 'Student registered successfully.']);
}

// ============================================================
//  COURSES
// ============================================================
function getCourses(): void {
    $db  = getDB();
    $sql = 'SELECT course_id, course_name, max_seats, day, start_time, end_time,
                   enrolled_count, waitlisted_count, seats_available
            FROM   course_enrollment_summary
            ORDER  BY day, start_time';
    $stmt = oci_parse($db, $sql);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo json_encode(['success' => false, 'message' => $e['message']]);
        return;
    }
    $rows = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $r = array_change_key_case($row, CASE_LOWER);
        $r['start_time_fmt'] = formatTime((int)$r['start_time']);
        $r['end_time_fmt']   = formatTime((int)$r['end_time']);
        $rows[] = $r;
    }
    oci_free_statement($stmt);
    echo json_encode(['success' => true, 'data' => $rows]);
}

function addCourse(): void {
    $raw   = file_get_contents('php://input');
    $body  = json_decode($raw, true) ?? [];
    $name  = trim($body['course_name'] ?? '');
    $seats = (int)($body['max_seats']  ?? 0);
    $day   = strtoupper(trim($body['day'] ?? ''));
    $start = (int)($body['start_time'] ?? 0);
    $end   = (int)($body['end_time']   ?? 0);

    $validDays = ['SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY'];
    if ($name === '' || $seats < 1 || !in_array($day, $validDays) || $start >= $end || $start < 100 || $end < 100) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid course data. Times must be HHMM format (e.g. 900 for 9am, 1030 for 10:30am). End must be after start.']);
        return;
    }

    $db   = getDB();
    $sql  = 'INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
             VALUES (:cn, :ms, :day, :st, :et) RETURNING course_id INTO :new_id';
    $stmt = oci_parse($db, $sql);
    oci_bind_by_name($stmt, ':cn',     $name,  200);
    oci_bind_by_name($stmt, ':ms',     $seats);
    oci_bind_by_name($stmt, ':day',    $day,   20);
    oci_bind_by_name($stmt, ':st',     $start);
    oci_bind_by_name($stmt, ':et',     $end);
    $newId = 0;
    oci_bind_by_name($stmt, ':new_id', $newId, 20, SQLT_INT);

    if (!oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        echo json_encode(['success' => false, 'message' => $e['message']]);
        return;
    }
    oci_free_statement($stmt);
    echo json_encode(['success' => true, 'course_id' => (int)$newId, 'message' => 'Course added successfully.']);
}

function deleteCourse(): void {
    $id = (int)($_GET['course_id'] ?? 0);
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid course_id.']);
        return;
    }
    $db   = getDB();
    $stmt = oci_parse($db, 'DELETE FROM courses WHERE course_id = :id');
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    $rows = oci_num_rows($stmt);
    oci_free_statement($stmt);
    if ($rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Course not found.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Course deleted.']);
    }
}

// ============================================================
//  ENROLLMENTS
// ============================================================
function enrollStudent(): void {
    $raw       = file_get_contents('php://input');
    $body      = json_decode($raw, true) ?? [];
    $studentId = (int)($body['student_id'] ?? 0);
    $courseId  = (int)($body['course_id']  ?? 0);

    if ($studentId < 1 || $courseId < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'student_id and course_id are required.']);
        return;
    }

    $db   = getDB();
    $stmt = oci_parse($db, 'BEGIN enroll_student(:sid, :cid, :status, :msg); END;');
    if (!$stmt) {
        $e = oci_error($db);
        echo json_encode(['success' => false, 'message' => 'Parse failed: ' . $e['message']]);
        return;
    }

    oci_bind_by_name($stmt, ':sid',    $studentId);
    oci_bind_by_name($stmt, ':cid',    $courseId);
    $status = str_repeat(' ', 12);
    $msg    = str_repeat(' ', 512);
    oci_bind_by_name($stmt, ':status', $status, 12);
    oci_bind_by_name($stmt, ':msg',    $msg,    512);

    if (!oci_execute($stmt, OCI_DEFAULT)) {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        echo json_encode(['success' => false, 'message' => $e['message'] ?? 'Execute failed']);
        return;
    }
    oci_free_statement($stmt);

    $status = trim($status);
    $msg    = trim($msg);
    $success = ($status === 'ENROLLED' || $status === 'WAITLISTED');
    if (!$success) http_response_code(409);
    echo json_encode(['success' => $success, 'status' => $status, 'message' => $msg]);
}

function dropCourse(): void {
    $raw       = file_get_contents('php://input');
    $body      = json_decode($raw, true) ?? [];
    $studentId = (int)($body['student_id'] ?? 0);
    $courseId  = (int)($body['course_id']  ?? 0);

    if ($studentId < 1 || $courseId < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'student_id and course_id required.']);
        return;
    }

    $db   = getDB();
    $stmt = oci_parse($db, 'DELETE FROM enrollments WHERE student_id = :sid AND course_id = :cid');
    oci_bind_by_name($stmt, ':sid', $studentId);
    oci_bind_by_name($stmt, ':cid', $courseId);
    oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    $rows = oci_num_rows($stmt);
    oci_free_statement($stmt);

    if ($rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Enrollment not found.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Course dropped. Waitlist auto-enrollment triggered if applicable.']);
    }
}

function getEnrollments(): void {
    $studentId = (int)($_GET['student_id'] ?? 0);
    if ($studentId < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'student_id required.']);
        return;
    }

    $db  = getDB();
    $sql = "SELECT e.enrollment_id, c.course_id, c.course_name, c.day,
                   c.start_time, c.end_time, c.max_seats, e.status, e.enrolled_at
            FROM   enrollments e
            JOIN   courses     c ON c.course_id = e.course_id
            WHERE  e.student_id = :sid
            ORDER  BY e.status, c.day, c.start_time";
    $stmt = oci_parse($db, $sql);
    oci_bind_by_name($stmt, ':sid', $studentId);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo json_encode(['success' => false, 'message' => $e['message']]);
        return;
    }
    $rows = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $r = array_change_key_case($row, CASE_LOWER);
        $r['start_time_fmt'] = formatTime((int)$r['start_time']);
        $r['end_time_fmt']   = formatTime((int)$r['end_time']);
        $rows[] = $r;
    }
    oci_free_statement($stmt);
    echo json_encode(['success' => true, 'data' => $rows]);
}

function getAllEnrollments(): void {
    $db  = getDB();
    $sql = "SELECT e.enrollment_id,
                   s.student_id, s.name AS student_name, s.cgpa,
                   c.course_id, c.course_name, c.day, c.start_time, c.end_time,
                   e.status, TO_CHAR(e.enrolled_at,'YYYY-MM-DD HH24:MI') AS enrolled_at
            FROM   enrollments e
            JOIN   students    s ON s.student_id = e.student_id
            JOIN   courses     c ON c.course_id  = e.course_id
            ORDER  BY c.course_name, e.status, s.cgpa DESC";
    $stmt = oci_parse($db, $sql);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo json_encode(['success' => false, 'message' => $e['message']]);
        return;
    }
    $rows = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $r = array_change_key_case($row, CASE_LOWER);
        $r['start_time_fmt'] = formatTime((int)$r['start_time']);
        $r['end_time_fmt']   = formatTime((int)$r['end_time']);
        $rows[] = $r;
    }
    oci_free_statement($stmt);
    echo json_encode(['success' => true, 'data' => $rows]);
}

// ============================================================
//  HELPER
// ============================================================
function formatTime(int $t): string {
    if ($t <= 0) return '--:--';
    $h = intdiv($t, 100);
    $m = $t % 100;
    $suffix = $h >= 12 ? 'PM' : 'AM';
    $h12    = $h > 12 ? $h - 12 : ($h === 0 ? 12 : $h);
    return sprintf('%d:%02d %s', $h12, $m, $suffix);
}
