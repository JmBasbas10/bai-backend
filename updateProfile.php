<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Allow requests from any origin (for development)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['memberID'], $data['email'], $data['name'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields."]);
    exit();
}

$memberID = intval($data['memberID']);
$email = trim($data['email']);
$name = $data['name'];

try {

    $stmt = $conn->prepare("
        UPDATE tbl_members m
        JOIN tbl_accounts a
        ON m.Member_ID = a.Member_ID
        SET
            m.Member_Name = ?,
            a.Email = ?
        WHERE m.Member_ID  = ?
    ");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("iss", $email, $name, $memberID);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["message" => "Profile Successfuly Edited."]);
    } else {
        throw new Exception("Failed to register account: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
}

$conn->close();
?>
