-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 11, 2025 at 10:54 PM
-- Server version: 10.3.16-MariaDB
-- PHP Version: 7.2.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `etracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` bigint(20) NOT NULL,
  `commitment_id` bigint(20) DEFAULT NULL,
  `commenter_name` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `commitment_id`, `commenter_name`, `comment`, `likes`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'John Citizen', 'Great progress on the road construction!', 10, NULL, NULL, NULL),
(2, 1, 'Hassan Bello', 'Masha Allah... Allah ya saka muku da alkairi', 0, NULL, '2024-02-03 18:47:12', '2024-02-03 18:47:12'),
(3, 1, 'Kabiru Mage', 'It is effective', 0, NULL, '2024-02-11 10:56:35', '2024-02-11 10:56:35'),
(4, 1, 'Uztaz Kamal', 'Alhamdulillah', 0, NULL, '2024-02-11 10:56:57', '2024-02-11 10:56:57'),
(5, 1, 'Kabiru Mage', 'hjhb h', 0, NULL, '2024-03-04 19:24:03', '2024-03-04 19:24:03'),
(6, 1, 'Jabiru Ahmed', 'hello', 0, NULL, '2024-04-17 14:33:13', '2024-04-17 14:33:13');

-- --------------------------------------------------------

--
-- Table structure for table `comment_images`
--

