<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode(['status'=>'error','message'=>'Unauthorized']));

$data = json_decode(file_get_contents("php://input"), true);
$id   = intval($data['id'] ?? 0);
if ($id === 0) die(json_encode(['status'=>'error','message'=>'Invalid Member ID.']));

$conn->begin_transaction();
try {
    $s1 = $conn->prepare("DELETE FROM payments WHERE user_id=?");
    $s1->bind_param("i", $id); $s1->execute();
    $s2 = $conn->prepare("DELETE FROM users WHERE id=?");
    $s2->bind_param("i", $id); $s2->execute();
    if ($s2->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['status'=>'success','message'=>'Member deleted.']);
    } else {
        $conn->rollback();
        echo json_encode(['status'=>'error','message'=>'Member not found.']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
