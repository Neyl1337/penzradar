<?php
require_once '../adatbazis.php';
session_start();

$mai_nap = date('Y-m-d');
$honap_eleje = date('Y-m-01');
$het_eleje = date('Y-m-d', strtotime('monday this week', strtotime($mai_nap)));
$het_vege = date('Y-m-d', strtotime('sunday this week', strtotime($mai_nap)));

if (isset($_SESSION['felhasznalo_id'])) {
    $stmt = $pdo->prepare("SELECT f.rang, p.egyenleg FROM felhasznalok f INNER JOIN persely p ON f.id = p.felhasznalo_id WHERE f.id = ?");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
    } else {
        $_SESSION['szerepkor'] = null;
        $_SESSION['perselyegyenleg'] = 0;
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['perselyegyenleg'] = 0;
}

$formatált_egyenleg = number_format($_SESSION['perselyegyenleg'] ?? 0, 0, '.', ',');

$napi_bevetel = 0;
if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_lekerdezes = $pdo->prepare("SELECT SUM(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NBevetel IS NOT NULL");
    $naptar_lekerdezes->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $naptar_eredmeny = $naptar_lekerdezes->fetch(PDO::FETCH_ASSOC);
    $napi_bevetel += $naptar_eredmeny['osszeg'] ?? 0;

    $tervezo_lekerdezes = $pdo->prepare("SELECT osszeg, gyakorisag, datum FROM tervezo WHERE felhasznalo_nev = ? AND tipus = 'Bevétel' AND felfuggesztve = 0 AND datum <= ?");
    $tervezo_lekerdezes->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $tervezo_sorok = $tervezo_lekerdezes->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tervezo_sorok as $sor) {
        $osszeg = $sor['osszeg'] ?? 0;
        $gyakorisag = $sor['gyakorisag'];
        $datum = $sor['datum'];

        if ($datum <= $mai_nap) {
            switch ($gyakorisag) {
                case 'Napi':
                    if ($datum == $mai_nap) $napi_bevetel += $osszeg;
                    break;
                case 'Heti':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days'));
                    }
                    break;
                case 'Kétheti':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days'));
                    }
                    break;
                case 'Havi':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month'));
                    }
                    break;
                case 'Negyedévi':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months'));
                    }
                    break;
                case 'Félévi':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months'));
                    }
                    break;
                case 'Évi':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year'));
                    }
                    break;
            }
        }
    }
}

$heti_bevetel = 0;
if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_het = $pdo->prepare("SELECT SUM(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum >= ? AND datum <= ? AND NBevetel IS NOT NULL");
    $naptar_het->execute([$_SESSION['felhasznalo_id'], $het_eleje, $mai_nap]);
    $naptar_het_eredmeny = $naptar_het->fetch(PDO::FETCH_ASSOC);
    $heti_bevetel += $naptar_het_eredmeny['osszeg'] ?? 0;

    $tervezo_het = $pdo->prepare("SELECT osszeg, gyakorisag, datum FROM tervezo WHERE felhasznalo_nev = ? AND tipus = 'Bevétel' AND felfuggesztve = 0 AND datum <= ?");
    $tervezo_het->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $tervezo_het_sorok = $tervezo_het->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tervezo_het_sorok as $sor) {
        $osszeg = $sor['osszeg'] ?? 0;
        $gyakorisag = $sor['gyakorisag'];
        $datum = $sor['datum'];

        if ($datum <= $mai_nap && $datum >= $het_eleje) {
            switch ($gyakorisag) {
                case 'Napi':
                    $napok_szama = (strtotime($mai_nap) - strtotime(max($het_eleje, $datum))) / (60 * 60 * 24) + 1;
                    $heti_bevetel += $osszeg * $napok_szama;
                    break;
                case 'Heti':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days'));
                    }
                    break;
                case 'Kétheti':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days'));
                    }
                    break;
                case 'Havi':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month'));
                    }
                    break;
                case 'Negyedévi':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months'));
                    }
                    break;
                case 'Félévi':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months'));
                    }
                    break;
                case 'Évi':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_bevetel += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year'));
                    }
                    break;
            }
        }
    }
}