CREATE TABLE `comment_images` (
  `id` bigint(20) NOT NULL,
  `comment_id` bigint(20) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `comment_images`
--

INSERT INTO `comment_images` (`id`, `comment_id`, `image_path`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'comment_images/great_progress.jpg', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `commitments`
--

CREATE TABLE `commitments` (
  `id` bigint(20) NOT NULL,
  `sector_id` bigint(20) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `duration_in_days` int(11) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `commitments`
--

INSERT INTO `commitments` (`id`, `sector_id`, `name`, `type`, `start_date`, `duration_in_days`, `end_date`, `status`, `budget`, `description`, `img_url`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Highway Expansion Project', 'Road Construction', '2024-01-01', 180, '2024-06-30', 'In Progress', '5000000.00', 'Expanding key highways to reduce traffic congestion.', '1707315627.png', NULL, '2024-01-20 11:51:39', '2024-02-08 08:34:13'),
(2, 1, 'Bridge Rehabilitation Initiative', 'Infrastructure Enhancement', '2024-02-15', 150, '2024-07-15', 'Not Started', '3000000.00', 'Revitalizing and strengthening key bridges for safety and durability.', '1707315628.png', NULL, '2024-01-20 11:51:39', '2024-02-08 08:37:21'),
(3, 1, 'Community Park Redevelopment', 'Community Enhancement', '2024-03-10', 120, '2024-07-08', 'Completed', '2000000.00', 'Transforming community parks for recreation and social gatherings.', '1707315629.png', NULL, '2024-01-20 11:51:39', '2024-02-08 08:37:28'),
(4, 1, 'Smart Street Lighting Project', 'Technology Integration', '2024-04-25', 90, '2024-07-24', 'Not Started', '1500000.00', 'Implementing energy-efficient street lighting for sustainability.', '1707315630.png', NULL, '2024-01-20 11:51:39', '2024-02-08 08:37:35'),
(5, 2, 'Medical Facility Expansion', 'Infrastructure Enhancement', '2024-01-10', 180, '2024-07-08', 'In Progress', '4500000.00', 'Expanding healthcare facilities for better patient care.', '1707315628.png', NULL, '2024-01-20 11:54:23', '2024-02-08 08:38:14'),
(6, 2, 'Public Health Awareness Campaign', 'Community Engagement', '2024-03-01', 120, '2024-06-29', 'Completed', '2500000.00', 'Educating the community on public health and well-being.', NULL, NULL, '2024-01-20 11:54:23', '2024-01-21 18:27:03'),
(7, 2, 'Telemedicine Implementation', 'Technology Integration', '2024-05-15', 90, '2024-08-13', 'Not Started', '3500000.00', 'Introducing telemedicine services for remote patient consultations.', NULL, NULL, '2024-01-20 11:54:23', '2024-01-20 11:54:23'),
(8, 2, 'Medical Research Grant Program', 'Research and Innovation', '2024-07-20', 150, '2024-12-15', 'Not Started', '3000000.00', 'Supporting medical research for breakthroughs in healthcare.', NULL, NULL, '2024-01-20 11:54:23', '2024-01-20 11:54:23'),
(9, 2, 'Medical Facility Expansion', 'Infrastructure Enhancement', '2024-01-10', 180, '2024-07-08', 'Completed', '4500000.00', 'Expanding healthcare facilities for better patient care.', NULL, NULL, '2024-01-20 11:54:23', '2024-01-21 18:27:11'),
(10, 2, 'Public Health Awareness Campaign', 'Community Engagement', '2024-03-01', 120, '2024-06-29', 'Not Started', '2500000.00', 'Educating the community on public health and well-being.', NULL, NULL, '2024-01-20 11:54:23', '2024-01-20 11:54:23'),
(11, 2, 'Telemedicine Implementation', 'Technology Integration', '2024-05-15', 90, '2024-08-13', 'Not Started', '3500000.00', 'Introducing telemedicine services for remote patient consultations.', NULL, NULL, '2024-01-20 11:54:23', '2024-01-20 11:54:23'),
(12, 2, 'Medical Research Grant Program', 'Research and Innovation', '2024-07-20', 150, '2024-12-15', 'Completed', '3000000.00', 'Supporting medical research for breakthroughs in healthcare.', NULL, NULL, '2024-01-20 11:54:23', '2024-01-21 18:27:22'),
(13, 3, 'Software Development Project', 'Technology Innovation', '2024-01-15', 120, '2024-05-15', 'In Progress', '6000000.00', 'Developing a cutting-edge software solution.', '1707315629.png', NULL, '2024-01-20 11:59:29', '2024-02-08 08:38:24'),
(14, 3, 'IT Infrastructure Upgrade', 'Infrastructure Enhancement', '2024-03-01', 90, '2024-05-30', 'Not Started', '3000000.00', 'Upgrading the organization\'s IT infrastructure.', NULL, NULL, '2024-01-20 11:59:29', '2024-01-20 11:59:29'),
(15, 3, 'Cybersecurity Implementation', 'Security Enhancement', '2024-06-01', 180, '2024-11-28', 'Not Started', '4500000.00', 'Enhancing cybersecurity measures for data protection.', NULL, NULL, '2024-01-20 11:59:29', '2024-01-20 11:59:29'),
(16, 3, 'Employee Training Program', 'Skill Development', '2024-09-01', 60, '2024-10-31', 'Not Started', '2000000.00', 'Training employees on the latest technologies.', NULL, NULL, '2024-01-20 11:59:29', '2024-01-20 11:59:29'),
(17, 1, 'Dutse Housing Estate', 'Essential', '2024-02-10', 325, '2024-12-31', 'Not Started', '6000000.00', 'Construction of 20 Housing Units', '1707465568.jpg', NULL, '2024-02-09 12:59:28', '2024-02-09 12:59:28');

-- --------------------------------------------------------

--
-- Table structure for table `commitment_budgets`
--

CREATE TABLE `commitment_budgets` (
  `id` bigint(20) NOT NULL,
  `commitment_id` bigint(20) NOT NULL,
  `year` int(11) NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `last_modified_by` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `commitment_budgets`
--

INSERT INTO `commitment_budgets` (`id`, `commitment_id`, `year`, `amount`, `last_modified_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2021, '120000', 1, NULL, NULL),
(2, 2, 2021, '123000', 1, NULL, NULL),
(3, 1, 2024, '50000', 0, '2024-01-08 17:27:37', '2024-01-08 17:27:37'),
(4, 4, 2024, '100000000', 0, '2024-01-08 17:27:57', '2024-01-08 17:27:57'),
(5, 3, 2024, '4500000', 0, '2024-01-15 00:34:02', '2024-01-15 00:34:02');

-- --------------------------------------------------------

--
-- Table structure for table `day_increment`
--

CREATE TABLE `day_increment` (
  `id` bigint(20) NOT NULL,
  `commitment_id` bigint(20) NOT NULL,
  `deliverable_id` bigint(20) NOT NULL,
  `days` int(11) NOT NULL,
  `reason` text NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `deliverables`
--

CREATE TABLE `deliverables` (
  `id` bigint(20) NOT NULL,
  `commitment_id` bigint(20) DEFAULT NULL,
  `deliverable` varchar(255) DEFAULT NULL,
  `budget` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `last_kip_id` bigint(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `deliverables`
--

INSERT INTO `deliverables` (`id`, `commitment_id`, `deliverable`, `budget`, `start_date`, `end_date`, `last_kip_id`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Earthwork and Grading', '2000000.00', '2024-01-01', '2024-03-31', NULL, 'In Progress', NULL, '2024-01-20 11:52:00', '2024-01-20 11:52:00'),
(2, 1, 'Asphalt Paving', '3000000.00', '2024-04-01', '2024-06-30', NULL, 'Not Started', NULL, '2024-01-20 11:52:00', '2024-01-20 11:52:00'),
(3, 1, 'Traffic Sign Installation', '1000000.00', '2024-07-01', '2024-08-31', NULL, 'Not Started', NULL, '2024-01-20 11:52:00', '2024-01-20 11:52:00'),
(4, 1, 'Landscaping and Beautification', '500000.00', '2024-09-01', '2024-10-31', NULL, 'Not Started', NULL, '2024-01-20 11:52:00', '2024-01-20 11:52:00'),
(5, 5, 'Building Construction', '2000000.00', '2024-01-10', '2024-07-08', NULL, 'In Progress', NULL, '2024-01-20 11:55:14', '2024-01-20 11:55:14'),
(6, 5, 'Medical Equipment Procurement', '1500000.00', '2024-02-20', '2024-05-15', NULL, 'Not Started', NULL, '2024-01-20 11:55:14', '2024-01-20 11:55:14'),
(7, 5, 'Staff Recruitment and Training', '1000000.00', '2024-05-01', '2024-08-01', NULL, 'Not Started', NULL, '2024-01-20 11:55:14', '2024-01-20 11:55:14'),
(8, 5, 'Facility Infrastructure Upgrade', '1000000.00', '2024-08-15', '2024-10-31', NULL, 'Not Started', NULL, '2024-01-20 11:55:14', '2024-01-20 11:55:14'),
(9, 13, 'Software Prototype Development', '2500000.00', '2024-01-15', '2024-03-15', NULL, 'In Progress', NULL, '2024-01-20 12:00:31', '2024-01-20 12:00:31'),
(10, 13, 'Beta Testing', '2000000.00', '2024-03-20', '2024-04-30', NULL, 'Not Started', NULL, '2024-01-20 12:00:31', '2024-01-20 12:00:31'),
(11, 13, 'Full-scale Development', '1000000.00', '2024-05-01', '2024-05-15', NULL, 'Not Started', NULL, '2024-01-20 12:00:31', '2024-01-20 12:00:31'),
(12, 13, 'Software Launch', '500000.00', '2024-05-20', '2024-05-31', NULL, 'Not Started', NULL, '2024-01-20 12:00:31', '2024-01-20 12:00:31'),
(13, 2, 'Clearing', '20000', '2024-02-01', '2024-05-10', NULL, 'In Progress', NULL, '2024-02-13 14:06:34', '2024-02-13 14:06:34');

-- --------------------------------------------------------

--
-- Table structure for table `expenditures`
--

CREATE TABLE `expenditures` (
  `id` bigint(20) NOT NULL,
  `commitment_id` bigint(20) NOT NULL,
  `deliverable_id` bigint(20) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `fund_releases`
--

CREATE TABLE `fund_releases` (
  `id` bigint(20) NOT NULL,
  `commitment_id` bigint(20) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `released_amount` decimal(15,2) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `fund_releases`
--

INSERT INTO `fund_releases` (`id`, `commitment_id`, `release_date`, `released_amount`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-01-15', '2000000.00', NULL, NULL, NULL),
(2, 1, '2024-04-01', '3000000.00', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpis`
--

CREATE TABLE `kpis` (
  `id` bigint(20) NOT NULL,
  `deliverable_id` bigint(20) DEFAULT NULL,
  `kpi` varchar(255) DEFAULT NULL,
  `target_value` varchar(64) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `unit_of_measurement` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kpis`
--

INSERT INTO `kpis` (`id`, `deliverable_id`, `kpi`, `target_value`, `start_date`, `end_date`, `unit_of_measurement`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Road Grading Speed', '20', '2024-01-01', '2024-03-31', 'meters per day', NULL, '2024-01-18 15:09:49', '2024-01-18 15:09:49'),
(2, 1, 'Average Daily Earthwork Progress', '5000', '2024-01-01', '2024-03-31', 'cubic meters per day', NULL, '2024-01-20 11:53:19', '2024-01-20 11:53:19'),
(3, 2, 'Asphalt Thickness Compliance', '95', '2024-04-01', '2024-06-30', 'percentage', NULL, '2024-01-20 11:53:19', '2024-02-13 11:18:12'),
(4, 3, 'Timely Traffic Sign Installation', '100', '2024-07-01', '2024-08-31', 'percentage', NULL, '2024-01-20 11:53:19', '2024-02-13 11:18:22'),
(5, 5, 'Construction Progress', '60', '2024-01-10', '2024-07-08', 'percentage', NULL, '2024-01-20 11:56:04', '2024-02-13 11:18:21'),
(6, 6, 'Equipment Procurement Completion', '40', '2024-02-20', '2024-05-15', 'percentage', NULL, '2024-01-20 11:56:04', '2024-02-13 11:18:20'),
(7, 7, 'Staff Training Completion', '20', '2024-05-01', '2024-08-01', 'percentage', NULL, '2024-01-20 11:56:04', '2024-02-13 11:18:18'),
(8, 9, 'Prototype Completion', '50', '2024-02-15', '2024-03-15', 'percentage', NULL, '2024-01-20 12:01:25', '2024-02-13 11:18:17'),
(9, 10, 'Beta Testing Feedback', '0', '2024-04-01', '2024-04-30', 'percentage', NULL, '2024-01-20 12:01:25', '2024-02-13 11:18:16'),
(10, 11, 'Full-scale Development Progress', '0', '2024-05-01', '2024-05-15', 'percentage', NULL, '2024-01-20 12:01:25', '2024-02-13 11:18:15'),
(11, 4, 'Landscaping speed', '30', '2024-01-01', '2024-02-09', 'metres per day', NULL, '2024-01-21 18:23:09', '2024-01-21 18:23:09'),
(12, 3, 'Timely Traffic Sign Installation Training', '100', '2024-07-01', '2024-08-31', 'percentage', NULL, '2024-01-20 11:53:19', '2024-02-13 11:18:14'),
(13, 1, 'Grading acuracy', '0', '2024-02-01', '2024-06-16', 'Acuracy', NULL, '2024-02-13 14:43:24', '2024-02-13 14:43:24'),
(14, 13, 'Grading acuracy', '3334', '2024-02-01', '2024-03-10', 'Acuracy', NULL, '2024-02-14 14:24:55', '2024-02-14 14:24:55');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_targets`
--

CREATE TABLE `kpi_targets` (
  `id` bigint(20) NOT NULL,
  `kpi_id` bigint(20) NOT NULL,
  `year` int(11) NOT NULL,
  `target` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `kpi_targets`
--

INSERT INTO `kpi_targets` (`id`, `kpi_id`, `year`, `target`, `created_at`, `updated_at`) VALUES
(4, 1, 2024, '7', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(5, 2, 2024, '900', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(6, 3, 2024, '', '2024-02-13 11:07:39', '2024-02-14 14:23:50'),
(7, 4, 2024, '40', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(8, 5, 2024, '20', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(9, 6, 2024, '10', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(10, 7, 2024, '10', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(11, 8, 2024, '20', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(12, 9, 2024, '1', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(13, 10, 2024, '1', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(14, 11, 2024, '10', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(15, 12, 2024, '30', '2024-02-13 11:07:39', '2024-02-13 11:07:39'),
(19, 13, 2024, '123', '2024-02-14 14:23:50', '2024-02-14 14:23:58'),
(20, 14, 2024, '900', '2024-02-14 14:24:55', '2024-02-14 14:25:48'),
(21, 14, 2023, '', '2024-02-14 15:16:11', '2024-02-14 15:16:11');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2016_06_01_000001_create_oauth_auth_codes_table', 1),
(2, '2016_06_01_000002_create_oauth_access_tokens_table', 1),
(3, '2016_06_01_000003_create_oauth_refresh_tokens_table', 1),
(4, '2016_06_01_000004_create_oauth_clients_table', 1),
(5, '2016_06_01_000005_create_oauth_personal_access_clients_table', 1),
(6, '2019_08_19_000000_create_failed_jobs_table', 1),
(7, '2019_12_14_000001_create_personal_access_tokens_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_files`
--

CREATE TABLE `model_files` (
  `id` bigint(20) NOT NULL,
  `model_id` bigint(20) NOT NULL,
  `model_type` enum('Sector','Commitment','Deliverables','KPI','Performance') NOT NULL,
  `file_id` bigint(20) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `type` enum('Personal Message','Payment Received','Payment Made','Pocket Invitation','Payment Reminder','Item Selection','Request Made','Request Approved','User Joined') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` bigint(20) NOT NULL DEFAULT 0,
  `model_id` bigint(20) NOT NULL,
  `status` enum('Read','Not Read') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Not Read',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_access_tokens`
--

INSERT INTO `oauth_access_tokens` (`id`, `user_id`, `client_id`, `name`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES
('0b7c9ce022c99585a16247eb0546819c2b2c073688e343c4e3e20c22db22ae6ea2ddde6e1d46ca96', 1, 1, 'eTrackerX8nE@9', '[]', 0, '2024-04-14 15:43:11', '2024-04-14 15:43:11', '2025-04-14 16:43:11'),
('2b196acbf4e89a221c3d6ad6c450fd4774d546da328aed4ce799232d79b1eddcb22ca181f225f3e0', 1, 1, 'eTrackerX8nE@9', '[]', 0, '2024-03-26 03:41:00', '2024-03-26 03:41:00', '2025-03-26 04:41:00'),
('c759f035274d9fa14b409b449545966867c797c17bb96afc324875bd7e9f688292e177fd8001b586', 1, 1, 'eTrackerX8nE@9', '[]', 0, '2024-03-26 03:52:27', '2024-03-26 03:52:27', '2025-03-26 04:52:27'),
('dfea8176c4b701a29ee20f23c6d9c9dca6794cd40ce89cf8717142d3b6a910ff3dcb6e988abd6f5b', 1, 1, 'eTrackerX8nE@9', '[]', 1, '2024-03-26 04:02:15', '2024-04-14 15:41:35', '2025-03-26 05:02:15'),
('fd46dca925ffcd7ab0b8ec36d48b3d767619f90d665ac18648de76caa8137c91651e94bb1bf73c19', 1, 1, 'eTrackerX8nE@9', '[]', 0, '2024-03-26 02:35:20', '2024-03-26 02:35:20', '2025-03-26 03:35:20');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_auth_codes`
--

CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_clients`
--

CREATE TABLE `oauth_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redirect` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_clients`
--

INSERT INTO `oauth_clients` (`id`, `user_id`, `name`, `secret`, `provider`, `redirect`, `personal_access_client`, `password_client`, `revoked`, `created_at`, `updated_at`) VALUES
(1, NULL, 'eTrackerX8nE', '8qtkrsqRe1HnGENp0sQy2dg0PKKtZaGHrcJabtdr', NULL, 'http://localhost', 1, 0, 0, '2024-03-26 02:35:11', '2024-03-26 02:35:11');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_personal_access_clients`
--

CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_personal_access_clients`
--

INSERT INTO `oauth_personal_access_clients` (`id`, `client_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-03-26 02:35:11', '2024-03-26 02:35:11');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_refresh_tokens`
--

CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_trackings`
--

CREATE TABLE `performance_trackings` (
  `id` bigint(20) NOT NULL,
  `kpi_id` bigint(20) DEFAULT NULL,
  `quarter` int(11) NOT NULL,
  `milestone` varchar(64) NOT NULL,
  `tracking_date` date DEFAULT NULL,
  `year` int(11) NOT NULL,
  `actual_value` varchar(200) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `delivery_department_value` varchar(200) DEFAULT NULL,
  `delivery_department_remark` text DEFAULT NULL,
  `confirmation_status` enum('Confirmed','Not Confirmed','Rejected') DEFAULT 'Not Confirmed',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `performance_trackings`
--

INSERT INTO `performance_trackings` (`id`, `kpi_id`, `quarter`, `milestone`, `tracking_date`, `year`, `actual_value`, `remarks`, `delivery_department_value`, `delivery_department_remark`, `confirmation_status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '22', '2024-02-01', 2024, '20', 'On track', '20', 'Met target', 'Confirmed', NULL, '2024-01-18 15:09:49', '2024-03-15 11:38:54'),
(3, 2, 1, '95', '2024-05-10', 2024, '93', NULL, '93', NULL, 'Confirmed', NULL, '2024-01-20 11:53:52', '2024-03-15 11:39:01'),
(4, 3, 1, '100', '2024-08-20', 2024, '100', NULL, '100', NULL, 'Confirmed', NULL, '2024-01-20 11:53:52', '2024-03-15 11:39:01'),
(5, 5, 1, '68', '2024-03-01', 2024, '65', NULL, '65', NULL, 'Confirmed', NULL, '2024-01-20 11:56:43', '2024-03-15 11:39:01'),
(6, 6, 1, '35', '2024-04-15', 2024, '35', NULL, '35', NULL, 'Confirmed', NULL, '2024-01-20 11:56:43', '2024-03-15 11:39:01'),
(7, 7, 1, '20', '2024-06-10', 2024, '15', NULL, '15', NULL, 'Confirmed', NULL, '2024-01-20 11:56:43', '2024-03-15 11:39:01'),
(8, 8, 1, '49', '2024-02-28', 2024, '45', NULL, '45', NULL, 'Confirmed', NULL, '2024-01-20 12:03:06', '2024-03-15 11:39:01'),
(9, 9, 1, '50', '2024-04-15', 2024, '41', NULL, NULL, NULL, '', NULL, '2024-01-20 12:03:06', '2024-03-15 11:39:01'),
(10, 10, 1, '76', '2024-05-10', 2024, '66', NULL, NULL, NULL, '', NULL, '2024-01-20 12:03:06', '2024-03-15 11:39:01'),
(11, 11, 1, '65', '2024-01-22', 2024, '30', 'qwerty', NULL, NULL, 'Not Confirmed', NULL, '2024-01-22 08:59:04', '2024-03-15 11:39:01'),
(12, 1, 2, '1232', '2024-04-08', 2024, '1200', 'hmmm', '1200', 'vgf', 'Confirmed', NULL, '2024-01-30 14:08:44', '2024-03-15 11:39:01'),
(13, 2, 2, '95', '2024-01-19', 2024, '94', 'yay', NULL, NULL, 'Not Confirmed', NULL, '2024-01-30 14:09:32', '2024-03-15 11:39:01'),
(14, 1, 3, '123', '2024-03-01', 2024, '1200', 'dscsd d sdjgs jd', '121', 'hdfgjvh', 'Confirmed', NULL, '2024-03-04 19:48:12', '2024-03-15 11:54:45'),
(15, 1, 4, '900', '2024-03-03', 2024, '900', 'kjhvhbj,', '700', 'mhvjhjb', 'Confirmed', NULL, '2024-03-04 20:19:07', '2024-03-15 11:54:50');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `id` bigint(20) NOT NULL,
  `sector_name` varchar(255) DEFAULT NULL,
  `sector_head_id` bigint(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`id`, `sector_name`, `sector_head_id`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Infrastructure', 2, 'Responsible for road construction and maintenance task', '2024-01-18 15:07:05', '2024-02-06 16:31:22'),
(2, 'Healthcare', 2, 'Dedicated to enhancing healthcare services and facilities.', '2024-01-20 11:48:51', '2024-01-20 11:48:51'),
(3, 'Technology', 3, 'Driving technological advancements and innovation.', '2024-01-20 11:48:51', '2024-01-20 11:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `sector_budgets`
--

CREATE TABLE `sector_budgets` (
  `id` bigint(20) NOT NULL,
  `sector_id` bigint(20) NOT NULL,
  `year` int(11) NOT NULL,
  `amount` varchar(64) NOT NULL,
  `last_modified_by` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sector_budgets`
--

INSERT INTO `sector_budgets` (`id`, `sector_id`, `year`, `amount`, `last_modified_by`, `created_at`, `updated_at`) VALUES
(1, 3, 2024, '23000000', 1, NULL, NULL),
(2, 2, 2024, '244500000', 0, '2024-01-08 16:22:20', '2024-01-08 16:22:20'),
(4, 1, 2024, '50000000', 0, '2024-01-14 19:29:46', '2024-01-14 19:29:46');

-- --------------------------------------------------------

--
-- Table structure for table `sector_files`
--

CREATE TABLE `sector_files` (
  `id` bigint(20) NOT NULL,
  `sector_id` bigint(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(200) NOT NULL,
  `type` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sector_files`
--

INSERT INTO `sector_files` (`id`, `sector_id`, `title`, `url`, `type`, `created_at`, `updated_at`) VALUES
(1, 2, 'Employement Notice', 'viewNoti.jpg', 'jpg', NULL, NULL),
(2, 2, 'Data Files', 'uploads/1704733424_certificate.png', 'image', '2024-01-08 16:03:44', '2024-01-08 16:03:44'),
(3, 2, 'House File', 'uploads/1704736433_certificate.png', 'image', '2024-01-08 16:53:53', '2024-01-08 16:53:53'),
(4, 2, 'hujjj', 'uploads/1704736456_WhatsApp Image 2024-01-07 at 8.02.16 AM.jpeg', 'image', '2024-01-08 16:54:16', '2024-01-08 16:54:16'),
(5, 2, 'Certificate', 'uploads/1704744667_certificate.png', 'image', '2024-01-09 01:11:07', '2024-01-09 01:11:07');

-- --------------------------------------------------------

--
-- Table structure for table `sector_heads`
--

CREATE TABLE `sector_heads` (
  `id` bigint(20) NOT NULL,
  `sector_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sector_heads`
--

INSERT INTO `sector_heads` (`id`, `sector_id`, `user_id`, `date_from`, `date_to`, `created_at`, `updated_at`) VALUES
(1, 2, 2, '2022-11-01', '2025-08-31', NULL, NULL),
(2, 3, 3, '2023-10-01', '2024-01-30', '2024-01-08 04:12:56', '2024-01-08 04:12:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `full_name` varchar(222) NOT NULL,
  `email` varchar(222) NOT NULL,
  `phone_number` varchar(32) NOT NULL,
  `role` int(11) NOT NULL,
  `password` varchar(233) NOT NULL,
  `image_url` varchar(233) NOT NULL,
  `token` text DEFAULT NULL,
  `fcm_token` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone_number`, `role`, `password`, `image_url`, `token`, `fcm_token`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Umar Namadi', 'governor@example.com', '+2348065993421', 1, '$2y$12$jgFSqTfEULt3IFn.IbWrreOCvPZ4gsDF.wKsBrAra.7gcQlqPLEFC', '5.jpg', NULL, NULL, NULL, NULL, '2024-02-08 21:16:35'),
(2, 'Bello Haruna', 'sectorhead@example.com', '+2348065993421', 2, '$2y$12$jgFSqTfEULt3IFn.IbWrreOCvPZ4gsDF.wKsBrAra.7gcQlqPLEFC', 'sectorhead.jpg', NULL, NULL, NULL, NULL, '2024-02-08 21:09:32'),
(3, 'Delivery Department User', 'delivery@example.com', '', 3, '$2y$12$jgFSqTfEULt3IFn.IbWrreOCvPZ4gsDF.wKsBrAra.7gcQlqPLEFC', 'delivery.jpg', NULL, NULL, NULL, NULL, NULL),
(4, 'Alice Johnson', 'admin@example.com', '+2348065993421', 0, '$2y$12$jgFSqTfEULt3IFn.IbWrreOCvPZ4gsDF.wKsBrAra.7gcQlqPLEFC', 'admin.jpg', NULL, NULL, NULL, NULL, '2024-02-08 21:13:25'),
(5, 'Matazu Ibrahim', 'systemadmin@example.com', '785422684555', 0, '$2y$12$oPwYTzvC3XDIUr8KLjisxeFovwUT.i4YOM/Ew1X86Gr.jBU5xZKbq', '', NULL, NULL, NULL, '2024-01-31 21:15:18', '2024-02-08 21:14:01'),
(6, 'Jamilu Garba', 'jamilu@gmail.com', '+2348065993421', 0, '$2y$12$hv2aITOpQ/KH05zfpxM46eTD7w5c7J4ie9X23pGEN5MsvPBZ9kGEy', '', NULL, NULL, NULL, '2024-02-08 21:06:19', '2024-02-09 02:02:19'),
(7, 'Haruna Adamu', 'haruna@gmail.com', '+2348065993425', 0, '$2y$12$z3FUy8n8j2zuRCJWPjnC..ZbrjKvNxdqq1x/7aecRciQCJOrMR3/S', '', NULL, NULL, NULL, '2024-02-09 01:57:02', '2024-02-09 01:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `role` enum('Governor','Sector Head','Sector Admin','Delivery Department','System Admin') NOT NULL,
  `target_entity` enum('System','State','Sector','Project','Deliverable') NOT NULL,
  `entity_id` bigint(20) NOT NULL COMMENT '0 for all',
  `role_status` enum('Active','Revoked') NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role`, `target_entity`, `entity_id`, `role_status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Governor', 'State', 1, 'Active', NULL, NULL, NULL),
(2, 2, 'Sector Head', 'Sector', 1, 'Active', NULL, NULL, NULL),
(3, 3, 'Delivery Department', 'Deliverable', 0, 'Active', NULL, NULL, NULL),
(4, 4, 'Sector Admin', 'Sector', 1, 'Active', NULL, NULL, NULL),
(5, 5, 'System Admin', 'System', 1, 'Active', NULL, '2024-01-31 21:15:18', '2024-01-31 21:15:18'),
(6, 6, 'System Admin', 'System', 1, 'Active', NULL, '2024-02-08 21:06:19', '2024-02-08 21:06:19'),
(7, 7, 'Sector Head', 'Sector', 2, 'Active', NULL, '2024-02-09 01:57:02', '2024-02-09 01:57:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`commitment_id`);

--
-- Indexes for table `comment_images`
--
ALTER TABLE `comment_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comment_id` (`comment_id`);

--
-- Indexes for table `commitments`
--
ALTER TABLE `commitments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indexes for table `commitment_budgets`
--
ALTER TABLE `commitment_budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commitment_id` (`commitment_id`),
  ADD KEY `year` (`year`);

--
-- Indexes for table `day_increment`
--
ALTER TABLE `day_increment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commitment_id` (`commitment_id`),
  ADD KEY `delivery_id` (`deliverable_id`);

--
-- Indexes for table `deliverables`
--
ALTER TABLE `deliverables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commitment_id` (`commitment_id`),
  ADD KEY `last_kip_id` (`last_kip_id`);

--
-- Indexes for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commitment_id` (`commitment_id`),
  ADD KEY `deliverable_id` (`deliverable_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fund_releases`
--
ALTER TABLE `fund_releases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commitment_id` (`commitment_id`);

--
-- Indexes for table `kpis`
--
ALTER TABLE `kpis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deliverable_id` (`deliverable_id`);

--
-- Indexes for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kpi_id` (`kpi_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_files`
--
ALTER TABLE `model_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `model_id` (`model_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `model_id` (`model_id`),
  ADD KEY `type` (`type`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_access_tokens_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_auth_codes`
--
ALTER TABLE `oauth_auth_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_auth_codes_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_clients_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`);

--
-- Indexes for table `performance_trackings`
--
ALTER TABLE `performance_trackings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kpi_id` (`kpi_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_head_id` (`sector_head_id`);

--
-- Indexes for table `sector_budgets`
--
ALTER TABLE `sector_budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indexes for table `sector_files`
--
ALTER TABLE `sector_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indexes for table `sector_heads`
--
ALTER TABLE `sector_heads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `comment_images`
--
ALTER TABLE `comment_images`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `commitments`
--
ALTER TABLE `commitments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `commitment_budgets`
--
ALTER TABLE `commitment_budgets`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `day_increment`
--
ALTER TABLE `day_increment`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliverables`
--
ALTER TABLE `deliverables`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `expenditures`
--
ALTER TABLE `expenditures`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fund_releases`
--
ALTER TABLE `fund_releases`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kpis`
--
ALTER TABLE `kpis`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `model_files`
--
ALTER TABLE `model_files`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `performance_trackings`
--
ALTER TABLE `performance_trackings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sector_budgets`
--
ALTER TABLE `sector_budgets`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sector_files`
--
ALTER TABLE `sector_files`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sector_heads`
--
ALTER TABLE `sector_heads`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`commitment_id`) REFERENCES `commitments` (`id`);

--
-- Constraints for table `comment_images`
--
ALTER TABLE `comment_images`
  ADD CONSTRAINT `comment_images_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`);

--
-- Constraints for table `commitments`
--
ALTER TABLE `commitments`
  ADD CONSTRAINT `commitments_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`);

--
-- Constraints for table `day_increment`
--
ALTER TABLE `day_increment`
  ADD CONSTRAINT `day_increment_ibfk_1` FOREIGN KEY (`commitment_id`) REFERENCES `commitments` (`id`),
  ADD CONSTRAINT `day_increment_ibfk_2` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`);

--
-- Constraints for table `deliverables`
--
ALTER TABLE `deliverables`
  ADD CONSTRAINT `deliverables_ibfk_1` FOREIGN KEY (`commitment_id`) REFERENCES `commitments` (`id`);

--
-- Constraints for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD CONSTRAINT `expenditures_ibfk_1` FOREIGN KEY (`commitment_id`) REFERENCES `commitments` (`id`),
  ADD CONSTRAINT `expenditures_ibfk_2` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`);

--
-- Constraints for table `fund_releases`
--
ALTER TABLE `fund_releases`
  ADD CONSTRAINT `fund_releases_ibfk_1` FOREIGN KEY (`commitment_id`) REFERENCES `commitments` (`id`);

--
-- Constraints for table `kpis`
--
ALTER TABLE `kpis`
  ADD CONSTRAINT `kpis_ibfk_1` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`);

--
-- Constraints for table `performance_trackings`
--
ALTER TABLE `performance_trackings`
  ADD CONSTRAINT `performance_trackings_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`);

--
-- Constraints for table `sectors`
--
ALTER TABLE `sectors`
  ADD CONSTRAINT `sectors_ibfk_1` FOREIGN KEY (`sector_head_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
