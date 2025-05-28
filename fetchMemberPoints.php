<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$activeMemberID = $data['Member_ID'];

// Use "i" if Member_ID is an integer
$stmt = $conn->prepare('
SELECT 
    (SELECT COUNT(*) 
        FROM tbl_member_duty_records 
        WHERE Member_ID = ?) 
    AS member_duty_points, 
    (SELECT SUM(p.Product_Points * pr.Quantity) 
        FROM tbl_purchase_records pr 
        JOIN tbl_products p 
        ON pr.Product_ID = p.Product_ID 
        WHERE pr.Member_ID = ?) 
    AS member_purchase_points;
');
$stmt->bind_param("ii", $activeMemberID, $activeMemberID);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "member_duty_points" => $row["member_duty_points"],
        "member_purchase_points" => round($row["member_purchase_points"], 2),
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "member_duty_points" => 0,
        "member_purchase_points" => 0
    ]);
}
?>
