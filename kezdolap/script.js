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


function hetTartalma() {
    const maiDatum = new Date();
    const jelenlegiNap = maiDatum.getDay();
    const kulonbsegHetfoig = jelenlegiNap === 0 ? -6 : 1 - jelenlegiNap;

    const hetfo = new Date(maiDatum);
    hetfo.setDate(maiDatum.getDate() + kulonbsegHetfoig);

    const vasarnap = new Date(hetfo);
    vasarnap.setDate(hetfo.getDate() + 6);

    const hetfoDatum = hetfo.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const vasarnapDatum = vasarnap.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    return `${hetfoDatum} - ${vasarnapDatum}`;
}

function maiNap() {
    const maiDatum = new Date();
    const napNevek = ['Vasárnap', 'Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat'];
    const maiNapNeve = napNevek[maiDatum.getDay()];
    const formazottDatum = maiDatum.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    return `Ma <span class="zold-nap">${maiNapNeve}</span> van: ${formazottDatum}`;
}

function frissitIdo() {
    const most = new Date();
    const ido = most.toLocaleTimeString('hu-HU', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    return `Jelenlegi idő: <span class="zold-nap">${ido}</span>`;
}

const hetiTartalomP = document.getElementById('heti-tartalom');
const maiNapP = document.getElementById('mai-nap');
const aktualisIdoP = document.getElementById('aktualis-ido');

hetiTartalomP.textContent = hetTartalma();
maiNapP.innerHTML = maiNap();

aktualisIdoP.innerHTML = frissitIdo();
setInterval(() => {
    aktualisIdoP.innerHTML = frissitIdo();
}, 1000);

if (bejelentkezve === "true") {
    const cookieModal = document.getElementById('cookieModal');
    const tesztModal = document.getElementById('tesztModal');

    if (cookieModal) {
        const cookieBsModal = new bootstrap.Modal(cookieModal, { backdrop: 'static', keyboard: false });
        cookieBsModal.show();
    } else if (tesztModal) {
        const tesztBsModal = new bootstrap.Modal(tesztModal, { backdrop: 'static', keyboard: false });
        tesztBsModal.show();
    }
}