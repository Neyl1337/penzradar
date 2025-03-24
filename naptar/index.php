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
        $sql = "SELECT NBevetel, NKiadas, datum FROM naptar WHERE felhasznalo_id = :felhasznalo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':felhasznalo_id' => $_SESSION['felhasznalo_id']]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['perselyegyenleg'] = null;
}

// Perselyegyenleg formázása PHP-ban vesszővel
$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Az adatok feldolgozása
    if (isset($_POST['PBevitel'])) {
        $adat1 = $_POST['PBevitel'];
        $valasztas = $_POST['valaszto'];
        $com = $_POST['ind'];
        $date = $_POST['datum'];

        if ($valasztas === 'bevetel'  ) {
            // Bevétel feltöltés
            $stmt = $pdo->prepare("
                INSERT INTO naptar (felhasznalo_id, ind, datum, NBevetel, NKiadas) 
                VALUES (?, ?, ?, ?, null);
            ");
            $stmt->execute([$_SESSION['felhasznalo_id'], $com, $date, $adat1]);
        } elseif ($valasztas === 'kiadas') {
            // Kiadás feltöltés
            $adat1 = abs($adat1) * -1;

            $stmt = $pdo->prepare("
                INSERT INTO naptar (felhasznalo_id, ind, datum, NBevetel, NKiadas) 
                VALUES (?, ?, ?, null, ?);
            ");
            $stmt->execute([$_SESSION['felhasznalo_id'], $com, $date, $adat1]);
        }
    }

    // Törlés kezelése
    if (isset($_POST['torles_id'])) {
        $utasitas = $pdo->prepare("DELETE FROM naptar WHERE id = ? AND felhasznalo_id = ?");
        $utasitas->execute([$_POST['torles_id'], $_SESSION['felhasznalo_id']]);
    }

    // Átirányítás az oldalra (PRG minta)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Eredmények lekérdezése
$sql = "SELECT id, NBevetel, NKiadas, ind, datum FROM naptar WHERE felhasznalo_id = :felhasznalo_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':felhasznalo_id' => $_SESSION['felhasznalo_id']]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$waiting_supports = $pdo->query("SELECT COUNT(*) FROM support WHERE statusz = 'Várakozás' or statusz = 'Megtekintett' or statusz = 'Folyamatban'")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM felhasznalok")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Naptár</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../tervezo/">
                        <i class="fas fa-tasks <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                        <span class="link-szoveg">Tervező</span>
                        <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                            <i class="fas fa-lock lakat-jobb"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../naptar/" style="background-color: #4ACDA3;">
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
                        <li class="nav-item"><a class="nav-link" href="../admin/index.php"><p id="adminpanel"><i class="fas fa-cogs"></i> Admin Panel  <div id="felhszam"><?php echo $total_users; ?></div>
                    </p></a></li>
                    </div>
                    <div>
                        <li class="nav-item"><a class="nav-link" href="../admin/support.php"><p id="supportpanel"><i class="fas fa-users"></i> Support  <div id="supportszam">0<?php echo $waiting_supports; ?></div>
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
                <div class="dashboard mt-4" id="naptar" style="visibility: hidden;">
                    <div class="container" id="tervezoablakok">
                        <div class="row justify-content-center align-items-start">
                            <div class="col-12 col-md-6 mb-4">
                                <br>
                                <div class="calendar mx-auto" style="width: 100%; max-width: 350px;">
                                    <div class="header d-flex justify-content-between align-items-center">
                                        <div id="prev" class="btn"><i class="fa-solid fa-arrow-left"></i></div>
                                        <div id="month-year"></div>
                                        <div id="next" class="btn"><i class="fa-solid fa-arrow-right"></i></div>
                                    </div>
                                    <div class="weekdays d-flex justify-content-between">
                                        <div>V</div>
                                        <div>H</div>
                                        <div>K</div>
                                        <div>Sz</div>
                                        <div>Cs</div>
                                        <div>P</div>
                                        <div>Szo</div>
                                    </div>
                                    <div class="days" id="days"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-4">
                            <div id="bevitel" class="mx-auto mt-4" style="width: 100%; max-width: 350px;">
                                <form action="" method="POST">
                                <select name="valaszto" id="valaszto">
                                        <option value="bevetel">Bevétel</option>
                                        <option value="kiadas">Kiadás</option>

                                    </select>
                                    <br>
                                    <br>
                                    Írd be a költésed/bevételed:
                                    <input type="number" id="PBevitel" name="PBevitel" required>
                                    <br>
                                    <br>
                                    jelöld meg, mikor (fog) történt:
                                    <input type="date" id="dateInput" name="datum" required>
                                    <br>
                                    <br>
                                    Írd be az indokot
                                    <br>
                                    <input type="text" id="ind" name="ind" required>
                                    <input type="submit" id="kuld">
                                </form>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-12">
                                <table class="table table-striped tranzakcio-tabla mx-auto" style="width: 100%; max-width: 1000px;">
                                    <thead>
                                        <tr>
                                            <th>Összeg</th>
                                            <th>Állapot</th>
                                            <th>Mikor</th>
                                            <th>Indok</th>
                                            <th>Műveletek</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($result as $tranzakcio): ?>
                                            <tr data-label="Sor">
                                                <?php
                                                if ($tranzakcio['NBevetel'] === null) {
                                                    echo '<td data-label="Összeg">' . htmlspecialchars($tranzakcio['NKiadas']) . '</td>';
                                                } elseif ($tranzakcio['NKiadas'] === null) {
                                                    echo '<td data-label="Összeg">' . htmlspecialchars($tranzakcio['NBevetel']) . '</td>';
                                                }

                                                if($tranzakcio['NBevetel'] === null)
                                                {
                                                   echo '<td data-label="Állapot">Kiadás</td>';
                                                }
                                                else
                                                {
                                                    echo '<td data-label="Állapot">Bevétel</td>';

                                                }
                                                ?>
                                                <td data-label="Mikor"><?php echo htmlspecialchars($tranzakcio['datum'] ?? ''); ?></td>
                                                <td data-label="Indok"><?php echo htmlspecialchars($tranzakcio['ind']); ?></td>
                                                <td data-label="Műveletek">
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
                    </div>
                </div>
                        <div class="dashboard mt-4" id="nemvagybejelentkezve" style="visibility: hidden;">
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
</body>
</html>