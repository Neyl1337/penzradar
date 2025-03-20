window.onload = () => {
    const formatáltEgyenleg = new Intl.NumberFormat('en-US', { useGrouping: true }).format(egyenleg);

    document.getElementById('perselyegyenlegText').textContent = formatáltEgyenleg;

    if (userName) {
        document.getElementById('felhasznaloNev').textContent = userName;
        document.getElementById("bejelentkezesopcio").style.display = "none";
        document.getElementById("profilopcio").style.display = "block";
        document.getElementById("beallitasopcio").style.display = "block";
        document.getElementById("kijelentkezesopcio").style.display = "block";
        document.getElementById("perselyegyenleg").style.visibility = "visible";
        document.getElementById("szerepkor").style.visibility = "visible";
        document.getElementById("egyenlegkezeles").style.visibility = "visible";
        document.getElementById("nemvagybejelentkezve").innerHTML = "";
    } else {
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("egyenlegkezeles").style.visibility = "none";
        document.getElementById("bejelentkez").style.visibility = "visible";
        document.getElementById("egyenlegkezeles").innerHTML = "";
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const penzradarTitle = document.getElementById('penzradarTitle');
    const penzradarAudio = document.getElementById('penzradarAudio');
    const submitButton = document.getElementById('submitButton');
    const form = document.getElementById('supportForm');
    const responseMessage = document.getElementById('responseMessage');

    penzradarTitle.addEventListener('click', function() {
        penzradarAudio.currentTime = 0;
        penzradarAudio.play().catch(function(error) {
            console.log("A hang lejátszása nem sikerült: ", error);
        });
    });

    let lastSubmissionTime = localStorage.getItem('lastSubmissionTime') ? parseInt(localStorage.getItem('lastSubmissionTime')) : 0;
    const cooldownPeriod = 1 * 60 * 1000; // 1 perc cooldown
    let isSubmitting = false;

    function updateCooldown() {
        const now = Date.now();
        const timeLeft = Math.max(0, Math.floor((cooldownPeriod - (now - lastSubmissionTime)) / 1000));

        if (timeLeft > 0 || isSubmitting) {
            submitButton.disabled = true;
            submitButton.classList.add('cooldown-gray');
            if (!isSubmitting) {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                submitButton.textContent = `Elérhetővé válik: ${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
        } else {
            submitButton.disabled = false;
            submitButton.classList.remove('cooldown-gray');
            submitButton.textContent = 'Küldés';
        }
    }

    setInterval(updateCooldown, 1000);
    updateCooldown();

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (submitButton.disabled) {
            console.log('A gomb le van tiltva, küldés nem lehetséges.');
            return;
        }
        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.classList.add('success-green');
        submitButton.textContent = 'Küldés folyamatban';

        const formData = new FormData(form);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => {
                    submitButton.classList.remove('success-green');
                    submitButton.classList.add('cooldown-gray');
                    responseMessage.style.display = 'block';
                    responseMessage.style.color = 'green';
                    responseMessage.textContent = 'Üzenetét sikeresen elküldtük! Megerősítő emailt fog kapni.';
                    lastSubmissionTime = Date.now();
                    localStorage.setItem('lastSubmissionTime', lastSubmissionTime);
                    isSubmitting = false;
                    updateCooldown();
                    form.reset();
                    setTimeout(() => {
                        responseMessage.style.display = 'none';
                        location.reload();
                    }, 5000);
                }, 3000);
            } else {
                submitButton.classList.remove('success-green');
                submitButton.classList.add('cooldown-gray');
                submitButton.textContent = 'Küldés sikertelen';
                responseMessage.style.display = 'block';
                responseMessage.style.color = 'red';
                responseMessage.textContent = 'Az üzenet küldése sikertelen!';
                isSubmitting = false;
                updateCooldown();
                setTimeout(() => {
                    responseMessage.style.display = 'none';
                }, 5000);
            }
        })
        .catch(error => {
            console.error('Hiba történt:', error);
            submitButton.classList.remove('success-green');
            submitButton.classList.add('cooldown-gray');
            submitButton.textContent = 'Küldés sikertelen';
            responseMessage.style.display = 'block';
            responseMessage.style.color = 'red';
            responseMessage.textContent = 'Az üzenet küldése sikertelen!';
            isSubmitting = false;
            updateCooldown();
            setTimeout(() => {
                responseMessage.style.display = 'none';
            }, 5000);
        });
    });

    // Visszaszámláló logika
    function startCountdown() {
        const countdownElements = document.querySelectorAll('.countdown');
        countdownElements.forEach(element => {
            const startTime = parseInt(element.getAttribute('data-start-time')) * 1000; // Unix időbélyeg milliszekundumban
            const endTime = startTime + (10 * 60 * 60 * 1000); // 5 óra

            function updateCountdown() {
                const now = Date.now();
                const timeLeft = endTime - now;

                if (timeLeft <= 0) {
                    element.textContent = 'Lejárt';
                } else {
                    const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                    element.textContent = `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        });
    }

    startCountdown();
});