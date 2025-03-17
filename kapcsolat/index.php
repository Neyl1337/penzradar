<?php
require_once '../adatbazis.php';

session_start();

// Ellenőrizzük, hogy a felhasználó be van-e jelentkezve
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

// Perselyegyenleg formázása PHP-ban vesszővel
$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';

// Alapértelmezett kiválasztott persely összegének lekérdezése (első persely)
$alapertelmezett_persely_id = !empty($_POST['persely_id']) ? (int)$_POST['persely_id'] : (isset($perselyek[0]['ID']) ? $perselyek[0]['ID'] : null);
$jelenlegi_osszeg = 0;
if ($alapertelmezett_persely_id) {
    $stmt = $pdo->prepare("SELECT osszeg FROM perselyk WHERE ID = ? AND felhasznalo_nev = ?");
    $stmt->execute([$alapertelmezett_persely_id, $_SESSION['felhasznalo_nev']]);
    $persely = $stmt->fetch(PDO::FETCH_ASSOC);
    $jelenlegi_osszeg = $persely ? $persely['osszeg'] : 0;
}
$formatált_jelenlegi_osszeg = number_format($jelenlegi_osszeg, 0, '.', ',');

// Persely műveletek kezelése
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['persely_id'], $_POST['muvelet'], $_POST['osszeg'])) {
        $persely_id = (int)$_POST['persely_id'];
        $muvelet = $_POST['muvelet'];
        $osszeg = (float)$_POST['osszeg'];

        if ($osszeg < 0) {
            $_SESSION['utolso_muvelet'] = "Hiba: Az összeg nem lehet negatív!";
        } else {
            $stmt = $pdo->prepare("SELECT osszeg FROM perselyk WHERE ID = ? AND felhasznalo_nev = ?");
            $stmt->execute([$persely_id, $_SESSION['felhasznalo_nev']]);
            $persely = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($persely) {
                $jelenlegi_osszeg = $persely['osszeg'];

                if ($muvelet === 'betet') {
                    $stmt = $pdo->prepare("
                        UPDATE perselyk 
                        SET osszeg = osszeg + ?, 
                            betesz = betesz + ? 
                        WHERE ID = ? AND felhasznalo_nev = ?
                    ");
                    $stmt->execute([$osszeg, $osszeg, $persely_id, $_SESSION['felhasznalo_nev']]);
                    $_SESSION['utolso_muvelet'] = "Betét: +$osszeg Ft";

                } elseif ($muvelet === 'kivet') {
                    if ($jelenlegi_osszeg >= $osszeg) {
                        $stmt = $pdo->prepare("
                            UPDATE perselyk 
                            SET osszeg = osszeg - ?, 
                                kivesz = kivesz + ? 
                            WHERE ID = ? AND felhasznalo_nev = ?
                        ");
                        $stmt->execute([$osszeg, $osszeg, $persely_id, $_SESSION['felhasznalo_nev']]);
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
                        WHERE ID = ? AND felhasznalo_nev = ?
                    ");
                    $stmt->execute([$osszeg, $persely_id, $_SESSION['felhasznalo_nev']]);
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

        $stmt = $pdo->prepare("SELECT perselynev FROM perselyk WHERE ID = ? AND felhasznalo_nev = ?");
        $stmt->execute([$persely_id, $_SESSION['felhasznalo_nev']]);
        $persely = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($persely) {
            $persely_nev = $persely['perselynev'];
            $stmt = $pdo->prepare("DELETE FROM perselyk WHERE ID = ? AND felhasznalo_nev = ?");
            $stmt->execute([$persely_id, $_SESSION['felhasznalo_nev']]);
            $_SESSION['utolso_muvelet'] = "Persely törölve: $persely_nev";
        } else {
            $_SESSION['utolso_muvelet'] = "Hiba: A persely nem található!";
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Persely létrehozás kezelése
    if (isset($_POST['uj_persely_nev'])) {
        $uj_persely_nev = trim($_POST['uj_persely_nev']);
        $felhasznalo_nev = $_SESSION['felhasznalo_nev'];
        $datum = date('Y-m-d');

        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM perselyk WHERE perselynev = ? AND felhasznalo_nev = ?");
        $check_stmt->execute([$uj_persely_nev, $felhasznalo_nev]);
        $exists = $check_stmt->fetchColumn();

        if ($exists == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO perselyk (perselynev, felhasznalo_nev, osszeg, betesz, kivesz, datum) 
                VALUES (?, ?, 0, 0, 0, ?)
            ");
            $stmt->execute([$uj_persely_nev, $felhasznalo_nev, $datum]);
            $_SESSION['utolso_muvelet'] = "Új persely létrehozva: $uj_persely_nev";
        } else {
            $_SESSION['utolso_muvelet'] = "Hiba: A '$uj_persely_nev' nevű persely már létezik!";
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Perselyek újbóli lekérdezése a frissítés után
$stmt = $pdo->prepare("
    SELECT ID, perselynev, osszeg 
    FROM perselyk 
    WHERE felhasznalo_nev = ?
");
$stmt->execute([$_SESSION['felhasznalo_nev']]);
$perselyek = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Összes egyenleg kiszámítása
$ossz_egyenleg = array_sum(array_column($perselyek, 'osszeg'));
$formatált_egyenleg = number_format($ossz_egyenleg, 0, '.', ',');
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
    <button type="button" class="toggle-nav-btn" onclick="toggleNav()" id="toggleKiegeszito<?= $row['id'] ?>">
        <i class="fas fa-chevron-left"></i>
    </button>
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
                <div class="dashboard mt-4">

                </div>
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
    <script src="../alapoldal/arfolyam/js.js"></script>
    <script src="../alapoldal/kamat/js.js"></script>
    <script src="../alapoldal/alapstilus/nav.js"></script>
</body>
</html>