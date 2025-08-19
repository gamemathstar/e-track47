-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql204.infinityfree.com
-- Generation Time: Aug 14, 2025 at 06:18 PM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Import your original SQL file here
-- (You'll need to copy the content of if0_38970114_etracker.sql here)

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38970114_etracker`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `commitments`
--

INSERT INTO `commitments` (`id`, `sector_id`, `name`, `type`, `start_date`, `duration_in_days`, `end_date`, `status`, `budget`, `description`, `img_url`, `deleted_at`, `created_at`, `updated_at`) VALUES
(19, 4, 'Work with the relevant agencies to create Jigawa State Joint Intelligence Fusion Center for command, communication and control under the relevant agencies to have a Single Security Coordination Center.', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Work with the relevant agencies to create Jigawa State Joint Intelligence Fusion Center for command, communication and control under the relevant agencies to have a Single Security Coordination Center.', '1752756840.jpg', NULL, '2025-07-17 19:54:00', '2025-07-17 19:54:00'),
(20, 4, 'Continued support for community policing and strengthening of preventive mechanisms with effective enforcement of the relevant criminal laws in justice system administration.', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Continued support for community policing and strengthening of preventive mechanisms with effective enforcement of the relevant criminal laws in justice system administration.', '1752756899.jpg', NULL, '2025-07-17 19:54:59', '2025-07-17 19:54:59'),
(21, 4, 'Collaboration with the relevant Federal Agencies in dealing with security threats and youth drug abuse through the nefarious activities of drug dealers in the Communities.', 'Essential', '2025-07-02', 168, '2025-12-17', 'In Progress', NULL, 'Collaboration with the relevant Federal Agencies in dealing with security threats and youth drug abuse through the nefarious activities of drug dealers in the Communities.', '1752757029.jpg', NULL, '2025-07-17 19:57:09', '2025-07-17 19:57:09'),
(22, 4, 'Empower the traditional institutions to maintain and regularly update the data of residents and newcomers to curtail and prevent the infiltration of', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Empower the traditional institutions to maintain and regularly update the data of residents and newcomers to curtail and prevent the infiltration of', '1752757101.jpg', NULL, '2025-07-17 19:58:21', '2025-07-17 19:58:21'),
(23, 4, 'Facilitate partinerships between law enforcementagencies and residents, there by improving trust between the law enforcement agencies and residenta and ignite the free flow of accurate information from the residents regarding criminal activities in thier ', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Facilitate partinerships between law enforcementagencies and residents, there by improving trust between the law enforcement agencies and residenta and ignite the free flow of accurate information from the residents regarding criminal activities in thier community', '1752757170.jpg', NULL, '2025-07-17 19:59:30', '2025-07-17 19:59:30'),
(24, 4, 'Intergrate the various local security outfits and equip them with relavent non-lethal tools and better support in terms of logistics training and intellegece as part of the stratgy to improve our vigillance service.', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Intergrate the various local security outfits and equip them with relavent non-lethal tools and better support in terms of logistics training and intellegece as part of the stratgy to improve our vigillance service.', '1752757235.jpg', NULL, '2025-07-17 20:00:35', '2025-07-17 20:00:35'),
(25, 4, 'Introduce and Operationalize Jigawa Security Trust fund to increase financing and support to national and local security forces', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Introduce and Operationalize Jigawa Security Trust fund to increase financing and support to national and local security forces', '1752757296.jpg', NULL, '2025-07-17 20:01:36', '2025-07-17 20:01:36'),
(26, 5, 'Sustain Improvement of Public Service Delivery through an optimized State Civil Service by the Principles of rewards and sanctions', 'Essential', '2025-07-09', 161, '2025-12-17', 'In Progress', NULL, 'Sustain Improvement of Public Service Delivery through an optimized State Civil Service by the Principles of rewards and sanctions', '1752757824.jpg', NULL, '2025-07-17 20:10:24', '2025-07-17 20:10:24'),
(27, 5, 'Merit based Staff Recruitment in Ministries, Departments and Agencies (especially in professional and technical cadres) to fill critical manpower gaps in public service and other sectors to allow for optimal performance', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Merit based Staff Recruitment in Ministries, Departments and Agencies (especially in professional and technical cadres) to fill critical manpower gaps in public service and other sectors to allow for optimal performance', '1752758162.jpg', NULL, '2025-07-17 20:16:02', '2025-07-17 20:16:02'),
(28, 5, 'Ensure effectiveness and Sustainability of the State Pension Schemes', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Ensure effectiveness and Sustainability of the State Pension Schemes', '1752758224.jpg', NULL, '2025-07-17 20:17:04', '2025-07-17 20:17:04'),
(29, 5, 'Pursuit of Human Resource Development in the State Public Service to ensure that MDAs are manned and managed by competent, proficient and skilled manpower', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Pursuit of Human Resource Development in the State Public Service to ensure that MDAs are manned and managed by competent, proficient and skilled manpower', '1752758282.jpg', NULL, '2025-07-17 20:18:02', '2025-07-17 20:18:02'),
(30, 6, 'Effectively and Efficiently Execute Emergency Response & Preparedness in dealing with any form of disaster or emergencies in the State', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Effectively and Efficiently Execute Emergency Response & Preparedness in dealing with any form of disaster or emergencies in the State', '1752758504.jpg', NULL, '2025-07-17 20:21:44', '2025-07-17 20:21:44'),
(31, 6, 'Effectively and Efficiently Provide Support to Citizens of Jigawa State Seeking Enlistment in the Military, Paramilitary Organizations and Federal Establishments including the Provision of Guidance & Counselling Support to Youths in Jigawa State', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Effectively and Efficiently Provide Support to Citizens of Jigawa State Seeking Enlistment in the Military, Paramilitary Organizations and Federal Establishments including the Provision of guidance and counseling support to Youths in Jigawa State', '1752758573.jpg', NULL, '2025-07-17 20:22:53', '2025-07-17 20:22:53'),
(32, 6, 'Effectively and Efficiently Execute any Duty of Special Nature required for the overall socioeconomic wellbeing of the citizens of Jigawa State', 'Essential', '2025-07-01', 153, '2025-12-01', 'In Progress', NULL, 'Effectively and Efficiently Execute any Duty of Special Nature required for the overall socioeconomic wellbeing of the citizens of Jigawa State', '1752758621.jpg', NULL, '2025-07-17 20:23:41', '2025-07-17 20:23:41'),
(33, 7, 'Development and Protection of Grazing Reserves', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Development and Protection of Grazing Reserves', '1752759866.jpg', NULL, '2025-07-17 20:44:26', '2025-07-17 20:44:26'),
(34, 7, 'Development and sustained support for cluster farmers', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Development and sustained support for cluster farmers', '1752759992.jpg', NULL, '2025-07-17 20:46:32', '2025-07-17 20:46:32'),
(35, 7, 'Support Capacity Enhancement for Value Addition in Agricultural Produce Processing and Marketing', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Support Capacity Enhancement for Value Addition in Agricultural Produce Processing and Marketing', '1752760063.jpg', NULL, '2025-07-17 20:47:43', '2025-07-17 20:47:43'),
(36, 7, 'Sustained Collaboration with partners in the Pursuit of Agric Tural Transformation Agenda of the State Government', 'Essential', '2025-07-17', 153, '2025-12-17', 'In Progress', NULL, 'Sustained Collaboration with partners in the Pursuit of Agric Tural Transformation Agenda of the State Government', '1752760212.jpg', NULL, '2025-07-17 20:50:12', '2025-07-17 20:50:12'),
(37, 7, 'Livestock Development', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Livestock Development', '1752760268.jpg', NULL, '2025-07-17 20:51:08', '2025-07-17 20:51:08'),
(38, 7, 'Fisheries Development', 'Essential', '2025-07-01', 16, '2025-07-17', 'In Progress', NULL, 'Fisheries Development', '1752760319.jpg', NULL, '2025-07-17 20:51:59', '2025-07-17 20:51:59'),
(39, 7, 'Improve the Policy Environment for the Development of Agriculture in the State', 'Essential', '2025-07-25', 145, '2025-12-17', 'In Progress', NULL, 'Improve the Policy Environment for the Development of Agriculture in the State', '1752760386.jpg', NULL, '2025-07-17 20:53:06', '2025-07-17 20:53:06'),
(40, 7, 'Farmer Support in Accessing Agricultural Financing', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Farmer Support in Accessing Agricultural Financing', '1752760448.jpg', NULL, '2025-07-17 20:54:08', '2025-07-17 20:54:08'),
(41, 7, 'Revitalize and strengthen the State Agricultural & Rural Development Agency', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Revitalize and strengthen the State Agricultural & Rural Development Agency', '1752760550.jpg', NULL, '2025-07-17 20:55:50', '2025-07-17 20:55:50'),
(42, 7, 'Strengthen and expand the operations of the State Agricultural Supply to farmers.', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Strengthen and expand the operations of the State Agricultural Supply to farmers.', '1752760612.jpg', NULL, '2025-07-17 20:56:52', '2025-07-17 20:56:52'),
(43, 7, 'Exploit the State’s Agricultural Potentials through Agricultural Mechanization  and  Irrigation Development', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Exploit the State’s Agricultural Potentials through Agricultural Mechanization  and  Irrigation Development', '1752760667.jpg', NULL, '2025-07-17 20:57:47', '2025-07-17 20:57:47'),
(44, 7, 'Facilitate and mobilize large-scale private sector investments into the Agricultural Sector particularly around crops and livestock production and other aspects of the agricultural value chain', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Facilitate and mobilize large-scale private sector investments into the Agricultural Sector particularly around crops and livestock production and other aspects of the agricultural value chain', '1752760719.jpg', NULL, '2025-07-17 20:58:39', '2025-07-17 20:58:39'),
(45, 7, 'Timely Access to Quality and Affordable Agricultural Inputs (fertilizer, seedlings, herbicides, insecticide, etc.)', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Timely Access to Quality and Affordable Agricultural Inputs (fertilizer, seedlings, herbicides, insecticide, etc.)', '1752760906.jpg', NULL, '2025-07-17 21:01:46', '2025-07-17 21:01:46'),
(46, 8, 'Automation and digitization of school administrations, governance and admission process', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Automation and digitization of school administrations, governance and admission process', '1752761302.jpg', NULL, '2025-07-17 21:08:22', '2025-07-17 21:08:22'),
(47, 8, 'Enhance the Education database for proper mapping of all schools in the State by undertaking bio-metric-based capturing of all pupils and teachers', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Enhance the Education database for proper mapping of all schools in the State by undertaking bio-metric-based capturing of all pupils and teachers', '1752761371.jpg', NULL, '2025-07-17 21:09:31', '2025-07-17 21:09:31'),
(48, 8, 'Automation and digitization of school administrations, governance and admission process', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Automation and digitization of school administrations, governance and admission process', '1752761487.jpg', NULL, '2025-07-17 21:11:27', '2025-07-17 21:11:27'),
(49, 8, 'Enhance the efficiency, resourcefulness, competence of teachers and other educational personnel through training, capacity building, and motivation (Continuous training and retraining of at least 5,000 Teachers/Instructors annually, focusing on Modern Tea', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Enhance the efficiency, resourcefulness, competence of teachers and other educational personnel through training, capacity building, and motivation (Continuous training and retraining of at least 5,000 Teachers/Instructors annually, focusing on Modern Teaching Research Techniques, Digital Literacy, and Skills for Basic Education)', '1752761544.jpg', NULL, '2025-07-17 21:12:24', '2025-07-17 21:12:24'),
(50, 8, 'Consolidate on gains achieved in girl-child education by introducing Girl-Child Community Committee (GCCC) to monitor retention rates for girls in schools', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Consolidate on gains achieved in girl-child education by introducing Girl-Child Community Committee (GCCC) to monitor retention rates for girls in schools', '1752761682.jpg', NULL, '2025-07-17 21:14:42', '2025-07-17 21:14:42'),
(51, 8, 'Leapfrog Jigawa into the Digital Economy by introducing digital skills such as coding, web development, and artificial intelligence to all public primary and secondary schools in the state', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Leapfrog Jigawa into the Digital Economy by introducing digital skills such as coding, web development, and artificial intelligence to all public primary and secondary schools in the state', '1752761785.jpg', NULL, '2025-07-17 21:16:25', '2025-07-17 21:16:25'),
(52, 8, 'Address the challenge of unfavorable pupil-teacher ratio in Basic schools', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Address the challenge of unfavorable pupil-teacher ratio in Basic schools', '1752761891.jpg', NULL, '2025-07-17 21:18:11', '2025-07-17 21:18:11'),
(53, 8, 'Reviewing the concept of established Effective Schools at Basic Education', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Reviewing the concept of established Effective Schools at Basic Education', '1752761940.jpg', NULL, '2025-07-17 21:19:00', '2025-07-17 21:19:00'),
(54, 8, 'Leveraging on the power of e-learning to achieve better learning outcomes in schools (This would be part of an integrated State ICT Strategy and e-governance)', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Leveraging on the power of e-learning to achieve better learning outcomes in schools (This would be part of an integrated State ICT Strategy and e-governance)', '1752761992.jpg', NULL, '2025-07-17 21:19:52', '2025-07-17 21:19:52'),
(55, 9, 'Mobilization of Local and International Development Organization and Effective Donor coordination', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Mobilization of Local and International Development Organization and Effective Donor coordination', '1752762307.jpg', NULL, '2025-07-17 21:25:07', '2025-07-17 21:25:07'),
(56, 9, 'Improved Governance & Accountability through sustained Public Expenditure and Financial Management Reforms / Fiscal Sustainability.', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Improved Governance & Accountability through sustained Public Expenditure and Financial Management Reforms / Fiscal Sustainability.', '1752762366.jpg', NULL, '2025-07-17 21:26:06', '2025-07-17 21:26:06'),
(57, 9, 'Pursuit of Annual Planning and Budget Processes in line with the provisions of the State Budget Calander.', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Pursuit of Annual Planning and Budget Processes in line with the provisions of the State Budget Calander.', '1752762416.jpg', NULL, '2025-07-17 21:26:56', '2025-07-17 21:26:56'),
(58, 9, 'Expand the Coverage of Social Protection Program by bringing more people into the social register for poor and vulnerable citizens.', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Expand the Coverage of Social Protection Program by bringing more people into the social register for poor and vulnerable citizens.', '1752762561.jpg', NULL, '2025-07-17 21:29:21', '2025-07-17 21:29:21'),
(59, 10, 'Promoting of MSMSEs and Cooperatives', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Promoting of MSMSEs and Cooperatives', '1752763313.jpg', NULL, '2025-07-17 21:41:53', '2025-07-17 21:41:53'),
(60, 10, 'Revitalization of Maigatari EPZ', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Revitalization of Maigatari EPZ', '1752763361.jpg', NULL, '2025-07-17 21:42:41', '2025-07-17 21:42:41'),
(61, 10, 'Trade Fairs', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Trade Fairs', '1752763445.jpg', NULL, '2025-07-17 21:44:05', '2025-07-17 21:44:05'),
(62, 10, 'Tourism Development', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Tourism Development', '1752763509.jpg', NULL, '2025-07-17 21:45:09', '2025-07-17 21:45:09'),
(63, 10, 'Access to Financing', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Access to Financing', '1752763550.jpg', NULL, '2025-07-17 21:45:50', '2025-07-17 21:45:50'),
(64, 10, 'Export Promotion - Increase Market Access and International Trade Opportunities', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Export Promotion - Increase Market Access and International Trade Opportunities', '1752763602.jpg', NULL, '2025-07-17 21:46:42', '2025-07-17 21:46:42'),
(65, 10, 'Support the operation of Jigawa State Chamber of Commerce to drive the growth of Trade and Industries in the State.', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Support the operation of Jigawa State Chamber of Commerce to drive the growth of Trade and Industries in the State.', '1752763680.jpg', NULL, '2025-07-17 21:48:00', '2025-07-17 21:48:00'),
(66, 10, 'Capitalize Coops, Agric-based Association and Social Enterprises Development', 'Essential', '2025-07-01', 169, '2025-12-17', 'In Progress', NULL, 'Capitalize Coops, Agric-based Association and Social Enterprises Development', '1752763733.jpg', NULL, '2025-07-17 21:48:53', '2025-07-17 21:48:53'),
(67, 11, 'Take appropriate proactive measures to address some critical asreas of enironmental degradation.', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Take appropriate proactive measures to address some critical asreas of enironmental degradation.', '1752955028.jpg', NULL, '2025-07-20 02:57:08', '2025-07-20 02:57:08'),
(68, 11, 'Take action to address desert encroachment and improved vegetation cover', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Take action to address desert encroachment and improved vegetation cover', '1752955106.jpg', NULL, '2025-07-20 02:58:26', '2025-07-20 02:58:26'),
(69, 11, 'Mobilize and Leverage on External Support from Development Partners to Address issues on Environmental Sustainability including Climate Change (These include World Bank Assisted Projects, FGN Supported Great Green World Projects, etc.', 'Essential', '2025-07-01', 0, '2025-07-01', 'In Progress', NULL, 'Mobilize and Leverage on External Support from Development Partners to Address issues on Environmental Sustainability including Climate Change (These include World Bank Assisted Projects, FGN Supported Great Green World Projects, etc.', '1752955162.jpg', NULL, '2025-07-20 02:59:22', '2025-07-20 02:59:22'),
(70, 11, 'Improve the Health Status of the Population through the Promotion of Environmental Health and Public Health Security', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Improve the Health Status of the Population through the Promotion of Environmental Health and Public Health Security', '1752955271.jpg', NULL, '2025-07-20 03:01:11', '2025-07-20 03:01:11'),
(71, 11, 'Pursuit of initiatives to mitigate the adverse effects of Climate Change on Environmental Sustainability', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Pursuit of initiatives to mitigate the adverse effects of Climate Change on Environmental Sustainability', '1752955345.jpg', NULL, '2025-07-20 03:02:25', '2025-07-20 03:02:25'),
(72, 11, 'Pursuit of Initiatives to promote nature conservation for the protection of the flora and fauna of the environment', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Pursuit of Initiatives to promote nature conservation for the protection of the flora and fauna of the environment', '1752955413.jpg', NULL, '2025-07-20 03:03:33', '2025-07-20 03:03:33'),
(73, 12, 'Sustained State IGR performance in 2024, double it by the end of 2025, and further advance it up to 150% in the first Quarter of 2027 when compared with 2023 baseline value.', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Sustained State IGR performance in 2024, double it by the end of 2025, and further advance it up to 150% in the first Quarter of 2027 when compared with 2023 baseline value.', '1752955670.jpg', NULL, '2025-07-20 03:07:50', '2025-07-20 03:07:50'),
(74, 13, 'Improved access healthcare services through the attainment of Government’s vision of at least one functional Basic Health Clinic per Political Ward, one Secondary Health Facility for ach State Constituency and a Specialist Hospital for each Senatorial Zon', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Improved access healthcare services through the attainment of Government’s vision of at least one functional Basic Health Clinic per Political Ward, one Secondary Health Facility for ach State Constituency and a Specialist Hospital for each Senatorial Zone, This involved the completion and commissioning all the outstanding projects on Secondary and Specialist Hospitals before the end of the year.', '1752955783.jpg', NULL, '2025-07-20 03:09:43', '2025-07-20 03:09:43'),
(75, 13, 'Improved Performance and Expanded Coverage of State Health Insurance Scheme', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Improved Performance and Expanded Coverage of State Health Insurance Scheme', '1752955832.jpg', NULL, '2025-07-20 03:10:32', '2025-07-20 03:10:32'),
(76, 13, 'Pursuit of Interventions and Initiatives the Accelerate Progress towards the attainment of SDG Goal 3 concerned with ensuring healthy lives and promotion of well-being of the citizens. Specifically, this includes achieving Universal Health Coverage, Acces', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Pursuit of Interventions and Initiatives the Accelerate Progress towards the attainment of SDG Goal 3 concerned with ensuring healthy lives and promotion of well-being of the citizens. Specifically, this includes achieving Universal Health Coverage, Access to Quality Essential Basic Healthcare Services Access to safe, effective, quality and affordable essential medicines.', '1752955900.jpg', NULL, '2025-07-20 03:11:40', '2025-07-20 03:11:40'),
(77, 13, 'Improve Emergency Response Capacity and Effective in the Mitigation and Containment of Epidemics including AIDS, Tuberculosis, Malaria and Other Water-borne and Communicable Diseases Enhanced including the Strengthening the Referral Systems', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Improve Emergency Response Capacity and Effective in the Mitigation and Containment of Epidemics including AIDS, Tuberculosis, Malaria and Other Water-borne and Communicable Diseases Enhanced including the Strengthening the Referral Systems', '1752955950.jpg', NULL, '2025-07-20 03:12:30', '2025-07-20 03:12:30'),
(78, 13, ':Sustained Improvement in the Recruitment, Training and Deployment of Human resources for Health', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, ':Sustained Improvement in the Recruitment, Training and Deployment of Human resources for Health', '1752956015.jpg', NULL, '2025-07-20 03:13:35', '2025-07-20 03:13:35'),
(79, 13, 'Planning of the State\'s Health System', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Planning of the State\'s Health System', '1752956077.jpg', NULL, '2025-07-20 03:14:37', '2025-07-20 03:14:37'),
(80, 14, 'Address the chalenges of unfavourable pupil - teacher ratio in post basic school (senior secondary school)', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Address the chalenges of unfavourable pupil - teacher ratio in post basic school (senior secondary school)', '1752956905.jpg', NULL, '2025-07-20 03:28:25', '2025-07-20 03:28:25'),
(81, 14, 'Reviewing the concept of Establishing centres of excellence and effecctive schools at post basic Education', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Reviewing the concept of Establishing centres of excellence and effecctive schools at post basic Education', '1752956958.jpg', NULL, '2025-07-20 03:29:18', '2025-07-20 03:29:18'),
(82, 14, 'Provision of Critical Infracturactures (Decongestion of student classes and Hostel)', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Provision of Critical Infracturactures (Decongestion of student classes and Hostel)', '1752957004.jpg', NULL, '2025-07-20 03:30:04', '2025-07-20 03:30:04'),
(83, 14, 'Enhance quality of science, technology and vocational Education Training (STEM and TVET)', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Enhance quality of science, technology and vocational Education Training (STEM and TVET)', '1752957065.jpg', NULL, '2025-07-20 03:31:05', '2025-07-20 03:31:05'),
(84, 14, 'Leveraging on the power of e-learning to achieve better learning outcomes in schools (This would be part of an integrated state ICT strategy and e-governence', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Leveraging on the power of e-learning to achieve better learning outcomes in schools (This would be part of an integrated state ICT strategy and e-governence', '1752957143.jpg', NULL, '2025-07-20 03:32:23', '2025-07-20 03:32:23'),
(85, 14, 'Ensure effective operation of ICT in Schools', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Ensure effective operation of ICT in Schools', '1752957203.jpg', NULL, '2025-07-20 03:33:23', '2025-07-20 03:33:23'),
(87, 14, 'Accelerate effort to commence medical training in the state university', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Accelerate effort to commence medical training in the state university', '1752957362.jpg', NULL, '2025-07-20 03:36:02', '2025-07-20 03:36:02'),
(88, 14, 'Improve operation of Jigawa quality Assurance Agency (JISEQAA)', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Improve operation of Jigawa quality Assurance Agency (JISEQAA)', '1752957414.jpg', NULL, '2025-07-20 03:36:54', '2025-07-20 03:36:54'),
(89, 14, 'Ensure that all programm in the state tertiary institution are fully accredited by the relevant authorities', 'Ensure that all programm in the state tertiary institution are fully accredited by the relevant authorities', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Ensure that all programm in the state tertiary institution are fully accredited by the relevant authorities', '1752957475.jpg', NULL, '2025-07-20 03:37:55', '2025-07-20 03:37:55'),
(90, 14, 'Establish research centres in tertiary institutions to attract intervention/colaboration from (public/private sectors) and improve the existing ones', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Establish research centres in tertiary institutions to attract intervention/colaboration from (public/private sectors) and improve the existing ones', '1752957532.jpg', NULL, '2025-07-20 03:38:52', '2025-07-20 03:38:52'),
(91, 14, 'Funding research in science, technology and Agriculture through collaboration with private sector and Donor agencies', 'Essential', '2025-07-01', 171, '2025-12-19', 'In Progress', NULL, 'Funding research in science, technology and Agriculture through collaboration with private sector and Donor agencies', '1752957587.jpg', NULL, '2025-07-20 03:39:47', '2025-07-20 03:39:47'),
(93, 17, 'Development of comprehensive Geographic Information System for more effective Land Administration in the State', 'Essential', '2025-07-01', 173, '2025-12-21', 'In Progress', NULL, 'Development of comprehensive Geographic Information System for more effective Land Administration in the State', '1753131882.jpg', NULL, '2025-07-22 04:04:42', '2025-07-22 04:04:42'),
(94, 17, 'Implementation of Mass Housing Program (including in collaboration with private sector)  to reducing Housing Deficit in the State Capital and other Major Urban Centers in the State', 'Essential', '2025-07-01', 173, '2025-12-21', 'In Progress', NULL, 'Implementation of Mass Housing Program (including in collaboration with private sector)  to reducing Housing Deficit in the State Capital and other Major Urban Centers in the State', '1753131938.jpg', NULL, '2025-07-22 04:05:38', '2025-07-22 04:05:38'),
(95, 17, 'Pursuit of urban renewal program guided by updated and simple master plan', 'Essential', '2025-07-01', 173, '2025-12-21', 'In Progress', NULL, 'Pursuit of urban renewal program guided by updated and simple master plan', '1753131991.jpg', NULL, '2025-07-22 04:06:31', '2025-07-22 04:06:31'),
(96, 17, 'Percentage of Progress on Review of Revenue Rates and Scope under Land Administration', 'Essential', '2025-07-01', 173, '2025-12-21', 'In Progress', NULL, 'Percentage of Progress on Review of Revenue Rates and Scope under Land Administration', '1753132034.jpg', NULL, '2025-07-22 04:07:14', '2025-07-22 04:07:14'),
(97, 18, 'Strengthen Community Based Security Architecture', 'Essential', '2025-12-21', 0, '2025-12-21', 'In Progress', NULL, 'Strengthen Community Based Security Architecture', '1753132178.jpg', NULL, '2025-07-22 04:09:38', '2025-07-22 04:09:38'),
(98, 18, 'Launch of Community Peace Initiative (CPI) for improve service delivery and Community Development', 'Essential', '2025-07-01', 173, '2025-12-21', 'In Progress', NULL, 'Launch of Community Peace Initiative (CPI) for improve service delivery and Community Development', '1753132220.jpg', NULL, '2025-07-22 04:10:20', '2025-07-22 04:10:20'),
(99, 18, 'Create an Elders Advisory Committee (ELCO) in every Local Government to advise Government on various government programs and implementation strategies for development.', 'Essential', '2025-07-01', 173, '2025-12-21', 'In Progress', NULL, 'Create an Elders Advisory Committee (ELCO) in every Local Government to advise Government on various government programs and implementation strategies for development.', '1753132286.jpg', NULL, '2025-07-22 04:11:26', '2025-07-22 04:11:26'),
(100, 18, 'Entrench an effective financial management system alongside improved policy and strategy across all the Local Governments  with the support and collaboration with Development Partners.', 'Essential', '2025-07-01', 173, '2025-12-21', 'In Progress', NULL, 'Entrench an effective financial management system alongside improved policy and strategy across all the Local Governments  with the support and collaboration with Development Partners.', '1753132349.jpg', NULL, '2025-07-22 04:12:29', '2025-07-22 04:12:29'),
(101, 18, 'Sustain street light maintenance across all the 27 LGAs', 'Essential', '2025-07-01', 173, '2025-12-21', 'In Progress', NULL, 'Sustain street light maintenance across all the 27 LGAs', '1753132403.jpg', NULL, '2025-07-22 04:13:23', '2025-07-22 04:13:23'),
(102, 19, 'To improve access to realizable and affordable energy for the citizens through development of conventional energy sources', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'To improve access to realizable and affordable energy for the citizens through development of conventional energy sources', '1753376306.jpg', NULL, '2025-07-24 23:58:26', '2025-07-24 23:58:26'),
(103, 20, 'Achieve universal access to safe drinking water and sanitation services', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Achieve universal access to safe drinking water and sanitation services', '1753376661.jpg', NULL, '2025-07-25 00:04:21', '2025-07-25 00:04:21'),
(104, 20, 'Increased Investments in the Rehabilitation and Expansion of Irrigation Facilities and Infrastructure (Dams and Water Reservoirs)', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Increased Investments in the Rehabilitation and Expansion of Irrigation Facilities and Infrastructure (Dams and Water Reservoirs)', '1753376710.jpg', NULL, '2025-07-25 00:05:10', '2025-07-25 00:05:10'),
(105, 20, 'Achieve the provisions of SDG Goal 6 to ensure availability and sustainable management of water and sanitation for all', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Achieve the provisions of SDG Goal 6 to ensure availability and sustainable management of water and sanitation for all', '1753376757.jpg', NULL, '2025-07-25 00:05:57', '2025-07-25 00:05:57'),
(106, 21, 'Pursuit of targeted women empowerment programmes for job and employment creation', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Pursuit of targeted women empowerment programmes for job and employment creation', '1753376872.jpg', NULL, '2025-07-25 00:07:52', '2025-07-25 00:07:52'),
(107, 21, 'Evolution of new social protection programs along the Life Cycle', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Evolution of new social protection programs along the Life Cycle', '1753376920.jpg', NULL, '2025-07-25 00:08:40', '2025-07-25 00:08:40'),
(108, 21, 'Develop and implement policies on the protection, survival & development of women and children to address concerns on the Situation of Children in Jigawa State', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Develop and implement policies on the protection, survival & development of women and children to address concerns on the Situation of Children in Jigawa State', '1753376967.jpg', NULL, '2025-07-25 00:09:27', '2025-07-25 00:09:27'),
(109, 21, 'Sustained improvements in Social Welfare Institutions in the State', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Sustained improvements in Social Welfare Institutions in the State', '1753377014.jpg', NULL, '2025-07-25 00:10:14', '2025-07-25 00:10:14'),
(110, 22, 'Continued provision of Critical Infrastructure including Regional and Township Roads, Bridges/Culverts for Inclusive and Sustainable Economic Growth and improved Socioeconomic Wellbeing of the People. This includes new constructions, rehabilitation and ro', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Continued provision of Critical Infrastructure including Regional and Township Roads, Bridges/Culverts for Inclusive and Sustainable Economic Growth and improved Socioeconomic Wellbeing of the People. This includes new constructions, rehabilitation and routine maintenance.', '1753377241.jpg', NULL, '2025-07-25 00:14:01', '2025-07-25 00:14:01'),
(111, 23, 'Improvement of the State’s Business Environment and Investment Climate aimed at mobilizing Domestic and Foreign Direct Investments into the State to support quest for economic growth and development. This will involve the provision of pragmatic and releva', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Improvement of the State’s Business Environment and Investment Climate aimed at mobilizing Domestic and Foreign Direct Investments into the State to support quest for economic growth and development. This will involve the provision of pragmatic and relevant policy, legal, regulatory, and institutional frameworks for investment mobilization and private sector development.', '1753377380.jpg', NULL, '2025-07-25 00:16:20', '2025-07-25 00:16:20'),
(112, 23, 'To Fast track the realization of mobilized / pipeline investment proposals  and support to the private sector for increased investments and job creation', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'To Fast track the realization of mobilized / pipeline investment proposals  and support to the private sector for increased investments and job creation', '1753377425.jpg', NULL, '2025-07-25 00:17:05', '2025-07-25 00:17:05'),
(113, 23, 'Continue to provide the necessary business environment and investment climate attractive to both Domestic and Foreign Investors; be among the topmost performers in the Nation’s Business Competitiveness Index', 'Essential', '2025-07-01', 153, '2025-12-01', 'In Progress', NULL, 'Continue to provide the necessary business environment and investment climate attractive to both Domestic and Foreign Investors; be among the topmost performers in the Nation’s Business Competitiveness Index', '1753377478.jpg', NULL, '2025-07-25 00:17:58', '2025-07-25 00:17:58'),
(114, 24, 'Employment Generation and Youth Empowerment for Productivity and Sustainable Means of Livelihoods for the People of Jigawa State.', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Employment Generation and Youth Empowerment for Productivity and Sustainable Means of Livelihoods for the People of Jigawa State.', '1753377571.jpg', NULL, '2025-07-25 00:19:31', '2025-07-25 00:19:31'),
(115, 15, 'Strategic Information Management to generate Public Awareness, Mass Mobilization and Re-orientation for Participatory Governance and Patriotism.', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Strategic Information Management to generate Public Awareness, Mass Mobilization and Re-orientation for Participatory Governance and Patriotism.', '1753378032.jpg', NULL, '2025-07-25 00:27:12', '2025-07-25 00:27:12'),
(116, 15, 'Mass Mobilization for Societal Re-orientation and Participatory Governance', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Mass Mobilization for Societal Re-orientation and Participatory Governance', '1753378081.jpg', NULL, '2025-07-25 00:28:01', '2025-07-25 00:28:01'),
(117, 15, 'Special Education', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Special Education', '1753378129.jpg', NULL, '2025-07-25 00:28:49', '2025-07-25 00:28:49'),
(118, 15, 'Promote the Development of History and Culture', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Promote the Development of History and Culture', '1753378166.jpg', NULL, '2025-07-25 00:29:26', '2025-07-25 00:29:26'),
(119, 15, 'Revitalization of Jigawa State Printing Press', 'Essential', '2025-07-01', 176, '2025-12-24', 'In Progress', NULL, 'Revitalization of Jigawa State Printing Press', '1753378235.jpg', NULL, '2025-07-25 00:30:35', '2025-07-25 00:30:35'),
(120, 4, 'Launch the Community Peace Initiative (CPI) as a performance- based grant to promote peacekeeping and attract development to the communities', 'Essential', '2025-08-05', 122, '2025-12-05', 'In Progress', NULL, 'Launch the Community Peace Initiative (CPI) as a performance- based grant to promote peacekeeping and attract development to the communities', '1754397179.jpg', NULL, '2025-08-05 19:32:59', '2025-08-05 19:32:59');

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliverables`
--

INSERT INTO `deliverables` (`id`, `commitment_id`, `deliverable`, `budget`, `start_date`, `end_date`, `last_kip_id`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(14, 26, 'Enhanced Personnel and Organizational Productivity through their mandates, job descriptions and performance target with Annual State Productivity Awards', NULL, '2025-08-04', '2025-12-31', NULL, 'In Progress', NULL, '2025-08-04 20:10:44', '2025-08-04 20:10:44'),
(15, 27, 'New staff recruitment to fill criticall manpower gaps especially in Public Service Delivery Sectors', NULL, '2025-12-04', '2025-08-04', NULL, 'In Progress', NULL, '2025-08-04 20:25:18', '2025-08-04 20:25:18'),
(16, 28, 'Gratuity and Monthly Pension paid as when due', NULL, '2025-08-04', '2025-12-04', NULL, 'In Progress', NULL, '2025-08-04 20:32:13', '2025-08-04 20:32:13'),
(17, 29, 'Human Recource Development in the State Public Service to ensure it is manned and managed by competent proficient, and skilled manpower in-line with the professional standards demanded by the peculiarities of each cadre for optimal performance', NULL, '2025-08-04', '2025-12-04', NULL, 'In Progress', NULL, '2025-08-04 20:36:59', '2025-08-04 20:36:59'),
(18, 19, 'Sustained Support for Security Operations and Community Policing through effective collaboration between the State & Local Governments, Security Agencies, Emirate Councils, Local Vigilantes, and other Critical Stakeholders in the State Security Arctecture', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 17:48:39', '2025-08-05 17:48:39'),
(19, 120, 'Performance based grants given to CPIs for peace keeping performance', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 19:33:36', '2025-08-05 19:33:36'),
(20, 22, 'Database of residents in the State updated', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 19:36:31', '2025-08-05 19:36:31'),
(21, 23, 'Improve residents perception of trust on the law enforcement agencies', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 19:39:36', '2025-08-05 19:39:36'),
(22, 24, 'Community Security guards officers intigated into the police system', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 19:45:21', '2025-08-05 19:45:21'),
(23, 20, 'Support for the establishment of Community Securty Guards based on a PPP Model', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 19:49:03', '2025-08-05 19:49:03'),
(24, 21, 'MultiSectoral Approach in the Crusade Against Drug Abuse', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 20:26:01', '2025-08-05 20:26:01'),
(25, 21, 'Community Security guards officers intigated into the police system', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 20:26:42', '2025-08-05 20:26:42'),
(26, 25, 'Establishment of Security Trust Fund for improved funding and support to Security Agencies including the implementation of Performance-based grants for Community-based Peace Initiatives', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-05 20:32:17', '2025-08-05 20:32:17'),
(27, 30, 'Improved Effeciency and Effectiveness of Emergency relief and Disaster Mangement', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-06 03:57:31', '2025-08-06 03:57:31'),
(28, 30, 'Emergency mangement committee established at State level in 27 local Government and at a ward level in specific flood-prone areas', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-06 03:58:16', '2025-08-06 03:58:16'),
(29, 31, 'Support towards the placement of Citizens of Jigawa State in the Military, Paramilitary and other Federal Establishment', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-06 04:12:51', '2025-08-06 04:12:51'),
(30, 31, 'Guidance and Counselling Support to Youths in Jigawa State', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-06 04:13:19', '2025-08-06 04:13:19'),
(31, 32, 'Effective Discharge of Other Special Duties', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-06 04:17:15', '2025-08-06 04:17:15'),
(32, 97, 'Community Guards Coordinating Unit established and Fuctional at State and Local Governments', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-06 04:35:04', '2025-08-06 04:35:04'),
(33, 98, 'Support Community Based Projects across the State', NULL, '2025-08-05', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-06 04:38:29', '2025-08-06 04:38:29'),
(34, 99, 'Elders Advisory Committee Fuctional in the 27 Local Governments', NULL, '2025-08-06', '2025-12-05', NULL, 'In Progress', NULL, '2025-08-06 04:41:36', '2025-08-06 04:41:36'),
(35, 100, 'Development Plan Formulated for the 27 Local Governments.', NULL, '2025-08-06', '2025-12-06', NULL, 'In Progress', NULL, '2025-08-06 12:37:31', '2025-08-06 12:37:31'),
(36, 101, 'Functionality of LGA Streetlights across the LGAs (Maintenance, Diesel, etc)', NULL, '2025-08-06', '2025-12-06', NULL, 'In Progress', NULL, '2025-08-06 12:42:08', '2025-08-06 12:42:08'),
(37, 55, 'The scope of development support and technical assistance with International Development Partners widened', NULL, '2025-08-06', '2025-12-06', NULL, 'In Progress', NULL, '2025-08-06 12:50:18', '2025-08-06 12:50:18'),
(38, 56, 'Establishment of a Functional PFM Core Group to Sustain Fiscal and Financial Management Reforms.', NULL, '2025-08-06', '2025-12-06', NULL, 'In Progress', NULL, '2025-08-06 13:18:48', '2025-08-06 13:18:48'),
(39, 56, 'Citizens Engagement in PFM Reforms', NULL, '2025-08-06', '2025-12-06', NULL, 'In Progress', NULL, '2025-08-06 13:24:04', '2025-08-06 13:24:04'),
(40, 57, 'Timely Completion of all specific milestones covered in the Budget Calander', NULL, '2025-08-06', '2025-12-06', NULL, 'In Progress', NULL, '2025-08-06 13:27:51', '2025-08-06 13:27:51'),
(41, 58, 'More poor and vulnerable Households covered in the State Social Register', NULL, '2025-08-06', '2025-08-31', NULL, 'In Progress', NULL, '2025-08-06 13:34:15', '2025-08-06 13:34:15'),
(42, 58, 'More coverage to be achieved on poor and vulnarable households in the provision  of basic social services, including the elderly and the most vulnerable segment of the state', NULL, '2025-08-06', '2025-08-07', NULL, 'In Progress', NULL, '2025-08-06 13:38:26', '2025-08-06 13:38:26'),
(43, 110, 'Road Infrastructure Expanded', NULL, '2025-08-01', '2025-12-07', NULL, 'In Progress', NULL, '2025-08-07 12:11:51', '2025-08-07 12:11:51'),
(44, 102, 'Reliable access to Electricity from the National Grid Increase', NULL, '2025-08-01', '2025-12-07', NULL, 'In Progress', NULL, '2025-08-07 12:39:32', '2025-08-07 12:39:32'),
(45, 102, 'Increased Unit of Power Generation and Distribution from the mini Grid', NULL, '2025-08-01', '2025-12-07', NULL, 'In Progress', NULL, '2025-08-07 12:46:03', '2025-08-07 12:46:03'),
(46, 102, 'Development of renewable Energy', NULL, '2025-08-01', '2025-12-07', NULL, 'In Progress', NULL, '2025-08-07 12:49:13', '2025-08-07 12:49:13');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpis`
--

INSERT INTO `kpis` (`id`, `deliverable_id`, `kpi`, `target_value`, `start_date`, `end_date`, `unit_of_measurement`, `deleted_at`, `created_at`, `updated_at`) VALUES
(15, 14, 'Organisational Productivity Level', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:12:59', '2025-08-04 20:12:59'),
(16, 14, 'No. of staff that are rewarded for recorded outstanding performance', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:14:22', '2025-08-04 20:14:22'),
(17, 14, 'No. of staff that are sanctioned for recorded poor performance', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:16:47', '2025-08-04 20:16:47'),
(18, 14, 'No of MDAs introduced to Corporate Planning', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:17:53', '2025-08-04 20:17:53'),
(19, 14, 'Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:19:09', '2025-08-04 20:19:09'),
(20, 14, 'Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:20:37', '2025-08-04 20:20:37'),
(21, 15, '% of staff needs that are met  through recruitments across all MDAs', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:26:19', '2025-08-04 20:26:19'),
(22, 15, '% of Recruited staff that are females', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:27:39', '2025-08-04 20:27:39'),
(23, 15, '% of Recruited staff that are people with disabilities', '0', '2025-08-04', '2025-12-04', 'Percentage', NULL, '2025-08-04 20:28:19', '2025-08-04 20:28:19'),
(24, 15, 'Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed)', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:29:15', '2025-08-04 20:29:15'),
(25, 16, 'Percentage of retired staff paid their gratuity as when due', '0', '2025-08-04', '2025-12-04', 'Percentage', NULL, '2025-08-04 20:33:20', '2025-08-04 20:33:20'),
(26, 16, 'Percentage of retired staff paid their monthly pension as when due', '0', '2025-08-04', '2025-12-04', 'Percentage', NULL, '2025-08-04 20:34:00', '2025-08-04 20:34:00'),
(27, 17, 'No of training programmes implemented by the State MDI across various cadres in the State Civil Service', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:37:53', '2025-08-04 20:37:53'),
(28, 17, 'Number of Civil Servants Trained by State MDI across all MDAs covering various cadres', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:38:49', '2025-08-04 20:38:49'),
(29, 17, 'Total number of participated in short term Capacity Building Programme for Staff across MDAs', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:39:31', '2025-08-04 20:39:31'),
(30, 17, 'Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions.', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:40:08', '2025-08-04 20:40:08'),
(31, 17, 'Number of staff trained through professional bodies on their task related skills', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:40:52', '2025-08-04 20:40:52'),
(32, 17, 'Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:41:42', '2025-08-04 20:41:42'),
(33, 17, 'Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:42:23', '2025-08-04 20:42:23'),
(34, 17, 'Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:43:10', '2025-08-04 20:43:10'),
(35, 17, 'Annual growth in revenues generated by MDI', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:44:00', '2025-08-04 20:44:00'),
(36, 17, 'Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council', '0', '2025-08-04', '2025-12-04', 'Number', NULL, '2025-08-04 20:44:39', '2025-08-04 20:44:39'),
(37, 18, '% Progress in streamlinng Joint Security Operations based on an Established and Functional Single Security Coordinating Center (SCC)', '0', '2025-08-05', '2025-12-05', 'Percentage', NULL, '2025-08-05 17:49:52', '2025-08-05 17:49:52'),
(38, 18, 'Number of community security cases and social vices (theft, robbery, drug-related offences, etc) cases intervened/mitigated through the Joint Security Operations', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 17:50:44', '2025-08-05 17:50:44'),
(39, 18, 'Number of potential conflicts & disputes (e.g., farmers/herders conflicts, border disputes, etc.) mitigated annually through Community-Policing and other proactive means with early-warning signals.', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 17:51:23', '2025-08-05 17:51:23'),
(40, 18, 'State Security Council meets at least  Quarterly to consider reports and strategies for sustained security vigilance', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 17:51:59', '2025-08-05 17:51:59'),
(41, 18, 'No. of Targetted Stakeholder Engagements, including the Security Agencies, Traditional Institutions, and the Ulamas efforts to maintain Peace and Security in the State through effective collaboration and dialogue', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 17:52:42', '2025-08-05 17:52:42'),
(42, 19, 'Number of CPIs that have Received Performance Grants', '0', '2025-08-05', '2025-12-05', '3', NULL, '2025-08-05 19:34:20', '2025-08-05 19:34:20'),
(43, 20, 'Percentage of the population of residents whose data have been capture in the state security database', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 19:37:31', '2025-08-05 19:37:31'),
(44, 21, 'Residents perception on confidence in security enforcement agencies (opinion pool)', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 19:40:12', '2025-08-05 19:40:12'),
(45, 22, 'Number of Trained Community Security Guards recruited to support security efforts in Government Institutions and Public Places', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 19:46:06', '2025-08-05 19:46:06'),
(46, 22, 'Propotion of the trained Community Security Guards integrated into formal security systems', '0', '2025-08-05', '2025-12-05', 'Percentage', NULL, '2025-08-05 19:46:54', '2025-08-05 19:46:54'),
(47, 23, 'Percentage of Progresss towards the establishment and functionality of Community Security Guard Office (CSGO) for strengthened Community Based Security Architecture', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 20:07:34', '2025-08-05 20:07:34'),
(48, 23, 'Number of Community Security Guards Recruited, Trained, Empwered and Drafted into Community Policing Annually', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 20:08:16', '2025-08-05 20:08:16'),
(49, 24, 'Establishment and Functionality of a MultiSectoral Taskforce on the Security Threats by Local Drug Dealers, Canpaign Agaist Drug Abuse and Rehabilitarion of Drug Addicts', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 20:27:26', '2025-08-05 20:27:26'),
(50, 24, 'Number of Local Drug Dealers Apprehended', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 20:28:16', '2025-08-05 20:28:16'),
(51, 24, 'Number of Activities Conducted in the Campsign Against Drug Abuse', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 20:28:52', '2025-08-05 20:28:52'),
(52, 25, 'Propotion of the trained community security guards officers that have been integrated into formal police force', '0', '2025-08-05', '2025-12-05', 'Percentage', NULL, '2025-08-05 20:30:51', '2025-08-05 20:30:51'),
(53, 26, 'Percentage Progress towards the establishment and Functionality of Security Trust Fund (Establishment, Legal Backing and Operations', '0', '2025-08-05', '2025-12-05', 'Percentage', NULL, '2025-08-05 20:33:23', '2025-08-05 20:33:23'),
(54, 26, '% Increase in Mobilized Financial Resources Above Initial Seed Funds', '0', '2025-08-05', '2025-12-05', 'Percentage', NULL, '2025-08-05 20:34:00', '2025-08-05 20:34:00'),
(55, 26, 'Number of Community-based Peace Initiatives Supported through the Performance-based Grants', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-05 20:34:33', '2025-08-05 20:34:33'),
(56, 27, 'Timeliness in Response to Disaster Occurance', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:06:26', '2025-08-06 04:06:26'),
(57, 27, 'Number of MoUs signed with either Local or international Partners on disaster management', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:07:11', '2025-08-06 04:07:11'),
(58, 27, 'Percentage of household victims that received relief package during emergency', '0', '2025-08-05', '2025-12-05', 'Percentage', NULL, '2025-08-06 04:08:08', '2025-08-06 04:08:08'),
(59, 27, 'State emergency policy developed and operationalised', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:08:58', '2025-08-06 04:08:58'),
(60, 28, 'Numberof local Government that have fuctional emergency management committee', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:10:56', '2025-08-06 04:10:56'),
(61, 28, 'Percentage of political wards that have fuctional Emergency management committee', '0', '2025-08-05', '2025-12-05', 'Percentage', NULL, '2025-08-06 04:11:37', '2025-08-06 04:11:37'),
(62, 29, 'No. of Jigawa Citizens Supported to be Enrolled in Military, Paramilitary and Federal Establishments', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:14:08', '2025-08-06 04:14:08'),
(63, 29, 'No. of Engagements with Federal Character Commission through the State Representative in the Employment of Jigawa Citizens in Federal Agencies', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:14:38', '2025-08-06 04:14:38'),
(64, 30, 'No of Guidance & Counselling Activities Conducted across all the 27 LGAs in the State', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:16:10', '2025-08-06 04:16:10'),
(65, 31, 'No. of Palliative Shops Established in each LGA Headquarter', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:18:00', '2025-08-06 04:18:00'),
(66, 31, 'No. of Palliative Shops Established in all 287 Political Wards in the State', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:18:24', '2025-08-06 04:18:24'),
(67, 31, 'No. of other Major Special Duties Successfully Executed', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:18:56', '2025-08-06 04:18:56'),
(68, 32, 'Number of Local Governments Councils that have Functional Community Guards Coordinating Units.', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:35:59', '2025-08-06 04:35:59'),
(69, 33, 'Number of Community Led Projects Financed', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:39:24', '2025-08-06 04:39:24'),
(70, 34, 'Number of Locsl Government Councils with Functional Elders Advisory Committee.', '0', '2025-08-05', '2025-12-05', 'Number', NULL, '2025-08-06 04:42:45', '2025-08-06 04:42:45'),
(71, 35, 'Percentage of LGA that have Developed First Generation Local Government Development Plan (LGDP)', '0', '2025-08-06', '2025-12-06', 'Percentage', NULL, '2025-08-06 12:38:20', '2025-08-06 12:38:20'),
(72, 35, 'Percentage of LGA that upgraded their Annual Buddget to IPSAS Standards based on National Chart of Accounts', '0', '2025-08-06', '2025-12-06', 'Percentage', NULL, '2025-08-06 12:39:03', '2025-08-06 12:39:03'),
(73, 36, 'Average number hours Streetlights function on a daily Basis', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 12:43:14', '2025-08-06 12:43:14'),
(74, 36, 'Number of Diesel Streetlight Projects Converted to Solar', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 12:44:23', '2025-08-06 12:44:23'),
(75, 36, 'Number of days before non-funtional Streetlights in LGAs are Fixed.', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 12:45:06', '2025-08-06 12:45:06'),
(76, 37, 'Number of Development Partners / Programs Operating in the State', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 12:51:23', '2025-08-06 12:51:23'),
(77, 37, 'Number of Programmes being supported by Development Partners in the State (exisiting and new).', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 12:54:37', '2025-08-06 12:54:37'),
(78, 37, 'Increase in the Quantum of Resources coming to the State from Development Partners', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 13:04:16', '2025-08-06 13:04:16'),
(79, 37, 'Percentage of financing gap of State Development plan filled by the ODA', '0', '2025-08-06', '2025-12-06', 'Percentage', NULL, '2025-08-06 13:05:03', '2025-08-06 13:05:03'),
(80, 37, 'Functionality of Development Partners Forum (Percentage of Quarterly Meetings Conducted)', '0', '2025-08-06', '2025-12-06', 'Percentage', NULL, '2025-08-06 13:05:55', '2025-08-06 13:05:55'),
(81, 37, 'Extent of JIMAF Implementation across MDAs and Initiatives', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 13:06:40', '2025-08-06 13:06:40'),
(82, 38, 'Functionality of PFM Core Group (Minmum of 4 Quarterly Meetings and 2 Key Governance Reform Activities such as Training Workshops)', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 13:19:52', '2025-08-06 13:19:52'),
(83, 38, 'Conduct of Annual Report on Rapid Performance Assessment of PFM Indicators and Percentage Target Score of 75%', '0', '2025-08-06', '2025-08-06', 'Percentage', NULL, '2025-08-06 13:20:46', '2025-08-06 13:20:46'),
(84, 38, 'Bi-annual Reports on the Implementation of PFM Reform Sustainability Plan by the Core Group', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 13:21:59', '2025-08-06 13:21:59'),
(85, 39, 'Functionality State OGP ( 4No. Quaterly Meetings, and 2 bi-annual reports on the Implementation & Monitoring State OGP Work Plan', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 13:24:51', '2025-08-06 13:24:51'),
(86, 39, 'Extent of Citizens Participation in Governance', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 13:25:45', '2025-08-06 13:25:45'),
(87, 40, 'Timely Publication of Quaterly Budget Implementation Reports', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 13:28:51', '2025-08-06 13:28:51'),
(88, 40, 'Milestones in the Budget Calander', '0', '2025-08-06', '2025-12-06', 'Number', NULL, '2025-08-06 13:29:28', '2025-08-06 13:29:28'),
(89, 41, 'Percentage of Communities Covered by the State Social Register', '0', '2025-08-06', '2025-12-06', 'Percentage', NULL, '2025-08-06 13:35:10', '2025-08-06 13:35:10'),
(90, 41, 'Percentage of Poor and vulnerable Household Covered in the State Social Register', '0', '2025-08-06', '2025-12-06', 'Percentage', NULL, '2025-08-06 13:35:52', '2025-08-06 13:35:52'),
(91, 41, "Percentage of the State's Population Covered in the social register", '0', '2025-08-31', '2025-12-06', 'Percentage', NULL, '2025-08-06 13:36:45', '2025-08-06 13:36:45'),
(92, 42, 'Percentage of coverage of the poor and vulnarable households that include elderly and the most vulnarable segment in the social register', '0', '2025-08-06', '2025-12-06', 'Percentage', NULL, '2025-08-06 13:39:15', '2025-08-06 13:39:15'),
(93, 42, 'Percentage of already empowered poor and vulnarable households that exists the social register', '0', '2025-08-07', '2025-12-06', 'Percentage', NULL, '2025-08-06 13:40:01', '2025-08-06 13:40:01'),
(94, 43, 'Number of kilometer of New Regional Roads constructed', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:16:55', '2025-08-07 12:16:55'),
(95, 43, 'Number of kilometer of township roads constructed', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:17:40', '2025-08-07 12:17:40'),
(96, 43, 'Number of kilometer of feeder roads constructed', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:18:34', '2025-08-07 12:18:34'),
(97, 43, 'Number of Major Box Culverts/Bridge constructed', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:20:21', '2025-08-07 12:20:21'),
(98, 43, 'Number of kilometer of regional roads Maintained', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:21:05', '2025-08-07 12:21:05'),
(99, 43, 'Number of kilometer of township roads Maintained', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:21:59', '2025-08-07 12:21:59'),
(100, 43, 'Number of kilometer of feeder roads Maintained', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:22:56', '2025-08-07 12:22:56'),
(101, 44, 'Number of Communities that are Connected to National Grid', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:40:37', '2025-08-07 12:40:37'),
(102, 44, 'Number of kilometer of 33/11kv  - ITC Interconnected Lines', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:41:23', '2025-08-07 12:41:23'),
(103, 44, 'Number of Non Functional Transformers Replaced', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:42:16', '2025-08-07 12:42:16'),
(104, 44, 'Number of Non Functional Transformers Rehabilitated', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:43:00', '2025-08-07 12:43:00'),
(105, 45, 'Megawatt of Electricity Units Generated from mini - grid', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:46:56', '2025-08-07 12:46:56'),
(106, 45, 'Megawatt of Electricity Units that was Generated from mini - grid Distributed', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:47:40', '2025-08-07 12:47:40'),
(107, 46, 'Number of Town Provided with Solar Street Lights', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:49:58', '2025-08-07 12:49:58'),
(108, 46, 'Number of Stand-alone Solar Street Lights Provided or Rehabilitated in the State', '0', '2025-08-01', '2025-12-07', 'Number', NULL, '2025-08-07 12:50:51', '2025-08-07 12:50:51');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `kpi_targets`
--

INSERT INTO `kpi_targets` (`id`, `kpi_id`, `year`, `target`, `created_at`, `updated_at`) VALUES
(23, 15, 2024, '70', '2025-08-04 20:12:59', '2025-08-04 20:13:16'),
(24, 16, 2024, '10', '2025-08-04 20:14:22', '2025-08-04 20:15:06'),
(25, 17, 2024, '10', '2025-08-04 20:16:49', '2025-08-04 20:17:03'),
(26, 18, 2024, '5', '2025-08-04 20:17:53', '2025-08-04 20:18:16'),
(27, 19, 2024, '10', '2025-08-04 20:19:09', '2025-08-04 20:19:49'),
(28, 20, 2024, '10', '2025-08-04 20:20:37', '2025-08-04 20:21:03'),
(29, 21, 2024, '5', '2025-08-04 20:26:20', '2025-08-04 20:30:00'),
(30, 22, 2024, '10', '2025-08-04 20:27:39', '2025-08-04 20:30:00'),
(31, 23, 2024, '5', '2025-08-04 20:28:20', '2025-08-04 20:30:00'),
(32, 24, 2024, '91', '2025-08-04 20:29:15', '2025-08-04 20:30:00'),
(33, 25, 2024, '100', '2025-08-04 20:33:20', '2025-08-04 20:34:31'),
(34, 26, 2024, '100', '2025-08-04 20:34:01', '2025-08-04 20:34:31'),
(35, 27, 2024, '18', '2025-08-04 20:37:54', '2025-08-04 20:46:45'),
(36, 28, 2024, '540', '2025-08-04 20:38:50', '2025-08-04 20:46:45'),
(37, 29, 2024, '300', '2025-08-04 20:39:32', '2025-08-04 20:46:45'),
(38, 30, 2024, '1000', '2025-08-04 20:40:09', '2025-08-04 20:46:45'),
(39, 31, 2024, '150', '2025-08-04 20:40:53', '2025-08-04 20:46:45'),
(40, 32, 2024, '27', '2025-08-04 20:41:42', '2025-08-04 20:46:45'),
(41, 33, 2024, '1080', '2025-08-04 20:42:24', '2025-08-04 20:46:45'),
(42, 34, 2024, '90', '2025-08-04 20:43:11', '2025-08-04 20:46:45'),
(43, 35, 2024, '10', '2025-08-04 20:44:00', '2025-08-04 20:46:45'),
(44, 36, 2024, '4', '2025-08-04 20:44:40', '2025-08-04 20:46:45'),
(45, 37, 2024, '60', '2025-08-05 17:49:53', '2025-08-05 17:53:47'),
(46, 38, 2024, '20', '2025-08-05 17:50:45', '2025-08-05 17:53:47'),
(47, 39, 2024, '10', '2025-08-05 17:51:24', '2025-08-05 17:53:47'),
(48, 40, 2024, '4', '2025-08-05 17:52:00', '2025-08-05 17:53:47'),
(49, 41, 2024, '4', '2025-08-05 17:52:43', '2025-08-05 17:53:47'),
(50, 42, 2024, '3', '2025-08-05 19:34:21', '2025-08-05 19:35:17'),
(51, 43, 2024, '50', '2025-08-05 19:37:32', '2025-08-05 19:38:14'),
(52, 44, 2024, '0', '2025-08-05 19:40:13', '2025-08-05 19:44:22'),
(53, 45, 2024, '900', '2025-08-05 19:46:07', '2025-08-05 19:47:15'),
(54, 46, 2024, '90', '2025-08-05 19:46:55', '2025-08-05 19:47:15'),
(55, 47, 2024, '60', '2025-08-05 20:07:35', '2025-08-05 20:08:36'),
(56, 48, 2024, '810', '2025-08-05 20:08:16', '2025-08-05 20:08:36'),
(57, 49, 2024, '', '2025-08-05 20:27:27', '2025-08-05 20:27:27'),
(58, 50, 2024, '', '2025-08-05 20:28:16', '2025-08-05 20:28:16'),
(59, 51, 2024, '', '2025-08-05 20:28:52', '2025-08-05 20:28:52'),
(60, 52, 2024, '', '2025-08-05 20:30:51', '2025-08-05 20:30:51'),
(61, 53, 2024, '20', '2025-08-05 20:33:23', '2025-08-05 20:34:51'),
(62, 54, 2024, '20', '2025-08-05 20:34:00', '2025-08-05 20:34:51'),
(63, 55, 2024, '60', '2025-08-05 20:34:34', '2025-08-05 20:34:51'),
(64, 56, 2024, '', '2025-08-06 04:06:27', '2025-08-06 04:09:50'),
(65, 57, 2024, '5', '2025-08-06 04:07:11', '2025-08-06 04:09:50'),
(66, 58, 2024, '100', '2025-08-06 04:08:09', '2025-08-06 04:09:50'),
(67, 59, 2024, '1', '2025-08-06 04:08:59', '2025-08-06 04:09:50'),
(68, 60, 2024, '12', '2025-08-06 04:10:56', '2025-08-06 04:11:52'),
(69, 61, 2024, '20', '2025-08-06 04:11:37', '2025-08-06 04:11:52'),
(70, 62, 2024, '1500', '2025-08-06 04:14:09', '2025-08-06 04:15:15'),
(71, 63, 2024, '6', '2025-08-06 04:14:39', '2025-08-06 04:15:15'),
(72, 64, 2024, '54', '2025-08-06 04:16:10', '2025-08-06 04:16:22'),
(73, 65, 2024, '27', '2025-08-06 04:18:00', '2025-08-06 04:19:27'),
(74, 66, 2024, '287', '2025-08-06 04:18:25', '2025-08-06 04:19:27'),
(75, 67, 2024, '6', '2025-08-06 04:18:56', '2025-08-06 04:19:27'),
(76, 68, 2024, '6', '2025-08-06 04:36:00', '2025-08-06 04:36:30'),
(77, 69, 2024, '50', '2025-08-06 04:39:25', '2025-08-06 04:39:51'),
(78, 70, 2024, '10', '2025-08-06 04:42:45', '2025-08-06 04:43:12'),
(79, 71, 2024, '100', '2025-08-06 12:38:20', '2025-08-06 12:39:31'),
(80, 72, 2024, '40', '2025-08-06 12:39:04', '2025-08-06 12:39:31'),
(81, 73, 2024, '12', '2025-08-06 12:43:15', '2025-08-06 12:45:34'),
(82, 74, 2024, '15', '2025-08-06 12:44:23', '2025-08-06 12:45:34'),
(83, 75, 2024, '14', '2025-08-06 12:45:06', '2025-08-06 12:45:34'),
(84, 76, 2024, '25', '2025-08-06 12:51:24', '2025-08-06 13:10:52'),
(85, 77, 2024, '33', '2025-08-06 12:54:38', '2025-08-06 13:10:52'),
(86, 78, 2024, '100', '2025-08-06 13:04:17', '2025-08-06 13:10:52'),
(87, 79, 2024, '70', '2025-08-06 13:05:05', '2025-08-06 13:10:52'),
(88, 80, 2024, '4', '2025-08-06 13:05:56', '2025-08-06 13:10:52'),
(89, 81, 2024, '90', '2025-08-06 13:06:41', '2025-08-06 13:10:52'),
(90, 82, 2024, '7', '2025-08-06 13:19:53', '2025-08-06 13:22:29'),
(91, 83, 2024, '75', '2025-08-06 13:20:46', '2025-08-06 13:22:29'),
(92, 84, 2024, '2', '2025-08-06 13:22:00', '2025-08-06 13:22:29'),
(93, 85, 2024, '6', '2025-08-06 13:24:52', '2025-08-06 13:26:18'),
(94, 86, 2024, '90', '2025-08-06 13:25:46', '2025-08-06 13:26:18'),
(95, 87, 2024, '4', '2025-08-06 13:28:51', '2025-08-06 13:29:59'),
(96, 88, 2024, '7', '2025-08-06 13:29:29', '2025-08-06 13:29:59'),
(97, 89, 2024, '20', '2025-08-06 13:35:11', '2025-08-06 13:37:15'),
(98, 90, 2024, '70', '2025-08-06 13:35:52', '2025-08-06 13:37:15'),
(99, 91, 2024, '15', '2025-08-06 13:36:45', '2025-08-06 13:37:15'),
(100, 92, 2024, '20', '2025-08-06 13:39:15', '2025-08-06 13:40:25'),
(101, 93, 2024, '15', '2025-08-06 13:40:02', '2025-08-06 13:40:25'),
(102, 94, 2024, '3700', '2025-08-07 12:16:56', '2025-08-07 12:28:29'),
(103, 95, 2024, '230', '2025-08-07 12:17:40', '2025-08-07 12:28:29'),
(104, 96, 2024, '300', '2025-08-07 12:18:34', '2025-08-07 12:28:29'),
(105, 97, 2024, '10', '2025-08-07 12:20:22', '2025-08-07 12:28:29'),
(106, 98, 2024, '600', '2025-08-07 12:21:06', '2025-08-07 12:28:29'),
(107, 99, 2024, '15', '2025-08-07 12:22:00', '2025-08-07 12:28:29'),
(108, 100, 2024, '150', '2025-08-07 12:22:57', '2025-08-07 12:28:29'),
(109, 101, 2024, '7', '2025-08-07 12:40:38', '2025-08-07 12:44:58'),
(110, 102, 2024, '90', '2025-08-07 12:41:23', '2025-08-07 12:44:58'),
(111, 103, 2024, '25', '2025-08-07 12:42:16', '2025-08-07 12:44:58'),
(112, 104, 2024, '16', '2025-08-07 12:43:01', '2025-08-07 12:44:58'),
(113, 105, 2024, '', '2025-08-07 12:46:57', '2025-08-07 12:46:57'),
(114, 106, 2024, '', '2025-08-07 12:47:41', '2025-08-07 12:47:41'),
(115, 107, 2024, '10', '2025-08-07 12:49:58', '2025-08-07 12:51:33'),
(116, 108, 2024, '150', '2025-08-07 12:50:52', '2025-08-07 12:51:33');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `body`, `sender_id`, `model_id`, `status`, `created_at`, `updated_at`) VALUES
(4, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Organisational Productivity Level. It awaits your review', 7, 16, 'Not Read', '2025-08-15 04:03:10', '2025-08-15 04:03:10'),
(5, 7, '', 'Tracking Submitted', 'Your request on Organisational Productivity Level has been submitted to Delivery Department. It is waiting for review', 3, 16, 'Not Read', '2025-08-15 04:03:11', '2025-08-15 04:03:11'),
(6, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Organisational Productivity Level. It awaits your review', 7, 17, 'Not Read', '2025-08-15 04:03:35', '2025-08-15 04:03:35'),
(7, 7, '', 'Tracking Submitted', 'Your request on Organisational Productivity Level has been submitted to Delivery Department. It is waiting for review', 3, 17, 'Not Read', '2025-08-15 04:03:35', '2025-08-15 04:03:35'),
(8, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Organisational Productivity Level. It awaits your review', 7, 18, 'Not Read', '2025-08-15 04:04:05', '2025-08-15 04:04:05'),
(9, 7, '', 'Tracking Submitted', 'Your request on Organisational Productivity Level has been submitted to Delivery Department. It is waiting for review', 3, 18, 'Not Read', '2025-08-15 04:04:05', '2025-08-15 04:04:05'),
(10, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Organisational Productivity Level. It awaits your review', 7, 19, 'Not Read', '2025-08-15 04:04:27', '2025-08-15 04:04:27'),
(11, 7, '', 'Tracking Submitted', 'Your request on Organisational Productivity Level has been submitted to Delivery Department. It is waiting for review', 3, 19, 'Not Read', '2025-08-15 04:04:27', '2025-08-15 04:04:27'),
(12, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No. of staff that are rewarded for recorded outstanding performance. It awaits your review', 7, 20, 'Not Read', '2025-08-15 04:05:22', '2025-08-15 04:05:22'),
(13, 7, '', 'Tracking Submitted', 'Your request on No. of staff that are rewarded for recorded outstanding performance has been submitted to Delivery Department. It is waiting for review', 3, 20, 'Not Read', '2025-08-15 04:05:22', '2025-08-15 04:05:22'),
(14, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No. of staff that are rewarded for recorded outstanding performance. It awaits your review', 7, 21, 'Not Read', '2025-08-15 04:05:58', '2025-08-15 04:05:58'),
(15, 7, '', 'Tracking Submitted', 'Your request on No. of staff that are rewarded for recorded outstanding performance has been submitted to Delivery Department. It is waiting for review', 3, 21, 'Not Read', '2025-08-15 04:05:58', '2025-08-15 04:05:58'),
(16, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No. of staff that are rewarded for recorded outstanding performance. It awaits your review', 7, 22, 'Not Read', '2025-08-15 04:06:24', '2025-08-15 04:06:24'),
(17, 7, '', 'Tracking Submitted', 'Your request on No. of staff that are rewarded for recorded outstanding performance has been submitted to Delivery Department. It is waiting for review', 3, 22, 'Not Read', '2025-08-15 04:06:24', '2025-08-15 04:06:24'),
(18, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No. of staff that are rewarded for recorded outstanding performance. It awaits your review', 7, 23, 'Not Read', '2025-08-15 04:06:50', '2025-08-15 04:06:50'),
(19, 7, '', 'Tracking Submitted', 'Your request on No. of staff that are rewarded for recorded outstanding performance has been submitted to Delivery Department. It is waiting for review', 3, 23, 'Not Read', '2025-08-15 04:06:50', '2025-08-15 04:06:50'),
(20, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No. of staff that are sanctioned for recorded poor performance. It awaits your review', 7, 24, 'Not Read', '2025-08-15 04:08:42', '2025-08-15 04:08:42'),
(21, 7, '', 'Tracking Submitted', 'Your request on No. of staff that are sanctioned for recorded poor performance has been submitted to Delivery Department. It is waiting for review', 3, 24, 'Not Read', '2025-08-15 04:08:42', '2025-08-15 04:08:42'),
(22, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No. of staff that are sanctioned for recorded poor performance. It awaits your review', 7, 25, 'Not Read', '2025-08-15 04:09:09', '2025-08-15 04:09:09'),
(23, 7, '', 'Tracking Submitted', 'Your request on No. of staff that are sanctioned for recorded poor performance has been submitted to Delivery Department. It is waiting for review', 3, 25, 'Not Read', '2025-08-15 04:09:09', '2025-08-15 04:09:09'),
(24, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No. of staff that are sanctioned for recorded poor performance. It awaits your review', 7, 26, 'Not Read', '2025-08-15 04:09:43', '2025-08-15 04:09:43'),
(25, 7, '', 'Tracking Submitted', 'Your request on No. of staff that are sanctioned for recorded poor performance has been submitted to Delivery Department. It is waiting for review', 3, 26, 'Not Read', '2025-08-15 04:09:43', '2025-08-15 04:09:43'),
(26, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No. of staff that are sanctioned for recorded poor performance. It awaits your review', 7, 27, 'Not Read', '2025-08-15 04:10:08', '2025-08-15 04:10:08'),
(27, 7, '', 'Tracking Submitted', 'Your request on No. of staff that are sanctioned for recorded poor performance has been submitted to Delivery Department. It is waiting for review', 3, 27, 'Not Read', '2025-08-15 04:10:08', '2025-08-15 04:10:08'),
(28, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No of MDAs introduced to Corporate Planning. It awaits your review', 7, 28, 'Not Read', '2025-08-15 04:11:13', '2025-08-15 04:11:13'),
(29, 7, '', 'Tracking Submitted', 'Your request on No of MDAs introduced to Corporate Planning has been submitted to Delivery Department. It is waiting for review', 3, 28, 'Not Read', '2025-08-15 04:11:13', '2025-08-15 04:11:13'),
(30, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No of MDAs introduced to Corporate Planning. It awaits your review', 7, 29, 'Not Read', '2025-08-15 04:11:33', '2025-08-15 04:11:33'),
(31, 7, '', 'Tracking Submitted', 'Your request on No of MDAs introduced to Corporate Planning has been submitted to Delivery Department. It is waiting for review', 3, 29, 'Not Read', '2025-08-15 04:11:33', '2025-08-15 04:11:33'),
(32, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No of MDAs introduced to Corporate Planning. It awaits your review', 7, 30, 'Not Read', '2025-08-15 04:11:54', '2025-08-15 04:11:54'),
(33, 7, '', 'Tracking Submitted', 'Your request on No of MDAs introduced to Corporate Planning has been submitted to Delivery Department. It is waiting for review', 3, 30, 'Not Read', '2025-08-15 04:11:54', '2025-08-15 04:11:54'),
(34, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No of MDAs introduced to Corporate Planning. It awaits your review', 7, 31, 'Not Read', '2025-08-15 04:12:15', '2025-08-15 04:12:15'),
(35, 7, '', 'Tracking Submitted', 'Your request on No of MDAs introduced to Corporate Planning has been submitted to Delivery Department. It is waiting for review', 3, 31, 'Not Read', '2025-08-15 04:12:15', '2025-08-15 04:12:15'),
(36, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented. It awaits your review', 7, 32, 'Not Read', '2025-08-15 04:13:41', '2025-08-15 04:13:41'),
(37, 7, '', 'Tracking Submitted', 'Your request on Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented has been submitted to Delivery Department. It is waiting for review', 3, 32, 'Not Read', '2025-08-15 04:13:41', '2025-08-15 04:13:41'),
(38, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented. It awaits your review', 7, 33, 'Not Read', '2025-08-15 04:14:05', '2025-08-15 04:14:05'),
(39, 7, '', 'Tracking Submitted', 'Your request on Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented has been submitted to Delivery Department. It is waiting for review', 3, 33, 'Not Read', '2025-08-15 04:14:05', '2025-08-15 04:14:05'),
(40, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented. It awaits your review', 7, 34, 'Not Read', '2025-08-15 04:14:25', '2025-08-15 04:14:25'),
(41, 7, '', 'Tracking Submitted', 'Your request on Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented has been submitted to Delivery Department. It is waiting for review', 3, 34, 'Not Read', '2025-08-15 04:14:25', '2025-08-15 04:14:25'),
(42, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented. It awaits your review', 7, 35, 'Not Read', '2025-08-15 04:14:45', '2025-08-15 04:14:45'),
(43, 7, '', 'Tracking Submitted', 'Your request on Executive Orders Issued on Development and Implementation of Service Charters in MDAs (No. of E.O Provisions implemented has been submitted to Delivery Department. It is waiting for review', 3, 35, 'Not Read', '2025-08-15 04:14:45', '2025-08-15 04:14:45'),
(44, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM. It awaits your review', 7, 36, 'Not Read', '2025-08-15 04:16:03', '2025-08-15 04:16:03'),
(45, 7, '', 'Tracking Submitted', 'Your request on Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM has been submitted to Delivery Department. It is waiting for review', 3, 36, 'Not Read', '2025-08-15 04:16:03', '2025-08-15 04:16:03'),
(46, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM. It awaits your review', 7, 37, 'Not Read', '2025-08-15 04:16:30', '2025-08-15 04:16:30'),
(47, 7, '', 'Tracking Submitted', 'Your request on Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM has been submitted to Delivery Department. It is waiting for review', 3, 37, 'Not Read', '2025-08-15 04:16:30', '2025-08-15 04:16:30'),
(48, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM. It awaits your review', 7, 38, 'Not Read', '2025-08-15 04:16:55', '2025-08-15 04:16:55'),
(49, 7, '', 'Tracking Submitted', 'Your request on Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM has been submitted to Delivery Department. It is waiting for review', 3, 38, 'Not Read', '2025-08-15 04:16:55', '2025-08-15 04:16:55'),
(50, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM. It awaits your review', 7, 39, 'Not Read', '2025-08-15 04:17:17', '2025-08-15 04:17:17'),
(51, 7, '', 'Tracking Submitted', 'Your request on Number of comprehensive service charters in MDAs (New and Reviewed)  with bi-annual report from SERVICOM has been submitted to Delivery Department. It is waiting for review', 3, 39, 'Not Read', '2025-08-15 04:17:18', '2025-08-15 04:17:18'),
(52, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of staff needs that are met  through recruitments across all MDAs. It awaits your review', 7, 40, 'Not Read', '2025-08-15 04:21:58', '2025-08-15 04:21:58'),
(53, 7, '', 'Tracking Submitted', 'Your request on % of staff needs that are met  through recruitments across all MDAs has been submitted to Delivery Department. It is waiting for review', 3, 40, 'Not Read', '2025-08-15 04:21:58', '2025-08-15 04:21:58'),
(54, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of staff needs that are met  through recruitments across all MDAs. It awaits your review', 7, 41, 'Not Read', '2025-08-15 04:22:32', '2025-08-15 04:22:32'),
(55, 7, '', 'Tracking Submitted', 'Your request on % of staff needs that are met  through recruitments across all MDAs has been submitted to Delivery Department. It is waiting for review', 3, 41, 'Not Read', '2025-08-15 04:22:32', '2025-08-15 04:22:32'),
(56, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of staff needs that are met  through recruitments across all MDAs. It awaits your review', 7, 42, 'Not Read', '2025-08-15 04:22:49', '2025-08-15 04:22:49'),
(57, 7, '', 'Tracking Submitted', 'Your request on % of staff needs that are met  through recruitments across all MDAs has been submitted to Delivery Department. It is waiting for review', 3, 42, 'Not Read', '2025-08-15 04:22:49', '2025-08-15 04:22:49'),
(58, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of staff needs that are met  through recruitments across all MDAs. It awaits your review', 7, 43, 'Not Read', '2025-08-15 04:23:11', '2025-08-15 04:23:11'),
(59, 7, '', 'Tracking Submitted', 'Your request on % of staff needs that are met  through recruitments across all MDAs has been submitted to Delivery Department. It is waiting for review', 3, 43, 'Not Read', '2025-08-15 04:23:11', '2025-08-15 04:23:11'),
(60, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of Recruited staff that are females. It awaits your review', 7, 44, 'Not Read', '2025-08-15 04:23:53', '2025-08-15 04:23:53'),
(61, 7, '', 'Tracking Submitted', 'Your request on % of Recruited staff that are females has been submitted to Delivery Department. It is waiting for review', 3, 44, 'Not Read', '2025-08-15 04:23:54', '2025-08-15 04:23:54'),
(62, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of Recruited staff that are females. It awaits your review', 7, 45, 'Not Read', '2025-08-15 04:24:23', '2025-08-15 04:24:23'),
(63, 7, '', 'Tracking Submitted', 'Your request on % of Recruited staff that are females has been submitted to Delivery Department. It is waiting for review', 3, 45, 'Not Read', '2025-08-15 04:24:23', '2025-08-15 04:24:23'),
(64, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of Recruited staff that are females. It awaits your review', 7, 46, 'Not Read', '2025-08-15 04:24:43', '2025-08-15 04:24:43'),
(65, 7, '', 'Tracking Submitted', 'Your request on % of Recruited staff that are females has been submitted to Delivery Department. It is waiting for review', 3, 46, 'Not Read', '2025-08-15 04:24:43', '2025-08-15 04:24:43'),
(66, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of Recruited staff that are females. It awaits your review', 7, 47, 'Not Read', '2025-08-15 04:25:01', '2025-08-15 04:25:01'),
(67, 7, '', 'Tracking Submitted', 'Your request on % of Recruited staff that are females has been submitted to Delivery Department. It is waiting for review', 3, 47, 'Not Read', '2025-08-15 04:25:02', '2025-08-15 04:25:02'),
(68, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of Recruited staff that are people with disabilities. It awaits your review', 7, 48, 'Not Read', '2025-08-15 04:26:28', '2025-08-15 04:26:28'),
(69, 7, '', 'Tracking Submitted', 'Your request on % of Recruited staff that are people with disabilities has been submitted to Delivery Department. It is waiting for review', 3, 48, 'Not Read', '2025-08-15 04:26:28', '2025-08-15 04:26:28'),
(70, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of Recruited staff that are people with disabilities. It awaits your review', 7, 49, 'Not Read', '2025-08-15 04:26:53', '2025-08-15 04:26:53'),
(71, 7, '', 'Tracking Submitted', 'Your request on % of Recruited staff that are people with disabilities has been submitted to Delivery Department. It is waiting for review', 3, 49, 'Not Read', '2025-08-15 04:26:53', '2025-08-15 04:26:53'),
(72, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of Recruited staff that are people with disabilities. It awaits your review', 7, 50, 'Not Read', '2025-08-15 04:27:43', '2025-08-15 04:27:43'),
(73, 7, '', 'Tracking Submitted', 'Your request on % of Recruited staff that are people with disabilities has been submitted to Delivery Department. It is waiting for review', 3, 50, 'Not Read', '2025-08-15 04:27:43', '2025-08-15 04:27:43'),
(74, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on % of Recruited staff that are people with disabilities. It awaits your review', 7, 51, 'Not Read', '2025-08-15 04:28:18', '2025-08-15 04:28:18'),
(75, 7, '', 'Tracking Submitted', 'Your request on % of Recruited staff that are people with disabilities has been submitted to Delivery Department. It is waiting for review', 3, 51, 'Not Read', '2025-08-15 04:28:18', '2025-08-15 04:28:18'),
(76, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed). It awaits your review', 7, 52, 'Not Read', '2025-08-15 04:29:10', '2025-08-15 04:29:10'),
(77, 7, '', 'Tracking Submitted', 'Your request on Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed) has been submitted to Delivery Department. It is waiting for review', 3, 52, 'Not Read', '2025-08-15 04:29:10', '2025-08-15 04:29:10'),
(78, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed). It awaits your review', 7, 53, 'Not Read', '2025-08-15 04:29:33', '2025-08-15 04:29:33'),
(79, 7, '', 'Tracking Submitted', 'Your request on Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed) has been submitted to Delivery Department. It is waiting for review', 3, 53, 'Not Read', '2025-08-15 04:29:33', '2025-08-15 04:29:33'),
(80, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed). It awaits your review', 7, 54, 'Not Read', '2025-08-15 04:29:56', '2025-08-15 04:29:56'),
(81, 7, '', 'Tracking Submitted', 'Your request on Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed) has been submitted to Delivery Department. It is waiting for review', 3, 54, 'Not Read', '2025-08-15 04:29:56', '2025-08-15 04:29:56'),
(82, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed). It awaits your review', 7, 55, 'Not Read', '2025-08-15 04:30:22', '2025-08-15 04:30:22'),
(83, 7, '', 'Tracking Submitted', 'Your request on Skill/Manpower Gaps Analysis conducted across all MDAs and periodically updated (Number of MDAs covered / reviewed) has been submitted to Delivery Department. It is waiting for review', 3, 55, 'Not Read', '2025-08-15 04:30:22', '2025-08-15 04:30:22'),
(84, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Percentage of retired staff paid their gratuity as when due. It awaits your review', 7, 56, 'Not Read', '2025-08-15 04:38:50', '2025-08-15 04:38:50'),
(85, 7, '', 'Tracking Submitted', 'Your request on Percentage of retired staff paid their gratuity as when due has been submitted to Delivery Department. It is waiting for review', 3, 56, 'Not Read', '2025-08-15 04:38:50', '2025-08-15 04:38:50'),
(86, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Percentage of retired staff paid their gratuity as when due. It awaits your review', 7, 57, 'Not Read', '2025-08-15 04:39:17', '2025-08-15 04:39:17'),
(87, 7, '', 'Tracking Submitted', 'Your request on Percentage of retired staff paid their gratuity as when due has been submitted to Delivery Department. It is waiting for review', 3, 57, 'Not Read', '2025-08-15 04:39:17', '2025-08-15 04:39:17'),
(88, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Percentage of retired staff paid their gratuity as when due. It awaits your review', 7, 58, 'Not Read', '2025-08-15 04:39:47', '2025-08-15 04:39:47'),
(89, 7, '', 'Tracking Submitted', 'Your request on Percentage of retired staff paid their gratuity as when due has been submitted to Delivery Department. It is waiting for review', 3, 58, 'Not Read', '2025-08-15 04:39:47', '2025-08-15 04:39:47'),
(90, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Percentage of retired staff paid their gratuity as when due. It awaits your review', 7, 59, 'Not Read', '2025-08-15 04:41:10', '2025-08-15 04:41:10'),
(91, 7, '', 'Tracking Submitted', 'Your request on Percentage of retired staff paid their gratuity as when due has been submitted to Delivery Department. It is waiting for review', 3, 59, 'Not Read', '2025-08-15 04:41:10', '2025-08-15 04:41:10'),
(92, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Percentage of retired staff paid their monthly pension as when due. It awaits your review', 7, 60, 'Not Read', '2025-08-15 04:41:52', '2025-08-15 04:41:52'),
(93, 7, '', 'Tracking Submitted', 'Your request on Percentage of retired staff paid their monthly pension as when due has been submitted to Delivery Department. It is waiting for review', 3, 60, 'Not Read', '2025-08-15 04:41:52', '2025-08-15 04:41:52'),
(94, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Percentage of retired staff paid their monthly pension as when due. It awaits your review', 7, 61, 'Not Read', '2025-08-15 04:42:12', '2025-08-15 04:42:12'),
(95, 7, '', 'Tracking Submitted', 'Your request on Percentage of retired staff paid their monthly pension as when due has been submitted to Delivery Department. It is waiting for review', 3, 61, 'Not Read', '2025-08-15 04:42:12', '2025-08-15 04:42:12'),
(96, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Percentage of retired staff paid their monthly pension as when due. It awaits your review', 7, 62, 'Not Read', '2025-08-15 04:43:01', '2025-08-15 04:43:01'),
(97, 7, '', 'Tracking Submitted', 'Your request on Percentage of retired staff paid their monthly pension as when due has been submitted to Delivery Department. It is waiting for review', 3, 62, 'Not Read', '2025-08-15 04:43:01', '2025-08-15 04:43:01'),
(98, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Percentage of retired staff paid their monthly pension as when due. It awaits your review', 7, 63, 'Not Read', '2025-08-15 04:43:22', '2025-08-15 04:43:22'),
(99, 7, '', 'Tracking Submitted', 'Your request on Percentage of retired staff paid their monthly pension as when due has been submitted to Delivery Department. It is waiting for review', 3, 63, 'Not Read', '2025-08-15 04:43:22', '2025-08-15 04:43:22'),
(100, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No of training programmes implemented by the State MDI across various cadres in the State Civil Service. It awaits your review', 7, 64, 'Not Read', '2025-08-15 04:45:20', '2025-08-15 04:45:20'),
(101, 7, '', 'Tracking Submitted', 'Your request on No of training programmes implemented by the State MDI across various cadres in the State Civil Service has been submitted to Delivery Department. It is waiting for review', 3, 64, 'Not Read', '2025-08-15 04:45:20', '2025-08-15 04:45:20'),
(102, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No of training programmes implemented by the State MDI across various cadres in the State Civil Service. It awaits your review', 7, 65, 'Not Read', '2025-08-15 04:45:41', '2025-08-15 04:45:41'),
(103, 7, '', 'Tracking Submitted', 'Your request on No of training programmes implemented by the State MDI across various cadres in the State Civil Service has been submitted to Delivery Department. It is waiting for review', 3, 65, 'Not Read', '2025-08-15 04:45:41', '2025-08-15 04:45:41'),
(104, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No of training programmes implemented by the State MDI across various cadres in the State Civil Service. It awaits your review', 7, 66, 'Not Read', '2025-08-15 04:46:10', '2025-08-15 04:46:10'),
(105, 7, '', 'Tracking Submitted', 'Your request on No of training programmes implemented by the State MDI across various cadres in the State Civil Service has been submitted to Delivery Department. It is waiting for review', 3, 66, 'Not Read', '2025-08-15 04:46:10', '2025-08-15 04:46:10'),
(106, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on No of training programmes implemented by the State MDI across various cadres in the State Civil Service. It awaits your review', 7, 67, 'Not Read', '2025-08-15 04:46:36', '2025-08-15 04:46:36'),
(107, 7, '', 'Tracking Submitted', 'Your request on No of training programmes implemented by the State MDI across various cadres in the State Civil Service has been submitted to Delivery Department. It is waiting for review', 3, 67, 'Not Read', '2025-08-15 04:46:36', '2025-08-15 04:46:36'),
(108, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of Civil Servants Trained by State MDI across all MDAs covering various cadres. It awaits your review', 7, 68, 'Not Read', '2025-08-15 04:47:27', '2025-08-15 04:47:27'),
(109, 7, '', 'Tracking Submitted', 'Your request on Number of Civil Servants Trained by State MDI across all MDAs covering various cadres has been submitted to Delivery Department. It is waiting for review', 3, 68, 'Not Read', '2025-08-15 04:47:27', '2025-08-15 04:47:27'),
(110, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of Civil Servants Trained by State MDI across all MDAs covering various cadres. It awaits your review', 7, 69, 'Not Read', '2025-08-15 04:47:46', '2025-08-15 04:47:46'),
(111, 7, '', 'Tracking Submitted', 'Your request on Number of Civil Servants Trained by State MDI across all MDAs covering various cadres has been submitted to Delivery Department. It is waiting for review', 3, 69, 'Not Read', '2025-08-15 04:47:46', '2025-08-15 04:47:46'),
(112, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of Civil Servants Trained by State MDI across all MDAs covering various cadres. It awaits your review', 7, 70, 'Not Read', '2025-08-15 04:48:04', '2025-08-15 04:48:04'),
(113, 7, '', 'Tracking Submitted', 'Your request on Number of Civil Servants Trained by State MDI across all MDAs covering various cadres has been submitted to Delivery Department. It is waiting for review', 3, 70, 'Not Read', '2025-08-15 04:48:04', '2025-08-15 04:48:04'),
(114, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of Civil Servants Trained by State MDI across all MDAs covering various cadres. It awaits your review', 7, 71, 'Not Read', '2025-08-15 04:48:36', '2025-08-15 04:48:36'),
(115, 7, '', 'Tracking Submitted', 'Your request on Number of Civil Servants Trained by State MDI across all MDAs covering various cadres has been submitted to Delivery Department. It is waiting for review', 3, 71, 'Not Read', '2025-08-15 04:48:36', '2025-08-15 04:48:36'),
(116, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Total number of participated in short term Capacity Building Programme for Staff across MDAs. It awaits your review', 7, 72, 'Not Read', '2025-08-15 04:49:27', '2025-08-15 04:49:27'),
(117, 7, '', 'Tracking Submitted', 'Your request on Total number of participated in short term Capacity Building Programme for Staff across MDAs has been submitted to Delivery Department. It is waiting for review', 3, 72, 'Not Read', '2025-08-15 04:49:27', '2025-08-15 04:49:27'),
(118, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Total number of participated in short term Capacity Building Programme for Staff across MDAs. It awaits your review', 7, 73, 'Not Read', '2025-08-15 04:49:46', '2025-08-15 04:49:46'),
(119, 7, '', 'Tracking Submitted', 'Your request on Total number of participated in short term Capacity Building Programme for Staff across MDAs has been submitted to Delivery Department. It is waiting for review', 3, 73, 'Not Read', '2025-08-15 04:49:46', '2025-08-15 04:49:46'),
(120, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Total number of participated in short term Capacity Building Programme for Staff across MDAs. It awaits your review', 7, 74, 'Not Read', '2025-08-15 04:50:05', '2025-08-15 04:50:05'),
(121, 7, '', 'Tracking Submitted', 'Your request on Total number of participated in short term Capacity Building Programme for Staff across MDAs has been submitted to Delivery Department. It is waiting for review', 3, 74, 'Not Read', '2025-08-15 04:50:05', '2025-08-15 04:50:05'),
(122, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Total number of participated in short term Capacity Building Programme for Staff across MDAs. It awaits your review', 7, 75, 'Not Read', '2025-08-15 04:50:39', '2025-08-15 04:50:39'),
(123, 7, '', 'Tracking Submitted', 'Your request on Total number of participated in short term Capacity Building Programme for Staff across MDAs has been submitted to Delivery Department. It is waiting for review', 3, 75, 'Not Read', '2025-08-15 04:50:39', '2025-08-15 04:50:39'),
(124, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions.. It awaits your review', 7, 76, 'Not Read', '2025-08-15 04:51:27', '2025-08-15 04:51:27'),
(125, 7, '', 'Tracking Submitted', 'Your request on Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions. has been submitted to Delivery Department. It is waiting for review', 3, 76, 'Not Read', '2025-08-15 04:51:27', '2025-08-15 04:51:27'),
(126, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions.. It awaits your review', 7, 77, 'Not Read', '2025-08-15 04:51:57', '2025-08-15 04:51:57'),
(127, 7, '', 'Tracking Submitted', 'Your request on Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions. has been submitted to Delivery Department. It is waiting for review', 3, 77, 'Not Read', '2025-08-15 04:51:57', '2025-08-15 04:51:57'),
(128, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions.. It awaits your review', 7, 78, 'Not Read', '2025-08-15 04:52:25', '2025-08-15 04:52:25'),
(129, 7, '', 'Tracking Submitted', 'Your request on Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions. has been submitted to Delivery Department. It is waiting for review', 3, 78, 'Not Read', '2025-08-15 04:52:25', '2025-08-15 04:52:25'),
(130, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions.. It awaits your review', 7, 79, 'Not Read', '2025-08-15 04:53:11', '2025-08-15 04:53:11'),
(131, 7, '', 'Tracking Submitted', 'Your request on Number of staff trained on long term Programme through In-Service Training and other established collaborations with tertiary institutions. has been submitted to Delivery Department. It is waiting for review', 3, 79, 'Not Read', '2025-08-15 04:53:11', '2025-08-15 04:53:11'),
(132, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of staff trained through professional bodies on their task related skills. It awaits your review', 7, 80, 'Not Read', '2025-08-15 04:54:24', '2025-08-15 04:54:24'),
(133, 7, '', 'Tracking Submitted', 'Your request on Number of staff trained through professional bodies on their task related skills has been submitted to Delivery Department. It is waiting for review', 3, 80, 'Not Read', '2025-08-15 04:54:24', '2025-08-15 04:54:24'),
(134, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of staff trained through professional bodies on their task related skills. It awaits your review', 7, 81, 'Not Read', '2025-08-15 04:55:02', '2025-08-15 04:55:02'),
(135, 7, '', 'Tracking Submitted', 'Your request on Number of staff trained through professional bodies on their task related skills has been submitted to Delivery Department. It is waiting for review', 3, 81, 'Not Read', '2025-08-15 04:55:02', '2025-08-15 04:55:02'),
(136, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of staff trained through professional bodies on their task related skills. It awaits your review', 7, 82, 'Not Read', '2025-08-15 04:55:25', '2025-08-15 04:55:25'),
(137, 7, '', 'Tracking Submitted', 'Your request on Number of staff trained through professional bodies on their task related skills has been submitted to Delivery Department. It is waiting for review', 3, 82, 'Not Read', '2025-08-15 04:55:25', '2025-08-15 04:55:25'),
(138, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Number of staff trained through professional bodies on their task related skills. It awaits your review', 7, 83, 'Not Read', '2025-08-15 04:55:54', '2025-08-15 04:55:54'),
(139, 7, '', 'Tracking Submitted', 'Your request on Number of staff trained through professional bodies on their task related skills has been submitted to Delivery Department. It is waiting for review', 3, 83, 'Not Read', '2025-08-15 04:55:54', '2025-08-15 04:55:54'),
(140, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others. It awaits your review', 7, 84, 'Not Read', '2025-08-15 04:57:04', '2025-08-15 04:57:04'),
(141, 7, '', 'Tracking Submitted', 'Your request on Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others has been submitted to Delivery Department. It is waiting for review', 3, 84, 'Not Read', '2025-08-15 04:57:04', '2025-08-15 04:57:04'),
(142, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others. It awaits your review', 7, 85, 'Not Read', '2025-08-15 04:57:21', '2025-08-15 04:57:21'),
(143, 7, '', 'Tracking Submitted', 'Your request on Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others has been submitted to Delivery Department. It is waiting for review', 3, 85, 'Not Read', '2025-08-15 04:57:21', '2025-08-15 04:57:21'),
(144, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others. It awaits your review', 7, 86, 'Not Read', '2025-08-15 04:57:40', '2025-08-15 04:57:40'),
(145, 7, '', 'Tracking Submitted', 'Your request on Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others has been submitted to Delivery Department. It is waiting for review', 3, 86, 'Not Read', '2025-08-15 04:57:40', '2025-08-15 04:57:40'),
(146, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others. It awaits your review', 7, 87, 'Not Read', '2025-08-15 04:58:04', '2025-08-15 04:58:04'),
(147, 7, '', 'Tracking Submitted', 'Your request on Repositioned and Improved Perforamance of the State MDI as assessed by No. of programmes mounted by the Institute across State MDAs, LGAs and others has been submitted to Delivery Department. It is waiting for review', 3, 87, 'Not Read', '2025-08-15 04:58:04', '2025-08-15 04:58:04'),
(148, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others. It awaits your review', 7, 88, 'Not Read', '2025-08-15 04:58:59', '2025-08-15 04:58:59'),
(149, 7, '', 'Tracking Submitted', 'Your request on Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others has been submitted to Delivery Department. It is waiting for review', 3, 88, 'Not Read', '2025-08-15 04:58:59', '2025-08-15 04:58:59'),
(150, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others. It awaits your review', 7, 89, 'Not Read', '2025-08-15 04:59:20', '2025-08-15 04:59:20'),
(151, 7, '', 'Tracking Submitted', 'Your request on Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others has been submitted to Delivery Department. It is waiting for review', 3, 89, 'Not Read', '2025-08-15 04:59:20', '2025-08-15 04:59:20'),
(152, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others. It awaits your review', 7, 90, 'Not Read', '2025-08-15 04:59:51', '2025-08-15 04:59:51'),
(153, 7, '', 'Tracking Submitted', 'Your request on Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others has been submitted to Delivery Department. It is waiting for review', 3, 90, 'Not Read', '2025-08-15 04:59:51', '2025-08-15 04:59:51'),
(154, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others. It awaits your review', 7, 91, 'Not Read', '2025-08-15 05:00:26', '2025-08-15 05:00:26'),
(155, 7, '', 'Tracking Submitted', 'Your request on Repositioned and Improved Perforamance of the State MDI as assessed by No. of people trained by the Institute across State MDAs, LGAs and others has been submitted to Delivery Department. It is waiting for review', 3, 91, 'Not Read', '2025-08-15 05:00:26', '2025-08-15 05:00:26'),
(156, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute. It awaits your review', 7, 92, 'Not Read', '2025-08-15 05:01:13', '2025-08-15 05:01:13'),
(157, 7, '', 'Tracking Submitted', 'Your request on Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute has been submitted to Delivery Department. It is waiting for review', 3, 92, 'Not Read', '2025-08-15 05:01:13', '2025-08-15 05:01:13'),
(158, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute. It awaits your review', 7, 93, 'Not Read', '2025-08-15 05:01:32', '2025-08-15 05:01:32'),
(159, 7, '', 'Tracking Submitted', 'Your request on Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute has been submitted to Delivery Department. It is waiting for review', 3, 93, 'Not Read', '2025-08-15 05:01:32', '2025-08-15 05:01:32'),
(160, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute. It awaits your review', 7, 94, 'Not Read', '2025-08-15 05:01:59', '2025-08-15 05:01:59'),
(161, 7, '', 'Tracking Submitted', 'Your request on Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute has been submitted to Delivery Department. It is waiting for review', 3, 94, 'Not Read', '2025-08-15 05:01:59', '2025-08-15 05:01:59'),
(162, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute. It awaits your review', 7, 95, 'Not Read', '2025-08-15 05:02:37', '2025-08-15 05:02:37'),
(163, 7, '', 'Tracking Submitted', 'Your request on Attainment of Annual Revenue Estimates (Targets as per Approved Budget) by the Manpower Development Institute has been submitted to Delivery Department. It is waiting for review', 3, 95, 'Not Read', '2025-08-15 05:02:37', '2025-08-15 05:02:37'),
(164, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Annual growth in revenues generated by MDI. It awaits your review', 7, 96, 'Not Read', '2025-08-15 05:03:09', '2025-08-15 05:03:09'),
(165, 7, '', 'Tracking Submitted', 'Your request on Annual growth in revenues generated by MDI has been submitted to Delivery Department. It is waiting for review', 3, 96, 'Not Read', '2025-08-15 05:03:10', '2025-08-15 05:03:10'),
(166, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Annual growth in revenues generated by MDI. It awaits your review', 7, 97, 'Not Read', '2025-08-15 05:03:41', '2025-08-15 05:03:41'),
(167, 7, '', 'Tracking Submitted', 'Your request on Annual growth in revenues generated by MDI has been submitted to Delivery Department. It is waiting for review', 3, 97, 'Not Read', '2025-08-15 05:03:41', '2025-08-15 05:03:41'),
(168, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Annual growth in revenues generated by MDI. It awaits your review', 7, 98, 'Not Read', '2025-08-15 05:04:11', '2025-08-15 05:04:11'),
(169, 7, '', 'Tracking Submitted', 'Your request on Annual growth in revenues generated by MDI has been submitted to Delivery Department. It is waiting for review', 3, 98, 'Not Read', '2025-08-15 05:04:11', '2025-08-15 05:04:11'),
(170, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Annual growth in revenues generated by MDI. It awaits your review', 7, 99, 'Not Read', '2025-08-15 05:04:35', '2025-08-15 05:04:35'),
(171, 7, '', 'Tracking Submitted', 'Your request on Annual growth in revenues generated by MDI has been submitted to Delivery Department. It is waiting for review', 3, 99, 'Not Read', '2025-08-15 05:04:35', '2025-08-15 05:04:35'),
(172, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council. It awaits your review', 7, 100, 'Not Read', '2025-08-15 05:11:11', '2025-08-15 05:11:11'),
(173, 7, '', 'Tracking Submitted', 'Your request on Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council has been submitted to Delivery Department. It is waiting for review', 3, 100, 'Not Read', '2025-08-15 05:11:11', '2025-08-15 05:11:11'),
(174, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council. It awaits your review', 7, 101, 'Not Read', '2025-08-15 05:11:34', '2025-08-15 05:11:34'),
(175, 7, '', 'Tracking Submitted', 'Your request on Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council has been submitted to Delivery Department. It is waiting for review', 3, 101, 'Not Read', '2025-08-15 05:11:34', '2025-08-15 05:11:34'),
(176, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council. It awaits your review', 7, 102, 'Not Read', '2025-08-15 05:12:19', '2025-08-15 05:12:19'),
(177, 7, '', 'Tracking Submitted', 'Your request on Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council has been submitted to Delivery Department. It is waiting for review', 3, 102, 'Not Read', '2025-08-15 05:12:19', '2025-08-15 05:12:19'),
(178, 3, '', 'Review Request', 'Sector Head of Office of the Head of the State Civil Service made a submission on Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council. It awaits your review', 7, 103, 'Not Read', '2025-08-15 05:12:43', '2025-08-15 05:12:43'),
(179, 7, '', 'Tracking Submitted', 'Your request on Resuscitated State Manpower Committee as assessed by qualitative bi-annual reports submitted to the State Executive Council has been submitted to Delivery Department. It is waiting for review', 3, 103, 'Not Read', '2025-08-15 05:12:44', '2025-08-15 05:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
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
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `scopes` text DEFAULT NULL,
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
  `name` varchar(255) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect` text NOT NULL,
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
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(15, 1, 4, '900', '2024-03-03', 2024, '900', 'kjhvhbj,', '700', 'mhvjhjb', 'Confirmed', NULL, '2024-03-04 20:19:07', '2024-03-15 11:54:50'),
(16, 15, 1, '20', '2025-08-14', 0, '0', 'Measurement Yardstick not defined yet.', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:03:10', '2025-08-15 04:03:10'),
(17, 15, 2, '20', '2025-08-14', 0, '0', 'Measurement Yardstick not defined yet.', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:03:35', '2025-08-15 04:03:35'),
(18, 15, 3, '20', '2025-08-14', 0, '0', 'Measurement Yardstick not defined yet.', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:04:05', '2025-08-15 04:04:05'),
(19, 15, 4, '10', '2025-08-14', 0, '0', 'Measurement Yardstick not defined yet.', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:04:27', '2025-08-15 04:04:27'),
(20, 16, 1, '2', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:05:22', '2025-08-15 04:05:22'),
(21, 16, 2, '3', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:05:58', '2025-08-15 04:05:58'),
(22, 16, 3, '3', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:06:24', '2025-08-15 04:06:24'),
(23, 16, 4, '2', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:06:50', '2025-08-15 04:06:50'),
(24, 17, 1, '3', '2025-08-14', 0, '3', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:08:42', '2025-08-15 04:08:42'),
(25, 17, 2, '4', '2025-08-14', 0, '3', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:09:09', '2025-08-15 04:09:09'),
(26, 17, 3, '3', '2025-08-14', 0, '2', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:09:43', '2025-08-15 04:09:43'),
(27, 17, 4, '3', '2025-08-14', 0, '2', 'Scores of staff were sanctioned following recommendations by a Whitepaper Committee. The MDAs included Ministry of Health and Tertiary Educational Institutions', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:10:08', '2025-08-15 04:10:08'),
(28, 18, 1, '1', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:11:13', '2025-08-15 04:11:13'),
(29, 18, 2, '2', '2025-08-14', 0, '2', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:11:33', '2025-08-15 04:11:33'),
(30, 18, 3, '1', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:11:54', '2025-08-15 04:11:54'),
(31, 18, 4, '1', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:12:15', '2025-08-15 04:12:15'),
(32, 19, 1, '2', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:13:41', '2025-08-15 04:13:41'),
(33, 19, 2, '2', '2025-08-14', 0, '0', 'Servicom not yet reactivated and Exe. Order Issued', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:14:05', '2025-08-15 04:14:05'),
(34, 19, 3, '3', '2025-08-14', 0, '0', 'Servicom not yet reactivated and Exe. Order Issued', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:14:25', '2025-08-15 04:14:25'),
(35, 19, 4, '3', '2025-08-14', 0, '0', 'Servicom not yet reactivated and Exe. Order Issued', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:14:45', '2025-08-15 04:14:45'),
(36, 20, 1, '2', '2025-08-14', 0, '0', 'Servicom will soon be reactivated', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:16:03', '2025-08-15 04:16:03'),
(37, 20, 2, '2', '2025-08-14', 0, '0', 'Servicom will soon be reactivated', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:16:30', '2025-08-15 04:16:30'),
(38, 20, 3, '2', '2025-08-14', 0, '0', 'Servicom will soon be reactivated', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:16:55', '2025-08-15 04:16:55'),
(39, 20, 4, '3', '2025-08-14', 0, '0', 'Servicom will soon be reactivated', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:17:17', '2025-08-15 04:17:17'),
(40, 21, 1, '2', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:21:58', '2025-08-15 04:21:58'),
(41, 21, 2, '2', '2025-08-14', 0, '2', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:22:32', '2025-08-15 04:22:32'),
(42, 21, 3, '1', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:22:49', '2025-08-15 04:22:49'),
(43, 21, 4, '1', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:23:11', '2025-08-15 04:23:11'),
(44, 22, 1, '3', '2025-08-14', 0, '3', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:23:53', '2025-08-15 04:23:53'),
(45, 22, 2, '2', '2025-08-14', 0, '2', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:24:23', '2025-08-15 04:24:23'),
(46, 22, 3, '3', '2025-08-14', 0, '3', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:24:43', '2025-08-15 04:24:43'),
(47, 22, 4, '2', '2025-08-14', 0, '2', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:25:01', '2025-08-15 04:25:01'),
(48, 23, 1, '1', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:26:28', '2025-08-15 04:26:28'),
(49, 23, 2, '2', '2025-08-14', 0, '0.4', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:26:53', '2025-08-15 04:26:53'),
(50, 23, 3, '2', '2025-08-14', 0, '0.1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:27:43', '2025-08-15 04:27:43'),
(51, 23, 4, '2', '2025-08-14', 0, '0.4', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:28:18', '2025-08-15 04:28:18'),
(52, 24, 1, '30', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:29:10', '2025-08-15 04:29:10'),
(53, 24, 2, '20', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:29:33', '2025-08-15 04:29:33'),
(54, 24, 3, '30', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:29:56', '2025-08-15 04:29:56'),
(55, 24, 4, '30', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:30:22', '2025-08-15 04:30:22'),
(56, 25, 1, '20', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:38:50', '2025-08-15 04:38:50'),
(57, 25, 2, '20', '2025-08-14', 0, '20.8', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:39:17', '2025-08-15 04:39:17'),
(58, 25, 3, '30', '2025-08-14', 0, '30', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:39:47', '2025-08-15 04:39:47'),
(59, 25, 4, '25', '2025-08-14', 0, '20.18', 'N6,965,000,000 was paid to 2642 out of about ….. persons were paid gratuity and death benefits.', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:41:10', '2025-08-15 04:41:10'),
(60, 26, 1, '25', '2025-08-14', 0, '25', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:41:52', '2025-08-15 04:41:52'),
(61, 26, 2, '25', '2025-08-14', 0, '25', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:42:12', '2025-08-15 04:42:12'),
(62, 26, 3, '25', '2025-08-14', 0, '25', '17,029 pensioners were paid the sum of N6,965,912,178.17', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:43:01', '2025-08-15 04:43:01'),
(63, 26, 4, '25', '2025-08-14', 0, '25', '17,029 pensioners were paid the sum of N6,965,912,178.17', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:43:22', '2025-08-15 04:43:22'),
(64, 27, 1, '2', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:45:20', '2025-08-15 04:45:20'),
(65, 27, 2, '2', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:45:41', '2025-08-15 04:45:41'),
(66, 27, 3, '2', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:46:10', '2025-08-15 04:46:10'),
(67, 27, 4, '1', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:46:36', '2025-08-15 04:46:36'),
(68, 28, 1, '50', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:47:27', '2025-08-15 04:47:27'),
(69, 28, 2, '20', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:47:46', '2025-08-15 04:47:46'),
(70, 28, 3, '20', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:48:04', '2025-08-15 04:48:04'),
(71, 28, 4, '21', '2025-08-14', 0, '21', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:48:36', '2025-08-15 04:48:36'),
(72, 29, 1, '150', '2025-08-14', 0, '200', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:49:27', '2025-08-15 04:49:27'),
(73, 29, 2, '150', '2025-08-14', 0, '150', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:49:46', '2025-08-15 04:49:46'),
(74, 29, 3, '100', '2025-08-14', 0, '100', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:50:05', '2025-08-15 04:50:05'),
(75, 29, 4, '100', '2025-08-14', 0, '100', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:50:39', '2025-08-15 04:50:39'),
(76, 30, 1, '200', '2025-08-14', 0, '200', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:51:27', '2025-08-15 04:51:27'),
(77, 30, 2, '300', '2025-08-14', 0, '300', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:51:57', '2025-08-15 04:51:57'),
(78, 30, 3, '300', '2025-08-14', 0, '218', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:52:25', '2025-08-15 04:52:25'),
(79, 30, 4, '200', '2025-08-14', 0, '200', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:53:11', '2025-08-15 04:53:11'),
(80, 31, 1, '30', '2025-08-14', 0, '12', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:54:24', '2025-08-15 04:54:24'),
(81, 31, 2, '10', '2025-08-14', 0, '10', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:55:02', '2025-08-15 04:55:02'),
(82, 31, 3, '10', '2025-08-14', 0, '5', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:55:25', '2025-08-15 04:55:25'),
(83, 31, 4, '10', '2025-08-14', 0, '5', 'Funds are limited to revolving payment of professional loan: N11,200,000 was paid to 32 applicants', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:55:54', '2025-08-15 04:55:54'),
(84, 32, 1, '3', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:57:04', '2025-08-15 04:57:04'),
(85, 32, 2, '4', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:57:21', '2025-08-15 04:57:21'),
(86, 32, 3, '2', '2025-08-14', 0, '2', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:57:40', '2025-08-15 04:57:40'),
(87, 32, 4, '5', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:58:04', '2025-08-15 04:58:04'),
(88, 33, 1, '100', '2025-08-14', 0, '20', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:58:59', '2025-08-15 04:58:59'),
(89, 33, 2, '100', '2025-08-14', 0, '100', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:59:20', '2025-08-15 04:59:20'),
(90, 33, 3, '200', '2025-08-14', 0, '100', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 04:59:51', '2025-08-15 04:59:51'),
(91, 33, 4, '200', '2025-08-14', 0, '100', 'The breakdown of additional 49 is as follows 32 Computer Certificate - 13 Professional Diploma and 4 Coding', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:00:26', '2025-08-15 05:00:26'),
(92, 34, 1, '20', '2025-08-14', 0, '2', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:01:13', '2025-08-15 05:01:13'),
(93, 34, 2, '30', '2025-08-14', 0, '3', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:01:32', '2025-08-15 05:01:32'),
(94, 34, 3, '30', '2025-08-14', 0, '2', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:01:59', '2025-08-15 05:01:59'),
(95, 34, 4, '30', '2025-08-14', 0, '2', 'Only about  54.72 million reported against a target of N587 million as per 2024 AFS which amounts to less than 10%', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:02:37', '2025-08-15 05:02:37'),
(96, 35, 1, '10', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:03:09', '2025-08-15 05:03:09'),
(97, 35, 2, '3', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:03:41', '2025-08-15 05:03:41'),
(98, 35, 3, '30', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:04:11', '2025-08-15 05:04:11'),
(99, 35, 4, '20', '2025-08-14', 0, '0', 'Actual collection in 2023 was about N112.47 million against N54.72 in 2024 - declines of over 51.3% (2024 AFS)', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:04:35', '2025-08-15 05:04:35'),
(100, 36, 1, '1', '2025-08-14', 0, '0', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:11:11', '2025-08-15 05:11:11'),
(101, 36, 2, '2', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:11:34', '2025-08-15 05:11:34'),
(102, 36, 3, '3', '2025-08-14', 0, '1', NULL, NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:12:19', '2025-08-15 05:12:19'),
(103, 36, 4, '3', '2025-08-14', 0, '1', '3 reports for workforce gap analysis were presented by the HoS to the State Exco (Workforce Technical Committee serving as Manpower Committee)', NULL, NULL, 'Not Confirmed', NULL, '2025-08-15 05:12:43', '2025-08-15 05:12:43');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`id`, `sector_name`, `sector_head_id`, `description`, `created_at`, `updated_at`) VALUES
(4, 'Office of the Secretary to the State Government', NULL, 'SSG', '2025-07-17 03:13:08', '2025-07-17 03:13:08'),
(5, 'Office of the Head of the State Civil Service', NULL, 'State Civil Service', '2025-07-17 03:14:30', '2025-07-17 03:14:30'),
(6, 'Ministry For Special Duties', NULL, 'Special Duties', '2025-07-17 03:14:58', '2025-07-17 03:14:58'),
(7, 'Ministry of Agriculture & Natural Resources', NULL, 'Agriculture & Natural Resources', '2025-07-17 03:15:34', '2025-07-17 03:15:34'),
(8, 'Ministry of Basic Education', NULL, 'Education', '2025-07-17 03:15:58', '2025-07-17 03:15:58'),
(9, 'Ministry of Budget and Economic Planning', NULL, 'Budget & Planning', '2025-07-17 03:16:33', '2025-07-17 03:16:33'),
(10, 'Ministry of Commerce, Industries and Co-operatives', NULL, 'Commerce', '2025-07-17 03:16:58', '2025-07-17 03:16:58'),
(11, 'Ministry of Environment', NULL, 'Environment', '2025-07-17 03:17:23', '2025-07-17 03:17:23'),
(12, 'Ministry of Finance', NULL, 'Finance', '2025-07-17 03:17:47', '2025-07-17 03:17:47'),
(13, 'Ministry of Health', NULL, 'Health', '2025-07-17 03:18:15', '2025-07-17 03:18:15'),
(14, 'Ministry of Higher Education, Science & Technology', NULL, 'Higher Education', '2025-07-17 03:18:47', '2025-07-17 03:18:47'),
(15, 'Ministry of Information Youths, Sports and Culture', NULL, 'Information', '2025-07-17 03:19:12', '2025-07-17 03:19:12'),
(16, 'Ministry of Justice', NULL, 'Justice', '2025-07-17 03:19:46', '2025-07-17 03:19:46'),
(17, 'Ministry of Lands, Housing, Urban & Regional Planning Development', NULL, 'Urban Planning', '2025-07-17 03:20:28', '2025-07-17 03:20:28'),
(18, 'Ministry of Local Government', NULL, 'Local Government', '2025-07-17 03:21:00', '2025-07-17 03:21:00'),
(19, 'Ministry of Power and Energy', NULL, 'Power and Energy', '2025-07-17 03:21:27', '2025-07-17 03:21:27'),
(20, 'Ministry of Water Resources', NULL, 'Water Resources', '2025-07-17 03:21:56', '2025-07-17 03:21:56'),
(21, 'Ministry of Women Affairs & Social Development', NULL, 'Social Development', '2025-07-17 03:22:26', '2025-07-17 03:22:26'),
(22, 'Ministry of Works & Transport', NULL, 'Works & Transport', '2025-07-17 03:22:50', '2025-07-17 03:22:50'),
(23, 'State Investment Promotion Agency', NULL, 'Investment Promotion', '2025-07-17 03:23:17', '2025-07-17 03:23:17'),
(24, 'Youth Empowerment and Employment Agency', NULL, 'Youth Empowerment and Employment', '2025-07-17 03:23:49', '2025-07-17 03:23:49');

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone_number`, `role`, `password`, `image_url`, `token`, `fcm_token`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Umar Namadi', 'governor@example.com', '+2348065993421', 1, '$2y$12$jgFSqTfEULt3IFn.IbWrreOCvPZ4gsDF.wKsBrAra.7gcQlqPLEFC', '1.PNG', NULL, NULL, NULL, NULL, '2025-05-14 04:59:20'),
(2, 'Bello Haruna', 'sectorhead@example.com', '+2348065993421', 2, '$2y$12$jgFSqTfEULt3IFn.IbWrreOCvPZ4gsDF.wKsBrAra.7gcQlqPLEFC', 'sectorhead.jpg', NULL, NULL, NULL, NULL, '2024-02-08 21:09:32'),
(3, 'Delivery Department User', 'delivery@example.com', '', 3, '$2y$12$jgFSqTfEULt3IFn.IbWrreOCvPZ4gsDF.wKsBrAra.7gcQlqPLEFC', 'delivery.jpg', NULL, NULL, NULL, NULL, NULL),
(4, 'Alice Johnson', 'admin@example.com', '+2348065993421', 0, '$2y$12$jgFSqTfEULt3IFn.IbWrreOCvPZ4gsDF.wKsBrAra.7gcQlqPLEFC', 'admin.jpg', NULL, NULL, NULL, NULL, '2024-02-08 21:13:25'),
(5, 'Matazu Ibrahim', 'systemadmin@example.com', '785422684555', 0, '$2y$12$oPwYTzvC3XDIUr8KLjisxeFovwUT.i4YOM/Ew1X86Gr.jBU5xZKbq', '', NULL, NULL, NULL, '2024-01-31 21:15:18', '2024-02-08 21:14:01'),
(6, 'Jamilu Garba', 'jamilu@gmail.com', '+2348065993421', 0, '$2y$12$hv2aITOpQ/KH05zfpxM46eTD7w5c7J4ie9X23pGEN5MsvPBZ9kGEy', '', NULL, NULL, NULL, '2024-02-08 21:06:19', '2024-02-09 02:02:19'),
(7, 'Haruna Adamu', 'haruna@gmail.com', '+2348065993425', 0, '$2y$12$.eWlnuEDA3x2ZjiMc.47Z.O1p0Dfq86N71JJNmVpEcfvT1fErihwy', '', NULL, NULL, NULL, '2024-02-09 01:57:02', '2025-08-15 03:59:56');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(7, 7, 'Sector Head', 'Sector', 5, 'Active', NULL, '2024-02-09 01:57:02', '2025-08-15 03:59:24');

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
