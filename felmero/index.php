<?php
require_once '../adatbazis.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    header('Content-Type: application/json');
    $valasz = ['siker' => false];
    
    if (isset($_SESSION['felhasznalo_id'])) {
        $bejovo_adat = json_decode(file_get_contents('php://input'), true);
        $szerepkor = $bejovo_adat['szerepkor'] ?? null;
        
        if ($szerepkor) {
            try {
                $lekerdezes = $pdo->prepare("UPDATE felhasznalok SET rang = ? WHERE id = ?");
                $lekerdezes->execute([$szerepkor, $_SESSION['felhasznalo_id']]);
                $_SESSION['szerepkor'] = $szerepkor;
                $valasz['siker'] = true;
            } catch (PDOException $e) {
                $valasz['hiba'] = "Adatbázis hiba: " . $e->getMessage();
            }
        } else {
            $valasz['hiba'] = "Nincs RadarSzint megadva";
        }
    } else {
        $valasz['hiba'] = "Nincs bejelentkezett felhasználó";
    }
    
    echo json_encode($valasz);
    exit;
}

if (isset($_SESSION['felhasznalo_id'])) {
    $lekerdezes = $pdo->prepare("
        SELECT f.rang, f.email, f.nev, p.egyenleg 
        FROM felhasznalok f
        INNER JOIN persely p ON f.id = p.felhasznalo_id
        WHERE f.id = ?
    ");
    $lekerdezes->execute([$_SESSION['felhasznalo_id']]);
    $felhasznalo = $lekerdezes->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['email'] = $felhasznalo['email'];
        $_SESSION['felhasznalo_nev'] = $felhasznalo['nev'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['email'] = null;
    $_SESSION['felhasznalo_nev'] = null;
    $_SESSION['perselyegyenleg'] = null;
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RadarSzint felmérő</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="kontener">
        <h1>PénzRadar</h1>
        <h2>RadarSzint felmérő</h2>
        <center><p>Bejelentkezve mint: <?php echo isset($_SESSION['felhasznalo_nev']) ? htmlspecialchars($_SESSION['felhasznalo_nev']) : "Nem vagy bejelentkezve"; ?></p></center>
        <br><br>
        <div id="kerdesek">
            <div class="kerdes aktiv">
                <label>Mennyit költesz havonta ételre és alapvető élelmiszerekre?</label>
                <div class="valasz"><input type="radio" name="etel" value="0"><span>Kevesebb mint 20 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="etel" value="1"><span>20 000 - 50 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="etel" value="2"><span>50 000 - 100 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="etel" value="3"><span>Több mint 100 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Mennyit költesz havonta szórakozásra (pl. mozi, étterem, hobbi)?</label>
                <div class="valasz"><input type="radio" name="szorakozas" value="0"><span>Kevesebb mint 10 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="szorakozas" value="1"><span>10 000 - 30 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="szorakozas" value="2"><span>30 000 - 60 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="szorakozas" value="3"><span>Több mint 60 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Hány ember megélhetéséről gondoskodsz (beleértve magadat is)?</label>
                <div class="valasz"><input type="radio" name="emberek" value="0"><span>Csak magamról</span></div>
                <div class="valasz"><input type="radio" name="emberek" value="1"><span>1-2 ember</span></div>
                <div class="valasz"><input type="radio" name="emberek" value="2"><span>3-4 ember</span></div>
                <div class="valasz"><input type="radio" name="emberek" value="3"><span>5 vagy több ember</span></div>
            </div>
            <div class="kerdes">
                <label>Milyen gyakran vásárolsz új, nem alapvető dolgokat (pl. elektronika, bútor)?</label>
                <div class="valasz"><input type="radio" name="vasarlas" value="0"><span>Ritkán vagy soha</span></div>
                <div class="valasz"><input type="radio" name="vasarlas" value="1"><span>Havonta egyszer</span></div>
                <div class="valasz"><input type="radio" name="vasarlas" value="2"><span>Hetente</span></div>
                <div class="valasz"><input type="radio" name="vasarlas" value="3"><span>Majdnem naponta</span></div>
            </div>
            <div class="kerdes">
                <label>Mennyit tudsz havonta megtakarítani?</label>
                <div class="valasz"><input type="radio" name="takarekos" value="0"><span>Semmit</span></div>
                <div class="valasz"><input type="radio" name="takarekos" value="1"><span>Kevesebb mint 20 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="takarekos" value="2"><span>20 000 - 50 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="takarekos" value="3"><span>Több mint 50 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Mennyit költesz lakhatásra (bérleti díj, rezsi, vagy hitel)?</label>
                <div class="valasz"><input type="radio" name="lakhatas" value="0"><span>0 Ft (pl. szüleimmel élek)</span></div>
                <div class="valasz"><input type="radio" name="lakhatas" value="1"><span>Kevesebb mint 50 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="lakhatas" value="2"><span>50 000 - 150 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="lakhatas" value="3"><span>Több mint 150 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Milyen gyakran utazol belföldön vagy külföldön?</label>
                <div class="valasz"><input type="radio" name="utazas" value="0"><span>Évente egyszer vagy ritkábban</span></div>
                <div class="valasz"><input type="radio" name="utazas" value="1"><span>Évente 2-3 alkalommal</span></div>
                <div class="valasz"><input type="radio" name="utazas" value="2"><span>Negyedévente</span></div>
                <div class="valasz"><input type="radio" name="utazas" value="3"><span>Havonta vagy gyakrabban</span></div>
            </div>
            <div class="kerdes">
                <label>Mennyit költesz ruházatra havonta?</label>
                <div class="valasz"><input type="radio" name="ruha" value="0"><span>Kevesebb mint 10 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="ruha" value="1"><span>10 000 - 30 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="ruha" value="2"><span>30 000 - 60 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="ruha" value="3"><span>Több mint 60 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Van autód, és mennyit költesz rá havonta (üzemanyag, szerviz, biztosítás)?</label>
                <div class="valasz"><input type="radio" name="auto" value="0"><span>Nincs autóm</span></div>
                <div class="valasz"><input type="radio" name="auto" value="1"><span>Van, kevesebb mint 20 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="auto" value="2"><span>Van, 20 000 - 50 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="auto" value="3"><span>Van, több mint 50 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Mennyit költesz ajándékokra évente?</label>
                <div class="valasz"><input type="radio" name="ajandek" value="0"><span>Kevesebb mint 10 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="ajandek" value="1"><span>10 000 - 30 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="ajandek" value="2"><span>30 000 - 70 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="ajandek" value="3"><span>Több mint 70 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Milyen lakásban élsz?</label>
                <div class="valasz"><input type="radio" name="lakas_tipus" value="0"><span>Szüleim háza</span></div>
                <div class="valasz"><input type="radio" name="lakas_tipus" value="1"><span>Bérelt lakás vagy szoba</span></div>
                <div class="valasz"><input type="radio" name="lakas_tipus" value="2"><span>Saját lakás hitel nélkül</span></div>
                <div class="valasz"><input type="radio" name="lakas_tipus" value="3"><span>Saját lakás hitellel</span></div>
            </div>
            <div class="kerdes">
                <label>Mennyit költesz közlekedésre (ha nincs autód, pl. bérlet, taxi)?</label>
                <div class="valasz"><input type="radio" name="kozlekedes" value="0"><span>Kevesebb mint 5 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="kozlekedes" value="1"><span>5 000 - 15 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="kozlekedes" value="2"><span>15 000 - 30 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="kozlekedes" value="3"><span>Több mint 30 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Van-e hiteled (kivéve lakáshitelt)?</label>
                <div class="valasz"><input type="radio" name="hitel" value="0"><span>Nincs hitelem</span></div>
                <div class="valasz"><input type="radio" name="hitel" value="1"><span>Van, havi törlesztés 20 000 Ft alatt</span></div>
                <div class="valasz"><input type="radio" name="hitel" value="2"><span>Van, havi törlesztés 20 000 - 50 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="hitel" value="3"><span>Van, havi törlesztés 50 000 Ft felett</span></div>
            </div>
            <div class="kerdes">
                <label>Mennyit költesz egészségügyre (pl. gyógyszer, orvos)?</label>
                <div class="valasz"><input type="radio" name="egeszsegugy" value="0"><span>Kevesebb mint 5 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="egeszsegugy" value="1"><span>5 000 - 15 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="egeszsegugy" value="2"><span>15 000 - 30 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="egeszsegugy" value="3"><span>Több mint 30 000 Ft</span></div>
            </div>
            <div class="kerdes">
                <label>Mennyit költesz oktatásra vagy önképzésre évente?</label>
                <div class="valasz"><input type="radio" name="oktatas" value="0"><span>Semmit</span></div>
                <div class="valasz"><input type="radio" name="oktatas" value="1"><span>Kevesebb mint 20 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="oktatas" value="2"><span>20 000 - 50 000 Ft</span></div>
                <div class="valasz"><input type="radio" name="oktatas" value="3"><span>Több mint 50 000 Ft</span></div>
            </div>
        </div>
        <button id="kovetkezo" onclick="lepjTovabb()">Következő</button>
        <div id="eredmeny">
            <?php
                if (isset($_SESSION['szerepkor'])) {
                    echo "Megítélt RadarSzint: " . htmlspecialchars($_SESSION['szerepkor']) . " költekező";
                }
            ?>
        </div>
        <button id="mentes" style="display: none;" onclick="mentesSzerepkor()">Kérem a RadarSzintem!</button>
    </div>

    <script src="script.js"></script>
</body>
</html>