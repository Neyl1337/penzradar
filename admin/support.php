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

// Support ticket handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['support_action'])) {
    if ($_POST['support_action'] === 'delete_support') {
        $support_id = intval($_POST['support_id']);
        try {
            $delete_query = "DELETE FROM support WHERE id = ?";
            $stmt = $pdo->prepare($delete_query);
            $stmt->execute([$support_id]);
            header("Location: support.php");
            exit();
        } catch (PDOException $e) {
            echo "Hiba történt a support törlése során: " . $e->getMessage();
        }
    } elseif ($_POST['support_action'] === 'update_text') {
        $support_id = intval($_POST['support_id']);
        $new_text = $_POST['new_text'];
        try {
            $update_query = "UPDATE support SET szoveg = ? WHERE id = ?";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$new_text, $support_id]);
            header("Location: support.php");
            exit();
        } catch (PDOException $e) {
            echo "Hiba történt a szöveg frissítése során: " . $e->getMessage();
        }
    } elseif ($_POST['support_action'] === 'update_status') {
        $support_id = intval($_POST['support_id']);
        try {
            $update_query = "UPDATE support SET statusz = 'Megtekintett' WHERE id = ?";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$support_id]);
            // JSON válasz az AJAX kéréshez
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => 'Megtekintett']);
            exit();
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }
}

