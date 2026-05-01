<?php
// ============================================================
//  config.php — FINAL CONFIRMED SETTINGS
//  Service: ORCL1PDB (confirmed working from your SQL*Plus test)
// ============================================================

error_reporting(0);
ini_set('display_errors', '0');

define('OCI_DSN',  '//localhost:1521/ORCL1PDB');  // CONFIRMED working service name
define('OCI_USER', 'unireg');                       // NOT unireg1 — just unireg
define('OCI_PASS', 'unireg123');

function getDB() {
    static $conn = null;
    if ($conn !== null) return $conn;

    if (!function_exists('oci_connect')) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'OCI8 not loaded. Add extension=oci8_12c to php.ini and restart Apache.'
        ]);
        exit;
    }

    $conn = @oci_connect(OCI_USER, OCI_PASS, OCI_DSN, 'AL32UTF8');

    if (!$conn) {
        $e = oci_error();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Oracle connection failed: ' . ($e['message'] ?? 'Unknown'),
            'dsn_tried' => OCI_DSN
        ]);
        exit;
    }

    return $conn;
}
