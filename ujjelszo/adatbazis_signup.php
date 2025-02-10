<?php
require_once '../adatbazis.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        echo json_encode(["success" => false, "message" => "Az email cím megadása kötelező.", "type" => "error"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Az email cím nem érvényes.", "type" => "error"]);
        exit;
    }

    $stmtEmail = $pdo->prepare("SELECT COUNT(*) FROM felhasznalok WHERE email = ?");
    $stmtEmail->execute([$email]);
    if ($stmtEmail->fetchColumn() == 0) {
        echo json_encode(["success" => false, "message" => "Az email cím nem található.", "type" => "error"]);
        exit;
    }

    $kod = rand(100000, 999999);
    $_SESSION['email'] = $email;
    $_SESSION['kod'] = $kod;

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
        $mail->addAddress($email);
        $mail->Subject = 'Jelszó visszaállítási kód';
        $mail->CharSet = 'UTF-8';
        $mail->Body = "Az új jelszóhoz használd ezt a kódot: $kod\n\nÜdvözlettel, PénzRadar csapata";
        $mail->send();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Hiba történt az email küldése során.", "type" => "error"]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Kód elküldve!", "redirect" => "megerosites.php"]);
}
?>
