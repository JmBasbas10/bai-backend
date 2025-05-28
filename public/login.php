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

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$password = $data['password'];

$stmt = $conn->prepare("
  SELECT a.Member_ID, a.Email, a.Password, m.Member_Name, m.Member_Role
  FROM tbl_accounts a
  JOIN tbl_members m ON a.Member_ID = m.Member_ID
  WHERE a.Email = ?
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($password, $user['Password'])) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}

// Create session
$session_id = bin2hex(random_bytes(32));
$device_info = $_SERVER['HTTP_USER_AGENT'];
$ip_address = $_SERVER['REMOTE_ADDR'];
$expires_at = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 days

// Optional: delete old sessions for this device (or skip if you want multiple)
$stmt = $conn->prepare("DELETE FROM tbl_sessions WHERE member_id = ? AND device_info = ?");
$stmt->bind_param("is", $user['Member_ID'], $device_info);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO tbl_sessions (session_id, member_id, device_info, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sisss", $session_id, $user['Member_ID'], $device_info, $ip_address, $expires_at);
$stmt->execute();

// Set cookie (30 days, secure, HTTP only)
setcookie("session_token", $session_id, [
    'expires' => time() + 60 * 60 * 24 * 30,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

echo json_encode([
    "success" => true,
    "member_id" => $user['Member_ID'],
    "name" => $user['Member_Name'],
    "email" => $user['Email'],
    "role" => $user['Member_Role']
]);
?>
