const defaultArfolyamok = {
    eur: 408.38,
    usd: 359.10,
    gbp: 476.11, 
    chf: 420.00,
    rub: 4.50,
    ron: 80.00,
    aud: 230.00,
    pln: 95.00,
    uah: 10.00
};

let reversedStates = JSON.parse(localStorage.getItem('reversedStates')) || {
    eur: false,
    usd: false,
    gbp: false,
    chf: false,
    rub: false,
    ron: false,
    aud: false,
    pln: false,
    uah: false 
};

// Árfolyamok megjelenítése
function megjelenitArfolyamokat(arfolyamok, hufInBtc, data, arfolyamLista, frissitesIdo) {
    arfolyamLista.innerHTML = '';
    const currencies = ['eur', 'usd', 'gbp', 'chf', 'rub', 'ron', 'aud', 'pln', 'uah']; // UAH hozzáadva

    currencies.forEach(currency => {
        if (data.rates[currency] && data.rates[currency].value) {
            const li = document.createElement('li');
            li.classList.add(reversedStates[currency] ? 'reversed' : 'normal');
            const currencyInBtc = data.rates[currency].value;
            let arfolyam;

            if (reversedStates[currency]) {
                // Megfordított árfolyam: 1 HUF hány egység valuta
                arfolyam = (currencyInBtc / hufInBtc).toFixed(4);
            } else {
                // Normál árfolyam: 1 valuta hány HUF
                arfolyam = ((1 / currencyInBtc) * hufInBtc).toFixed(2);
            }
            arfolyamok[currency] = arfolyam;

            const currencyWrapper = document.createElement('span');
            currencyWrapper.classList.add('currency-wrapper');

            const icon = document.createElement('img');
            icon.src = `../kepek/${currency.toUpperCase()}.png`;
            icon.alt = `${currency.toUpperCase()} flag`;

            const currencySpan = document.createElement('span');
            currencySpan.textContent = `${currency.toUpperCase()}: `;
            currencySpan.classList.add('currency-code');

            const rateSpan = document.createElement('span');
            rateSpan.textContent = reversedStates[currency] ? `${arfolyam} ${currency.toUpperCase()}` : `${arfolyam} HUF`;
            rateSpan.classList.add('arfolyam-ertek');

            currencyWrapper.appendChild(icon);
            currencyWrapper.appendChild(currencySpan);
            li.appendChild(currencyWrapper);
            li.appendChild(rateSpan);

            // Kattintási esemény az árfolyam megfordításához
            li.addEventListener('click', () => {
                reversedStates[currency] = !reversedStates[currency];
                localStorage.setItem('reversedStates', JSON.stringify(reversedStates));
                li.classList.toggle('reversed');
                li.classList.toggle('normal');
                const newArfolyam = reversedStates[currency] ? (currencyInBtc / hufInBtc).toFixed(4) : ((1 / currencyInBtc) * hufInBtc).toFixed(2);
                rateSpan.textContent = reversedStates[currency] ? `${newArfolyam} ${currency.toUpperCase()}` : `${newArfolyam} HUF`;
                arfolyamok[currency] = newArfolyam;
                localStorage.setItem('arfolyamok', JSON.stringify(arfolyamok));
            });

            arfolyamLista.appendChild(li);
        }
    });

    localStorage.setItem('arfolyamok', JSON.stringify(arfolyamok));

    // Utolsó frissítés időpontjának megjelenítése
    const lastUpdate = new Date().toLocaleString();
    frissitesIdo.textContent = `Utolsó frissítés: ${lastUpdate} (API-ból)`;
}

