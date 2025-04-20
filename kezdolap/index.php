<?php
require_once '../adatbazis.php';
session_start();

if (isset($_POST['cookie_elfogad'])) {
    setcookie('cookie_elfogadva', '1', time() + (365 * 24 * 60 * 60), "/");
    setcookie('cookie_elfogadva_ideje', time(), time() + (365 * 24 * 60 * 60), "/");
    header("Refresh:0");
    exit;
}

if (isset($_POST['cookie_elutasit'])) {
    setcookie('cookie_elutasitva', '1', time() + 120, "/");
    header("Refresh:0");
    exit;
}

if (isset($_POST['teszt_elrejt']) && $_POST['teszt_elrejt'] == '1') {
    setcookie('teszt_elrejtve', '1', time() + (10 * 365 * 24 * 60 * 60), "/");
    setcookie('teszt_utolso_megjelenes', '', time() - 3600, "/");
    header("Refresh:0");
    exit;
} elseif (isset($_POST['teszt_ok']) && !isset($_POST['teszt_elrejt'])) {
    setcookie('teszt_utolso_megjelenes', time(), time() + (365 * 24 * 60 * 60), "/");
    header("Refresh:0");
    exit;
}

$valasztott_idoszak = isset($_GET['idoszak']) ? $_GET['idoszak'] : date('Y-m');
$honap_eleje = date('Y-m-01', strtotime($valasztott_idoszak));
$honap_vege = date('Y-m-t', strtotime($valasztott_idoszak));
$napok_szama = date('t', strtotime($valasztott_idoszak));

$mai_nap = date('Y-m-d');
$het_eleje = date('Y-m-d', strtotime('monday this week', strtotime($mai_nap)));
$het_vege = date('Y-m-d', strtotime('sunday this week', strtotime($mai_nap)));

// Magyar napnevek
$napok_magyarul = [
    'Monday' => 'Hétfő',
    'Tuesday' => 'Kedd',
    'Wednesday' => 'Szerda',
    'Thursday' => 'Csütörtök',
    'Friday' => 'Péntek',
    'Saturday' => 'Szombat',
    'Sunday' => 'Vasárnap'
];
$mai_nap_magyarul = $napok_magyarul[date('l', strtotime($mai_nap))];

// Magyar hónapnevek a havi nézethez
$honap_nevek = [
    1 => 'január',
    2 => 'február',
    3 => 'március',
    4 => 'április',
    5 => 'május',
    6 => 'június',
    7 => 'július',
    8 => 'augusztus',
    9 => 'szeptember',
    10 => 'október',
    11 => 'november',
    12 => 'december'
];
$valasztott_honap = (int)date('m', strtotime($valasztott_idoszak));
$valasztott_ev = date('Y', strtotime($valasztott_idoszak));
$havi_cim = "$valasztott_ev. {$honap_nevek[$valasztott_honap]}";

