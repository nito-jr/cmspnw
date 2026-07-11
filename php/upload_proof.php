<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) die(json_encode(['status'=>'error','message'=>'Unauthorized']));

$user_id     = intval($_SESSION['user_id']);
$amount      = floatval($_POST['amount'] ?? 0);
$allowedTypes= ['application','dues','platform_charge'];
$paymentType = (isset($_POST['payment_type']) && in_array($_POST['payment_type'], $allowedTypes))
    ? $_POST['payment_type'] : 'dues';

$transactionId = trim($_POST['transaction_id'] ?? '');
$senderName    = trim($_POST['sender_name']    ?? '');
$senderPhone   = trim($_POST['sender_phone']   ?? '');

if ($amount <= 0) die(json_encode(['status'=>'error','message'=>'Invalid amount.']));
if ($senderPhone === '') die(json_encode(['status'=>'error','message'=>'Sender phone number is required so we can match your payment.']));

if (isset($_FILES['proof']) && $_FILES['proof']['error']==0) {
    $allowedExts  = ['jpg','jpeg','png','gif','webp','pdf'];
    $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
    $ext  = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
    $finfo= finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['proof']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes))
        die(json_encode(['status'=>'error','message'=>'Invalid file type. Allowed: JPG, PNG, PDF.']));

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
    $fileName = "proof_{$user_id}_" . time() . ".$ext";
    if (move_uploaded_file($_FILES['proof']['tmp_name'], UPLOAD_DIR.$fileName)) {
        $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_type, proof_image, transaction_id, sender_name, sender_phone) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("idsssss", $user_id, $amount, $paymentType, $fileName, $transactionId, $senderName, $senderPhone);
        echo $stmt->execute()
            ? json_encode(['status'=>'success','message'=>'Proof uploaded! Awaiting verification.'])
            : json_encode(['status'=>'error','message'=>'DB error: '.$conn->error]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to save file.']);
    }
} else {
    echo json_encode(['status'=>'error','message'=>'No file uploaded.']);
}
?>