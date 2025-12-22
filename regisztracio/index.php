<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PénzRadar - Regisztráció</title>
    <link rel="icon" type="image/x-icon" href="../kepek/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="regisztracios-doboz">
        <div class="text-center">
            <img src="../kepek/ujlogo.png" alt="PénzRadar Logó" class="logo" id="mobilnezet">
        </div>
        <h1>PénzRadar</h1>
        <b class="d-flex justify-content-end border-bottom" id="mobilnezet"></b><br id="mobilnezet">
        <h5>Regisztráció</h5>
        <div class="mb-3" id="Uzenet">
            <center><p></p></center>
        </div>
        <form id="regForm" method="POST" action="adatbazis_signup.php">
            <div class="mb-3">
                <label for="nev" class="form-label">Felhasználónév</label>
                <input type="text" class="form-control" id="nev" name="nev" placeholder="Felhasználónév" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email cím</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Email cím" required>
            </div>
            <div class="mb-3 position-relative">
                <label for="jelszo" class="form-label">Jelszó</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="jelszo" name="jelszo" placeholder="Jelszó" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button" id="togglePassword1">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3 position-relative">
                <label for="jelszo-megerosites" class="form-label">Jelszó megerősítése</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="jelszo_megerosites" name="jelszo_megerosites" placeholder="Jelszó megerősítése" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button" id="togglePassword2">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="aszf" name="aszf" required>
                <label class="form-check-label" for="aszf">Elfogadom, hogy kezeljék az adataimat.</label>
            </div>
            <button type="submit" id="regisztracio" class="btn btn-zold w-100">Regisztráció</button>
        </form>        
        <div class= "oldal">
            <br><center><a href="../kezdolap/" id="mobilnezet"><b id="mobilnezet">Vissza az oldalra</b></a></center>
            <br id="mobilnezet"><b class="d-flex justify-content-end border-bottom" id="mobilnezet"></b><br id="mobilnezet">
        </div>
        <div class="almenet">
            <p>Van már fiókod? <a href="../bejelentkezes/">Bejelentkezés</a></p>
        </div>
    </div>

    <script>
        // Toggle for first password field
        document.getElementById('togglePassword1').addEventListener('click', function () {
            const password = document.getElementById('jelszo');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });

        // Toggle for second password field
        document.getElementById('togglePassword2').addEventListener('click', function () {
            const passwordConfirm = document.getElementById('jelszo_megerosites');
            const icon = this.querySelector('i');
            
            if (passwordConfirm.type === 'password') {
                passwordConfirm.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                passwordConfirm.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    </script>
    <script src="script.js"></script>
</body>
</html>