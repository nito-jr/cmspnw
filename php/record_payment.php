<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode(['status'=>'error','message'=>'Unauthorized']));

$data   = json_decode(file_get_contents("php://input"), true);
$userId = intval($data['user_id'] ?? 0);
$amount = floatval($data['amount'] ?? 0);
$allowedTypes = ['application','dues','platform_charge'];
$paymentType  = (isset($data['payment_type']) && in_array($data['payment_type'], $allowedTypes))
    ? $data['payment_type'] : 'dues';

if ($userId === 0) die(json_encode(['status'=>'error','message'=>'Invalid member ID.']));
if ($amount <= 0)  die(json_encode(['status'=>'error','message'=>'Amount must be greater than zero.']));

$balRes     = $conn->query("SELECT balance_due FROM users WHERE id=$userId");
$currentBal = floatval($balRes->fetch_assoc()['balance_due']);

$stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_type, status) VALUES (?,?,'$paymentType','approved')");
$stmt->bind_param("id", $userId, $amount);
if (!$stmt->execute()) die(json_encode(['status'=>'error','message'=>'Failed: '.$conn->error]));

$upd = $conn->prepare("UPDATE users SET balance_due = balance_due - ? WHERE id=?");
$upd->bind_param("di", $amount, $userId);
$upd->execute();

$newBal = floatval($conn->query("SELECT balance_due FROM users WHERE id=$userId")->fetch_assoc()['balance_due']);

$warning = null;
if ($amount > $currentBal && $currentBal > 0)
    $warning = "⚠️ Overpayment: " . number_format($amount - $currentBal, 0) . " XAF above balance. New balance: " . number_format($newBal, 0) . " XAF.";
elseif ($currentBal <= 0)
    $warning = "⚠️ Member had no outstanding balance. Payment recorded but please verify.";

echo json_encode(['status'=>'success','message'=>'Cash payment recorded!','new_balance'=>$newBal,'warning'=>$warning]);
?>
