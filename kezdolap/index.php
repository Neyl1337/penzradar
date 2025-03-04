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
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Kezdőlap</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../hirdetes/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #introModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            opacity: 1;
            transition: opacity 1s ease-in-out;
            pointer-events: none;
        }
        #introModal.fade-out {
            opacity: 0;
        }
        #introModal video {
            width: 100vw;
            height: 100vh;
            object-fit: cover;
        }
        #mainContent {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Intro videó modális ablak -->
    <div id="introModal">
        <video id="introVideo" autoplay muted>
            <source src="../videok/intro.mp4" type="video/mp4">
            A böngésződ nem támogatja a videó lejátszását.
        </video>
    </div>

    <div class="container-fluid" id="mainContent">
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
                    <!-- Bal oldali kalkulátor - csak bejelentkezett állapotban -->
                    <?php if (isset($_SESSION['felhasznalo_id'])): ?>
                    <div class="kamat-container my-3">
                        <h4>Kamatszámítás</h4>
                        <form id="kamatSzamitasForm">
                            <div class="mb-2">
                                <label for="alapOsszeg">Tőke (Ft):</label>
                                <input type="number" id="alapOsszeg" class="form-control" min="0" value="<?php echo htmlspecialchars($formatált_egyenleg); ?>">
                            </div>
                            <div class="mb-2">
                                <label for="kamatSzazalek">Kamatláb (%):</label>
                                <input type="number" id="kamatSzazalek" class="form-control" min="0" step="0.1" value="5">
                            </div>
                            <div class="mb-2">
                                <label for="idotartam">Futamidő (év):</label>
                                <input type="number" id="idotartam" class="form-control" min="1" value="1">
                            </div>
                            <button type="button" class="btn btn-primary w-100" onclick="szamitKamat()">Számítás</button>
                        </form>
                        <p id="kamatEredmeny" class="mt-2"></p>
                    </div>
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
                <div class="dashboard mt-4" id="statisztika" style="visibility: hidden;">
                    <section id="bevetelek">
                        <h3 class="text-center">Bevételek</h3>
                        <br>
                        <div class="row g-4">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Napi bevétel</h5>
                                    <b>0 Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Havi bevétel</h5>
                                    <b>0 Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Átlagos bevétel</h5>
                                    <b>0 Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Legnagyobb bevétel</h5>
                                    <b>0 Ft</b>
                                </div>
                            </div>
                        </div>
                        <br><br>
                        <div class="container text-center">
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-6 grafikon-container">
                                    <canvas id="haviBevetelChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </section>
                    <br><br>
                    <hr>
                    <section id="kiadasok">
                        <h3 class="text-center">Költések</h3>
                        <br>
                        <div class="row g-4">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Napi költés</h5>
                                    <b>0 Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Havi költés</h5>
                                    <b>0 Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Átlagos költés</h5>
                                    <b>0 Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Legnagyobb költés</h5>
                                    <b>0 Ft</b>
                                </div>
                            </div>
                        </div>
                        <br><br>
                        <div class="container text-center">
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-6 grafikon-container">
                                    <canvas id="haviKoltesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </section>
                    <br><br>
                    <hr>
                </div>
                <div class="dashboard mt-4" id="nemvagybejelentkezve" style="visibility: hidden;">
                    <div class="card p-3 mt-3 kartya1">
                        <center>
                            <h3>Jelenleg Nem vagy bejelentkezve!</h3>
                            <h4>Jelentkezz be <a href="../bejelentkezes/">itt</a></h4>
                            <h5>Amennyiben még nem regisztráltál, <a href="../regisztracio/">itt</a> megteheted</h5>
                        </center>
                    </div>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <div class="ad-container">
                        <h1 id="title"></h1>
                        <div class="subtitle" id="subtitle"></div>
                        <div class="calculator">
                            <div class="circle"></div>
                            <div class="counter" id="counter"></div>
                        </div>
                        <a href="../regisztracio/" class="cta-button" id="cta"></a>
                    </div>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <!-- Jobb oldali kalkulátor - csak kijelentkezett állapotban -->
                    <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                    <div class="card p-3 mt-3 kartya1">
                        <div class="kamat-container my-3">
                            <h4>Kamatszámítás</h4>
                            <form id="kamatSzamitasForm">
                                <div class="mb-2">
                                    <label for="alapOsszeg">Tőke (Ft):</label>
                                    <input type="number" id="alapOsszeg" class="form-control" min="0" value="<?php echo htmlspecialchars($formatált_egyenleg); ?>">
                                </div>
                                <div class="mb-2">
                                    <label for="kamatSzazalek">Kamatláb (%):</label>
                                    <input type="number" id="kamatSzazalek" class="form-control" min="0" step="0.1" value="5">
                                </div>
                                <div class="mb-2">
                                    <label for="idotartam">Futamidő (év):</label>
                                    <input type="number" id="idotartam" class="form-control" min="1" value="1">
                                </div>
                                <button type="button" class="btn btn-primary w-100" onclick="szamitKamat()">Számítás</button>
                            </form>
                            <p id="kamatEredmeny" class="mt-2"></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';

        // Videó lejátszás kezelése
        document.addEventListener('DOMContentLoaded', function() {
            const introVideo = document.getElementById('introVideo');
            const introModal = document.getElementById('introModal');
            const mainContent = document.getElementById('mainContent');

            const isFirstVisitInTab = !sessionStorage.getItem('hasVisitedInTab');
            const isLoggedIn = '<?php echo isset($_SESSION["felhasznalo_id"]) ? "true" : "false"; ?>';

            if (!isFirstVisitInTab || isLoggedIn !== "true") {
                introModal.style.display = 'none';
                return;
            }

            introVideo.play().then(() => {
                sessionStorage.setItem('hasVisitedInTab', 'true');
            }).catch(function(error) {
                console.log("A videó automatikus lejátszása nem sikerült: ", error);
                introModal.classList.add('fade-out');
                setTimeout(() => {
                    introModal.style.display = 'none';
                }, 1000);
            });

            introVideo.onended = function() {
                introModal.classList.add('fade-out');
                setTimeout(() => {
                    introModal.style.display = 'none';
                }, 1000);
            };

            introVideo.onerror = function() {
                introModal.classList.add('fade-out');
                setTimeout(() => {
                    introModal.style.display = 'none';
                }, 1000);
            };
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="../hirdetes/js.js"></script>
</body>
</html>