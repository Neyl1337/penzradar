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
    } else {
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("statisztika").style.visibility = "none";
        document.getElementById("nemvagybejelentkezve").style.visibility = "visible";
        document.getElementById("statisztika").innerHTML = "";
    }

    // Árfolyamok lekérése és frissítése
    async function frissitArfolyamok() {
        try {
            const response = await fetch('https://api.exchangerate-api.com/v4/latest/HUF');
            const data = await response.json();
            const arfolyamLista = document.getElementById('arfolyam-lista');
            const frissitesIdo = document.getElementById('frissites-ido');
    
            // Töröld a meglévő listát
            arfolyamLista.innerHTML = '';
    
            // Csak az adott árfolyamok hozzáadása a listához, ikonokkal
            const currencies = ['EUR', 'USD', 'GBP', 'CHF', 'RUB', 'RON', 'AUD', 'PLN'];
    
            currencies.forEach(currency => {
                if (data.rates[currency]) {
                    const li = document.createElement('li');
                    const arfolyam = (1 / data.rates[currency]).toFixed(2); // Árfolyam forintban
    
                    // Ikon kép hozzáadása
                    const icon = document.createElement('img');
                    icon.src = `../kepek/${currency}.png`;
                    icon.alt = `${currency} flag`;
    
                    li.appendChild(icon);
                    li.innerHTML += ` ${currency}: ${arfolyam} HUF`;
                    arfolyamLista.appendChild(li);
                }
            });
    
            // Utolsó frissítés időpontjának megjelenítése
            const lastUpdate = new Date(data.time_last_updated * 1000);
            frissitesIdo.textContent = `Utolsó frissítés: ${lastUpdate.toLocaleString()}`;
        } catch (error) {
            console.error('Hiba az árfolyamok lekérése során:', error);
        }
    }
    
    // Az árfolyamok frissítése az oldal betöltésekor és minden órában
    frissitArfolyamok();
    // setInterval(frissitArfolyamok, 3600000); // 3600000 ms = 1 óra
    setInterval(frissitArfolyamok, 300000); // 300000 ms = 5 perc
}

const napiKoltesek = [5000, 6000, 5500, 7000, 8000, 7500, 7200];
const napiAtlag = napiKoltesek.map((_, i) => napiKoltesek.slice(0, i + 1).reduce((a, b) => a + b) / (i + 1));
const haviKoltesek = [150000, 160000, 155000, 170000, 180000, 175000, 172000, 165000, 158000, 170500, 178000, 185000];
const haviAtlag = haviKoltesek.map((_, i) => haviKoltesek.slice(0, i + 1).reduce((a, b) => a + b) / (i + 1));

const napiBevetelek = [3250, 3000, 58500, 7000, 8000, 6500, 7200];
const napiAtlagBevetel = napiKoltesek.map((_, i) => napiKoltesek.slice(0, i + 1).reduce((a, b) => a + b) / (i + 1));
const haviBevetelek = [15000, 10000, 155000, 170000, 180000, 15000, 1720, 165000, 158000, 1500, 1000, 18000];
const haviAtlagBevetel = haviKoltesek.map((_, i) => haviKoltesek.slice(0, i + 1).reduce((a, b) => a + b) / (i + 1));

new Chart(document.getElementById('napiKoltesChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: ['Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat', 'Vasárnap'],
        datasets: [
            { label: 'Napi költés (Ft)', data: napiKoltesek, borderColor: '#63FFBE', backgroundColor: '#1E1E1E', borderWidth: 2 },
            { label: 'Átlagos költés (Ft)', data: napiAtlag, borderColor: 'darkgreen', backgroundColor: '#1E1E1E', borderWidth: 2, borderDash: [5, 5] }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Deaktiválja az alapértelmezett arányt
        aspectRatio: 2, // Arány beállítása (2:1 például)
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});

new Chart(document.getElementById('haviKoltesChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Márc', 'Ápr', 'Máj', 'Júni', 'Júli', 'Aug', 'Szept', 'Okt', 'Nov', 'Dec'],
        datasets: [
            { label: 'Havi költés (Ft)', data: haviKoltesek, borderColor: '#63FFBE', backgroundColor: '#1E1E1E', borderWidth: 2 },
            { label: 'Átlagos havi költés (Ft)', data: haviAtlag, borderColor: 'darkgreen', backgroundColor: '#1E1E1E', borderWidth: 2, borderDash: [5, 5] }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, 
        aspectRatio: 2, // Arány beállítása
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});

new Chart(document.getElementById('napiBevetelChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: ['Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat', 'Vasárnap'],
        datasets: [
            { label: 'Napi bevétel (Ft)', data: napiBevetelek, borderColor: '#63FFBE', backgroundColor: '#1E1E1E', borderWidth: 2 },
            { label: 'Átlagos bevétel (Ft)', data: napiAtlagBevetel, borderColor: 'darkgreen', backgroundColor: '#1E1E1E', borderWidth: 2, borderDash: [5, 5] }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 2, // Arány beállítása
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
        labels: ['Jan', 'Feb', 'Márc', 'Ápr', 'Máj', 'Júni', 'Júli', 'Aug', 'Szept', 'Okt', 'Nov', 'Dec'],
        datasets: [
            { label: 'Havi bevétel (Ft)', data: haviBevetelek, borderColor: '#63FFBE', backgroundColor: '#1E1E1E', borderWidth: 2 },
            { label: 'Átlagos havi bevétel (Ft)', data: haviAtlagBevetel, borderColor: 'darkgreen', backgroundColor: '#1E1E1E', borderWidth: 2, borderDash: [5, 5] }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 2, // Arány beállítása
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});