if (isset($_SESSION['felhasznalo_id'])) {
    $stmt = $pdo->prepare("SELECT f.rang, f.havimax, f.hetimax, f.napimax, p.egyenleg 
                           FROM felhasznalok f 
                           INNER JOIN persely p ON f.id = p.felhasznalo_id 
                           WHERE f.id = ?");
    $stmt->execute([$_SESSION['felhasznalo_id']]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo) {
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['egyenleg'];
        // Limitek tárolása
        $havimax = $felhasznalo['havimax'] ?? 0;
        $hetimax = $felhasznalo['hetimax'] ?? 0;
        $napimax = $felhasznalo['napimax'] ?? 0;
    } else {
        $_SESSION['szerepkor'] = null;
        $_SESSION['perselyegyenleg'] = 0;
        $havimax = 0;
        $hetimax = 0;
        $napimax = 0;
    }
} else {
    $_SESSION['szerepkor'] = null;
    $_SESSION['perselyegyenleg'] = 0;
    $havimax = 0;
    $hetimax = 0;
    $napimax = 0;
}
$formatált_egyenleg = number_format($_SESSION['perselyegyenleg'] ?? 0, 0, '.', ',');

$napi_bevetel = 0;
$heti_bevetel = 0;
$havi_bevetel = 0;
$atlagos_bevetel = 0;
$legnagyobb_bevetel = 0;
$legkisebb_bevetel = PHP_INT_MAX;

$napi_kiadas = 0;
$heti_kiadas = 0;
$havi_kiadas = 0;
$atlagos_kiadas = 0;
$legnagyobb_kiadas = 0;
$legkisebb_kiadas = PHP_INT_MAX;

$atlagos_napi_bevetel = 0;
$atlagos_heti_bevetel = 0;
$atlagos_havi_bevetel = 0;
$atlagos_napi_kiadas = 0;
$atlagos_heti_kiadas = 0;
$atlagos_havi_kiadas = 0;

$heti_bevetelek = array_fill(0, 7, 0);
$heti_kiadasok = array_fill(0, 7, 0);
$havi_bevetelek = array_fill(0, $napok_szama, 0);
$havi_kiadasok = array_fill(0, $napok_szama, 0);


if (isset($_SESSION['felhasznalo_id'])) {
    $naptar_lekerdezes = $pdo->prepare("SELECT SUM(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NBevetel IS NOT NULL");
    $naptar_lekerdezes->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $napi_bevetel += $naptar_lekerdezes->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0;

    $naptar_het = $pdo->prepare("SELECT SUM(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum >= ? AND datum <= ? AND NBevetel IS NOT NULL");
    $naptar_het->execute([$_SESSION['felhasznalo_id'], $het_eleje, $het_vege]);
    $heti_bevetel += $naptar_het->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0;

    $naptar_honap = $pdo->prepare("SELECT SUM(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum >= ? AND datum <= ? AND NBevetel IS NOT NULL");
    $naptar_honap->execute([$_SESSION['felhasznalo_id'], $honap_eleje, $honap_vege]);
    $havi_bevetel += $naptar_honap->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0;

    $naptar_kiadas = $pdo->prepare("SELECT SUM(NKiadas) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NKiadas IS NOT NULL");
    $naptar_kiadas->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    $napi_kiadas += abs($naptar_kiadas->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0);

    $naptar_het_kiadas = $pdo->prepare("SELECT SUM(NKiadas) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum >= ? AND datum <= ? AND NKiadas IS NOT NULL");
    $naptar_het_kiadas->execute([$_SESSION['felhasznalo_id'], $het_eleje, $het_vege]);
    $heti_kiadas += abs($naptar_het_kiadas->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0);

    $naptar_honap_kiadas = $pdo->prepare("SELECT SUM(NKiadas) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum >= ? AND datum <= ? AND NKiadas IS NOT NULL");
    $naptar_honap_kiadas->execute([$_SESSION['felhasznalo_id'], $honap_eleje, $honap_vege]);
    $havi_kiadas += abs($naptar_honap_kiadas->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0);

    $tervezo_lekerdezes = $pdo->prepare("SELECT osszeg, gyakorisag, datum, tipus FROM tervezo WHERE felhasznalo_nev = ? AND datum <= ?");
    $tervezo_lekerdezes->execute([$_SESSION['felhasznalo_nev'], $honap_vege]);
    $tervezo_sorok = $tervezo_lekerdezes->fetchAll(PDO::FETCH_ASSOC);

    $naptar_bevetelek = [];
    $naptar_kiadasok = [];
    $tervezo_bevetelek = [];
    $tervezo_kiadasok = [];

    $naptar_minden_bevetel = $pdo->prepare("SELECT NBevetel, datum FROM naptar WHERE felhasznalo_id = ? AND NBevetel IS NOT NULL");
    $naptar_minden_bevetel->execute([$_SESSION['felhasznalo_id']]);
    while ($row = $naptar_minden_bevetel->fetch(PDO::FETCH_ASSOC)) {
        $naptar_bevetelek[] = $row['NBevetel'];
    }

    $naptar_minden_kiadas = $pdo->prepare("SELECT NKiadas, datum FROM naptar WHERE felhasznalo_id = ? AND NKiadas IS NOT NULL");
    $naptar_minden_kiadas->execute([$_SESSION['felhasznalo_id']]);
    while ($row = $naptar_minden_kiadas->fetch(PDO::FETCH_ASSOC)) {
        $naptar_kiadasok[] = abs($row['NKiadas']);
    }

    foreach ($tervezo_sorok as $sor) {
        $osszeg = $sor['osszeg'] ?? 0;
        $gyakorisag = $sor['gyakorisag'];
        $datum = $sor['datum'];
        $tipus = $sor['tipus'];
        $aktualis_datum = $datum;

        while ($aktualis_datum <= $honap_vege) {
            if ($tipus == 'Bevétel') {
                if ($aktualis_datum == $mai_nap) $napi_bevetel += $osszeg;
                if ($aktualis_datum >= $het_eleje && $aktualis_datum <= $het_vege) $heti_bevetel += $osszeg;
                if ($aktualis_datum >= $honap_eleje && $aktualis_datum <= $honap_vege) $havi_bevetel += $osszeg;
                $legnagyobb_bevetel = max($legnagyobb_bevetel, $osszeg);
                $legkisebb_bevetel = min($legkisebb_bevetel, $osszeg);
                $tervezo_bevetelek[] = $osszeg;
            } elseif ($tipus == 'Kiadás') {
                if ($aktualis_datum == $mai_nap) $napi_kiadas += $osszeg;
                if ($aktualis_datum >= $het_eleje && $aktualis_datum <= $het_vege) $heti_kiadas += $osszeg;
                if ($aktualis_datum >= $honap_eleje && $aktualis_datum <= $honap_vege) $havi_kiadas += $osszeg;
                $legnagyobb_kiadas = max($legnagyobb_kiadas, $osszeg);
                $legkisebb_kiadas = min($legkisebb_kiadas, $osszeg);
                $tervezo_kiadasok[] = $osszeg;
            }

            switch ($gyakorisag) {
                case 'Napi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 day')); break;
                case 'Heti': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days')); break;
                case 'Kétheti': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days')); break;
                case 'Havi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month')); break;
                case 'Negyedévi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months')); break;
                case 'Félévi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months')); break;
                case 'Évi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year')); break;
            }
        }
    }

    $osszes_bevetel = array_merge($naptar_bevetelek, $tervezo_bevetelek);
    $osszes_kiadas = array_merge($naptar_kiadasok, $tervezo_kiadasok);

    if (count($osszes_bevetel) > 0) {
        $atlagos_bevetel = array_sum($osszes_bevetel) / count($osszes_bevetel);
        $legnagyobb_bevetel = max($osszes_bevetel);
        $legkisebb_bevetel = min($osszes_bevetel);
    }

    if (count($osszes_kiadas) > 0) {
        $atlagos_kiadas = array_sum($osszes_kiadas) / count($osszes_kiadas);
        $legnagyobb_kiadas = max($osszes_kiadas);
        $legkisebb_kiadas = min($osszes_kiadas);
    }

    $naptar_napi_bevetelek = [];
    $naptar_heti_bevetelek = [];
    $naptar_havi_bevetelek = [];
    $naptar_napi_kiadasok = [];
    $naptar_heti_kiadasok = [];
    $naptar_havi_kiadasok = [];

    $naptar_minden_napi_bevetel = $pdo->prepare("SELECT NBevetel, datum FROM naptar WHERE felhasznalo_id = ? AND NBevetel IS NOT NULL AND datum = ?");
    $naptar_minden_napi_bevetel->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    while ($row = $naptar_minden_napi_bevetel->fetch(PDO::FETCH_ASSOC)) {
        $naptar_napi_bevetelek[] = $row['NBevetel'];
    }

    $naptar_minden_heti_bevetel = $pdo->prepare("SELECT NBevetel, datum FROM naptar WHERE felhasznalo_id = ? AND NBevetel IS NOT NULL AND datum >= ? AND datum <= ?");
    $naptar_minden_heti_bevetel->execute([$_SESSION['felhasznalo_id'], $het_eleje, $het_vege]);
    while ($row = $naptar_minden_heti_bevetel->fetch(PDO::FETCH_ASSOC)) {
        $naptar_heti_bevetelek[] = $row['NBevetel'];
    }

    $naptar_minden_havi_bevetel = $pdo->prepare("SELECT NBevetel, datum FROM naptar WHERE felhasznalo_id = ? AND NBevetel IS NOT NULL AND datum >= ? AND datum <= ?");
    $naptar_minden_havi_bevetel->execute([$_SESSION['felhasznalo_id'], $honap_eleje, $honap_vege]);
    while ($row = $naptar_minden_havi_bevetel->fetch(PDO::FETCH_ASSOC)) {
        $naptar_havi_bevetelek[] = $row['NBevetel'];
    }

    $naptar_minden_napi_kiadas = $pdo->prepare("SELECT NKiadas, datum FROM naptar WHERE felhasznalo_id = ? AND NKiadas IS NOT NULL AND datum = ?");
    $naptar_minden_napi_kiadas->execute([$_SESSION['felhasznalo_id'], $mai_nap]);
    while ($row = $naptar_minden_napi_kiadas->fetch(PDO::FETCH_ASSOC)) {
        $naptar_napi_kiadasok[] = abs($row['NKiadas']);
    }

    $naptar_minden_heti_kiadas = $pdo->prepare("SELECT NKiadas, datum FROM naptar WHERE felhasznalo_id = ? AND NKiadas IS NOT NULL AND datum >= ? AND datum <= ?");
    $naptar_minden_heti_kiadas->execute([$_SESSION['felhasznalo_id'], $het_eleje, $het_vege]);
    while ($row = $naptar_minden_heti_kiadas->fetch(PDO::FETCH_ASSOC)) {
        $naptar_heti_kiadasok[] = abs($row['NKiadas']);
    }

    $naptar_minden_havi_kiadas = $pdo->prepare("SELECT NKiadas, datum FROM naptar WHERE felhasznalo_id = ? AND NKiadas IS NOT NULL AND datum >= ? AND datum <= ?");
    $naptar_minden_havi_kiadas->execute([$_SESSION['felhasznalo_id'], $honap_eleje, $honap_vege]);
    while ($row = $naptar_minden_havi_kiadas->fetch(PDO::FETCH_ASSOC)) {
        $naptar_havi_kiadasok[] = abs($row['NKiadas']);
    }

    $tervezo_napi_bevetelek = [];
    $tervezo_heti_bevetelek = [];
    $tervezo_havi_bevetelek = [];
    $tervezo_napi_kiadasok = [];
    $tervezo_heti_kiadasok = [];
    $tervezo_havi_kiadasok = [];

    foreach ($tervezo_sorok as $sor) {
        $osszeg = $sor['osszeg'] ?? 0;
        $gyakorisag = $sor['gyakorisag'];
        $datum = $sor['datum'];
        $tipus = $sor['tipus'];
        $aktualis_datum = $datum;

        while ($aktualis_datum <= $honap_vege) {
            if ($tipus == 'Bevétel') {
                if ($aktualis_datum == $mai_nap) $tervezo_napi_bevetelek[] = $osszeg;
                if ($aktualis_datum >= $het_eleje && $aktualis_datum <= $het_vege) $tervezo_heti_bevetelek[] = $osszeg;
                if ($aktualis_datum >= $honap_eleje && $aktualis_datum <= $honap_vege) $tervezo_havi_bevetelek[] = $osszeg;
            } elseif ($tipus == 'Kiadás') {
                if ($aktualis_datum == $mai_nap) $tervezo_napi_kiadasok[] = $osszeg;
                if ($aktualis_datum >= $het_eleje && $aktualis_datum <= $het_vege) $tervezo_heti_kiadasok[] = $osszeg;
                if ($aktualis_datum >= $honap_eleje && $aktualis_datum <= $honap_vege) $tervezo_havi_kiadasok[] = $osszeg;
            }

            switch ($gyakorisag) {
                case 'Napi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 day')); break;
                case 'Heti': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days')); break;
                case 'Kétheti': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days')); break;
                case 'Havi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month')); break;
                case 'Negyedévi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months')); break;
                case 'Félévi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months')); break;
                case 'Évi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year')); break;
            }
        }
    }

    $osszes_napi_bevetel = array_merge($naptar_napi_bevetelek, $tervezo_napi_bevetelek);
    $osszes_heti_bevetel = array_merge($naptar_heti_bevetelek, $tervezo_heti_bevetelek);
    $osszes_havi_bevetel = array_merge($naptar_havi_bevetelek, $tervezo_havi_bevetelek);
    $osszes_napi_kiadas = array_merge($naptar_napi_kiadasok, $tervezo_napi_kiadasok);
    $osszes_heti_kiadas = array_merge($naptar_heti_kiadasok, $tervezo_heti_kiadasok);
    $osszes_havi_kiadas = array_merge($naptar_havi_kiadasok, $tervezo_havi_kiadasok);

    if (count($osszes_napi_bevetel) > 0) {
        $atlagos_napi_bevetel = array_sum($osszes_napi_bevetel) / count($osszes_napi_bevetel);
    }
    if (count($osszes_heti_bevetel) > 0) {
        $atlagos_heti_bevetel = array_sum($osszes_heti_bevetel) / count($osszes_heti_bevetel);
    }
    if (count($osszes_havi_bevetel) > 0) {
        $atlagos_havi_bevetel = array_sum($osszes_havi_bevetel) / count($osszes_havi_bevetel);
    }
    if (count($osszes_napi_kiadas) > 0) {
        $atlagos_napi_kiadas = array_sum($osszes_napi_kiadas) / count($osszes_napi_kiadas);
    }
    if (count($osszes_heti_kiadas) > 0) {
        $atlagos_heti_kiadas = array_sum($osszes_heti_kiadas) / count($osszes_heti_kiadas);
    }
    if (count($osszes_havi_kiadas) > 0) {
        $atlagos_havi_kiadas = array_sum($osszes_havi_kiadas) / count($osszes_havi_kiadas);
    }

    for ($i = 0; $i < 7; $i++) {
        $nap = date('Y-m-d', strtotime("$het_eleje +$i days"));
        $naptar_nap_bev = $pdo->prepare("SELECT SUM(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NBevetel IS NOT NULL");
        $naptar_nap_bev->execute([$_SESSION['felhasznalo_id'], $nap]);
        $heti_bevetelek[$i] = $naptar_nap_bev->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0;

        $naptar_nap_kiad = $pdo->prepare("SELECT SUM(ABS(NKiadas)) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NKiadas IS NOT NULL");
        $naptar_nap_kiad->execute([$_SESSION['felhasznalo_id'], $nap]);
        $heti_kiadasok[$i] = $naptar_nap_kiad->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0;

        $tervezo_nap = $pdo->prepare("SELECT osszeg, gyakorisag, datum, tipus FROM tervezo WHERE felhasznalo_nev = ? AND datum <= ?");
        $tervezo_nap->execute([$_SESSION['felhasznalo_nev'], $nap]);
        $tervezo_nap_sorok = $tervezo_nap->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tervezo_nap_sorok as $sor) {
            $osszeg = $sor['osszeg'] ?? 0;
            $gyakorisag = $sor['gyakorisag'];
            $datum = $sor['datum'];
            $tipus = $sor['tipus'];
            $aktualis_datum = $datum;

            while ($aktualis_datum <= $nap) {
                if ($aktualis_datum == $nap) {
                    if ($tipus == 'Bevétel') $heti_bevetelek[$i] += $osszeg;
                    else if ($tipus == 'Kiadás') $heti_kiadasok[$i] += $osszeg;
                    break;
                }
                switch ($gyakorisag) {
                    case 'Napi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 day')); break;
                    case 'Heti': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days')); break;
                    case 'Kétheti': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days')); break;
                    case 'Havi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month')); break;
                    case 'Negyedévi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months')); break;
                    case 'Félévi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months')); break;
                    case 'Évi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year')); break;
                }
            }
        }
    }

    for ($i = 0; $i < $napok_szama; $i++) {
        $nap = date('Y-m-d', strtotime("$honap_eleje +$i days"));
        $naptar_nap_bev = $pdo->prepare("SELECT SUM(NBevetel) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NBevetel IS NOT NULL");
        $naptar_nap_bev->execute([$_SESSION['felhasznalo_id'], $nap]);
        $havi_bevetelek[$i] = $naptar_nap_bev->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0;

        $naptar_nap_kiad = $pdo->prepare("SELECT SUM(ABS(NKiadas)) as osszeg FROM naptar WHERE felhasznalo_id = ? AND datum = ? AND NKiadas IS NOT NULL");
        $naptar_nap_kiad->execute([$_SESSION['felhasznalo_id'], $nap]);
        $havi_kiadasok[$i] = $naptar_nap_kiad->fetch(PDO::FETCH_ASSOC)['osszeg'] ?? 0;

        $tervezo_nap = $pdo->prepare("SELECT osszeg, gyakorisag, datum, tipus FROM tervezo WHERE felhasznalo_nev = ? AND datum <= ?");
        $tervezo_nap->execute([$_SESSION['felhasznalo_nev'], $nap]);
        $tervezo_nap_sorok = $tervezo_nap->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tervezo_nap_sorok as $sor) {
            $osszeg = $sor['osszeg'] ?? 0;
            $gyakorisag = $sor['gyakorisag'];
            $datum = $sor['datum'];
            $tipus = $sor['tipus'];
            $aktualis_datum = $datum;

            while ($aktualis_datum <= $nap) {
                if ($aktualis_datum == $nap) {
                    if ($tipus == 'Bevétel') $havi_bevetelek[$i] += $osszeg;
                    else if ($tipus == 'Kiadás') $havi_kiadasok[$i] += $osszeg;
                    break;
                }
                switch ($gyakorisag) {
                    case 'Napi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 day')); break;
                    case 'Heti': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +7 days')); break;
                    case 'Kétheti': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +14 days')); break;
                    case 'Havi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 month')); break;
                    case 'Negyedévi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +3 months')); break;
                    case 'Félévi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +6 months')); break;
                    case 'Évi': $aktualis_datum = date('Y-m-d', strtotime($aktualis_datum . ' +1 year')); break;
                }
            }
        }
    }
}

