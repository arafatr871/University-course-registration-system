-- ============================================================
--  COMPLETE SETUP — 100% clean rebuild
--  Connect first: sqlplus unireg/unireg123@localhost:1521/ORCL1PDB
--  Then run:      @C:\xampp\htdocs\unireg\setup_complete.sql
-- ============================================================

-- Turn on output so you can see what's happening
SET SERVEROUTPUT ON
SET ECHO ON

-- ── STEP 1: Drop everything cleanly ─────────────────────────
-- Drop tables (CASCADE removes FK constraints and triggers automatically)
DECLARE
BEGIN
    BEGIN EXECUTE IMMEDIATE 'DROP TABLE enrollments CASCADE CONSTRAINTS PURGE'; EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP TABLE courses    CASCADE CONSTRAINTS PURGE'; EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP TABLE students   CASCADE CONSTRAINTS PURGE'; EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE enrollment_seq';                    EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP VIEW course_enrollment_summary';             EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE enroll_student';                   EXCEPTION WHEN OTHERS THEN NULL; END;
    BEGIN EXECUTE IMMEDIATE 'DROP TRIGGER trg_auto_waitlist_enroll';           EXCEPTION WHEN OTHERS THEN NULL; END;
    DBMS_OUTPUT.PUT_LINE('>> Old objects dropped (or did not exist). OK.');
END;
/

-- ── STEP 2: Create tables ────────────────────────────────────
CREATE TABLE students (
    student_id  NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name        VARCHAR2(100) NOT NULL,
    email       VARCHAR2(150) NOT NULL CONSTRAINT uq_student_email UNIQUE,
    cgpa        NUMBER(4,2)   NOT NULL,
    CONSTRAINT chk_cgpa CHECK (cgpa BETWEEN 0 AND 4)
);

CREATE TABLE courses (
    course_id   NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_name VARCHAR2(200) NOT NULL,
    max_seats   NUMBER(5)     NOT NULL,
    day         VARCHAR2(20)  NOT NULL,
    start_time  NUMBER(4)     NOT NULL,
    end_time    NUMBER(4)     NOT NULL,
    CONSTRAINT chk_seats CHECK (max_seats > 0),
    CONSTRAINT chk_times CHECK (end_time > start_time),
    CONSTRAINT chk_day   CHECK (day IN ('SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY'))
);

CREATE TABLE enrollments (
    enrollment_id NUMBER(10)   NOT NULL,
    student_id    NUMBER(10)   NOT NULL,
    course_id     NUMBER(10)   NOT NULL,
    status        VARCHAR2(12) NOT NULL,
    enrolled_at   TIMESTAMP    DEFAULT SYSTIMESTAMP,
    CONSTRAINT pk_enrollment     PRIMARY KEY (enrollment_id),
    CONSTRAINT fk_enr_student    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_enr_course     FOREIGN KEY (course_id)  REFERENCES courses(course_id)   ON DELETE CASCADE,
    CONSTRAINT uq_student_course UNIQUE (student_id, course_id),
    CONSTRAINT chk_status        CHECK  (status IN ('ENROLLED','WAITLISTED'))
);

-- ── STEP 3: Sequence ─────────────────────────────────────────
CREATE SEQUENCE enrollment_seq
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;

-- ── STEP 4: View ─────────────────────────────────────────────
CREATE OR REPLACE VIEW course_enrollment_summary AS
SELECT
    c.course_id,
    c.course_name,
    c.max_seats,
    c.day,
    c.start_time,
    c.end_time,
    COUNT(CASE WHEN e.status = 'ENROLLED'   THEN 1 END) AS enrolled_count,
    COUNT(CASE WHEN e.status = 'WAITLISTED' THEN 1 END) AS waitlisted_count,
    c.max_seats - COUNT(CASE WHEN e.status = 'ENROLLED' THEN 1 END) AS seats_available
FROM courses c
LEFT JOIN enrollments e ON c.course_id = e.course_id
GROUP BY c.course_id, c.course_name, c.max_seats, c.day, c.start_time, c.end_time;

