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
        document.getElementById("modositas").style.visibility = "visible";
        document.getElementById("modositas2").style.visibility = "visible";
        document.getElementById("modositas3").style.visibility = "visible";
        document.getElementById("modositas4").style.visibility = "visible";
    } else {
        document.getElementById('felhasznaloNev').textContent = "Jelentkezz be!";
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("modositas").style.visibility = "none";
        document.getElementById("modositas2").style.visibility = "none";
        document.getElementById("modositas3").style.visibility = "none";
        document.getElementById("modositas4").style.visibility = "none";
    }
};