function megjelenitMentettVagyAlapArfolyamokat(arfolyamLista, frissitesIdo) {
    const savedArfolyamok = localStorage.getItem('arfolyamok');
    const savedReversedStates = JSON.parse(localStorage.getItem('reversedStates')) || reversedStates;

    if (savedArfolyamok) {
        const parsedArfolyamok = JSON.parse(savedArfolyamok);
        Object.keys(parsedArfolyamok).forEach(currency => {
            const li = document.createElement('li');
            li.classList.add(savedReversedStates[currency] ? 'reversed' : 'normal');
            const currencyWrapper = document.createElement('span');
            currencyWrapper.classList.add('currency-wrapper');

            const icon = document.createElement('img');
            icon.src = `../kepek/${currency.toUpperCase()}.png`;
            icon.alt = `${currency.toUpperCase()} flag`;

            const currencySpan = document.createElement('span');
            currencySpan.textContent = `${currency.toUpperCase()}: `;
            currencySpan.classList.add('currency-code');

            const rateSpan = document.createElement('span');
            rateSpan.textContent = savedReversedStates[currency] ? `${(1 / parsedArfolyamok[currency]).toFixed(4)} ${currency.toUpperCase()}` : `${parsedArfolyamok[currency]} HUF`;
            rateSpan.classList.add('arfolyam-ertek');

            currencyWrapper.appendChild(icon);
            currencyWrapper.appendChild(currencySpan);
            li.appendChild(currencyWrapper);
            li.appendChild(rateSpan);

            li.addEventListener('click', () => {
                reversedStates[currency] = !reversedStates[currency];
                localStorage.setItem('reversedStates', JSON.stringify(reversedStates));
                li.classList.toggle('reversed');
                li.classList.toggle('normal');
                const newArfolyam = reversedStates[currency] ? (1 / parsedArfolyamok[currency]).toFixed(4) : parsedArfolyamok[currency];
                rateSpan.textContent = reversedStates[currency] ? `${newArfolyam} ${currency.toUpperCase()}` : `${newArfolyam} HUF`;
                parsedArfolyamok[currency] = reversedStates[currency] ? (1 / parsedArfolyamok[currency]).toFixed(4) : parsedArfolyamok[currency];
                localStorage.setItem('arfolyamok', JSON.stringify(parsedArfolyamok));
            });

            arfolyamLista.appendChild(li);
        });
        frissitesIdo.textContent = `Utolsó frissítés: (korábbi mentett adatok a helyi tárolóból)`;
    } else {
        Object.keys(defaultArfolyamok).forEach(currency => {
            const li = document.createElement('li');
            li.classList.add('normal');
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

            li.addEventListener('click', () => {
                reversedStates[currency] = !reversedStates[currency];
                localStorage.setItem('reversedStates', JSON.stringify(reversedStates));
                li.classList.toggle('reversed');
                li.classList.toggle('normal');
                const newArfolyam = reversedStates[currency] ? (1 / defaultArfolyamok[currency]).toFixed(4) : defaultArfolyamok[currency];
                rateSpan.textContent = reversedStates[currency] ? `${newArfolyam} ${currency.toUpperCase()}` : `${newArfolyam} HUF`;
            });

            arfolyamLista.appendChild(li);
        });
        frissitesIdo.textContent = `Utolsó frissítés: (alapértelmezett adatok, nem frissek – kérlek, próbáld meg frissíteni)`;
    }
}

async function frissitArfolyamok(retryCount = 0, maxRetries = 3) {
    const arfolyamLista = document.getElementById('arfolyam-lista');
    const frissitesIdo = document.getElementById('frissites-ido');
    const frissitesGomb = document.getElementById('frissites-gomb');

    if (!arfolyamLista || !frissitesIdo) {
        console.error('Hiányzó DOM elemek: arfolyam-lista vagy frissites-ido');
        return;
    }

    arfolyamLista.innerHTML = '<li class="betoltes">Betöltés...</li>';
    if (frissitesGomb) frissitesGomb.disabled = true;

    try {
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

        if (!data.rates || !data.rates.huf || !data.rates.huf.value) {
            throw new Error('Hiányzó vagy érvénytelen adatok az API válaszban');
        }

        const hufInBtc = data.rates.huf.value;
        const arfolyamok = {};

        megjelenitArfolyamokat(arfolyamok, hufInBtc, data, arfolyamLista, frissitesIdo);

        if (arfolyamLista.children.length === 0) {
            throw new Error('Nem sikerült árfolyamokat betölteni az API-ból');
        }
    } catch (error) {
        console.error('Hiba az árfolyamok lekérése során:', error.message);
        arfolyamLista.innerHTML = '';
        megjelenitMentettVagyAlapArfolyamokat(arfolyamLista, frissitesIdo);

        if (retryCount < maxRetries) {
            console.log(`Újrapróbálkozás (${retryCount + 1}/${maxRetries})...`);
            arfolyamLista.insertAdjacentHTML('beforeend', `<li class="info">Újrapróbálkozás ${retryCount + 1}/${maxRetries}...</li>`);
            setTimeout(() => frissitArfolyamok(retryCount + 1, maxRetries), 10000);
        }
    } finally {
        if (frissitesGomb) frissitesGomb.disabled = false;
    }
}

// Az árfolyamok frissítése az oldal betöltésekor és minden 5 percben
document.addEventListener('DOMContentLoaded', () => {
    frissitArfolyamok();
    setInterval(() => frissitArfolyamok(), 300000); // 300000 ms = 5 perc

    const frissitesGomb = document.getElementById('frissites-gomb');
    if (frissitesGomb) {
        frissitesGomb.addEventListener('click', () => {
            frissitArfolyamok();
        });
    }
});