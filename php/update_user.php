<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode(['status'=>'error','message'=>'Unauthorized']));

$data   = json_decode(file_get_contents("php://input"), true);
$userId = intval($data['id'] ?? 0);
if ($userId === 0) die(json_encode(['status'=>'error','message'=>'Invalid member ID.']));

$simpleFields = [
    'license'          => 'license_number',
    'school'           => 'school',
    'phone'            => 'phone',
    'address'          => 'address',
    'year_graduation'  => 'year_graduation',
    'year_registration'=> 'year_registration',
];

foreach ($simpleFields as $key => $col) {
    if (isset($data[$key])) {
        $val  = $conn->real_escape_string(trim($data[$key]));
        $res  = $conn->query("UPDATE users SET $col='$val' WHERE id=$userId");
        echo $res
            ? json_encode(['status'=>'success','message'=>ucfirst(str_replace('_',' ',$key)).' updated!'])
            : json_encode(['status'=>'error','message'=>'DB Error: '.$conn->error]);
        exit;
    }
}

if (isset($data['email'])) {
    $val = trim($data['email']);
    if (!filter_var($val, FILTER_VALIDATE_EMAIL)) { echo json_encode(['status'=>'error','message'=>'Invalid email.']); exit; }
    $chk = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
    $chk->bind_param("si",$val,$userId); $chk->execute();
    if ($chk->get_result()->num_rows>0) { echo json_encode(['status'=>'error','message'=>'Email already in use.']); exit; }
    $stmt = $conn->prepare("UPDATE users SET email=? WHERE id=?");
    $stmt->bind_param("si",$val,$userId);
    echo $stmt->execute() ? json_encode(['status'=>'success','message'=>'Email updated!']) : json_encode(['status'=>'error','message'=>'DB Error: '.$conn->error]);
    exit;
}

if (isset($data['password'])) {
    $p = trim($data['password']);
    if (strlen($p)<4) { echo json_encode(['status'=>'error','message'=>'Password too short.']); exit; }
    $h = password_hash($p, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si",$h,$userId);
    echo $stmt->execute() ? json_encode(['status'=>'success','message'=>'Password updated!']) : json_encode(['status'=>'error','message'=>'DB Error: '.$conn->error]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'No valid field to update.']);
?>
