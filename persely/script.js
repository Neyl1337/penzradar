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
        document.getElementById("egyenlegkezeles").style.visibility = "visible";
        document.getElementById("nemvagybejelentkezve").innerHTML = "";
    } else {
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("egyenlegkezeles").style.visibility = "none";
        document.getElementById("bejelentkez").style.visibility = "visible";
        document.getElementById("egyenlegkezeles").innerHTML = "";
    }
};

// Dinamikusan frissítjük a kiválasztott persely egyenlegét
document.getElementById('persely_id').addEventListener('change', function() {
    const perselyId = this.value;
    fetch(`get_persely_osszeg.php?persely_id=${perselyId}&felhasznalo_nev=<?php echo urlencode($_SESSION['felhasznalo_nev']); ?>`)
        .then(response => response.json())
        .then(data => {
            document.querySelector('.piggy-bank-selected-balance').textContent = `Kiválasztott persely egyenlege: ${data.osszeg.toLocaleString('hu-HU')} Ft`;
        })
        .catch(error => console.error('Hiba a lekérdezésben:', error));
});

// Gomb szövegének dinamikus frissítése a művelet alapján
document.getElementById('muvelet').addEventListener('change', function() {
    const muvelet = this.value;
    const gomb = document.getElementById('vegrehajtasGomb');
    if (muvelet === 'betet') {
        gomb.textContent = 'Pénz betétele';
    } else if (muvelet === 'kivet') {
        gomb.textContent = 'Pénz kivétele';
    } else if (muvelet === 'modositas') {
        gomb.textContent = 'Összeg módosítása';
    }
});

// Alapértelmezett gomb szöveg beállítása betöltéskor
document.addEventListener('DOMContentLoaded', function() {
    const muvelet = document.getElementById('muvelet').value;
    const gomb = document.getElementById('vegrehajtasGomb');
    if (muvelet === 'betet') {
        gomb.textContent = 'Pénz betétele';
    } else if (muvelet === 'kivet') {
        gomb.textContent = 'Pénz kivétele';
    } else if (muvelet === 'modositas') {
        gomb.textContent = 'Összeg módosítása';
    }
});