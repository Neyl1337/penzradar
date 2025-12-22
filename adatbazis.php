<?php
$host = "mysql.rackhost.hu";
$dbname = "c78305felh";
$username = "c78305db";
$password = "Wzd4Jiem";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Adatbázis kapcsolat sikertelen: " . $e->getMessage());
}
?>