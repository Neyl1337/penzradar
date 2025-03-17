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
    $adat1 = $_POST['PBevitel'];
    $com = $_POST['ind'];
    $date = $_POST['datum'];

    if ($adat1 > 0) {
        // Bevétel feltöltés
        $stmt = $pdo->prepare("
            INSERT INTO naptar (felhasznalo_id, ind, datum, NBevetel, NKiadas) 
            VALUES (?, ?, ?, ?, null);
        ");
        $stmt->execute([$_SESSION['felhasznalo_id'], $com, $date, $adat1]);
    } elseif ($adat1 < 0) {
        // Kiadás feltöltés
        $stmt = $pdo->prepare("
            INSERT INTO naptar (felhasznalo_id, ind, datum, NBevetel, NKiadas) 
            VALUES (?, ?, ?, null, ?);
        ");
        $stmt->execute([$_SESSION['felhasznalo_id'], $com, $date, $adat1]);
    }

    // Átirányítás az oldalra (PRG minta)
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

// Eredmények lekérdezése
$sql = "SELECT NBevetel, NKiadas, datum FROM naptar WHERE felhasznalo_id = :felhasznalo_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':felhasznalo_id' => $_SESSION['felhasznalo_id']]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Naptár</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="naptar.css">
    <link rel="stylesheet" href="../alapoldal/alapstilus/style.css">
    <link rel="stylesheet" href="../alapoldal/kamat/style.css">
    <link rel="stylesheet" href="../alapoldal/arfolyam/style.css">
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
                        <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
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
                <div class="dashboard mt-4" id="statisztika" style="visibility: hidden;">
                    <div class="calendar">
                        <div class="header">
                        <div id="prev" class="btn"><i class="fa-solid fa-arrow-left"></i></div>
                        <div id="month-year"></div>
                        <div id="next" class="btn"><i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                    <div class="weekdays">
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

                    <div id="bevitel">
                    <form action="" method="POST">
                    Írd be a költésed/bevételed:
                    <input type="number" id="PBevitel" name="PBevitel" required>
                    <br>
                    <br>
                    jelöld meg, mikor (fog) történt:
                    <input type="date" id="dateInput" name="datum" required>
                    <br>
                    Írd be az indokot
                    <br>
                    <input type="text" id="ind" name="ind" required>
                    <input type="submit" id="kuld">
                </form>


                    </div> 

                    <div id="kiiratas">   
                        <div id="B_K">
                        Bevétel/Kiadás
                        </div>
                        <div>
                            <?php
                                  if ($result && count($result) > 0) {
                                    foreach ($result as $row) {
                                       if($row["NBevetel"] != null)
                                       {
                                            echo $row["datum"] . " " . $row["NBevetel"] ."FT" ."<br>";
                                        }
                                        else
                                        {
                                            echo $row["datum"] . " " . $row["NKiadas"] ."FT" .  "<br>";
                                        }
                                    }
                                } else {
                                    echo "Nincs találat";
                                }
                        ?>
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
