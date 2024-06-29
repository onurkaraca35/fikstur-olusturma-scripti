<?php
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "futbol_ligi";

try {
    // PDO ile veritabanına bağlantı
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $db_username, $db_password);
    // Hata modunu ayarla
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}
?>

