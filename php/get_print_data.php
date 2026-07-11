<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode(['status'=>'error','message'=>'Unauthorized']));

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['ids']) || !is_array($input['ids']))
    die(json_encode(['status'=>'error','message'=>'No members selected']));

$ids    = implode(',', array_map('intval', $input['ids']));
$result = $conn->query("SELECT * FROM users WHERE id IN ($ids)");
$data   = [];
while ($row = $result->fetch_assoc()) { unset($row['password']); $data[] = $row; }
echo json_encode(['status'=>'success','data'=>$data]);
?>