$napi_bevetel_format = number_format($napi_bevetel, 0, '.', ',');
$heti_bevetel_format = number_format($heti_bevetel, 0, '.', ',');
$havi_bevetel_format = number_format($havi_bevetel, 0, '.', ',');
$atlagos_bevetel_format = number_format($atlagos_bevetel, 0, '.', ',');
$legnagyobb_bevetel_format = number_format($legnagyobb_bevetel, 0, '.', ',');
$legkisebb_bevetel_format = $legkisebb_bevetel == PHP_INT_MAX ? '0' : number_format($legkisebb_bevetel, 0, '.', ',');

$napi_kiadas_format = number_format($napi_kiadas, 0, '.', ',');
$heti_kiadas_format = number_format($heti_kiadas, 0, '.', ',');
$havi_kiadas_format = number_format($havi_kiadas, 0, '.', ',');
$atlagos_kiadas_format = number_format($atlagos_kiadas, 0, '.', ',');
$legnagyobb_kiadas_format = number_format($legnagyobb_kiadas, 0, '.', ',');
$legkisebb_kiadas_format = $legkisebb_kiadas == PHP_INT_MAX ? '0' : number_format($legkisebb_kiadas, 0, '.', ',');

$atlagos_napi_bevetel_format = number_format($atlagos_napi_bevetel, 0, '.', ',');
$atlagos_heti_bevetel_format = number_format($atlagos_heti_bevetel, 0, '.', ',');
$atlagos_havi_bevetel_format = number_format($atlagos_havi_bevetel, 0, '.', ',');
$atlagos_napi_kiadas_format = number_format($atlagos_napi_kiadas, 0, '.', ',');
$atlagos_heti_kiadas_format = number_format($atlagos_heti_kiadas, 0, '.', ',');
$atlagos_havi_kiadas_format = number_format($atlagos_havi_kiadas, 0, '.', ',');

