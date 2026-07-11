<?php
// Generates a simple math captcha and stores the answer in the session.
// The login form calls this on load (and after a failed attempt) to get a fresh question.
session_start();

$a = random_int(1, 10);
$b = random_int(1, 10);

// Occasionally use subtraction so the answer isn't always "add these two small numbers"
$op = random_int(0, 1) === 0 ? '+' : '-';
if ($op === '-' && $a < $b) { // keep the result non-negative
    [$a, $b] = [$b, $a];
}
$answer = $op === '+' ? ($a + $b) : ($a - $b);

$_SESSION['captcha_answer'] = $answer;

header('Content-Type: application/json');
echo json_encode(['question' => "$a $op $b = ?"]);
?>
