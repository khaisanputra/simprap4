-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 22, 2026 at 02:15 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simprap4`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'izin/dispensasi/sakit/alpha',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_user_id` bigint UNSIGNED DEFAULT NULL,
  `student_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL COMMENT 'attendance id jika ada',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `type`, `title`, `message`, `student_user_id`, `student_name`, `tanggal`, `reference_id`, `read_at`, `created_at`, `updated_at`) VALUES
(263, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 23 September 2026.', 2, 'Khaisan Raihan Saputra', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(264, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 23 September 2026.', 3, 'Fagus Ridwan', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(265, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 23 September 2026.', 4, 'Nauval Vedra Pratama', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(266, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 23 September 2026.', 5, 'Fadli Rizky Saputra', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(267, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 23 September 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(268, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 23 September 2026.', 7, 'Riska Sahwalani', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(269, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 23 September 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(270, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 23 September 2026.', 9, 'Marvel Rasya Pramudya', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(271, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 23 September 2026.', 10, 'Hanung Aprilio Gunawan', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(272, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 23 September 2026.', 11, 'Almira Kusumaningrum', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(273, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 23 September 2026.', 12, 'Nadina Ainur Pratiwi', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(274, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 23 September 2026.', 13, 'Muhammad Khoiruzzadi', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(275, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 23 September 2026.', 14, 'Putri Setyaningsih', '2026-09-23', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(276, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 22 September 2026.', 2, 'Khaisan Raihan Saputra', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(277, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 22 September 2026.', 3, 'Fagus Ridwan', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(278, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 22 September 2026.', 4, 'Nauval Vedra Pratama', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(279, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 22 September 2026.', 5, 'Fadli Rizky Saputra', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(280, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 22 September 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(281, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 22 September 2026.', 7, 'Riska Sahwalani', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(282, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 22 September 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(283, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 22 September 2026.', 9, 'Marvel Rasya Pramudya', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(284, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 22 September 2026.', 10, 'Hanung Aprilio Gunawan', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(285, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 22 September 2026.', 11, 'Almira Kusumaningrum', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(286, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 22 September 2026.', 12, 'Nadina Ainur Pratiwi', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(287, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 22 September 2026.', 13, 'Muhammad Khoiruzzadi', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(288, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 22 September 2026.', 14, 'Putri Setyaningsih', '2026-09-22', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(289, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 21 September 2026.', 2, 'Khaisan Raihan Saputra', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:33', '2026-09-24 05:35:42'),
(290, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 21 September 2026.', 3, 'Fagus Ridwan', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(291, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 21 September 2026.', 4, 'Nauval Vedra Pratama', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(292, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 21 September 2026.', 5, 'Fadli Rizky Saputra', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(293, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 21 September 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(294, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 21 September 2026.', 7, 'Riska Sahwalani', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(295, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 21 September 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(296, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 21 September 2026.', 9, 'Marvel Rasya Pramudya', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(297, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 21 September 2026.', 10, 'Hanung Aprilio Gunawan', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(298, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 21 September 2026.', 11, 'Almira Kusumaningrum', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(299, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 21 September 2026.', 12, 'Nadina Ainur Pratiwi', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(300, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 21 September 2026.', 13, 'Muhammad Khoiruzzadi', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(301, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 21 September 2026.', 14, 'Putri Setyaningsih', '2026-09-21', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(302, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 18 September 2026.', 2, 'Khaisan Raihan Saputra', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(303, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 18 September 2026.', 3, 'Fagus Ridwan', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(304, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 18 September 2026.', 4, 'Nauval Vedra Pratama', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(305, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 18 September 2026.', 5, 'Fadli Rizky Saputra', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(306, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 18 September 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(307, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 18 September 2026.', 7, 'Riska Sahwalani', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(308, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 18 September 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(309, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 18 September 2026.', 9, 'Marvel Rasya Pramudya', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(310, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 18 September 2026.', 10, 'Hanung Aprilio Gunawan', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(311, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 18 September 2026.', 11, 'Almira Kusumaningrum', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(312, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 18 September 2026.', 12, 'Nadina Ainur Pratiwi', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(313, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 18 September 2026.', 13, 'Muhammad Khoiruzzadi', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(314, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 18 September 2026.', 14, 'Putri Setyaningsih', '2026-09-18', NULL, '2026-09-24 05:35:42', '2026-09-24 05:35:34', '2026-09-24 05:35:42'),
(315, 'izin', 'Muhammad Khoiruzzadi — Izin', 'Muhammad Khoiruzzadi mengajukan izin hari ini.', 13, 'Muhammad Khoiruzzadi', '2026-09-24', 17, '2026-09-24 07:24:43', '2026-09-24 07:24:21', '2026-09-24 07:24:43'),
(316, 'dispensasi', 'Nevril Nur Irwan Khoiriansah — Dispensasi', 'Nevril Nur Irwan Khoiriansah mengajukan dispensasi hari ini.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-09-24', 18, '2026-09-24 09:20:13', '2026-09-24 07:26:25', '2026-09-24 09:20:13'),
(317, 'sakit', 'Marvel Rasya Pramudya — Sakit', 'Marvel Rasya Pramudya mengajukan izin sakit hari ini.', 9, 'Marvel Rasya Pramudya', '2026-09-24', 19, '2026-09-24 09:19:49', '2026-09-24 07:28:44', '2026-09-24 09:19:49'),
(318, 'izin', 'Hanung Aprilio Gunawan — Izin', 'Hanung Aprilio Gunawan mengajukan izin hari ini.', 10, 'Hanung Aprilio Gunawan', '2026-09-24', 20, '2026-09-25 02:56:26', '2026-09-24 07:29:22', '2026-09-25 02:56:26'),
(319, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 24 September 2026.', 2, 'Khaisan Raihan Saputra', '2026-09-24', NULL, '2026-09-25 02:56:26', '2026-09-25 02:34:49', '2026-09-25 02:56:26'),
(320, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 24 September 2026.', 3, 'Fagus Ridwan', '2026-09-24', NULL, '2026-09-25 02:56:26', '2026-09-25 02:34:49', '2026-09-25 02:56:26'),
(321, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 24 September 2026.', 4, 'Nauval Vedra Pratama', '2026-09-24', NULL, '2026-09-25 02:56:26', '2026-09-25 02:34:49', '2026-09-25 02:56:26'),
(322, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 24 September 2026.', 5, 'Fadli Rizky Saputra', '2026-09-24', NULL, '2026-09-25 02:56:22', '2026-09-25 02:34:49', '2026-09-25 02:56:22'),
(323, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 24 September 2026.', 7, 'Riska Sahwalani', '2026-09-24', NULL, '2026-09-25 02:56:26', '2026-09-25 02:34:49', '2026-09-25 02:56:26'),
(324, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 24 September 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-09-24', NULL, '2026-09-25 02:56:26', '2026-09-25 02:34:49', '2026-09-25 02:56:26'),
(325, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 24 September 2026.', 11, 'Almira Kusumaningrum', '2026-09-24', NULL, '2026-09-25 02:56:26', '2026-09-25 02:34:49', '2026-09-25 02:56:26'),
(326, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 24 September 2026.', 12, 'Nadina Ainur Pratiwi', '2026-09-24', NULL, '2026-09-25 02:56:26', '2026-09-25 02:34:49', '2026-09-25 02:56:26'),
(327, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 24 September 2026.', 14, 'Putri Setyaningsih', '2026-09-24', NULL, '2026-09-25 02:56:26', '2026-09-25 02:34:49', '2026-09-25 02:56:26'),
(334, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 25 September 2026.', 2, 'Khaisan Raihan Saputra', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(335, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 25 September 2026.', 3, 'Fagus Ridwan', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(336, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 25 September 2026.', 4, 'Nauval Vedra Pratama', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(337, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 25 September 2026.', 5, 'Fadli Rizky Saputra', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(338, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 25 September 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(339, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 25 September 2026.', 7, 'Riska Sahwalani', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(340, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 25 September 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(341, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 25 September 2026.', 9, 'Marvel Rasya Pramudya', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(342, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 25 September 2026.', 10, 'Hanung Aprilio Gunawan', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(343, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 25 September 2026.', 11, 'Almira Kusumaningrum', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(344, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 25 September 2026.', 12, 'Nadina Ainur Pratiwi', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(345, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 25 September 2026.', 13, 'Muhammad Khoiruzzadi', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(346, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 25 September 2026.', 14, 'Putri Setyaningsih', '2026-09-25', NULL, '2026-09-28 05:49:20', '2026-09-28 02:49:48', '2026-09-28 05:49:20'),
(347, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 29 September 2026.', 2, 'Khaisan Raihan Saputra', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(348, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 29 September 2026.', 3, 'Fagus Ridwan', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(349, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 29 September 2026.', 4, 'Nauval Vedra Pratama', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(350, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 29 September 2026.', 5, 'Fadli Rizky Saputra', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(351, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 29 September 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(352, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 29 September 2026.', 7, 'Riska Sahwalani', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(353, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 29 September 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(354, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 29 September 2026.', 9, 'Marvel Rasya Pramudya', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(355, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 29 September 2026.', 10, 'Hanung Aprilio Gunawan', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(356, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 29 September 2026.', 11, 'Almira Kusumaningrum', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(357, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 29 September 2026.', 12, 'Nadina Ainur Pratiwi', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(358, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 29 September 2026.', 13, 'Muhammad Khoiruzzadi', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(359, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 29 September 2026.', 14, 'Putri Setyaningsih', '2026-09-29', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(360, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 28 September 2026.', 2, 'Khaisan Raihan Saputra', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(361, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 28 September 2026.', 3, 'Fagus Ridwan', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(362, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 28 September 2026.', 4, 'Nauval Vedra Pratama', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(363, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 28 September 2026.', 5, 'Fadli Rizky Saputra', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(364, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 28 September 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(365, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 28 September 2026.', 7, 'Riska Sahwalani', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(366, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 28 September 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(367, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 28 September 2026.', 9, 'Marvel Rasya Pramudya', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(368, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 28 September 2026.', 10, 'Hanung Aprilio Gunawan', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(369, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 28 September 2026.', 11, 'Almira Kusumaningrum', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(370, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 28 September 2026.', 12, 'Nadina Ainur Pratiwi', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(371, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 28 September 2026.', 13, 'Muhammad Khoiruzzadi', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(372, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 28 September 2026.', 14, 'Putri Setyaningsih', '2026-09-28', NULL, '2026-10-16 02:41:39', '2026-09-30 03:48:09', '2026-10-16 02:41:39'),
(373, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 15 Oktober 2026.', 2, 'Khaisan Raihan Saputra', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:14', '2026-10-16 02:41:39'),
(374, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 15 Oktober 2026.', 3, 'Fagus Ridwan', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:14', '2026-10-16 02:41:39'),
(375, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 15 Oktober 2026.', 4, 'Nauval Vedra Pratama', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:14', '2026-10-16 02:41:39'),
(376, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 15 Oktober 2026.', 5, 'Fadli Rizky Saputra', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:14', '2026-10-16 02:41:39'),
(377, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 15 Oktober 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(378, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 15 Oktober 2026.', 7, 'Riska Sahwalani', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(379, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 15 Oktober 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(380, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 15 Oktober 2026.', 9, 'Marvel Rasya Pramudya', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(381, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 15 Oktober 2026.', 10, 'Hanung Aprilio Gunawan', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(382, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 15 Oktober 2026.', 11, 'Almira Kusumaningrum', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(383, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 15 Oktober 2026.', 12, 'Nadina Ainur Pratiwi', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(384, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 15 Oktober 2026.', 13, 'Muhammad Khoiruzzadi', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(385, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 15 Oktober 2026.', 14, 'Putri Setyaningsih', '2026-10-15', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(386, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 14 Oktober 2026.', 2, 'Khaisan Raihan Saputra', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(387, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 14 Oktober 2026.', 3, 'Fagus Ridwan', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(388, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 14 Oktober 2026.', 4, 'Nauval Vedra Pratama', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(389, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 14 Oktober 2026.', 5, 'Fadli Rizky Saputra', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(390, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 14 Oktober 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(391, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 14 Oktober 2026.', 7, 'Riska Sahwalani', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(392, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 14 Oktober 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(393, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 14 Oktober 2026.', 9, 'Marvel Rasya Pramudya', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(394, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 14 Oktober 2026.', 10, 'Hanung Aprilio Gunawan', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(395, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 14 Oktober 2026.', 11, 'Almira Kusumaningrum', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(396, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 14 Oktober 2026.', 12, 'Nadina Ainur Pratiwi', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(397, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 14 Oktober 2026.', 13, 'Muhammad Khoiruzzadi', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(398, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 14 Oktober 2026.', 14, 'Putri Setyaningsih', '2026-10-14', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(399, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 13 Oktober 2026.', 2, 'Khaisan Raihan Saputra', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(400, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 13 Oktober 2026.', 3, 'Fagus Ridwan', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(401, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 13 Oktober 2026.', 4, 'Nauval Vedra Pratama', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(402, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 13 Oktober 2026.', 5, 'Fadli Rizky Saputra', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(403, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 13 Oktober 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(404, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 13 Oktober 2026.', 7, 'Riska Sahwalani', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(405, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 13 Oktober 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(406, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 13 Oktober 2026.', 9, 'Marvel Rasya Pramudya', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(407, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 13 Oktober 2026.', 10, 'Hanung Aprilio Gunawan', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(408, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 13 Oktober 2026.', 11, 'Almira Kusumaningrum', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(409, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 13 Oktober 2026.', 12, 'Nadina Ainur Pratiwi', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(410, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 13 Oktober 2026.', 13, 'Muhammad Khoiruzzadi', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(411, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 13 Oktober 2026.', 14, 'Putri Setyaningsih', '2026-10-13', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(412, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 12 Oktober 2026.', 2, 'Khaisan Raihan Saputra', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(413, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 12 Oktober 2026.', 3, 'Fagus Ridwan', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(414, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 12 Oktober 2026.', 4, 'Nauval Vedra Pratama', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(415, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 12 Oktober 2026.', 5, 'Fadli Rizky Saputra', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(416, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 12 Oktober 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(417, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 12 Oktober 2026.', 7, 'Riska Sahwalani', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(418, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 12 Oktober 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(419, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 12 Oktober 2026.', 9, 'Marvel Rasya Pramudya', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(420, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 12 Oktober 2026.', 10, 'Hanung Aprilio Gunawan', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(421, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 12 Oktober 2026.', 11, 'Almira Kusumaningrum', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(422, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 12 Oktober 2026.', 12, 'Nadina Ainur Pratiwi', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(423, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 12 Oktober 2026.', 13, 'Muhammad Khoiruzzadi', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(424, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 12 Oktober 2026.', 14, 'Putri Setyaningsih', '2026-10-12', NULL, '2026-10-16 02:41:39', '2026-10-16 02:41:15', '2026-10-16 02:41:39'),
(425, 'alpha', 'Shima Yuniar Alzara — Alpha', 'Shima Yuniar Alzara tercatat Alpha pada 15 Oktober 2026.', 15, 'Shima Yuniar Alzara', '2026-10-15', NULL, '2026-10-19 08:04:09', '2026-10-16 09:22:53', '2026-10-19 08:04:09'),
(426, 'alpha', 'Shima Yuniar Alzara — Alpha', 'Shima Yuniar Alzara tercatat Alpha pada 14 Oktober 2026.', 15, 'Shima Yuniar Alzara', '2026-10-14', NULL, '2026-10-19 08:04:09', '2026-10-16 09:22:53', '2026-10-19 08:04:09'),
(427, 'alpha', 'Shima Yuniar Alzara — Alpha', 'Shima Yuniar Alzara tercatat Alpha pada 13 Oktober 2026.', 15, 'Shima Yuniar Alzara', '2026-10-13', NULL, '2026-10-19 08:04:09', '2026-10-16 09:22:53', '2026-10-19 08:04:09'),
(428, 'alpha', 'Shima Yuniar Alzara — Alpha', 'Shima Yuniar Alzara tercatat Alpha pada 12 Oktober 2026.', 15, 'Shima Yuniar Alzara', '2026-10-12', NULL, '2026-10-19 08:04:09', '2026-10-16 09:22:53', '2026-10-19 08:04:09'),
(429, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 16 Oktober 2026.', 2, 'Khaisan Raihan Saputra', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(430, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 16 Oktober 2026.', 3, 'Fagus Ridwan', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(431, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 16 Oktober 2026.', 4, 'Nauval Vedra Pratama', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(432, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 16 Oktober 2026.', 5, 'Fadli Rizky Saputra', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(433, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 16 Oktober 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(434, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 16 Oktober 2026.', 7, 'Riska Sahwalani', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(435, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 16 Oktober 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(436, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 16 Oktober 2026.', 9, 'Marvel Rasya Pramudya', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(437, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 16 Oktober 2026.', 10, 'Hanung Aprilio Gunawan', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(438, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 16 Oktober 2026.', 11, 'Almira Kusumaningrum', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(439, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 16 Oktober 2026.', 12, 'Nadina Ainur Pratiwi', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(440, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 16 Oktober 2026.', 13, 'Muhammad Khoiruzzadi', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(441, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 16 Oktober 2026.', 14, 'Putri Setyaningsih', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(442, 'alpha', 'Shima Yuniar Alzara — Alpha', 'Shima Yuniar Alzara tercatat Alpha pada 16 Oktober 2026.', 15, 'Shima Yuniar Alzara', '2026-10-16', NULL, '2026-10-19 08:04:09', '2026-10-19 02:25:33', '2026-10-19 08:04:09'),
(443, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 19 Oktober 2026.', 3, 'Fagus Ridwan', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(444, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 19 Oktober 2026.', 4, 'Nauval Vedra Pratama', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(445, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 19 Oktober 2026.', 5, 'Fadli Rizky Saputra', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(446, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 19 Oktober 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(447, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 19 Oktober 2026.', 7, 'Riska Sahwalani', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(448, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 19 Oktober 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(449, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 19 Oktober 2026.', 9, 'Marvel Rasya Pramudya', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(450, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 19 Oktober 2026.', 10, 'Hanung Aprilio Gunawan', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(451, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 19 Oktober 2026.', 11, 'Almira Kusumaningrum', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(452, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 19 Oktober 2026.', 12, 'Nadina Ainur Pratiwi', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(453, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 19 Oktober 2026.', 13, 'Muhammad Khoiruzzadi', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(454, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 19 Oktober 2026.', 14, 'Putri Setyaningsih', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(455, 'alpha', 'Shima Yuniar Alzara — Alpha', 'Shima Yuniar Alzara tercatat Alpha pada 19 Oktober 2026.', 15, 'Shima Yuniar Alzara', '2026-10-19', NULL, NULL, '2026-10-20 02:39:00', '2026-10-20 02:39:00'),
(456, 'alpha', 'testing — Alpha', 'testing tercatat Alpha pada 19 Oktober 2026.', 16, 'testing', '2026-10-19', NULL, NULL, '2026-10-20 04:41:00', '2026-10-20 04:41:00'),
(457, 'alpha', 'testing — Alpha', 'testing tercatat Alpha pada 16 Oktober 2026.', 16, 'testing', '2026-10-16', NULL, NULL, '2026-10-20 04:41:00', '2026-10-20 04:41:00'),
(458, 'alpha', 'testing — Alpha', 'testing tercatat Alpha pada 15 Oktober 2026.', 16, 'testing', '2026-10-15', NULL, NULL, '2026-10-20 04:41:00', '2026-10-20 04:41:00'),
(459, 'alpha', 'testing — Alpha', 'testing tercatat Alpha pada 14 Oktober 2026.', 16, 'testing', '2026-10-14', NULL, NULL, '2026-10-20 04:41:00', '2026-10-20 04:41:00'),
(460, 'alpha', 'Khaisan Raihan Saputra — Alpha', 'Khaisan Raihan Saputra tercatat Alpha pada 20 Oktober 2026.', 2, 'Khaisan Raihan Saputra', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(461, 'alpha', 'Fagus Ridwan — Alpha', 'Fagus Ridwan tercatat Alpha pada 20 Oktober 2026.', 3, 'Fagus Ridwan', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(462, 'alpha', 'Nauval Vedra Pratama — Alpha', 'Nauval Vedra Pratama tercatat Alpha pada 20 Oktober 2026.', 4, 'Nauval Vedra Pratama', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(463, 'alpha', 'Fadli Rizky Saputra — Alpha', 'Fadli Rizky Saputra tercatat Alpha pada 20 Oktober 2026.', 5, 'Fadli Rizky Saputra', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(464, 'alpha', 'Nevril Nur Irwan Khoiriansah — Alpha', 'Nevril Nur Irwan Khoiriansah tercatat Alpha pada 20 Oktober 2026.', 6, 'Nevril Nur Irwan Khoiriansah', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(465, 'alpha', 'Riska Sahwalani — Alpha', 'Riska Sahwalani tercatat Alpha pada 20 Oktober 2026.', 7, 'Riska Sahwalani', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(466, 'alpha', 'Rifqi Fahruzian Anurul Huda — Alpha', 'Rifqi Fahruzian Anurul Huda tercatat Alpha pada 20 Oktober 2026.', 8, 'Rifqi Fahruzian Anurul Huda', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(467, 'alpha', 'Marvel Rasya Pramudya — Alpha', 'Marvel Rasya Pramudya tercatat Alpha pada 20 Oktober 2026.', 9, 'Marvel Rasya Pramudya', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(468, 'alpha', 'Hanung Aprilio Gunawan — Alpha', 'Hanung Aprilio Gunawan tercatat Alpha pada 20 Oktober 2026.', 10, 'Hanung Aprilio Gunawan', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(469, 'alpha', 'Almira Kusumaningrum — Alpha', 'Almira Kusumaningrum tercatat Alpha pada 20 Oktober 2026.', 11, 'Almira Kusumaningrum', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(470, 'alpha', 'Nadina Ainur Pratiwi — Alpha', 'Nadina Ainur Pratiwi tercatat Alpha pada 20 Oktober 2026.', 12, 'Nadina Ainur Pratiwi', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(471, 'alpha', 'Muhammad Khoiruzzadi — Alpha', 'Muhammad Khoiruzzadi tercatat Alpha pada 20 Oktober 2026.', 13, 'Muhammad Khoiruzzadi', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(472, 'alpha', 'Putri Setyaningsih — Alpha', 'Putri Setyaningsih tercatat Alpha pada 20 Oktober 2026.', 14, 'Putri Setyaningsih', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57'),
(473, 'alpha', 'Shima Yuniar Alzara — Alpha', 'Shima Yuniar Alzara tercatat Alpha pada 20 Oktober 2026.', 15, 'Shima Yuniar Alzara', '2026-10-20', NULL, NULL, '2026-10-21 03:05:57', '2026-10-21 03:05:57');

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Masuk',
  `status_kehadiran` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Masuk',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `bukti_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_masuk` date DEFAULT NULL,
  `jam_masuk` time DEFAULT NULL,
  `tgl_pulang` date DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `accuracy` double DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jarak` decimal(10,2) DEFAULT NULL,
  `foto_pulang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude_pulang` double DEFAULT NULL,
  `longitude_pulang` double DEFAULT NULL,
  `accuracy_pulang` double DEFAULT NULL,
  `jarak_pulang` decimal(10,2) DEFAULT NULL,
  `radius_kantor_saat_pulang` decimal(10,2) DEFAULT NULL,
  `radius_kantor_saat_absen` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `user_id`, `nama`, `status`, `status_kehadiran`, `keterangan`, `bukti_file`, `bukti_original_name`, `bukti_mime_type`, `tgl_masuk`, `jam_masuk`, `tgl_pulang`, `jam_pulang`, `latitude`, `longitude`, `accuracy`, `foto`, `jarak`, `foto_pulang`, `latitude_pulang`, `longitude_pulang`, `accuracy_pulang`, `jarak_pulang`, `radius_kantor_saat_pulang`, `radius_kantor_saat_absen`, `created_at`, `updated_at`) VALUES
