let aktualisKerdes = 0;
let kerdesek = document.getElementsByClassName('kerdes');
let valaszok = [];
kerdesek[aktualisKerdes].classList.add('aktiv');

function lepjTovabb() {
    let kivalasztott = kerdesek[aktualisKerdes].querySelector('input[type="radio"]:checked');
    if (!kivalasztott) {
        alert("Kérlek, válassz egy opciót!");
        return;
    }
    let kivalasztottErtek = parseInt(kivalasztott.value);
    valaszok.push(kivalasztottErtek);
    kerdesek[aktualisKerdes].classList.remove('aktiv');
    aktualisKerdes++;
    if (aktualisKerdes < kerdesek.length) {
        kerdesek[aktualisKerdes].classList.add('aktiv');
    } else {
        document.getElementById('kovetkezo').style.display = 'none';
        eredmenyKalkulacio();
    }
}

function eredmenyKalkulacio() {
    let osszeg = valaszok.reduce((a, b) => a + b, 0);
    let eredmenyDiv = document.getElementById('eredmeny');
    let eredmenySzoveg;

    if (osszeg <= 5) {
        eredmenySzoveg = "Takarékos";
    } else if (osszeg <= 10) {
        eredmenySzoveg = "Szerény";
    } else if (osszeg <= 15) {
        eredmenySzoveg = "Átlagos";
    } else if (osszeg <= 20) {
        eredmenySzoveg = "Kiegyensúlyozott";
    } else if (osszeg <= 25) {
        eredmenySzoveg = "Tehetős";
    } else if (osszeg <= 30) {
        eredmenySzoveg = "Luxus";
    } else if (osszeg <= 35) {
        eredmenySzoveg = "Prémium";
    } else {
        eredmenySzoveg = "Elit";
    }

    eredmenyDiv.textContent = "Megítélt szerepkör: " + eredmenySzoveg;
    eredmenyDiv.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    let sutiModal = document.getElementById('sutiModal');
    let oldalModal = document.getElementById('oldalModal');
    let sutiElfogad = document.getElementById('sutiElfogad');
    let sutiBezár = document.getElementById('sutiBezár');
    let oldalMegnyit = document.getElementById('oldalMegnyit');
    let oldalBezár = document.getElementById('oldalBezár');

    // Sütik ellenőrzése
    let sutiElfogadva = localStorage.getItem('sutiElfogadva');
    let utolsoElfogadas = localStorage.getItem('sutiElfogadasDatum');
    let egyEv = 365 * 24 * 60 * 60 * 1000; // 1 év milliszekundumban
    let most = new Date().getTime();

    if (!sutiElfogadva || (utolsoElfogadas && (most - utolsoElfogadas > egyEv))) {
        sutiModal.style.display = 'block';
    } else {
        mutasdOldalModal(); // Ha nincs sütik ablak, rögtön a másik jön
    }

    // Sütik elfogadása
    sutiElfogad.onclick = function() {
        localStorage.setItem('sutiElfogadva', 'igen');
        localStorage.setItem('sutiElfogadasDatum', most);
        sutiModal.style.display = 'none';
        mutasdOldalModal();
    };

    // Sütik bezárása
    sutiBezár.onclick = function() {
        sutiModal.style.display = 'none';
        mutasdOldalModal();
    };

    // Másik oldal megnyitása
    oldalMegnyit.onclick = function() {
        window.open('https://www.penzugyitippek.hu', '_blank'); // Példa URL
        oldalModal.style.display = 'none';
    };

    // Másik oldal bezárása
    oldalBezár.onclick = function() {
        oldalModal.style.display = 'none';
    };

    function mutasdOldalModal() {
        oldalModal.style.display = 'block';
    }
});