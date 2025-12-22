window.onload = () => {
    // Biztosítjuk, hogy az egyenleg szám legyen, különben 0-t használunk
    const validEgyenleg = isNaN(parseFloat(egyenleg)) ? 0 : parseFloat(egyenleg);
    const formatáltEgyenleg = new Intl.NumberFormat('hu-HU', { useGrouping: true }).format(validEgyenleg);

    const perselyegyenlegText = document.getElementById('perselyegyenlegText');
    if (perselyegyenlegText) {
        perselyegyenlegText.textContent = formatáltEgyenleg;
    }

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

// Dinamikus egyenleg frissítése API-n keresztül
function frissitesFelhasznaloAdatok() {
    fetch('../api/felhasznalo_adatok.php')
        .then(response => response.json())
        .then(data => {
            // Szerepkör frissítése
            const szerepkorText = document.getElementById('szerepkorText');
            if (szerepkorText) {
                szerepkorText.textContent = data.szerepkor || 'Felhasználó';
            }

            // Persely egyenleg frissítése
            const perselyegyenlegText = document.getElementById('perselyegyenlegText');
            if (perselyegyenlegText) {
                const egyenleg = parseFloat(data.perselyegyenleg) || 0;
                perselyegyenlegText.textContent = egyenleg.toLocaleString('hu-HU');
            }
        })
        .catch(error => {
            console.error('Hiba a felhasználói adatok frissítése közben:', error);
        });
}

// Időszakos frissítés indítása
setInterval(frissitesFelhasznaloAdatok, 5000);

// Dinamikusan frissítjük a kiválasztott persely egyenlegét
document.getElementById('persely_id').addEventListener('change', function() {
    const perselyId = this.value;
    fetch(`get_persely_osszeg.php?persely_id=${perselyId}&felhasznalo_nev=${encodeURIComponent(userName)}`)
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