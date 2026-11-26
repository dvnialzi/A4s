-- A4S Footwear database (cleaned for GitHub)
-- Products, sizes, colour variants, coupons and settings are included.
-- Admin accounts, customers, addresses, orders, messages and refunds are left empty on purpose.
-- After importing, create your first admin with:  php tools/create_admin.php "username" "email" "password"

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2026 at 10:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbshoes`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminID` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

-- Sample data for `admin` removed: it held passwords and personal details.

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `msgID` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

-- Sample data for `contact_messages` removed: it held passwords and personal details.

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `coupon_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','flat','shipping') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `seasonal_theme` varchar(50) DEFAULT 'none',
  `is_active` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`coupon_id`, `code`, `discount_type`, `discount_value`, `seasonal_theme`, `is_active`) VALUES
(1, 'WINTER50', 'flat', 100.00, 'none', 0),
(2, 'WINTER100', 'flat', 200.00, 'winter', 1),
(3, 'RAYA10', 'flat', 50.00, 'none', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `custID` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `addresscust` text NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expire` datetime DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `towncity` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

-- Sample data for `customer` removed: it held passwords and personal details.

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `address_id` int(11) NOT NULL,
  `custID` int(11) NOT NULL,
  `address_label` varchar(50) DEFAULT 'Home',
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `towncity` varchar(100) NOT NULL,
  `zipcode` varchar(20) NOT NULL,
  `is_default` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_addresses`
--

-- Sample data for `customer_addresses` removed: it held passwords and personal details.

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderID` int(11) NOT NULL,
  `custID` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT 'Credit Card',
  `shipping_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

-- Sample data for `orders` removed: it held passwords and personal details.

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `itemID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `prodID` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `shoe_size` varchar(50) DEFAULT 'N/A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

