<?php
session_start();
include 'config.php';

$data     = json_decode(file_get_contents("php://input"));
$email    = $data->email    ?? '';
$password = $data->password ?? '';
$captcha  = isset($data->captcha) ? trim((string)$data->captcha) : '';

// Generic, non-specific error message so we never reveal whether the
// email exists, or whether the email or the password was the problem.
$genericError = 'Incorrect email or password.';

// --- Captcha check (must happen before we touch the DB) ---
if (!isset($_SESSION['captcha_answer']) || $captcha === '' || intval($captcha) !== intval($_SESSION['captcha_answer'])) {
    unset($_SESSION['captcha_answer']); // force a fresh captcha on the next attempt
    echo json_encode(['status' => 'error', 'message' => 'Incorrect captcha answer. Please try again.', 'refresh_captcha' => true]);
    exit;
}
// Captcha is single-use whether or not the login itself succeeds
unset($_SESSION['captcha_answer']);

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if (($row = $result->fetch_assoc()) && password_verify($password, $row['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['role']    = $row['role'];
    echo json_encode(['status' => 'success', 'role' => $row['role'], 'name' => $row['name']]);
} else {
    // Same message whether the email doesn't exist or the password is wrong
    echo json_encode(['status' => 'error', 'message' => $genericError, 'refresh_captcha' => true]);
}
?>
