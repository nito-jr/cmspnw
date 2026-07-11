<?php
ini_set('display_errors', 1);
session_start();
include 'config.php';

function getPost($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator'])) {
    die(json_encode(['status'=>'error','message'=>'Unauthorized']));
}

$name         = getPost('name');
$password_raw = getPost('password');

if (empty($name) || empty($password_raw)) {
    die(json_encode(['status'=>'error','message'=>'Name and Password are required.']));
}

$email = getPost('email');
if (empty($email)) $email = "member_" . uniqid() . "@cmsp.temp";

$phone       = getPost('phone');
$address     = getPost('address');
$profession  = getPost('profession', 'Other');
$school      = getPost('school');
$year_grad   = intval(getPost('year_graduation', date("Y")));
$license     = getPost('license_number');
$password    = password_hash($password_raw, PASSWORD_DEFAULT);
$currentYear = date("Y");

$fees    = calculateFees($year_grad, $currentYear);
$balance = $fees['application'] + $fees['dues'] + $fees['platform_charge'];
$platformCharge = $fees['platform_charge'];

$photoName = 'default.jpg';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
        $photoName = "user_" . time() . "." . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photoName);
    }
}

$name    = $conn->real_escape_string($name);
$email   = $conn->real_escape_string($email);
$phone   = $conn->real_escape_string($phone);
$address = $conn->real_escape_string($address);
$profession = $conn->real_escape_string($profession);
$school  = $conn->real_escape_string($school);
$license = $conn->real_escape_string($license);

$sql = "INSERT INTO users
            (name, email, phone, address, password, profession, school, year_graduation,
             status, license_number, year_registration, balance_due, platform_charge, role, photo)
        VALUES
            ('$name','$email','$phone','$address','$password','$profession','$school',
             '$year_grad','approved','$license','$currentYear','$balance','$platformCharge','member','$photoName')";

if ($conn->query($sql)) {
    echo json_encode(['status'=>'success','message'=>'Member added successfully!']);
} else {
    echo json_encode(['status'=>'error','message'=>'Database Error: ' . $conn->error]);
}
?>
