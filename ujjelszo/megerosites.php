<?php
session_start();
require_once '../adatbazis.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['kod'])) {
    header("Location: adatbazis_signup.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kod = $_POST['kod'] ?? '';
    $jelszo = $_POST['jelszo'] ?? '';
    $jelszo_megerosites = $_POST['jelszo_megerosites'] ?? '';

    if (empty($kod) || empty($jelszo) || empty($jelszo_megerosites)) {
        echo json_encode(["success" => false, "message" => "Minden mező kitöltése kötelező.", "type" => "error"]);
        exit;
    }

    if ($kod != $_SESSION['kod']) {
        echo json_encode(["success" => false, "message" => "Hibás kód.", "type" => "error"]);
        exit;
    }

    if ($jelszo !== $jelszo_megerosites) {
        echo json_encode(["success" => false, "message" => "A két jelszó nem egyezik.", "type" => "error"]);
        exit;
    }

    $hashedPassword = password_hash($jelszo, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE felhasznalok SET jelszo = ? WHERE email = ?");
    if ($stmt->execute([$hashedPassword, $_SESSION['email']])) {
        unset($_SESSION['email']);
        unset($_SESSION['kod']);
        echo json_encode(["success" => true, "message" => "A jelszó sikeresen megváltozott.", "redirect" => "../bejelentkezes/"]);
    } else {
        echo json_encode(["success" => false, "message" => "Hiba történt a jelszó módosítása során.", "type" => "error"]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Új jelszó</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="regisztracios-doboz">
        <h1>PénzRadar</h1>
        <h5>Új jelszó</h5>
        <div class="mb-3" id="Uzenet">
            <center><p></p></center>
        </div>
        <form id="megerositesForm">
            <div class="mb-3">
                <label for="kod" class="form-label">Megerősítő kód</label>
                <input type="text" class="form-control" id="kod" name="kod" placeholder="Kód" required>
            </div>
            <div class="mb-3">
                <label for="jelszo" class="form-label">Új jelszó</label>
                <input type="password" class="form-control" id="jelszo" name="jelszo" placeholder="Új jelszó" required>
            </div>
            <div class="mb-3">
                <label for="jelszo-megerosites" class="form-label">Új jelszó megerősítése</label>
                <input type="password" class="form-control" id="jelszo_megerosites" name="jelszo_megerosites" placeholder="Jelszó megerősítése" required>
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