-- Sample data for `order_items` removed: it held passwords and personal details.

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `prodID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 10,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) NOT NULL,
  `image_alt` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `is_active` int(11) NOT NULL DEFAULT 1,
  `search_tags` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`prodID`, `name`, `price`, `stock`, `stock_quantity`, `image`, `image_alt`, `category`, `description`, `is_active`, `search_tags`) VALUES
(57, 'MARJINAL x SNIIKA', 279.00, 80, 0, 'images/item_1782140519_122.jpeg', 'images/item_1782140519_285.jpeg', 'Men', 'Kasut ini bukan sekadar sneakers. Ia lahir dari semangat jalanan Jakarta dan Kuala Lumpur — gabungan dua kota yang penuh suara anak muda, penuh marah, penuh cinta. Dari budak punk, dari budak jalanan, lahirlah kasut ini sebagai simbol Do It Yourself. Maknanya: berdiri atas kaki sendiri, tanpa tunggu siapa-siapa tolong. Kita bina jalan kita sendiri.\r\nKasut ini juga dicipta untuk mereka yang “mati”. Mati bukan sebab tiada nyawa, tapi sebab tiada siapa peduli. Kita hanyalah kita. Kita sendiri tentukan masa depan tanpa bantuan sesiapa. Dan walau dunia pandang sepi, kita tetap berdiri, kita tetap melangkah.\r\nDetail Kasut\r\n•Material: PU Leather Synthetic Premium Grade, dengan sentuhan tekstur lebih kukuh & tahan.\r\n•Design: Black on black – warna hitam penuh misteri, dengan corak batik dan sentuhan Marjinal yang menyatu secara halus, menjadikan kasut ini premium gila.\r\n•Front Buckle: Bertulis MARJINAL pada bahagian tali depan.\r\n•Back Heel: Dihadirkan dengan getah belakang yang juga bertulis MARJINAL.\r\n•Tongue Label: Eksklusif dengan Sniika X Marjinal.\r\n•Inner Lining: Tulisan MARJINAL berulang-ulang di bahagian dalam, seolah suara jalanan yang tidak pernah padam.\r\n•Extra Lace: Lace tambahan warna merah untuk tukar gaya bila bosan dengan hitam.\r\n•Bonus: Set sticker Marjinal + thank you card.\r\nPackaging Eksklusif\r\nSetiap pasangan datang dengan kotak keras bermagnet, full black on black design — hitam dengan ukiran hitam, simple tapi mewah. Box ini bukan sekadar pembungkus, tapi collector’s item.\r\n⸻\r\nKasut ini bukan hanya untuk dipakai. Ia adalah manifestasi jiwa bebas, suara jalanan, dan semangat punk yang takkan pernah mati.', 1, ''),
(58, 'SNIIKA MERDEKA 68', 168.00, 80, 0, 'images/item_1782140643_782.jpeg', 'images/item_1782140643_833.jpeg', 'Men', 'Ini adalah edisi Merdeka ke 6 sniika keluarkan. Edisi pertama dikeluarkan pada Hari Merdeka yang ke 63 tahun. Kenapa perlu keluarkan design baru sempena Merdeka? Sebab rasa cinta pada tanah air perlu diraikan dengan edisi terbaru. Ia adalah hari bertuah, hari bersejarah!\r\n\r\nDesign menggunakan elemen Songket Melayu\r\nWarna coklat melambangkan tanah air\r\nSulaman 68 tahun berwarna gold\r\nMaterial Suede & leather synthetic premium.\r\nMerdeka kali ini sangat special, biasanya material ni kami letak RM269. Tapi kali ni sempena merdeka ke 68 sniika bagi RM168 sahaja!', 1, ''),
(59, 'SNIIKA x ACAB', 269.00, 80, 0, 'images/item_1782140691_694.jpeg', 'images/item_1782140691_981.jpeg', 'Men', 'Di Malaysia, budaya skinhead selalu di pandang serong.\r\n\r\nMereka cakap “budaya ni g4n4s, h4r4m, samseng, r4c1st…”\r\n\r\nPadahal bagi yang faham, skinhead adalah simbol working class pride, unity & rebellion.\r\n\r\nKatanya budaya ni sesat, ganas, tak patut ada. Tapi mereka lupa, pengalaman hidup di jalananlah yang ajar mereka erti setia. Mereka tak puja trend, mereka berpegang pada prinsip.\r\n\r\nKalau kau faham, kasut ni kau HARUS ada!\r\n\r\n \r\n\r\nKolaborasi khas bersama A.C.A.B\r\n\r\nMenggunakan material full suede\r\nLogo belakang 1.3.1.2 (ACAB) sulam\r\nPrinted padi skinhead di sebelah sisi\r\nFull print padi skinhead di dalam insole\r\nLabel sniika X acab di bahagian lidah\r\nFree set sticker ACAB\r\nThanks card dengan lirik BANGUN\r\nKeluaran kolaborasi ini sangat limited & x akan restock.\r\n\r\n25 pembeli terawal akan dpt kopi PREMIUM ACAB bernilai RM35.\r\n', 1, ''),
(60, 'SNIIKA x ARIF RAFHAN', 229.00, 80, 0, 'images/item_1782140768_461.jpeg', 'images/item_1782140768_980.jpeg', 'Women', 'Kasut berkonsep kan batik pertama di dunia. Hasil berkolaborasi bersama Arif Rafhan iaitu pelukis batik modern terkenal. Beliau pernah melukis batik Venom Marvel dan lukisan itu disiarkan oleh pihak Marvel sendiri di Bawagan. Nama beliau juga tercatat di Malaysian Book of Record kerana melukis batik mural terpanjang di Malaysia.\r\n\r\nKoleksi kali ini bertemakan Batik Modern. Terdapat juga lukisan gajah dan wayang kulit Melayu. Material yang digunakan adalah canvas dan juga suede bagi menampakkan lagi desain klasik dan lebih elegan.\r\n\r\nKeluaran kasut ini adalah sangat terhad. Kalau habis, tidak akan di-restock kembali. Sila dapatkan segera sebelum habis. Hargailah tradisi batik Melayu. Jangan biarkan dia hilang begitu saja.', 1, ''),
(61, 'SNIIKA x SOG', 259.00, 80, 0, 'images/item_1782140847_169.jpeg', 'images/item_1782140847_278.jpeg', 'Women', 'Kasut edisi kolaborasi Sniika bersama kumpulan SOG ini menggunakan material berkualiti tinggi iaitu canvas dan baldu. Bahagian atas direka dengan jahitan zigzag berwarna emas, memberikan sentuhan premium yang menonjol.\r\n\r\n \r\n\r\nDi sisi luar kanan, tertera cetakan album Bahtera – album ikonik SOG yang diterbitkan pada tahun 2014 dan menerima beberapa cetakan semula atas permintaan tinggi. Pada bahagian belakang terdapat sulaman “ESOJI” bersama logo rasmi SOG di bahagian atas tumit. Sisi kasut turut menampilkan logo “S” serta label Sniika dengan rekaan minimalis namun berkarakter.\r\n\r\n \r\n\r\nInsole bercorak ombak, diilhamkan daripada tema album Bahtera. Setiap pasang didatangkan dengan kotak eksklusif berwarna hitam dan emas yang memaparkan visual album dan elemen ombak, lengkap bersama selimut kasut khas, set stiker edisi terhad, serta kad autentik rasmi. Koleksi ini dihasilkan dalam jumlah terhad dan tidak akan dikeluarkan semula.', 1, ''),
(62, 'SNIIKA x KELUANG MAN', 129.00, 80, -4, 'images/item_1782140909_164.jpeg', 'images/item_1782140909_684.jpeg', 'Men', 'Satu penghormatan buat hero lagenda Malaysia! Kasut edisi kolaborasi Sniika x Keluang Man ini direka khas dengan material mesh & PU leather — breathable dan tahan lasak untuk keselesaan harian.\r\nApa yang kau akan dapat?\r\n•Kotak Eksklusif Keluang Man\r\n•Kasut red yang gempaks\r\n•Extra lace\r\n•Selimut kasut edisi Keluang Man\r\n•Set stiker Keluang Man\r\n•Kad autentik rasmi\r\nUntuk pembeli terawal (50-60 orang pertama): akan turut menerima Majalah Komik Keluang Man (Percuma!)\r\nReady stock, tapi penghantaran mungkin sedikit lewat atas sebab permintaan order terlalu banyak.\r\nCuma 2,000 pasang sahaja untuk koleksi ini. Tiada restock.\r\nKami sarankan beli dua pasang – satu untuk pakai, satu lagi untuk simpan koleksi.', 1, ''),
(63, 'SNIIKA x NARATU', 269.00, 80, -4, 'images/item_1782140973_476.jpeg', 'images/item_1782140973_381.jpeg', 'Women', 'Naratu, band hardcore metal legenda Malaysia yang diasaskan pada tahun 1998 di Kuala Lumpur, dikenali dengan muzik berat dan lirik penuh makna—ditulis sepenuhnya dalam Bahasa Melayu. Siapa yang tak kenal dengan lagu-lagu mereka yang membakar semangat? Sekarang, Sniika dan Naratu bergabung dalam satu kolaborasi epik, membawa jiwa hardcore terus ke atas kasut!\r\n\r\n \r\n\r\nRekaan Khas Sempena Raya\r\n\r\nEdisi khas ini direka untuk peminat setia muzik berat & streetwear tempatan. Kasut ini menggunakan material kanvas dan suede untuk gabungan keselesaan dan ketahanan, sesuai dipakai ke gig, open house, atau sesi lepak santai.', 1, ''),
(64, 'SNIIKA x ATAS ANGIN', 249.00, 80, 0, 'images/item_1782141036_612.jpeg', 'images/item_1782141036_244.jpeg', 'Men', 'Kolaborasi sniika yang ke 13 sudah dilancarkan. Design kali ini menggunakan 2 material yang berbeza di bahagian upper iaitu suede & kanvas. Untuk menambahkan lagi kontra, kami mengenakan jahitan putih. Label getah dibelakang menggunakan printing 3D tulisan atas angin.\r\n\r\nKasut ini terbaru 600 pasang worldwide. Untuk pembeli awal akan dapat kad autentik & extra lace 2 pasang kuning & hitam.', 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `size_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_name` varchar(50) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`size_id`, `product_id`, `size_name`, `stock_quantity`) VALUES
(33, 62, 'UK 5', 10),
(34, 62, 'UK 6', 10),
(35, 62, 'UK 7', 10),
(36, 62, 'UK 8', 10),
(37, 62, 'UK 9', 10),
(38, 62, 'UK 10', 8),
(39, 62, 'UK 11', 9),
(40, 62, 'UK 12', 9),
(41, 61, 'UK 5', 10),
(42, 61, 'UK 6', 10),
(43, 61, 'UK 7', 10),
(44, 61, 'UK 8', 10),
(45, 61, 'UK 9', 10),
(46, 61, 'UK 10', 10),
(47, 61, 'UK 11', 10),
(48, 61, 'UK 12', 10),
(49, 60, 'UK 5', 10),
(50, 60, 'UK 6', 10),
(51, 60, 'UK 7', 10),
(52, 60, 'UK 8', 10),
(53, 60, 'UK 9', 10),
(54, 60, 'UK 10', 10),
(55, 60, 'UK 11', 10),
(56, 60, 'UK 12', 10),
(57, 59, 'UK 5', 10),
(58, 59, 'UK 6', 10),
(59, 59, 'UK 7', 10),
(60, 59, 'UK 8', 10),
(61, 59, 'UK 9', 10),
(62, 59, 'UK 10', 10),
(63, 59, 'UK 11', 10),
(64, 59, 'UK 12', 10),
(65, 58, 'UK 5', 10),
(66, 58, 'UK 6', 10),
(67, 58, 'UK 7', 10),
(68, 58, 'UK 8', 10),
(69, 58, 'UK 9', 10),
(70, 58, 'UK 10', 10),
(71, 58, 'UK 11', 10),
(72, 58, 'UK 12', 10),
(73, 57, 'UK 5', 10),
(74, 57, 'UK 6', 10),
(75, 57, 'UK 7', 10),
(76, 57, 'UK 8', 10),
(77, 57, 'UK 9', 10),
(78, 57, 'UK 10', 10),
(79, 57, 'UK 11', 10),
(80, 57, 'UK 12', 10),
(105, 63, 'UK 5', 10),
(106, 63, 'UK 6', 10),
(107, 63, 'UK 7', 10),
(108, 63, 'UK 8', 10),
(109, 63, 'UK 9', 10),
(110, 63, 'UK 10', 10),
(111, 63, 'UK 11', 10),
(112, 63, 'UK 12', 10),
(186, 64, 'UK 5', 10),
(187, 64, 'UK 6', 10),
(188, 64, 'UK 7', 10),
(189, 64, 'UK 8', 10),
(190, 64, 'UK 9', 10),
(191, 64, 'UK 10', 10),
(192, 64, 'UK 11', 10),
(193, 64, 'UK 12', 10);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `variant_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color_name` varchar(50) NOT NULL,
  `img_side` varchar(255) NOT NULL,
  `img_top` varchar(255) NOT NULL,
  `img_thumb` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`variant_id`, `product_id`, `color_name`, `img_side`, `img_top`, `img_thumb`) VALUES
(3, 45, 'black', 'images/variants/side_1781540464_593.webp', 'images/variants/top_1781540464_724.webp', 'images/variants/thumb_1781540464_940.webp'),
(4, 43, 'aurora green', 'images/variants/side_1781540669_936.jpg', 'images/variants/top_1781540669_611.webp', 'images/variants/thumb_1781540669_417.webp'),
(5, 43, 'Sweet Pea', 'images/variants/side_1781540855_471.webp', 'images/variants/top_1781540855_286.webp', 'images/variants/thumb_1781540855_806.webp'),
(6, 41, 'pink blossom', 'images/variants/side_1781541131_114.webp', 'images/variants/top_1781541131_137.webp', 'images/variants/thumb_1781541131_187.webp'),
(7, 37, 'camo', 'images/variants/side_1781541936_222.webp', 'images/variants/top_1781541936_440.webp', 'images/variants/thumb_1781541936_198.webp'),
(8, 56, 'matcha', 'images/variants/side_1781764514_218.webp', 'images/variants/top_1781764514_385.webp', 'images/variants/thumb_1781764514_929.webp');

-- --------------------------------------------------------

--
-- Table structure for table `refund_requests`
--

CREATE TABLE `refund_requests` (
  `refund_id` int(11) NOT NULL,
  `custID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `reason` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `evidence_image` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refund_requests`
--

-- Sample data for `refund_requests` removed: it held passwords and personal details.

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `cart_id` int(11) NOT NULL,
  `custID` int(11) NOT NULL,
  `prodID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shoe_size` varchar(50) DEFAULT 'N/A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_settings`
--

CREATE TABLE `store_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_settings`
--

INSERT INTO `store_settings` (`setting_key`, `setting_value`) VALUES
('active_theme', 'none');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminID`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`msgID`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`custID`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`address_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderID`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`itemID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`prodID`);

--
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`size_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`variant_id`);

--
-- Indexes for table `refund_requests`
--
ALTER TABLE `refund_requests`
  ADD PRIMARY KEY (`refund_id`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `custID` (`custID`),
  ADD KEY `prodID` (`prodID`);

--
-- Indexes for table `store_settings`
--
ALTER TABLE `store_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `msgID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `custID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `orderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `itemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `prodID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `size_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `refund_requests`
--
ALTER TABLE `refund_requests`
  MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`custID`) REFERENCES `customer` (`custID`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`prodID`) REFERENCES `product` (`prodID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
