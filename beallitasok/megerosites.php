<?php
session_start();
require_once '../adatbazis.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kod = $_POST['kod'];
    $action = $_SESSION['action'] ?? '';

    if ($action === 'email' && $kod == $_SESSION['email_kod']) {
        $uj_email = $_SESSION['uj_email'];
        $felhasznalo_id = $_SESSION['felhasznalo_id'];

        $stmt = $pdo->prepare("UPDATE felhasznalok SET email = ? WHERE id = ?");
        $stmt->execute([$uj_email, $felhasznalo_id]);

        unset($_SESSION['email_kod']);
        unset($_SESSION['uj_email']);
        unset($_SESSION['action']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Az email cím sikeresen módosítva!', 'redirect' => '../beallitasok/']);
        exit;
    } elseif ($action === 'torles' && $kod == $_SESSION['torles_kod']) {
        $felhasznalo_id = $_SESSION['felhasznalo_id'];

        $pdo->beginTransaction();

        try {
            $stmt1 = $pdo->prepare("DELETE FROM naptar WHERE felhasznalo_id = ?");
            $stmt1->execute([$felhasznalo_id]);

            $stmt2 = $pdo->prepare("DELETE FROM persely WHERE felhasznalo_id = ?");
            $stmt2->execute([$felhasznalo_id]);

            $stmt3 = $pdo->prepare("DELETE FROM tervezo WHERE felhasznalo_nev = ?");
            $stmt3->execute([$felhasznalo_id]);

            $stmt4 = $pdo->prepare("DELETE FROM felhasznalok WHERE id = ?");
            $stmt4->execute([$felhasznalo_id]);

            $pdo->commit();

            unset($_SESSION['torles_kod']);
            unset($_SESSION['action']);
            session_destroy();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'A fiók és minden hozzátartozó adat sikeresen törölve!', 'redirect' => '../adatbazis_logout.php']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Hiba történt a törlés során: ' . $e->getMessage()]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Helytelen kód!']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Megerősítés</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="megerosites.css">
</head>
<body>
    <div class="regisztracios-doboz">
        <h1>PénzRadar</h1>
        <h5><?php echo ($_SESSION['action'] ?? '') === 'torles' ? 'Fiók törlés megerősítése' : 'Email megerősítés'; ?></h5>
        <div class="mb-3" id="Uzenet">
            <center><p></p></center>
        </div>
        <form id="megerositesForm">
            <div class="mb-3">
                <label for="kod" class="form-label">Megerősítő kód</label>
                <input type="text" class="form-control" id="kod" name="kod" placeholder="Kód" required>
            </div>
            <button type="submit" id="megerosites" class="btn btn-zold w-100">Megerősítés</button>
        </form>      
    </div>
    
    <script>
        document.getElementById('megerositesForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const response = await fetch('megerosites.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            const uzenetElem = document.getElementById('Uzenet');
            uzenetElem.style.display = 'block';
            uzenetElem.querySelector('p').textContent = result.message;
            uzenetElem.style.color = result.success ? '#90EE90' : '#FF7F7F';
            if (result.success) {
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
            } else {
                setTimeout(() => {
                    uzenetElem.style.display = 'none';
                }, 3000);
            }
        });
    </script>
</body>
</html>