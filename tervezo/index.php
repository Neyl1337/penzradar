<?php
require_once '../adatbazis.php';

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$_SESSION['szerepkor'] = null;
$_SESSION['perselyegyenleg'] = null;
$_SESSION['heti_maximum'] = null;
$_SESSION['napi_maximum'] = null;
$_SESSION['gyakorisag'] = null;

if (isset($_SESSION['felhasznalo_nev'])) {
    $parancs = $pdo->prepare("
        SELECT f.rang, p.egyenleg, f.hetimax, f.napimax 
        FROM felhasznalok f
        INNER JOIN persely p ON f.id = p.felhasznalo_id
        WHERE f.nev = ?
    ");
    $parancs->execute([$_SESSION['felhasznalo_nev']]);
    $felhasznalo = $parancs->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
        $_SESSION['heti_maximum'] = $felhasznalo['hetimax'];
        $_SESSION['napi_maximum'] = $felhasznalo['napimax'];
    }

    $parancs_gyakorisag = $pdo->prepare("
        SELECT gyakorisag 
        FROM tervezo 
        WHERE felhasznalo_nev = ? 
        ORDER BY datum DESC 
        LIMIT 1
    ");
    $parancs_gyakorisag->execute([$_SESSION['felhasznalo_nev']]);
    $utolso_gyakorisag = $parancs_gyakorisag->fetch(PDO::FETCH_ASSOC);
    $_SESSION['gyakorisag'] = $utolso_gyakorisag['gyakorisag'] ?? null;

    $parancs_celok = $pdo->prepare("
        SELECT id, cel, jegyzet 
        FROM celok 
        WHERE felhasznalo_nev = ? AND cel IS NOT NULL
    ");
    $parancs_celok->execute([$_SESSION['felhasznalo_nev']]);
    $celok = $parancs_celok->fetchAll(PDO::FETCH_ASSOC);

    $parancs_koltseg = $pdo->prepare("
        SELECT SUM(osszeg) as osszes_koltseg 
        FROM tervezo 
        WHERE felhasznalo_nev = ? AND tipus = 'Kiadás' AND felfuggesztve = 0
    ");
    $parancs_koltseg->execute([$_SESSION['felhasznalo_nev']]);
    $osszes_koltseg = $parancs_koltseg->fetch(PDO::FETCH_ASSOC)['osszes_koltseg'] ?? 0;

    $parancs = $pdo->prepare("
        SELECT id, tipus, osszeg, gyakorisag, leiras, datum, tipus_reszletezes, felfuggesztve 
        FROM tervezo 
        WHERE felhasznalo_nev = ? 
        ORDER BY tipus, gyakorisag
    ");
    $parancs->execute([$_SESSION['felhasznalo_nev']]);
    $tranzakciok = $parancs->fetchAll(PDO::FETCH_ASSOC);
} else {
    $celok = [];
    $osszes_koltseg = 0;
    $tranzakciok = [];
}

$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';

$heti_maximum = $_SESSION['heti_maximum'] ?? null;
$napi_maximum = $_SESSION['napi_maximum'] ?? null;
$gyakorisag = $_SESSION['gyakorisag'] ?? null;
$formatált_heti_maximum = $heti_maximum ? number_format($heti_maximum, 0, '.', ',') : 'Még nincs megadva';
$formatált_napi_maximum = $napi_maximum ? number_format($napi_maximum, 0, '.', ',') : 'Még nincs megadva';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['felhasznalo_nev'])) {
    if (isset($_POST['tipus']) && isset($_POST['osszeg']) && isset($_POST['gyakorisag'])) {
        $parancs_szamolas = $pdo->prepare("SELECT COUNT(*) FROM tervezo WHERE felhasznalo_nev = ?");
        $parancs_szamolas->execute([$_SESSION['felhasznalo_nev']]);
        $tranzakciok_szama = $parancs_szamolas->fetchColumn();

        if ($tranzakciok_szama < 50) {
            $engedelyezett_gyakorisagok = [
                'Napi', 'Heti', 'Kétheti', 'Havi', 'Negyedévi', 'Félévi', 'Évi'
            ];
            $bevitt_gyakorisag = trim($_POST['gyakorisag']);
            $gyakorisag = in_array($bevitt_gyakorisag, $engedelyezett_gyakorisagok) ? $bevitt_gyakorisag : 'Napi';
            
            $tipus = ($_POST['tipus'] === 'Kiadás') ? 'Kiadás' : 'Bevétel';

            $parancs = $pdo->prepare("
                INSERT INTO tervezo (felhasznalo_nev, tipus, osszeg, gyakorisag, leiras, datum, tipus_reszletezes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $parancs->execute([
                $_SESSION['felhasznalo_nev'],
                $tipus,
                $_POST['osszeg'],
                $gyakorisag,
                $_POST['leiras'] ?? '',
                $_POST['datum'],
                $_POST['tipus_reszletezes']
            ]);

            $_SESSION['gyakorisag'] = $gyakorisag;
        } else {
            $_SESSION['uzenet'] = "Elérted a maximum 50 bevétel/kiadás tételt!";
        }
    }

    if (isset($_POST['torles_id'])) {
        $parancs = $pdo->prepare("DELETE FROM tervezo WHERE id = ? AND felhasznalo_nev = ?");
        $parancs->execute([$_POST['torles_id'], $_SESSION['felhasznalo_nev']]);
    }
    
    if (isset($_POST['heti_maximum_megerosites'])) {
        $parancs = $pdo->prepare("UPDATE felhasznalok SET hetimax = ?, napimax = ? WHERE nev = ?");
        $parancs->execute([$_POST['heti_maximum'], $_POST['napi_maximum'], $_SESSION['felhasznalo_nev']]);
        $_SESSION['heti_maximum'] = $_POST['heti_maximum'];
        $_SESSION['napi_maximum'] = $_POST['napi_maximum'];
    }
    
    if (isset($_POST['cel_hozzaadas'])) {
        $parancs_szamolas = $pdo->prepare("SELECT COUNT(*) FROM celok WHERE felhasznalo_nev = ?");
        $parancs_szamolas->execute([$_SESSION['felhasznalo_nev']]);
        $celok_szama = $parancs_szamolas->fetchColumn();

        if ($celok_szama < 15) {
            $parancs = $pdo->prepare("
                INSERT INTO celok (felhasznalo_nev, cel) 
                VALUES (?, ?)
            ");
            $parancs->execute([
                $_SESSION['felhasznalo_nev'],
                $_POST['uj_cel']
            ]);
        } else {
            $_SESSION['uzenet'] = "Elérted a maximum 15 célt!";
        }
    }
    
    if (isset($_POST['cel_torles'])) {
        $parancs = $pdo->prepare("DELETE FROM celok WHERE id = ? AND felhasznalo_nev = ?");
        $parancs->execute([$_POST['cel_torles'], $_SESSION['felhasznalo_nev']]);
    }

    if (isset($_POST['jegyzet_mentese']) && isset($_POST['cel_id'])) {
        $parancs = $pdo->prepare("
            UPDATE celok 
            SET jegyzet = ? 
            WHERE id = ? AND felhasznalo_nev = ?
        ");
        $jegyzet = substr($_POST['jegyzet'], 0, 150);
        $parancs->execute([$jegyzet, $_POST['cel_id'], $_SESSION['felhasznalo_nev']]);
    }

    if (isset($_POST['jegyzet_modositasa']) && isset($_POST['cel_id'])) {
        $parancs = $pdo->prepare("
            UPDATE celok 
            SET jegyzet = NULL 
            WHERE id = ? AND felhasznalo_nev = ?
        ");
        $parancs->execute([$_POST['cel_id'], $_SESSION['felhasznalo_nev']]);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$varakozo_tamogatasok = $pdo->query("SELECT COUNT(*) FROM support WHERE statusz = 'Várakozás'")->fetchColumn();
$osszes_felhasznalo = $pdo->query("SELECT COUNT(*) FROM felhasznalok")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Tervező</title>
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
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../tervezo/" style="background-color: #4ACDA3;">
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
                <b class="d-flex justify-content-end py-3 border-bottom"></b>
                <br>
                <div id="arfolyamok" class="my-3">
                    <h4 class="text-center" style="color: #63ffbe; font-size: 1.2rem;">Árfolyamok</h4>
                    <ul id="arfolyam-lista" class="arfolyam-stilus list-unstyled d-flex flex-column align-items-center"></ul>
                </div>
                <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                    <div>
                        <b id="frissites-ido" style="color: red;" class="text-center d-block"></b>
                    </div>
                <?php endif; ?>
                <b class="d-flex justify-content-end py-3 border-bottom"></b>
                    <?php if (isset($_SESSION['felhasznalo_id'])): ?>
                        <br>
                        <h4 style="color: #63ffbe; font-size: 1.2rem;">Kamatszámítás</h4>
                        <form id="kamatSzamitasUrlap">
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
                        <li class="nav-item"><a class="nav-link" href="../admin/index.php"><p id="adminpanel"><i class="fas fa-cogs"></i> Admin Panel  <div id="felhszam"><?php echo $osszes_felhasznalo; ?></div>
                    </p></a></li>
                    </div>
                    <div>
                        <li class="nav-item"><a class="nav-link" href="../admin/support.php"><p id="supportpanel"><i class="fas fa-users"></i> Support  <div id="supportszam">0<?php echo $varakozo_tamogatasok; ?></div>
                    </p></a></li>
                    </div>
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
                <div class="container" id="tervezoablakok" style="visibility: hidden;">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="mennyit-container">
                                <center>
                                    <h2 id="h2mennyit">Kiadások tervezése</h2>
                                    <button type="button" class="btn btn-primary maxkoltes-gomb button2" data-bs-toggle="modal" data-bs-target="#hetiMaximumModal">
                                        Tervezett kiadások megadása
                                    </button>
                                    <br><br>
                                    <div class="tervezett-koltseg">
                                        <span style="color: white;">Tervezett heti kiadás:</span> 
                                        <span style="color: #63ffbe;"><?php echo $formatált_heti_maximum; ?> Ft</span>
                                    </div>
                                    <div class="tervezett-koltseg">
                                        <span style="color: white;">Tervezett napi kiadás:</span> 
                                        <span style="color: #63ffbe;"><?php echo $formatált_napi_maximum; ?> Ft</span>
                                    </div>
                                </center>
                            </div>

                            <form method="POST" class="tervezo-form mt-4">
                            <center><h2 id="h2mennyit">Rendszeres bevétel / kiadás</h2></center>
                                <div class="mb-3">
                                    <label for="tipus" class="form-label">Típus</label>
                                    <select name="tipus" id="tipus" class="form-select" required onchange="reszletekFrissites()" <?php echo count($tranzakciok) >= 50 ? 'disabled' : ''; ?>>
                                        <option value="Bevétel">Bevétel</option>
                                        <option value="Kiadás">Kiadás</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tipus_reszletezes" class="form-label">Részletezés</label>
                                    <select name="tipus_reszletezes" id="tipus_reszletezes" class="form-select" required <?php echo count($tranzakciok) >= 50 ? 'disabled' : ''; ?>>
                                        <option value="">Válasszon részletezést</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="osszeg" class="form-label">Összeg (Ft)</label>
                                    <input type="number" name="osszeg" id="osszeg" class="form-control" min="1" required <?php echo count($tranzakciok) >= 50 ? 'disabled' : ''; ?>>
                                </div>
                                <div class="mb-3">
                                    <label for="gyakorisag" class="form-label">Gyakoriság</label>
                                    <select name="gyakorisag" id="gyakorisag" class="form-select" required <?php echo count($tranzakciok) >= 50 ? 'disabled' : ''; ?>>
                                        <option value="Napi">Napi</option>
                                        <option value="Heti">Heti</option>
                                        <option value="Kétheti">Kétheti</option>
                                        <option value="Havi">Havi</option>
                                        <option value="Negyedévi">Negyedévi</option>
                                        <option value="Félévi">Félévi</option>
                                        <option value="Évi">Évi</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="datum" class="form-label" id="datumCimke">Dátum: (Mikor történik a jóváírás?)</label>
                                    <input type="date" name="datum" id="datum" class="form-control" value="<?php echo date('Y-m-d'); ?>" required <?php echo count($tranzakciok) >= 50 ? 'disabled' : ''; ?>>
                                </div>
                                <div class="mb-3">
                                    <label for="leiras" class="form-label">Leírás (Opcionális)</label>
                                    <input type="text" name="leiras" id="leiras" class="form-control" <?php echo count($tranzakciok) >= 50 ? 'disabled' : ''; ?>>
                                </div>
                                <button type="submit" class="btn btn-primary button2" <?php echo count($tranzakciok) >= 50 ? 'disabled' : ''; ?>>Hozzáadás</button>
                                <?php if (count($tranzakciok) >= 50): ?>
                                    <p style="color: red;">Elérted a maximum 50 bevétel/kiadás tételt!</p>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['uzenet'])): ?>
                                    <p style="color: red;"><?php echo htmlspecialchars($_SESSION['uzenet']); unset($_SESSION['uzenet']); ?></p>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="col-md-6 mb-4">
                        <div class="celok-ablak mt-4">
                            <h3>Célok naplója</h3>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="uj_cel" class="form-label">Új cél megadása</label>
                                    <input type="text" name="uj_cel" id="uj_cel" class="form-control" required <?php echo count($celok) >= 15 ? 'disabled' : ''; ?>>
                                    <button type="submit" name="cel_hozzaadas" class="btn btn-primary button2 mt-2" <?php echo count($celok) >= 15 ? 'disabled' : ''; ?>>Hozzáadás</button>
                                    <?php if (count($celok) >= 15): ?>
                                        <p style="color: red;">Elérted a maximum 15 célt!</p>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['uzenet'])): ?>
                                        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['uzenet']); unset($_SESSION['uzenet']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-striped celok-tabla">
                                    <thead>
                                        <tr>
                                            <th>Cél</th>
                                            <th>Jegyzet</th>
                                            <th>Művelet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($celok as $cel): ?>
                                            <tr data-label="Sor">
                                                <td data-label="Cél"><?php echo htmlspecialchars($cel['cel']); ?></td>
                                                <td data-label="Jegyzet">
                                                    <form method="POST">
                                                        <input type="hidden" name="cel_id" value="<?php echo $cel['id']; ?>">
                                                        <textarea class="form-control" name="jegyzet" rows="3" maxlength="150" placeholder="Írj jegyzetet ehhez a célhoz..." <?php echo $cel['jegyzet'] ? 'readonly' : ''; ?>><?php echo htmlspecialchars($cel['jegyzet'] ?? ''); ?></textarea>
                                                        <?php if (!$cel['jegyzet']): ?>
                                                            <div class="gomb-kozep">
                                                                <button type="submit" name="jegyzet_mentese" class="btn btn-primary button2 mt-2">Mentés</button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </form>
                                                </td>
                                                <td data-label="Művelet">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="cel_torles" value="<?php echo $cel['id']; ?>">
                                                        <center>
                                                            <button type="submit" class="btn btn-link p-0">
                                                                <i class="bi bi-check-circle text-success pipa-nagy"></i>
                                                            </button>
                                                        </center>
                                                    </form>
                                                    <?php if ($cel['jegyzet']): ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="cel_id" value="<?php echo $cel['id']; ?>">
                                                            <center>
                                                            <div class="gomb-kozep">
                                                                <button type="submit" name="jegyzet_modositasa" class="btn btn-warning button2 mt-2">Módosítás</button>
                                                            </div>
                                                            </center>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="sporoasi-ablak">
                            <h3><span style="color: #63ffbe; font-weight: bold;">$</span> AI ALAPÚ SPÓROLÁSI TIPPEK <span style="color: #63ffbe; font-weight: bold;">$</span></h3>
                            <ul class="sporolas">
                                <li>Használj hűségkártyákat, kuponokat és cashback alkalmazásokat.</li>
                                <li>Válts át előre fizetett mobilcsomagra, ha nem használod ki a havi előfizetésedet.</li>
                                <li>Kerüld a kis értékű, de gyakori kiadásokat (pl. napi kávé, üdítő).</li>
                                <li>Vásárolj használt vagy felújított műszaki cikkeket és bútorokat.</li>
                                <li>Sétálj vagy biciklizz rövidebb utakra, így üzemanyagot spórolsz.</li>
                                <li>Kapcsold ki a készenléti állapotban lévő elektromos eszközöket.</li>
                                <li>Készíts otthon kávét vagy teát ahelyett, hogy naponta vennél egyet.</li>
                                <li>Vásárolj nagyobb kiszerelésben, ha az hosszú távon olcsóbb.</li>
                                <li>Adj el vagy cserélj el olyan dolgokat, amiket már nem használsz.</li>
                                <li>Tervezz meg minden nagyobb vásárlást, és várd meg a leárazásokat.</li>
                                <li>Ne menj éhesen bevásárolni, mert így feleslegesen többet költhetsz.</li>
                                <li>Tanuld meg otthon megjavítani az egyszerűbb dolgokat, így nem kell mindig szakembert hívni.</li>
                                <li>Keresd a helyi termelői piacokat, ahol gyakran olcsóbb és jobb minőségű az áru.</li>
                                <li>Kapcsold ki a fűtést vagy a klímát, ha épp nem vagy otthon.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-striped tranzakcio-tabla">
                                <thead>
                                    <tr>
                                        <th>Típus</th>
                                        <th>Részlet</th>
                                        <th>Összeg</th>
                                        <th>Gyakoriság</th>
                                        <th>Dátum</th>
                                        <th>Leírás</th>
                                        <th>Művelet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tranzakciok as $tranzakcio): ?>
                                        <tr class="<?php echo $tranzakcio['felfuggesztve'] ? 'felfuggesztett' : ''; ?>" data-label="Sor">
                                            <td data-label="Típus"><?php echo htmlspecialchars($tranzakcio['tipus']); ?></td>
                                            <td data-label="Részletezés"><?php echo htmlspecialchars($tranzakcio['tipus_reszletezes'] ?? ''); ?></td>
                                            <td data-label="Összeg (Ft)"><?php echo number_format($tranzakcio['osszeg'], 0, '.', ','); ?></td>
                                            <td data-label="Gyakoriság"><?php echo htmlspecialchars($tranzakcio['gyakorisag']); ?></td>
                                            <td data-label="Dátum"><?php echo htmlspecialchars($tranzakcio['datum']); ?></td>
                                            <td data-label="Leírás"><?php echo htmlspecialchars($tranzakcio['leiras']); ?></td>
                                            <center>
                                            <td data-label="Műveletek">
                                                <form method="POST">
                                                    <input type="hidden" name="torles_id" value="<?php echo $tranzakcio['id']; ?>">
                                                    <button type="submit" class="btn p-0 kuka-kozep">
                                                        <i class="bi bi-trash button3"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            </center>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="hetiMaximumModal" tabindex="-1" aria-labelledby="hetiMaximumModalCimke" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="hetiMaximumModalCimke">Tervezett kiadás beállítása</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" id="hetiMaximumUrlap">
                                    <div class="mb-3">
                                        <label for="heti_maximum" class="form-label">Tervezett heti kiadás (Ft)</label>
                                        <input type="number" name="heti_maximum" id="heti_maximum" class="form-control" min="0" value="<?php echo $heti_maximum ?? ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="napi_maximum" class="form-label">Tervezett napi kiadás (Ft)</label>
                                        <input type="number" name="napi_maximum" id="napi_maximum" class="form-control" min="0" value="<?php echo $napi_maximum ?? ''; ?>">
                                    </div>
                                    <button type="submit" name="heti_maximum_megerosites" class="btn btn-primary button2">Megerősítés</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
    const felhasznaloNev = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
    const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';
    const hetiMaximum = '<?php echo htmlspecialchars($_SESSION["heti_maximum"] ?? ""); ?>';
    const napiMaximum = '<?php echo htmlspecialchars($_SESSION["napi_maximum"] ?? ""); ?>';
    const gyakorisag = '<?php echo htmlspecialchars($_SESSION["gyakorisag"] ?? ""); ?>';
    
    if (felhasznaloNev) {
        document.getElementById('felhasznaloNev').textContent = felhasznaloNev;
        document.getElementById('bejelentkezesopcio').style.display = 'none';
        document.getElementById('profilopcio').style.display = 'block';
        document.getElementById('beallitasopcio').style.display = 'block';
        document.getElementById('kijelentkezesopcio').style.display = 'block';
        document.getElementById('szerepkor').style.visibility = 'visible';
        document.getElementById('perselyegyenleg').style.visibility = 'visible';
        document.getElementById('tervezoablakok').style.visibility = 'visible';
    } else {
        document.getElementById('nemvagybejelentkezve22').style.visibility = 'visible';
    }

    function reszletekFrissites() {
        const tipus = document.getElementById('tipus').value;
        const reszletekKivalasztas = document.getElementById('tipus_reszletezes');
        const datumCimke = document.getElementById('datumCimke');
        
        reszletekKivalasztas.innerHTML = '<option value="">Válasszon részletezést</option>';
        
        if (tipus === 'Bevétel') {
            datumCimke.textContent = 'Dátum: (Mikor történik a jóváírás?)';
        } else {
            datumCimke.textContent = 'Dátum: (Mikor történik a terhelés?)';
        }

        const bevetelLehetosegek = [
            'Ösztöndíj', 'Fizetés', 'Családi támogatás', 'Egyéb'
        ];
        const koltsegLehetosegek = [
            'Előfizetés', 'Lakbér', 'Rezsi', 'Élelmiszer', 'Szórakozás', 'Egyéb'
        ];

        const lehetosegek = tipus === 'Bevétel' ? bevetelLehetosegek : koltsegLehetosegek;
        lehetosegek.forEach(lehetoseg => {
            const opcio = document.createElement('option');
            opcio.value = lehetoseg;
            opcio.textContent = lehetoseg;
            reszletekKivalasztas.appendChild(opcio);
        });
    }

    document.getElementById('datum').addEventListener('input', function(e) {
        const bevitel = this.value;
        const ev = bevitel.split('-')[0];
        if (ev.length > 4) {
            this.value = bevitel.slice(0, -1);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
    reszletekFrissites();

    const tippek = document.querySelectorAll('.sporolas li');
    let jelenlegiIndex = 0;
    const tippekPerOldal = 3;

    function kovetkezoTippekMutatasa() {
        tippek.forEach(tipp => tipp.classList.remove('visible'));

        for (let i = 0; i < tippekPerOldal; i++) {
            let index = (jelenlegiIndex + i) % tippek.length;
            tippek[index].classList.add('visible');
        }

        jelenlegiIndex = (jelenlegiIndex + tippekPerOldal) % tippek.length;
    }

    kovetkezoTippekMutatasa();
    setInterval(kovetkezoTippekMutatasa, 20000);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
<script src="../alapoldal/arfolyam/js.js"></script>
<script src="../alapoldal/kamat/js.js"></script>
</body>
</html>