<?php
$allowed_origins = ['https://bai-website.netlify.app'];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "db.php";

// Get session token from cookie
$session_token = $_COOKIE['session_token'] ?? '';

if (!$session_token) {
    http_response_code(401);
    echo json_encode(["error" => "No session token"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        s.*, 
        a.Email, 
        m.member_name, 
        m.member_role 
    FROM tbl_sessions s 
    JOIN tbl_accounts a ON s.member_id = a.Member_ID 
    JOIN tbl_members m ON s.member_id = m.Member_ID 
    WHERE s.session_id = ? 
    AND s.expires_at > NOW()
");
$stmt->bind_param("s", $session_token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo json_encode([
        "user" => [
            "success" => true,
            "email" => $user['Email'],
            "member_id" => $user['member_id'],
            "name" => $user['member_name'],
            "role" => $user['member_role'],
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Session invalid or expired"]);
}
?>

