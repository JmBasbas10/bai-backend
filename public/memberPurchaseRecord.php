<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$activeMemberID = $data['Member_ID'];

$stmt = $conn->prepare('
    SELECT 
        m.Member_Name AS Member_Name,
        p.Product_Name,
        p.Product_Type,
        pr.Quantity,
        p.Product_Price,
        (pr.Quantity * p.Product_Price) AS Total_Price,
        sup.Member_Name AS Supervisor_Name,
        ds.Duty_Date
    FROM 
        tbl_purchase_records pr
    JOIN tbl_members m ON pr.Member_ID = m.Member_ID
    JOIN tbl_products p ON pr.Product_ID = p.Product_ID
    JOIN tbl_duty_shifts ds ON pr.Shift_ID = ds.Duty_Shift_ID
    JOIN tbl_members sup ON ds.Supervisor_ID = sup.Member_ID
    WHERE pr.Member_ID = ?
    ORDER BY pr.Purchase_ID DESC;

');

$stmt->bind_param("s", $activeMemberID);
$stmt->execute();
$result = $stmt->get_result();

$memberPurchaseRecords = [];
while ($row = $result->fetch_assoc()) {
    $memberPurchaseRecords[] = [
        "member_name" => $row['Member_Name'],
        "product_name" => $row['Product_Name'],
        "product_type" => $row['Product_Type'],
        "quantity" => $row['Quantity'],
        "product_price" => $row['Product_Price'],
        "total_price" => $row['Total_Price'],
        "supervisor_name" => $row['Supervisor_Name'],
        "duty_date" => $row['Duty_Date'],
    ];
}

if (!empty($memberPurchaseRecords)) {
    echo json_encode([
        "success" => true,
        "memberPurchaseRecords" => $memberPurchaseRecords
    ]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "No records found or session invalid."]);
}

?>
