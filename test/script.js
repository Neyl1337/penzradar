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
            szamitas();
        }
    }
    function szamitas() {
        let osszeg = valaszok.reduce((a, b) => a + b, 0);
        let eredmenyDiv = document.getElementById('eredmeny');
        let eredmenySzoveg;
        switch (true) {
            case (osszeg <= 3):
                eredmenySzoveg = "Te egy Spártai vagy!";
                break;
            case (osszeg <= 6):
                eredmenySzoveg = "Te egy Túlélő vagy!";
                break;
            case (osszeg <= 9):
                eredmenySzoveg = "Te egy Gazdálkodó vagy!";
                break;
            case (osszeg <= 12):
                eredmenySzoveg = "Te egy Kontrollőr vagy!";
                break;
            case (osszeg <= 15):
                eredmenySzoveg = "Te egy Egyensúlyozó vagy!";
                break;
            case (osszeg <= 18):
                eredmenySzoveg = "Te egy Élvező vagy!";
                break;
            case (osszeg <= 21):
                eredmenySzoveg = "Te egy Tehetős vagy!";
                break;
            case (osszeg <= 24):
                eredmenySzoveg = "Te egy Luxus vagy!";
                break;
            case (osszeg <= 27):
                eredmenySzoveg = "Te egy Király vagy!";
                break;
            default:
                eredmenySzoveg = "Te egy Milliárdos vagy!";
                break;
        }
        eredmenyDiv.textContent = eredmenySzoveg;
        eredmenyDiv.style.display = 'block';
    }