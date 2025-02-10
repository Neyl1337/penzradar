<?php
require_once 'adatbazis.php';
session_start();

try {
    // Felhasználói adat lekérése
    $stmt = $pdo->prepare("SELECT rang, perselyegyenleg FROM felhasznalok WHERE nev = :username");
    $stmt->bindParam(':username', $_SESSION['username']);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        // Szerepkör és perselyegyenleg mentése a sessionbe
        $_SESSION['szerepkor'] = $userData['rang'];
        $_SESSION['perselyegyenleg'] = $userData['perselyegyenleg'];

        // Kiíratjuk a szerepkört és a perselyegyenleget
        echo "Felhasználó rangja: " . htmlspecialchars($userData['rang']) . "<br>";
        echo "Persely egyenleg: " . htmlspecialchars($userData['perselyegyenleg']);
    } else {
        echo "A felhasználó nem található.";
    }
} catch (PDOException $e) {
    die("Hiba: " . $e->getMessage());
}
?>
