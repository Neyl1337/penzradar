<?php
require_once '../adatbazis.php';

header('Content-Type: application/json');

if (isset($_GET['persely_id']) && isset($_GET['felhasznalo_nev'])) {
    $persely_id = (int)$_GET['persely_id'];
    $felhasznalo_nev = $_GET['felhasznalo_nev'];

    $stmt = $pdo->prepare("SELECT osszeg FROM perselyk WHERE ID = ? AND felhasznalo_nev = ?");
    $stmt->execute([$persely_id, $felhasznalo_nev]);
    $persely = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($persely) {
        echo json_encode(['osszeg' => $persely['osszeg']]);
    } else {
        echo json_encode(['osszeg' => 0]);
    }
} else {
    echo json_encode(['osszeg' => 0]);
}
exit;