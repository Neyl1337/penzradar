// Bemenet ellenőrző függvény
function validateInput(input) {
    const maxToke = 1000000000000; // 1 billió
    const maxKamat = 100; // 100% (tizedesekkel is)
    const maxIdotartam = 99; // 2 számjegy

    let value = input.value.replace(/[^0-9.]/g, ''); // Tizedes pont engedélyezése a kamatlábhoz
    if (input.id.includes('kamatSzazalek')) {
        value = parseFloat(value) || 0;
        if (value > maxKamat) value = maxKamat;
        if (value < 0) value = 0; // Negatív számok tiltása
    } else if (input.id.includes('idotartam')) {
        value = parseInt(value) || 1;
        if (value > maxIdotartam) value = maxIdotartam;
        if (value < 1) value = 1; // Minimum 1 év
    } else { // alapOsszeg
        value = parseInt(value) || 0;
        if (value > maxToke) value = maxToke;
        if (value < 0) value = 0; // Negatív számok tiltása
    }
    input.value = value;
}

// Kamatszámítás bejelentkezett felhasználóknak
function szamitKamat() {
    const alapOsszeg = BigInt(document.getElementById('alapOsszeg').value || 0);
    const kamatSzazalek = parseFloat(document.getElementById('kamatSzazalek').value || 0);
    const idotartam = BigInt(document.getElementById('idotartam').value || 0);

    if (kamatSzazalek < 0 || kamatSzazalek > 100 || idotartam < 0) {
        document.getElementById('kamatEredmeny').innerText = "Kérlek, adj meg érvényes számokat! (Kamatláb: 0-100% lehet tizedesekkel, Futamidő: 1-99 év)";
        return;
    }

    const kamat = (alapOsszeg * BigInt(Math.round(kamatSzazalek * 100)) * idotartam) / BigInt(10000);
    const osszeg = alapOsszeg + kamat;

    document.getElementById('kamatEredmeny').innerText = 
        `Kamat: ${kamat.toLocaleString('hu-HU')} Ft\nÖsszeg: ${osszeg.toLocaleString('hu-HU')} Ft`;
}

// Kamatszámítás kijelentkezett felhasználóknak
function szamitKamatLoggedOut() {
    const alapOsszeg = BigInt(document.getElementById('alapOsszegLoggedOut').value || 0);
    const kamatSzazalek = parseFloat(document.getElementById('kamatSzazalekLoggedOut').value || 0);
    const idotartam = BigInt(document.getElementById('idotartamLoggedOut').value || 0);

    if (kamatSzazalek < 0 || kamatSzazalek > 100 || idotartam < 0) {
        document.getElementById('kamatEredmenyLoggedOut').innerText = "Kérlek, adj meg érvényes számokat! (Kamatláb: 0-100% lehet tizedesekkel, Futamidő: 1-99 év)";
        return;
    }

    const kamat = (alapOsszeg * BigInt(Math.round(kamatSzazalek * 100)) * idotartam) / BigInt(10000);
    const osszeg = alapOsszeg + kamat;

    document.getElementById('kamatEredmenyLoggedOut').innerText = 
        `Kamat: ${kamat.toLocaleString('hu-HU')} Ft\nÖsszeg: ${osszeg.toLocaleString('hu-HU')} Ft`;
}

// Kamatszámítás függvény
function szamitKamat() {
    const alapOsszeg = parseFloat(document.getElementById('alapOsszeg').value);
    const kamatSzazalek = parseFloat(document.getElementById('kamatSzazalek').value);
    const idotartam = parseInt(document.getElementById('idotartam').value);

    if (isNaN(alapOsszeg) || isNaN(kamatSzazalek) || isNaN(idotartam)) {
        document.getElementById('kamatEredmeny').innerText = "Kérlek, adj meg érvényes számokat!";
        return;
    }

    // Egyszerű kamatszámítás: Összeg = Alapösszeg * (1 + Kamat * Időtartam)
    const vegOsszeg = alapOsszeg * (1 + (kamatSzazalek / 100) * idotartam);
    const formataltVegOsszeg = vegOsszeg.toLocaleString('hu-HU', { maximumFractionDigits: 0 });

    document.getElementById('kamatEredmeny').innerText = `Végösszeg: ${formataltVegOsszeg} Ft`;
}