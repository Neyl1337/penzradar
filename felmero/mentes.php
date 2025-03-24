<?php
session_start();

header('Content-Type: application/json');

$bejovo = json_decode(file_get_contents('php://input'), true);
$szerepkor = $bejovo['szerepkor'];

if ($szerepkor) {
    $_SESSION['szerepkor'] = $szerepkor;
    echo json_encode(['siker' => true]);
} else {
    echo json_encode(['siker' => false]);
}
?>