$atlagos_bevetel = 0;
if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_atlag = $pdo->prepare("SELECT AVG(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NBevetel IS NOT NULL");
    $naptar_atlag->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $naptar_atlag_eredmeny = $naptar_atlag->fetch(PDO::FETCH_ASSOC);
    $atlagos_bevetel = $naptar_atlag_eredmeny['osszeg'] ?? 0;
}

$legnagyobb_bevetel = 0;
if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_max = $pdo->prepare("SELECT MAX(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND NBevetel IS NOT NULL");
    $naptar_max->execute([$_SESSION['felhasznalo_id']]);
    $naptar_max_eredmeny = $naptar_max->fetch(PDO::FETCH_ASSOC);
    $legnagyobb_bevetel = $naptar_max_eredmeny['osszeg'] ?? 0;

    $tervezo_max = $pdo->prepare("SELECT MAX(osszeg) as osszeg FROM tervezo WHERE felhasznalo_nev = ? AND tipus = 'Bevétel' AND felfuggesztve = 0");
    $tervezo_max->execute([$_SESSION['felhasznalo_id']]);
    $tervezo_max_eredmeny = $tervezo_max->fetch(PDO::FETCH_ASSOC);
    $legnagyobb_bevetel = max($legnagyobb_bevetel, $tervezo_max_eredmeny['osszeg'] ?? 0);
}

$napi_kiadas = 0;
if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_kiadas = $pdo->prepare("SELECT SUM(NKiadas) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NKiadas IS NOT NULL");
    $naptar_kiadas->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $naptar_kiadas_eredmeny = $naptar_kiadas->fetch(PDO::FETCH_ASSOC);
    $napi_kiadas += abs($naptar_kiadas_eredmeny['osszeg'] ?? 0);

    $tervezo_kiadas = $pdo->prepare("SELECT osszeg, gyakorisag, datum FROM tervezo WHERE felhasznalo_nev = ? AND tipus = 'Kiadás' AND felfuggesztve = 0 AND datum <= ?");
    $tervezo_kiadas->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $tervezo_kiadas_sorok = $tervezo_kiadas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tervezo_kiadas_sorok as $sor) {
        $osszeg = $sor['osszeg'] ?? 0;
        $gyakorisag = $sor['gyakorisag'];
        $datum = $sor['datum'];

        if ($datum <= $mai_nap) {
            switch ($gyakorisag) {
                case 'Napi':
                    if ($datum == $mai_nap) $napi_kiadas += $osszeg;
                    break;
                case 'Heti':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days'));
                    }
                    break;
                case 'Kétheti':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days'));
                    }
                    break;
                case 'Havi':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month'));
                    }
                    break;
                case 'Negyedévi':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months'));
                    }
                    break;
                case 'Félévi':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months'));
                    }
                    break;
                case 'Évi':
                    $aktualis_datum = $datum;
                    while ($aktualis_datum <= $mai_nap) {
                        if ($aktualis_datum == $mai_nap) $napi_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year'));
                    }
                    break;
            }
        }
    }
}

$heti_kiadas = 0;
if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_het_kiadas = $pdo->prepare("SELECT SUM(NKiadas) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum >= ? AND datum <= ? AND NKiadas IS NOT NULL");
    $naptar_het_kiadas->execute([$_SESSION['felhasznalo_id'], $het_eleje, $mai_nap]);
    $naptar_het_kiadas_eredmeny = $naptar_het_kiadas->fetch(PDO::FETCH_ASSOC);
    $heti_kiadas += abs($naptar_het_kiadas_eredmeny['osszeg'] ?? 0);

    $tervezo_het_kiadas = $pdo->prepare("SELECT osszeg, gyakorisag, datum FROM tervezo WHERE felhasznalo_nev = ? AND tipus = 'Kiadás' AND felfuggesztve = 0 AND datum <= ?");
    $tervezo_het_kiadas->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $tervezo_het_kiadas_sorok = $tervezo_het_kiadas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tervezo_het_kiadas_sorok as $sor) {
        $osszeg = $sor['osszeg'] ?? 0;
        $gyakorisag = $sor['gyakorisag'];
        $datum = $sor['datum'];

        if ($datum <= $mai_nap && $datum >= $het_eleje) {
            switch ($gyakorisag) {
                case 'Napi':
                    $napok_szama = (strtotime($mai_nap) - strtotime(max($het_eleje, $datum))) / (60 * 60 * 24) + 1;
                    $heti_kiadas += $osszeg * $napok_szama;
                    break;
                case 'Heti':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days'));
                    }
                    break;
                case 'Kétheti':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days'));
                    }
                    break;
                case 'Havi':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month'));
                    }
                    break;
                case 'Negyedévi':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months'));
                    }
                    break;
                case 'Félévi':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months'));
                    }
                    break;
                case 'Évi':
                    $aktualis_datum = max($het_eleje, $datum);
                    while ($aktualis_datum <= $mai_nap) {
                        $heti_kiadas += $osszeg;
                        $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year'));
                    }
                    break;
            }
        }
    }
}

