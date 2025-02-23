<?php
require_once '../adatbazis.php';

session_start();

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

$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';


    $hiba_nev = false;
    $hiba_jelszo = false;
    $siker_nev = false;
    $siker_jelszo = false;

                    if (isset($_POST['uj_nev']) || isset($_POST['uj_email']) || isset($_POST['uj_jelszo'])) {
                        $felhasznalo_id = $_SESSION['felhasznalo_id'];
                        $felhasznalo_jelszo = $_POST['regi_jelszo_nev'];

                        // Név módosítása
                        if (isset($_POST['uj_nev'])) {
                            $stmt = $pdo->prepare("SELECT jelszo FROM felhasznalok WHERE id = ?");
                            $stmt->execute([$felhasznalo_id]);
                            $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($felhasznalo && password_verify($felhasznalo_jelszo, $felhasznalo['jelszo'])) {
                                $uj_nev = $_POST['uj_nev'];
                                $stmt = $pdo->prepare("UPDATE felhasznalok SET nev = ? WHERE id = ?");
                                $stmt->execute([$uj_nev, $felhasznalo_id]);
                                $_SESSION['felhasznalo_nev'] = $uj_nev;
                                $siker_nev = true;
                            } else {
                                $hiba_nev = true;
                            }
                        }

                        // Jelszó módosítása
                        if (isset($_POST['uj_jelszo'])) {
                            $felhasznalo_jelszo = $_POST['regi_jelszo_jelszo'];

                            $stmt = $pdo->prepare("SELECT jelszo FROM felhasznalok WHERE id = ?");
                            $stmt->execute([$felhasznalo_id]);
                            $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($felhasznalo && password_verify($felhasznalo_jelszo, $felhasznalo['jelszo'])) {
                                if ($_POST['uj_jelszo'] === $_POST['uj_jelszo_meg']) {
                                    $uj_jelszo = $_POST['uj_jelszo'];
                                    $uj_jelszo_hash = password_hash($uj_jelszo, PASSWORD_DEFAULT);
                                    $stmt = $pdo->prepare("UPDATE felhasznalok SET jelszo = ? WHERE id = ?");
                                    $stmt->execute([$uj_jelszo_hash, $felhasznalo_id]);

                                    // Jelszó módosítása után kijelentkeztetés
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
                        <ul id="arfolyam-lista" class="arfolyam-stilus"></ul>
                    </div>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                        <div>
                            <b id="frissites-ido" style="color: red;"></b>
                        </div>
                    <?php endif; ?>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b>
                    <div>
                        <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                            <li class="nav-item"><a class="nav-link" href="../admin/"><p id="adminpanel"><i class="fas fa-cogs"></i> Admin Panel</p></a></li>
                        <?php endif; ?>
                    </div>
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
                <!-- HTML kód -->
                <div class="container">
                    <div class="row">
                        <div class="col-md-4" id="modositas" style="visibility: hidden;">
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
                                    <?php if ($hiba_nev): ?>
                                        <div class="alert alert-danger" role="alert">
                                            A jelenlegi jelszó nem megfelelő a név módosításához!
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
                        <div class="col-md-4" id="modositas2" style="visibility: hidden;">
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
                                            A jelszó sikeresen módosítva! Kijelentkezés után újra be kell jelentkeznie.
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="button2 btn-primary">Mentés</button>
                                </form>
                            </div>
                        </div>

                        <!-- <div class="col-md-4" id="modositas3" style="visibility: hidden;">
                            <div>
                                <button type="submit" class="button2 btn-danger">Fiók törlése</button>
                            </div>
                        </div> -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
