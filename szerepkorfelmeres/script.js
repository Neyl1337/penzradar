let aktualisKerdes = 0;
let kerdesek = document.getElementsByClassName('kerdes');
let valaszok = [];
let aktualisSzerepkor = '';
kerdesek[aktualisKerdes].classList.add('aktiv');

function initValaszok() {
    const valaszElemenek = document.querySelectorAll('.valasz');
    valaszElemenek.forEach(valasz => {
        valasz.addEventListener('click', function() {
            const testverek = this.parentElement.querySelectorAll('.valasz');
            testverek.forEach(t => t.classList.remove('kivalasztott'));
            this.classList.add('kivalasztott');
            const input = this.querySelector('input[type="radio"]');
            input.checked = true;
        });
    });
}

function lepjTovabb() {
    let kivalasztott = kerdesek[aktualisKerdes].querySelector('.valasz.kivalasztott input[type="radio"]:checked');
    if (!kivalasztott) {
        alert("Kérlek, válasszon egy opciót!");
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

    if (osszeg <= 5) {
        aktualisSzerepkor = "Takarékos";
    } else if (osszeg <= 10) {
        aktualisSzerepkor = "Szerény";
    } else if (osszeg <= 15) {
        aktualisSzerepkor = "Átlagos";
    } else if (osszeg <= 20) {
        aktualisSzerepkor = "Kiegyensúlyozott";
    } else if (osszeg <= 25) {
        aktualisSzerepkor = "Tehetős";
    } else if (osszeg <= 30) {
        aktualisSzerepkor = "Luxus";
    } else if (osszeg <= 35) {
        aktualisSzerepkor = "Prémium";
    } else {
        aktualisSzerepkor = "Elit";
    }

    eredmenyDiv.textContent = "Megítélt szerepkör: " + aktualisSzerepkor;
    eredmenyDiv.style.display = 'block';
    document.getElementById('mentes').style.display = 'block';
}

function mentesSzerepkor() {
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ szerepkor: aktualisSzerepkor })
    })
    .then(valasz => valasz.json())
    .then(adatok => {
        let eredmenyDiv = document.getElementById('eredmeny');
        if (adatok.siker) {
            eredmenyDiv.textContent = "Szerepkör sikeresen mentve: " + aktualisSzerepkor;
            setTimeout(() => {
                window.location.href = '../kezdolap/';
            }, 1000);
        } else {
            eredmenyDiv.textContent = "Hiba történt a mentés során: " + (adatok.hiba || "Ismeretlen hiba");
        }
    })
    .catch(hiba => {
        document.getElementById('eredmeny').textContent = "Hiba: " + hiba.message;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initValaszok();
});