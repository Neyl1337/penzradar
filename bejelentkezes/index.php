<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés - PénzRadar.hu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="bejelentkezo-doboz">
        <h1>PénzRadar.hu</h1>
        <div class="mb-3" id="Uzenet">
            <center><p></p></center>
        </div>
        <form id="login-form">
            <div class="mb-3">
                <label for="nev" class="form-label">Név</label>
                <input type="text" class="form-control" id="nev" name="nev" placeholder="Név" required>
            </div>
            <div class="mb-3">
                <label for="jelszo" class="form-label">Jelszó</label>
                <input type="password" class="form-control" id="jelszo" name="jelszo" placeholder="Jelszó" required>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="emlekezzRam" name="emlekezzRam">
                <label class="form-check-label" for="emlekezzRam">Név elmentése</label>
            </div>
            <button type="submit" class="btn btn-zold w-100">Bejelentkezés</button>
        </form>        
        <div class="almenet">
            <p>Nincs még fiókod? <a href="../regisztracio/">Regisztráció</a></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
