<?php
require_once '../adatbazis.php';

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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

// Felhasználók lekérdezése az adatbázisból
$query = "SELECT id, nev, email, rang, regisztracio_idopont FROM felhasznalok";
$stmt = $pdo->query($query);

if ($stmt) {
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $result = []; // Ha hiba van, üres tömb, hogy elkerüljük a hibát
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        // Felhasználó törlése
        $delete_id = intval($_POST['delete_id']);

        try {
            $pdo->beginTransaction();

            // Kapcsolódó rekordok törlése a persely táblából
            $delete_persely = "DELETE FROM persely WHERE felhasznalo_id = ?";
            $stmt = $pdo->prepare($delete_persely);
            $stmt->execute([$delete_id]);

            // Felhasználó törlése a felhasznalok táblából
            $delete_user = "DELETE FROM felhasznalok WHERE id = ?";
            $stmt = $pdo->prepare($delete_user);
            $stmt->execute([$delete_id]);

            $pdo->commit();

            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "Hiba történt a törlés során: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_id'])) {
        // Felhasználó szerkesztése
        $edit_id = intval($_POST['edit_id']);
        $new_rank = trim($_POST['new_rank']); // Üres karakterek levágása

        // Ellenőrizzük, hogy a rang valóban ki lett-e választva
        if (!empty($new_rank) && $new_rank !== "valassz") {  
            $update_query = "UPDATE felhasznalok SET rang = ? WHERE id = ?";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$new_rank, $edit_id]);
        }

        header("Location: index.php");
        exit();
    }
}

// Szűrés rang alapján (GET kérésből)
$filter_rank = $_GET['rank'] ?? ''; // Ha nincs kiválasztva, akkor üres marad

$query = "SELECT id, nev, email, rang, regisztracio_idopont FROM felhasznalok";

// Ha a felhasználó kiválasztott egy rangot, szűrjük az adatokat
if (!empty($filter_rank)) {
    $query .= " WHERE rang = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$filter_rank]);
} else {
    $stmt = $pdo->query($query);
}

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);




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
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        <nav class="col-12 col-md-3 col-lg-2 oldalsav">
                <h2 class="text-center">PénzRadar</h2>
                <ul class="nav flex-column flex-md-column mt-4">
                    <li class="nav-item"><a class="nav-link" href="../kezdolap/"><i class="fas fa-home"></i> Kezdőlap</a></li>
                    <li class="nav-item"><a class="nav-link" href="../naptar/"><i class="fas fa-calendar-alt"></i> Naptár</a></li>
                    <li class="nav-item"><a class="nav-link" href="../persely/"><i class="fas fa-piggy-bank"></i> Persely</a></li>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                        <div>
                            <b id="frissites-ido" style="color: red;"> 
                                <!-- A frissítés időpontja itt jelenik meg -->
                            </b>
                        </div>
                    <?php endif; ?>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b>
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
                <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                <div class="card p-3 mt-3 kartya1">
                        <center>
                        <h3>Jelenleg az Admin felületen vagy!</h3>
                        </center>
                </div>
                <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <table>
                    <tr>
                        <th colspan="4" class="text-center">
                            <h4 class="text-center m-0">Felhasználók kezelése</h4>
                        </th>
                        <th colspan="3">
                            <div class="filter-container card p-3 mt-3 kartya1">
                                <form method="GET" class="filter-form">
                                    <label for="rank">Szűrés rang szerint:</label>
                                    <select name="rank" id="rank">
                                        <option value="">-- Összes rang --</option>
                                        <option value="Felhasználó" <?= isset($_GET['rank']) && $_GET['rank'] == "Felhasználó" ? "selected" : "" ?>>Felhasználó</option>
                                        <option value="VIP" <?= isset($_GET['rank']) && $_GET['rank'] == "VIP" ? "selected" : "" ?>>VIP</option>
                                        <option value="Admin" <?= isset($_GET['rank']) && $_GET['rank'] == "Admin" ? "selected" : "" ?>>Admin</option>
                                    </select>
                                    <button type="submit">Szűrés</button>
                                </form>
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <th>ID</th>
                        <th>Felhasználó</th>
                        <th class="formaz">Email</th>
                        <th>Rang</th>
                        <th class="formaz">Regisztráció</th>
                        <th class="formaz">Rangkezelés</th>
                        <th class="formaz">Törlés</th>
                    </tr>
                        <?php foreach ($result as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['nev']) ?></td>
                                <td class="formaz"><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['rang']) ?></td>
                                <td class="formaz"><?= htmlspecialchars($row['regisztracio_idopont']) ?></td>
                                <td class="formaz">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                        <select name="new_rank">
                                            <option value="valassz" disabled selected>Válassz rangot</option>
                                            <option value="Felhasználó">Felhasználó</option>
                                            <option value="VIP">VIP</option>
                                            <option value="Admin">Admin</option>
                                        </select>
                                        <button type="submit" class="button-edit">Módosítás</button>
                                    </form>
                                </td>
                                <td class="formaz">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="button-delete">Törlés</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
            </main>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="../kezdolap/script.js"></script>
</body>
</html>
