<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode([]));

$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id === 0) die(json_encode([]));

$stmt = $conn->prepare("SELECT * FROM payments WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = [];
$res  = $stmt->get_result();
while ($row = $res->fetch_assoc()) $data[] = $row;
echo json_encode($data);
?>