-- ── STEP 5: Stored Procedure ─────────────────────────────────
CREATE OR REPLACE PROCEDURE enroll_student (
    p_student_id  IN  NUMBER,
    p_course_id   IN  NUMBER,
    p_status      OUT VARCHAR2,
    p_message     OUT VARCHAR2
) AS
    v_enrolled_count  NUMBER;
    v_max_seats       NUMBER;
    v_conflict_count  NUMBER;
    v_dup_count       NUMBER;
    v_cgpa            NUMBER;
    v_day             VARCHAR2(20);
    v_start           NUMBER;
    v_end             NUMBER;
    v_new_id          NUMBER;
    v_assign_status   VARCHAR2(12);
    ex_duplicate      EXCEPTION;
    ex_time_conflict  EXCEPTION;
    ex_not_found      EXCEPTION;
BEGIN
    -- Check student exists
    BEGIN
        SELECT cgpa INTO v_cgpa FROM students WHERE student_id = p_student_id;
    EXCEPTION WHEN NO_DATA_FOUND THEN RAISE ex_not_found;
    END;

    -- Check course exists
    BEGIN
        SELECT max_seats, day, start_time, end_time
        INTO   v_max_seats, v_day, v_start, v_end
        FROM   courses WHERE course_id = p_course_id;
    EXCEPTION WHEN NO_DATA_FOUND THEN RAISE ex_not_found;
    END;

    -- Check duplicate enrollment
    SELECT COUNT(*) INTO v_dup_count
    FROM   enrollments
    WHERE  student_id = p_student_id AND course_id = p_course_id;
    IF v_dup_count > 0 THEN RAISE ex_duplicate; END IF;

    -- Check time conflict
    SELECT COUNT(*) INTO v_conflict_count
    FROM   enrollments e
    JOIN   courses c ON c.course_id = e.course_id
    WHERE  e.student_id = p_student_id
    AND    e.status     = 'ENROLLED'
    AND    c.day        = v_day
    AND    c.start_time < v_end
    AND    c.end_time   > v_start;
    IF v_conflict_count > 0 THEN RAISE ex_time_conflict; END IF;

    -- Check seat availability
    SELECT COUNT(*) INTO v_enrolled_count
    FROM   enrollments
    WHERE  course_id = p_course_id AND status = 'ENROLLED';

    IF v_enrolled_count < v_max_seats THEN
        v_assign_status := 'ENROLLED';
    ELSE
        v_assign_status := 'WAITLISTED';
    END IF;

    -- Insert enrollment
    v_new_id := enrollment_seq.NEXTVAL;
    INSERT INTO enrollments (enrollment_id, student_id, course_id, status)
    VALUES (v_new_id, p_student_id, p_course_id, v_assign_status);
    COMMIT;

    p_status  := v_assign_status;
    p_message := 'Successfully ' || LOWER(v_assign_status) || ' in course.';

EXCEPTION
    WHEN ex_duplicate THEN
        p_status  := 'ERROR';
        p_message := 'Already enrolled or waitlisted in this course.';
        ROLLBACK;
    WHEN ex_time_conflict THEN
        p_status  := 'ERROR';
        p_message := 'Schedule conflict on ' || v_day || '.';
        ROLLBACK;
    WHEN ex_not_found THEN
        p_status  := 'ERROR';
        p_message := 'Student or course not found.';
        ROLLBACK;
    WHEN OTHERS THEN
        p_status  := 'ERROR';
        p_message := 'Error: ' || SQLERRM;
        ROLLBACK;
END enroll_student;
/

-- ── STEP 6: Trigger ──────────────────────────────────────────
CREATE OR REPLACE TRIGGER trg_auto_waitlist_enroll
AFTER DELETE ON enrollments
FOR EACH ROW
WHEN (OLD.status = 'ENROLLED')
DECLARE
    v_enrolled_count NUMBER;
    v_max_seats      NUMBER;
    v_day            VARCHAR2(20);
    v_start          NUMBER;
    v_end            NUMBER;
    v_conflict       NUMBER;
    CURSOR c_waitlist IS
        SELECT e.enrollment_id, e.student_id
        FROM   enrollments e
        JOIN   students    s ON s.student_id = e.student_id
        WHERE  e.course_id = :OLD.course_id
        AND    e.status    = 'WAITLISTED'
        ORDER  BY s.cgpa DESC, e.enrolled_at ASC;
