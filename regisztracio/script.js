document.getElementById('regForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    const submitButton = document.querySelector('button[type="submit"]');
    const uzenetElem = document.getElementById('Uzenet');

    submitButton.disabled = true;
    submitButton.innerText = "Kérlek várj...";

    const response = await fetch('adatbazis_signup.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();

    uzenetElem.style.display = 'block';
    uzenetElem.querySelector('p').textContent = result.message;
    uzenetElem.style.color = result.success ? '#90EE90' : '#FF7F7F';

    setTimeout(() => {
        if (result.success) {
            window.location.href = result.redirect;
        } else {
            submitButton.disabled = false;
            submitButton.innerText = "Regisztráció";
            uzenetElem.style.display = 'none';
        }
    }, 1500);
});
