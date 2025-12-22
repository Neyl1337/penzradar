<?php
session_start();
require_once '../adatbazis.php';

if (!isset($_SESSION['reg_email']) || !isset($_SESSION['reg_kod'])) {
    header("Location: adatbazis_signup.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kod = $_POST['kod'] ?? '';

    if (empty($kod)) {
        echo json_encode(["success" => false, "message" => "A kód megadása kötelező.", "type" => "error"]);
        exit;
    }

    if ($kod == $_SESSION['reg_kod']) {
        $stmt = $pdo->prepare("INSERT INTO felhasznalok (nev, email, jelszo) VALUES (?, ?, ?)");
        if ($stmt->execute([$_SESSION['reg_nev'], $_SESSION['reg_email'], $_SESSION['reg_jelszo']])) {
            $felhasznaloId = $pdo->lastInsertId();

            $stmtPersely = $pdo->prepare("INSERT INTO persely (felhasznalo_id, egyenleg) VALUES (?, ?)");
            $alapertek = 0;
            $stmtPersely->execute([$felhasznaloId, $alapertek]);

            unset($_SESSION['reg_nev']);
            unset($_SESSION['reg_email']);
            unset($_SESSION['reg_jelszo']);
            unset($_SESSION['reg_kod']);

            echo json_encode(["success" => true, "message" => "Regisztráció sikeres!", "redirect" => "../bejelentkezes/"]);
        } else {
            echo json_encode(["success" => false, "message" => "Hiba történt a regisztráció során.", "type" => "error"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Hibás kód.", "type" => "error"]);
    }
    exit;
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
</head>
<body>
    <div class="regisztracios-doboz">
        <h1>PénzRadar</h1>
        <h5>Regisztrációs kód</h5>
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