BEGIN
    SELECT COUNT(*) INTO v_enrolled_count
    FROM   enrollments
    WHERE  course_id = :OLD.course_id AND status = 'ENROLLED';

    SELECT max_seats, day, start_time, end_time
    INTO   v_max_seats, v_day, v_start, v_end
    FROM   courses
    WHERE  course_id = :OLD.course_id;

    IF v_enrolled_count < v_max_seats THEN
        FOR rec IN c_waitlist LOOP
            SELECT COUNT(*) INTO v_conflict
            FROM   enrollments e
            JOIN   courses     c ON c.course_id = e.course_id
            WHERE  e.student_id = rec.student_id
            AND    e.status     = 'ENROLLED'
            AND    c.day        = v_day
            AND    c.start_time < v_end
            AND    c.end_time   > v_start;

            IF v_conflict = 0 THEN
                UPDATE enrollments
                SET    status = 'ENROLLED'
                WHERE  enrollment_id = rec.enrollment_id;
                EXIT;
            END IF;
        END LOOP;
    END IF;
END trg_auto_waitlist_enroll;
/

-- ── STEP 7: Seed data ────────────────────────────────────────
INSERT INTO students (name, email, cgpa) VALUES ('Alice Rahman',  'alice@uni.edu',  3.92);
INSERT INTO students (name, email, cgpa) VALUES ('Bob Hossain',   'bob@uni.edu',    3.45);
INSERT INTO students (name, email, cgpa) VALUES ('Carla Ahmed',   'carla@uni.edu',  3.78);
INSERT INTO students (name, email, cgpa) VALUES ('David Khan',    'david@uni.edu',  2.90);
INSERT INTO students (name, email, cgpa) VALUES ('Eva Begum',     'eva@uni.edu',    3.60);
INSERT INTO students (name, email, cgpa) VALUES ('Farhan Islam',  'farhan@uni.edu', 3.15);
INSERT INTO students (name, email, cgpa) VALUES ('Grace Noor',    'grace@uni.edu',  3.88);
INSERT INTO students (name, email, cgpa) VALUES ('Hamid Uddin',   'hamid@uni.edu',  2.75);

INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Database Systems',        3, 'SUNDAY',    900,  1030);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Operating Systems',       4, 'MONDAY',   1100,  1230);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Algorithms & Complexity', 3, 'TUESDAY',   800,   930);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Computer Networks',       5, 'WEDNESDAY',1300,  1430);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Software Engineering',    2, 'THURSDAY',  900,  1030);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Artificial Intelligence', 4, 'SUNDAY',   1100,  1230);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Linear Algebra',          3, 'MONDAY',    800,   930);
INSERT INTO courses (course_name, max_seats, day, start_time, end_time)
    VALUES ('Web Technologies',        5, 'FRIDAY',   1400,  1530);

COMMIT;

-- ── STEP 8: Verify everything ────────────────────────────────
PROMPT ============================================
PROMPT VERIFICATION RESULTS — should all show OK
PROMPT ============================================

SELECT 'students table  : ' || COUNT(*) || ' rows — OK' AS result FROM students
UNION ALL
SELECT 'courses table   : ' || COUNT(*) || ' rows — OK'  FROM courses
UNION ALL
SELECT 'enrollments     : ' || COUNT(*) || ' rows — OK'  FROM enrollments;

SELECT object_type || ' : ' || object_name || ' — ' || status AS objects_created
FROM   user_objects
WHERE  object_type IN ('TABLE','VIEW','PROCEDURE','TRIGGER','SEQUENCE')
ORDER  BY object_type, object_name;

PROMPT ============================================
PROMPT SETUP COMPLETE! All objects created.
PROMPT Now open: http://localhost/unireg/index.php
PROMPT ============================================

EXIT;
