<?php
/**
 * Direct Mobile Money collection (MTN MoMo / Orange Money) via MeSomb.
 *
 * Requires the MeSomb PHP SDK on the server:
 *   composer require hachther/mesomb
 *
 * Docs: https://mesomb.hachther.com/en/api/v1.1/schemes/
 */
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die(json_encode([
        'status'  => 'error',
        'message' => 'Mobile Money is not configured on this server yet (missing MeSomb SDK). Please use "I already paid" instead, or contact support.'
    ]));
}
require $autoload;

use MeSomb\Operation\PaymentOperation;

$userId      = intval($_SESSION['user_id']);
$data        = json_decode(file_get_contents("php://input"), true);
$amount      = floatval($data['amount'] ?? 0);
$service     = strtoupper(trim($data['service'] ?? '')); // MTN or ORANGE
$phone       = preg_replace('/\D/', '', $data['phone'] ?? '');
$allowedTypes= ['application', 'dues', 'platform_charge'];
$paymentType = (isset($data['payment_type']) && in_array($data['payment_type'], $allowedTypes))
    ? $data['payment_type'] : 'dues';

if ($amount <= 0) die(json_encode(['status' => 'error', 'message' => 'Invalid amount.']));
if (!in_array($service, ['MTN', 'ORANGE'])) die(json_encode(['status' => 'error', 'message' => 'Select MTN or Orange Money.']));
if (strlen($phone) < 9) die(json_encode(['status' => 'error', 'message' => 'Enter a valid phone number.']));

// Normalize to local 9-digit MSISDN MeSomb expects (237XXXXXXXXX also accepted)
if (strlen($phone) === 9) $phone = '237' . $phone;

try {
    $client = new PaymentOperation(MESOMB_APPLICATION_KEY, MESOMB_ACCESS_KEY, MESOMB_SECRET_KEY);

    $response = $client->makeCollect([
        'amount'   => $amount,
        'service'  => $service,
        'payer'    => $phone,
        'nonce'    => bin2hex(random_bytes(16)),
        'trxID'    => 'CMSP-' . $userId . '-' . time(),
        'customer' => ['phone' => $phone],
        'products' => [[
            'name'     => ucfirst(str_replace('_', ' ', $paymentType)),
            'category' => 'Membership Fee',
            'quantity' => 1,
            'amount'   => $amount,
        ]],
    ]);

    $isSuccess = method_exists($response, 'isOperationSuccess') && $response->isOperationSuccess()
        && method_exists($response, 'isTransactionSuccess') && $response->isTransactionSuccess();

    if (!$isSuccess) {
        $reason = method_exists($response, 'getMessage') ? $response->getMessage() : 'Transaction was not completed.';
        echo json_encode(['status' => 'error', 'message' => 'Payment failed: ' . $reason]);
        exit;
    }

    // Record as an approved payment straight away (MeSomb already confirmed funds)
    $balRes     = $conn->query("SELECT balance_due FROM users WHERE id=$userId");
    $currentBal = floatval($balRes->fetch_assoc()['balance_due']);

    $reference = method_exists($response, 'getReference') ? $response->getReference() : null;

    $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_type, status, proof_image) VALUES (?,?,?,'approved',?)");
    $note = 'MeSomb:' . $service . ($reference ? ':' . $reference : '');
    $stmt->bind_param("idss", $userId, $amount, $paymentType, $note);
    $stmt->execute();

    $upd = $conn->prepare("UPDATE users SET balance_due = balance_due - ? WHERE id=?");
    $upd->bind_param("di", $amount, $userId);
    $upd->execute();

    $newBal = floatval($conn->query("SELECT balance_due FROM users WHERE id=$userId")->fetch_assoc()['balance_due']);

    $warning = null;
    if ($amount > $currentBal && $currentBal > 0) {
        $warning = "Overpayment of " . number_format($amount - $currentBal, 0) . " XAF. New balance: " . number_format($newBal, 0) . " XAF.";
    } elseif ($currentBal <= 0) {
        $warning = "You had no outstanding balance for this fee type. Payment recorded — please contact support if this is unexpected.";
    }

    echo json_encode([
        'status'      => 'success',
        'message'     => 'Payment received via ' . $service . ' Mobile Money!',
        'new_balance' => $newBal,
        'warning'     => $warning,
    ]);

} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Mobile Money error: ' . $e->getMessage()]);
}
?>
