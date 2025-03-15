window.onload = () => {
    const formatáltEgyenleg = new Intl.NumberFormat('en-US', { useGrouping: true }).format(egyenleg);

    document.getElementById('perselyegyenlegText').textContent = formatáltEgyenleg;

    if (userName) {
        document.getElementById('felhasznaloNev').textContent = userName;
        document.getElementById("bejelentkezesopcio").style.display = "none";
        document.getElementById("profilopcio").style.display = "block";
        document.getElementById("beallitasopcio").style.display = "block";
        document.getElementById("kijelentkezesopcio").style.display = "block";
        document.getElementById("perselyegyenleg").style.visibility = "visible";
        document.getElementById("szerepkor").style.visibility = "visible";
        document.getElementById("statisztika").style.visibility = "visible";
        document.getElementById("nemvagybejelentkezve").innerHTML = "";
    } else {
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("statisztika").innerHTML = "";
        document.getElementById("nemvagybejelentkezve").style.visibility = "visible";
        document.getElementById("frissites-ido").innerHTML = "";
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (userName) {
        document.getElementById('felhasznaloNev').innerText = userName;
        document.getElementById('bejelentkezesopcio').style.display = 'none';
        document.getElementById('profilopcio').style.display = 'block';
        document.getElementById('beallitasopcio').style.display = 'block';
        document.getElementById('kijelentkezesopcio').style.display = 'block';
        document.getElementById('szerepkor').style.visibility = 'visible';
        document.getElementById('perselyegyenleg').style.visibility = 'visible';
        document.getElementById('statisztika').style.visibility = 'visible';
        document.getElementById('nemvagybejelentkezve').style.visibility = 'hidden';
    } else {
        document.getElementById('statisztika').style.visibility = 'hidden';
        document.getElementById('nemvagybejelentkezve').style.visibility = 'visible';
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const szerepkor = '<?php echo htmlspecialchars($_SESSION["szerepkor"] ?? ""); ?>';

    if (szerepkor !== 'Admin' && szerepkor !== 'Tulaj') {
        const frissitesTartalom = document.getElementById('frissites-tartalom');
        if (frissitesTartalom) {
            frissitesTartalom.remove();
        }
    }
});