$atlagos_kiadas = 0;
if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_atlag_kiadas = $pdo->prepare("SELECT AVG(ABS(NKiadas)) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NKiadas IS NOT NULL");
    $naptar_atlag_kiadas->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $naptar_atlag_kiadas_eredmeny = $naptar_atlag_kiadas->fetch(PDO::FETCH_ASSOC);
    $atlagos_kiadas = $naptar_atlag_kiadas_eredmeny['osszeg'] ?? 0;
}

$legnagyobb_kiadas = 0;
if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_max_kiadas = $pdo->prepare("SELECT MAX(ABS(NKiadas)) as osszeg FROM naptar WHERE felhasznalo_id = ? AND NKiadas IS NOT NULL");
    $naptar_max_kiadas->execute([$_SESSION['felhasznalo_id']]);
    $naptar_max_kiadas_eredmeny = $naptar_max_kiadas->fetch(PDO::FETCH_ASSOC);
    $legnagyobb_kiadas = $naptar_max_kiadas_eredmeny['osszeg'] ?? 0;

    $tervezo_max_kiadas = $pdo->prepare("SELECT MAX(osszeg) as osszeg FROM tervezo WHERE felhasznalo_nev = ? AND tipus = 'Kiadás' AND felfuggesztve = 0");
    $tervezo_max_kiadas->execute([$_SESSION['felhasznalo_id']]);
    $tervezo_max_kiadas_eredmeny = $tervezo_max_kiadas->fetch(PDO::FETCH_ASSOC);
    $legnagyobb_kiadas = max($legnagyobb_kiadas, $tervezo_max_kiadas_eredmeny['osszeg'] ?? 0);
}

$napi_bevetel_format = number_format($napi_bevetel, 0, '.', ',');
$heti_bevetel_format = number_format($heti_bevetel, 0, '.', ',');
$atlagos_bevetel_format = number_format($atlagos_bevetel, 0, '.', ',');
$legnagyobb_bevetel_format = number_format($legnagyobb_bevetel, 0, '.', ',');
$napi_kiadas_format = number_format($napi_kiadas, 0, '.', ',');
$heti_kiadas_format = number_format($heti_kiadas, 0, '.', ',');
$atlagos_kiadas_format = number_format($atlagos_kiadas, 0, '.', ',');
$legnagyobb_kiadas_format = number_format($legnagyobb_kiadas, 0, '.', ',');

