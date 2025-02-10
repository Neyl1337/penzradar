<?php
session_start();

// Töröljük a session adatokat
session_unset();
session_destroy();

// Töröljük a cookie-kat is
setcookie('user_id', '', time() - 3600, '/');
setcookie('user_name', '', time() - 3600, '/');

// Átirányítjuk a felhasználót a bejelentkezési oldalra
header('Location: ../bejelentkezes/');
exit;
?>
