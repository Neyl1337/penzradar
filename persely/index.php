<?php
// Hibajelentés bekapcsolása a hibakereséshez
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../adatbazis.php';
} catch (Exception $e) {
    die("Nem sikerült betölteni az adatbazis.php fájlt: " . $e->getMessage());
}

session_start();

// Ellenőrizzük, hogy a felhasználó be van-e jelentkezve
if (!isset($_SESSION['felhasznalo_id'])) {
    header("Location: ../bejelentkezes/");
    exit;
}

// Függvény az egyenleg frissítésére a persely táblában
function frissitPerselyEgyenleg($pdo, $felhasznalo_id) {
    try {
        // Összeadjuk az osszeg értékeket a perselyk táblában az adott felhasznalo_id-hez
        $stmt = $pdo->prepare("SELECT SUM(osszeg) as total_osszeg FROM perselyk WHERE felhasznalo_id = ?");
        $stmt->execute([$felhasznalo_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $ossz_egyenleg = $result['total_osszeg'] ? (int)$result['total_osszeg'] : 0; // Ha NULL, akkor 0

        // Ellenőrizzük, hogy létezik-e már rekord a persely táblában
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM persely WHERE felhasznalo_id = ?");
        $stmt->execute([$felhasznalo_id]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Ha létezik, frissítjük az egyenleget
            $stmt = $pdo->prepare("UPDATE persely SET egyenleg = ? WHERE felhasznalo_id = ?");
            $stmt->execute([$ossz_egyenleg, $felhasznalo_id]);
        } else {
            // Ha nem létezik, beszúrunk egy új rekordot
            $stmt = $pdo->prepare("INSERT INTO persely (felhasznalo_id, egyenleg) VALUES (?, ?)");
            $stmt->execute([$felhasznalo_id, $ossz_egyenleg]);
        }
    } catch (PDOException $e) {
        $_SESSION['utolso_muvelet'] = "Hiba az egyenleg frissítése közben a persely táblában: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

try {
    // Felhasználó adatainak lekérdezése
    $stmt = $pdo->prepare("SELECT id, nev AS felhasznalo_nev, rang FROM felhasznalok WHERE id = ?");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['felhasznalo_nev'] = $felhasznalo['felhasznalo_nev'];
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
    } else {
        $_SESSION['szerepkor'] = null;
        header("Location: ../bejelentkezes/");
        exit;
    }

    // Perselyek lekérdezése az adott felhasználóhoz, további adatokkal (datum, betesz, kivesz)
    $stmt = $pdo->prepare("
        SELECT ID, felhasznalo_id, perselynev, osszeg, betesz, kivesz, datum 
        FROM perselyk 
        WHERE felhasznalo_id = ?
    ");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $perselyek = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Alapértelmezett kiválasztott persely összegének lekérdezése (első persely)
    $alapertelmezett_persely_id = !empty($_POST['persely_id']) ? (int)$_POST['persely_id'] : (isset($perselyek[0]['ID']) ? $perselyek[0]['ID'] : null);
    $jelenlegi_osszeg = 0;
    if ($alapertelmezett_persely_id) {
        $stmt = $pdo->prepare("SELECT osszeg FROM perselyk WHERE ID = ? AND felhasznalo_id = ?");
        $stmt->execute([$alapertelmezett_persely_id, $_SESSION['felhasznalo_id']]);
        $persely = $stmt->fetch(PDO::FETCH_ASSOC);
        $jelenlegi_osszeg = $persely ? (float)$persely['osszeg'] : 0; // decimal(10,2) típusú, ezért float-ként kezeljük
    }
    $formatált_jelenlegi_osszeg = number_format($jelenlegi_osszeg, 0, '.', ',');

    // Összes egyenleg kiszámítása
    $osszegek = array_map(function($persely) {
        return (float)$persely['osszeg']; // Biztosítjuk, hogy az osszeg float legyen
    }, $perselyek);
    $ossz_egyenleg = array_sum($osszegek);
    $formatált_egyenleg = number_format($ossz_egyenleg, 0, '.', ',');

    // Egyenleg frissítése a persely táblában
    frissitPerselyEgyenleg($pdo, $_SESSION['felhasznalo_id']);

    // Persely műveletek kezelése
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['persely_id'], $_POST['muvelet'], $_POST['osszeg'])) {
            $persely_id = (int)$_POST['persely_id'];
            $muvelet = $_POST['muvelet'];
            $osszeg = (float)$_POST['osszeg'];

            if ($osszeg < 0) {
                $_SESSION['utolso_muvelet'] = "Hiba: Az összeg nem lehet negatív!";
            } else {
                $stmt = $pdo->prepare("SELECT osszeg FROM perselyk WHERE ID = ? AND felhasznalo_id = ?");
                $stmt->execute([$persely_id, $_SESSION['felhasznalo_id']]);
                $persely = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($persely) {
                    $jelenlegi_osszeg = (float)$persely['osszeg'];

                    if ($muvelet === 'betet') {
                        $stmt = $pdo->prepare("
                            UPDATE perselyk 
                            SET osszeg = osszeg + ?, 
                                betesz = betesz + ? 
                            WHERE ID = ? AND felhasznalo_id = ?
                        ");
                        $stmt->execute([$osszeg, $osszeg, $persely_id, $_SESSION['felhasznalo_id']]);
                        $_SESSION['utolso_muvelet'] = "Betét: +$osszeg Ft";

                    } elseif ($muvelet === 'kivet') {
                        if ($jelenlegi_osszeg >= $osszeg) {
                            $stmt = $pdo->prepare("
                                UPDATE perselyk 
                                SET osszeg = osszeg - ?, 
                                    kivesz = kivesz + ? 
                                WHERE ID = ? AND felhasznalo_id = ?
                            ");
                            $stmt->execute([$osszeg, $osszeg, $persely_id, $_SESSION['felhasznalo_id']]);
                            $_SESSION['utolso_muvelet'] = "Kivét: -$osszeg Ft";
                        } else {
                            $_SESSION['utolso_muvelet'] = "Hiba: Nincs elég egyenleg a kivételhez!";
                        }
                    } elseif ($muvelet === 'modositas') {
                        $stmt = $pdo->prepare("
                            UPDATE perselyk 
                            SET osszeg = ?, 
                                betesz = 0, 
                                kivesz = 0 
                            WHERE ID = ? AND felhasznalo_id = ?
                        ");
                        $stmt->execute([$osszeg, $persely_id, $_SESSION['felhasznalo_id']]);
                        $_SESSION['utolso_muvelet'] = "Módosítás: Az összeg frissítve $osszeg Ft-ra, betét és kivét nullázva.";
                    }
                } else {
                    $_SESSION['utolso_muvelet'] = "Hiba: Érvénytelen persely!";
                }
            }
        }

        // Persely törlés kezelése
        if (isset($_POST['torles_persely_id'])) {
            $persely_id = (int)$_POST['torles_persely_id'];

            // Hibakeresés: Ellenőrizzük, hogy a torles_persely_id értéke megérkezik-e
            if (empty($persely_id)) {
                $_SESSION['utolso_muvelet'] = "Hiba: A persely ID nem érkezett meg!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT perselynev FROM perselyk WHERE ID = ? AND felhasznalo_id = ?");
            $stmt->execute([$persely_id, $_SESSION['felhasznalo_id']]);
            $persely = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($persely) {
                $persely_nev = $persely['perselynev'];
                $stmt = $pdo->prepare("DELETE FROM perselyk WHERE ID = ? AND felhasznalo_id = ?");
                $stmt->execute([$persely_id, $_SESSION['felhasznalo_id']]);
                $_SESSION['utolso_muvelet'] = "Persely törölve: $persely_nev";
            } else {
                $_SESSION['utolso_muvelet'] = "Hiba: A persely nem található!";
            }
        }

        // Persely létrehozás kezelése
        if (isset($_POST['uj_persely_nev'])) {
            $uj_persely_nev = trim($_POST['uj_persely_nev']);
            $felhasznalo_id = $_SESSION['felhasznalo_id'];
            $felhasznalo_nev = $_SESSION['felhasznalo_nev'];
            $datum = date('Y-m-d');

            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM perselyk WHERE perselynev = ? AND felhasznalo_id = ?");
            $check_stmt->execute([$uj_persely_nev, $felhasznalo_id]);
            $exists = $check_stmt->fetchColumn();

            if ($exists == 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO perselyk (felhasznalo_id, felhasznalo_nev, perselynev, osszeg, betesz, kivesz, datum) 
                    VALUES (?, ?, ?, 0, 0, 0, ?)
                ");
                $stmt->execute([$felhasznalo_id, $felhasznalo_nev, $uj_persely_nev, $datum]);
                $_SESSION['utolso_muvelet'] = "Új persely létrehozva: $uj_persely_nev";
            } else {
                $_SESSION['utolso_muvelet'] = "Hiba: A '$uj_persely_nev' nevű persely már létezik!";
            }
        }

        // Perselyek újbóli lekérdezése a POST műveletek után
        $stmt = $pdo->prepare("
            SELECT ID, perselynev, osszeg, betesz, kivesz, datum 
            FROM perselyk 
            WHERE felhasznalo_id = ?
        ");
        $stmt->execute([$_SESSION['felhasznalo_id']]);
        $perselyek = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Összes egyenleg újraszámítása
        $osszegek = array_map(function($persely) {
            return (float)$persely['osszeg'];
        }, $perselyek);
        $ossz_egyenleg = array_sum($osszegek);
        $formatált_egyenleg = number_format($ossz_egyenleg, 0, '.', ',');

        // Egyenleg frissítése a persely táblában a POST műveletek után
        frissitPerselyEgyenleg($pdo, $_SESSION['felhasznalo_id']);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Perselyek újbóli lekérdezése a frissítés után, további adatokkal
    $stmt = $pdo->prepare("
        SELECT ID, perselynev, osszeg, betesz, kivesz, datum 
        FROM perselyk 
        WHERE felhasznalo_id = ?
    ");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $perselyek = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Összes egyenleg kiszámítása
    $osszegek = array_map(function($persely) {
        return (float)$persely['osszeg'];
    }, $perselyek);
    $ossz_egyenleg = array_sum($osszegek);
    $formatált_egyenleg = number_format($ossz_egyenleg, 0, '.', ',');

    // Egyenleg frissítése a persely táblában (biztosítjuk, hogy mindig naprakész legyen)
    frissitPerselyEgyenleg($pdo, $_SESSION['felhasznalo_id']);

    $waiting_supports = $pdo->query("SELECT COUNT(*) FROM support WHERE statusz = 'Várakozás' or statusz = 'Megtekintett' or statusz = 'Folyamatban'")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM felhasznalok")->fetchColumn();

} catch (PDOException $e) {
    die("Adatbázis hiba: " . $e->getMessage());
} catch (Exception $e) {
    die("Hiba történt: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Persely</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
                <div class="text-center">
                    <img src="../kepek/ujlogo.png" alt="PénzRadar Logó" class="logo">
                </div>
                <h2 class="text-center">PénzRadar</h2>
                <ul class="nav flex-column flex-md-column mt-4">
                    <li class="nav-item">
                        <a class="nav-link" href="../kezdolap/">
                            <i class="fas fa-home"></i>
                            <span class="link-szoveg">Kezdőlap</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../tervezo/">
                            <i class="fas fa-tasks"></i> 
                            <span class="link-szoveg">Tervező</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../naptar/">
                            <i class="fas fa-calendar-alt"></i> 
                            <span class="link-szoveg">Naptár</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../persely/" style="background-color: #4ACDA3;">
                            <i class="fas fa-piggy-bank"></i> 
                            <span class="link-szoveg">Persely</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link kapcsolat-link" href="../kapcsolat/">
                            <i class="bi bi-envelope-at-fill"></i> 
                            <span class="link-szoveg">Kapcsolat</span>
                        </a>
                    </li>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
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
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <li class="nav-item">
                        <a class="nav-link" href="../alapoldal/arfolyam/">
                            <i class="bi bi-currency-exchange <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                            <span class="link-szoveg">Kamatszámítás</span>
                        </a>
                    </li>
                <?php endif; ?>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                        <div>
                            <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                            <li class="nav-item"><a class="nav-link" href="../admin/index.php"><p id="adminpanel"><i class="fas fa-cogs"></i> Admin Panel  <div id="felhszam"><?php echo $total_users; ?></div></p></a></li>
                        </div>
                        <div>
                            <li class="nav-item"><a class="nav-link" href="../admin/support.php"><p id="supportpanel"><i class="fas fa-users"></i> Support  <div id="supportszam">0<?php echo $waiting_supports; ?></div></p></a></li>
                        </div>
                    <?php endif; ?>
                </nav>
                <main class="col-12 col-md-9 col-lg-10 main-content">
                    <header class="d-flex justify-content-end py-3 border-bottom">
                        <div class="dropdown d-flex align-items-center">
                            <span class="me-3" id="szerepkor">RadarSzint: <b style="color: #63ffbe" id="szerepkorText"><?php echo htmlspecialchars($_SESSION['szerepkor'] ?? "Felhasználó"); ?></b></span>
                            <span class="me-3" id="perselyegyenleg">Persely egyenleg: <b style="color: #63ffbe" id="perselyegyenlegText"><?php echo $formatált_egyenleg; ?></b> Ft</span>
                            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="felhasznaloDropdownGomb">
                                <i class="fas fa-user-circle"></i> 
                                <span id="felhasznaloNev"><?php echo htmlspecialchars($_SESSION['felhasznalo_nev']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="felhasznaloDropdownGomb">
                                <li id="profilopcio"><a class="dropdown-item" href="../profilom/">Profilom</a></li>
                                <li id="beallitasopcio"><a class="dropdown-item" href="../beallitasok/">Beállítások</a></li>
                                <li id="kijelentkezesopcio"><a class="dropdown-item" href="../adatbazis_logout.php">Kijelentkezés</a></li>
                            </ul>
                        </div>
                    </header>
                    <div class="row mt-4" id="egyenlegkezeles">
                    <!-- Bal oldal: Persely létrehozása és műveletek -->
                    <div class="col-md-6 col-lg-5">
                        <div class="piggy-bank-card">
                            <h3 class="piggy-bank-title">Új persely létrehozása</h3>
                            <div class="piggy-bank-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="uj_persely_nev" class="form-label">Persely neve:</label>
                                        <input type="text" name="uj_persely_nev" id="uj_persely_nev" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-success piggy-bank-btn">Új persely készítése</button>
                                </form>
                            </div>
                        </div>

                        <?php if (!empty($perselyek)): ?>
                            <div class="piggy-bank-card mt-4">
                                <h3 class="piggy-bank-title">Persely műveletek</h3>
                                <div class="piggy-bank-body">
                                    <!-- "Utolsó művelet" felirat az űrlap tetején -->
                                    <p class="piggy-bank-last-action"><b>Utolsó művelet</b><br><?php echo htmlspecialchars($_SESSION['utolso_muvelet'] ?? 'Nincs előző művelet'); ?></p>

                                    <form method="POST" class="mb-3">
                                        <div class="mb-3">
                                            <label for="persely_id" class="form-label">Persely kiválasztása:</label>
                                            <select name="persely_id" id="persely_id" class="form-select" required>
                                                <?php foreach ($perselyek as $persely): ?>
                                                    <option value="<?php echo $persely['ID']; ?>">
                                                        <?php echo htmlspecialchars($persely['perselynev']) . " (" . number_format($persely['osszeg'], 0, '.', ',') . " Ft)"; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="muvelet" class="form-label">Művelet:</label>
                                            <select name="muvelet" id="muvelet" class="form-select" required>
                                                <option value="betet">Betét</option>
                                                <option value="kivet">Kivét</option>
                                                <option value="modositas">Módosítás</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="osszeg" class="form-label">Összeg (Ft):</label>
                                            <input type="number" name="osszeg" id="osszeg" class="form-control" min="0" step="0.01" value="0" required>
                                        </div>
                                        <button type="submit" class="btn btn-success piggy-bank-btn" id="vegrehajtasGomb">Pénz betétele</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Jobb oldal: Perselyek kártyája -->
                    <div class="col-md-6 col-lg-7">
                        <div class="piggy-bank-card">
                            <h3 class="piggy-bank-title">Perselyek</h3>
                            <div class="piggy-bank-body">
                                <?php if (!empty($perselyek)): ?>
                                    <div class="row">
                                        <?php foreach ($perselyek as $persely): ?>
                                            <div class="col-12 mb-3">
                                                <div class="piggy-bank-mini-card">
                                                    <div class="piggy-bank-mini-content">
                                                        <div class="piggy-bank-mini-left">
                                                            <p class="piggy-bank-mini-detail">Egyenleg: <?php echo number_format($persely['osszeg'], 0, '.', ','); ?> Ft</p>
                                                            <p class="piggy-bank-mini-detail">Létrehozva: <?php echo htmlspecialchars($persely['datum']); ?></p>
                                                        </div>
                                                        <div class="piggy-bank-mini-center">
                                                            <h5 class="piggy-bank-mini-title"><?php echo htmlspecialchars($persely['perselynev']); ?></h5>
                                                        </div>
                                                        <div class="piggy-bank-mini-right">
                                                            <p class="piggy-bank-mini-detail">Betett: <?php echo number_format($persely['betesz'], 0, '.', ','); ?> Ft</p>
                                                            <p class="piggy-bank-mini-detail">Kivett: <?php echo number_format($persely['kivesz'], 0, '.', ','); ?> Ft</p>
                                                        </div>
                                                    </div>
                                                    <!-- Törlés gomb -->
                                                    <form method="POST" class="mt-2">
                                                        <input type="hidden" name="torles_persely_id" value="<?php echo $persely['ID']; ?>">
                                                        <button type="submit" class="btn btn-danger piggy-bank-mini-btn">Persely törlése</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <h4 class="piggy-bank-balance">Összesen: <?php echo $formatált_egyenleg; ?> Ft</h4>
                                <?php else: ?>
                                    <p class="piggy-bank-empty">Nincsenek még perselyeid!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                </main>
            </div>
        </div>
    </div>
    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($formatált_egyenleg); ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="../alapoldal/arfolyam/js.js"></script>
    <script src="../alapoldal/kamat/js.js"></script>
    <script src="../alapoldal/alapstilus/nav.js"></script>
</body>
</html>