<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés - PénzRadar.hu</title>
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
        .bejelentkezo-doboz {
            background-color: #2b2b2b;
            border-radius: 12px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        }
        .bejelentkezo-doboz h1 {
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

    <script>
        window.onload = () => {
            const elmentettNev = localStorage.getItem('nev');
            const elmentettEmlekezzRam = localStorage.getItem('emlekezzRam') === 'true';

            if (elmentettNev && elmentettEmlekezzRam) {
                document.getElementById('nev').value = elmentettNev;
                document.getElementById('emlekezzRam').checked = true;
            }
        };

        const form = document.getElementById('login-form');
        const uzenetElem = document.getElementById('Uzenet');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const nev = document.getElementById('nev').value;
            const jelszo = document.getElementById('jelszo').value;
            const emlekezzRam = document.getElementById('emlekezzRam').checked;

            const formData = new FormData(form);
            const response = await fetch('adatbazis_login.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.siker) {
                uzenetElem.style.display = 'block';
                uzenetElem.style.color = '#90EE90';
                uzenetElem.querySelector('p').textContent = 'Sikeres bejelentkezés!';

                if (emlekezzRam) {
                    localStorage.setItem('nev', nev);
                    localStorage.setItem('emlekezzRam', 'true');
                } else {
                    localStorage.removeItem('nev');
                    localStorage.removeItem('emlekezzRam');
                }

                setTimeout(() => {
                    window.location.href = result.redirect_url;
                }, 1000);
            } else {
                uzenetElem.style.display = 'block';
                uzenetElem.style.color = '#FF7F7F';
                uzenetElem.querySelector('p').textContent = result.uzenet;

                setTimeout(() => {
                    uzenetElem.style.display = 'none';
                }, 3000);
            }
        });
    </script>
</body>
</html>
