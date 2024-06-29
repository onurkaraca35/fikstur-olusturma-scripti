-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 30 Haz 2024, 00:37:45
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `futbol_ligi`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `maclar`
--

CREATE TABLE `maclar` (
  `id` int(11) NOT NULL,
  `hafta` int(11) NOT NULL,
  `takim1_id` int(11) DEFAULT NULL,
  `takim2_id` int(11) DEFAULT NULL,
  `takim1_gol` int(11) DEFAULT 0,
  `takim2_gol` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `maclar`
--

INSERT INTO `maclar` (`id`, `hafta`, `takim1_id`, `takim2_id`, `takim1_gol`, `takim2_gol`) VALUES
(1, 1, 6, 4, 1, 1),
(2, 1, 5, 2, 2, 1),
(3, 1, 3, 1, 3, 1),
(4, 2, 6, 1, 0, 0),
(5, 2, 3, 5, 1, 1),
(6, 2, 4, 2, 2, 3),
(7, 3, 4, 3, 4, 3),
(8, 3, 1, 5, 2, 1),
(9, 3, 6, 2, 0, 2),
(10, 4, 2, 1, 1, 2),
(11, 4, 6, 3, 2, 1),
(12, 4, 4, 5, 3, 3),
(13, 5, 2, 3, 1, 1),
(14, 5, 6, 5, 2, 1),
(15, 5, 4, 1, 3, 2);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `takimlar`
--

CREATE TABLE `takimlar` (
  `id` int(11) NOT NULL,
  `isim` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `takimlar`
--

INSERT INTO `takimlar` (`id`, `isim`) VALUES
(1, 'Portekiz'),
(2, 'Fransa'),
(3, 'Almanya'),
(4, 'İngiltere'),
(5, 'İspanya'),
(6, 'Hollanda');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `kadi` varchar(255) NOT NULL,
  `sifre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `user`
--

INSERT INTO `user` (`id`, `kadi`, `sifre`) VALUES
(1, 'admin', '123456.');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `maclar`
--
ALTER TABLE `maclar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `takim1_id` (`takim1_id`),
  ADD KEY `takim2_id` (`takim2_id`);

--
-- Tablo için indeksler `takimlar`
--
ALTER TABLE `takimlar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `maclar`
--
ALTER TABLE `maclar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `takimlar`
--
ALTER TABLE `takimlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `maclar`
--
ALTER TABLE `maclar`
  ADD CONSTRAINT `maclar_ibfk_1` FOREIGN KEY (`takim1_id`) REFERENCES `takimlar` (`id`),
  ADD CONSTRAINT `maclar_ibfk_2` FOREIGN KEY (`takim2_id`) REFERENCES `takimlar` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
