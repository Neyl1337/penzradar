// Alapértelmezett árfolyamok (frissítve a képen látható legutóbbi értékekkel)
const defaultArfolyamok = {
    eur: 402.45, // Frissítve
    usd: 371.54, // Frissítve
    gbp: 481.66, // Frissítve
    chf: 420.00,
    rub: 4.50,
    ron: 80.00,
    aud: 230.00,
    pln: 95.00
};

// Árfolyamok lekérése és frissítése
async function frissitArfolyamok(retryCount = 0, maxRetries = 3) {
    const arfolyamLista = document.getElementById('arfolyam-lista');
    const frissitesIdo = document.getElementById('frissites-ido');
    const frissitesGomb = document.getElementById('frissites-gomb');

    // Ellenőrizzük, hogy az elemek léteznek-e
    if (!arfolyamLista || !frissitesIdo) {
        console.error('Hiányzó DOM elemek: arfolyam-lista vagy frissites-ido');
        return;
    }

    // Betöltési állapot jelzése
    arfolyamLista.innerHTML = '<li class="betoltes">Betöltés...</li>';
    if (frissitesGomb) frissitesGomb.disabled = true; // Gomb letiltása betöltés közben

    try {
        // Időtúllépés beállítása (10 másodperc)
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);

        const response = await fetch('https://api.coingecko.com/api/v3/exchange_rates', {
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error(`HTTP hiba! Státusz: ${response.status}`);
        }

        const data = await response.json();

        // Ellenőrizzük, hogy az API válasz tartalmazza-e a szükséges adatokat
        if (!data.rates || !data.rates.huf || !data.rates.huf.value) {
            throw new Error('Hiányzó vagy érvénytelen adatok az API válaszban');
        }

        // Töröljük a betöltési üzenetet
        arfolyamLista.innerHTML = '';

        // HUF értéke BTC-ben az API-ból
        const hufInBtc = data.rates.huf.value;

        // Csak az adott árfolyamok hozzáadása a listához, ikonokkal
        const currencies = ['eur', 'usd', 'gbp', 'chf', 'rub', 'ron', 'aud', 'pln'];
        const arfolyamok = {};

        currencies.forEach(currency => {
            if (data.rates[currency] && data.rates[currency].value) {
                const li = document.createElement('li');
                // Árfolyam kiszámítása: (1 / valuta értéke BTC-ben) * HUF értéke BTC-ben
                const currencyInBtc = data.rates[currency].value;
                const arfolyam = ((1 / currencyInBtc) * hufInBtc).toFixed(2);

                // Tároljuk az árfolyamot a helyi tároláshoz
                arfolyamok[currency] = arfolyam;

                // Zászló és valutakód egy közös span-ben
                const currencyWrapper = document.createElement('span');
                currencyWrapper.classList.add('currency-wrapper');

                // Ikon kép hozzáadása
                const icon = document.createElement('img');
                icon.src = `../kepek/${currency.toUpperCase()}.png`;
                icon.alt = `${currency.toUpperCase()} flag`;

                // Valutanév
                const currencySpan = document.createElement('span');
                currencySpan.textContent = `${currency.toUpperCase()}: `;
                currencySpan.classList.add('currency-code');

                // Árfolyam érték
                const rateSpan = document.createElement('span');
                rateSpan.textContent = `${arfolyam} HUF`;
                rateSpan.classList.add('arfolyam-ertek');

                // Összeállítás
                currencyWrapper.appendChild(icon);
                currencyWrapper.appendChild(currencySpan);
                li.appendChild(currencyWrapper);
                li.appendChild(rateSpan);
                arfolyamLista.appendChild(li);
            }
        });

        // Ha egyetlen árfolyam sem töltődött be az API-ból
        if (arfolyamLista.children.length === 0) {
            throw new Error('Nem sikerült árfolyamokat betölteni az API-ból');
        }

        // Mentsük el az árfolyamokat a helyi tárolóba
        localStorage.setItem('arfolyamok', JSON.stringify(arfolyamok));

        // Utolsó frissítés időpontjának megjelenítése
        const lastUpdate = new Date().toLocaleString();
        frissitesIdo.textContent = `Utolsó frissítés: ${lastUpdate} (API-ból)`;
    } catch (error) {
        console.error('Hiba az árfolyamok lekérése során:', error.message);
        arfolyamLista.innerHTML = '';

        // Próbáljunk meg a helyi tárolásból adatokat betölteni
        const savedArfolyamok = localStorage.getItem('arfolyamok');
        if (savedArfolyamok) {
            const parsedArfolyamok = JSON.parse(savedArfolyamok);
            Object.keys(parsedArfolyamok).forEach(currency => {
                const li = document.createElement('li');
                const currencyWrapper = document.createElement('span');
                currencyWrapper.classList.add('currency-wrapper');

                const icon = document.createElement('img');
                icon.src = `../kepek/${currency.toUpperCase()}.png`;
                icon.alt = `${currency.toUpperCase()} flag`;

                const currencySpan = document.createElement('span');
                currencySpan.textContent = `${currency.toUpperCase()}: `;
                currencySpan.classList.add('currency-code');

                const rateSpan = document.createElement('span');
                rateSpan.textContent = `${parsedArfolyamok[currency]} HUF`;
                rateSpan.classList.add('arfolyam-ertek');

                currencyWrapper.appendChild(icon);
                currencyWrapper.appendChild(currencySpan);
                li.appendChild(currencyWrapper);
                li.appendChild(rateSpan);
                arfolyamLista.appendChild(li);
            });
            frissitesIdo.textContent = `Utolsó frissítés: (korábbi mentett adatok a helyi tárolóból)`;
        } else {
            // Ha a helyi tároló üres, használjuk az alapértelmezett árfolyamokat
            Object.keys(defaultArfolyamok).forEach(currency => {
                const li = document.createElement('li');
                const currencyWrapper = document.createElement('span');
                currencyWrapper.classList.add('currency-wrapper');

                const icon = document.createElement('img');
                icon.src = `../kepek/${currency.toUpperCase()}.png`;
                icon.alt = `${currency.toUpperCase()} flag`;

                const currencySpan = document.createElement('span');
                currencySpan.textContent = `${currency.toUpperCase()}: `;
                currencySpan.classList.add('currency-code');

                const rateSpan = document.createElement('span');
                rateSpan.textContent = `${defaultArfolyamok[currency]} HUF`;
                rateSpan.classList.add('arfolyam-ertek');

                currencyWrapper.appendChild(icon);
                currencyWrapper.appendChild(currencySpan);
                li.appendChild(currencyWrapper);
                li.appendChild(rateSpan);
                arfolyamLista.appendChild(li);
            });
            frissitesIdo.textContent = `Utolsó frissítés: (alapértelmezett adatok, nem frissek – kérlek, próbáld meg frissíteni)`;
        }

        // Újrapróbálkozás, ha nem értük el a maximális próbálkozási számot
        if (retryCount < maxRetries) {
            console.log(`Újrapróbálkozás (${retryCount + 1}/${maxRetries})...`);
            arfolyamLista.insertAdjacentHTML('beforeend', `<li class="info">Újrapróbálkozás ${retryCount + 1}/${maxRetries}...</li>`);
            setTimeout(() => frissitArfolyamok(retryCount + 1, maxRetries), 10000);
        }
    } finally {
        if (frissitesGomb) frissitesGomb.disabled = false; // Gomb engedélyezése a folyamat végén
    }
}

// Az árfolyamok frissítése az oldal betöltésekor és minden 5 percben
document.addEventListener('DOMContentLoaded', () => {
    frissitArfolyamok();
    setInterval(() => frissitArfolyamok(), 300000); // 300000 ms = 5 perc

    // Frissítés gomb eseménykezelő
    const frissitesGomb = document.getElementById('frissites-gomb');
    if (frissitesGomb) {
        frissitesGomb.addEventListener('click', () => {
            frissitArfolyamok();
        });
    }
});