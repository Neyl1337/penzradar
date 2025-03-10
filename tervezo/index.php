<?php
require_once '../adatbazis.php';

session_start();

if (isset($_SESSION['felhasznalo_nev'])) {
    $stmt = $pdo->prepare("
        SELECT f.rang, p.egyenleg, f.maxkoltes 
        FROM felhasznalok f
        INNER JOIN persely p ON f.id = p.felhasznalo_id
        WHERE f.nev = ?
    ");
    $stmt->execute([$_SESSION['felhasznalo_nev']]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
        $_SESSION['maxkoltes'] = $felhasznalo['maxkoltes'];
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['perselyegyenleg'] = null;
    $_SESSION['maxkoltes'] = null;
}

$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';

$stmt_koltseg = $pdo->prepare("
    SELECT SUM(osszeg) as osszes_koltseg 
    FROM tervezo 
    WHERE felhasznalo_nev = ? AND tipus = 'koltseg' AND felfuggesztve = 0
");
$stmt_koltseg->execute([$_SESSION['felhasznalo_nev']]);
$osszes_koltseg = $stmt_koltseg->fetch(PDO::FETCH_ASSOC)['osszes_koltseg'] ?? 0;
$maxkoltes = $_SESSION['maxkoltes'] ?? null;
$formatált_maxkoltes = $maxkoltes ? number_format($maxkoltes, 0, '.', ',') : 'Még nincs megadva';
$formatált_osszes_koltseg = $osszes_koltseg ? number_format($osszes_koltseg, 0, '.', ',') : 'Még nincs megadva';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tipus']) && isset($_POST['osszeg']) && isset($_POST['gyakorisag'])) {
        $stmt = $pdo->prepare("
            INSERT INTO tervezo (felhasznalo_nev, tipus, osszeg, gyakorisag, leiras, datum, tipus_reszletezes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['felhasznalo_nev'],
            $_POST['tipus'],
            $_POST['osszeg'],
            $_POST['gyakorisag'],
            $_POST['leiras'] ?? '',
            $_POST['datum'],
            $_POST['tipus_reszletezes']
        ]);
    }
    
    if (isset($_POST['felfuggeszt_id'])) {
        $stmt = $pdo->prepare("UPDATE tervezo SET felfuggesztve = 1 WHERE id = ? AND felhasznalo_nev = ?");
        $stmt->execute([$_POST['felfuggeszt_id'], $_SESSION['felhasznalo_nev']]);
    }
    
    if (isset($_POST['aktiv_id'])) {
        $stmt = $pdo->prepare("UPDATE tervezo SET felfuggesztve = 0 WHERE id = ? AND felhasznalo_nev = ?");
        $stmt->execute([$_POST['aktiv_id'], $_SESSION['felhasznalo_nev']]);
    }
    
    if (isset($_POST['torles_id'])) {
        $stmt = $pdo->prepare("DELETE FROM tervezo WHERE id = ? AND felhasznalo_nev = ?");
        $stmt->execute([$_POST['torles_id'], $_SESSION['felhasznalo_nev']]);
    }
    
    if (isset($_POST['maxkoltes_megerősít'])) {
        $stmt = $pdo->prepare("UPDATE felhasznalok SET maxkoltes = ? WHERE nev = ?");
        $stmt->execute([$_POST['maxkoltes'], $_SESSION['felhasznalo_nev']]);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, tipus, osszeg, gyakorisag, leiras, datum, tipus_reszletezes, felfuggesztve 
    FROM tervezo 
    WHERE felhasznalo_nev = ? 
    ORDER BY tipus, gyakorisag
");
$stmt->execute([$_SESSION['felhasznalo_nev']]);
$tranzakciok = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Tervező</title>
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
                <div id="tervezoablakok" style="visibility: hidden;">
                <br>
                <div class="mennyit-container">
                    <center><h2 id="h2mennyit">Mennyit szeretne költeni?</h2></center>
                    <button type="button" class="btn btn-primary maxkoltes-gomb button2" data-bs-toggle="modal" data-bs-target="#maxkoltesModal">
                        Tervezett havi költés megadása
                    </button>
                </div>

                <form method="POST" class="tervezo-form">
                <div class="tervezett-koltseg">
                    <span style="color: white;">Tervezett költés:</span> 
                    <span style="color: #63ffbe;"><?php echo $formatált_osszes_koltseg; ?></span>
                </div>

                    <div class="mb-3">
                        <label for="tipus" class="form-label">Típus</label>
                        <select name="tipus" id="tipus" class="form-select" required onchange="updateReszletek()">
                            <option value="bevétel">Bevétel</option>
                            <option value="koltseg">Költés</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tipus_reszletezes" class="form-label">Részletezés</label>
                        <select name="tipus_reszletezes" id="tipus_reszletezes" class="form-select" required>
                            <option value="">Válassz részletezést</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="osszeg" class="form-label">Összeg (Ft)</label>
                        <input type="number" name="osszeg" id="osszeg" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="gyakorisag" class="form-label">Gyakoriság</label>
                        <select name="gyakorisag" id="gyakorisag" class="form-select" required>
                            <option value="napi">Napi</option>
                            <option value="havi">Havi</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="datum" class="form-label">Dátum</label>
                        <input type="date" name="datum" id="datum" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="leiras" class="form-label">Leírás (opcionális)</label>
                        <input type="text" name="leiras" id="leiras" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary button2">Hozzáadás</button>
                </form>

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

                <div class="modal fade" id="maxkoltesModal" tabindex="-1" aria-labelledby="maxkoltesModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="maxkoltesModalLabel">Tervezett havi költés beállítása</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" id="maxkoltesForm">
                                    <div class="mb-3">
                                        <label for="maxkoltes" class="form-label">Tervezett havi költés (Ft)</label>
                                        <input type="number" name="maxkoltes" id="maxkoltes" class="form-control" min="0" value="<?php echo $maxkoltes ?? ''; ?>">
                                    </div>
                                    <button type="submit" name="maxkoltes_megerősít" class="btn btn-primary button2">Megerősítés</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard mt-4" id="nemvagybejelentkezve22" style="visibility: hidden;">
                    <div class="card p-3 mt-3 kartya1">
                        <center>
                            <h3>Jelenleg Nem vagy bejelentkezve!</h3>
                            <h4>Jelentkezz be <a href="../bejelentkezes/">itt</a></h4>
                            <h5>Amennyiben még nem regisztráltál, <a href="../regisztracio/">itt</a> megteheted</h5>
                        </center>
                    </div>
                </div>
            </main>
        </div>
    </div>
    </div>
    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';
        const maxkoltes = '<?php echo htmlspecialchars($_SESSION["maxkoltes"] ?? ""); ?>';
        
        if (userName) {
            document.getElementById('felhasznaloNev').textContent = userName;
            document.getElementById('bejelentkezesopcio').style.display = 'none';
            document.getElementById('profilopcio').style.display = 'block';
            document.getElementById('beallitasopcio').style.display = 'block';
            document.getElementById('kijelentkezesopcio').style.display = 'block';
            document.getElementById('szerepkor').style.visibility = 'visible';
            document.getElementById('perselyegyenleg').style.visibility = 'visible';
        }

        function updateReszletek() {
            const tipus = document.getElementById('tipus').value;
            const reszletekSelect = document.getElementById('tipus_reszletezes');
            reszletekSelect.innerHTML = '<option value="">Válassz részletezést</option>';

            const bevetelOpciok = [
                'Ösztöndíj', 'Fizetés', 'Családi támogatás', 'Egyéb'
            ];
            const koltsegOpciok = [
                'Előfizetés', 'Lakbér', 'Rezsi', 'Élelmiszer', 'Szórakozás', 'Egyéb'
            ];

            const opciok = tipus === 'bevétel' ? bevetelOpciok : koltsegOpciok;
            opciok.forEach(opcio => {
                const option = document.createElement('option');
                option.value = opcio;
                option.textContent = opcio;
                reszletekSelect.appendChild(option);
            });
        }

        document.getElementById('datum').addEventListener('input', function(e) {
            const input = this.value;
            const year = input.split('-')[0];
            if (year.length > 4) {
                this.value = input.slice(0, -1);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            updateReszletek();
            if (maxkoltes) {
                document.querySelector('.tervezett-koltseg').innerHTML = 
                '<span style="color: white;">Tervezett havi költés:</span> ' + 
                '<span style="color: #63ffbe;">' + maxkoltes + '</span>';
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="../alapoldal/arfolyam/js.js"></script>
    <script src="../alapoldal/kamat/js.js"></script>
</body>
</html>