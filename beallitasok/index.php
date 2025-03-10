<?php
require_once '../adatbazis.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (isset($_SESSION['felhasznalo_id'])) {
    $stmt = $pdo->prepare("
        SELECT f.rang, p.egyenleg 
        FROM felhasznalok f
        INNER JOIN persely p ON f.id = p.felhasznalo_id
        WHERE f.id = ?
    ");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['perselyegyenleg'] = null;
}

$formatalt_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';

$hiba_nev = false;
$hiba_jelszo = false;
$siker_nev = false;
$siker_jelszo = false;
$hiba_email = false;
$siker_email = false;

if (isset($_POST['uj_nev']) || isset($_POST['uj_email']) || isset($_POST['uj_jelszo']) || isset($_POST['fiok_torles'])) {
    $felhasznalo_id = $_SESSION['felhasznalo_id'];
    $felhasznalo_jelszo = $_POST['regi_jelszo_nev'] ?? '';

    if (isset($_POST['uj_nev'])) {
        $stmt = $pdo->prepare("SELECT jelszo FROM felhasznalok WHERE id = ?");
        $stmt->execute([$felhasznalo_id]);
        $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($felhasznalo && password_verify($felhasznalo_jelszo, $felhasznalo['jelszo'])) {
            $uj_nev = trim($_POST['uj_nev']);

            // Ellenőrzés: 3-20 karakter, csak betűk és számok
            if (!preg_match("/^[\p{L}0-9]{3,20}$/u", $uj_nev)) {
                $hiba_nev = "ervenytelen_nev";
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM felhasznalok WHERE nev = ? AND id != ?");
                $stmt->execute([$uj_nev, $felhasznalo_id]);
                $nev_letezik = $stmt->fetchColumn();

                if ($nev_letezik > 0) {
                    $hiba_nev = "ilyen_nev_mar_van";
                } else {
                    $stmt = $pdo->prepare("UPDATE felhasznalok SET nev = ? WHERE id = ?");
                    $stmt->execute([$uj_nev, $felhasznalo_id]);
                    $_SESSION['felhasznalo_nev'] = $uj_nev;
                    $siker_nev = true;
                }
            }
        } else {
            $hiba_nev = true;
        }
    }

    if (isset($_POST['uj_jelszo'])) {
        $felhasznalo_jelszo = $_POST['regi_jelszo_jelszo'] ?? '';

        $stmt = $pdo->prepare("SELECT jelszo FROM felhasznalok WHERE id = ?");
        $stmt->execute([$felhasznalo_id]);
        $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($felhasznalo && password_verify($felhasznalo_jelszo, $felhasznalo['jelszo'])) {
            if ($_POST['uj_jelszo'] === $_POST['uj_jelszo_meg']) {
                $uj_jelszo = $_POST['uj_jelszo'];
                $uj_jelszo_hash = password_hash($uj_jelszo, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE felhasznalok SET jelszo = ? WHERE id = ?");
                $stmt->execute([$uj_jelszo_hash, $felhasznalo_id]);

                session_destroy();
                header('Location: ../adatbazis_logout.php');
                exit;
            } else {
                $hiba_jelszo = true;
            }
        } else {
            $hiba_jelszo = true;
        }
    }

    if (isset($_POST['uj_email'])) {
        $uj_email = trim($_POST['uj_email']);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM felhasznalok WHERE email = ? AND id != ?");
        $stmt->execute([$uj_email, $felhasznalo_id]);
        $email_letezik = $stmt->fetchColumn();

        if ($email_letezik > 0) {
            $hiba_email = "ilyen_email_mar_van";
        } else {
            $kod = rand(100000, 999999);
            $stmt = $pdo->prepare("SELECT nev FROM felhasznalok WHERE id = ?");
            $stmt->execute([$felhasznalo_id]);
            $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);
            $nev = $felhasznalo['nev'];
            $_SESSION['email_kod'] = $kod;
            $_SESSION['uj_email'] = $uj_email;
            $_SESSION['action'] = 'email';

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
                $mail->addAddress($uj_email, $nev);
                $mail->Subject = 'Módosító kód';
                $mail->CharSet = 'UTF-8';
                $mail->Body = "Kedves $nev,\n\nA módosítás befejezéséhez használd ezt a kódot: $kod\n\nÜdvözlettel, PénzRadar csapata";
                $mail->send();
                $siker_email = true;
                header('Location: megerosites.php');
                exit;
            } catch (Exception $e) {
                $hiba_email = true;
            }
        }
    }

    if (isset($_POST['fiok_torles'])) {
        $stmt = $pdo->prepare("SELECT email, nev FROM felhasznalok WHERE id = ?");
        $stmt->execute([$felhasznalo_id]);
        $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

        $email = $felhasznalo['email'];
        $nev = $felhasznalo['nev'];
        $kod = rand(100000, 999999);
        $_SESSION['torles_kod'] = $kod;
        $_SESSION['action'] = 'torles';

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
            $mail->Subject = 'Törlés kód';
            $mail->CharSet = 'UTF-8';
            $mail->Body = "Kedves $nev,\n\nA törlés befejezéséhez használd ezt a kódot: $kod\n\nÜdvözlettel, PénzRadar csapata";
            $mail->send();
            header('Location: megerosites.php');
            exit;
        } catch (Exception $e) {
            $hiba_email = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Beállítások</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../alapoldal/alapstilus/style.css">
    <link rel="stylesheet" href="../alapoldal/kamat/style.css">
    <link rel="stylesheet" href="../alapoldal/arfolyam/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        <nav class="col-12 col-md-3 col-lg-2 oldalsav">
                <h2 class="text-center">PénzRadar</h2>
                <ul class="nav flex-column flex-md-column mt-4">
                    <li class="nav-item"><a class="nav-link" href="../kezdolap/"><i class="fas fa-home"></i> Kezdőlap</a></li>
                    <li class="nav-item"><a class="nav-link" href="../tervezo/"><i class="fas fa-tasks"></i> Tervező</a></li>
                    <li class="nav-item"><a class="nav-link" href="../naptar/"><i class="fas fa-calendar-alt"></i> Naptár</a></li>
                    <li class="nav-item"><a class="nav-link" href="../persely/"><i class="fas fa-piggy-bank"></i> Persely</a></li>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b>
                    <div id="arfolyamok" class="text-center my-3">
                        <h4 class="text-center" style="color: #63ffbe; font-size: 1.2rem;">Árfolyamok</h4>
                        <ul id="arfolyam-lista" class="arfolyam-stilus list-unstyled d-flex flex-column align-items-center"></ul>
                    </div>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                        <div>
                            <b id="frissites-ido" style="color: red;"> 
                                <!-- A frissítés időpontja itt jelenik meg -->
                            </b>
                        </div>
                    <?php endif; ?>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b>
                    <!-- Bal oldali kalkulátor - csak bejelentkezett állapotban, keret nélkül -->
                    <?php if (isset($_SESSION['felhasznalo_id'])): ?>
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
                            <b class="d-flex justify-content-end py-3 border-bottom"></b>
                            <li class="nav-item"><a class="nav-link" href="../admin/"><p id="adminpanel"><i class="fas fa-cogs"></i> Admin Panel</p></a></li>
                        </div>
                    <?php endif; ?>
                </ul>
            </nav>
            <main class="col-12 col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-end py-3 border-bottom">
                    <div class="dropdown d-flex align-items-center">
                        <span class="me-3" id="szerepkor" style="visibility: hidden;">Szerepkör: <b style="color: #63ffbe" id="szerepkorText"><?php echo htmlspecialchars($_SESSION['szerepkor'] ?? "Felhasználó"); ?></b></span>
                        <span class="me-3" id="perselyegyenleg" style="visibility: hidden;">Persely egyenleg: <b style="color: #63ffbe" id="perselyegyenlegText"><?php echo htmlspecialchars($formatalt_egyenleg); ?></b> Ft</span>
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
                <div class="container-fluid">
                    <div class="row">
                        <div id="modositas" style="visibility: hidden;">
                            <div>
                                <h2 id="nevmodosit">Név módosítása</h2>
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="regi_jelszo_nev" class="form-label">Jelenlegi jelszó</label>
                                        <input type="password" class="form-control" id="regi_jelszo_nev" name="regi_jelszo_nev" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="uj_nev" class="form-label">Új név</label>
                                        <input type="text" class="form-control" id="uj_nev" name="uj_nev">
                                    </div>
                                    <?php if ($hiba_nev === true): ?>
                                        <div class="alert alert-danger" role="alert">
                                            A jelenlegi jelszó nem megfelelő a név módosításához!
                                        </div>
                                    <?php elseif ($hiba_nev === "ilyen_nev_mar_van"): ?>
                                        <div class="alert alert-danger" role="alert">
                                            Ez a név már foglalt, kérlek válassz másikat!
                                        </div>
                                    <?php elseif ($hiba_nev === "ervenytelen_nev"): ?>
                                        <div class="alert alert-danger" role="alert">
                                            A név 3-20 karakter hosszú lehet, és csak betűket meg számokat tartalmazhat!
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($siker_nev): ?>
                                        <div class="alert alert-success" role="alert">
                                            A név sikeresen módosítva!
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="button2 btn-primary">Mentés</button>
                                </form>
                            </div>
                        </div>
                        <div id="modositas2" style="visibility: hidden;">
                            <div>
                                <h2 id="jelszomodosit">Jelszó módosítása</h2>
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="regi_jelszo_jelszo" class="form-label">Jelenlegi jelszó</label>
                                        <input type="password" class="form-control" id="regi_jelszo_jelszo" name="regi_jelszo_jelszo" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="uj_jelszo" class="form-label">Új jelszó</label>
                                        <input type="password" class="form-control" id="uj_jelszo" name="uj_jelszo">
                                    </div>
                                    <div class="mb-3">
                                        <label for="uj_jelszo_meg" class="form-label">Új jelszó megerősítése</label>
                                        <input type="password" class="form-control" id="uj_jelszo_meg" name="uj_jelszo_meg">
                                    </div>
                                    <?php if ($hiba_jelszo): ?>
                                        <div class="alert alert-danger" role="alert">
                                            A jelenlegi jelszó nem megfelelő vagy a két új jelszó nem egyezik!
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($siker_jelszo): ?>
                                        <div class="alert alert-success" role="alert">
                                            A jelszó sikeresen módosítva!
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="button2 btn-primary">Mentés</button>
                                </form>
                            </div>
                        </div>

                        <div id="modositas3" style="visibility: hidden;">
                            <div>
                                <h2 id="emailmodositas">Email módosítása</h2>
                                <form action="" method="POST" id="emailModositasForm">
                                    <label for="uj_email" class="form-label">Új email</label>
                                    <input type="text" class="form-control" id="uj_email" name="uj_email"><br>
                                    <?php if ($hiba_email === true): ?>
                                        <div class="alert alert-danger" role="alert">
                                            Az email küldése sikertelen!
                                        </div>
                                    <?php elseif ($hiba_email === "ilyen_email_mar_van"): ?>
                                        <div class="alert alert-danger" role="alert">
                                            Ez az email cím már foglalt, kérlek válassz másikat!
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($siker_email): ?>
                                        <div class="alert alert-success" role="alert">
                                            A megerősítő kód elküldve az új email címre.
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="button2 btn-primary" id="emailMentesGomb">Mentés</button>
                                </form>
                            </div>
                        </div>

                        <div id="modositas4" style="visibility: hidden;">
                            <div>
                                <h2 id="fioktorles">Fiók törlése</h2>
                                <button type="button" class="button3 btn-danger" data-bs-toggle="modal" data-bs-target="#fioktorlesModal">Törlés</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="fioktorlesModal" class="modal fade" tabindex="-1" aria-labelledby="fioktorlesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fioktorlesModalLabel">Fiók törlése</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Biztosan törölni szeretné a fiókját? Ez a művelet nem visszafordítható.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary button2" data-bs-dismiss="modal">Mégsem</button>
                    <form action="" method="POST">
                        <button type="submit" name="fiok_torles" class="btn btn-danger button3">Törlöm!</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
    const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';

    // Email módosítás gomb letiltása és szöveg változtatása
    document.getElementById('emailModositasForm').addEventListener('submit', function(event) {
        const gomb = document.getElementById('emailMentesGomb');
        gomb.disabled = true; // Gomb letiltása
        gomb.textContent = 'Kérlek várj...'; // Szöveg módosítása
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="../alapoldal/arfolyam/js.js"></script>
    <script src="../alapoldal/kamat/js.js"></script>
</body>
</html>