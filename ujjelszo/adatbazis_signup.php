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
        $mail->SMTPSecure =  'ssl';
        $mail->Port = 465;

        $mail->setFrom('penzradar.hu@gmail.com', 'PénzRadar');
        $mail->addAddress($email);
        $mail->Subject = 'Jelszó visszaállítási kód';
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
                    <h2>Jelszó helyreállítás</h2>
                    <p>Az új jelszó beállításához kérjük, használd az alábbi kódot:</p>
                    <div class='code-box'>$kod</div>
                    <p>Kérjük, add meg ezt a kódot a jelszó-visszaállítási felületen.</p>
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
        echo json_encode(["success" => false, "message" => "Hiba történt az email küldése során: " . $mail->ErrorInfo, "type" => "error"]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Kód elküldve!", "redirect" => "megerosites.php"]);
}
?>