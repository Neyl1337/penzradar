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

 Perselyegyenleg formázása PHP-ban vesszővel
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="sty.css">
    <link rel="stylesheet" href="naptar.css">


</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-12 col-md-3 col-lg-2 oldalsav">
                <h2 class="text-center">PénzRadar.hu</h2>
                <ul class="nav flex-column flex-md-column mt-4">
                <li class="nav-item"><a class="nav-link" href="../kezdolap/">Kezdőlap</a></li>
                    <li class="nav-item"><a class="nav-link" href="../naptar/">Naptár</a></li>
                    <li class="nav-item"><a class="nav-link" href="../persely/">Persely</a></li>
                    <li class="nav-item"><a class="nav-link" href="../beallitasok/">Beállítások</a></li>
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
                            <li id="profilopcio" style="display:none;"><a class="dropdown-item" href="#">Profilom</a></li>
                            <li id="beallitasopcio" style="display:none;"><a class="dropdown-item" href="../beallitasok/">Beállítások</a></li>
                            <li id="kijelentkezesopcio" style="display:none;"><a class="dropdown-item" href="../adatbazis_logout.php">Kijelentkezés</a></li>
                        </ul>
                    </div>
                </header>
                <div class="dashboard mt-4">

                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
                    <div class="calendar">
                        <div class="header">
                        <div id="prev" class="btn"><i class="fa-solid fa-arrow-left"></i></div>
                        <div id="month-year"></div>
                        <div id="next" class="btn"><i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                    <div class="weekdays">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>
                    <div class="days" id="days"></div>
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
<script src="scr.js"></script>
<script src="naptar.js"></script>
</body>
</html>
