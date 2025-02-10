<?php
require_once '../adatbazis.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nev = $_POST['nev'] ?? '';
    $email = $_POST['email'] ?? '';
    $jelszo = $_POST['jelszo'] ?? '';
    $jelszoMegerosites = $_POST['jelszo_megerosites'] ?? '';
    $aszf = $_POST['aszf'] ?? '';

    if (empty($nev) || empty($email) || empty($jelszo) || empty($jelszoMegerosites) || empty($aszf)) {
        echo json_encode(["success" => false, "message" => "Minden mező kitöltése kötelező.", "type" => "error"]);
        exit;
    }

    if ($jelszo !== $jelszoMegerosites) {
        echo json_encode(["success" => false, "message" => "A két jelszó nem egyezik.", "type" => "error"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/^[^@]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/", $email)) {
        echo json_encode(["success" => false, "message" => "Az email cím nem érvényes.", "type" => "error"]);
        exit;
    }

    if ($aszf !== 'on') {
        echo json_encode(["success" => false, "message" => "Az ÁSZF elfogadása kötelező.", "type" => "error"]);
        exit;
    }

    $hashedPassword = password_hash($jelszo, PASSWORD_DEFAULT);
    $rang = "Felhasználó";

    // Ellenőrizzük, hogy az email és a felhasználónév már létezik-e
    $stmtEmail = $pdo->prepare("SELECT COUNT(*) FROM felhasznalok WHERE email = ?");
    $stmtEmail->execute([$email]);
    $emailExists = $stmtEmail->fetchColumn() > 0;

    $stmtNev = $pdo->prepare("SELECT COUNT(*) FROM felhasznalok WHERE nev = ?");
    $stmtNev->execute([$nev]);
    $nevExists = $stmtNev->fetchColumn() > 0;

    if ($emailExists && $nevExists) {
        echo json_encode(["success" => false, "message" => "A megadott név és email már használatban van.", "type" => "error"]);
        exit;
    }

    if ($emailExists) {
        echo json_encode(["success" => false, "message" => "Az email cím már használatban van.", "type" => "error"]);
        exit;
    }

    if ($nevExists) {
        echo json_encode(["success" => false, "message" => "A felhasználónév már használatban van.", "type" => "error"]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO felhasznalok (nev, email, jelszo, rang) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nev, $email, $hashedPassword, $rang])) {
        $felhasznaloId = $pdo->lastInsertId();
        $stmtPersely = $pdo->prepare("INSERT INTO persely (felhasznalo_id, egyenleg) VALUES (?, ?)");
        $alapertek = 0;
        if ($stmtPersely->execute([$felhasznaloId, $alapertek])) {
            echo json_encode(["success" => true, "message" => "Sikeres regisztráció", "type" => "success"]);
        } else {
            echo json_encode(["success" => false, "message" => "Hiba történt", "type" => "error"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Hiba történt a regisztráció során.", "type" => "error"]);
    }
}
?>
