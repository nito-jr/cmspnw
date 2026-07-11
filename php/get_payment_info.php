<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) die(json_encode(['status'=>'error','message'=>'Unauthorized']));

echo json_encode([
    'status'       => 'success',
    'momo_number'  => COUNCIL_MOMO_NUMBER,
    'om_number'    => COUNCIL_OM_NUMBER,
    'account_name' => COUNCIL_ACCOUNT_NAME,
]);
?>
