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
        m.Member_Name, 
        ds.Shift, 
        sup.Member_Name AS Supervisor_Name, 
        ds.Duty_Date
    FROM 
        tbl_member_duty_records mdr
    JOIN 
        tbl_members m ON mdr.Member_ID = m.Member_ID
    JOIN 
        tbl_duty_shifts ds ON mdr.Duty_Shift_ID = ds.Duty_Shift_ID
    JOIN 
        tbl_members sup ON ds.Supervisor_ID = sup.Member_ID
    WHERE 
        m.Member_ID = ?;
');

$stmt->bind_param("s", $activeMemberID);
$stmt->execute();
$result = $stmt->get_result();

$memberDutyRecords = [];
while ($row = $result->fetch_assoc()) {
    $memberDutyRecords[] = [
        "member_name" => $row['Member_Name'],
        "shift" => $row['Shift'],
        "supervisor_name" => $row['Supervisor_Name'],
        "duty_date" => $row['Duty_Date']
    ];
}

if (!empty($memberDutyRecords)) {
    echo json_encode([
        "success" => true,
        "memberDutyRecords" => $memberDutyRecords
    ]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "No records found or session invalid."]);
}
?>
