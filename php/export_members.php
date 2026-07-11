<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator'])) die('Unauthorized');

$data    = json_decode(file_get_contents("php://input"), true);
$where   = "role='member'";
if (isset($data['ids']) && is_array($data['ids']) && !empty($data['ids'])) {
    $ids   = implode(',', array_map('intval', $data['ids']));
    $where = "id IN ($ids)";
}

$result   = $conn->query("SELECT * FROM users WHERE $where ORDER BY name ASC");
$filename = 'CMSP_Export_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$out = fopen('php://output','w');
fputs($out, "\xEF\xBB\xBF");
fputcsv($out, ['Name','Email','Phone','Address','Profession','School','Graduation Year','License','Reg Year','Status','Trans_Date','Trans_Amount','Trans_Type','Trans_Status']);
while ($m = $result->fetch_assoc()) {
    $base = [$m['name'],$m['email'],$m['phone'],$m['address'],$m['profession'],$m['school'],$m['year_graduation'],$m['license_number'],$m['year_registration'],$m['status']];
    $pays = $conn->query("SELECT created_at,amount,payment_type,status FROM payments WHERE user_id={$m['id']} ORDER BY created_at DESC");
    if ($pays->num_rows>0) {
        while ($p=$pays->fetch_assoc()) {
            $r=$base; $r[]=$p['created_at']; $r[]=$p['amount']; $r[]=$p['payment_type']; $r[]=$p['status'];
            fputcsv($out,$r);
        }
    } else { $r=$base; $r[]=''; $r[]=''; $r[]=''; $r[]=''; fputcsv($out,$r); }
}
fclose($out); exit;
?>
