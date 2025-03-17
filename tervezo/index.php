<?php
require_once '../adatbazis.php';

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Alapértelmezett értékek beállítása, ha nincs bejelentkezve a felhasználó
$_SESSION['szerepkor'] = null;
$_SESSION['perselyegyenleg'] = null;
$_SESSION['havimax'] = null;
$_SESSION['napimax'] = null;
$_SESSION['gyakorisag'] = null;

if (isset($_SESSION['felhasznalo_nev'])) {
    // Felhasználói adatok lekérdezése
    $utasitas = $pdo->prepare("
        SELECT f.rang, p.egyenleg, f.havimax, f.napimax 
        FROM felhasznalok f
        INNER JOIN persely p ON f.id = p.felhasznalo_id
        WHERE f.nev = ?
    ");
    $utasitas->execute([$_SESSION['felhasznalo_nev']]);
    $felhasznalo = $utasitas->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
        $_SESSION['havimax'] = $felhasznalo['havimax'];
        $_SESSION['napimax'] = $felhasznalo['napimax'];
    }

    // Utolsó gyakoriság lekérdezése
    $utasitas_gyakorisag = $pdo->prepare("
        SELECT gyakorisag 
        FROM tervezo 
        WHERE felhasznalo_nev = ? 
        ORDER BY datum DESC 
        LIMIT 1
    ");
    $utasitas_gyakorisag->execute([$_SESSION['felhasznalo_nev']]);
    $utolso_gyakorisag = $utasitas_gyakorisag->fetch(PDO::FETCH_ASSOC);
    $_SESSION['gyakorisag'] = $utolso_gyakorisag['gyakorisag'] ?? null;

    // Célok lekérdezése
    $utasitas_celok = $pdo->prepare("
        SELECT id, cel, teljesitve 
        FROM celok 
        WHERE felhasznalo_nev = ? AND cel IS NOT NULL
    ");
    $utasitas_celok->execute([$_SESSION['felhasznalo_nev']]);
    $celok = $utasitas_celok->fetchAll(PDO::FETCH_ASSOC);

    // Összes költség lekérdezése
    $utasitas_koltseg = $pdo->prepare("
        SELECT SUM(osszeg) as osszes_koltseg 
        FROM tervezo 
        WHERE felhasznalo_nev = ? AND tipus = 'Kiadás' AND felfuggesztve = 0
    ");
    $utasitas_koltseg->execute([$_SESSION['felhasznalo_nev']]);
    $osszes_koltseg = $utasitas_koltseg->fetch(PDO::FETCH_ASSOC)['osszes_koltseg'] ?? 0;

    // Tranzakciók lekérdezése
    $utasitas = $pdo->prepare("
        SELECT id, tipus, osszeg, gyakorisag, leiras, datum, tipus_reszletezes, felfuggesztve 
        FROM tervezo 
        WHERE felhasznalo_nev = ? 
        ORDER BY tipus, gyakorisag
    ");
    $utasitas->execute([$_SESSION['felhasznalo_nev']]);
    $tranzakciok = $utasitas->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Ha nincs bejelentkezve, üres tömböket állítunk be
    $celok = [];
    $osszes_koltseg = 0;
    $tranzakciok = [];
}

$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';

$havimax = $_SESSION['havimax'] ?? null;
$napimax = $_SESSION['napimax'] ?? null;
$gyakorisag = $_SESSION['gyakorisag'] ?? null;
$formatált_havimax = $havimax ? number_format($havimax, 0, '.', ',') : 'Még nincs megadva';
$formatált_napimax = $napimax ? number_format($napimax, 0, '.', ',') : 'Még nincs megadva';

// POST kérések kezelése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['felhasznalo_nev'])) {
    if (isset($_POST['tipus']) && isset($_POST['osszeg']) && isset($_POST['gyakorisag'])) {
        $engedelyezett_gyakorisagok = [
            'Napi', 
            'Heti', 
            'Kétheti', 
            'Havi', 
            'Negyedévi', 
            'Félévi', 
            'Évi'
        ];
        $bevitt_gyakorisag = trim($_POST['gyakorisag']);
        $gyakorisag = in_array($bevitt_gyakorisag, $engedelyezett_gyakorisagok) ? $bevitt_gyakorisag : 'Napi';
        
        $tipus = ($_POST['tipus'] === 'Kiadás') ? 'Kiadás' : 'Bevétel';

        $utasitas = $pdo->prepare("
            INSERT INTO tervezo (felhasznalo_nev, tipus, osszeg, gyakorisag, leiras, datum, tipus_reszletezes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $utasitas->execute([
            $_SESSION['felhasznalo_nev'],
            $tipus,
            $_POST['osszeg'],
            $gyakorisag,
            $_POST['leiras'] ?? '',
            $_POST['datum'],
            $_POST['tipus_reszletezes']
        ]);

        $_SESSION['gyakorisag'] = $gyakorisag;
    }
    
    if (isset($_POST['felfuggeszt_id'])) {
        $utasitas = $pdo->prepare("UPDATE tervezo SET felfuggesztve = 1 WHERE id = ? AND felhasznalo_nev = ?");
        $utasitas->execute([$_POST['felfuggeszt_id'], $_SESSION['felhasznalo_nev']]);
    }
    
    if (isset($_POST['aktiv_id'])) {
        $utasitas = $pdo->prepare("UPDATE tervezo SET felfuggesztve = 0 WHERE id = ? AND felhasznalo_nev = ?");
        $utasitas->execute([$_POST['aktiv_id'], $_SESSION['felhasznalo_nev']]);
    }
    
    if (isset($_POST['torles_id'])) {
        $utasitas = $pdo->prepare("DELETE FROM tervezo WHERE id = ? AND felhasznalo_nev = ?");
        $utasitas->execute([$_POST['torles_id'], $_SESSION['felhasznalo_nev']]);
    }
    
    if (isset($_POST['havimax_megerosit'])) {
        $utasitas = $pdo->prepare("UPDATE felhasznalok SET havimax = ?, napimax = ? WHERE nev = ?");
        $utasitas->execute([$_POST['havimax'], $_POST['napimax'], $_SESSION['felhasznalo_nev']]);
        $_SESSION['havimax'] = $_POST['havimax'];
        $_SESSION['napimax'] = $_POST['napimax'];
    }
    
    if (isset($_POST['cel_hozzaadas'])) {
        $utasitas = $pdo->prepare("
            INSERT INTO celok (felhasznalo_nev, cel, teljesitve) 
            VALUES (?, ?, 0)
        ");
        $utasitas->execute([
            $_SESSION['felhasznalo_nev'],
            $_POST['uj_cel']
        ]);
    }
    
    if (isset($_POST['cel_teljesites'])) {
        $utasitas = $pdo->prepare("UPDATE celok SET teljesitve = 1 WHERE id = ? AND felhasznalo_nev = ?");
        $utasitas->execute([$_POST['cel_teljesites'], $_SESSION['felhasznalo_nev']]);
    }
    
    if (isset($_POST['cel_torles'])) {
        $utasitas = $pdo->prepare("DELETE FROM celok WHERE id = ? AND felhasznalo_nev = ?");
        $utasitas->execute([$_POST['cel_torles'], $_SESSION['felhasznalo_nev']]);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
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
                        <i class="fas fa-home"></i> Kezdőlap
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../tervezo/">
                        <i class="fas fa-tasks <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                        Tervező
                        <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                            <i class="fas fa-lock ms-2"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../naptar/">
                        <i class="fas fa-calendar-alt <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                        Naptár
                        <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                            <i class="fas fa-lock ms-2"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../persely/">
                        <i class="fas fa-piggy-bank <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                        Persely
                        <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                            <i class="fas fa-lock ms-2"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                        <a class="nav-link_kapcsolat <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../kapcsolat/">
                            <i class="bi bi-envelope-at-fill <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                            <span>Kapcsolat</span>
                            <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                                <i class="fas fa-lock ms-2"></i>
                            <?php endif; ?>
                        </a>
                </li>
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
                    <!-- Bal oldali kalkulátor - csak bejelentkezett állapotban, keret nélkül -->
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
                                    <h2 id="h2mennyit">Költések tervezése</h2>
                                    <button type="button" class="btn btn-primary maxkoltes-gomb button2" data-bs-toggle="modal" data-bs-target="#havimaxModal">
                                        Tervezett költések megadása
                                    </button>
                                    <br><br>
                                    <div class="tervezett-koltseg">
                                        <span style="color: white;">Tervezett napi költés:</span> 
                                        <span style="color: #63ffbe;"><?php echo $formatált_napimax; ?></span>
                                    </div>
                                    <div class="tervezett-koltseg">
                                        <span style="color: white;">Tervezett havi költés:</span> 
                                        <span style="color: #63ffbe;"><?php echo $formatált_havimax; ?></span>
                                    </div>
                                </center>
                            </div>

                            <form method="POST" class="tervezo-form mt-4">
                                <div class="mb-3">
                                    <label for="tipus" class="form-label">Típus</label>
                                    <select name="tipus" id="tipus" class="form-select" required onchange="reszletekFrissites()">
                                        <option value="Bevétel">Bevétel</option>
                                        <option value="Kiadás">Kiadás</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tipus_reszletezes" class="form-label">Részletezés</label>
                                    <select name="tipus_reszletezes" id="tipus_reszletezes" class="form-select" required>
                                        <option value="">Válasszon részletezést</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="osszeg" class="form-label">Összeg (Ft)</label>
                                    <input type="number" name="osszeg" id="osszeg" class="form-control" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label for="gyakorisag" class="form-label">Gyakoriság</label>
                                    <select name="gyakorisag" id="gyakorisag" class="form-select" required>
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
                                    <input type="date" name="datum" id="datum" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="leiras" class="form-label">Leírás (Opcionális)</label>
                                    <input type="text" name="leiras" id="leiras" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary button2">Hozzáadás</button>
                            </form>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="celok-ablak mt-4">
                                <h3>Célok naplója</h3>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="uj_cel" class="form-label">Új cél megadása</label>
                                        <input type="text" name="uj_cel" id="uj_cel" class="form-control" required>
                                        <button type="submit" name="cel_hozzaadas" class="btn btn-primary button2 mt-2">Hozzáadás</button>
                                    </div>
                                </form>
                                <table class="table table-striped celok-tabla">
                                    <thead>
                                        <tr>
                                            <th>Cél</th>
                                            <th>Teljesítve</th>
                                            <th>Művelet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($celok as $cel): ?>
                                            <tr class="<?php echo $cel['teljesitve'] ? 'teljesitve' : ''; ?>" data-label="Sor">
                                                <td data-label="Cél"><?php echo htmlspecialchars($cel['cel']); ?></td>
                                                <td data-label="Teljesítve">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="cel_teljesites" value="<?php echo $cel['id']; ?>">
                                                        <input type="checkbox" name="teljesitve_check" <?php echo $cel['teljesitve'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                    </form>
                                                </td>
                                                <td data-label="Művelet">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="cel_torles" value="<?php echo $cel['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm button3">Törlés</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="sporoasi-ablak">
                                <h3><span style="color: #63ffbe; font-weight: bold;">$</span> SPÓROLJ OKOSAN <span style="color: #63ffbe; font-weight: bold;">$</span></h3>
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
                                            <td data-label="Műveletek">
                                                <?php if ($tranzakcio['felfuggesztve']): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="aktiv_id" value="<?php echo $tranzakcio['id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm button2">Aktiválás</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="felfuggeszt_id" value="<?php echo $tranzakcio['id']; ?>">
                                                        <button type="submit" class="btn btn-warning btn-sm button4">Felfüggesztés</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="torles_id" value="<?php echo $tranzakcio['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm button3">Törlés</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal fade" id="havimaxModal" tabindex="-1" aria-labelledby="havimaxModalCimke" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="havimaxModalCimke">Tervezett költés beállítása</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="havimaxFormularus">
                                        <div class="mb-3">
                                            <label for="havimax" class="form-label">Tervezett havi költés (Ft)</label>
                                            <input type="number" name="havimax" id="havimax" class="form-control" min="0" value="<?php echo $havimax ?? ''; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="napimax" class="form-label">Tervezett napi költés (Ft)</label>
                                            <input type="number" name="napimax" id="napimax" class="form-control" min="0" value="<?php echo $napimax ?? ''; ?>">
                                        </div>
                                        <button type="submit" name="havimax_megerosit" class="btn btn-primary button2">Megerősítés</button>
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
        const havimax = '<?php echo htmlspecialchars($_SESSION["havimax"] ?? ""); ?>';
        const napimax = '<?php echo htmlspecialchars($_SESSION["napimax"] ?? ""); ?>';
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
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="../alapoldal/arfolyam/js.js"></script>
    <script src="../alapoldal/kamat/js.js"></script>
</body>
</html>