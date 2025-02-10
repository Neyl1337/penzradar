<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Új jelszó</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="regisztracios-doboz">
        <h1>PénzRadar</h1>
        <h5>Új jelszó</h5>
        <div class="mb-3" id="Uzenet">
            <center><p></p></center>
        </div>
        <form id="regForm" method="POST" action="adatbazis_signup.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email cím</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Email cím" required>
            </div>
            <button type="submit" id="regisztracio" class="btn btn-zold w-100">Kód küldése</button>
        </form>        
        <div class= "oldal">
            <br><center><a href="../kezdolap/"><b>Vissza az oldalra</b></a></center>
            <br>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
