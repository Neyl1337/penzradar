<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../adatbazis.php';
} catch (Exception $e) {
    die("Nem sikerült betölteni az adatbazis.php fájlt: " . $e->getMessage());
}

session_start();

if (!isset($_SESSION['felhasznalo_id'])) {
    header("Location: ../bejelentkezes/");
    exit;
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Kamatszámítás</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link rel="stylesheet" href="../alapstilus/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="szamitas-doboz">
        <img src='../../kepek/ujlogo.png' alt='PénzRadar' id="logo" />
        <h2>PénzRadar</h2>
        <h4>Kamatszámítás</h4>
        <form id="kamatszamitas">
            <div class="input-group">
                <label for="toke">Kezdeti tőke (Ft)</label>
                <input type="number" id="toke" min="1000" max="9999999999" required placeholder="Pl. 1000000">
            </div>
            <div class="input-group">
                <label for="kamatlab">Éves kamatláb (%)</label>
                <input type="number" id="kamatlab" min="0.1" max="99.9" step="0.1" required placeholder="Pl. 5.5">
            </div>
            <div class="input-group">
                <label for="futamido">Futamidő (év)</label>
                <input type="number" id="futamido" min="1" max="99" required placeholder="Pl. 5">
            </div>
            <div class="input-group">
                <label for="kamatozasi_gyakorisag">Kamatozási gyakoriság</label>
                <select id="kamatozasi_gyakorisag" required>
                    <option value="1">Évente</option>
                    <option value="2">Félévente</option>
                    <option value="4">Negyedévente</option>
                    <option value="12">Havonta</option>
                </select>
            </div>
            <div class="input-group">
                <label for="kamat_tipus">Kamat típusa</label>
                <select id="kamat_tipus" required>
                    <option value="egyszeru">Egyszerű kamat</option>
                    <option value="kamatos">Kamatos kamat</option>
                </select>
            </div>
            <div class="input-group">
                <label for="toke_valtozas">Éves tőkeváltozás (Ft, pozitív: hozzáadás, negatív: kivonás)</label>
                <input type="number" id="toke_valtozas" value="0" placeholder="Pl. 50000">
            </div>
            <button type="submit">Számítás</button>
        </form>
        <div id="eredmeny" class="eredmeny"></div>
        <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
        <div class= "oldal">
                <center><a href="../../kezdolap/"><b>Vissza az oldalra</b></a></center>
        </div>
    </div>
    <script src="js.js"></script>
</body>
</html>