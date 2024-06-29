<?php
session_start(); // Session başlat

include 'baglan.php';
error_reporting(0);
ini_set('display_errors', 0);

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

    // Takım isimlerini veritabanından çekme
    $takimlar_sorgu = "SELECT id, isim FROM takimlar";
    $stmt_takimlar = $conn->query($takimlar_sorgu);
    $takimlar = $stmt_takimlar->fetchAll(PDO::FETCH_ASSOC);

    // Maç sonuçlarını güncelleme
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_results'])) {
        $fixtures_sorgu = "SELECT id, takim1_id, takim2_id FROM maclar";
        $stmt_fixtures = $conn->query($fixtures_sorgu);
        $matches = $stmt_fixtures->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matches as $match) {
            $mac_id = $match['id'];
            $takim1_gol = isset($_POST['takim1_gol_' . $mac_id]) && $_POST['takim1_gol_' . $mac_id] !== '' ? $_POST['takim1_gol_' . $mac_id] : null;
            $takim2_gol = isset($_POST['takim2_gol_' . $mac_id]) && $_POST['takim2_gol_' . $mac_id] !== '' ? $_POST['takim2_gol_' . $mac_id] : null;

            // Maç sonucunu güncelle
            $sql = "UPDATE maclar SET takim1_gol = :takim1_gol, takim2_gol = :takim2_gol WHERE id = :mac_id";
            $stmt_update = $conn->prepare($sql);
            $stmt_update->execute([':takim1_gol' => $takim1_gol, ':takim2_gol' => $takim2_gol, ':mac_id' => $mac_id]);
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['truncate_fixtures'])) {
        // maclar tablosunu truncate et
        $truncate_sql = "TRUNCATE TABLE maclar";
        $conn->exec($truncate_sql);
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['truncate_teams'])) {
        // Yabancı anahtar kısıtlamalarını geçici olarak devre dışı bırak
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // takimlar tablosunu truncate et
        $truncate_sql = "TRUNCATE TABLE takimlar";
        $conn->exec($truncate_sql);

        // Yabancı anahtar kısıtlamalarını tekrar etkinleştir
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    } else {
        // Fikstür olup olmadığını kontrol et
        $fixtures_check_sorgu = "SELECT COUNT(*) as count FROM maclar";
        $stmt_check = $conn->query($fixtures_check_sorgu);
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);

        // Eğer fikstür yoksa oluştur
        if ($result['count'] == 0) {
            $total_teams = count($takimlar);
            $total_weeks = $total_teams - 1; // 6 takım, 5 hafta
            $matches_per_week = $total_teams / 2; // Her hafta 3 maç

            $matches = [];
            $match_count = 0;

            // Fikstürü rastgele oluşturma
            for ($week = 1; $week <= $total_weeks; $week++) {
                $used_teams = [];
                for ($match = 1; $match <= $matches_per_week; $match++) {
                    do {
                        $team1 = rand(0, $total_teams - 1);
                    } while (in_array($team1, $used_teams));
                    $used_teams[] = $team1;

                    do {
                        $team2 = rand(0, $total_teams - 1);
                    } while (in_array($team2, $used_teams) || $team2 == $team1);
                    $used_teams[] = $team2;

                    $matches[$match_count] = [
                        'hafta' => $week,
                        'takim1_id' => $takimlar[$team1]['id'],
                        'takim1' => $takimlar[$team1]['isim'],
                        'takim2_id' => $takimlar[$team2]['id'],
                        'takim2' => $takimlar[$team2]['isim']
                    ];
                    $match_count++;
                }
            }

            // Fikstürü veritabanına kaydetme
            foreach ($matches as $match) {
                $sql = "INSERT INTO maclar (hafta, takim1_id, takim2_id) VALUES (:hafta, :takim1_id, :takim2_id)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':hafta' => $match['hafta'], ':takim1_id' => $match['takim1_id'], ':takim2_id' => $match['takim2_id']]);
            }
        }
    }

    // Fixtures sorgusu
    $fixtures_sorgu = "SELECT m.id, m.hafta, t1.isim as takim1, t2.isim as takim2, m.takim1_gol, m.takim2_gol
                       FROM maclar m
                       INNER JOIN takimlar t1 ON m.takim1_id = t1.id
                       INNER JOIN takimlar t2 ON m.takim2_id = t2.id
                       ORDER BY m.hafta, m.id";
    $stmt_fixtures = $conn->query($fixtures_sorgu);

?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="submit" name="logout" value="Çıkış Yap">
</form>
    <h2>Fikstürler</h2>
    <form method="post" action="fixtures.php">
        <table border='1'>
            <tr>
                <th>Hafta</th>
                <th>Takım 1</th>
                <th>Takım 2</th>
                <th>Sonuç</th>
            </tr>
            <?php
            $current_week = 0;
            while ($mac = $stmt_fixtures->fetch(PDO::FETCH_ASSOC)) {
                if ($mac['hafta'] != $current_week) {
                    $current_week = $mac['hafta'];
                    echo "<tr><td colspan='4' style='height:20px;'></td></tr>";
                }
            ?>
                <tr>
                    <td><?php echo $mac['hafta']; ?></td>
                    <td><?php echo $mac['takim1']; ?></td>
                    <td><?php echo $mac['takim2']; ?></td>
                    <td>
                        <input type="number" name="takim1_gol_<?php echo $mac['id']; ?>" value="<?php echo $mac['takim1_gol'] !== null ? $mac['takim1_gol'] : ''; ?>"> -
                        <input type="number" name="takim2_gol_<?php echo $mac['id']; ?>" value="<?php echo $mac['takim2_gol'] !== null ? $mac['takim2_gol'] : ''; ?>">
                    </td>
                </tr>
            <?php
            }
            ?>
        </table>
        <button type="submit" name="update_results" style="margin-top:20px">Güncelle</button>
    </form>

    <form method="post" action="fixtures.php" style="margin-top:50px">
        <button type="submit" name="truncate_fixtures">Fikstürü Temizle</button>
    </form>

    <form method="post" action="fixtures.php">
        <button type="submit" name="truncate_teams">Takımları Temizle</button>
    </form>

<?php

} catch (PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
}

// Bağlantıyı kapat
$conn = null;
?>
