<?php
// Allow requests from your frontend's origin
header("Access-Control-Allow-Origin: http://localhost:5173");

// Allow cookies to be sent
header("Access-Control-Allow-Credentials: true");

// Allow preflight method headers
header("Access-Control-Allow-Headers: Content-Type");

// Allow certain HTTP methods
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Handle preflight OPTIONS request early and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once "db.php";
header('Content-Type: application/json');

if (isset($_COOKIE['session_token'])) {
    $session_id = $_COOKIE['session_token'];

    $stmt = $conn->prepare("DELETE FROM tbl_sessions WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();

    setcookie("session_token", "", time() - 3600, "/");
}

echo json_encode(["success" => true]);
?>