// Fetch support tickets (including 'ido' and 'statusz' columns)
$support_query = "SELECT id, targy AS target, email, felhasznalo, szoveg, datum, ido, statusz FROM support";
$support_stmt = $pdo->prepare($support_query);
$support_stmt->execute();
$support_tickets = $support_stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$osszes_felhasznalo = $pdo->query("SELECT COUNT(*) FROM felhasznalok")->fetchColumn();
$adminok_szama = $pdo->query("SELECT COUNT(*) FROM felhasznalok WHERE rang = 'Admin'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - ADMIN PANEL</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../alapoldal/alapstilus/style.css">
    <style>
    /* Egyéni stílus a support felülethez */
    #supportTable {
        background-color: #1e1e1e;
        box-shadow: 0 0 15px #63ffbe;
        border-radius: 10px;
        overflow: hidden;
    }
    #supportTable th {
        background-color: #63ffbe;
        color: #121212;
        text-transform: uppercase;
        text-align: center;
        vertical-align: middle;
    }
    #supportTable td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid #444;
    }
    #supportTable tr:hover {
        background-color: #2a2a2a;
    }
    .button-view {
        background-color: #63ffbe;
        color: #121212;
        border: none;
        padding: 6px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }
    .button-view:hover {
        background-color: #55daa2;
        color: #ffffff;
    }
    .button-delete {
        background-color: #dc3545;
        color: #1e1e1e;
        border: none;
        padding: 6px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }
    .button-delete:hover {
        background-color: #c82333;
        color: #ffffff;
    }
    .modal-content {
        background-color: #1e1e1e;
        color: white;
        border: 1px solid #63ffbe;
        box-shadow: 0 0 15px #63ffbe;
        border-radius: 10px;
    }
    .modal-header {
        background-color: #63ffbe;
        color: #121212;
        border-bottom: 1px solid #55daa2;
    }
    .support-text {
        background-color: #4a4a4a; /* Szürke háttér */
        padding: 10px;
        border-radius: 5px;
        font-size: 14px; /* Kisebb betűtípus */
        line-height: 1.5;
    }
    .btn-close {
        filter: invert(1); /* Fehér "X" ikon */
    }
    .status-button {
        padding: 6px 10px;
        border: none;
        border-radius: 5px;
        cursor: default;
        font-size: 14px;
        color: #fff;
    }
    .status-waiting {
        background-color: #ff9800; /* Narancssárga */
    }
    .status-viewed {
        background-color: #2196f3; /* Kék */
    }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-12 col-md-3 col-lg-2 oldalsav">
                <div class="text-center">
                    <img src="../kepek/ujlogo.png" alt="PénzRadar Logó" class="logo">
                </div>
                <p id="Adminszoveg">PénzRadar ADMIN</p>
                <ul class="nav flex-column flex-md-column mt-4">
                    <li class="nav-item"><a class="nav-link" href="../kezdolap/"><i class="fas fa-home"></i> Kezdőlap</a></li>
                    <li class="nav-item"><a class="nav-link" href="../tervezo/"><i class="fas fa-tasks"></i> Tervező</a></li>
                    <li class="nav-item"><a class="nav-link" href="../naptar/"><i class="fas fa-calendar-alt"></i> Naptár</a></li>
                    <li class="nav-item"><a class="nav-link" href="../persely/"><i class="fas fa-piggy-bank"></i> Persely</a></li>
                    <li class="nav-item"><a class="nav-link kapcsolat-link" href="../kapcsolat/"><i class="bi bi-envelope-at-fill"></i> Kapcsolat</a></li>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                        <br>
                        <h4>Statisztikák</h4>
                        <p>Összes felhasználó: <b><?php echo $osszes_felhasznalo; ?></b></p>
                        <p>Adminok száma: <b><?php echo $adminok_szama; ?></b></p>
                        <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                        <li class="nav-item"><a class="nav-link" href="index.php" id="felhasznalok_gomb"><i class="fas fa-users"></i> Felhasználók</a></li>
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
                <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                    <div class="card p-3 mt-3 kartya1">
                        <center>
                            <h3>Jelenleg az Admin felületen vagy!</h3>
                        </center>
                    </div>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <table id="supportTable">
                        <tr>
                            <th colspan="9" class="text-center">
                                <h2>Support Felület</h2>
                            </th>
                        </tr>
                        <tr>
                            <th>ID</th>
                            <th>Tárgy</th>
                            <th class="formaz">Email</th>
                            <th>Felhasználó</th>
                            <th class="formaz">Szöveg</th>
                            <th class="formaz">Dátum</th>
                            <th class="formaz">Idő</th>
                            <th class="formaz">Törlés</th>
                            <th class="formaz">Státusz</th>
                        </tr>
                        <?php foreach ($support_tickets as $ticket): ?>
                            <tr>
                                <td><?= htmlspecialchars($ticket['id']) ?></td>
                                <td><?= htmlspecialchars($ticket['target']) ?></td>
                                <td class="formaz"><?= htmlspecialchars($ticket['email']) ?></td>
                                <td><?= htmlspecialchars($ticket['felhasznalo']) ?></td>
                                <td class="formaz">
                                    <button type="button" class="button-view view-btn" data-support-id="<?= $ticket['id'] ?>" data-bs-toggle="modal" data-bs-target="#viewTextModal<?= $ticket['id'] ?>">Megtekintés</button>
                                </td>
                                <td class="formaz"><?= htmlspecialchars($ticket['datum']) ?></td>
                                <td class="formaz"><?= htmlspecialchars($ticket['ido']) ?></td>
                                <td class="formaz">
                                    <form method="post">
                                        <input type="hidden" name="support_id" value="<?= $ticket['id'] ?>">
                                        <input type="hidden" name="support_action" value="delete_support">
                                        <button type="submit" class="button-delete">Törlés</button>
                                    </form>
                                </td>
                                <td class="formaz">
                                    <span class="status-button <?= $ticket['statusz'] === 'Várakozás' ? 'status-waiting' : 'status-viewed' ?>" id="status-<?= $ticket['id'] ?>">
                                        <?= htmlspecialchars($ticket['statusz']) ?>
                                    </span>
                                </td>
                            </tr>

                            <div class="modal fade" id="viewTextModal<?= $ticket['id'] ?>" tabindex="-1" aria-labelledby="viewTextModalLabel<?= $ticket['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewTextModalLabel<?= $ticket['id'] ?>">Bejelentés tárgya: <?= htmlspecialchars($ticket['target']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="support-text"><?= htmlspecialchars($ticket['szoveg']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded esemény lefutott');

        const viewButtons = document.querySelectorAll('.view-btn');
        console.log('Megtalált gombok száma:', viewButtons.length);

        viewButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                console.log('Megtekintés gomb megnyomva');

                const supportId = this.getAttribute('data-support-id');
                const statusElement = document.getElementById(`status-${supportId}`);

                if (!statusElement) {
                    console.error('Státusz elem nem található, ID:', supportId);
                    return;
                }

                // Státusz ellenőrzés finomítása (whitespace-ek eltávolítása és nagybetű-érzéketlenség)
                const currentStatus = statusElement.textContent.trim().toLowerCase();
                console.log('Jelenlegi státusz:', currentStatus);

                // Azonnali státusz váltás a kliensoldalon
                if (currentStatus !== 'megtekintett') {
                    console.log('Státusz váltás: Várakozás -> Megtekintett');
                    statusElement.textContent = 'Megtekintett';
                    statusElement.classList.remove('status-waiting');
                    statusElement.classList.add('status-viewed');
                } else {
                    console.log('A státusz már Megtekintett, nincs váltás');
                }

                // AJAX kérés a státusz frissítésére a háttérben
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `support_action=update_status&support_id=${supportId}`
                })
                .then(response => {
                    console.log('AJAX válasz státusz:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('AJAX válasz:', data);
                    if (!data.success) {
                        console.error('Hiba a státusz frissítésekor:', data.error);
                        // Ha az adatbázis frissítése nem sikerült, visszaállítjuk a státuszt
                        statusElement.textContent = 'Várakozás';
                        statusElement.classList.remove('status-viewed');
                        statusElement.classList.add('status-waiting');
                    }
                })
                .catch(error => {
                    console.error('AJAX hiba:', error);
                    // Késleltetett visszaállítás
                    setTimeout(() => {
                        statusElement.textContent = 'Várakozás';
                        statusElement.classList.remove('status-viewed');
                        statusElement.classList.add('status-waiting');
                    }, 1000);
                });

                console.log('Modal megnyitása folyamatban...');
            });
        });

        // Meglévő JavaScript kód
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';

        document.getElementById('felhasznalok_gomb').addEventListener('click', function() {
            document.getElementById('usersTable').style.display = 'block';
            document.getElementById('supportTable').style.display = 'none';
        });

        document.getElementById('tamogatas_gomb').addEventListener('click', function() {
            document.getElementById('usersTable').style.display = 'none';
            document.getElementById('supportTable').style.display = 'block';
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../kezdolap/script.js"></script>
</body>
</html>