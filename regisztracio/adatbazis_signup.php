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

    if (!preg_match("/^[\p{L}0-9]{3,20}$/u", $nev)) {
        echo json_encode(["success" => false, "message" => "A név 3-20 karakter hosszú lehet, és csak betűkből meg számokból állhat!", "type" => "error"]);
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
        $mail->isHTML(true);

        $logoUrl = 'https://penzradar.hu/kepek/ujlogo.png';

        $emailBody = "
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #2b2b2b;
                        color: #ffffff;
                        padding: 20px;
                        margin: 0;
                    }
                    .container {
                        background-color: #2b2b2b;
                        padding: 20px;
                        border-radius: 12px;
                        border: 2px solid #63ffbe;
                        max-width: 600px;
                        margin: 0 auto;
                        text-align: center;
                    }
                    .header {
                        margin-bottom: 20px;
                    }
                    .header img {
                        max-width: 80px;
                        height: auto;
                        margin-bottom: 10px;
                    }
                    .header h1 {
                        color: #63ffbe;
                        margin: 0;
                        font-size: 24px;
                    }
                    h2 {
                        color: #63ffbe;
                        margin: 0 0 10px 0;
                        font-size: 28px;
                    }
                    .code-box {
                        background-color: #1e1e1e;
                        color: #63ffbe;
                        padding: 15px;
                        border-radius: 8px;
                        display: inline-block;
                        font-size: 24px;
                        font-weight: bold;
                        margin: 20px 0;
                    }
                    p {
                        line-height: 1.6;
                        color: #ffffff;
                    }
                    .footer {
                        margin-top: 20px;
                        color: #ffffff;
                        font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='$logoUrl' alt='PénzRadar Logo' />
                        <h1>PénzRadar</h1>
                    </div>
                    <h2>Kedves $nev!</h2>
                    <p>Köszönjük, hogy regisztráltál a PénzRadar rendszerébe! A regisztráció befejezéséhez kérjük, használd az alábbi ellenőrző kódot:</p>
                    <div class='code-box'>$kod</div>
                    <p>Ez a kód a regisztrációs folyamat részeként szükséges. Kérjük, add meg a következő lépésben a megadott felületen.</p>
                    <div class='footer'>
                        <p>Üdvözlettel,<br>PénzRadar csapata</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->Body = $emailBody;
        $mail->send();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Hiba történt az email küldése során: " . $e->getMessage(), "type" => "error"]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Kód elküldve!", "redirect" => "megerosites.php"]);
}