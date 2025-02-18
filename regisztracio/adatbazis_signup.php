<?php
require_once '../adatbazis.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nev = $_POST['nev'] ?? '';
    $email = $_POST['email'] ?? '';
    $jelszo = $_POST['jelszo'] ?? '';
    $jelszo_megerosites = $_POST['jelszo_megerosites'] ?? '';
    $aszf = $_POST['aszf'] ?? '';

    if (empty($nev) || empty($email) || empty($jelszo) || empty($jelszo_megerosites) || empty($aszf)) {
        echo json_encode(["success" => false, "message" => "Minden mező kitöltése kötelező.", "type" => "error"]);
        exit;
    }

    if ($jelszo !== $jelszo_megerosites) {
        echo json_encode(["success" => false, "message" => "A két jelszó nem egyezik.", "type" => "error"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Az email cím nem érvényes.", "type" => "error"]);
        exit;
    }

    if ($aszf !== 'on') {
        echo json_encode(["success" => false, "message" => "Az ÁSZF elfogadása kötelező.", "type" => "error"]);
        exit;
    }

    if (!preg_match("/^[a-zA-Z0-9]{3,}$/", $nev)) {
        echo json_encode(["success" => false, "message" => "A név legalább 3 karakter kell legyen betűkből és számokból!", "type" => "error"]);
        exit;
    }    

    $hashedPassword = password_hash($jelszo, PASSWORD_DEFAULT);
    
    $stmtEmail = $pdo->prepare("SELECT COUNT(*) FROM felhasznalok WHERE email = ?");
    $stmtEmail->execute([$email]);
    if ($stmtEmail->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "Az email cím már használatban van.", "type" => "error"]);
        exit;
    }

    $stmtNev = $pdo->prepare("SELECT COUNT(*) FROM felhasznalok WHERE nev = ?");
    $stmtNev->execute([$nev]);
    if ($stmtNev->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "A felhasználónév már használatban van.", "type" => "error"]);
        exit;
    }

    $kod = rand(100000, 999999);
    $_SESSION['reg_nev'] = $nev;
    $_SESSION['reg_email'] = $email;
    $_SESSION['reg_jelszo'] = $hashedPassword;
    $_SESSION['reg_kod'] = $kod;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'penzradar.hu@gmail.com';
        $mail->Password = 'obvgamdjyxnqmwrv';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
    
        $mail->setFrom('penzradar.hu@gmail.com', 'PénzRadar');
        $mail->addAddress($email, $nev);
        $mail->Subject = 'Regisztrációs kód';
        $mail->CharSet = 'UTF-8';
        $mail->Body = "Kedves $nev,\n\nA regisztráció befejezéséhez használd ezt a kódot: $kod\n\nÜdvözlettel, PénzRadar csapata";
        $mail->send();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Hiba történt az email küldése során.", "type" => "error"]);
        exit;
    }
    

    echo json_encode(["success" => true, "message" => "Kód elküldve!", "redirect" => "megerosites.php"]);
}
