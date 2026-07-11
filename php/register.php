<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name       = trim($_POST['name']              ?? '');
    $email      = trim($_POST['email']             ?? '');
    $phone      = trim($_POST['phone']             ?? '');
    $address    = trim($_POST['address']           ?? '');
    $profession = trim($_POST['profession']        ?? '');
    $school     = trim($_POST['school']             ?? '');
    $year_grad  = intval($_POST['year_graduation'] ?? date('Y'));
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $currentYear    = date("Y");
    $fees           = calculateFees($year_grad, $currentYear);
    $initialBalance = $fees['application'] + $fees['dues'] + $fees['platform_charge'];

    $photoName = 'default.jpg';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
            $photoName = uniqid() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photoName);
        }
    }

    $platformCharge = $fees['platform_charge'];
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, phone, address, password, profession, school, year_graduation,
         photo, status, balance_due, platform_charge)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)"
    );
    $stmt->bind_param("sssssssisdd",
        $name, $email, $phone, $address, $password,
        $profession, $school, $year_grad, $photoName,
        $initialBalance, $platformCharge
    );

    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>
            'Application submitted! Total due: ' .
            number_format($fees['platform_charge']) . ' XAF platform + ' .
            number_format($fees['application'])     . ' XAF application + ' .
            number_format($fees['dues'])            . ' XAF dues = ' .
            number_format($initialBalance)          . ' XAF total.'
        ]);
    } elseif ($conn->errno == 1062) {
        echo json_encode(['status'=>'error','message'=>'Email already registered.']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Database error: ' . $conn->error]);
    }
}
?>
