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