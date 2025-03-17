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
        document.getElementById("egyenlegkezeles").style.visibility = "visible";
        document.getElementById("nemvagybejelentkezve").innerHTML = "";
    } else {
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("egyenlegkezeles").style.visibility = "none";
        document.getElementById("bejelentkez").style.visibility = "visible";
        document.getElementById("egyenlegkezeles").innerHTML = "";
    }
};

// document.addEventListener('DOMContentLoaded', () => {
//     const form = document.getElementById('supportForm');
//     const responseMessage = document.getElementById('responseMessage');

//     form.addEventListener('submit', (e) => {
//         e.preventDefault();
//         const type = document.getElementById('messageType').value;
//         const message = document.getElementById('message').value;

//         if (type && message) {
//             responseMessage.textContent = `Köszönjük! Az üzeneted elküldve: ${type}, hamarosan egy SUPPORT munkatársunk fel fogja venni veled a kapcsolatot!`;
//             form.reset();
//         } else {
//             responseMessage.textContent = 'Kérlek, töltsd ki az összes mezőt!';
//             responseMessage.style.color = '#ff073a';
//         }
//     });
// });