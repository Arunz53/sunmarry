-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 07:00 PM
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
-- Database: `marriage_profile_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `district` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `caste` varchar(100) NOT NULL,
  `subcaste` varchar(100) DEFAULT NULL,
  `marriage_type` enum('First','Second') NOT NULL,
  `education_type` varchar(100) DEFAULT NULL,
  `nakshatram` varchar(100) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `file_upload` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_time` varchar(10) DEFAULT NULL,
  `kulam` varchar(150) DEFAULT NULL,
  `rasi` varchar(100) DEFAULT NULL,
  `brothers_total` int(11) DEFAULT 0,
  `brothers_married` int(11) DEFAULT 0,
  `sisters_total` int(11) DEFAULT 0,
  `sisters_married` int(11) DEFAULT 0,
  `profession` varchar(150) DEFAULT NULL,
  `phone_primary` varchar(20) DEFAULT NULL,
  `phone_secondary` varchar(20) DEFAULT NULL,
  `phone_tertiary` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `name`, `age`, `gender`, `district`, `city`, `caste`, `subcaste`, `marriage_type`, `education_type`, `nakshatram`, `religion`, `profile_photo`, `file_upload`, `created_at`, `father_name`, `mother_name`, `birth_date`, `birth_time`, `kulam`, `rasi`, `brothers_total`, `brothers_married`, `sisters_total`, `sisters_married`, `profession`, `phone_primary`, `phone_secondary`, `phone_tertiary`) VALUES
(4, 'நித்யஸ்ரீ', 21, 'Female', 'Coimbatore', 'உடுமலை', 'செட்டியார்', 'நாட்டுக்கோட்டை செட்டியார்', 'Second', 'இளங்கலை', 'ஹஸ்தம்', 'இந்து', 'uploads/690a3cd188a94_1.jpg', 'uploads/690a3cd188d5d_1989-07-09T17_15_1259440_1_sr_ta.webp', '2025-11-04 17:50:09', 'குப்புசாமி', 'மாரியம்மா', '1995-12-16', '11:14 AM', 'வெண்டை', 'விருச்சிகம்', 0, 0, 1, 0, '', '9677317513', '', ''),
(6, 'அருண்', 29, 'Male', 'Coimbatore', 'பீளமேடு', 'செட்டியார்', 'கன்னட தேவாங்க செட்டியார்', 'Second', 'பொறியியல்', 'ஹஸ்தம்', 'இந்து', 'uploads/690a3e4aeda78_5.jpg', 'uploads/690a3e4aedc83_1997-10-03T17_15_1259440_1_sr_ta.webp', '2025-11-04 17:56:26', 'இளமதி', 'வாணி', '1995-12-16', '10:10 AM', 'மகரிஷி', 'கன்னி', 0, 1, 0, 0, 'கம்ப்யூட்டர்', '9677317517', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