(1, 10, 'Hanung Aprilio Gunawan', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-21', '11:36:04', NULL, NULL, -8.1987080153991, 111.11797975494, 102, 'absen/foto/UEIEDBTcokWjz2NAe329XMJmgdCeknr93Yo6jCzu.jpg', '19.37', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-21 04:36:04', '2026-09-21 04:36:04'),
(2, 2, 'Khaisan Raihan Saputra', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-21', '11:40:07', NULL, NULL, -8.1986911703895, 111.11801469263, 102, 'absen/foto/Fh4WfpubPSjeXXmCwZAeQe2TGlLgklyBRkvep40D.jpg', '18.58', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-21 04:40:07', '2026-09-21 04:40:07'),
(3, 2, 'Khaisan Raihan Saputra', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-22', '09:09:36', NULL, NULL, -8.19871545, 111.11797318, 102, 'absen/foto/9n4LRb6PMVu3pUz4CKXWtcj9Qg8P7rPioxNFf6cT.jpg', '20.09', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-22 02:09:37', '2026-09-22 02:09:37'),
(4, 13, 'Muhammad Khoiruzzadi', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-22', '14:48:23', NULL, NULL, -8.19871545, 111.11797318, 102, 'absen/foto/QwpVqx0nNUDCVlyTy5qq25Ne2yhOv5N1S8sbEZm9.jpg', '20.09', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-22 07:48:23', '2026-09-22 07:48:23'),
(5, 6, 'Nevril Nur Irwan Khoiriansah', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-22', '15:08:40', NULL, NULL, -8.19871317, 111.11797549, 102, 'absen/foto/N89qrSXGlCqHfUtceS0lCSoOHMlYd0Iaqm643VSG.jpg', '19.87', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-22 08:08:40', '2026-09-22 08:08:40'),
(6, 10, 'Hanung Aprilio Gunawan', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-22', '15:12:08', NULL, NULL, -8.19871317, 111.11797549, 102, 'absen/foto/PZKeIcprHeu4mRpp8TSh8ZoocsXZW0uW55ylZDZH.jpg', '19.87', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-22 08:12:08', '2026-09-22 08:12:08'),
(12, 2, 'Khaisan Raihan Saputra', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-24', '11:58:00', NULL, NULL, -8.198719825, 111.11797075, 102, 'absen/foto/QZy88bSQQlc5yLF16ITxi0wWJPJper3zBb5qVSp6.jpg', '20.54', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-24 04:58:01', '2026-09-24 04:58:01'),
(17, 13, 'Muhammad Khoiruzzadi', 'Izin', 'Izin', 'rwsgdzhsdhdhsehsdhse', NULL, NULL, NULL, '2026-09-24', '14:24:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-24 07:24:21', '2026-09-24 07:24:21'),
(18, 6, 'Nevril Nur Irwan Khoiriansah', 'Dispensasi', 'Dispensasi', 'dispen drumband', 'absen/bukti/Sf2XmycP8rlxglCY83wkP2P0aFitJfLHkhay4C4Z.pdf', 'Cetak Laporan - Khaisan Raihan Saputra.pdf', 'application/pdf', '2026-09-24', '14:26:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-24 07:26:25', '2026-09-24 07:26:25'),
(19, 9, 'Marvel Rasya Pramudya', 'Sakit', 'Sakit', 'sakit diaree', 'absen/bukti/KV8uD6XnwTzUS2FO4PojbeK8UCdKRhU4DrFX66fx.jpg', 'WhatsApp Image 2026-09-22 at 15.02.07.jpeg', 'image/jpeg', '2026-09-24', '14:28:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-24 07:28:44', '2026-09-24 07:28:44'),
(20, 10, 'Hanung Aprilio Gunawan', 'Izin', 'Izin', 'hjdsjbasdncnabnxcane', NULL, NULL, NULL, '2026-09-24', '14:29:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-24 07:29:22', '2026-09-24 07:29:22'),
(21, 2, 'Khaisan Raihan Saputra', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-25', '13:10:55', NULL, NULL, -8.19869376, 111.11798152, 103, 'absen/foto/x3Rv7goErjVQHUkNQqfiEi8MRaZudCFL4lPSsAUd.jpg', '17.84', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-25 06:10:55', '2026-09-25 06:10:55'),
(22, 2, 'Khaisan Raihan Saputra', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-28', '09:46:30', NULL, NULL, -8.1987021967165, 111.11800002979, 102, 'absen/foto/CBxGUEFomUbfG2hQIDETzvc8oen9s1EPErfB1MBr.jpg', '19.22', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-28 02:46:30', '2026-09-28 02:46:30'),
(23, 2, 'Khaisan Raihan Saputra', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-30', '10:51:33', NULL, NULL, -8.1987328624937, 111.11796748773, 102, 'absen/foto/xemJtM6Hq6WqKln0kVLCHRPjxBJSfEIT1uABAHX3.jpg', '21.96', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-30 03:51:33', '2026-09-30 03:51:33'),
(24, 13, 'Muhammad Khoiruzzadi', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-09-30', '11:02:39', NULL, NULL, -8.1987446667205, 111.11795799565, 102, 'absen/foto/wldo31lPjDc9NFrekmsnrBDvc0b2laxn13F9gG3M.jpg', '23.21', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-09-30 04:02:39', '2026-09-30 04:02:39'),
(25, 2, 'Khaisan Raihan Saputra', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-10-16', '15:49:35', NULL, NULL, -8.1986890257846, 111.11790387459, 94, 'absen/foto/F3J0sv20ULbQrsJFF32CgZHx1DFaxVHSb5vZTUiX.jpg', '17.82', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-10-16 08:49:35', '2026-10-16 08:49:35'),
(26, 13, 'Muhammad Khoiruzzadi', 'Telat', 'Telat', NULL, NULL, NULL, NULL, '2026-10-16', '15:58:07', NULL, NULL, -8.1986581746449, 111.11795401306, 88, 'absen/foto/OW60n0wfaz3agYfAIePyO9nsCLHvQNhHz5HwOrJs.jpg', '13.59', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-10-16 08:58:07', '2026-10-16 08:58:07'),
(27, 2, 'Khaisan Raihan Saputra', 'Pulang', 'Masuk', NULL, NULL, NULL, NULL, '2026-10-19', '09:30:47', '2026-10-19', '15:07:00', -8.1987033088817, 111.11794441921, 85, 'absen/foto/QXZIGfnwiIQCK7cu2277VB8jmjyyaYxRCInVezdm.jpg', '18.62', 'absen/foto-pulang/CeNhD8WiyWR80Ybcxjo2x76lOkLCSF756STfdmJb.jpg', -8.1986980841923, 111.11796554267, 85, '18.08', '100.00', '100.00', '2026-10-19 02:30:47', '2026-10-19 08:07:00'),
(28, 2, 'Khaisan Raihan Saputra', 'Masuk', 'Masuk', NULL, NULL, NULL, NULL, '2026-10-20', '09:41:01', NULL, NULL, -8.198716775, 111.117932925, 90, 'absen/foto/pUMTUWuxX8mrMTWQ06nGhf8QCTEHocsdB1ch41IF.jpg', '20.21', NULL, NULL, NULL, NULL, NULL, NULL, '100.00', '2026-10-20 02:41:01', '2026-10-20 02:41:01');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporans`
--

CREATE TABLE `laporans` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `tanggal_laporan` date NOT NULL,
  `teks_laporan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_laporan` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `laporans`
--

INSERT INTO `laporans` (`id`, `user_id`, `tanggal_laporan`, `teks_laporan`, `jenis_laporan`, `foto`, `foto_original_name`, `foto_mime_type`, `created_at`, `updated_at`) VALUES
(1, 10, '2026-09-21', 'keskjdjghsrusdhjgh4usdhguwhsjdhgjshdjhgjehsjdgjskhdjgh', 'teknisi', 'laporan/foto/859yeLulDwYlaQuixNizdvEM5PEQUeE1pqJvb9jZ.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-21 04:36:55', '2026-09-21 04:36:55'),
(2, 2, '2026-09-21', 'daoeugaugaoudgauguadughadg', 'sales', 'laporan/foto/wsYo7GNg5z2oDJVDnDPcTSwFjupmsIiOT46cP9at.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-21 04:41:26', '2026-09-21 04:41:26'),
(3, 13, '2026-09-22', 'ashkfahbfhasbfkhabsfbasjfb', 'sales', 'laporan/foto/oUzSmQ7cMlBtBhUxz2T1oMqfMWusLpuRGI0svaEm.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-22 07:49:07', '2026-09-22 07:49:07'),
(4, 2, '2026-09-25', 'ajngangjangdsgnejadsgneajndmsgnejandgnejandgnejn', 'teknisi', 'laporan/foto/rvoaJrxaKYtXpiCwtTWbLvgrj1wdjD9YeGdOnxQD.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-25 06:11:27', '2026-09-25 06:11:28'),
(5, 2, '2026-09-25', 'ajdjansnagdangaendsgeadgnejandmgneansd', 'sales', 'laporan/foto/XbiNmradbaPL9oTDRr4SDb2A9fZbLjt57YoshsG5.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-25 06:12:17', '2026-09-25 06:12:17'),
(6, 2, '2026-09-25', 'svsnsAKVKASVKAKSVKAKVKKKAVKSKSVKVknvkdnknsknvksndbks', 'sales', 'laporan/foto/uDeJsj1WdojFxFh8A9aU2z6xRr14A5nRGPRmmgfO.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-25 06:17:20', '2026-09-25 06:17:20'),
(7, 2, '2026-09-28', 'yxfhghgvhgvhvhvhbvhbvhbvhv', 'sales', 'laporan/foto/DgXws2clcj69G4pU4sMWHs3VfUVirlezckXvevUU.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-28 05:51:37', '2026-09-28 05:51:37'),
(8, 2, '2026-09-30', 'ashfjasjgaeohsnfeuoafneouadfneoansmfnwanslkfn anjay mabar', 'teknisi', 'laporan/foto/O3YyV5gQD9dpuoq7EO8g1cra8nlksPuX7kEi4rYl.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-30 04:00:21', '2026-09-30 04:01:18'),
(9, 13, '2026-09-30', 'weihgshdojghw4ejhguwsdjghushdsdgnsn anjay mabar', 'sales', 'laporan/foto/GSHTKq3Zy811VZOOhwzz1lXXxMiQC3fi8JNIIfre.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-09-30 04:03:18', '2026-09-30 04:04:38'),
(10, 2, '2026-10-16', 'awgasdgsgesdgsdgsedgeehgasjb', 'teknisi', 'laporan/foto/AyHHO15PfKn2xtSDgFv86DsLCmfNY2ETgvtmjeAP.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-10-16 08:57:19', '2026-10-16 08:57:19'),
(11, 2, '2026-10-19', 'asv a v asmv am smjasm a m vaksnvwans vwkansv a vwknaksnv wak', 'sales', 'laporan/foto/l2Kz61UrY5lbzHTXKjWyRrxaMf72qm0l46awxz1R.jpg', 'attendance-0.jpg', 'image/jpeg', '2026-10-19 08:05:32', '2026-10-19 08:05:32');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_fotos`
--

CREATE TABLE `laporan_fotos` (
  `id` bigint UNSIGNED NOT NULL,
  `laporan_id` bigint UNSIGNED NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `laporan_fotos`
--

INSERT INTO `laporan_fotos` (`id`, `laporan_id`, `foto`, `foto_original_name`, `foto_mime_type`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'laporan/foto/859yeLulDwYlaQuixNizdvEM5PEQUeE1pqJvb9jZ.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-21 04:36:55', '2026-09-21 04:36:55'),
(2, 1, 'laporan/foto/rV99hztxAwSYSxXNpM4hBHcLm1sYQbrwI9uExp4I.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-09-21 04:36:55', '2026-09-21 04:36:55'),
(3, 1, 'laporan/foto/RtPm67zvjBMYY0KgAq6AdPvOmO2wGXWxROl3zsgD.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-09-21 04:36:55', '2026-09-21 04:36:55'),
(4, 1, 'laporan/foto/Ytr1CdwSMBStkkkFGYXKK8OZDniiikzjBmwRstW3.jpg', 'attendance-3.jpg', 'image/jpeg', 3, '2026-09-21 04:36:55', '2026-09-21 04:36:55'),
(5, 1, 'laporan/foto/zeKVlLCbFdr1vbNL3icIrMPJaaQvIfeRbmE1UHds.jpg', 'attendance-4.jpg', 'image/jpeg', 4, '2026-09-21 04:36:55', '2026-09-21 04:36:55'),
(6, 2, 'laporan/foto/wsYo7GNg5z2oDJVDnDPcTSwFjupmsIiOT46cP9at.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-21 04:41:26', '2026-09-21 04:41:26'),
(7, 2, 'laporan/foto/BiyfJyFF4Jix89zU4wQJyCO8kvdHYUWPTrPczcnt.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-09-21 04:41:26', '2026-09-21 04:41:26'),
(8, 2, 'laporan/foto/Q0vdIJ6wUXKCLdMZ70a1aWShHv6rrkJkKWIJrLxM.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-09-21 04:41:26', '2026-09-21 04:41:26'),
(9, 3, 'laporan/foto/oUzSmQ7cMlBtBhUxz2T1oMqfMWusLpuRGI0svaEm.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-22 07:49:07', '2026-09-22 07:49:07'),
(10, 4, 'laporan/foto/rvoaJrxaKYtXpiCwtTWbLvgrj1wdjD9YeGdOnxQD.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-25 06:11:27', '2026-09-25 06:11:27'),
(11, 4, 'laporan/foto/LwGhfsR17KxSrBeVG9hiuSu1lYPLx7Z4WCeBE5nV.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-09-25 06:11:28', '2026-09-25 06:11:28'),
(12, 4, 'laporan/foto/I6xIv72567VIOb1tYKNYGaXZcSVvOdxa1qXaGARn.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-09-25 06:11:28', '2026-09-25 06:11:28'),
(13, 5, 'laporan/foto/XbiNmradbaPL9oTDRr4SDb2A9fZbLjt57YoshsG5.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-25 06:12:17', '2026-09-25 06:12:17'),
(14, 5, 'laporan/foto/prTI9t3LycNA6JlgUSbXulPEMZ8Hu1txvHUVvInr.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-09-25 06:12:17', '2026-09-25 06:12:17'),
(15, 5, 'laporan/foto/7MCP64hzZ71HHjuTkk30PcR7cnO8tuXUMoT6uyQX.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-09-25 06:12:17', '2026-09-25 06:12:17'),
(16, 5, 'laporan/foto/dhMy1x9iHdzolZjjjy36y5stI4pYggCOKZuZZvGT.jpg', 'attendance-3.jpg', 'image/jpeg', 3, '2026-09-25 06:12:18', '2026-09-25 06:12:18'),
(17, 5, 'laporan/foto/bt4GnEK4WhjuANrPgfbXKQb6CE3r0Ujtfk5JZOyt.jpg', 'attendance-4.jpg', 'image/jpeg', 4, '2026-09-25 06:12:18', '2026-09-25 06:12:18'),
(18, 5, 'laporan/foto/hgjbqN42ibaQvhEMQwzMDcnDbbkjCGU8Gfzkreun.jpg', 'attendance-5.jpg', 'image/jpeg', 5, '2026-09-25 06:12:18', '2026-09-25 06:12:18'),
(19, 6, 'laporan/foto/uDeJsj1WdojFxFh8A9aU2z6xRr14A5nRGPRmmgfO.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-25 06:17:20', '2026-09-25 06:17:20'),
(20, 6, 'laporan/foto/GwNmziuIPhc5TWWqMcbri2yPss00blUgLjYpqfqc.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-09-25 06:17:20', '2026-09-25 06:17:20'),
(21, 6, 'laporan/foto/6iJ0TDOudSsuJFvC0Wpse1D3M9XUagfHFvlChqvw.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-09-25 06:17:20', '2026-09-25 06:17:20'),
(22, 6, 'laporan/foto/Icc0wIkE2RKl0mVF7q2rw6etw3OQv32Iiw2c9K3m.jpg', 'attendance-3.jpg', 'image/jpeg', 3, '2026-09-25 06:17:20', '2026-09-25 06:17:20'),
(23, 6, 'laporan/foto/w1M0o1Wv4e2F8qDjQkJa1uvx80ozibJX6TFQ1odu.jpg', 'attendance-4.jpg', 'image/jpeg', 4, '2026-09-25 06:17:20', '2026-09-25 06:17:20'),
(24, 6, 'laporan/foto/8uHLWGLmoWEAgxn6noz050jklHEE19xJy6LSHdSQ.jpg', 'attendance-5.jpg', 'image/jpeg', 5, '2026-09-25 06:17:21', '2026-09-25 06:17:21'),
(25, 7, 'laporan/foto/DgXws2clcj69G4pU4sMWHs3VfUVirlezckXvevUU.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-28 05:51:37', '2026-09-28 05:51:37'),
(26, 7, 'laporan/foto/cORB5kJ01apAUo5gfBDZgy9O1oHDJeNmgs24nbZ8.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-09-28 05:51:37', '2026-09-28 05:51:37'),
(27, 7, 'laporan/foto/h8sgJHXlnA2hL2oIFGpBxVWN6z1sFpBIM3TRneFd.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-09-28 05:51:37', '2026-09-28 05:51:37'),
(28, 7, 'laporan/foto/HzSosXiNRklR1zvkduvzOqDT66FO7Q1E4UugYosl.jpg', 'attendance-3.jpg', 'image/jpeg', 3, '2026-09-28 05:51:37', '2026-09-28 05:51:37'),
(29, 7, 'laporan/foto/uKRxwLdHhi7C9rP7RRsOKgsqTXh49efV7sgpszut.jpg', 'attendance-4.jpg', 'image/jpeg', 4, '2026-09-28 05:51:37', '2026-09-28 05:51:37'),
(30, 7, 'laporan/foto/JOpTqLV4ogbsSkUqXF3OT3Jo9pr7sPy3jVejfSNk.jpg', 'attendance-5.jpg', 'image/jpeg', 5, '2026-09-28 05:51:38', '2026-09-28 05:51:38'),
(31, 8, 'laporan/foto/O3YyV5gQD9dpuoq7EO8g1cra8nlksPuX7kEi4rYl.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-30 04:00:21', '2026-09-30 04:00:21'),
(32, 8, 'laporan/foto/bHnGlvIeYxGEHuNJozFR6wt1LJAEn43Q8Tdc8tf6.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-09-30 04:00:21', '2026-09-30 04:00:21'),
(33, 8, 'laporan/foto/jFi0NoZ1tuVCHFkhY81AqHBO6ReaNUMmEsdIaDbn.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-09-30 04:00:22', '2026-09-30 04:00:22'),
(34, 8, 'laporan/foto/htzV8FKrjdsLlaVFKgfXGo1ibeiqz6S0SyKgOoXK.jpg', 'attendance-3.jpg', 'image/jpeg', 3, '2026-09-30 04:00:22', '2026-09-30 04:00:22'),
(35, 8, 'laporan/foto/W78bo2dWkJNCL9X0gcWN0sYtED5NtBBxahOLPN9q.jpg', 'Screenshot_24-9-2026_143047_simprap4.test.jpeg', 'image/jpeg', 4, '2026-09-30 04:01:18', '2026-09-30 04:01:18'),
(36, 9, 'laporan/foto/GSHTKq3Zy811VZOOhwzz1lXXxMiQC3fi8JNIIfre.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-09-30 04:03:18', '2026-09-30 04:03:18'),
(37, 9, 'laporan/foto/zMJuWa8Paj9bNf7w1EfBZLg21FgdcwmnDvzPfrRk.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-09-30 04:03:18', '2026-09-30 04:03:18'),
(38, 9, 'laporan/foto/xIDa6dcl938mNmiAbrfUEmuJGUM7qTX3SOsomxLY.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-09-30 04:03:18', '2026-09-30 04:03:18'),
(39, 9, 'laporan/foto/KSlr3Cj5YhQPlD2TXRJ3qXKjfOXah7sbF9H54grB.jpg', 'attendance-3.jpg', 'image/jpeg', 3, '2026-09-30 04:03:18', '2026-09-30 04:03:18'),
(40, 9, 'laporan/foto/ulZKJbRqJLrPVWPoCW0u8IEanf6iK7SXTIHsncwr.jpg', 'Screenshot_24-9-2026_143047_simprap4.test.jpeg', 'image/jpeg', 4, '2026-09-30 04:04:38', '2026-09-30 04:04:38'),
(41, 10, 'laporan/foto/AyHHO15PfKn2xtSDgFv86DsLCmfNY2ETgvtmjeAP.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-10-16 08:57:19', '2026-10-16 08:57:19'),
(42, 10, 'laporan/foto/TscxJqdvrqS0OtAxktOFHgQLsvecAigp1ACPWgYD.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-10-16 08:57:20', '2026-10-16 08:57:20'),
(43, 10, 'laporan/foto/rXujBeFNGHGARvwwoTexCvUEjhEpyHxgMMY8Oh5N.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-10-16 08:57:20', '2026-10-16 08:57:20'),
(44, 10, 'laporan/foto/5cEskseyxLoHGNTzOfJAzYvmZb2tGycu5mPM4uGf.jpg', 'attendance-3.jpg', 'image/jpeg', 3, '2026-10-16 08:57:20', '2026-10-16 08:57:20'),
(45, 10, 'laporan/foto/1JReEWrRZdEK3DBlznr8GuP2pTFihnm58rRK2VA7.jpg', 'attendance-4.jpg', 'image/jpeg', 4, '2026-10-16 08:57:20', '2026-10-16 08:57:20'),
(46, 11, 'laporan/foto/l2Kz61UrY5lbzHTXKjWyRrxaMf72qm0l46awxz1R.jpg', 'attendance-0.jpg', 'image/jpeg', 0, '2026-10-19 08:05:32', '2026-10-19 08:05:32'),
(47, 11, 'laporan/foto/msXjFtUDBChu1M5k9iXF5m5hEpdFZYFyWZ7VSezr.jpg', 'attendance-1.jpg', 'image/jpeg', 1, '2026-10-19 08:05:32', '2026-10-19 08:05:32'),
(48, 11, 'laporan/foto/9ADlonlYUg7u88DXOunk7g5GglGBKlWKNx1y715h.jpg', 'attendance-2.jpg', 'image/jpeg', 2, '2026-10-19 08:05:33', '2026-10-19 08:05:33'),
(49, 11, 'laporan/foto/qJKlLXx9qQ45tceLoBkYu9clNBaIpt3caedPFKwN.jpg', 'attendance-3.jpg', 'image/jpeg', 3, '2026-10-19 08:05:33', '2026-10-19 08:05:33'),
(50, 11, 'laporan/foto/jZM71tR5Q7I6xvwENElgvkIVxcOEztd0sahw2UPl.jpg', 'attendance-4.jpg', 'image/jpeg', 4, '2026-10-19 08:05:33', '2026-10-19 08:05:33');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_sales`
--

CREATE TABLE `laporan_sales` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `laporan_id` bigint UNSIGNED DEFAULT NULL,
  `tanggal` date NOT NULL,
  `nama_customer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_telp` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_customer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelebihan` text COLLATE utf8mb4_unicode_ci,
  `kekurangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `laporan_sales`
--

INSERT INTO `laporan_sales` (`id`, `user_id`, `laporan_id`, `tanggal`, `nama_customer`, `no_telp`, `alamat_customer`, `provider`, `kelebihan`, `kekurangan`, `created_at`, `updated_at`) VALUES
(1, 2, 2, '2026-09-21', 'siswanto', '098765432123', 'ngampel ploso', 'Speedy', 'lancar', NULL, '2026-09-21 04:43:29', '2026-09-21 04:43:29'),
(2, 2, 2, '2026-09-21', 'sarwono', '098765432123', 'ngampel ploso', 'Wifa', NULL, 'lag', '2026-09-21 04:43:29', '2026-09-21 04:43:29'),
(3, 2, 2, '2026-09-21', 'urep', '098765432123', 'ngampel ploso', 'Azafanet', 'murah', NULL, '2026-09-21 04:43:29', '2026-09-21 04:43:29'),
(4, 2, 2, '2026-09-21', 'sumiati', '098765432123', 'ngampel ploso', 'Tidak menggunakan layanan internet', NULL, NULL, '2026-09-21 04:43:29', '2026-09-21 04:43:29'),
(5, 2, 2, '2026-09-21', 'ismiyati', '098765432123', 'ngampel ploso', 'Pakai tidak tau namanya', NULL, NULL, '2026-09-21 04:43:29', '2026-09-21 04:43:29'),
(6, 2, 5, '2026-09-25', 'siswanto', '098765432123', 'ngampel ploso pacitan', 'Starlite', 'lancar', NULL, '2026-09-25 06:13:27', '2026-09-25 06:13:27'),
(7, 2, 5, '2026-09-25', 'sarwono', '098765432123', 'ngampel ploso pacitan', 'Tidak menggunakan layanan internet', NULL, NULL, '2026-09-25 06:13:27', '2026-09-25 06:13:27'),
(8, 2, 6, '2026-09-25', 'Hanung', '098765432123', 'ngampel ploso pacitan', 'Smartnet', 'lancarr', NULL, '2026-09-25 06:17:50', '2026-09-25 06:17:50'),
(9, 2, 6, '2026-09-25', 'urep subagyo', '098765432123', 'ngampel ploso pacitan', 'Wifa', NULL, NULL, '2026-09-25 06:17:50', '2026-09-25 06:17:50'),
(10, 2, 6, '2026-09-25', 'Hanung', '098765432123', 'ngampel ploso pacitan', 'Smartnet', 'lancarr', NULL, '2026-09-25 06:17:51', '2026-09-25 06:17:51'),
(11, 2, 6, '2026-09-25', 'urep subagyo', '098765432123', 'ngampel ploso pacitan', 'Wifa', NULL, NULL, '2026-09-25 06:17:51', '2026-09-25 06:17:51'),
(12, 2, 7, '2026-09-28', 'Hanung', '098765432123', 'sdSDBsdbsdbs', 'Starlite', 'lancarr', NULL, '2026-09-28 05:53:22', '2026-09-28 05:53:22'),
(13, 2, 7, '2026-09-28', 'urep subagyo', '098765432123', 'dgsdgsgsdgs', 'Pakai tidak tau namanya', 'lancarr', NULL, '2026-09-28 05:53:22', '2026-09-28 05:53:22'),
(14, 2, 7, '2026-09-28', 'siswanto', '098765432123', 'adgqasgasgasadg', 'Azafanet', NULL, 'lagg', '2026-09-28 05:53:22', '2026-09-28 05:53:22'),
(15, 2, 7, '2026-09-28', 'sarwono', '098765432123', 'asfsfa', 'Inalite', 'lancarr', NULL, '2026-09-28 06:04:12', '2026-09-28 06:04:12'),
(16, 2, 7, '2026-09-28', 'ismiyati', '098765432123', 'abaksjbfjahshfjawh', 'Smartnet', 'lancarr', NULL, '2026-09-28 06:04:12', '2026-09-28 06:04:12'),
(17, 13, 9, '2026-09-30', 'siswanto', '098765432123', 'jzncbajbs xshijja ab', 'Smartnet', 'lancar', NULL, '2026-09-30 04:04:06', '2026-09-30 04:04:06'),
(18, 13, 9, '2026-09-30', 'sarwono', '098765432123', 'qjibfjqbsfhqysuhh', 'Tidak menggunakan layanan internet', NULL, NULL, '2026-09-30 04:04:06', '2026-09-30 04:04:06'),
(19, 13, 9, '2026-09-30', 'urep', '098765432123', 'asjansjn uha ug iha', 'Tridata', NULL, 'lagg', '2026-09-30 04:04:06', '2026-09-30 04:04:06'),
(20, 2, 11, '2026-10-19', 'siswanto', '098765432123', 'Ngampel Ploso Pacitan', 'Tidak menggunakan layanan internet', NULL, NULL, '2026-10-19 08:06:34', '2026-10-19 08:06:34'),
(21, 2, 11, '2026-10-19', 'sarwono', '098765432123', 'Ngampel Ploso Pacitan', 'Tridata', 'lancar', NULL, '2026-10-19 08:06:34', '2026-10-19 08:06:34'),
(22, 2, 11, '2026-10-19', 'urep', '098765432123', 'Ngampel Ploso Pacitan', 'Pakai tidak tau namanya', NULL, NULL, '2026-10-19 08:06:34', '2026-10-19 08:06:34'),
(23, 2, 11, '2026-10-19', 'sumiati', '098765432123', 'Ngampel Ploso Pacitan', 'Smartnet', NULL, 'Ngelag', '2026-10-19 08:06:34', '2026-10-19 08:06:34');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_01_01_000000_add_email_to_users_table', 1),
(6, '2025_01_01_000001_create_password_reset_otps_table', 1),
(7, '2026_01_01_000000_create_attendances_table', 1),
(8, '2026_01_01_000001_add_role_and_status_to_users_table', 1),
(9, '2026_08_18_000000_add_gps_and_user_to_attendances_table', 1),
(10, '2026_08_18_000001_create_settings_table', 1),
(11, '2026_08_18_000002_add_foto_and_jarak_to_attendances_table', 1),
(12, '2026_08_18_000003_add_jam_absen_to_settings_table', 1),
(13, '2026_08_18_000004_add_radius_saat_absen_to_attendances_table', 1),
(14, '2026_08_18_000005_add_remember_token_to_users_table', 1),
(15, '2026_08_19_000000_add_absen_pulang_to_attendances_table', 1),
(16, '2026_08_19_000001_add_jam_pulang_to_settings_table', 1),
(17, '2026_08_21_000000_add_status_kehadiran_to_attendances_table', 1),
(18, '2026_08_22_000000_add_bukti_file_to_attendances_table', 1),
(19, '2026_08_31_094129_create_laporans_table', 1),
(20, '2026_09_02_000000_create_laporan_fotos_table', 1),
(21, '2026_09_02_000001_update_jam_masuk_to_0800_fixed_pulang', 1),
(22, '2026_09_04_093537_create_monthly_statistics_table', 1),
(23, '2026_09_07_000001_add_user_id_to_users_table', 1),
(24, '2026_09_07_000002_fill_user_id_for_existing_siswa', 1),
(25, '2026_09_07_000003_correct_user_id_to_int0030', 1),
(26, '2026_09_10_000000_add_hadir_alpha_laporan_to_monthly_statistics_table', 1),
(27, '2026_09_11_000000_create_laporan_sales_table', 1),
(28, '2026_09_15_101100_add_laporan_id_to_laporan_sales_table', 1),
(29, '2026_09_17_000000_add_jenis_laporan_to_laporans_table', 1),
(30, '2026_09_17_000001_update_user_foreign_keys_to_restrict', 1),
(31, '2026_09_24_000000_create_admin_notifications_table', 2),
(32, '2026_10_20_000000_add_profile_fields_to_users_table', 3),
(33, '2026_10_21_000000_add_dikeluarkan_to_users_status', 4);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_statistics`
--

CREATE TABLE `monthly_statistics` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `bulan` tinyint UNSIGNED NOT NULL,
  `tahun` smallint UNSIGNED NOT NULL,
  `jumlah_masuk` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_pulang` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_izin` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_sakit` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_dispensasi` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_telat` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_tidak_masuk` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_hadir` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_alpha` int UNSIGNED NOT NULL DEFAULT '0',
  `jumlah_laporan` int UNSIGNED NOT NULL DEFAULT '0',
  `persentase_kehadiran` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `monthly_statistics`
--

INSERT INTO `monthly_statistics` (`id`, `user_id`, `bulan`, `tahun`, `jumlah_masuk`, `jumlah_pulang`, `jumlah_izin`, `jumlah_sakit`, `jumlah_dispensasi`, `jumlah_telat`, `jumlah_tidak_masuk`, `jumlah_hadir`, `jumlah_alpha`, `jumlah_laporan`, `persentase_kehadiran`, `created_at`, `updated_at`) VALUES
(1, 10, 9, 2026, 0, 0, 1, 0, 0, 2, 7, 0, 7, 1, 33, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(2, 9, 9, 2026, 0, 0, 0, 1, 0, 0, 7, 0, 7, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(3, 5, 9, 2026, 0, 0, 0, 0, 0, 0, 8, 0, 8, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(4, 6, 9, 2026, 0, 0, 1, 0, 1, 1, 7, 0, 7, 0, 17, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(5, 4, 9, 2026, 0, 0, 0, 0, 0, 0, 8, 0, 8, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(6, 8, 9, 2026, 0, 0, 0, 0, 0, 0, 8, 0, 8, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(7, 14, 9, 2026, 0, 0, 0, 0, 0, 0, 8, 0, 8, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(8, 12, 9, 2026, 0, 0, 0, 0, 0, 0, 8, 0, 8, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(9, 11, 9, 2026, 0, 0, 0, 0, 0, 0, 8, 0, 8, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(10, 7, 9, 2026, 0, 0, 0, 0, 0, 0, 8, 0, 8, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(11, 2, 9, 2026, 0, 0, 0, 0, 0, 6, 8, 0, 8, 6, 100, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(12, 3, 9, 2026, 0, 0, 0, 0, 0, 0, 8, 0, 8, 0, 0, '2026-10-16 02:42:11', '2026-10-16 02:42:11'),
(13, 13, 9, 2026, 0, 0, 1, 0, 0, 2, 7, 0, 7, 2, 33, '2026-10-16 02:42:11', '2026-10-16 02:42:11');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otps`
--

CREATE TABLE `password_reset_otps` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expired_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `attempts` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'lokasi_kantor_nama', 'PT Sandya Network', '2026-09-21 03:22:32', '2026-09-21 03:22:32'),
(2, 'lokasi_kantor_alamat', 'Jl. Jend. Sudirman No.3, Barang, Arjowinangun, Kec. Pacitan, Kabupaten Pacitan, Jawa Timur 63516', '2026-09-21 03:22:32', '2026-09-21 03:22:32'),
(3, 'lokasi_kantor_latitude', '-8.198536', '2026-09-21 03:22:33', '2026-09-21 03:52:50'),
(4, 'lokasi_kantor_longitude', '111.117952', '2026-09-21 03:22:33', '2026-09-21 03:52:50'),
(5, 'lokasi_kantor_radius', '100', '2026-09-21 03:22:33', '2026-09-21 04:20:02'),
(6, 'jam_absen_mulai', '08:00', '2026-09-21 03:22:34', '2026-10-19 02:29:13'),
(7, 'jam_absen_selesai', '10:30', '2026-09-21 03:22:33', '2026-10-19 02:27:54'),
(8, 'jam_pulang_mulai', '15:00', '2026-09-21 03:22:34', '2026-10-19 02:27:54'),
(9, 'jam_pulang_selesai', '23:00', '2026-09-21 03:22:33', '2026-09-21 03:22:33'),
(10, 'jam_laporan_mulai', '10:00', '2026-09-21 03:52:50', '2026-10-19 02:27:54'),
(11, 'jam_laporan_selesai', '23:59', '2026-09-21 03:52:50', '2026-09-21 03:52:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nik` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat_lahir` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asal_sekolah` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_sekolah` text COLLATE utf8mb4_unicode_ci,
  `jenis_identitas_sekolah` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_identitas_sekolah` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pkl_mulai` date DEFAULT NULL,
  `pkl_selesai` date DEFAULT NULL,
  `ktp_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kartu_pelajar_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','siswa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `status` enum('Aktif','Nonaktif','Dikeluarkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `nik`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_telepon`, `asal_sekolah`, `alamat_sekolah`, `jenis_identitas_sekolah`, `nomor_identitas_sekolah`, `pkl_mulai`, `pkl_selesai`, `ktp_path`, `kartu_pelajar_path`, `username`, `email`, `nama`, `role`, `status`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'daunbot03@gmail.com', 'Administrator', 'admin', 'Aktif', '$2y$12$d2/IrNf/KsSDtXe/WGmE2uOvcHSQtxF6XOXqxUo3AzQOSqpXrDF5q', NULL, '2026-09-21 03:22:37', '2026-09-24 06:20:56'),
(2, 'G1 - INT0040', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Rehan', 'KhaisanRaihanSaputra@gmail.com', 'Khaisan Raihan Saputra', 'siswa', 'Aktif', '$2y$12$6JGIzx4YU1xGRiXVrImMCOKAf8xo6FPZb//PbIsrosDqlifDM.14m', NULL, '2026-09-21 03:22:38', '2026-10-16 08:43:47'),
(3, 'G1 - INT0041', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Fagus', 'FagusRidwan@gmail.com', 'Fagus Ridwan', 'siswa', 'Aktif', '$2y$12$8eO6AYtDEaY1PJUIU0rUKe5k6EDdZPjtHX2Cpfuz4iwHNf3JR1GhS', NULL, '2026-09-21 03:22:39', '2026-09-21 03:27:15'),
(4, 'G1 - INT0034', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nauval', 'NauvalVedraPratama@gmail.com', 'Nauval Vedra Pratama', 'siswa', 'Aktif', '$2y$12$k6H22CACYNBMgOTWY0PMlObjOmHLBhu3swRWDOMV0Vx0MJDrerGZW', NULL, '2026-09-21 03:22:40', '2026-09-21 03:27:15'),
(5, 'G1 - INT0032', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Fadli', 'FadliRizkySaputra@gmail.com', 'Fadli Rizky Saputra', 'siswa', 'Aktif', '$2y$12$tkkoPSBBSct6ZfRFmzgv0.iesYNGC9wvf0ZA/T2BpBVhh/jFWfQbC', NULL, '2026-09-21 03:22:41', '2026-09-21 03:27:16'),
(6, 'G1 - INT0033', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nevril', 'NevrilNurIrwanKhoiriansah@gmail.com', 'Nevril Nur Irwan Khoiriansah', 'siswa', 'Aktif', '$2y$12$4CSYOx6XZPQzonRguwKLvuEOKcpU200hZBKIt5sv6KgCSQlS0JyYC', NULL, '2026-09-21 03:22:42', '2026-09-22 08:06:22'),
(7, 'G1 - INT0039', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Riska', 'RiskaSahwalani@gmail.com', 'Riska Sahwalani', 'siswa', 'Aktif', '$2y$12$HjunUWXXEHC/2Z9ibm33EOIHhTHMK87H8lHSoA39wVEz2wguiXGte', NULL, '2026-09-21 03:22:43', '2026-09-21 03:27:19'),
(8, 'G1 - INT0035', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Huda', 'RifqiFahruzianAnurulHuda@gmail.com', 'Rifqi Fahruzian Anurul Huda', 'siswa', 'Aktif', '$2y$12$uij7PxKmN24IMOQk579HjOFaCCiEHHWPzmBj8t412eFbAlhBuKrQK', NULL, '2026-09-21 03:22:44', '2026-09-21 03:27:19'),
(9, 'G1 - INT0031', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Marvel', 'MarvelRasyaPramudya@gmail.com', 'Marvel Rasya Pramudya', 'siswa', 'Aktif', '$2y$12$rLqN.1NXAwokPmkQBHdfweOAe89ZOlCiBSEiaGDcpkuDAU7QFaXsC', NULL, '2026-09-21 03:22:45', '2026-09-24 07:27:58'),
(10, 'G1 - INT0030', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Hanung', 'HanungAprilioGunawan@gmail.com', 'Hanung Aprilio Gunawan', 'siswa', 'Aktif', '$2y$12$jOQduKvp0IHvptB9L0kXmu2oUlivniIRyZH593Ydmec3Ud3ILkRNu', NULL, '2026-09-21 03:22:46', '2026-09-24 07:27:38'),
(11, 'G1 - INT0038', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mira', 'AlmiraKusumaningrum@gmail.com', 'Almira Kusumaningrum', 'siswa', 'Aktif', '$2y$12$rqiqws12rwtpmcnQnAHKnO0f1kLTQQ3t5erV6g5dvI5SW0JyjMATq', NULL, '2026-09-21 03:22:47', '2026-09-21 03:27:23'),
(12, 'G1 - INT0037', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nadin', 'NadinaAinurPratiwi@gmail.com', 'Nadina Ainur Pratiwi', 'siswa', 'Aktif', '$2y$12$yhwjzrdJysWL7wRqux6kXOOZx3ATnY9w8D7tDN35Lv4Q9.HfrpaWq', NULL, '2026-09-21 03:22:47', '2026-09-21 03:27:24'),
(13, 'G1 - INT0042', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Zadi', 'MuhammadKhoiruzzadi@gmail.com', 'Muhammad Khoiruzzadi', 'siswa', 'Aktif', '$2y$12$nDO8532B4wTw4j0kh4vZ6ODLq3YhrgYr/W0Zi/oAkvK8DrfVXh6gC', NULL, '2026-09-21 03:22:49', '2026-09-21 07:31:14'),
(14, 'G1 - INT0036', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Putri', 'PutriSetyaningsih@gmail.com', 'Putri Setyaningsih', 'siswa', 'Aktif', '$2y$12$0EbaPf2h3HiCqa/IiRHEMu8WYtX/eUoi83Nwg2JqRKxa/.HUSBGXu', NULL, '2026-09-21 03:22:50', '2026-09-24 08:05:01'),
(15, 'G1 - INT0043', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Shima', 'ShimaYuniarAlzara@gmail.com', 'Shima Yuniar Alzara', 'siswa', 'Aktif', '$2y$12$6HAiSH4aUGZU5szUSusP6ORGNYo79FQYmysqc.1Fprb51wT.1jmbC', NULL, '2026-10-16 09:22:50', '2026-10-16 09:22:50'),
(16, 'G1 - INT0044', '3501042702090001', 'Pacitan', '2009-02-27', 'Jalan Ikan Tenggiri Ngampel Ploso Pacitan Rt 04 Rw 07', '082245368544', 'SMKN 1 PACITAN', 'Jl. Letjen R. Suprapto No.53, Barean, Ploso, Kec. Pacitan, Kabupaten Pacitan, Jawa Timur 63515', 'NISN', '3093982644', '2026-06-22', '2026-12-11', 'dokumen-user/ktp/6qoIwnVRlJpndbTJNuYwP8FyFg2fwflKOUdYmJJT.png', 'dokumen-user/kartu-pelajar/nxRszGeTAOX0kxociID7KFddoF7GosqXoHYOMkwZ.png', 'test', 'testing@gmail.com', 'testing', 'siswa', 'Nonaktif', '$2y$12$1pP2sts6EazO3bkXi0qZTO6Ea9/KVP6W2gi/sUX9fI0GzbcG9vB0W', NULL, '2026-10-20 04:40:56', '2026-10-20 09:33:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_notif_unique` (`type`,`student_user_id`,`tanggal`),
  ADD KEY `admin_notifications_created_at_index` (`created_at`),
  ADD KEY `admin_notifications_type_index` (`type`),
  ADD KEY `admin_notifications_student_user_id_index` (`student_user_id`),
  ADD KEY `admin_notifications_tanggal_index` (`tanggal`),
  ADD KEY `admin_notifications_reference_id_index` (`reference_id`),
  ADD KEY `admin_notifications_read_at_index` (`read_at`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendances_user_id_index` (`user_id`),
  ADD KEY `attendances_status_kehadiran_index` (`status_kehadiran`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `laporans`
--
ALTER TABLE `laporans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporans_user_id_tanggal_laporan_index` (`user_id`,`tanggal_laporan`),
  ADD KEY `laporans_jenis_laporan_index` (`jenis_laporan`);

--
-- Indexes for table `laporan_fotos`
--
ALTER TABLE `laporan_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_fotos_laporan_id_index` (`laporan_id`);

--
-- Indexes for table `laporan_sales`
--
ALTER TABLE `laporan_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_sales_user_id_tanggal_index` (`user_id`,`tanggal`),
  ADD KEY `laporan_sales_provider_index` (`provider`),
  ADD KEY `laporan_sales_laporan_id_index` (`laporan_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_statistics`
--
ALTER TABLE `monthly_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_bulan_tahun` (`user_id`,`bulan`,`tahun`),
  ADD KEY `monthly_statistics_tahun_bulan_index` (`tahun`,`bulan`),
  ADD KEY `monthly_statistics_user_id_index` (`user_id`);

--
-- Indexes for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `password_reset_otps_email_id_index` (`email`,`id`),
  ADD KEY `password_reset_otps_email_index` (`email`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_user_id_unique` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=474;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporans`
--
ALTER TABLE `laporans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `laporan_fotos`
--
ALTER TABLE `laporan_fotos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `laporan_sales`
--
ALTER TABLE `laporan_sales`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `monthly_statistics`
--
ALTER TABLE `monthly_statistics`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `laporans`
--
ALTER TABLE `laporans`
  ADD CONSTRAINT `laporans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `laporan_fotos`
--
ALTER TABLE `laporan_fotos`
  ADD CONSTRAINT `laporan_fotos_laporan_id_foreign` FOREIGN KEY (`laporan_id`) REFERENCES `laporans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_sales`
--
ALTER TABLE `laporan_sales`
  ADD CONSTRAINT `laporan_sales_laporan_id_foreign` FOREIGN KEY (`laporan_id`) REFERENCES `laporans` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `laporan_sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `monthly_statistics`
--
ALTER TABLE `monthly_statistics`
  ADD CONSTRAINT `monthly_statistics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
