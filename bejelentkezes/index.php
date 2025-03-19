<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Bejelentkezés</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="bejelentkezo-doboz">
        <div class="text-center">
            <img src="../kepek/ujlogo.png" alt="PénzRadar Logó" class="logo" id="mobilnezet">
        </div>
        <h1>PénzRadar</h1>
        <b class="d-flex justify-content-end border-bottom" id="mobilnezet"></b><br id="mobilnezet">
        <h5>Bejelentkezés</h5>
        <div class="mb-3" id="Uzenet">
            <center><p></p></center>
        </div>
        <form id="login-form">
            <div class="mb-3">
                <label for="nev" class="form-label">Felhasználónév</label>
                <input type="text" class="form-control" id="nev" name="nev" placeholder="Felhasználónév" required>
            </div>
            <div class="mb-3">
                <label for="jelszo" class="form-label">Jelszó</label>
                <input type="password" class="form-control" id="jelszo" name="jelszo" placeholder="Jelszó" required>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="emlekezzRam" name="emlekezzRam">
                <label class="form-check-label" for="emlekezzRam">Név elmentése</label>
            </div>
            <button type="submit" class="btn btn-zold w-100">Bejelentkezés</button> <br> <br>
            <div class= "oldal">
                <center><a href="../kezdolap/"><b>Vissza az oldalra</b></a></center>
            </div>
            <br id="mobilnezet"><b class="d-flex justify-content-end border-bottom" id="mobilnezet"></b>
        </form>      
        <div class="almenet">
            <br>
            <p>Elfelejtetted a jelszavadat? <a href="../ujjelszo/">Új jelszó kérése</a></p>
            <p>Nincs még fiókod? <a href="../regisztracio/">Regisztráció</a></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
