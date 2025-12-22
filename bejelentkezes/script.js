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