<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode([]));

// Include proof_image so the notification panel can show it for validation
$sql = "SELECT p.id, p.user_id, p.amount, p.payment_type, p.proof_image, p.transaction_id,
               p.sender_name, p.sender_phone, p.created_at,
               u.name, u.profession, u.balance_due
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC LIMIT 20";

$data = [];
$res  = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $row['is_overpayment'] = (floatval($row['amount']) > floatval($row['balance_due']));
    $data[] = $row;
}
echo json_encode($data);
?>