$waiting_supports = $pdo->query("SELECT COUNT(*) FROM support WHERE statusz = 'Várakozás' or statusz = 'Megtekintett' or statusz = 'Folyamatban'")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM felhasznalok")->fetchColumn();


// Bevételek limit ellenőrzése
$havi_bevetel_tullepes = $havi_bevetel > $havimax && $havimax > 0 ? 'tullepes' : '';
$heti_bevetel_tullepes = $heti_bevetel > $hetimax && $hetimax > 0 ? 'tullepes' : '';
$napi_bevetel_tullepes = $napi_bevetel > $napimax && $napimax > 0 ? 'tullepes' : '';

// Kiadások limit ellenőrzése (opcionális, ha a kiadásokra is vonatkozik a limit)
$havi_kiadas_tullepes = $havi_kiadas > $havimax && $havimax > 0 ? 'tullepes' : '';
$heti_kiadas_tullepes = $heti_kiadas > $hetimax && $hetimax > 0 ? 'tullepes' : '';
$napi_kiadas_tullepes = $napi_kiadas > $napimax && $napimax > 0 ? 'tullepes' : '';

?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Kezdőlap</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../alapoldal/alapstilus/style.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../alapoldal/arfolyam/style.css">
    <link rel="stylesheet" href="../hirdetes/style.css">
    <link rel="stylesheet" href="../discord/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div id="introModal">
        <video id="introVideo" autoplay muted>
            <source src="../videok/intro.mp4" type="video/mp4">
            A böngésződ nem támogatja a videó lejátszását.
        </video>
    </div>

    <?php if (!isset($_SESSION['felhasznalo_id']) && !isset($_COOKIE['cookie_elfogadva']) && !isset($_COOKIE['cookie_elutasitva'])): ?>
        <div class="modal fade custom-modal" id="cookieModal" tabindex="-1" aria-labelledby="cookieModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-center" id="cookieModalLabel">Sütik elfogadása</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bezárás"></button>
                    </div>
                    <div class="modal-body text-center">
                        Ez a weboldal sütiket (Cookie-kat) használ a jobb felhasználói élmény érdekében. Kérjük, fogadja el vagy utasítsa el a sütik használatát!
                    </div>
                    <div class="modal-footer justify-content-center" style="border-top: none;">
                        <form method="post" id="cookieForm">
                            <button type="submit" name="cookie_elfogad" class="btn btn-primary">Elfogadom</button>
                            <button type="submit" name="cookie_elutasit" class="btn btn-secondary">Elutasítom</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php
        if (isset($_POST['cookie_elutasit'])) {
            setcookie('cookie_elutasitva', '1', time() + 60, "/");
            setcookie('cookie_elfogadva', '', time() - 3600, "/");
            header("Refresh:0");
            exit;
        }
    ?>

    <?php
    if (isset($_POST['teszt_ok'])) {
        setcookie('teszt_utolso_megjelenes', time(), time() + 600, "/");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_GET['elrejt_teszt'])) {
        setcookie('teszt_elrejtve', '1', time() + (365 * 24 * 60 * 60), "/");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $teszt_megjelenik = isset($_SESSION['felhasznalo_id']) && 
                        isset($_COOKIE['cookie_elfogadva']) && 
                        !isset($_COOKIE['teszt_elrejtve']) && 
                        (!isset($_COOKIE['teszt_utolso_megjelenes']) || (time() - $_COOKIE['teszt_utolso_megjelenes']) >= 600);

    $kizaro_szerepkorok = array('Takarékos', 'Szerény', 'Átlagos', 'Kiegyensúlyozott', 'Tehetős', 'Luxus', 'Prémium', 'Elit', 'Admin', 'Moderátor', 'Tulaj');
    $szerepkor_kizarva = isset($_SESSION['szerepkor']) && in_array($_SESSION['szerepkor'], $kizaro_szerepkorok);

    if ($teszt_megjelenik && !$szerepkor_kizarva): 
    ?>
    <div class="modal fade custom-modal" id="tesztModal" tabindex="-1" aria-labelledby="tesztModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-center" id="tesztModalLabel">Üdvözöljük!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bezárás"></button>
                </div>
                <div class="modal-body text-center">
                    Szeretne kitölteni egy rövid tesztet, és felmérni RadarSzintjét?
                    <br><br>
                    <p style="color: white; font-size: 15px; font-style: italic;">RadarSzint jelentése: Az oldalon betöltött szereped / kategóriád</p>
                </div>
                <div class="text-center" style="color: red; margin-bottom: 15px;">
                    <br>
                    <a href="?elrejt_teszt=1" style="color: red; text-decoration: none;">Ne jelenjen meg többé</a>
                </div>
                <div class="modal-footer justify-content-center" style="border-top: none;">
                    <form method="post" id="tesztForm">
                        <a href="../felmero/" class="btn btn-primary">Teszt kitöltése</a>
                        <button type="submit" name="teszt_ok" class="btn btn-secondary">Később</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container-fluid" id="mainContent">
        <div class="row">
            <nav class="col-12 col-md-3 col-lg-2 oldalsav">
                <div class="text-center">
                    <img src="../kepek/ujlogo.png" alt="PénzRadar Logó" class="logo">
                </div>
                <h2 class="text-center">PénzRadar</h2>
                <ul class="nav flex-column flex-md-column mt-4">
                    <li class="nav-item">
                        <a class="nav-link" href="../kezdolap/" style="background-color: #4ACDA3;">
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
                    <?php if (isset($_SESSION['felhasznalo_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link kapcsolat-link <?php echo !isset($_SESSION['felhasznalo_id']) ? 'letiltott-link' : ''; ?>" href="../kapcsolat/">
                                <i class="bi bi-envelope-at-fill <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                                <span class="link-szoveg">Kapcsolat</span>
                                <?php if (!isset($_SESSION['felhasznalo_id'])): ?>
                                    <i class="fas fa-lock lakat-jobb"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b>
                    <br>
                    <div id="arfolyamok" class="my-3">
                        <h4 class="text-center arfolyamok-cim" style="color: #63ffbe; font-size: 1.2rem;">Árfolyamok</h4>
                        <ul id="arfolyam-lista" class="arfolyam-stilus list-unstyled d-flex flex-column align-items-center"></ul>
                    </div>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                            <div id="frissites-ido" class="frissites-keret text-center"></div>
                    <?php endif; ?>
                    <button id="frissites-gomb">Frissítés</button>
                    <?php if (isset($_SESSION['felhasznalo_id'])): ?>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <li class="nav-item">
                        <a class="nav-link" href="../kamat/index.php">
                            <i class="bi bi-currency-exchange <?php echo !isset($_SESSION['felhasznalo_id']) ? 'felattetszo' : ''; ?>"></i> 
                            <span class="link-szoveg">Kamatszámítás</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['szerepkor'] == 'Admin' || $_SESSION['szerepkor'] == 'Tulaj'): ?>
                        <div>
                            <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                            <li class="nav-item"><a class="nav-link" href="../admin/index.php"><p id="adminpanel"><i class="fas fa-cogs"></i> Admin Panel  <div id="felhszam"><?php echo $total_users; ?></div></p></a></li>
                        </div>
                        <div>
                            <li class="nav-item"><a class="nav-link" href="../admin/support.php"><p id="supportpanel"><i class="fas fa-users"></i> Support  <div id="supportszam">0<?php echo $waiting_supports; ?></div></p></a></li>
                        </div>
                    <?php endif; ?>
                </ul>
            </nav>
            <main class="col-12 col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-end py-3 border-bottom">
                    <!-- <div>
                        <a href="../discord/index.php" class="discord-navbanner">
                            <img src="../discord/dcpr.png" alt="Csatlakozz a Discord szerverünkhöz!">
                        </a>
                    </div> -->
                    <div class="dropdown d-flex align-items-center">
                        <span class="me-3" id="szerepkor" style="visibility: hidden;">RadarSzint: <b style="color: #63ffbe" id="szerepkorText"><?php echo htmlspecialchars($_SESSION['szerepkor'] ?? "Felhasználó"); ?></b></span>
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
                <ul class="nav nav-tabs" style="gap: 10px;">
                    <li class="nav-item">
                        <a class="nav-link active" href="#bevetelek" data-bs-toggle="tab" style="border-radius: 10px;">Bevételek</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#kiadasok" data-bs-toggle="tab" style="border-radius: 10px;">Kiadások</a>
                    </li>
                </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="bevetelek">
                            <h3 class="text-center mt-4" style="color: #63ffbe;">Bevételek</h3>
                            <div class="row g-4 mt-3">
                                <div class="col-12 col-sm-4">
                                    <div class="kartya text-center">
                                        <h5>Havi bevétel</h5>
                                        <b><?php echo $havi_bevetel_format; ?> Ft</b>
                                        <div class="atlag">Átlag: <?php echo $atlagos_havi_bevetel_format; ?> Ft</div>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-4">
                                    <div class="kartya text-center">
                                        <h5>Heti bevétel</h5>
                                        <b><?php echo $heti_bevetel_format; ?> Ft</b>
                                        <div class="atlag">Átlag: <?php echo $atlagos_heti_bevetel_format; ?> Ft</div>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-4">
                                    <div class="kartya text-center">
                                        <h5>Napi bevétel</h5>
                                        <b><?php echo $napi_bevetel_format; ?> Ft</b>
                                        <div class="atlag">Átlag: <?php echo $atlagos_napi_bevetel_format; ?> Ft</div>
                                    </div>
                                </div>
                            </div>
                            <div class="atlag-container">
                                <div class="kartya text-center">
                                    <h5>Legkisebb bevétel</h5>
                                    <b><?php echo $legkisebb_bevetel_format; ?> Ft</b>
                                    <p style="font-style: italic; font-size: 15px; color: #D3D3D3;">Az eddigi legkisebb bevétel</p>
                                </div>
                                <div class="kartya text-center">
                                    <h5>Legnagyobb bevétel</h5>
                                    <b><?php echo $legnagyobb_bevetel_format; ?> Ft</b>
                                    <p style="font-style: italic; font-size: 15px; color: #D3D3D3;">Az eddigi legnagyobb bevétel</p>
                                </div>
                            </div>
                            <div class="text-center mt-4 period-selector">
                                <div>
                                    <button type="button" id="heti_nezet_gomb" class="btn2 btn-primary2">Heti nézet</button>
                                    <button type="button" id="havi_nezet_gomb" class="btn2 btn-primary2">Havi nézet</button>
                                </div>
                            </div>
                            <div id="bevetelChartCim" class="text-center mt-3">
                                <h5 class="chart-title"><?php echo "$het_eleje - $het_vege"; ?></h5>
                                <p class="chart-subtitle">Ma: <?php echo $mai_nap_magyarul; ?></p>
                            </div>
                            <div class="grafikon-container">
                                <canvas id="bevetelChart" style="max-height: 100%;"></canvas>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="kiadasok">
                            <h3 class="text-center mt-4" style="color: #63ffbe;">Kiadások</h3>
                            <div class="row g-4 mt-3">
                                <div class="col-12 col-sm-4">
                                    <div class="kartya text-center <?php echo $havi_kiadas_tullepes; ?>">
                                        <h5>Havi kiadás</h5>
                                        <b><?php echo $havi_kiadas_format; ?> Ft</b>
                                        <div class="atlag">Átlag: <?php echo $atlagos_havi_kiadas_format; ?> Ft</div>
                                        <?php if ($havi_kiadas_tullepes): ?>
                                            <p class="villogo-uzenet" style="color: red; font-size: 14px; margin-top: 10px;">Túllépte a havi limitet (<?php echo number_format($havimax, 0, '.', ','); ?> Ft)!</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-sm-4">
                                    <div class="kartya text-center <?php echo $heti_kiadas_tullepes; ?>">
                                        <h5>Heti kiadás</h5>
                                        <b><?php echo $heti_kiadas_format; ?> Ft</b>
                                        <div class="atlag">Átlag: <?php echo $atlagos_heti_kiadas_format; ?> Ft</div>
                                        <?php if ($heti_kiadas_tullepes): ?>
                                            <p class="villogo-uzenet" style="color: red; font-size: 14px; margin-top: 10px;">Túllépte a heti limitet (<?php echo number_format($hetimax, 0, '.', ','); ?> Ft)!</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-4">
                                    <div class="kartya text-center <?php echo $napi_kiadas_tullepes; ?>">
                                        <h5>Napi kiadás</h5>
                                        <b><?php echo $napi_kiadas_format; ?> Ft</b>
                                        <div class="atlag">Átlag: <?php echo $atlagos_napi_kiadas_format; ?> Ft</div>
                                        <?php if ($napi_kiadas_tullepes): ?>
                                            <p class="villogo-uzenet" style="color: red; font-size: 14px; margin-top: 10px;">Túllépte a napi limitet (<?php echo number_format($napimax, 0, '.', ','); ?> Ft)!</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="atlag-container">
                                <div class="kartya text-center">
                                    <h5>Legkisebb kiadás</h5>
                                    <b><?php echo $legkisebb_kiadas_format; ?> Ft</b>
                                    <p style="font-style: italic; font-size: 15px; color: #D3D3D3;">Az eddigi legkisebb kiadás</p>
                                </div>
                                <div class="kartya text-center">
                                    <h5>Legnagyobb kiadás</h5>
                                    <b><?php echo $legnagyobb_kiadas_format; ?> Ft</b>
                                    <p style="font-style: italic; font-size: 15px; color: #D3D3D3;">Az eddigi legnagyobb kiadás</p>
                                </div>
                            </div>
                            <div class="text-center mt-4 period-selector">
                                <div>
                                    <button type="button" id="heti_nezet_gomb_kiadas" class="btn2 btn-primary2">Heti nézet</button>
                                    <button type="button" id="havi_nezet_gomb_kiadas" class="btn2 btn-primary2">Havi nézet</button>
                                </div>
                            </div>
                            <div id="kiadasChartCim" class="text-center mt-3">
                                <h5 class="chart-title"><?php echo "$het_eleje - $het_vege"; ?></h5>
                                <p class="chart-subtitle">Ma: <?php echo $mai_nap_magyarul; ?></p>
                            </div>
                            <div class="grafikon-container">
                                <canvas id="kiadasChart" style="max-height: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
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
                    <div>
                        <a href="../discord/index.php" class="discord-banner">
                            <img src="../discord/dcpr.png" alt="Csatlakozz a Discord szerverünkhöz!">
                        </a>
                    </div>
                    <b class="d-flex justify-content-end py-3 border-bottom"></b><br>
                    <div class="row">
                        <div class="col-12">
                            <div class="ad-short">
                                <h3>Kevesebb kiadás, több megtakarítás!</h3>
                                <p>Regisztrálj most, és tartsd kézben pénzügyeidet a PénzRadar segítségével!</p>
                                <a href="../regisztracio/" class="btn btn-primary2">Regisztráció</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const userName = '<?php echo htmlspecialchars($_SESSION["felhasznalo_nev"] ?? ""); ?>';
        const egyenleg = '<?php echo htmlspecialchars($_SESSION["perselyegyenleg"] ?? "0"); ?>';
        const bejelentkezve = '<?php echo isset($_SESSION["felhasznalo_id"]) ? "true" : "false"; ?>';
        let valasztottNezet = '<?php echo isset($_GET['nezet']) ? $_GET['nezet'] : 'heti'; ?>';

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
                    setTimeout(() => introModal.style.display = 'none', 1000);
                });

                introVideo.onended = function() {
                    introModal.classList.add('fade-out');
                    setTimeout(() => introModal.style.display = 'none', 1000);
                };

                introVideo.onerror = function() {
                    introModal.classList.add('fade-out');
                    setTimeout(() => introModal.style.display = 'none', 1000);
                };
            }

            const cookieModalElement = document.getElementById('cookieModal');
            if (cookieModalElement) {
                const cookieModal = new bootstrap.Modal(cookieModalElement, { backdrop: 'static', keyboard: false });
                cookieModal.show();

                const elutasitGomb = document.getElementById('cookieElutasit');
                if (elutasitGomb) {
                    elutasitGomb.addEventListener('click', function() {
                        cookieModal.hide();
                        setTimeout(() => {
                            cookieModal.show();
                        }, 120000);
                    });
                }
            }

            const tesztModalElement = document.getElementById('tesztModal');
            if (tesztModalElement) {
                const tesztModal = new bootstrap.Modal(tesztModalElement, { backdrop: 'static', keyboard: false });
                tesztModal.show();
            }

            if (bejelentkezve === "true") {
                const hetiBevetelek = <?php echo json_encode($heti_bevetelek); ?>;
                const hetiKiadasok = <?php echo json_encode($heti_kiadasok); ?>;
                const haviBevetelek = <?php echo json_encode($havi_bevetelek); ?>;
                const haviKiadasok = <?php echo json_encode($havi_kiadasok); ?>;
                const napok = ['Hét', 'Kedd', 'Szer', 'Csüt', 'Pén', 'Szo', 'Vas'];
                const haviNapok = Array.from({length: <?php echo $napok_szama; ?>}, (_, i) => i + 1);

                let bevetelChart = null;
                let kiadasChart = null;

                function grafikonFrissites(tipus, adatTipus) {
                    const canvasId = adatTipus === 'bevetel' ? 'bevetelChart' : 'kiadasChart';
                    const chartCimId = adatTipus === 'bevetel' ? 'bevetelChartCim' : 'kiadasChartCim';
                    const chart = adatTipus === 'bevetel' ? bevetelChart : kiadasChart;
                    const adatok = tipus === 'heti' ? (adatTipus === 'bevetel' ? hetiBevetelek : hetiKiadasok) : (adatTipus === 'bevetel' ? haviBevetelek : haviKiadasok);
                    const cimkek = tipus === 'heti' ? napok : haviNapok;

                    if (chart) chart.destroy();

                    const chartCim = document.getElementById(chartCimId);
                    if (tipus === 'heti') {
                        chartCim.innerHTML = `
                            <h5 class="chart-title"><?php echo "$het_eleje - $het_vege"; ?></h5>
                            <p class="chart-subtitle">Ma: <?php echo $mai_nap_magyarul; ?></p>
                        `;
                    } else {
                        chartCim.innerHTML = `
                            <h5 class="chart-title"><?php echo $havi_cim; ?></h5>
                            <p class="chart-subtitle">Ma: <?php echo $mai_nap_magyarul; ?></p>
                        `;
                    }

                    const ujChart = new Chart(document.getElementById(canvasId), {
                        type: 'bar',
                        data: {
                            labels: cimkek,
                            datasets: [{
                                label: `${tipus === 'heti' ? 'Heti' : 'Havi'} ${adatTipus === 'bevetel' ? 'bevétel' : 'kiadás'} (Ft)`,
                                data: adatok,
                                backgroundColor: '#63ffbe',
                                borderColor: '#63ffbe',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: {
                                y: { 
                                    beginAtZero: true, 
                                    ticks: { color: '#ffffff' }, 
                                    grid: { color: '#555555' } 
                                },
                                x: {
                                    ticks: {
                                        color: '#ffffff',
                                        callback: function(value, index, values) {
                                            if (tipus === 'havi') {
                                                const napokSzama = <?php echo $napok_szama; ?>;
                                                const lepes = Math.ceil(napokSzama / 6);
                                                if (index % lepes === 0) return cimkek[index];
                                                return '';
                                            }
                                            return cimkek[index];
                                        },
                                        maxRotation: window.innerWidth <= 768 ? 45 : 0,
                                        minRotation: window.innerWidth <= 768 ? 45 : 0
                                    },
                                    grid: { color: '#555555' }
                                }
                            },
                            plugins: { 
                                legend: { 
                                    labels: { color: '#ffffff' } 
                                } 
                            }
                        }
                    });

                    if (adatTipus === 'bevetel') bevetelChart = ujChart;
                    else kiadasChart = ujChart;
                }

                grafikonFrissites(valasztottNezet, 'bevetel');
                grafikonFrissites(valasztottNezet, 'kiadas');

                const hetiGomb = document.getElementById('heti_nezet_gomb');
                const haviGomb = document.getElementById('havi_nezet_gomb');
                const hetiGombKiadas = document.getElementById('heti_nezet_gomb_kiadas');
                const haviGombKiadas = document.getElementById('havi_nezet_gomb_kiadas');

                hetiGomb.addEventListener('click', function() {
                    valasztottNezet = 'heti';
                    grafikonFrissites(valasztottNezet, 'bevetel');
                });

                haviGomb.addEventListener('click', function() {
                    valasztottNezet = 'havi';
                    grafikonFrissites(valasztottNezet, 'bevetel');
                });

                hetiGombKiadas.addEventListener('click', function() {
                    valasztottNezet = 'heti';
                    grafikonFrissites(valasztottNezet, 'kiadas');
                });

                haviGombKiadas.addEventListener('click', function() {
                    valasztottNezet = 'havi';
                    grafikonFrissites(valasztottNezet, 'kiadas');
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="../alapoldal/arfolyam/js.js"></script>
    <script src="../hirdetes/js.js"></script>
</body>
</html>