<?php
require_once '../adatbazis.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

date_default_timezone_set('Europe/Budapest');

session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject']) && isset($_POST['message'])) {
    $targy = $_POST['subject'];
    $email = $_SESSION['email'] ?? '';
    $felhasznalo = $_SESSION['felhasznalo_nev'] ?? '';
    $szoveg = $_POST['message'];
    $datum = date('Y-m-d');
    $ido = date('H:i:s');
    $statusz = 'Várakozás';

    $stmt = $pdo->prepare("
        INSERT INTO support (targy, email, felhasznalo, szoveg, datum, ido, statusz) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$targy, $email, $felhasznalo, $szoveg, $datum, $ido, $statusz]);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'penzradar.hu@gmail.com';
        $mail->Password = 'obvgamdjyxnqmwrv';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->AltBody = "Kedves $felhasznalo!\n\nKöszönjük, hogy felvette velünk a kapcsolatot! Az alábbi support üzenetet sikeresen rögzítettük:\n\nTárgy: $targy\nÜzenet: $szoveg\nDátum: $datum\n\nCsapatunk hamarosan átnézi az üzenetét, és szükség esetén kapcsolatba lép Önnel. Köszönjük a türelmét!\n\nÜdvözlettel,\nPénzRadar csapata";

        $mail->setFrom('penzradar.hu@gmail.com', 'PénzRadar');
        $mail->addAddress($email, $felhasznalo);
        $mail->Subject = 'Üzenetét megkaptuk';
        $mail->CharSet = 'UTF-8';

        $logoUrl = 'https://penzradar.hu/kepek/ujlogo.png';

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
                b { color:rgb(255, 76, 76); }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='$logoUrl' alt='PénzRadar Logó' />
                    <h1>PénzRadar</h1>
                </div>
                <h2>Kedves " . htmlspecialchars($felhasznalo) . "!</h2>
                <b>Ez a rendszer által autómatikusan elküldött üzenet, kérjük ne válaszoljon erre az üzenetre!</b>
                <p>Köszönjük, hogy felvette velünk a kapcsolatot! Az alábbi support üzenetet sikeresen rögzítettük:</p>
                <p><strong>Tárgy:</strong> " . htmlspecialchars($targy) . "</p>
                <div class='message-box'>
                    <div><strong>Üzenet:</strong></div>
                    <p>" . htmlspecialchars($szoveg) . "</p>
                </div>
                <p>Egy Support hamarosan átnézi az üzenetét, és szükség esetén kapcsolatba lép Önnel. Köszönjük a türelmét!</p>
                <div class='footer'>
                    <p>". htmlspecialchars($datum) . " - " . htmlspecialchars($ido) ."</p>
                    <p>Üdvözlettel,<br>PénzRadar csapata</p>
                    <p><a href='mailto:Support@penzradar.hu'>Support@penzradar.hu</a></p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $emailBody;
        $mail->send();
    } catch (Exception $e) {
        error_log("Email küldés sikertelen: " . $mail->ErrorInfo);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Email küldése sikertelen.']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Support</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../alapoldal/alapstilus/style.css">
    <link rel="stylesheet" href="../alapoldal/kamat/style.css">
    <link rel="stylesheet" href="../alapoldal/arfolyam/style.css">
    <link rel="stylesheet" href="style.css">
    <style>
        #submitButton {
            transition: background-color 0.3s;
        }
        .success-green {
            background-color: #28a745 !important;
            color: white !important;
        }
        .cooldown-gray {
            background-color: #808080 !important;
            color: white !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
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
                <h2 class="text-center" id="penzradarTitle">PénzRadar</h2>
                <audio id="penzradarAudio">
                    <source src="../videok/intohang.mp3" type="audio/mpeg">
                    A böngésződ nem támogatja az audió lejátszását.
                </audio>
                <ul class="nav flex-column flex-md-column mt-4">
                    <li class="nav-item">
                        <a class="nav-link" href="../kezdolap/">
                            <i class="fas fa-home"></i>
                            <span class="link-szoveg">Kezdőlap</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../tervezo/">
                            <i class="fas fa-tasks <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                            <span class="link-szoveg">Tervező</span>
                            <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                                <i class="fas fa-lock lakat-jobb"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../naptar/">
                            <i class="fas fa-calendar-alt <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                            <span class="link-szoveg">Naptár</span>
                            <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                                <i class="fas fa-lock lakat-jobb"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../persely/">
                            <i class="fas fa-piggy-bank <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                            <span class="link-szoveg">Persely</span>
                            <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                                <i class="fas fa-lock lakat-jobb"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['felhasznalo_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link kapcsolat-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../kapcsolat/">
                                <i class="bi bi-envelope-at-fill <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                                <span class="link-szoveg">Kapcsolat</span>
                                <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                                    <i class="fas fa-lock lakat-jobb"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                </ul>
                <div id="arfolyamok" class="my-3">
                    <h4 class="text-center" style="color: #63ffbe; font-size: 1.2rem;">Árfolyamok</h4>
                    <ul id="arfolyam-lista" class="arfolyam-stilus list-unstyled d-flex flex-column align-items-center"></ul>
                </div>
                <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                    <div>
                        <b id="frissites-ido" style="color: red;" class="text-center d-block"></b>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['felhasznalo_id'])): ?>
                    <br>
                    <h4 style="color: #63ffbe; font-size: 1.2rem;">Kamatszámítás</h4>
                    <form id="kamatSzamitasForm">
                        <div class="mb-2">
                            <label for="alapOsszeg" style="color: white; font-size: 1rem;">Tőke (Ft):</label>
                            <input type="number" id="alapOsszeg" class="form-control" min="0" value="<?php echo htmlspecialchars($formatált_egyenleg); ?>" style="background-color: #1e1e1e; color: white; border: 1px solid #63ffbe; border-radius: 5px;" oninput="validateInput(this)">
                        </div>
                        <div class="mb-2">
                            <label for="kamatSzazalek" style="color: white; font-size: 1rem;">Kamatláb (%):</label>
                            <input type="number" id="kamatSzazalek" class="form-control" min="0" max="100" step="0.1" value="5" style="background-color: #1e1e1e; color: white; border: 1px solid #63ffbe; border-radius: 5px;" oninput="validateInput(this)">
                        </div>
                        <div class="mb-2">
                            <label for="idotartam" style="color: white; font-size: 1rem;">Futamidő (év):</label>
                            <input type="number" id="idotartam" class="form-control" min="1" max="99" value="1" style="background-color: #1e1e1e; color: white; border: 1px solid #63ffbe; border-radius: 5px;" oninput="validateInput(this)">
                        </div>
                        <button type="button" class="btn btn-primary w-100 kamat-button" onclick="szamitKamat()" style="background-color: #1e1e1e; border: 1px solid #63ffbe; color: white;">Számítás</button>
                    </form>
                    <p id="kamatEredmeny" class="mt-2" style="color: #63ffbe;"></p>
                <?php endif; ?>
                <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                    <div>
                        <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                        <li class="nav-item"><a class="nav-link" href="../admin/"><p id="adminpanel"><i class="fas fa-cogs"></i> Admin Panel</p></a></li>
                    </div>
                <?php endif; ?>
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
                <div id="egyenlegkezeles" style="visibility: hidden;">
                <div id="egyenlegkezeles">
                    <div class="kartya">
                        <div class="text-center">
                            <img src="../kepek/ujlogo.png" alt="PénzRadar Logó" class="logo">
                        </div>
                        <h3 class="support-title">Support Kapcsolat</h3>
                        <div class="callout-container">
                            <p class="notice-text"><h3>FIGYELEM!</h3> A Weboldal még fejlesztés alatt, amennyiben hibát észlel, vagy ötlete, esetleg panasza van, itt jelezze nekünk!</p>
                            <p class="support-text">Ez a PénzRadar Support felülete</p>
                            <div class="user-info">
                                <p class="contact-text">Levelező rendszerünk: <b class="email-text">Support@penzradar.hu</b></p>
                            </div>
                        </div>
                        <div class="user-info">
                            <p class="user-label"><b>Az ön felhasználó neve:</b> <?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?></p>
                            <p class="user-label"><b>Az ön emailje:</b> <?php echo htmlspecialchars($_SESSION["email"] ?? ""); ?></p>
                        </div>
                        <form id="supportForm" method="POST">
                            <label for="subject">Tárgy:</label>
                            <select id="subject" name="subject">
                                <option value="Hibabejelentés">Hibabejelentés</option>
                                <option value="Ötlet">Ötlet</option>
                                <option value="Panasz">Panasz</option>
                                <option value="Egyéb">Egyéb</option>
                            </select>
                            <label for="message">Üzenet:</label>
                            <textarea id="message" name="message" maxlength="300" placeholder="Maximum 300 karakter" required></textarea>
                            <button type="submit" id="submitButton" class="btn btn-primary">Küldés</button>
                        </form>
                        <p id="responseMessage" style="display: none;"></p>
                        <p id="supportInfo">Köszönjük, hogy segít jobbá tenni a PénzRadart!</p>
                    </div>
                </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';

        document.addEventListener('DOMContentLoaded', function() {
            const penzradarTitle = document.getElementById('penzradarTitle');
            const penzradarAudio = document.getElementById('penzradarAudio');
            const submitButton = document.getElementById('submitButton');
            const form = document.getElementById('supportForm');
            const responseMessage = document.getElementById('responseMessage');

            penzradarTitle.addEventListener('click', function() {
                penzradarAudio.currentTime = 0;
                penzradarAudio.play().catch(function(error) {
                    console.log("A hang lejátszása nem sikerült: ", error);
                });
            });

            let lastSubmissionTime = localStorage.getItem('lastSubmissionTime') ? parseInt(localStorage.getItem('lastSubmissionTime')) : 0;
            const cooldownPeriod = 30 * 60 * 1000; // 1 perc cooldown
            let isSubmitting = false; // Nyomon követjük, hogy a küldés folyamatban van-e

            function updateCooldown() {
                const now = Date.now();
                const timeLeft = Math.max(0, Math.floor((cooldownPeriod - (now - lastSubmissionTime)) / 1000));

                if (timeLeft > 0 || isSubmitting) {
                    // Gomb letiltása, ha a visszaszámlálás tart vagy a küldés folyamatban van
                    submitButton.disabled = true;
                    submitButton.classList.add('cooldown-gray');
                    if (!isSubmitting) {
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;
                        submitButton.textContent = `Elérhetővé válik: ${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }
                } else {
                    // Gomb engedélyezése, ha a visszaszámlálás lejárt és a küldés befejeződött
                    submitButton.disabled = false;
                    submitButton.classList.remove('cooldown-gray');
                    submitButton.textContent = 'Küldés';
                }
            }

            // Visszaszámlálás frissítése másodpercenként
            setInterval(updateCooldown, 1000);
            updateCooldown();

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                // Ha a gomb le van tiltva, ne történjen semmi
                if (submitButton.disabled) {
                    console.log('A gomb le van tiltva, küldés nem lehetséges.');
                    return;
                }

                // Küldés folyamatban van
                isSubmitting = true;
                submitButton.disabled = true;
                submitButton.classList.add('success-green');
                submitButton.textContent = 'Küldés folyamatban';

                const formData = new FormData(form);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setTimeout(() => {
                            submitButton.classList.remove('success-green');
                            submitButton.classList.add('cooldown-gray');

                            responseMessage.style.display = 'block';
                            responseMessage.style.color = 'green';
                            responseMessage.textContent = 'Üzenetét sikeresen elküldtük! Megerősítő emailt fog kapni.';

                            // Frissítjük a legutóbbi küldés időpontját
                            lastSubmissionTime = Date.now();
                            localStorage.setItem('lastSubmissionTime', lastSubmissionTime);
                            isSubmitting = false; // Küldés befejeződött
                            updateCooldown();

                            form.reset();
                            setTimeout(() => {
                                responseMessage.style.display = 'none';
                            }, 5000);
                        }, 3000);
                    } else {
                        // Sikertelen küldés esetén
                        submitButton.classList.remove('success-green');
                        submitButton.classList.add('cooldown-gray');
                        submitButton.textContent = 'Küldés sikertelen';

                        responseMessage.style.display = 'block';
                        responseMessage.style.color = 'red';
                        responseMessage.textContent = 'Az üzenet küldése sikertelen!';

                        isSubmitting = false; // Küldés befejeződött
                        updateCooldown();

                        setTimeout(() => {
                            responseMessage.style.display = 'none';
                        }, 5000);
                    }
                })
                .catch(error => {
                    console.error('Hiba történt:', error);
                    submitButton.classList.remove('success-green');
                    submitButton.classList.add('cooldown-gray');
                    submitButton.textContent = 'Küldés sikertelen';

                    responseMessage.style.display = 'block';
                    responseMessage.style.color = 'red';
                    responseMessage.textContent = 'Az üzenet küldése sikertelen!';

                    isSubmitting = false; // Küldés befejeződött
                    updateCooldown();

                    setTimeout(() => {
                        responseMessage.style.display = 'none';
                    }, 5000);
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="../alapoldal/arfolyam/js.js"></script>
    <script src="../alapoldal/kamat/js.js"></script>
    <script src="../alapoldal/alapstilus/nav.js"></script>
</body>
</html>