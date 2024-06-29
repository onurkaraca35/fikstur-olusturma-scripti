<?php
include 'baglan.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Veritabanına bağlantı
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Maç sonuçlarını güncelleme
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clear_data'])) {
        // Maç sonuçlarını sıfırla
        $clear_matches_sql = "UPDATE maclar SET takim1_gol = NULL, takim2_gol = NULL";
        $stmt = $conn->prepare($clear_matches_sql);
        $stmt->execute();

        // Puan tablosunu sıfırla
        $clear_points_sql = "UPDATE takimlar SET oynanan = 0, kazanan = 0, berabere = 0, kaybeden = 0, atilan_gol = 0, yenilen_gol = 0, puan = 0";
        $stmt = $conn->prepare($clear_points_sql);
        $stmt->execute();

        echo "<p style='color: green;'>Maç sonuçları ve puan tablosu başarıyla sıfırlandı.</p>";
    }

    // Puan tablosunu başlangıçta sıfırlama
    $puan_tablosu = array();
    $takimlar_sorgu = "SELECT id, isim FROM takimlar";
    $stmt = $conn->query($takimlar_sorgu);
    while ($satir = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $puan_tablosu[$satir['id']] = [
            "isim" => $satir['isim'],
            "oynanan" => 0,
            "kazanan" => 0,
            "berabere" => 0,
            "kaybeden" => 0,
            "atilan_gol" => 0,
            "yenilen_gol" => 0,
            "puan" => 0,
            "averaj" => 0
        ];
    }

    // Maçları alıp puan tablosunu güncelleme
    $maclar_sorgu = "SELECT * FROM maclar WHERE takim1_gol IS NOT NULL AND takim2_gol IS NOT NULL";
    $stmt = $conn->query($maclar_sorgu);
    while ($mac = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $takim1 = $mac['takim1_id'];
        $takim2 = $mac['takim2_id'];
        $gol1 = $mac['takim1_gol'];
        $gol2 = $mac['takim2_gol'];

        $puan_tablosu[$takim1]['oynanan']++;
        $puan_tablosu[$takim2]['oynanan']++;
        $puan_tablosu[$takim1]['atilan_gol'] += $gol1;
        $puan_tablosu[$takim1]['yenilen_gol'] += $gol2;
        $puan_tablosu[$takim2]['atilan_gol'] += $gol2;
        $puan_tablosu[$takim2]['yenilen_gol'] += $gol1;

        if ($gol1 > $gol2) {
            $puan_tablosu[$takim1]['kazanan']++;
            $puan_tablosu[$takim2]['kaybeden']++;
            $puan_tablosu[$takim1]['puan'] += 3;
        } elseif ($gol1 < $gol2) {
            $puan_tablosu[$takim2]['kazanan']++;
            $puan_tablosu[$takim1]['kaybeden']++;
            $puan_tablosu[$takim2]['puan'] += 3;
        } else {
            $puan_tablosu[$takim1]['berabere']++;
            $puan_tablosu[$takim2]['berabere']++;
            $puan_tablosu[$takim1]['puan'] += 1;
            $puan_tablosu[$takim2]['puan'] += 1;
        }
    }

    // Averaj hesaplama
    foreach ($puan_tablosu as $id => $takim) {
        $puan_tablosu[$id]['averaj'] = $takim['atilan_gol'] - $takim['yenilen_gol'];
    }

    // Puan tablosunu puan sıralamasına göre sıralama
    usort($puan_tablosu, function ($a, $b) {
        if ($a['puan'] == $b['puan']) {
            return $b['averaj'] - $a['averaj']; // Puan eşitliğinde averajı dikkate al
        }
        return $b['puan'] - $a['puan']; // Puan sıralaması
    });

    // Sıra numarasını tutacak değişken
    $sira = 1;

    // Puan tablosunu ekrana yazdırma
    echo "<h2>Puan Tablosu</h2>";
    echo "<form method='post'>";

    echo "</form>";
    echo "<table border='1'>
    <tr>
    <th>Sıra</th>
    <th>Takım</th>
    <th>Oynanan</th>
    <th>Kazanan</th>
    <th>Berabere</th>
    <th>Kaybeden</th>
    <th>Atılan Gol</th>
    <th>Yenilen Gol</th>
    <th>Averaj</th>
    <th>Puan</th>
    </tr>";
    foreach ($puan_tablosu as $takim) {
        echo "<tr>
        <td>{$sira}</td>
        <td style=text-align:left>{$takim['isim']}</td>
        <td>{$takim['oynanan']}</td>
        <td>{$takim['kazanan']}</td>
        <td>{$takim['berabere']}</td>
        <td>{$takim['kaybeden']}</td>
        <td>{$takim['atilan_gol']}</td>
        <td>{$takim['yenilen_gol']}</td>
        <td>{$takim['averaj']}</td>
        <td>{$takim['puan']}</td>
        </tr>";
        $sira++;
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
}

// Bağlantıyı kapat
$conn = null;
?>
<?php
try {
    // Veritabanına bağlantı
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <h2>Fikstür</h2>
    <style>
        table {
            border-collapse: collapse;
        }

        th,
        td {
            padding: 5px;
            text-align: center;
        }

        body {
            font-family: 'Montserrat';
            font-size: 16px;
        }
    </style>

    <form method="post" action="fixtures.php">
        <table border='1'>
            <tr>
                <th>Hafta</th>
                <th>Ev Sahibi</th>
                <th>Deplasman</th>
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
                    <td style="text-align:left"><?php echo $mac['takim1']; ?></td>
                    <td style="text-align:left"><?php echo $mac['takim2']; ?></td>
                    <td>
                        <?php echo $mac['takim1_gol']; ?> -
                        <?php echo $mac['takim2_gol']; ?>
                    </td>
                </tr>
            <?php
            }
            ?>
        </table>

    </form>





<?php

} catch (PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
}

// Bağlantıyı kapat
$conn = null;
?>