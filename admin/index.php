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

// POST kérések kezelése
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);

        try {
            $pdo->beginTransaction();

            $delete_persely = "DELETE FROM persely WHERE felhasznalo_id = ?";
            $stmt = $pdo->prepare($delete_persely);
            $stmt->execute([$delete_id]);

            $delete_user = "DELETE FROM felhasznalok WHERE id = ?";
            $stmt = $pdo->prepare($delete_user);
            $stmt->execute([$delete_id]);

            $pdo->commit();

            // Cache frissítése: Session törlése, ha a felhasználó saját magát törli
            if (isset($_SESSION['felhasznalo_id']) && $_SESSION['felhasznalo_id'] == $delete_id) {
                // Session törlése
                session_unset();
                session_destroy();
                // Átirányítás a bejelentkezési oldalra
                header("Location: ../bejelentkezes/");
                exit();
            } else {
                // Normál esetben csak az oldal frissítése
                header("Location: index.php");
                exit();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "Hiba történt a törlés során: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['edit_id']) && !isset($_POST['update_user'])) {
        $edit_id = intval($_POST['edit_id']);
        $new_rank = trim($_POST['new_rank']);

        if (!empty($new_rank) && $new_rank !== "valassz") {
            try {
                $update_query = "UPDATE felhasznalok SET rang = ? WHERE id = ?";
                $stmt = $pdo->prepare($update_query);
                $stmt->execute([$new_rank, $edit_id]);

                header("Location: index.php");
                exit();
            } catch (PDOException $e) {
                echo "Hiba történt a rang módosítása során: " . $e->getMessage();
            }
        } else {
            echo "Kérlek, válassz érvényes rangot!";
        }
    }
    elseif (isset($_POST['update_user'])) {
        $edit_id = intval($_POST['edit_id']);
        $new_name = trim($_POST['new_name']);
        $new_email = trim($_POST['new_email']);
        $new_rank = trim($_POST['new_rank']);

        if (!empty($new_name) && !empty($new_email) && !empty($new_rank)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT nev, email, rang FROM felhasznalok WHERE id = ?");
                $stmt->execute([$edit_id]);
                $current_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($current_data && ($current_data['nev'] !== $new_name || $current_data['email'] !== $new_email || $current_data['rang'] !== $new_rank)) {
                    $update_query = "UPDATE felhasznalok SET nev = ?, email = ?, rang = ? WHERE id = ?";
                    $stmt = $pdo->prepare($update_query);
                    $success = $stmt->execute([$new_name, $new_email, $new_rank, $edit_id]);

                    if ($success) {
                        $pdo->commit();
                        header("Location: index.php");
                        exit();
                    } else {
                        throw new PDOException("A frissítés nem sikerült.");
                    }
                } else {
                    $pdo->commit();
                    echo '<script>alert("Nincs változás a mezőkben!"); window.location.href = "index.php";</script>';
                    exit();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "Hiba történt a frissítés során: " . $e->getMessage();
            }
        } else {
            echo "Minden mezőt ki kell tölteni!";
        }
    }
}

// Szűrés rang és név alapján
$filter_rank = $_GET['rank'] ?? '';
$filter_name = $_GET['name'] ?? '';
$query = "SELECT id, nev, email, rang, regisztracio_idopont FROM felhasznalok";
$params = [];
$where_clauses = [];

if (!empty($filter_rank)) {
    $where_clauses[] = "rang = ?";
    $params[] = $filter_rank;
}

