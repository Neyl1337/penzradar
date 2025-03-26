<?php
require_once '../adatbazis.php';

session_start();

if (isset($_SESSION['felhasznalo_id'])) {
    $stmt = $pdo->prepare("
        SELECT f.rang, f.email, p.egyenleg 
        FROM felhasznalok f
        INNER JOIN persely p ON f.id = p.felhasznalo_id
        WHERE f.id = ?
    ");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['email'] = $felhasznalo['email'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['email'] = null;
    $_SESSION['perselyegyenleg'] = null;
}

$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';

session_start();

$felhasznaloId = $_SESSION["felhasznalo_id"] ?? null;

if ($felhasznaloId) {
    $stmt = $pdo->prepare("SELECT regisztracio_idopont FROM felhasznalok WHERE id = :id");
    $stmt->execute(['id' => $felhasznaloId]);
    $regisztracioIdopont = $stmt->fetchColumn();

    if ($regisztracioIdopont) {
        $_SESSION["regisztracio_idopont"] = $regisztracioIdopont;
    }
}

$waiting_supports = $pdo->query("SELECT COUNT(*) FROM support WHERE statusz = 'Várakozás' or statusz = 'Megtekintett' or statusz = 'Folyamatban'")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM felhasznalok")->fetchColumn();
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
                <div id="szemelyes" style="visibility: hidden;" class="mt-4 mb-4 settings-container">
                    <div class="container mt-4">
                        <h2 id="szemelyesadat">Személyes Adatok</h2>
                        <form id="profilbox" method="POST" action="felhasznalo_modositas.php">
                            <div class="mb-3" >
                                <label class="form-label">Név</label>
                                <div class="form-check">
                                    <label class="form-check-label" for="nevValtoztatas"><?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?></label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <div class="form-check">
                                    <label class="form-check-label" for="emailValtoztatas"><?php echo htmlspecialchars($_SESSION["email"] ?? ""); ?></label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Regisztráció dátuma</label>
                                <div class="form-check">
                                    <label class="form-check-label" for="nevValtoztatas"><?php echo htmlspecialchars($_SESSION["regisztracio_idopont"] ?? ""); ?></label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Valuta</label>
                                <div class="form-check">
                                <select name="valaszto" id="valaszto">
                                        <option value="HUF">HUF</option>
                                        <option value="EUR">EUR</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Születési Dátum</label>
                                <div class="form-check">
                                <label class="form-check-label"><?php?></label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nem</label>
                                <div class="form-check">
                                <label class="form-check-label"><?php?></label>
                                </div>
                            </div>
                        </form>
                    </div>
            




              
                    <div class="container mt-4 ">
                        <h2 id="profilod">Web Adatok</h2>
                        <form id="profilbox" method="POST">
                        <div class="mb-3">
                                <label class="form-label">FelhasználóNév</label>
                                <div class="form-check">
                                    <label class="form-check-label" for="nevValtoztatas"><?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?></label>
                                </div>
                        </div>

                        <div class="mb-3">
                                <label class="form-label">Össz költés</label>
                                <div class="form-check">
                                    <label for=""></label>
                                </div>
                        </div>

                        <div class="mb-3">
                                <label class="form-label">Össz Bevétel </label>
                                <div class="form-check">
                                    <label for=""></label>
                                </div>
                        </div>
                        </form>
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