$heti_bevetelek = array_fill(0, 7, 0);
$heti_kiadasok = array_fill(0, 7, 0);
for ($i = 0; $i < 7; $i++) {
    $nap = date('Y-m-d', strtotime("$het_eleje +$i days"));
    $naptar_nap_bev = $pdo->prepare("SELECT SUM(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NBevetel IS NOT NULL");
    $naptar_nap_bev->execute([$_SESSION['felhasznalo_id'], $nap]);
    $naptar_nap_bev_eredmeny = $naptar_nap_bev->fetch(PDO::FETCH_ASSOC);
    $heti_bevetelek[$i] = $naptar_nap_bev_eredmeny['osszeg'] ?? 0;

    $naptar_nap_kiad = $pdo->prepare("SELECT SUM(ABS(NKiadas)) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NKiadas IS NOT NULL");
    $naptar_nap_kiad->execute([$_SESSION['felhasznalo_id'], $nap]);
    $naptar_nap_kiad_eredmeny = $naptar_nap_kiad->fetch(PDO::FETCH_ASSOC);
    $heti_kiadasok[$i] = $naptar_nap_kiad_eredmeny['osszeg'] ?? 0;

    if ($nap <= $mai_nap) {
        $tervezo_nap = $pdo->prepare("SELECT osszeg, gyakorisag, datum, tipus FROM tervezo WHERE felhasznalo_nev = ? AND felfuggesztve = 0 AND datum <= ?");
        $tervezo_nap->execute([$_SESSION['felhasznalo_id'], $nap]);
        $tervezo_nap_sorok = $tervezo_nap->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tervezo_nap_sorok as $sor) {
            $osszeg = $sor['osszeg'] ?? 0;
            $gyakorisag = $sor['gyakorisag'];
            $datum = $sor['datum'];
            $tipus = $sor['tipus'];

            if ($datum <= $nap) {
                switch ($gyakorisag) {
                    case 'Napi':
                        if ($datum == $nap) {
                            if ($tipus == 'Bevétel') $heti_bevetelek[$i] += $osszeg;
                            else $heti_kiadasok[$i] += $osszeg;
                        }
                        break;
                    case 'Heti':
                        $aktualis_datum = $datum;
                        while ($aktualis_datum <= $nap) {
                            if ($aktualis_datum == $nap) {
                                if ($tipus == 'Bevétel') $heti_bevetelek[$i] += $osszeg;
                                else $heti_kiadasok[$i] += $osszeg;
                            }
                            $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days'));
                        }
                        break;
                    case 'Kétheti':
                        $aktualis_datum = $datum;
                        while ($aktualis_datum <= $nap) {
                            if ($aktualis_datum == $nap) {
                                if ($tipus == 'Bevétel') $heti_bevetelek[$i] += $osszeg;
                                else $heti_kiadasok[$i] += $osszeg;
                            }
                            $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days'));
                        }
                        break;
                    case 'Havi':
                        $aktualis_datum = $datum;
                        while ($aktualis_datum <= $nap) {
                            if ($aktualis_datum == $nap) {
                                if ($tipus == 'Bevétel') $heti_bevetelek[$i] += $osszeg;
                                else $heti_kiadasok[$i] += $osszeg;
                            }
                            $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month'));
                        }
                        break;
                    case 'Negyedévi':
                        $aktualis_datum = $datum;
                        while ($aktualis_datum <= $nap) {
                            if ($aktualis_datum == $nap) {
                                if ($tipus == 'Bevétel') $heti_bevetelek[$i] += $osszeg;
                                else $heti_kiadasok[$i] += $osszeg;
                            }
                            $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months'));
                        }
                        break;
                    case 'Félévi':
                        $aktualis_datum = $datum;
                        while ($aktualis_datum <= $nap) {
                            if ($aktualis_datum == $nap) {
                                if ($tipus == 'Bevétel') $heti_bevetelek[$i] += $osszeg;
                                else $heti_kiadasok[$i] += $osszeg;
                            }
                            $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months'));
                        }
                        break;
                    case 'Évi':
                        $aktualis_datum = $datum;
                        while ($aktualis_datum <= $nap) {
                            if ($aktualis_datum == $nap) {
                                if ($tipus == 'Bevétel') $heti_bevetelek[$i] += $osszeg;
                                else $heti_kiadasok[$i] += $osszeg;
                            }
                            $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year'));
                        }
                        break;
                }
            }
        }
    }
}
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
    <link rel="stylesheet" href="../alapoldal/alapstilus/style.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../alapoldal/kamat/style.css">
    <link rel="stylesheet" href="../alapoldal/arfolyam/style.css">
    <link rel="stylesheet" href="../hirdetes/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div id="introModal">
        <video id="introVideo" autoplay muted>
            <source src="../videok/intro.mp4" type="video/mp4">
            A böngésződ nem támogatja a videó lejátszását.
        </video>
    </div>

    <div class="container-fluid" id="mainContent">
        <div class="row">
        <nav class="col-12 col-md-3 col-lg-2 oldalsav">
            <div class="text-center">
                <img src="../kepek/ujlogo.png" alt="PénzRadar Logó" class="logo">
            </div>
            <h2 class="text-center">PénzRadar</h2>
            <ul class="nav flex-column flex-md-column mt-4">
                <li class="nav-item">
                    <a class="nav-link" href="../kezdolap/">
                        <i class="fas fa-home"></i>
                        <span class="link-szoveg">Kezdőlap</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../tervezo/">
                        <i class="fas fa-tasks <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                        <span class="link-szoveg">Tervező</span>
                        <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                            <i class="fas fa-lock lakat-jobb"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../naptar/">
                        <i class="fas fa-calendar-alt <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                        <span class="link-szoveg">Naptár</span>
                        <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                            <i class="fas fa-lock lakat-jobb"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../persely/">
                        <i class="fas fa-piggy-bank <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                        <span class="link-szoveg">Persely</span>
                        <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                            <i class="fas fa-lock lakat-jobb"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <b class="d-flex justify-content-end py-3 border-bottom"></b>
                <br>
                <div id="arfolyamok" class="my-3">
                    <h4 class="text-center" style="color: #63ffbe; font-size: 1.2rem;">Árfolyamok</h4>
                    <ul id="arfolyam-lista" class="arfolyam-stilus list-unstyled d-flex flex-column align-items-center"></ul>
                </div>
                <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                    <div>
                        <b id="frissites-ido" style="color: red;" class="text-center d-block"></b>
                    </div>
                <?php endif; ?>
                <b class="d-flex justify-content-end py-3 border-bottom"></b>
                <?php if (isset($_SESSION['felhasznalo_id'])): ?>
                    <br>
                    <h4 style="color: #63ffbe; font-size: 1.2rem;">Kamatszámítás</h4>
                    <form id="kamatSzamitasForm">
                        <div class="mb-2">
                            <label for="alapOsszeg" style="color: white; font-size: 1rem;">Tőke (Ft):</label>
                            <input type="number" id="alapOsszeg" class="form-control" min="0" value="<?php echo htmlspecialchars($formatált_egyenleg); ?>" style="background-color: #1e1e1e; color: white; border: 1px solid #63ffbe; border-radius: 5px;" oninput="validateInput(this)">
                        </div>
                        <div class="mb-2">
                            <label for="kamatSzazalek" style="color: white; font-size: 1rem;">Kamatláb (%):</label>
                            <input type="number" id="kamatSzazalek" class="form-control" min="0" max="100" step="0.1" value="5" style="background-color: #1e1e1e; color: white; border: 1px solid #63ffbe; border-radius: 5px;" oninput="validateInput(this)">
                        </div>
                        <div class="mb-2">
                            <label for="idotartam" style="color: white; font-size: 1rem;">Futamidő (év):</label>
                            <input type="number" id="idotartam" class="form-control" min="1" max="99" value="1" style="background-color: #1e1e1e; color: white; border: 1px solid #63ffbe; border-radius: 5px;" oninput="validateInput(this)">
                        </div>
                        <button type="button" class="btn btn-primary w-100 kamat-button" onclick="szamitKamat()" style="background-color: #1e1e1e; border: 1px solid #63ffbe; color: white;">Számítás</button>
                    </form>
                    <p id="kamatEredmeny" class="mt-2" style="color: #63ffbe;"></p>
                <?php endif; ?>
                <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                    <div>
                        <b class="d-flex justify-content-end py-3 border-bottom"></b>
                        <li class="nav-item"><a class="nav-link" href="../admin/"><p id="adminpanel"><i class="fas fa-cogs"></i> Admin Panel</p></a></li>
                    </div>
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
                            <span id="felhasznaloNev"><?php echo htmlspecialchars($_SESSION['felhasznalo_nev'] ?? "Jelentkezz be!"); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="felhasznaloDropdownGomb">
                            <li id="bejelentkezesopcio" style="display: <?php echo isset($_SESSION['felhasznalo_id']) ? 'none' : 'block'; ?>;"><a class="dropdown-item" href="../bejelentkezes/">Bejelentkezés</a></li>
                            <li id="profilopcio" style="display: <?php echo isset($_SESSION['felhasznalo_id']) ? 'block' : 'none'; ?>;"><a class="dropdown-item" href="../profilom/">Profilom</a></li>
                            <li id="beallitasopcio" style="display: <?php echo isset($_SESSION['felhasznalo_id']) ? 'block' : 'none'; ?>;"><a class="dropdown-item" href="../beallitasok/">Beállítások</a></li>
                            <li id="kijelentkezesopcio" style="display: <?php echo isset($_SESSION['felhasznalo_id']) ? 'block' : 'none'; ?>;"><a class="dropdown-item" href="../adatbazis_logout.php">Kijelentkezés</a></li>
                        </ul>
                    </div>
                </header>
                <div class="dashboard mt-4" id="statisztika" style="<?php echo isset($_SESSION['felhasznalo_id']) ? 'visibility: visible;' : 'visibility: hidden;'; ?>">
                <center>
                <br>
                <div class="het-tartalom">
                    <center><h2 id="aktualishet">Aktuális hét:</h2></center>
                    <div id="heti-tartalom" class="het-tartalma"></div>
                    <div id="mai-nap" class="ma"></div>
                    <br>
                    <p id="aktualis-ido"></p>
                </div>
                </center>
                <br><br>
                <hr>
                <br>
                    <section id="bevetelek">
                        <h3 class="text-center">Bevételek</h3>
                        <br>
                        <div class="row g-4">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Napi bevétel</h5>
                                    <b><?php echo $napi_bevetel_format; ?> Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Heti bevétel</h5>
                                    <b><?php echo $heti_bevetel_format; ?> Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Átlagos napi bevétel</h5>
                                    <b><?php echo $atlagos_bevetel_format; ?> Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Legnagyobb bevétel</h5>
                                    <b><?php echo $legnagyobb_bevetel_format; ?> Ft</b>
                                </div>
                            </div>
                        </div>
                        <br><br>
                        <div class="grafikon-container">
                            <canvas id="hetiBevetelChart"></canvas>
                        </div>
                    </section>
                    <br><br>
                    <hr>
                    <p id="heti-tartalom"></p>
                    <p id="mai-nap"></p>
                    <br>
                    <section id="kiadasok">
                        <h3 class="text-center">Kiadások</h3>
                        <br>
                        <div class="row g-4">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Napi kiadás</h5>
                                    <b><?php echo $napi_kiadas_format; ?> Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Heti kiadás</h5>
                                    <b><?php echo $heti_kiadas_format; ?> Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Átlagos napi kiadás</h5>
                                    <b><?php echo $atlagos_kiadas_format; ?> Ft</b>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="kartya p-3 text-center">
                                    <h5>Legnagyobb kiadás</h5>
                                    <b><?php echo $legnagyobb_kiadas_format; ?> Ft</b>
                                </div>
                            </div>
                        </div>
                        <br><br>
                        <div class="grafikon-container">
                            <canvas id="hetiKiadasChart"></canvas>
                        </div>
                    </section>
                    <br><br>
                    <hr>
                </div>
                <div class="dashboard mt-4" id="nemvagybejelentkezve" style="<?php echo !isset($_SESSION['felhasznalo_id']) ? 'visibility: visible;' : 'visibility: hidden;'; ?>">
                    <div class="card p-3 mt-3 kartya1">
                        <center>
                            <h3>Jelenleg nem vagy bejelentkezve!</h3>
                            <h4>Jelentkezz be <a href="../bejelentkezes/">itt</a></h4>
                            <h5>Amennyiben még nem regisztráltál, <a href="../regisztracio/">itt</a> megteheted</h5>
                        </center>
                    </div>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="ad-container">
                                <h1 id="title"></h1>
                                <div class="subtitle" id="subtitle"></div>
                                <div class="calculator">
                                    <div class="circle"></div>
                                    <div class="counter" id="counter"></div>
                                </div>
                                <a href="../regisztracio/" class="cta-button" id="cta"></a>
                            </div>
                        </div>
                        <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                        <div class="col-12 col-md-6">
                            <div class="kamat-container my-3">
                                <h4>Kamatszámítás</h4>
                                <form id="kamatSzamitasFormLoggedOut">
                                    <div class="mb-2">
                                        <label for="alapOsszegLoggedOut">Tőke (Ft):</label>
                                        <input type="number" id="alapOsszegLoggedOut" class="form-control" min="0" value="0" oninput="validateInput(this)">
                                    </div>
                                    <div class="mb-2">
                                        <label for="kamatSzazalekLoggedOut">Kamatláb (%):</label>
                                        <input type="number" id="kamatSzazalekLoggedOut" class="form-control" min="0" max="100" step="0.1" value="5" oninput="validateInput(this)">
                                    </div>
                                    <div class="mb-2">
                                        <label for="idotartamLoggedOut">Futamidő (év):</label>
                                        <input type="number" id="idotartamLoggedOut" class="form-control" min="1" max="99" value="1" oninput="validateInput(this)">
                                    </div>
                                    <button type="button" class="btn btn-primary w-100" onclick="szamitKamatLoggedOut()">Számítás</button>
                                </form>
                                <p id="kamatEredmenyLoggedOut" class="mt-2"></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                </div>
            </main>
        </div>
    </div>

    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';
        const bejelentkezve = '<?php echo isset($_SESSION["felhasznalo_id"]) ? "true" : "false"; ?>';

        document.addEventListener('DOMContentLoaded', function() {
            const introVideo = document.getElementById('introVideo');
            const introModal = document.getElementById('introModal');

            const elsoLatogatas = !sessionStorage.getItem('hasVisitedInTab');
            if (!elsoLatogatas || bejelentkezve !== "true") {
                introModal.style.display = 'none';
            } else {
                introVideo.play().then(() => {
                    sessionStorage.setItem('hasVisitedInTab', 'true');
                }).catch(function(error) {
                    console.log("A videó automatikus lejátszása nem sikerült: ", error);
                    introModal.classList.add('fade-out');
                    setTimeout(() => {
                        introModal.style.display = 'none';
                    }, 1000);
                });

                introVideo.onended = function() {
                    introModal.classList.add('fade-out');
                    setTimeout(() => {
                        introModal.style.display = 'none';
                    }, 1000);
                };

                introVideo.onerror = function() {
                    introModal.classList.add('fade-out');
                    setTimeout(() => {
                        introModal.style.display = 'none';
                    }, 1000);
                };
            }

            if (bejelentkezve === "true") {
                const hetiBevetelek = <?php echo json_encode($heti_bevetelek); ?>;
                const hetiKiadasok = <?php echo json_encode($heti_kiadasok); ?>;
                const napok = ['Hét', 'Kedd', 'Szer', 'Csüt', 'Pén', 'Szo', 'Vas'];

                const hetiBevetelChart = new Chart(document.getElementById('hetiBevetelChart'), {
                    type: 'bar',
                    data: {
                        labels: napok,
                        datasets: [{
                            label: 'Heti bevétel (Ft)',
                            data: hetiBevetelek,
                            backgroundColor: '#63ffbe',
                            borderColor: '#63ffbe',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#ffffff'
                                },
                                grid: {
                                    color: '#555555'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#ffffff'
                                },
                                grid: {
                                    color: '#555555'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#ffffff'
                                }
                            }
                        }
                    }
                });

                const hetiKiadasChart = new Chart(document.getElementById('hetiKiadasChart'), {
                    type: 'bar',
                    data: {
                        labels: napok,
                        datasets: [{
                            label: 'Heti kiadás (Ft)',
                            data: hetiKiadasok,
                            backgroundColor: '#63ffbe',
                            borderColor: '#63ffbe',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#ffffff'
                                },
                                grid: {
                                    color: '#555555'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#ffffff'
                                },
                                grid: {
                                    color: '#555555'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#ffffff'
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="../alapoldal/arfolyam/js.js"></script>
    <script src="../alapoldal/kamat/js.js"></script>
    <script src="../hirdetes/js.js"></script>
</body>
</html>