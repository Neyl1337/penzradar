<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Regisztráció</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="regisztracios-doboz">
        <h1>PénzRadar</h1>
        <div class="mb-3" id="Uzenet">
            <center><p></p></center>
        </div>
        <form action="adatbazis_signup.php" method="POST" id="regForm">
            <div class="mb-3">
                <label for="email" class="form-label">Email cím</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Email cím" required>
            </div>
            <div class="mb-3">
                <label for="nev" class="form-label">Név</label>
                <input type="text" class="form-control" id="nev" name="nev" placeholder="Név" required>
            </div>
            <div class="mb-3">
                <label for="jelszo" class="form-label">Jelszó</label>
                <input type="password" class="form-control" id="jelszo" name="jelszo" placeholder="Jelszó" required>
            </div>
            <div class="mb-3">
                <label for="jelszo-megerosites" class="form-label">Jelszó megerősítése</label>
                <input type="password" class="form-control" id="jelszo_megerosites" name="jelszo_megerosites" placeholder="Jelszó megerősítése" required>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="aszf" name="aszf" required>
                <label class="form-check-label" for="aszf">Elfogadom, hogy kezeljék az adataimat.</label>
            </div>
            <button type="submit" id="regisztracio" class="btn btn-zold w-100">Regisztráció</button>
        </form>        
        <div class="almenet">
            <p>Van már fiókod? <a href="../bejelentkezes/">Bejelentkezés</a></p>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>