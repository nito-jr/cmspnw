<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) die(json_encode(['status'=>'error','message'=>'Not logged in']));

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];

function sanitizeRow($row) { unset($row['password']); return $row; }

function getFeeBreakdown($user, $conn) {
    $currentYear = date("Y");
    $gradYear    = intval($user['year_graduation']);
    $regYear     = intval($user['year_registration'] ?? $currentYear);

    $appDiff = $regYear - $gradYear;
    if ($appDiff == 0)                    $appFee = 10000;
    elseif ($appDiff >= 2 && $appDiff < 5) $appFee = 20000;
    elseif ($appDiff >= 5)               $appFee = 50000;
    else                                 $appFee = 10000;

    if ($currentYear == $gradYear) {
        $dues = 0;
    } else {
        $startArrears    = $gradYear + 1;
        $endArrears      = $currentYear - 1;
        $numArrearsYears = ($endArrears >= $startArrears) ? ($endArrears - $startArrears) + 1 : 0;
        $dues = ($numArrearsYears * 10000) + 6000;
    }

    $platformCharge = floatval($user['platform_charge'] ?? PLATFORM_CHARGE);
    $grandTotal     = $platformCharge + $appFee + $dues;

    $uid  = intval($user['id']);
    $pres = $conn->query(
        "SELECT payment_type, SUM(amount) as total FROM payments
         WHERE user_id=$uid AND status='approved' GROUP BY payment_type"
    );
    $paid = ['platform_charge'=>0,'application'=>0,'dues'=>0];
    while ($pr = $pres->fetch_assoc()) $paid[$pr['payment_type']] = floatval($pr['total']);

    $remaining = [
        'platform_charge' => max(0, $platformCharge  - $paid['platform_charge']),
        'application'     => max(0, $appFee          - $paid['application']),
        'dues'            => max(0, $dues             - $paid['dues']),
    ];

    $nextType = null;
    foreach (['platform_charge','application','dues'] as $t) {
        if ($remaining[$t] > 0) { $nextType = $t; break; }
    }

    return [
        'platform_charge'        => $platformCharge,
        'application_fee'        => $appFee,
        'dues'                   => $dues,
        'grand_total'            => $grandTotal,
        'balance_due'            => floatval($user['balance_due']),
        'paid_platform'          => $paid['platform_charge'],
        'paid_application'       => $paid['application'],
        'paid_dues'              => $paid['dues'],
        'remaining_platform'     => $remaining['platform_charge'],
        'remaining_application'  => $remaining['application'],
        'remaining_dues'         => $remaining['dues'],
        'next_payment_type'      => $nextType,
    ];
}

// Single member by ID
if (isset($_GET['id'])) {
    $targetId = intval($_GET['id']);
    if ($role === 'member' && $targetId !== $userId)
        die(json_encode(['status'=>'error','message'=>'Unauthorized']));
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $targetId);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $row = sanitizeRow($row);
        echo json_encode(array_merge($row, getFeeBreakdown($row, $conn)));
    } else {
        echo json_encode(['status'=>'error','message'=>'Member not found']);
    }
    exit;
}

// Admin/Operator list
if (in_array($role, ['admin','operator'])) {
    $search     = isset($_GET['search'])     ? $conn->real_escape_string($_GET['search'])     : '';
    $profession = isset($_GET['profession']) ? $conn->real_escape_string($_GET['profession']) : '';
    $status     = $_GET['status'] ?? '';
    $sort       = $_GET['sort']   ?? '';

    $sql = "SELECT * FROM users WHERE role='member'";
    if (!empty($search))     $sql .= " AND (name LIKE '%$search%' OR license_number LIKE '%$search%' OR email LIKE '%$search%')";
    if (!empty($profession)) $sql .= " AND profession='$profession'";
    if ($status==='owing')    $sql .= " AND balance_due>0";
    elseif ($status==='complete') $sql .= " AND balance_due<=0";

    $orderBy = "ORDER BY created_at DESC";
    if ($sort==='name_asc')       $orderBy="ORDER BY name ASC";
    elseif ($sort==='name_desc')  $orderBy="ORDER BY name DESC";
    elseif ($sort==='balance_high') $orderBy="ORDER BY balance_due DESC";
    elseif ($sort==='balance_low')  $orderBy="ORDER BY balance_due ASC";
    $sql .= " $orderBy";

    $data = [];
    $res  = $conn->query($sql);
    while ($row = $res->fetch_assoc()) $data[] = sanitizeRow($row);
    echo json_encode($data);
} else {
    // Member own profile
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $row = sanitizeRow($row);
    echo json_encode(array_merge($row, getFeeBreakdown($row, $conn)));
}
?>
