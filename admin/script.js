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
    } else {
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("statisztika").style.visibility = "none";
        document.getElementById("nemvagybejelentkezve").style.visibility = "visible";
        document.getElementById("statisztika").innerHTML = "";
    }
};

        // Rang szűrés automatikusan a kiválasztáskor
        document.getElementById('rank').addEventListener('change', function() {
            const selectedRank = this.value;
            const nameFilter = document.getElementById('name_filter').value;
            let url = 'index.php';
            const params = [];
            if (selectedRank) {
                params.push('rank=' + encodeURIComponent(selectedRank));
            }
            if (nameFilter) {
                params.push('name=' + encodeURIComponent(nameFilter));
            }
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            window.location.href = url;
        });