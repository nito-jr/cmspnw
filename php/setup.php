<?php
include 'config.php';

$plain    = "counciladmin";
$hashed   = password_hash($plain, PASSWORD_DEFAULT);
$check    = $conn->query("SELECT id FROM users WHERE email='admin@cmsp.com'");

if ($check->num_rows == 0) {
    $sql = "INSERT INTO users (name,email,password,role,status,license_number,year_graduation) VALUES
            ('Admin User','admin@cmsp.com','$hashed','admin','approved','ADM001',2020),
            ('Operator User','operator@cmsp.com','$hashed','operator','approved','OPT001',2020)";
    if ($conn->query($sql)) {
        echo "<h2>✅ Setup Complete</h2>
              <p><b>Admin:</b> admin@cmsp.com / counciladmin</p>
              <p><b>Operator:</b> operator@cmsp.com / counciladmin</p>
              <a href='../index.html'>Go to Login</a>
              <br><br><b style='color:red'>⚠️ Delete this file (setup.php) after first login!</b>";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "<h2>Accounts already exist.</h2><a href='../index.html'>Go to Login</a>";
}
?>
