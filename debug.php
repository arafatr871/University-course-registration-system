<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
<!DOCTYPE html>
<html>
<head>
<title>UniReg Debug</title>
<style>
body{font-family:monospace;background:#0a0f1e;color:#e0e6ff;padding:30px;line-height:1.8;font-size:14px}
h1{color:#f0b429;font-size:22px;margin-bottom:4px}
h2{color:#38bdf8;font-size:15px;margin-top:24px;border-bottom:1px solid #1e3a5f;padding-bottom:6px}
.ok{color:#34d399;font-weight:bold}
.fail{color:#f87171;font-weight:bold}
.warn{color:#fb923c;font-weight:bold}
.box{background:#0d1b33;border:1px solid #1e3a5f;border-radius:8px;padding:16px;margin:8px 0}
code{background:#0d1b33;padding:2px 7px;border-radius:4px;color:#38bdf8;font-size:13px}
table{border-collapse:collapse;width:100%}
td,th{padding:7px 12px;border:1px solid #1e3a5f;text-align:left;font-size:13px}
th{background:#0d1b33;color:#38bdf8}
.btn{display:inline-block;padding:8px 16px;background:#f0b429;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:4px 4px 4px 0;font-size:13px}
.btn-b{background:#38bdf8}
hr{border:none;border-top:1px solid #1e3a5f;margin:16px 0}
</style>
</head>
<body>
<h1>UniReg Diagnostic</h1>
<p style="color:#8b9ab5;margin-bottom:0">Automatically detects your Oracle setup and tells you exactly what to put in config.php</p>

<h2>1. PHP &amp; OCI8 Status</h2>
<div class="box">
<?php
echo "PHP Version: <span class='ok'>" . PHP_VERSION . "</span><br>";
$ts = defined('PHP_ZTS') && PHP_ZTS;
echo "Thread Safety: <span class='" . ($ts ? 'ok':'warn') . "'>" . ($ts ? 'TS (Thread Safe)':'NTS (Non-Thread Safe)') . "</span><br>";
echo "Architecture: <span class='ok'>" . (PHP_INT_SIZE===8?'64-bit':'32-bit') . "</span><br>";
echo "php.ini: <span class='ok'>" . php_ini_loaded_file() . "</span><br>";

$oci8 = extension_loaded('oci8');
echo "<br>OCI8 Extension: ";
if ($oci8) {
    echo "<span class='ok'>LOADED ✅</span> — version: " . phpversion('oci8') . "<br>";
} else {
    echo "<span class='fail'>NOT LOADED ❌</span><br>";
    echo "<br><b style='color:#fb923c'>Fix:</b><br>";
    echo "1. Open <b>" . php_ini_loaded_file() . "</b><br>";
    echo "2. Find <code>;extension=oci8_12c</code> and remove the semicolon<br>";
    echo "3. Save &amp; restart Apache in XAMPP<br>";
    echo "<br><span class='fail'>Cannot test Oracle connection until OCI8 is loaded.</span>";
}
?>
</div>

<?php if (extension_loaded('oci8')): ?>

<h2>2. Oracle Service Discovery</h2>
<div class="box">
<?php
// All service names to try, in order of likelihood for this machine
$servicesToTry = [
    'ORCLPDB1',   // Oracle 19c CDB - YOUR machine/12c pluggable DB (most likely given your services)
    'ORCL1',      // OracleServiceORCL1 variant
    'ORCL',       // Oracle 19c/12c CDB root
    'XEPDB1',     // Oracle 21c XE pluggable
    'XE',         // Oracle 11g/18c XE
    'ORCLCDB',    // Some 12c installs
    'PDBORCL',    // Some 12c PDB names
];

// Credentials to test with
$testUser = 'unireg';
$testPass = 'unireg123';
$sysPass  = ''; // we don't know SYSTEM password

$working = [];
$tried   = [];

echo "Testing " . count($servicesToTry) . " possible service names...<br><br>";

foreach ($servicesToTry as $svc) {
    $dsn = "//localhost:1521/$svc";
    $conn = @oci_connect($testUser, $testPass, $dsn, 'AL32UTF8');
    $tried[] = $svc;
    if ($conn) {
        $working[] = ['dsn' => $dsn, 'svc' => $svc, 'conn' => $conn];
        echo "<span class='ok'>✅ WORKS: $dsn</span><br>";
    } else {
        $e = oci_error();
        $code = $e['code'] ?? 0;
        // ORA-01017 = wrong password but service EXISTS - still useful!
        if ($code == 1017) {
            echo "<span class='warn'>⚠️  SERVICE EXISTS but wrong password: $dsn (ORA-01017)</span><br>";
            $working[] = ['dsn' => $dsn, 'svc' => $svc, 'conn' => null, 'auth_fail' => true];
        } elseif ($code == 28000) {
            echo "<span class='warn'>⚠️  SERVICE EXISTS but account locked: $dsn (ORA-28000)</span><br>";
            $working[] = ['dsn' => $dsn, 'svc' => $svc, 'conn' => null, 'locked' => true];
        } else {
            echo "<span style='color:#4a5a78'>✗  Not found: $dsn (" . ($e['message'] ? substr($e['message'],0,60) : 'no error') . ")</span><br>";
        }
    }
}

echo "<br>";
if (empty($working)) {
    echo "<span class='fail'>❌ No working service found!</span><br><br>";
    echo "<b>Next step:</b> Open Command Prompt as Administrator and run:<br>";
    echo "<code>lsnrctl status</code><br><br>";
    echo "Look for 'Services Summary' and tell us the service names listed there.<br><br>";
    echo "<b>Also check Windows Services (services.msc):</b><br>";
    echo "• OracleServiceORCL — should be <b>Running</b><br>";
    echo "• OracleServiceORCL1 — should be <b>Running</b><br>";
    echo "• OracleOraDB19Home1TNSListener — should be <b>Running</b><br>";
} else {
    echo "<span class='ok'>Found " . count($working) . " matching service(s)!</span> See recommendation below.";
}
?>
</div>

<?php if (!empty($working)): ?>
<h2>3. Recommended config.php Setting</h2>
<div class="box">
<?php
$best = null;
foreach ($working as $w) {
    if (!empty($w['conn'])) { $best = $w; break; }
}
if (!$best) $best = $working[0];

echo "<b>Put this in your config.php:</b><br><br>";
echo "<code>define('OCI_DSN', '" . $best['dsn'] . "');</code><br><br>";

if (!empty($best['conn'])) {
    echo "<span class='ok'>✅ This DSN works with your unireg user credentials!</span><br>";
} elseif (!empty($best['auth_fail'])) {
    echo "<span class='warn'>⚠️ Service found but password is wrong for 'unireg'.</span><br>";
    echo "Either the unireg user doesn't exist yet, or the password is different.<br><br>";
    echo "<b>Create the unireg user (run in SQL*Plus as SYSTEM):</b><br>";
    echo "<code>sqlplus system/YOUR_PASSWORD@localhost:1521/" . $best['svc'] . "</code><br><br>";
    echo "Then inside SQL*Plus:<br>";
    echo "<code>CREATE USER unireg IDENTIFIED BY mypassword123<br>";
    echo "&nbsp;&nbsp;DEFAULT TABLESPACE USERS<br>";
    echo "&nbsp;&nbsp;TEMPORARY TABLESPACE TEMP<br>";
    echo "&nbsp;&nbsp;QUOTA UNLIMITED ON USERS;<br>";
    echo "GRANT CONNECT, RESOURCE TO unireg;<br>";
    echo "GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE,<br>";
    echo "&nbsp;&nbsp;CREATE PROCEDURE, CREATE TRIGGER, CREATE VIEW TO unireg;<br>";
    echo "EXIT;</code><br>";
}
?>
</div>

<?php if (!empty($best['conn'])): ?>
<h2>4. Database Objects Check</h2>
<div class="box">
<?php
$conn = $best['conn'];
$objs = [
    ['TABLE',     'students',                    'students table'],
    ['TABLE',     'courses',                     'courses table'],
    ['TABLE',     'enrollments',                 'enrollments table'],
    ['VIEW',      'COURSE_ENROLLMENT_SUMMARY',   'summary view'],
    ['PROCEDURE', 'ENROLL_STUDENT',              'enroll procedure'],
    ['TRIGGER',   'TRG_AUTO_WAITLIST_ENROLL',    'waitlist trigger'],
    ['SEQUENCE',  'ENROLLMENT_SEQ',              'enrollment sequence'],
];

$allGood = true;
foreach ($objs as $obj) {
    [$type, $name, $label] = $obj;
    if ($type === 'TABLE') {
        $stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM $name");
        $ok = $stmt && oci_execute($stmt);
        if ($ok) {
            $row = oci_fetch_assoc($stmt);
            echo "<span class='ok'>✅ $label — {$row['CNT']} rows</span><br>";
            oci_free_statement($stmt);
        } else {
            echo "<span class='fail'>❌ $label — NOT FOUND (run setup.sql!)</span><br>";
            $allGood = false;
        }
    } else {
        $stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM user_objects WHERE object_type=:t AND object_name=:n");
        oci_bind_by_name($stmt, ':t', $type);
        oci_bind_by_name($stmt, ':n', $name);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        if ($row['CNT'] > 0) {
            echo "<span class='ok'>✅ $label</span><br>";
        } else {
            echo "<span class='fail'>❌ $label — missing (run setup.sql!)</span><br>";
            $allGood = false;
        }
    }
}

if (!$allGood) {
    echo "<br><span class='warn'>Some objects are missing. Run setup.sql:</span><br>";
    echo "<code>sqlplus unireg/mypassword123@localhost:1521/" . $best['svc'] . "</code><br>";
    echo "Then: <code>@C:\\xampp\\htdocs\\unireg\\setup.sql</code><br>";
} else {
    echo "<br><span class='ok'>🎉 ALL DATABASE OBJECTS EXIST! Your setup is complete.</span><br>";
    echo "<br>Go to your app: <a href='index.php'>index.php</a>";
}

foreach ($working as $w) {
    if (!empty($w['conn'])) oci_close($w['conn']);
}
?>
</div>
<?php endif; ?>
<?php endif; ?>

<h2>5. Quick Links</h2>
<div class="box">
<?php
$base = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']),'/');
?>
<a class="btn" href="<?=$base?>/api.php?action=ping" target="_blank">API Ping Test</a>
<a class="btn" href="<?=$base?>/api.php?action=get_students" target="_blank">Get Students JSON</a>
<a class="btn" href="<?=$base?>/api.php?action=get_courses" target="_blank">Get Courses JSON</a>
<a class="btn btn-b" href="<?=$base?>/index.php">Open App</a>
</div>

<?php endif; ?>

<h2>Your Oracle Services (from screenshot)</h2>
<div class="box">
<table>
<tr><th>Service Name</th><th>Status in your screenshot</th><th>Action needed</th></tr>
<tr><td>OracleServiceORCL</td><td style="color:#f87171">Not Running</td><td>Right-click → Start</td></tr>
<tr><td>OracleServiceORCL1</td><td style="color:#34d399">Running ✅</td><td>Nothing — already running</td></tr>
<tr><td>OracleOraDB19Home1TNSListener</td><td style="color:#f87171">Not Running</td><td>Right-click → Start</td></tr>
<tr><td>OracleOraDB19Home2TNSL...</td><td style="color:#34d399">Running ✅</td><td>Nothing — already running</td></tr>
</table>
<br>
<b style="color:#fb923c">⚠️ You have TWO Oracle homes (Home1 and Home2). Home2 listener is running.</b><br>
The running instance is <b>ORCL1</b>. Try DSN: <code>//localhost:1521/ORCL1</code>
</div>

<p style="margin-top:24px;color:#4a5a78;font-size:12px">Delete debug.php after setup is complete — it shows your credentials!</p>
</body>
</html>