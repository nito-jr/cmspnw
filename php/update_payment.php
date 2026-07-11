<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode(['status'=>'error','message'=>'Unauthorized']));

$data   = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

// 1. Approve Member (generate license)
if ($action === 'approve') {
    $id      = intval($data['id']);
    $license = "CMSP-" . date("Y") . "-" . $id;
    $year    = intval(date("Y"));
    $stmt    = $conn->prepare("UPDATE users SET status='approved', license_number=?, year_registration=? WHERE id=?");
    $stmt->bind_param("sii", $license, $year, $id);
    $stmt->execute();
    echo json_encode(['status'=>'success','message'=>"Member approved! License: $license"]);
    exit;
}

// 2. Approve Payment Proof
if ($action === 'approve_payment') {
    $payId = intval($data['payment_id']);
    $memId = intval($data['member_id']);

    $res = $conn->query("SELECT amount, status FROM payments WHERE id=$payId");
    $pay = $res->fetch_assoc();
    if (!$pay) { echo json_encode(['status'=>'error','message'=>'Payment not found.']); exit; }
    if ($pay['status']==='approved') { echo json_encode(['status'=>'error','message'=>'Already approved.']); exit; }

    $amt        = floatval($pay['amount']);
    $currentBal = floatval($conn->query("SELECT balance_due FROM users WHERE id=$memId")->fetch_assoc()['balance_due']);

    $conn->query("UPDATE payments SET status='approved' WHERE id=$payId");
    $upd = $conn->prepare("UPDATE users SET balance_due = balance_due - ? WHERE id=?");
    $upd->bind_param("di", $amt, $memId);
    $upd->execute();

    $newBal = floatval($conn->query("SELECT balance_due FROM users WHERE id=$memId")->fetch_assoc()['balance_due']);

    $warning = null;
    if ($amt > $currentBal && $currentBal > 0)
        $warning = "⚠️ Overpayment of " . number_format($amt - $currentBal, 0) . " XAF. Balance is now " . number_format($newBal, 0) . " XAF.";
    elseif ($currentBal <= 0)
        $warning = "⚠️ Member had no outstanding balance. Please verify.";

    echo json_encode(['status'=>'success','message'=>$warning ?? 'Payment verified & balance updated!','new_balance'=>$newBal,'warning'=>$warning]);
    exit;
}

// 3. Decline Payment
if ($action === 'decline_payment') {
    $payId = intval($data['payment_id']);
    $res   = $conn->query("SELECT status FROM payments WHERE id=$payId");
    $pay   = $res->fetch_assoc();
    if (!$pay) { echo json_encode(['status'=>'error','message'=>'Payment not found.']); exit; }
    if ($pay['status'] !== 'pending') { echo json_encode(['status'=>'error','message'=>'Only pending payments can be declined.']); exit; }
    $conn->query("UPDATE payments SET status='rejected' WHERE id=$payId");
    echo json_encode(['status'=>'success','message'=>'Payment declined.']);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Unknown action.']);
?>
