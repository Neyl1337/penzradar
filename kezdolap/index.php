<?php
require_once '../adatbazis.php';

session_start();

// Ellenőrizzük, hogy a felhasználó be van-e jelentkezve
if (isset($_SESSION['felhasznalo_id'])) {
    $stmt = $pdo->prepare("SELECT rang, perselyegyenleg FROM felhasznalok WHERE id = ?");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['perselyegyenleg'];
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['perselyegyenleg'] = null;
}

// Perselyegyenleg formázása PHP-ban vesszővel
$formatált_egyenleg = isset($_SESSION['perselyegyenleg']) 
    ? number_format($_SESSION['perselyegyenleg'], 0, '.', ',') 
    : '0';
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: white;
        }
        .oldalsav {
            background-color: #1e1e1e;
            min-height: 100vh;
            padding: 20px;
        }
        .oldalsav h2 {
            color: #63ffbe;
        }
        .oldalsav a {
            color: white;
            text-decoration: none;
        }
        .oldalsav a:hover {
            color: #63ffbe;
        }
        .kartya {
            background-color: #1e1e1e;
            color: white;
            border-radius: 10px;
        }
        .kartya b {
            color: #63ffbe;
        }
        @media (max-width: 768px) {
            .oldalsav {
                min-height: auto;
                padding: 10px;
                display: flex;
                justify-content: space-around;
                align-items: center;
            }
            .oldalsav h2 {
                display: none;
            }
        }
        .dropdown-menu {
            background-color: #1e1e1e;
            border: none;
        }
        .dropdown-item {
            color: white;
        }
        .dropdown-item:hover {
            color: #63ffbe;
            background-color: #1e1e1e;
        }
        .btn-secondary {
            background-color: #1e1e1e;
            border-color: #1e1e1e;
        }
        .btn-secondary:hover {
            background-color: #63ffbe;
            border-color: #63ffbe;
        }

        #role, #balance {
            color: #63ffbe;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            #szerepkor, #perselyegyenleg {
                font-size: 0.875rem;
                display: block;
                margin: 0;
            }
            .dropdown {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-12 col-md-3 col-lg-2 oldalsav">
                <h2 class="text-center">PénzRadar.hu</h2>
                <ul class="nav flex-column flex-md-column mt-4">
                    <li class="nav-item"><a class="nav-link" href="#">Kezdőlap</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Naptár</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Persely</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Beállítások</a></li>
                </ul>
            </nav>
            <main class="col-12 col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-end py-3 border-bottom">
                    <div class="dropdown d-flex align-items-center">
                        <span class="me-3" id="szerepkor">Szerepkör: <b style="color: #63ffbe" id="szerepkorText"><?php echo htmlspecialchars($_SESSION['szerepkor'] ?? "Felhasználó"); ?></b></span>
                        <span class="me-3" id="perselyegyenleg">Persely egyenleg: <b style="color: #63ffbe" id="perselyegyenlegText"><?php echo htmlspecialchars($formatált_egyenleg); ?></b> Ft</span>
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="felhasznaloDropdownGomb">
                            <i class="fas fa-user-circle"></i> 
                            <span id="felhasznaloNev">Jelentkezz be!</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="felhasznaloDropdownGomb">
                            <li id="bejelentkezesopcio"><a class="dropdown-item" href="../bejelentkezes/">Bejelentkezés</a></li>
                            <li id="profilopcio" style="display:none;"><a class="dropdown-item" href="#">Profilom</a></li>
                            <li id="beallitasopcio" style="display:none;"><a class="dropdown-item" href="#">Beállítások</a></li>
                            <li id="kijelentkezesopcio" style="display:none;"><a class="dropdown-item" href="../adatbazis_logout.php">Kijelentkezés</a></li>
                        </ul>
                    </div>
                </header>
                <div class="dashboard mt-4">
                    <div class="row g-4">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="kartya p-3 text-center">
                                <h5>Eddigi költés</h5>
                                <b>1,234 Ft</b>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="kartya p-3 text-center">
                                <h5>Átlagos költés</h5>
                                <b>1,234 Ft</b>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="kartya p-3 text-center">
                                <h5>Maradék összeg</h5>
                                <b>1,234 Ft</b>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="kartya p-3 text-center">
                                <h5>Legnagyobb költés</h5>
                                <b>1,234 Ft</b>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = () => {
            const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';
            const formatáltEgyenleg = new Intl.NumberFormat('en-US', { useGrouping: true }).format(egyenleg);

            document.getElementById('perselyegyenlegText').textContent = formatáltEgyenleg;

            const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';

            if (userName) {
                document.getElementById('felhasznaloNev').textContent = userName;
                document.getElementById("bejelentkezesopcio").style.display = "none";
                document.getElementById("profilopcio").style.display = "block";
                document.getElementById("beallitasopcio").style.display = "block";
                document.getElementById("kijelentkezesopcio").style.display = "block";
                document.getElementById("szerepkor").style.display = "block";
                document.getElementById("perselyegyenleg").style.display = "block";
            } else {
                document.getElementById("profilopcio").style.display = "none";
                document.getElementById("beallitasopcio").style.display = "none";
                document.getElementById("kijelentkezesopcio").style.display = "none";
                document.getElementById("szerepkor").style.display = "none";
                document.getElementById("perselyegyenleg").style.display = "none";
            }
        };
            </script>
</body>
</html>
