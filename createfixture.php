<?php
session_start();

// Veritabanı bağlantı bilgileri
include 'baglan.php';
try {
    // Veritabanına bağlantı
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    // Hata modunu ayarla
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Admin girişi kontrolü
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Kullanıcı adı ve şifreyle giriş doğrulaması
        $sql = "SELECT * FROM user WHERE kadi = :username AND sifre = :password";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $username, ':password' => $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            echo "Kullanıcı adı veya şifre yanlış!";
        }
    }

    // Admin giriş kontrolü
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        // Admin girişi yapılmamışsa, giriş formunu göster
        ?>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <title>Admin Girişi</title>
        </head>
        <body>
        <h2>Admin Girişi</h2>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <label for="username">Kullanıcı Adı:</label><br>
            <input type="text" id="username" name="username" required><br><br>

            <label for="password">Şifre:</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <input type="submit" name="login" value="Giriş Yap">
        </form>
        </body>
        </html>
        <?php
        // Admin girişi yapılmamışsa, burada sayfayı sonlandır
        exit;
    }

    // Çıkış işlemi
    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header("Location: {$_SERVER['PHP_SELF']}");
        exit;
    }

    // Fikstür oluşturma işlemleri
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
        // Gelen takım isimlerini al
        $takimlar_input = $_POST['takimlar'];

        // Takım isimlerini virgülle ayırarak diziye dönüştür
        $takimlar = explode(",", $takimlar_input);
        $takimlar = array_map('trim', $takimlar); // Her bir takım ismini trim ile boşlukları temizle

        // Takımları veritabanına ekleyin (eğer daha önce eklenmemişse)
        foreach ($takimlar as $takim) {
            $takim = htmlspecialchars($takim); // XSS koruması için htmlspecialchars kullan
            $sql = "SELECT * FROM takimlar WHERE isim = :isim";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':isim' => $takim]);
            if ($stmt->rowCount() == 0) {
                $sql = "INSERT INTO takimlar (isim) VALUES (:isim)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':isim' => $takim]);
                echo "Takım eklendi: $takim<br>";
            }
        }

        // Takımları veritabanından alın
        $takimlar_sorgu = "SELECT id, isim FROM takimlar";
        $stmt = $conn->query($takimlar_sorgu);
        $takimlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Takımlar başarıyla alındı.<br>";

        // Her takımın diğer takımlarla maç yapması için sıralama oluştur
        $num_teams = count($takimlar);
        $hafta = 1;

        // Daha önce oynanan maçları takip etmek için dizi
        $played_matches = [];

        // Maçları belirle
        for ($i = 0; $i < $num_teams - 1; $i++) {
            for ($j = $i + 1; $j < $num_teams; $j++) {
                $played_matches[$takimlar[$i]['id']][$takimlar[$j]['id']] = false;
                $played_matches[$takimlar[$j]['id']][$takimlar[$i]['id']] = false;
            }
        }

        // Her hafta için maçları oluştur
        while ($hafta <= 5) {
            echo "<h3>$hafta. Hafta Maçları</h3>";

            // Bu haftaki maçları tutacak dizi
            $weekly_matches = [];
            $attempts = 0;

            while (count($weekly_matches) < 3 && $attempts < 100) {
                // Takımları rastgele sırala
                shuffle($takimlar);

                $temp_matches = [];
                $used_teams = [];

                for ($i = 0; $i < $num_teams - 1; $i += 2) {
                    $takim1 = $takimlar[$i];
                    $takim2 = $takimlar[$i + 1];

                    // Aynı takımla tekrar maç yapma kontrolü ve aynı haftada maç yapmama kontrolü
                    if (!$played_matches[$takim1['id']][$takim2['id']] &&
                        !in_array($takim1['id'], $used_teams) &&
                        !in_array($takim2['id'], $used_teams)) {

                        // Maç yapıldığını işaretle
                        $temp_matches[] = [$takim1, $takim2];
                        $used_teams[] = $takim1['id'];
                        $used_teams[] = $takim2['id'];
                    }
                }

                if (count($temp_matches) == 3) {
                    foreach ($temp_matches as $mac) {
                        $takim1 = $mac[0];
                        $takim2 = $mac[1];

                        // Maçı veritabanına ekle
                        $sql = "INSERT INTO maclar (takim1_id, takim2_id, takim1_gol, takim2_gol, hafta) VALUES (:takim1_id, :takim2_id, NULL, NULL, :hafta)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([':takim1_id' => $takim1['id'], ':takim2_id' => $takim2['id'], ':hafta' => $hafta]);
                        echo "{$takim1['isim']} - {$takim2['isim']}<br>";

                        // Maç yapıldığını işaretle
                        $played_matches[$takim1['id']][$takim2['id']] = true;
                        $played_matches[$takim2['id']][$takim1['id']] = true;

                        // Haftalık maçlara ekle
                        $weekly_matches[] = [$takim1['id'], $takim2['id']];
                    }
                }

                $attempts++;
            }

            if (count($weekly_matches) < 3) {
                echo "Yeterli maç eşleşmesi oluşturulamadı.<br>";
                break;
            }

            $hafta++; // Hafta numarasını artır
        }
    
        echo "Fikstür başarıyla oluşturuldu.<br>";
    }
} catch (PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
}

// Çıkış işlemi formu
?>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Futbol Ligi Fikstür Oluşturma</title>
</head>
<body>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="submit" name="logout" value="Çıkış Yap">
</form>

<h2>Futbol Ligi Fikstür Oluşturma</h2>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <label for="takimlar">Takım İsimleri (Virgülle Ayırarak Girin):</label><br>
    <textarea id="takimlar" name="takimlar" rows="4" cols="50"></textarea><br><br>
    <input type="submit" name="submit" value="Fikstürü Oluştur">
</form>

</body>
</html>
