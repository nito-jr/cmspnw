<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode(['status'=>'error','message'=>'Unauthorized']));

$total    = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='member'")->fetch_assoc()['c'];
$complete = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='member' AND balance_due<=0")->fetch_assoc()['c'];
$owing    = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='member' AND balance_due>0")->fetch_assoc()['c'];
$debt     = $conn->query("SELECT SUM(balance_due) as t FROM users WHERE role='member'")->fetch_assoc()['t'] ?? 0;

echo json_encode([
    'status'        => 'success',
    'total_members' => $total,
    'complete_count'=> $complete,
    'owing_count'   => $owing,
    'complete_pct'  => $total>0 ? round(($complete/$total)*100,1) : 0,
    'owing_pct'     => $total>0 ? round(($owing/$total)*100,1)    : 0,
    'total_debt'    => $debt,
]);
?>
