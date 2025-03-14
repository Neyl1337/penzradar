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

const haviKoltesek = [0, 0, 0, 0, 0, 0, 0];
const haviAtlag = haviKoltesek.map((_, i) => haviKoltesek.slice(0, i + 1).reduce((a, b) => a + b) / (i + 1));

const haviBevetelek = [0, 0, 0, 0, 0, 0, 0];
const haviAtlagBevetel = haviBevetelek.map((_, i) => haviKoltesek.slice(0, i + 1).reduce((a, b) => a + b) / (i + 1));

new Chart(document.getElementById('haviKoltesChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: ['Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat', 'Vasárnap'],
        datasets: [
            { label: 'Heti kiadás (Ft)', data: haviKoltesek, borderColor: '#63FFBE', backgroundColor: '#1E1E1E', borderWidth: 2 },
            { label: 'Átlagos heti kiadás (Ft)', data: haviAtlag, borderColor: 'darkgreen', backgroundColor: '#1E1E1E', borderWidth: 2, borderDash: [5, 5] }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 2,
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});

new Chart(document.getElementById('haviBevetelChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: ['Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat', 'Vasárnap'],
        datasets: [
            { label: 'Heti bevétel (Ft)', data: haviBevetelek, borderColor: '#63FFBE', backgroundColor: '#1E1E1E', borderWidth: 2 },
            { label: 'Átlagos heti bevétel (Ft)', data: haviAtlagBevetel, borderColor: 'darkgreen', backgroundColor: '#1E1E1E', borderWidth: 2, borderDash: [5, 5] }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 2,
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});

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