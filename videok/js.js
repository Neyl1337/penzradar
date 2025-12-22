// Videó lejátszás kezelése
document.addEventListener('DOMContentLoaded', function() {
    const introVideo = document.getElementById('introVideo');
    const introModal = document.getElementById('introModal');
    const mainContent = document.getElementById('mainContent');

    const isFirstVisitInTab = !sessionStorage.getItem('hasVisitedInTab');
    const isLoggedIn = '<?php echo isset($_SESSION["felhasznalo_id"]) ? "true" : "false"; ?>';

    if (!isFirstVisitInTab || isLoggedIn !== "true") {
        introModal.style.display = 'none';
        return;
    }

    introVideo.play().then(() => {
        sessionStorage.setItem('hasVisitedInTab', 'true');
    }).catch(function(error) {
        console.log("A videó automatikus lejátszása nem sikerült: ", error);
        introModal.classList.add('fade-out');
        setTimeout(() => {
            introModal.style.display = 'none';
        }, 1000);
    });

    introVideo.onended = function() {
        introModal.classList.add('fade-out');
        setTimeout(() => {
            introModal.style.display = 'none';
        }, 1000);
    };

    introVideo.onerror = function() {
        introModal.classList.add('fade-out');
        setTimeout(() => {
            introModal.style.display = 'none';
        }, 1000);
    };
});