if (!empty($filter_name)) {
    $where_clauses[] = "nev LIKE ?";
    $params[] = "%$filter_name%";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statisztikák lekérdezése
$osszes_felhasznalo = $pdo->query("SELECT COUNT(*) FROM felhasznalok")->fetchColumn();
$adminok_szama = $pdo->query("SELECT COUNT(*) FROM felhasznalok WHERE rang = 'Admin'")->fetchColumn();
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
                    <li class="nav-item"><a class="nav-link" href="../tervezo/"><i class="fas fa-tasks"></i> Tervező</a></li>
                    <li class="nav-item"><a class="nav-link" href="../naptar/"><i class="fas fa-calendar-alt"></i> Naptár</a></li>
                    <li class="nav-item"><a class="nav-link" href="../persely/"><i class="fas fa-piggy-bank"></i> Persely</a></li>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                        <b class="d-flex justify-content-end py-3 border-bottom"></b>
                        <div class="admin-panel p-3 mt-4">
                            <h4>Statisztikák</h4>
                            <p>Összes felhasználó: <b><?php echo $osszes_felhasznalo; ?></b></p>
                            <p>Adminok száma: <b><?php echo $adminok_szama; ?></b></p>
                        </div>
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
                    <?php endif; ?>
                    <table>
                        <tr>
                            <th colspan="4" class="text-center">
                                <h4 class="text-center m-0">Felhasználók kezelése</h4>
                            </th>
                            <th colspan="4">
                                <div class="filter-container card p-3 mt-3 kartya1">
                                    <form id="filterForm" method="GET" class="filter-form">
                                        <div>
                                            <label for="rank">Szűrés rang szerint:</label>
                                            <select name="rank" id="rank">
                                                <option value="">-- Összes rang --</option>
                                                <option value="Felhasználó" <?= isset($_GET['rank']) && $_GET['rank'] == "Felhasználó" ? "selected" : "" ?>>Felhasználó</option>
                                                <option value="VIP" <?= isset($_GET['rank']) && $_GET['rank'] == "VIP" ? "selected" : "" ?>>VIP</option>
                                                <option value="Admin" <?= isset($_GET['rank']) && $_GET['rank'] == "Admin" ? "selected" : "" ?>>Admin</option>
                                            </select>
                                        </div>
                                        <div style="margin-left: 30px;">
                                            <label for="name_filter">Szűrés név szerint:</label>
                                            <input type="text" id="name_filter" name="name" value="<?= htmlspecialchars($filter_name) ?>" placeholder="Írd be a nevet..." class="form-control">
                                        </div>
                                        <button type="submit" class="filter-button">Szűrés</button>
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
                            <th class="formaz">Módosítás</th>
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
                                <td class="formaz">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                        <button type="button" class="btn btn-secondary" id="toggleKiegeszito<?= $row['id'] ?>">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="newRow<?= $row['id'] ?>" style="display: none;">
                                <form method="post">
                                    <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="update_user" value="1">
                                    <td colspan="2">
                                        <div class="form-group">
                                            <label for="new_name<?= $row['id'] ?>">Név módosítása:</label>
                                            <input type="text" id="new_name<?= $row['id'] ?>" name="new_name" value="<?= htmlspecialchars($row['nev']) ?>" class="form-control">
                                        </div>
                                    </td>
                                    <td colspan="3">
                                        <div class="form-group">
                                            <label for="new_email<?= $row['id'] ?>">Email módosítása:</label>
                                            <input type="text" id="new_email<?= $row['id'] ?>" name="new_email" value="<?= htmlspecialchars($row['email']) ?>" class="form-control">
                                        </div>
                                    </td>
                                    <td colspan="2">
                                        <div class="form-group">
                                            <label for="new_rank<?= $row['id'] ?>">Egyedi rang:</label>
                                            <input type="text" id="new_rank<?= $row['id'] ?>" name="new_rank" value="<?= htmlspecialchars($row['rang']) ?>" class="form-control">
                                        </div>
                                    </td>
                                    <td class="form-group">
                                        <button type="submit" class="btn btn-primary">Mentés</button>
                                    </td>
                                </form>
                            </tr>
                            <script>
                                document.getElementById('toggleKiegeszito<?= $row['id'] ?>').addEventListener('click', function() {
                                    const newRow = document.getElementById('newRow<?= $row['id'] ?>');
                                    const icon = this.querySelector('i');
                                    if (newRow.style.display === 'none') {
                                        newRow.style.display = 'table-row';
                                        icon.classList.remove('fa-chevron-down');
                                        icon.classList.add('fa-chevron-up');
                                    } else {
                                        newRow.style.display = 'none';
                                        icon.classList.remove('fa-chevron-up');
                                        icon.classList.add('fa-chevron-down');
                                    }
                                });
                            </script>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';

        // Rang szűrés automatikusan a kiválasztáskor
        document.getElementById('rank').addEventListener('change', function() {
            const selectedRank = this.value;
            const nameFilter = document.getElementById('name_filter').value;
            let url = 'index.php';
            const params = [];
            if (selectedRank) {
                params.push('rank=' + encodeURIComponent(selectedRank));
            }
            if (nameFilter) {
                params.push('name=' + encodeURIComponent(nameFilter));
            }
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            window.location.href = url;
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../kezdolap/script.js"></script>
</body>
</html>