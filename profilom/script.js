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
        document.getElementById('felhasznaloNev').textContent = "Jelentkezz be!";
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("statisztika").style.visibility = "none";
    }
};

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