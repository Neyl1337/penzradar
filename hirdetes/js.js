// Random szövegek és számok generálása
const titles = [
    "Számold ki devizáidat!",
    "Optimalizáld pénzügyed!",
    "Kalkulálj okosan!",
    "Fedezd fel megtakarításaid!",
    "Kezeld valutaidat most!"
];

const subtitles = [
    "Gyors és pontos átváltás!",
    "Spórolj a legjobb árfolyamokkal!",
    "Egyszerűen, azonnal!",
    "Próbáld ki ingyen!",
    "Légy pénzügyi mester!"
];

const ctas = [
    "Kezdd most!",
    "Számolj most!",
    "Próbáld ki!",
    "Nézd meg!",
    "Indulj el!"
];

function getRandomItem(array) {
    return array[Math.floor(Math.random() * array.length)];
}

function getRandomPrice() {
    const min = 1000;
    const max = 100000;
    const price = Math.floor(Math.random() * (max - min + 1)) + min;
    return price.toLocaleString("hu-HU") + " HUF";
}

function updateAd() {
    const title = document.getElementById("title");
    const subtitle = document.getElementById("subtitle");
    const counter = document.getElementById("counter");
    const cta = document.getElementById("cta");

    title.textContent = getRandomItem(titles);
    subtitle.textContent = getRandomItem(subtitles);
    counter.textContent = getRandomPrice();
    cta.textContent = getRandomItem(ctas);
}

// Inicializálás és 5 másodpercenkénti frissítés
updateAd();
setInterval(updateAd, 5000);