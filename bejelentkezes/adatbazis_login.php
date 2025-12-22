<?php
session_start();
require_once '../adatbazis.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $felhasznalonev = $_POST['nev'] ?? '';
    $jelszo = $_POST['jelszo'] ?? '';
    $emlekezzRam = isset($_POST['emlekezzRam']);

    if (empty($felhasznalonev) || empty($jelszo)) {
        echo json_encode(["siker" => false, "uzenet" => "A felhasználónév és jelszó megadása kötelező."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM felhasznalok WHERE nev = ?");
    $stmt->execute([$felhasznalonev]);
    $felhasznalo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($felhasznalo && password_verify($jelszo, $felhasznalo['jelszo'])) {
        $_SESSION['felhasznalo_id'] = $felhasznalo['id'];
        $_SESSION['felhasznalo_nev'] = $felhasznalo['nev'];
        $_SESSION['belepesi_ido'] = time();

        // Szerepkör és perselyegyenleg lekérése és mentése a sessionbe
        $_SESSION['szerepkor'] = $felhasznalo['rang'];
        $_SESSION['perselyegyenleg'] = $felhasznalo['perselyegyenleg'];

        // 30 percig legyen bejelentkezve
        setcookie('user_id', $felhasznalo['id'], time() + 1800, "/"); // 30 perc
        setcookie('user_name', $felhasznalo['nev'], time() + 1800, "/"); // 30 perc

        if ($emlekezzRam) {
            setcookie("felhasznalo_id", $felhasznalo['id'], time() + (30 * 24 * 60 * 60), "/");
            setcookie("felhasznalo_nev", $felhasznalo['nev'], time() + (30 * 24 * 60 * 60), "/");
        }

        echo json_encode([
            "siker" => true,
            "redirect_url" => "../kezdolap/"
        ]);
        exit;
    } else {
        echo json_encode(["siker" => false, "uzenet" => "Hibás felhasználónév vagy jelszó."]);
        exit;
    }
}
?>