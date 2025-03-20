<?php
require_once '../adatbazis.php';

// PHPMailer betöltése
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Biztosítjuk, hogy ne legyen kimenet az átirányítás előtt
ob_start();

date_default_timezone_set('Europe/Budapest');

if (isset($_SESSION['felhasznalo_id'])) {
    $stmt = $pdo->prepare("
        SELECT f.rang, f.email, p.egyenleg 
        FROM felhasznalok f
        INNER JOIN persely p ON f.id = p.felhasznalo_id
        WHERE f.id = ?
    ");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['email'] = $felhasznalo['email'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['email'] = null;
    $_SESSION['perselyegyenleg'] = null;
}

$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';

// Előre megírt válaszok definiálása
$predefined_responses = [
    'Javítva' => 'Köszönjük a bejelentést! A problémát sikeresen javítottuk.',
    'Folyamatban' => 'A bejelentett probléma megoldása folyamatban van, hamarosan értesítünk a fejleményekről.',
    'Hamarosan' => 'A problémát hamarosan megvizsgáljuk és megoldjuk.',
    'Elutasítva' => 'Sajnáljuk, de a bejelentést nem áll módunkban elfogadni. További részletekért kérjük, vedd fel velünk a kapcsolatot.'
];

// Support ticket handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['support_action'])) {
    if ($_POST['support_action'] === 'delete_support') {
        $support_id = intval($_POST['support_id']);
        try {
            $delete_query = "DELETE FROM support WHERE id = ?";
            $stmt = $pdo->prepare($delete_query);
            $stmt->execute([$support_id]);
            header("Location: support.php");
            exit();
        } catch (PDOException $e) {
            echo "Hiba történt a support törlése során: " . $e->getMessage();
        }
    } elseif ($_POST['support_action'] === 'update_text') {
        $support_id = intval($_POST['support_id']);
        $new_text = $_POST['new_text'];
        try {
            $update_query = "UPDATE support SET szoveg = ? WHERE id = ?";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$new_text, $support_id]);
            header("Location: support.php");
            exit();
        } catch (PDOException $e) {
            echo "Hiba történt a szöveg frissítése során: " . $e->getMessage();
        }
    } elseif ($_POST['support_action'] === 'update_status') {
        $support_id = intval($_POST['support_id']);
        try {
            $update_query = "UPDATE support SET statusz = 'Megtekintett' WHERE id = ?";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$support_id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => 'Megtekintett']);
            exit();
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    } elseif ($_POST['support_action'] === 'update_status_to_in_progress') {
        $support_id = intval($_POST['support_id']);
        try {
            $update_query = "UPDATE support SET statusz = 'Folyamatban' WHERE id = ?";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$support_id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => 'Folyamatban']);
            exit();
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    } elseif ($_POST['support_action'] === 'send_response') {
        $support_id = intval($_POST['support_id']);
        $response_text = trim($_POST['response_text']);
        
        if (!empty($response_text)) {
            try {
                // Lekérdezzük a ticket adatait az e-mailhez
                $stmt = $pdo->prepare("SELECT targy, email, felhasznalo, szoveg FROM support WHERE id = ?");
                $stmt->execute([$support_id]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ticket) {
                    // Frissítjük az adatbázist a válasszal és a státusszal
                    $update_query = "UPDATE support SET valasz = ?, statusz = 'Válasz elküldve', valasz_ido = NOW() WHERE id = ?";
                    $stmt = $pdo->prepare($update_query);
                    $stmt->execute([$response_text, $support_id]);

                    // E-mail küldése PHPMailer-rel
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
                        $mail->addAddress($ticket['email'], $ticket['felhasznalo']);
                        $mail->Subject = 'Válasz érkezett a support üzenetedre';
                        $mail->CharSet = 'UTF-8';

                        $logoUrl = 'https://penzradar.hu/kepek/ujlogo.png';
                        $datum = date('Y-m-d');
                        $ido = date('H:i:s');

                        $emailBody = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; background-color: #2b2b2b; color: #ffffff; padding: 20px; margin: 0; }
                                .container { background-color: #2b2b2b; padding: 20px; border-radius: 12px; border: 2px solid #63ffbe; max-width: 600px; margin: 0 auto; text-align: center; }
                                .header { margin-bottom: 20px; }
                                .header img { max-width: 80px; height: auto; margin-bottom: 10px; }
                                .header h1 { color: #63ffbe; margin: 0; font-size: 24px; }
                                h2 { color: #63ffbe; margin: 0 0 10px 0; font-size: 28px; }
                                .message-box { background-color: #1e1e1e; color: #63ffbe; padding: 15px; border-radius: 8px; display: inline-block; font-size: 16px; margin: 20px 0; text-align: center; }
                                p { line-height: 1.6; color: #ffffff; }
                                .footer { margin-top: 20px; color: #ffffff; font-size: 14px; }
                                .footer b { color: #ffba00; }
                                b { color: rgb(255, 76, 76); }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <img src='$logoUrl' alt='PénzRadar Logó' />
                                    <h1>PénzRadar</h1>
                                </div>
                                <h2>Kedves " . htmlspecialchars($ticket['felhasznalo']) . "!</h2>
                                <b>Ez a rendszer által autómatikusan elküldött üzenet, kérjük ne válaszoljon rá!</b>
                                <p>Köszönjük, hogy felkerested a PénzRadar support csapatát! Az alábbi üzenetedre válasz érkezett:</p>
                                <div class='message-box'>
                                    <p><strong>Tárgy:</strong> " . htmlspecialchars($ticket['targy']) . "</p>
                                    <p>" . htmlspecialchars($ticket['szoveg']) . "</p>
                                </div>
                                <div class='message-box'>
                                    <div><strong>Support válasza:</strong></div>
                                    <p>" . htmlspecialchars($response_text) . "</p>
                                </div>
                                <p>Ha további kérdésed van, kérjük, vedd fel velünk a kapcsolatot!</p>
                                <div class='footer'>
                                    <p>" . htmlspecialchars($datum) . " - " . htmlspecialchars($ido) . "</p>
                                    <p>Üdvözlettel,<br><b>PénzRadar csapata</b></p>
                                    <p><a href='mailto:Support@penzradar.hu'>Support@penzradar.hu</a></p>
                                    <p><a href='https://penzradar.hu/kapcsolat/'>Support felület</a></p>
                                </div>
                            </div>
                        </body>
                        </html>";

                        $mail->Body = $emailBody;
                        $mail->AltBody = "Kedves {$ticket['felhasznalo']}!\n\nKöszönjük, hogy felkerested a PénzRadar support csapatát! Az alábbi üzenetedre válasz érkezett:\n\nTárgy: {$ticket['targy']}\nEredeti üzenet: {$ticket['szoveg']}\nVálasz: {$response_text}\nDátum: {$datum}\n\nReméljük, hogy válaszunk megoldotta a problémádat. Ha további kérdésed van, kérjük, vedd fel velünk a kapcsolatot!\n\nÜdvözlettel,\nPénzRadar csapata";

                        $mail->send();
                        error_log("E-mail sikeresen elküldve a következő címre: " . $ticket['email']);
                    } catch (Exception $e) {
                        error_log("E-mail küldés sikertelen: " . $mail->ErrorInfo);
                        header("Location: support.php?error=Email_kuldes_sikertelen&details=" . urlencode($mail->ErrorInfo));
                        exit();
                    }

                    header("Location: support.php?message=Valasz_elkuldve");
                    exit();
                } else {
                    error_log("Nem található a ticket az ID-hoz: " . $support_id);
                    header("Location: support.php?error=Nem_talalhato_ticket");
                    exit();
                }
            } catch (PDOException $e) {
                error_log("Adatbázis hiba: " . $e->getMessage());
                header("Location: support.php?error=Valasz_hiba&details=" . urlencode($e->getMessage()));
                exit();
            }
        } else {
            error_log("Üres válasz szöveg");
            header("Location: support.php?error=Valasz_ures");
            exit();
        }
    } else {
        error_log("Hibás vagy hiányzó support_action: " . print_r($_POST, true));
        header("Location: support.php?error=Hibas_support_action");
        exit();
    }
}

// Fetch support tickets
$support_query = "SELECT id, targy AS target, email, felhasznalo, szoveg, datum, ido, statusz, valasz_ido, valasz FROM support";
$support_stmt = $pdo->prepare($support_query);
$support_stmt->execute();
$support_tickets = $support_stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$osszes_felhasznalo = $pdo->query("SELECT COUNT(*) FROM felhasznalok")->fetchColumn();
$adminok_szama = $pdo->query("SELECT COUNT(*) FROM felhasznalok WHERE rang = 'Admin'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - ADMIN PANEL</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../alapoldal/alapstilus/style.css">
    <style>
    #supportTable {
        background-color: #1e1e1e;
        box-shadow: 0 0 15px #63ffbe;
        border-radius: 10px;
        overflow: hidden;
    }
    #supportTable th {
        background-color: #63ffbe;
        color: #121212;
        text-transform: uppercase;
        text-align: center;
        vertical-align: middle;
    }
    #supportTable td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid #444;
    }
    #supportTable tr:hover {
        background-color: #2a2a2a;
    }
    .button-view {
        background-color: #63ffbe;
        color: #121212;
        border: none;
        padding: 6px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }
    .button-view:hover {
        background-color: #55daa2;
        color: #ffffff;
    }
    .button-delete {
        background-color: #dc3545;
        color: #1e1e1e;
        border: none;
        padding: 6px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }
    .button-delete:hover {
        background-color: #c82333;
        color: #ffffff;
    }
    .button-response {
        background-color: #63ffbe;
        color: #121212;
        border: none;
        padding: 6px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }
    .button-response:hover {
        background-color: #55daa2;
        color: #ffffff;
    }
    .button-send {
        background-color: #63ffbe;
        color: #121212;
        border: none;
        padding: 6px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }
    .button-send:hover {
        background-color: #55daa2;
        color: #ffffff;
    }
    .button-send:disabled {
        background-color: #666;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .button-orange {
        background-color: #ff9800;
        color: #ffffff;
        border: none;
        padding: 6px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        margin-right: 5px;
    }
    .button-orange:hover {
        background-color: #e68900;
    }
    .button-orange-active {
        background-color: #63ffbe;
        color: #121212;
    }
    .button-orange-active:hover {
        background-color: #55daa2;
        color: #ffffff;
    }
    .modal-content {
        background-color: #1e1e1e;
        color: white;
        border: 1px solid #63ffbe;
        box-shadow: 0 0 15px #63ffbe;
        border-radius: 10px;
    }
    .modal-header {
        background-color: #63ffbe;
        color: #121212;
        border-bottom: 1px solid #55daa2;
    }
    .modal-header b {
        color: #ff9800;
    }
    .support-text {
        background-color: #1e1e1e;
        padding: 10px;
        border-radius: 5px;
        font-size: 14px;
        line-height: 1.5;
    }
    .status-button {
        padding: 6px 10px;
        border: none;
        border-radius: 5px;
        cursor: default;
        font-size: 14px;
        color: #fff;
    }
    .status-waiting {
        background-color: #ff9800;
    }
    .status-viewed {
        background-color: #2196f3;
    }
    .status-in-progress {
        background-color: #b9a911;
    }
    .status-responded {
        background-color: #4caf50;
    }
    .form-control.response-textarea {
        background-color: #1e1e1e;
        color: #ffffff;
        border: 1px solid #63ffbe;
    }
    .form-control.response-textarea::placeholder {
        color: #aaaaaa;
    }
    .countdown {
        color: #ff9800;
        font-weight: bold;
    }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-12 col-md-3 col-lg-2 oldalsav">
                <div class="text-center">
                    <img src="../kepek/ujlogo.png" alt="PénzRadar Logó" class="logo">
                </div>
                <p id="Adminszoveg">PénzRadar ADMIN</p>
                <ul class="nav flex-column flex-md-column mt-4">
                    <li class="nav-item"><a class="nav-link" href="../kezdolap/"><i class="fas fa-home"></i> Kezdőlap</a></li>
                    <li class="nav-item"><a class="nav-link" href="../tervezo/"><i class="fas fa-tasks"></i> Tervező</a></li>
                    <li class="nav-item"><a class="nav-link" href="../naptar/"><i class="fas fa-calendar-alt"></i> Naptár</a></li>
                    <li class="nav-item"><a class="nav-link" href="../persely/"><i class="fas fa-piggy-bank"></i> Persely</a></li>
                    <li class="nav-item"><a class="nav-link kapcsolat-link" href="../kapcsolat/"><i class="bi bi-envelope-at-fill"></i> Kapcsolat</a></li>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                        <br>
                        <h4>Statisztikák</h4>
                        <p>Összes felhasználó: <b><?php echo $osszes_felhasznalo; ?></b></p>
                        <p>Adminok száma: <b><?php echo $adminok_szama; ?></b></p>
                        <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                        <li class="nav-item"><a class="nav-link" href="index.php" id="felhasznalok_gomb"><i class="fas fa-users"></i> Felhasználók</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <main class="col-12 col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-end py-3 border-bottom">
                    <div class="dropdown d-flex align-items-center">
                        <span class="me-3" id="szerepkor" style="visibility: hidden;">Szerepkör: <b style="color: #63ffbe" id="szerepkorText"><?php echo htmlspecialchars($_SESSION['szerepkor'] ?? "Felhasználó"); ?></b></span>
                        <span class="me-3" id="perselyegyenleg" style="visibility: hidden;">Persely egyenleg: <b style="color: #63ffbe" id="perselyegyenlegText"><?php echo htmlspecialchars($formatált_egyenleg); ?></b> Ft</span>
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="felhasznaloDropdownGomb">
                            <i class="fas fa-user-circle"></i> 
                            <span id="felhasznaloNev">Jelentkezz be!</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="felhasznaloDropdownGomb">
                            <li id="bejelentkezesopcio"><a class="dropdown-item" href="../bejelentkezes/">Bejelentkezés</a></li>
                            <li id="profilopcio" style="display:none;"><a class="dropdown-item" href="../profilom/">Profilom</a></li>
                            <li id="beallitasopcio" style="display:none;"><a class="dropdown-item" href="../beallitasok/">Beállítások</a></li>
                            <li id="kijelentkezesopcio" style="display:none;"><a class="dropdown-item" href="../adatbazis_logout.php">Kijelentkezés</a></li>
                        </ul>
                    </div>
                </header>
                <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                    <div class="card p-3 mt-3 kartya1">
                        <center>
                            <h3>Jelenleg az Admin felületen vagy!</h3>
                        </center>
                    </div>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <table id="supportTable">
                        <tr>
                            <th colspan="11" class="text-center">
                                <h3>Support Felület <i class="fas fa-headset"></i></h3>
                            </th>
                        </tr>
                        <tr>
                            <th>ID</th>
                            <th>Tárgy</th>
                            <th class="formaz">Email</th>
                            <th>Felhasználó</th>
                            <th class="formaz">Szöveg</th>
                            <th class="formaz">Válasz</th>
                            <th class="formaz">Dátum</th>
                            <th class="formaz">Idő</th>
                            <th class="formaz">Törlés</th>
                            <th class="formaz">Reagálás</th>
                            <th class="formaz">Státusz</th>
                        </tr>
                        <?php foreach ($support_tickets as $ticket): ?>
                            <tr>
                                <td><?= htmlspecialchars($ticket['id']) ?></td>
                                <td><?= htmlspecialchars($ticket['target']) ?></td>
                                <td class="formaz"><?= htmlspecialchars($ticket['email']) ?></td>
                                <td><?= htmlspecialchars($ticket['felhasznalo']) ?></td>
                                <td class="formaz">
                                    <button type="button" class="button-view view-btn" data-support-id="<?= $ticket['id'] ?>" data-bs-toggle="modal" data-bs-target="#viewTextModal<?= $ticket['id'] ?>">Megtekintés</button>
                                </td>
                                <td class="formaz">
                                    <?php if (!empty($ticket['valasz'])): ?>
                                        <button type="button" class="button-view view-response-btn" data-support-id="<?= $ticket['id'] ?>" data-bs-toggle="modal" data-bs-target="#viewResponseModal<?= $ticket['id'] ?>">Megtekintés</button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="formaz"><?= htmlspecialchars($ticket['datum']) ?></td>
                                <td class="formaz"><?= htmlspecialchars($ticket['ido']) ?></td>
                                <td class="formaz">
                                    <?php if ($ticket['statusz'] === 'Válasz elküldve' && !empty($ticket['valasz_ido'])): ?>
                                        <span class="countdown" data-support-id="<?= $ticket['id'] ?>" data-start-time="<?= strtotime($ticket['valasz_ido']) ?>"></span>
                                    <?php else: ?>
                                        <button type="button" class="button-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $ticket['id'] ?>">Törlés</button>
                                    <?php endif; ?>
                                </td>
                                <td class="formaz">
                                    <?php if ($ticket['statusz'] === 'Válasz elküldve'): ?>
                                        <button type="button" class="button-response" disabled>Reagálva</button>
                                    <?php else: ?>
                                        <button type="button" class="button-response response-status-btn" data-support-id="<?= $ticket['id'] ?>" data-bs-toggle="modal" data-bs-target="#responseModal<?= $ticket['id'] ?>">Válasz</button>
                                    <?php endif; ?>
                                </td>
                                <td class="formaz">
                                    <span class="status-button 
                                        <?php 
                                        if ($ticket['statusz'] === 'Várakozás') {
                                            echo 'status-waiting';
                                        } elseif ($ticket['statusz'] === 'Megtekintett') {
                                            echo 'status-viewed';
                                        } elseif ($ticket['statusz'] === 'Folyamatban') {
                                            echo 'status-in-progress';
                                        } elseif ($ticket['statusz'] === 'Válasz elküldve') {
                                            echo 'status-responded';
                                        }
                                        ?>" 
                                        id="status-<?= $ticket['id'] ?>">
                                        <?= htmlspecialchars($ticket['statusz']) ?>
                                    </span>
                                </td>
                            </tr>

                            <!-- Szöveg Megtekintés Modal -->
                            <div class="modal fade" id="viewTextModal<?= $ticket['id'] ?>" tabindex="-1" aria-labelledby="viewTextModalLabel<?= $ticket['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewTextModalLabel<?= $ticket['id'] ?>">Bejelentés tárgya: <?= htmlspecialchars($ticket['target']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="support-text"><?= htmlspecialchars($ticket['szoveg']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Válasz Megtekintés Modal -->
                            <div class="modal fade" id="viewResponseModal<?= $ticket['id'] ?>" tabindex="-1" aria-labelledby="viewResponseModalLabel<?= $ticket['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewResponseModalLabel<?= $ticket['id'] ?>">Válasz: <?= htmlspecialchars($ticket['target']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="support-text"><?= htmlspecialchars($ticket['valasz']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Törlés megerősítő Modal -->
                            <div class="modal fade" id="deleteModal<?= $ticket['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $ticket['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel<?= $ticket['id'] ?>">Support üzenet törlése</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            Biztosan törölni szeretnéd a(z) "<?= htmlspecialchars($ticket['target']) ?>" tárgyú üzenetet?
                                        </div>
                                        <div class="modal-footer">
                                            <form method="post">
                                                <input type="hidden" name="support_id" value="<?= $ticket['id'] ?>">
                                                <input type="hidden" name="support_action" value="delete_support">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mégse</button>
                                                <button type="submit" class="btn btn-danger">Törlés</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Válasz Modal -->
                            <div class="modal fade" id="responseModal<?= $ticket['id'] ?>" tabindex="-1" aria-labelledby="responseModalLabel<?= $ticket['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="responseModalLabel<?= $ticket['id'] ?>">Support felületi válasz: <b><?= htmlspecialchars($ticket['target']) ?></b><br>
                                            <b><?= htmlspecialchars($ticket['felhasznalo']) ?></b> Felhasználó részére</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post" id="responseForm<?= $ticket['id'] ?>" action="support.php">
                                                <input type="hidden" name="support_id" value="<?= $ticket['id'] ?>">
                                                <input type="hidden" name="support_action" value="send_response">
                                                <div class="d-flex mb-3" style="justify-content: center;">
                                                    <button type="button" class="button-orange response-btn" data-support-id="<?= $ticket['id'] ?>" data-response-text="<?= htmlspecialchars($predefined_responses['Javítva']) ?>">Javítva</button>
                                                    <button type="button" class="button-orange response-btn" data-support-id="<?= $ticket['id'] ?>" data-response-text="<?= htmlspecialchars($predefined_responses['Folyamatban']) ?>">Folyamatban</button>
                                                    <button type="button" class="button-orange response-btn" data-support-id="<?= $ticket['id'] ?>" data-response-text="<?= htmlspecialchars($predefined_responses['Hamarosan']) ?>">Hamarosan</button>
                                                    <button type="button" class="button-orange response-btn" data-support-id="<?= $ticket['id'] ?>" data-response-text="<?= htmlspecialchars($predefined_responses['Elutasítva']) ?>">Elutasítva</button>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="response_text<?= $ticket['id'] ?>" class="form-label">Elküldendő Üzenet:</label>
                                                    <textarea class="form-control response-textarea" id="response_text<?= $ticket['id'] ?>" name="response_text" rows="5" placeholder="Írd ide a válaszadat..."></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mégse</button>
                                                    <button type="submit" class="button-send" id="sendButton<?= $ticket['id'] ?>" disabled>Küldés</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <script>
    const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
    const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded esemény lefutott');

        // "Megtekintés" gombok eseménykezelője (Szöveg)
        const viewButtons = document.querySelectorAll('.view-btn');
        console.log('Megtalált "Megtekintés" gombok száma:', viewButtons.length);

        viewButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                console.log('Megtekintés gomb megnyomva');

                const supportId = this.getAttribute('data-support-id');
                const statusElement = document.getElementById(`status-${supportId}`);

                if (!statusElement) {
                    console.error('Státusz elem nem található, ID:', supportId);
                    return;
                }

                const currentStatus = statusElement.textContent.trim().toLowerCase();
                console.log('Jelenlegi státusz:', currentStatus);

                if (currentStatus !== 'megtekintett' && currentStatus !== 'folyamatban' && currentStatus !== 'válasz elküldve') {
                    console.log('Státusz váltás: Várakozás -> Megtekintett');
                    statusElement.textContent = 'Megtekintett';
                    statusElement.classList.remove('status-waiting');
                    statusElement.classList.remove('status-in-progress');
                    statusElement.classList.remove('status-responded');
                    statusElement.classList.add('status-viewed');
                } else {
                    console.log('A státusz már Megtekintett, Folyamatban vagy Válasz elküldve, nincs váltás');
                }

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `support_action=update_status&support_id=${supportId}`
                })
                .then(response => {
                    console.log('AJAX válasz státusz:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('AJAX válasz:', data);
                    if (!data.success) {
                        console.error('Hiba a státusz frissítésekor:', data.error);
                        statusElement.textContent = 'Várakozás';
                        statusElement.classList.remove('status-viewed');
                        statusElement.classList.remove('status-in-progress');
                        statusElement.classList.remove('status-responded');
                        statusElement.classList.add('status-waiting');
                    }
                })
                .catch(error => {
                    console.error('AJAX hiba:', error);
                    setTimeout(() => {
                        statusElement.textContent = 'Várakozás';
                        statusElement.classList.remove('status-viewed');
                        statusElement.classList.remove('status-in-progress');
                        statusElement.classList.remove('status-responded');
                        statusElement.classList.add('status-waiting');
                    }, 1000);
                });
            });
        });

        // "Válasz" gombok eseménykezelője (csak akkor fut, ha a gomb nem disabled)
        const responseStatusButtons = document.querySelectorAll('.response-status-btn');
        console.log('Megtalált "Válasz" gombok száma:', responseStatusButtons.length);

        responseStatusButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                console.log('Válasz gomb megnyomva');

                const supportId = this.getAttribute('data-support-id');
                const statusElement = document.getElementById(`status-${supportId}`);

                if (!statusElement) {
                    console.error('Státusz elem nem található, ID:', supportId);
                    return;
                }

                const currentStatus = statusElement.textContent.trim().toLowerCase();
                console.log('Jelenlegi státusz:', currentStatus);

                if (currentStatus !== 'folyamatban' && currentStatus !== 'válasz elküldve') {
                    console.log('Státusz váltás: -> Folyamatban');
                    statusElement.textContent = 'Folyamatban';
                    statusElement.classList.remove('status-waiting');
                    statusElement.classList.remove('status-viewed');
                    statusElement.classList.remove('status-responded');
                    statusElement.classList.add('status-in-progress');
                }

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `support_action=update_status_to_in_progress&support_id=${supportId}`
                })
                .then(response => {
                    console.log('AJAX válasz státusz:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('AJAX válasz:', data);
                    if (!data.success) {
                        console.error('Hiba a státusz frissítésekor:', data.error);
                        statusElement.textContent = 'Várakozás';
                        statusElement.classList.remove('status-viewed');
                        statusElement.classList.remove('status-in-progress');
                        statusElement.classList.remove('status-responded');
                        statusElement.classList.add('status-waiting');
                    }
                })
                .catch(error => {
                    console.error('AJAX hiba:', error);
                    setTimeout(() => {
                        statusElement.textContent = 'Várakozás';
                        statusElement.classList.remove('status-viewed');
                        statusElement.classList.remove('status-in-progress');
                        statusElement.classList.remove('status-responded');
                        statusElement.classList.add('status-waiting');
                    }, 1000);
                });
            });
        });

        // "Válasz" modal gombok eseménykezelője
        const responseButtons = document.querySelectorAll('.response-btn');
        responseButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault(); // Megakadályozzuk az alapértelmezett viselkedést
                const supportId = this.getAttribute('data-support-id');
                const responseText = this.getAttribute('data-response-text'); // Az előre megírt válasz szövege
                const textarea = document.getElementById(`response_text${supportId}`);
                const sendButton = document.getElementById(`sendButton${supportId}`);
                const isActive = this.classList.contains('button-orange-active');
                const siblingButtons = document.querySelectorAll(`.response-btn[data-support-id="${supportId}"]`);
                
                // Debug: Ellenőrizzük, hogy milyen szöveget állítunk be
                console.log(`Gomb szövege: ${responseText}, Support ID: ${supportId}`);

                siblingButtons.forEach(sibling => {
                    sibling.classList.remove('button-orange-active');
                });

                if (isActive) {
                    textarea.value = '';
                    textarea.placeholder = 'Írd ide a válaszadat...';
                    sendButton.disabled = true; // Letiltjuk a Küldés gombot, ha üres a textarea
                } else {
                    this.classList.add('button-orange-active');
                    textarea.value = responseText; // Az előre megírt szöveg kerül a textarea-ba
                    sendButton.disabled = false; // Engedélyezzük a Küldés gombot
                }

                // Debug: Ellenőrizzük a textarea értékét és a Küldés gomb állapotát
                console.log(`Textarea értéke beállítás után: ${textarea.value}`);
                console.log(`Küldés gomb állapota: ${sendButton.disabled ? 'Letiltva' : 'Engedélyezve'}`);
            });
        });

        // Küldés gomb engedélyezése/tiltása a textarea változásakor
        document.querySelectorAll('.response-textarea').forEach(textarea => {
            const supportId = textarea.id.replace('response_text', '');
            const sendButton = document.getElementById(`sendButton${supportId}`);

            textarea.addEventListener('input', function() {
                console.log(`Textarea (${supportId}) értéke: ${this.value}`);
                sendButton.disabled = !this.value.trim();
                console.log(`Küldés gomb állapota (input után): ${sendButton.disabled ? 'Letiltva' : 'Engedélyezve'}`);
            });

            // Kezdeti állapot beállítása
            sendButton.disabled = !textarea.value.trim();
        });

        // Űrlap elküldésének figyelése
        document.querySelectorAll('form[id^="responseForm"]').forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault(); // Megakadályozzuk az alapértelmezett űrlap elküldést a debugoláshoz
                console.log('Űrlap elküldve:', this.id);
                const formData = new FormData(this);
                const formDataObj = Object.fromEntries(formData);
                console.log('Űrlap adatai:', formDataObj);

                // Ellenőrizzük, hogy a response_text üres-e
                if (!formDataObj.response_text) {
                    console.error('Hiba: A response_text üres!');
                    return;
                }

                // Űrlap elküldése manuálisan
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Űrlap elküldés válasz státusz:', response.status);
                    if (response.redirected) {
                        window.location.href = response.url; // Átirányítás kezelése
                    }
                    return response.text();
                })
                .then(data => {
                    console.log('Űrlap elküldés válasz:', data);
                })
                .catch(error => {
                    console.error('Űrlap elküldés hiba:', error);
                });
            });
        });

        // Visszaszámláló
        function startCountdown() {
            const countdownElements = document.querySelectorAll('.countdown');
            countdownElements.forEach(element => {
                const supportId = element.getAttribute('data-support-id');
                const startTime = parseInt(element.getAttribute('data-start-time')) * 1000;
                const endTime = startTime + (10 * 60 * 60 * 1000); // 5 óra

                function updateCountdown() {
                    const now = Date.now();
                    const timeLeft = endTime - now;

                    if (timeLeft <= 0) {
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `support_action=delete_support&support_id=${supportId}`
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                        element.textContent = `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                }

                updateCountdown();
                setInterval(updateCountdown, 1000);
            });
        }

        startCountdown();

        document.getElementById('felhasznalok_gomb').addEventListener('click', function() {
            document.getElementById('usersTable').style.display = 'block';
            document.getElementById('supportTable').style.display = 'none';
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../kezdolap/script.js"></script>
</body>
</html>
<?php
// Biztosítjuk, hogy az összes kimenet az átirányítás előtt kerüljön elküldésre
ob_end_flush();
?>