<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció - PénzRadar.hu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .regisztracios-doboz {
            background-color: #2b2b2b;
            border-radius: 12px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        }
        .regisztracios-doboz h1 {
            color: #63ffbe;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-control {
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #63ffbe;
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: #63ffbe;
            box-shadow: 0 0 5px #63ffbe;
        }
        .btn-zold {
            background-color: #63ffbe;
            color: #1a1a1a;
            border-radius: 8px;
            border: none;
        }

        a {
            color: #63ffbe;
            text-decoration: none;
        }

        .btn-zold:hover {
            background-color: #63ffbe;
            color: #ffffff;
        }
        .almenet {
            text-align: center;
            margin-top: 10px;
        }
        .almenet a {
            color: #63ffbe;
            text-decoration: none;
        }
        
        .almenet a:hover {
            text-decoration: underline;
        }
        #Uzenet {
            color: #FF7F7F;
            display: none;
        }
    </style>
</head>
<body>
    <div class="regisztracios-doboz">
        <h1>PénzRadar.hu</h1>
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
                <label class="form-check-label" for="aszf">Elfogadom az <a href="https://policies.google.com/terms?hl=hu" target="_blank">Általános Szerződési Feltételek</a>et.</label>
            </div>
            <button type="submit" id="regisztracio" class="btn btn-zold w-100">Regisztráció</button>
        </form>        
        <div class="almenet">
            <p>Van már fiókod? <a href="../bejelentkezes/">Bejelentkezés</a></p>
        </div>
    </div>
</body>

<script>
    const form = document.querySelector('form');
    const uzenetElem = document.getElementById('Uzenet');
    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // Ne töltse újra az oldalt
        const formData = new FormData(form);

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();
        if (result.success) {
            uzenetElem.style.display = 'block';
            uzenetElem.querySelector('p').textContent = result.message;
            uzenetElem.style.color = '#90EE90';
            setTimeout(() => {
                window.location.href = "../bejelentkezes/"; // Átirányítás
            }, 1000);
        } else {
            uzenetElem.style.display = 'block';
            uzenetElem.querySelector('p').textContent = result.message;
            uzenetElem.style.color = '#FF7F7F';

            setTimeout(() => {
                uzenetElem.style.display = 'none';
            }, 3000);
        }
    });
</script>

</html>