document.getElementById('kamatszamitas').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Értékek lekérése
    const tokeInput = document.getElementById('toke');
    const kamatlabInput = document.getElementById('kamatlab');
    const futamidoInput = document.getElementById('futamido');
    const kamatozasiGyakorisagInput = document.getElementById('kamatozasi_gyakorisag');
    const kamatTipusInput = document.getElementById('kamat_tipus');
    const tokeValtozasInput = document.getElementById('toke_valtozas');
    const eredmenyDiv = document.getElementById('eredmeny');

    const toke = parseFloat(tokeInput.value);
    const kamatlab = parseFloat(kamatlabInput.value);
    const futamido = parseInt(futamidoInput.value);
    const kamatozasiGyakorisag = parseInt(kamatozasiGyakorisagInput.value);
    const kamatTipus = kamatTipusInput.value;
    const tokeValtozas = parseFloat(tokeValtozasInput.value) || 0;

    // Validáció
    let errorMessage = '';

    // Tőke validáció: 1 000 Ft és 9 999 999 999 Ft között (max 10 számjegy)
    if (isNaN(toke) || toke < 1000 || toke > 9999999999) {
        errorMessage += 'A tőke 1 000 Ft és 9 999 999 999 Ft között lehet!<br>';
    }

    // Kamatláb validáció: 0.1% és 99.9% között (max 3 számjegy)
    if (isNaN(kamatlab) || kamatlab < 0.1 || kamatlab > 99.9) {
        errorMessage += 'Az éves kamatláb 0.1% és 99.9% között lehet!<br>';
    }

    // Futamidő validáció: 1 év és 99 év között (max 2 számjegy)
    if (isNaN(futamido) || futamido < 1 || futamido > 99) {
        errorMessage += 'A futamidő 1 év és 99 év között lehet!<br>';
    }

    // Tőkeváltozás validáció: -toke és +9 999 999 999 Ft között
    if (isNaN(tokeValtozas) || tokeValtozas < -toke || tokeValtozas > 9999999999) {
        errorMessage += 'Az éves tőkeváltozás -' + toke.toLocaleString('hu-HU') + ' Ft és 9 999 999 999 Ft között lehet!<br>';
    }

    if (errorMessage) {
        eredmenyDiv.innerHTML = `<div class="error">${errorMessage}</div>`;
        return;
    }

    // Számítás
    let eredmenyHTML = '<h4>Eredmények</h4>';
    let aktualisToke = toke;
    let osszKamat = 0;
    let tableRows = '';

    if (kamatTipus === 'egyszeru') {
        // Egyszerű kamatszámítás
        for (let ev = 1; ev <= futamido; ev++) {
            const evesKamat = aktualisToke * (kamatlab / 100);
            osszKamat += evesKamat;
            const evesVegosszeg = aktualisToke + osszKamat;

            tableRows += `
                <tr>
                    <td>${ev}. év</td>
                    <td>${aktualisToke.toLocaleString('hu-HU', { maximumFractionDigits: 2 })} Ft</td>
                    <td>${evesKamat.toLocaleString('hu-HU', { maximumFractionDigits: 2 })} Ft</td>
                    <td>${evesVegosszeg.toLocaleString('hu-HU', { maximumFractionDigits: 2 })} Ft</td>
                </tr>
            `;

            // Tőke változása az év végén
            aktualisToke += tokeValtozas;
            if (aktualisToke < 0) aktualisToke = 0; // Nem lehet negatív tőke
        }

        eredmenyHTML += `
            <p><strong>Egyszerű kamatozás:</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Év</th>
                        <th>Tőke</th>
                        <th>Kamat</th>
                        <th>Végösszeg</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
        `;
    } else {
        // Kamatos kamatszámítás
        const evesKamatlab = kamatlab / 100;
        const periodusKamatlab = evesKamatlab / kamatozasiGyakorisag;

        for (let ev = 1; ev <= futamido; ev++) {
            // Kamatos kamat számítása az év eleji tőkére
            const periodusokSzama = kamatozasiGyakorisag;
            let evesVegosszeg = aktualisToke;

            for (let periodus = 0; periodus < periodusokSzama; periodus++) {
                evesVegosszeg = evesVegosszeg * (1 + periodusKamatlab);
            }

            const evesKamat = evesVegosszeg - aktualisToke;
            osszKamat += evesKamat;

            tableRows += `
                <tr>
                    <td>${ev}. év</td>
                    <td>${aktualisToke.toLocaleString('hu-HU', { maximumFractionDigits: 2 })} Ft</td>
                    <td>${evesKamat.toLocaleString('hu-HU', { maximumFractionDigits: 2 })} Ft</td>
                    <td>${evesVegosszeg.toLocaleString('hu-HU', { maximumFractionDigits: 2 })} Ft</td>
                </tr>
            `;

            // Tőke változása az év végén
            aktualisToke = evesVegosszeg + tokeValtozas;
            if (aktualisToke < 0) aktualisToke = 0; // Nem lehet negatív tőke
        }

        eredmenyHTML += `
            <p><strong>Kamatos kamatozás (gyakoriság: ${kamatozasiGyakorisag}x évente):</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Év</th>
                        <th>Tőke</th>
                        <th>Kamat</th>
                        <th>Végösszeg</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
        `;
    }

    eredmenyDiv.innerHTML = eredmenyHTML;
});