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

if (!isset($data['memberID'], $data['email'], $data['password'], $data['confirmPassword'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields."]);
    exit();
}

$memberID = intval($data['memberID']);
$email = trim($data['email']);
$password = $data['password'];
$confirmPassword = $data['confirmPassword'];

try {
    // Check if member exists
    $stmt = $conn->prepare("SELECT * FROM tbl_members WHERE Member_ID = ?");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $memberID);
    $stmt->execute();
    $memberResult = $stmt->get_result();

    if ($memberResult->num_rows === 0) {
        http_response_code(400);
        echo json_encode(["error" => "Member does not exist."]);
        exit();
    }

    // Check if member already has an account
    $stmt = $conn->prepare("SELECT * FROM tbl_accounts WHERE member_id = ?");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $memberID);
    $stmt->execute();
    $accountResult = $stmt->get_result();

    if ($accountResult->num_rows > 0) {
        http_response_code(400);
        echo json_encode(["error" => "This member already has an account."]);
        exit();
    }

    // Check if passwords match
    if ($password !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(["error" => "Passwords do not match."]);
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new account
    $stmt = $conn->prepare("INSERT INTO tbl_accounts (member_id, email, password) VALUES (?, ?, ?)");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("iss", $memberID, $email, $hashedPassword);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["message" => "Account successfully registered."]);
    } else {
        throw new Exception("Failed to register account: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
}

$conn->close();
?>
