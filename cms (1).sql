-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 07, 2026 at 08:23 AM
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
-- Database: `cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_meta`
--

CREATE TABLE `admin_meta` (
  `collection` varchar(255) NOT NULL,
  `record_id` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_meta`
--

INSERT INTO `admin_meta` (`collection`, `record_id`, `payload`, `created_at`, `updated_at`) VALUES
('designationMeta', 'booking_officer', '{\"id\":\"booking_officer\",\"label\":\"Booking Officer\",\"aliases\":[\"booking officer\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"order\":70}', '2026-07-14 04:55:56', '2026-08-07 02:56:49'),
('designationMeta', 'conductor', '{\"id\":\"conductor\",\"label\":\"Conductor\",\"aliases\":[\"conductor\"],\"restEligible\":true,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false,\"order\":30}', '2026-07-14 04:55:56', '2026-08-07 02:56:48'),
('designationMeta', 'driver', '{\"id\":\"driver\",\"label\":\"Driver\",\"aliases\":[\"locomotive_driver\",\"train_driver\"],\"restEligible\":true,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false,\"order\":10}', '2026-07-14 04:55:56', '2026-08-07 02:56:48'),
('designationMeta', 'guard', '{\"id\":\"guard\",\"label\":\"Guard\",\"aliases\":[\"train_guard\"],\"restEligible\":true,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false,\"order\":20}', '2026-07-14 04:55:56', '2026-08-07 02:56:48'),
('designationMeta', 'hq_admin', '{\"id\":\"hq_admin\",\"label\":\"HQ Admin\",\"aliases\":[\"hq admin\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"order\":80}', '2026-07-14 04:55:56', '2026-08-07 02:56:49'),
('designationMeta', 'inspector', '{\"id\":\"inspector\",\"label\":\"Inspector\",\"aliases\":[\"inspector\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"order\":50}', '2026-07-14 04:55:56', '2026-08-07 02:56:49'),
('designationMeta', 'LD', '{\"id\":\"LD\",\"label\":\"Locomotive Driver\",\"aliases\":[],\"restEligible\":true,\"order\":999,\"canLogin\":false,\"isCrewMember\":false,\"isUser\":false}', '2026-06-17 09:37:30', '2026-07-14 04:58:07'),
('designationMeta', 'shunter', '{\"id\":\"shunter\",\"label\":\"Shunter\",\"aliases\":[\"shunting\"],\"restEligible\":true,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false,\"order\":40}', '2026-07-14 04:55:56', '2026-08-07 02:56:49'),
('designationMeta', 'station_officer', '{\"id\":\"station_officer\",\"label\":\"Station Officer\",\"aliases\":[\"station officer\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"order\":60}', '2026-07-14 04:55:56', '2026-08-07 02:56:49'),
('designationMeta', 'super_admin', '{\"id\":\"super_admin\",\"label\":\"Super Admin\",\"aliases\":[\"super admin\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"order\":90}', '2026-07-14 04:55:56', '2026-08-07 02:56:49'),
('reportMeta', 'absence', '{\"id\":\"absence\",\"label\":\"Absence \\/ NTB report\",\"description\":\"Export staff who are currently on leave, sick, or marked NTB.\",\"reportType\":\"absence\",\"buttonText\":\"Export absence report\",\"visible\":true,\"order\":40}', '2026-08-04 09:15:00', '2026-08-05 06:58:32'),
('reportMeta', 'daily-status', '{\"id\":\"daily-status\",\"label\":\"Daily status export\",\"description\":\"Download the current crew status snapshot for the active depot view.\",\"reportType\":\"status\",\"buttonText\":\"Export current status\",\"visible\":true,\"order\":10}', '2026-08-04 09:14:58', '2026-08-05 06:58:30'),
('reportMeta', 'monthly-register', '{\"id\":\"monthly-register\",\"label\":\"Monthly register\",\"description\":\"Download the current month roster with daily status codes for every crew member.\",\"reportType\":\"monthly\",\"buttonText\":\"Download monthly register\",\"visible\":true,\"order\":20,\"builder_layout\":\"table\",\"builder_columns\":[],\"builder_filters\":[],\"builder_group_by\":null}', '2026-08-04 09:14:59', '2026-08-05 06:58:31'),
('reportMeta', 'print-register', '{\"id\":\"print-register\",\"label\":\"Printable register\",\"description\":\"Open the monthly register view for printing.\",\"reportType\":\"print\",\"buttonText\":\"Open printable view\",\"visible\":true,\"order\":50}', '2026-08-04 09:15:00', '2026-08-05 06:58:32'),
('reportMeta', 'utilization', '{\"id\":\"utilization\",\"label\":\"Utilization report\",\"description\":\"Review booked-day utilization over a selected time window.\",\"reportType\":\"utilization\",\"buttonText\":\"Export utilization\",\"visible\":true,\"order\":30}', '2026-08-04 09:14:59', '2026-08-05 06:58:31'),
('users', 'rsf_cgw', '{\"username\":\"rsf_cgw\",\"name\":\"Changamwe\",\"depot\":\"CGW\",\"role\":\"booking_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$6FsNpENkj7Oc8pprKt\\/OLed.LhtUDpyHsDtyutN.g9tCg9uXCGSaK\"}', '2026-08-04 09:55:27', '2026-08-07 02:52:32'),
('users', 'rsf_eld', '{\"username\":\"rsf_eld\",\"name\":\"Eldoret\",\"depot\":\"ELD\",\"role\":\"booking_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$Sg\\/vjKCoCuKL0p9pVQAWn.6.50OV4LReLu5thttAeOSO1VJWwsy\\/W\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'rsf_ksm', '{\"username\":\"rsf_ksm\",\"name\":\"Kisumu\",\"depot\":\"KSM\",\"role\":\"booking_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$3VY5DSQVobNv3bXhPJvzcehukJA\\/vkp3fA.uO0rHwm783YFEjzsFW\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'rsf_mkr', '{\"username\":\"rsf_mkr\",\"name\":\"Makadara\",\"depot\":\"MKR\",\"role\":\"booking_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$3xNrGJ9MS1tHrar2IZwUye0tFFdrQ2FMIoMqIpe9a96G36JpEXKvG\"}', '2026-08-04 09:55:27', '2026-08-07 02:52:32'),
('users', 'rsf_mlb', '{\"username\":\"rsf_mlb\",\"name\":\"Malaba\",\"depot\":\"MLB\",\"role\":\"booking_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$18j8zNBcQj9mifA.Gw9FOuIj\\/32mdGi9IwhYN\\/aArzR3ENyTnGF5G\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'rsf_mto', '{\"username\":\"rsf_mto\",\"name\":\"MTITO\",\"depot\":\"MTO\",\"role\":\"booking_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$.p4pGEHjby1uNlZqLW4DnOyTicghotm9HEIWce.MSLeDDHohBHt1m\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'rsf_nro', '{\"username\":\"rsf_nro\",\"name\":\"Nakuru\",\"depot\":\"NRO\",\"role\":\"booking_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$HNVA7JnJG9f9vTF.\\/mn1W.vHDo.2RKJ1cPD\\/lT5tIEIr1HP0Oa8nK\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'rsf_nuk', '{\"username\":\"rsf_nuk\",\"name\":\"Nanyuki\",\"depot\":\"NUK\",\"role\":\"booking_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$F0ARVVNiRJKeM7ZyeO3q6uGWDsVogHKmd6zoQXVcGxSy2hhI8uuiS\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'stn_cgw', '{\"username\":\"stn_cgw\",\"name\":\"Changamwe Station Officer\",\"depot\":\"CGW\",\"role\":\"station_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$vXDYzLtLxctXK\\/Jzynzn2.Qa7uz3TpzN1BhfUUjNBnfFx0r44ju0m\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'stn_eld', '{\"username\":\"stn_eld\",\"name\":\"Eldoret Station Officer\",\"depot\":\"ELD\",\"role\":\"station_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$7szUNqAfbpJTec7kRzYfIu8Xj.qcuMkKgBsmTUZCitYuYXy5jHsc.\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'stn_ksm', '{\"username\":\"stn_ksm\",\"name\":\"Kisumu Station Officer\",\"depot\":\"KSM\",\"role\":\"station_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$KRCjLxh5pSccfZcjauZd1eIPPtsBjz3WJkPLvlMscLZStu8o.5pAq\"}', '2026-08-04 09:55:29', '2026-08-07 02:52:32'),
('users', 'stn_mkr', '{\"username\":\"stn_mkr\",\"name\":\"Makadara Station Officer\",\"depot\":\"MKR\",\"role\":\"station_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$FhYVLQzbnvu3w.2mCM0VReRCSCNOyPjwzDjKqDsAuZKQQRiSSiKeW\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'stn_mlb', '{\"username\":\"stn_mlb\",\"name\":\"Malaba Station Officer\",\"depot\":\"MLB\",\"role\":\"station_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$AaejFHT7ux0CRf6oxg\\/Z8.MCzdn0lHiLTiwAyi54OvItWfQzp9d5O\"}', '2026-08-04 09:55:29', '2026-08-07 02:52:32'),
('users', 'stn_mto', '{\"username\":\"stn_mto\",\"name\":\"MTITO Station Officer\",\"depot\":\"MTO\",\"role\":\"station_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$M\\/xaR0yZkimTa1Gc4GzaHOvqHH7m9XUkg6Rcd70qMfTT1t6q3tzOi\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'stn_nro', '{\"username\":\"stn_nro\",\"name\":\"Nakuru Station Officer\",\"depot\":\"NRO\",\"role\":\"station_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$QOO3mP.AI3yMvCZl7H8JLOj835AhA824XWF1Ox7k\\/sBBJIp2BdWUO\"}', '2026-08-04 09:55:28', '2026-08-07 02:52:32'),
('users', 'stn_nuk', '{\"username\":\"stn_nuk\",\"name\":\"Nanyuki Station Officer\",\"depot\":\"NUK\",\"role\":\"station_officer\",\"isHQ\":false,\"isSuperAdmin\":false,\"password\":\"$2y$10$041QwvFTCnasrVFl0FFx1uTU0WmXmepNs0KRGEVqsFelihqz1FIqe\"}', '2026-08-04 09:55:29', '2026-08-07 02:52:32'),
('users', 'superadmin', '{\"username\":\"superadmin\",\"name\":\"Super Admin\",\"depot\":\"HQ\",\"role\":\"super_admin\",\"password\":\"$2y$10$cULZ\\/Ei8DI3p4k7oCs7ueeMlOERDYEzcsrKay.JmuMrrk8zhmIoqy\",\"isHQ\":true,\"isSuperAdmin\":true}', '2026-08-07 03:19:20', '2026-08-07 03:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `crew_members`
--

CREATE TABLE `crew_members` (
  `record_id` varchar(255) NOT NULL,
  `crew_id` varchar(255) DEFAULT NULL,
  `staff_number` varchar(255) DEFAULT NULL,
  `depot_code` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `designation_code` varchar(255) DEFAULT NULL,
  `employment_status_code` varchar(255) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crew_members`
--

INSERT INTO `crew_members` (`record_id`, `crew_id`, `staff_number`, `depot_code`, `first_name`, `last_name`, `display_name`, `designation_code`, `employment_status_code`, `hire_date`, `phone`, `email`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1234', NULL, '1234', 'NUK', NULL, NULL, 'Benard Mande', 'TA', NULL, NULL, NULL, NULL, 1, NULL, '2026-08-06 10:05:41', '2026-08-06 10:05:41', NULL),
('2b525e6e-7926-4a42-89e7-b32d901456d9', NULL, '3155', 'NRO', NULL, NULL, 'Michael Owoko', 'LD', 'Contract', NULL, NULL, NULL, 1, NULL, '2026-08-05 10:43:41', '2026-08-06 10:06:46', NULL),
('3456', NULL, '3456', 'KSM', NULL, NULL, 'Peter Kitiabi', 'LIO', 'Permanent', NULL, NULL, NULL, 1, NULL, '2026-08-05 10:45:12', '2026-08-05 10:45:12', NULL),
('3566', NULL, '3566', 'MKR', NULL, NULL, 'Gregory Kinisu', 'LD', NULL, NULL, NULL, NULL, 1, NULL, '2026-08-05 10:45:55', '2026-08-07 03:15:24', NULL),
('556452', NULL, '556452', 'MTO', NULL, NULL, 'William Saleh', 'RSF', NULL, NULL, NULL, NULL, 1, NULL, '2026-08-06 10:05:03', '2026-08-06 10:05:03', NULL),
('7867865', NULL, '7867865', 'MLB', NULL, NULL, 'Lukewilson Simiyu', 'RSF', NULL, NULL, NULL, NULL, 1, NULL, '2026-08-06 10:03:43', '2026-08-06 10:03:56', NULL),
('CGW_CG-001', 'CG-001', '4061', 'CGW', NULL, NULL, 'Lukewilson Simiyu', 'shunter', 'SK', NULL, NULL, NULL, 1, '{\"id\":\"CG-001\",\"name\":\"Lukewilson Simiyu\",\"staff_number\":\"4061\",\"grade\":\"shunter\",\"depot\":\"CGW\",\"route\":\"MTO-NRO\",\"status\":\"SK\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"08:11\",\"monthly\":{\"d7\":\"SK\"},\"status_segments\":[{\"segment_id\":\"2517dd9f-b1cc-4af2-9266-ce79fe2af84c\",\"crew_record_id\":\"CGW_CG-001\",\"crew_id\":\"CG-001\",\"depot_code\":\"CGW\",\"month_key\":\"2026-08\",\"day\":7,\"sort_order\":100,\"status_code\":\"SK\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-08-07 05:09:28\",\"updated_at\":\"2026-08-07 05:10:33\",\"status\":\"SK\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Sick\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_cgw\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T05:11:27.734Z\",\"bookTime\":null}', '2026-08-07 02:09:28', '2026-08-07 02:11:27', NULL),
('Changamwe_CH-001', 'CH-001', '32541', 'Changamwe', NULL, NULL, 'Hezron Luvonga', 'LD', 'R', NULL, NULL, NULL, 1, '{\"id\":\"CH-001\",\"name\":\"Hezron Luvonga\",\"staff_number\":\"32541\",\"grade\":\"LD\",\"depot\":\"Changamwe\",\"route\":\"\",\"status\":\"R\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"08:06\",\"monthly\":{\"d7\":\"R\"},\"restStarted\":\"2026-08-07T04:43:00.000Z\",\"awayDepot\":null,\"updatedBy\":\"rsf_cgw\",\"monthKey\":\"2026-06\",\"lastUpdated\":\"2026-08-07T05:06:59.334Z\",\"bookTime\":null,\"status_segments\":[{\"segment_id\":\"a0219109-df32-4630-b5eb-9791f6a6564c\",\"crew_record_id\":\"Changamwe_CH-001\",\"crew_id\":\"CH-001\",\"depot_code\":\"Changamwe\",\"month_key\":\"2026-06\",\"day\":7,\"sort_order\":100,\"status_code\":\"R\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-06-17 12:38:17\",\"updated_at\":\"2026-08-07 04:44:59\",\"status\":\"R\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Resting\"}]}', '2026-06-17 09:38:17', '2026-08-07 02:06:59', NULL),
('ELD_EL-001', 'EL-001', '09765', 'ELD', NULL, NULL, 'Charles Karonji Wahothi', 'LD', 'BK', NULL, NULL, NULL, 1, '{\"id\":\"EL-001\",\"name\":\"Charles Karonji Wahothi\",\"staff_number\":\"09765\",\"grade\":\"LD\",\"depot\":\"ELD\",\"route\":\"NRO-MLB\",\"status\":\"BK\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"08:28\",\"monthly\":{\"d1\":null,\"d2\":null,\"d3\":null,\"d4\":null,\"d5\":null,\"d6\":null,\"d7\":\"BK\",\"d8\":null,\"d9\":null,\"d10\":null,\"d11\":null,\"d12\":null,\"d13\":null,\"d14\":null,\"d15\":null,\"d16\":null,\"d17\":null,\"d18\":null,\"d19\":null,\"d20\":null,\"d21\":null,\"d22\":null,\"d23\":null,\"d24\":null,\"d25\":null,\"d26\":null,\"d27\":null,\"d28\":null,\"d29\":null,\"d30\":null,\"d31\":null},\"status_segments\":[{\"day\":7,\"sort_order\":100,\"status_code\":\"BK\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_eld\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T05:28:55.813Z\"}', '2026-08-07 02:28:55', '2026-08-07 02:28:55', NULL),
('ELD_EL-002', 'EL-002', '456544', 'ELD', NULL, NULL, 'Fanuel Ambundo Ainea', 'guard', 'L', NULL, NULL, NULL, 1, '{\"id\":\"EL-002\",\"name\":\"Fanuel Ambundo Ainea\",\"staff_number\":\"456544\",\"grade\":\"guard\",\"depot\":\"ELD\",\"route\":\"NRO-MLB\",\"status\":\"L\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"08:29\",\"monthly\":{\"d1\":null,\"d2\":null,\"d3\":null,\"d4\":null,\"d5\":null,\"d6\":null,\"d7\":\"L\",\"d8\":null,\"d9\":null,\"d10\":null,\"d11\":null,\"d12\":null,\"d13\":null,\"d14\":null,\"d15\":null,\"d16\":null,\"d17\":null,\"d18\":null,\"d19\":null,\"d20\":null,\"d21\":null,\"d22\":null,\"d23\":null,\"d24\":null,\"d25\":null,\"d26\":null,\"d27\":null,\"d28\":null,\"d29\":null,\"d30\":null,\"d31\":null},\"status_segments\":[{\"day\":7,\"sort_order\":100,\"status_code\":\"L\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_eld\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T05:29:42.687Z\"}', '2026-08-07 02:29:42', '2026-08-07 02:29:42', NULL),
('KSM_KS-001', 'KS-001', '3434', 'KSM', NULL, NULL, 'Joseph Nyaori', 'LD', 'R', NULL, NULL, NULL, 1, '{\"id\":\"KS-001\",\"name\":\"Joseph Nyaori\",\"staff_number\":\"3434\",\"grade\":\"LD\",\"depot\":\"KSM\",\"route\":\"NRO-KSM\",\"status\":\"R\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"09:02\",\"monthly\":{\"d7\":\"R\"},\"status_segments\":[{\"segment_id\":\"3edf24b9-dc9c-4c30-a924-e58a58cd24f2\",\"crew_record_id\":\"KSM_KS-001\",\"crew_id\":\"KS-001\",\"depot_code\":\"KSM\",\"month_key\":\"2026-08\",\"day\":7,\"sort_order\":100,\"status_code\":\"R\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-08-07 06:01:56\",\"updated_at\":\"2026-08-07 06:01:56\",\"status\":\"R\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Resting\"}],\"restStarted\":\"2026-08-07T06:02:00.000Z\",\"awayDepot\":\"NRO\",\"updatedBy\":\"rsf_ksm\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T06:02:29.153Z\",\"bookTime\":null}', '2026-08-07 03:01:56', '2026-08-07 03:02:29', NULL),
('KSM_KS-002', 'KS-002', '2324', 'KSM', NULL, NULL, 'Andrew Kiprotich Koskei', 'TA', 'TO', NULL, NULL, NULL, 1, '{\"id\":\"KS-002\",\"name\":\"Andrew Kiprotich Koskei\",\"staff_number\":\"2324\",\"grade\":\"TA\",\"depot\":\"KSM\",\"route\":\"NRO-KSM\",\"status\":\"TO\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"09:01\",\"monthly\":{\"d1\":null,\"d2\":null,\"d3\":null,\"d4\":null,\"d5\":null,\"d6\":null,\"d7\":\"TO\",\"d8\":null,\"d9\":null,\"d10\":null,\"d11\":null,\"d12\":null,\"d13\":null,\"d14\":null,\"d15\":null,\"d16\":null,\"d17\":null,\"d18\":null,\"d19\":null,\"d20\":null,\"d21\":null,\"d22\":null,\"d23\":null,\"d24\":null,\"d25\":null,\"d26\":null,\"d27\":null,\"d28\":null,\"d29\":null,\"d30\":null,\"d31\":null},\"status_segments\":[{\"day\":7,\"sort_order\":100,\"status_code\":\"TO\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_ksm\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T06:01:14.016Z\"}', '2026-08-07 03:01:14', '2026-08-07 03:01:14', NULL),
('MKR_MK-001', 'MK-001', '12345', 'MKR', NULL, NULL, 'Gregory Kinisu', 'shunter', 'T', NULL, NULL, NULL, 1, '{\"id\":\"MK-001\",\"name\":\"Gregory Kinisu\",\"staff_number\":\"12345\",\"grade\":\"shunter\",\"depot\":\"MKR\",\"route\":\"MTO-NRO\",\"status\":\"T\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"09:16\",\"monthly\":{\"d7\":\"T\",\"d4\":\"SB\"},\"status_segments\":[{\"segment_id\":\"43b153b0-2048-40da-8b4e-5cb99767f1df\",\"crew_record_id\":\"MKR_MK-001\",\"crew_id\":\"MK-001\",\"depot_code\":\"MKR\",\"month_key\":\"2026-08\",\"day\":7,\"sort_order\":100,\"status_code\":\"T\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-08-07 06:16:54\",\"updated_at\":\"2026-08-07 06:16:54\"},{\"day\":4,\"sort_order\":100,\"status_code\":\"SB\",\"status\":\"SB\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Stand By\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_mkr\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T06:20:34.720Z\"}', '2026-08-07 03:16:54', '2026-08-07 03:20:34', NULL),
('MKR_MK-002', 'MK-002', '554555', 'MKR', NULL, NULL, 'Daniel.Muteti Samuel', 'LD', 'ABS', NULL, NULL, NULL, 1, '{\"id\":\"MK-002\",\"name\":\"Daniel.Muteti Samuel\",\"staff_number\":\"554555\",\"grade\":\"LD\",\"depot\":\"MKR\",\"route\":\"MTO-NRO\",\"status\":\"ABS\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"09:17\",\"monthly\":{\"d7\":\"ABS\",\"d3\":\"SB\",\"d4\":\"SB\"},\"status_segments\":[{\"segment_id\":\"39089f2f-5a06-4a71-adc0-62aafe906001\",\"crew_record_id\":\"MKR_MK-002\",\"crew_id\":\"MK-002\",\"depot_code\":\"MKR\",\"month_key\":\"2026-08\",\"day\":7,\"sort_order\":100,\"status_code\":\"ABS\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-08-07 06:17:36\",\"updated_at\":\"2026-08-07 06:17:36\"},{\"day\":3,\"sort_order\":100,\"status_code\":\"SB\",\"status\":\"SB\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Stand By\"},{\"day\":4,\"sort_order\":100,\"status_code\":\"SB\",\"status\":\"SB\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Stand By\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_mkr\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T06:20:29.392Z\"}', '2026-08-07 03:17:36', '2026-08-07 03:20:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `crew_records`
--

CREATE TABLE `crew_records` (
  `record_id` varchar(255) NOT NULL,
  `depot` varchar(255) NOT NULL,
  `crew_id` varchar(255) DEFAULT NULL,
  `staff_number` varchar(255) DEFAULT NULL,
  `payload` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crew_records`
--

INSERT INTO `crew_records` (`record_id`, `depot`, `crew_id`, `staff_number`, `payload`, `created_at`, `updated_at`) VALUES
('CGW_CG-001', 'CGW', 'CG-001', '4061', '{\"id\":\"CG-001\",\"name\":\"Lukewilson Simiyu\",\"staff_number\":\"4061\",\"grade\":\"shunter\",\"depot\":\"CGW\",\"route\":\"MTO-NRO\",\"status\":\"SK\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"08:11\",\"monthly\":{\"d7\":\"SK\"},\"status_segments\":[{\"segment_id\":\"2517dd9f-b1cc-4af2-9266-ce79fe2af84c\",\"crew_record_id\":\"CGW_CG-001\",\"crew_id\":\"CG-001\",\"depot_code\":\"CGW\",\"month_key\":\"2026-08\",\"day\":7,\"sort_order\":100,\"status_code\":\"SK\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-08-07 05:09:28\",\"updated_at\":\"2026-08-07 05:10:33\",\"status\":\"SK\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Sick\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_cgw\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T05:11:27.734Z\",\"bookTime\":null}', '2026-08-07 02:09:28', '2026-08-07 02:11:27'),
('Changamwe_CH-001', 'Changamwe', 'CH-001', '32541', '{\"id\":\"CH-001\",\"name\":\"Hezron Luvonga\",\"staff_number\":\"32541\",\"grade\":\"LD\",\"depot\":\"Changamwe\",\"route\":\"\",\"status\":\"R\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"08:06\",\"monthly\":{\"d7\":\"R\"},\"restStarted\":\"2026-08-07T04:43:00.000Z\",\"awayDepot\":null,\"updatedBy\":\"rsf_cgw\",\"monthKey\":\"2026-06\",\"lastUpdated\":\"2026-08-07T05:06:59.334Z\",\"bookTime\":null,\"status_segments\":[{\"segment_id\":\"a0219109-df32-4630-b5eb-9791f6a6564c\",\"crew_record_id\":\"Changamwe_CH-001\",\"crew_id\":\"CH-001\",\"depot_code\":\"Changamwe\",\"month_key\":\"2026-06\",\"day\":7,\"sort_order\":100,\"status_code\":\"R\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-06-17 12:38:17\",\"updated_at\":\"2026-08-07 04:44:59\",\"status\":\"R\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Resting\"}]}', '2026-06-17 09:38:17', '2026-08-07 02:06:59'),
('ELD_EL-001', 'ELD', 'EL-001', '09765', '{\"id\":\"EL-001\",\"name\":\"Charles Karonji Wahothi\",\"staff_number\":\"09765\",\"grade\":\"LD\",\"depot\":\"ELD\",\"route\":\"NRO-MLB\",\"status\":\"BK\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"08:28\",\"monthly\":{\"d1\":null,\"d2\":null,\"d3\":null,\"d4\":null,\"d5\":null,\"d6\":null,\"d7\":\"BK\",\"d8\":null,\"d9\":null,\"d10\":null,\"d11\":null,\"d12\":null,\"d13\":null,\"d14\":null,\"d15\":null,\"d16\":null,\"d17\":null,\"d18\":null,\"d19\":null,\"d20\":null,\"d21\":null,\"d22\":null,\"d23\":null,\"d24\":null,\"d25\":null,\"d26\":null,\"d27\":null,\"d28\":null,\"d29\":null,\"d30\":null,\"d31\":null},\"status_segments\":[{\"day\":7,\"sort_order\":100,\"status_code\":\"BK\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_eld\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T05:28:55.813Z\"}', '2026-08-07 02:28:55', '2026-08-07 02:28:55'),
('ELD_EL-002', 'ELD', 'EL-002', '456544', '{\"id\":\"EL-002\",\"name\":\"Fanuel Ambundo Ainea\",\"staff_number\":\"456544\",\"grade\":\"guard\",\"depot\":\"ELD\",\"route\":\"NRO-MLB\",\"status\":\"L\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"08:29\",\"monthly\":{\"d1\":null,\"d2\":null,\"d3\":null,\"d4\":null,\"d5\":null,\"d6\":null,\"d7\":\"L\",\"d8\":null,\"d9\":null,\"d10\":null,\"d11\":null,\"d12\":null,\"d13\":null,\"d14\":null,\"d15\":null,\"d16\":null,\"d17\":null,\"d18\":null,\"d19\":null,\"d20\":null,\"d21\":null,\"d22\":null,\"d23\":null,\"d24\":null,\"d25\":null,\"d26\":null,\"d27\":null,\"d28\":null,\"d29\":null,\"d30\":null,\"d31\":null},\"status_segments\":[{\"day\":7,\"sort_order\":100,\"status_code\":\"L\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_eld\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T05:29:42.687Z\"}', '2026-08-07 02:29:42', '2026-08-07 02:29:42'),
('KSM_KS-001', 'KSM', 'KS-001', '3434', '{\"id\":\"KS-001\",\"name\":\"Joseph Nyaori\",\"staff_number\":\"3434\",\"grade\":\"LD\",\"depot\":\"KSM\",\"route\":\"NRO-KSM\",\"status\":\"R\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"09:02\",\"monthly\":{\"d7\":\"R\"},\"status_segments\":[{\"segment_id\":\"3edf24b9-dc9c-4c30-a924-e58a58cd24f2\",\"crew_record_id\":\"KSM_KS-001\",\"crew_id\":\"KS-001\",\"depot_code\":\"KSM\",\"month_key\":\"2026-08\",\"day\":7,\"sort_order\":100,\"status_code\":\"R\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-08-07 06:01:56\",\"updated_at\":\"2026-08-07 06:01:56\",\"status\":\"R\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Resting\"}],\"restStarted\":\"2026-08-07T06:02:00.000Z\",\"awayDepot\":\"NRO\",\"updatedBy\":\"rsf_ksm\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T06:02:29.153Z\",\"bookTime\":null}', '2026-08-07 03:01:56', '2026-08-07 03:02:29'),
('KSM_KS-002', 'KSM', 'KS-002', '2324', '{\"id\":\"KS-002\",\"name\":\"Andrew Kiprotich Koskei\",\"staff_number\":\"2324\",\"grade\":\"TA\",\"depot\":\"KSM\",\"route\":\"NRO-KSM\",\"status\":\"TO\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"09:01\",\"monthly\":{\"d1\":null,\"d2\":null,\"d3\":null,\"d4\":null,\"d5\":null,\"d6\":null,\"d7\":\"TO\",\"d8\":null,\"d9\":null,\"d10\":null,\"d11\":null,\"d12\":null,\"d13\":null,\"d14\":null,\"d15\":null,\"d16\":null,\"d17\":null,\"d18\":null,\"d19\":null,\"d20\":null,\"d21\":null,\"d22\":null,\"d23\":null,\"d24\":null,\"d25\":null,\"d26\":null,\"d27\":null,\"d28\":null,\"d29\":null,\"d30\":null,\"d31\":null},\"status_segments\":[{\"day\":7,\"sort_order\":100,\"status_code\":\"TO\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_ksm\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T06:01:14.016Z\"}', '2026-08-07 03:01:14', '2026-08-07 03:01:14'),
('MKR_MK-001', 'MKR', 'MK-001', '12345', '{\"id\":\"MK-001\",\"name\":\"Gregory Kinisu\",\"staff_number\":\"12345\",\"grade\":\"shunter\",\"depot\":\"MKR\",\"route\":\"MTO-NRO\",\"status\":\"T\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"09:16\",\"monthly\":{\"d7\":\"T\",\"d4\":\"SB\"},\"status_segments\":[{\"segment_id\":\"43b153b0-2048-40da-8b4e-5cb99767f1df\",\"crew_record_id\":\"MKR_MK-001\",\"crew_id\":\"MK-001\",\"depot_code\":\"MKR\",\"month_key\":\"2026-08\",\"day\":7,\"sort_order\":100,\"status_code\":\"T\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-08-07 06:16:54\",\"updated_at\":\"2026-08-07 06:16:54\"},{\"day\":4,\"sort_order\":100,\"status_code\":\"SB\",\"status\":\"SB\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Stand By\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_mkr\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T06:20:34.720Z\"}', '2026-08-07 03:16:54', '2026-08-07 03:20:34'),
('MKR_MK-002', 'MKR', 'MK-002', '554555', '{\"id\":\"MK-002\",\"name\":\"Daniel.Muteti Samuel\",\"staff_number\":\"554555\",\"grade\":\"LD\",\"depot\":\"MKR\",\"route\":\"MTO-NRO\",\"status\":\"ABS\",\"trainType\":\"\",\"notes\":\"\",\"since\":\"09:17\",\"monthly\":{\"d7\":\"ABS\",\"d3\":\"SB\",\"d4\":\"SB\"},\"status_segments\":[{\"segment_id\":\"39089f2f-5a06-4a71-adc0-62aafe906001\",\"crew_record_id\":\"MKR_MK-002\",\"crew_id\":\"MK-002\",\"depot_code\":\"MKR\",\"month_key\":\"2026-08\",\"day\":7,\"sort_order\":100,\"status_code\":\"ABS\",\"train_type\":null,\"route\":null,\"book_time\":null,\"rest_started_at\":null,\"away_depot\":null,\"notes\":null,\"metadata\":[],\"created_at\":\"2026-08-07 06:17:36\",\"updated_at\":\"2026-08-07 06:17:36\"},{\"day\":3,\"sort_order\":100,\"status_code\":\"SB\",\"status\":\"SB\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Stand By\"},{\"day\":4,\"sort_order\":100,\"status_code\":\"SB\",\"status\":\"SB\",\"start_time\":\"00:00\",\"end_time\":\"23:59\",\"note\":\"Stand By\"}],\"restStarted\":null,\"awayDepot\":null,\"updatedBy\":\"rsf_mkr\",\"monthKey\":\"2026-08\",\"lastUpdated\":\"2026-08-07T06:20:29.392Z\"}', '2026-08-07 03:17:36', '2026-08-07 03:20:29');

-- --------------------------------------------------------

--
-- Table structure for table `crew_shift_assignments`
--

CREATE TABLE `crew_shift_assignments` (
  `crew_record_id` varchar(255) NOT NULL,
  `record_id` varchar(255) NOT NULL,
  `crew_id` varchar(255) DEFAULT NULL,
  `depot` varchar(255) NOT NULL,
  `shift` varchar(255) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crew_shift_assignments`
--

INSERT INTO `crew_shift_assignments` (`crew_record_id`, `record_id`, `crew_id`, `depot`, `shift`, `assigned_at`, `created_at`, `updated_at`) VALUES
('CGW_CG-001', 'CGW_CG-001', 'CG-001', 'CGW', 'Day', '2026-08-07 02:11:27', '2026-08-07 02:11:27', '2026-08-07 02:11:27'),
('Changamwe_CH-001', 'Changamwe_CH-001', 'CH-001', 'Changamwe', 'Day', '2026-08-07 02:06:59', '2026-08-07 02:06:59', '2026-08-07 02:06:59'),
('KSM_KS-001', 'KSM_KS-001', 'KS-001', 'KSM', 'Day', '2026-08-07 03:02:29', '2026-08-07 03:02:29', '2026-08-07 03:02:29');

-- --------------------------------------------------------

--
-- Table structure for table `crew_status_history`
--

CREATE TABLE `crew_status_history` (
  `history_id` varchar(255) NOT NULL,
  `crew_record_id` varchar(255) NOT NULL,
  `crew_id` varchar(255) DEFAULT NULL,
  `depot_code` varchar(255) DEFAULT NULL,
  `status_code` varchar(255) NOT NULL,
  `reason_code` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `effective_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crew_status_history`
--

INSERT INTO `crew_status_history` (`history_id`, `crew_record_id`, `crew_id`, `depot_code`, `status_code`, `reason_code`, `notes`, `effective_at`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('2bfe5211-4ff7-488e-bff1-223fd0fc4c60', 'KSM_KS-001', 'KS-001', 'KSM', 'R', NULL, NULL, '2026-08-07 03:01:56', '{\"shift\":null,\"trainType\":\"\",\"route\":\"NRO-KSM\",\"updatedBy\":\"rsf_ksm\"}', '2026-08-07 03:01:56', '2026-08-07 03:01:56', NULL),
('8d15fac6-07ee-4bfa-a7e8-e19465aea1e9', 'CGW_CG-001', 'CG-001', 'CGW', 'NTB', NULL, 'Disciplinary', '2026-08-07 02:10:33', '{\"shift\":null,\"trainType\":\"\",\"route\":\"MTO-NRO\",\"updatedBy\":\"rsf_cgw\"}', '2026-08-07 02:10:33', '2026-08-07 02:10:33', NULL),
('a29f95b7-e768-4b8a-9365-4f2c00ee9f9b', 'KSM_KS-002', 'KS-002', 'KSM', 'TO', NULL, NULL, '2026-08-07 03:01:14', '{\"shift\":null,\"trainType\":\"\",\"route\":\"NRO-KSM\",\"updatedBy\":\"rsf_ksm\"}', '2026-08-07 03:01:14', '2026-08-07 03:01:14', NULL),
('a6527b5d-8b67-4660-bb52-acd98b9dde29', 'CGW_CG-001', 'CG-001', 'CGW', 'R', NULL, NULL, '2026-08-07 02:09:28', '{\"shift\":null,\"trainType\":\"\",\"route\":\"MTO-NRO\",\"updatedBy\":\"rsf_cgw\"}', '2026-08-07 02:09:28', '2026-08-07 02:09:28', NULL),
('b16b0173-0b0b-467f-be44-6f3901be09b9', 'ELD_EL-001', 'EL-001', 'ELD', 'BK', NULL, NULL, '2026-08-07 02:28:56', '{\"shift\":null,\"trainType\":\"\",\"route\":\"NRO-MLB\",\"updatedBy\":\"rsf_eld\"}', '2026-08-07 02:28:56', '2026-08-07 02:28:56', NULL),
('b85f914b-e3c2-4371-880c-f51dcf571fbd', 'CGW_CG-001', 'CG-001', 'CGW', 'SK', NULL, NULL, '2026-08-07 02:11:27', '{\"shift\":null,\"trainType\":\"\",\"route\":\"MTO-NRO\",\"updatedBy\":\"rsf_cgw\"}', '2026-08-07 02:11:27', '2026-08-07 02:11:27', NULL),
('c54aa114-7480-493b-b00f-71180895219b', 'MKR_MK-002', 'MK-002', 'MKR', 'ABS', NULL, NULL, '2026-08-07 03:17:36', '{\"shift\":null,\"trainType\":\"\",\"route\":\"MTO-NRO\",\"updatedBy\":\"rsf_mkr\"}', '2026-08-07 03:17:36', '2026-08-07 03:17:36', NULL),
('d2643d1f-972f-40e3-aae4-cae7eefb8dfa', 'Changamwe_CH-001', 'CH-001', 'Changamwe', 'R', NULL, NULL, '2026-08-07 01:42:42', '{\"shift\":null,\"trainType\":\"\",\"route\":\"\",\"updatedBy\":\"rsf_cgw\"}', '2026-08-07 01:42:42', '2026-08-07 01:42:42', NULL),
('d4d3c5a2-8c72-4aa0-9bca-59e6bc42d77c', 'ELD_EL-002', 'EL-002', 'ELD', 'L', NULL, NULL, '2026-08-07 02:29:42', '{\"shift\":null,\"trainType\":\"\",\"route\":\"NRO-MLB\",\"updatedBy\":\"rsf_eld\"}', '2026-08-07 02:29:42', '2026-08-07 02:29:42', NULL),
('dc46c56d-06b4-43e6-87e4-0ff056846d24', 'MKR_MK-001', 'MK-001', 'MKR', 'T', NULL, NULL, '2026-08-07 03:16:54', '{\"shift\":null,\"trainType\":\"\",\"route\":\"MTO-NRO\",\"updatedBy\":\"rsf_mkr\"}', '2026-08-07 03:16:54', '2026-08-07 03:16:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `crew_status_segments`
--

CREATE TABLE `crew_status_segments` (
  `segment_id` varchar(255) NOT NULL,
  `crew_record_id` varchar(255) NOT NULL,
  `crew_id` varchar(255) DEFAULT NULL,
  `depot_code` varchar(255) DEFAULT NULL,
  `month_key` varchar(255) DEFAULT NULL,
  `day` tinyint(3) UNSIGNED NOT NULL,
  `date` date DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `status_code` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `start_time` varchar(255) DEFAULT NULL,
  `end_time` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `train_type` varchar(255) DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL,
  `book_time` varchar(255) DEFAULT NULL,
  `rest_started_at` timestamp NULL DEFAULT NULL,
  `away_depot` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crew_status_segments`
--

INSERT INTO `crew_status_segments` (`segment_id`, `crew_record_id`, `crew_id`, `depot_code`, `month_key`, `day`, `date`, `sort_order`, `status_code`, `status`, `start_time`, `end_time`, `note`, `train_type`, `route`, `book_time`, `rest_started_at`, `away_depot`, `notes`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1191be41-752b-46da-a196-e53fd5ac5e79', 'MKR_MK-002', 'MK-002', 'MKR', '2026-08', 4, '2026-08-04', 100, 'SB', 'SB', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 03:17:36', '2026-08-07 03:20:30', NULL),
('2517dd9f-b1cc-4af2-9266-ce79fe2af84c', 'CGW_CG-001', 'CG-001', 'CGW', '2026-08', 7, '2026-08-07', 100, 'SK', 'SK', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 02:09:28', '2026-08-07 02:11:27', NULL),
('39089f2f-5a06-4a71-adc0-62aafe906001', 'MKR_MK-002', 'MK-002', 'MKR', '2026-08', 7, '2026-08-07', 100, 'ABS', 'ABS', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 03:17:36', '2026-08-07 03:20:29', NULL),
('3edf24b9-dc9c-4c30-a924-e58a58cd24f2', 'KSM_KS-001', 'KS-001', 'KSM', '2026-08', 7, '2026-08-07', 100, 'R', 'R', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 03:01:56', '2026-08-07 03:02:29', NULL),
('43b153b0-2048-40da-8b4e-5cb99767f1df', 'MKR_MK-001', 'MK-001', 'MKR', '2026-08', 7, '2026-08-07', 100, 'T', 'T', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 03:16:54', '2026-08-07 03:20:34', NULL),
('55c8ba35-9b30-4829-a32c-28a167ed7d0e', 'KSM_KS-002', 'KS-002', 'KSM', '2026-08', 7, '2026-08-07', 100, 'TO', 'TO', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 03:01:14', '2026-08-07 03:01:14', NULL),
('99301f4b-ad2d-4199-8f8a-8aae35d80b31', 'ELD_EL-001', 'EL-001', 'ELD', '2026-08', 7, '2026-08-07', 100, 'BK', 'BK', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 02:28:55', '2026-08-07 02:28:55', NULL),
('a0219109-df32-4630-b5eb-9791f6a6564c', 'Changamwe_CH-001', 'CH-001', 'Changamwe', '2026-06', 7, '2026-06-07', 100, 'R', 'R', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-06-17 09:38:17', '2026-08-07 02:06:59', NULL),
('b841b172-9046-43c5-8471-4ab4d5fa3c85', 'ELD_EL-002', 'EL-002', 'ELD', '2026-08', 7, '2026-08-07', 100, 'L', 'L', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 02:29:42', '2026-08-07 02:29:42', NULL),
('d42c7e26-3c41-4488-8a19-e3c9a557948a', 'MKR_MK-002', 'MK-002', 'MKR', '2026-08', 3, '2026-08-03', 100, 'SB', 'SB', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 03:17:36', '2026-08-07 03:20:29', NULL),
('ffc1965c-d3a3-4e15-9171-70f6ad57913b', 'MKR_MK-001', 'MK-001', 'MKR', '2026-08', 4, '2026-08-04', 100, 'SB', 'SB', '00:00', '23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', '2026-08-07 03:16:54', '2026-08-07 03:20:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `depots`
--

CREATE TABLE `depots` (
  `depot_code` varchar(255) NOT NULL,
  `depot_name` varchar(255) NOT NULL,
  `region` varchar(255) DEFAULT NULL,
  `color` varchar(32) DEFAULT NULL,
  `is_hq` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `depots`
--

INSERT INTO `depots` (`depot_code`, `depot_name`, `region`, `color`, `is_hq`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('CGW', 'Changamwe', 'central', '#db8282', 0, NULL, '2026-08-04 09:55:27', '2026-08-05 10:49:21', NULL),
('ELD', 'Eldoret', 'western-2', '#1a4db0', 0, NULL, '2026-08-04 09:55:27', '2026-08-05 10:55:37', NULL),
('KSM', 'Kisumu', 'western-1', '#db00ff', 0, NULL, '2026-08-04 09:55:27', '2026-08-05 10:55:52', NULL),
('MKR', 'Makadara', 'central', NULL, 0, NULL, '2026-08-04 09:55:27', '2026-08-05 10:49:56', NULL),
('MLB', 'Malaba', 'western-2', NULL, 0, NULL, '2026-08-04 09:55:27', '2026-08-05 10:50:03', NULL),
('MTO', 'MTITO', 'eastern', NULL, 0, NULL, '2026-08-04 09:55:27', '2026-08-05 10:50:11', NULL),
('NRO', 'Nakuru', 'western-1', NULL, 0, NULL, '2026-08-04 09:55:27', '2026-08-05 10:50:18', NULL),
('NUK', 'Nanyuki', 'central', NULL, 0, NULL, '2026-08-04 09:55:27', '2026-08-05 10:50:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `designation_code` varchar(255) NOT NULL,
  `designation_name` varchar(255) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`designation_code`, `designation_name`, `sort_order`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('booking_officer', 'Booking Officer', 70, 1, '{\"aliases\":[\"booking officer\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"active\":true}', '2026-08-07 02:56:49', '2026-08-07 02:56:49', NULL),
('conductor', 'Conductor', 30, 1, '{\"aliases\":[\"conductor\"],\"restEligible\":true,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false,\"active\":true}', '2026-08-07 02:56:48', '2026-08-07 02:56:48', NULL),
('driver', 'Driver', 10, 1, '{\"aliases\":[\"locomotive_driver\",\"train_driver\"],\"restEligible\":true,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false,\"active\":true}', '2026-08-07 02:56:48', '2026-08-07 02:56:48', NULL),
('guard', 'Guard', 20, 1, '{\"aliases\":[\"train_guard\"],\"restEligible\":true,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false,\"active\":true}', '2026-08-07 02:56:48', '2026-08-07 02:56:48', NULL),
('hq_admin', 'HQ Admin', 80, 1, '{\"aliases\":[\"hq admin\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"active\":true}', '2026-08-07 02:56:49', '2026-08-07 02:56:49', NULL),
('inspector', 'Inspector', 50, 1, '{\"aliases\":[\"inspector\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"active\":true}', '2026-08-07 02:56:49', '2026-08-07 02:56:49', NULL),
('LD', 'Locomotive Driver', 0, 1, '[]', '2026-08-05 06:52:01', '2026-08-05 10:51:44', NULL),
('LIO', 'Locomotive Inspecting Officer', 0, 1, NULL, '2026-08-05 10:36:07', '2026-08-05 10:36:07', NULL),
('RSF', 'Running Shift Foremen', 0, 1, NULL, '2026-08-05 10:36:48', '2026-08-05 10:36:48', NULL),
('SD', 'Shunter Driver', 0, 1, NULL, '2026-08-05 10:36:24', '2026-08-05 10:36:24', NULL),
('shunter', 'Shunter', 40, 1, '{\"aliases\":[\"shunting\"],\"restEligible\":true,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false,\"active\":true}', '2026-08-07 02:56:49', '2026-08-07 02:56:49', NULL),
('station_officer', 'Station Officer', 60, 1, '{\"aliases\":[\"station officer\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"active\":true}', '2026-08-07 02:56:49', '2026-08-07 02:56:49', NULL),
('super_admin', 'Super Admin', 90, 1, '{\"aliases\":[\"super admin\"],\"restEligible\":false,\"canLogin\":true,\"isCrewMember\":false,\"isUser\":true,\"active\":true}', '2026-08-07 02:56:49', '2026-08-07 02:56:49', NULL),
('TA', 'Train Assistant', 0, 1, NULL, '2026-08-05 10:37:20', '2026-08-05 10:37:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `duty_rosters`
--

CREATE TABLE `duty_rosters` (
  `roster_id` varchar(255) NOT NULL,
  `depot_code` varchar(255) NOT NULL,
  `roster_date` date NOT NULL,
  `period_label` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `duty_roster_items`
--

CREATE TABLE `duty_roster_items` (
  `item_id` varchar(255) NOT NULL,
  `roster_id` varchar(255) NOT NULL,
  `crew_record_id` varchar(255) NOT NULL,
  `crew_id` varchar(255) DEFAULT NULL,
  `shift_code` varchar(255) DEFAULT NULL,
  `train_type_code` varchar(255) DEFAULT NULL,
  `route_code` varchar(255) DEFAULT NULL,
  `rest_location_code` varchar(255) DEFAULT NULL,
  `duty_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(2, '2026_06_17_000001_create_crew_records_table', 1),
(3, '2026_06_17_000002_normalize_crew_schema', 2),
(5, '2026_06_17_000003_create_normalized_crew_core_schema', 3),
(6, '2026_07_14_130000_hash_existing_user_passwords', 4),
(7, '2026_07_14_140500_add_email_to_users_for_filament_login', 5),
(8, '2026_07_16_000001_create_recommended_auth_schema', 5),
(9, '2026_07_16_074256_add_last_login_at_to_users_table', 5),
(10, '2026_07_23_115809_create_reports_table', 5),
(11, '2026_08_03_000001_create_crew_status_segments_table', 5),
(12, '2026_08_04_000001_add_builder_fields_to_reports_table', 5),
(13, '2026_08_04_000002_add_time_columns_to_crew_status_segments_table', 5),
(14, '2026_08_05_000001_create_designations_table', 6),
(15, '2026_08_05_000001_add_color_to_depots_table', 7),
(16, '2026_08_05_000002_create_regions_table', 8),
(17, '2026_08_07_000001_synchronize_user_passwords_and_absent_status', 9);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_code` varchar(255) NOT NULL,
  `permission_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_code`, `permission_name`, `description`, `is_system`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('manage_crew', 'Create and edit crew members', NULL, 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL),
('manage_depots', 'Create and edit depots', NULL, 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL),
('manage_reports', 'Configure report visibility', NULL, 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL),
('manage_roles', 'Assign roles and permissions', NULL, 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL),
('manage_rosters', 'Maintain roster data', NULL, 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL),
('manage_users', 'Create and edit users', NULL, 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `region_code` varchar(255) NOT NULL,
  `region_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`region_code`, `region_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
('central', 'Central', NULL, 1, '2026-08-05 10:49:11', '2026-08-05 10:49:11'),
('eastern', 'Eastern', NULL, 1, '2026-08-05 10:49:05', '2026-08-05 10:49:05'),
('western-1', 'Western 1', NULL, 1, '2026-08-05 10:48:56', '2026-08-05 10:48:56'),
('western-2', 'Western 2', NULL, 1, '2026-08-05 10:49:00', '2026-08-05 10:49:00');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'export',
  `route_name` varchar(255) DEFAULT NULL,
  `action_label` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `builder_layout` varchar(255) NOT NULL DEFAULT 'table',
  `builder_columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`builder_columns`)),
  `builder_filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`builder_filters`)),
  `builder_group_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `name`, `slug`, `description`, `icon`, `type`, `route_name`, `action_label`, `category`, `is_active`, `sort_order`, `created_at`, `updated_at`, `builder_layout`, `builder_columns`, `builder_filters`, `builder_group_by`) VALUES
(1, 'Daily Status Export', 'daily-status-export', 'Download the current crew status snapshot for the active depot view.', '📊', 'export', 'reports.daily-status', 'Export current status', 'Crew Management', 1, 10, '2026-08-04 09:14:53', '2026-08-04 09:14:53', 'table', NULL, NULL, NULL),
(2, 'Monthly Register', 'monthly-register', 'Download the current month roster with daily status codes for every crew member.', '📅', 'export', 'reports.monthly-register', 'Download monthly register', 'Crew Management', 1, 20, '2026-08-04 09:14:53', '2026-08-04 09:14:53', 'table', NULL, NULL, NULL),
(3, 'Utilization Report', 'utilization-report', 'Review booked-day utilization over a selected time window.', '📈', 'report', 'reports.utilization', 'Export utilization', 'Operations', 1, 30, '2026-08-04 09:14:53', '2026-08-04 09:14:53', 'table', NULL, NULL, NULL),
(4, 'Absence / NTB Report', 'absence-ntb-report', 'Export staff who are currently on leave, sick, or marked NTB.', '⚠️', 'export', 'reports.absence', 'Export absence report', 'Operations', 1, 40, '2026-08-04 09:14:53', '2026-08-04 09:14:53', 'table', NULL, NULL, NULL),
(5, 'Printable Register', 'printable-register', 'Open the monthly register view for printing.', '🖨️', 'view', 'reports.printable', 'Open printable view', 'Printing', 1, 50, '2026-08-04 09:14:53', '2026-08-04 09:14:53', 'table', NULL, NULL, NULL),
(6, 'New report', 'new-report', NULL, NULL, 'report', NULL, NULL, NULL, 1, 0, '2026-08-05 06:44:40', '2026-08-05 06:44:40', 'table', '[]', '[]', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rest_locations`
--

CREATE TABLE `rest_locations` (
  `rest_location_code` varchar(255) NOT NULL,
  `rest_location_name` varchar(255) NOT NULL,
  `depot_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_code` varchar(255) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_code`, `role_name`, `description`, `is_system`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('booking_officer', 'Booking Officer', 'Crew booking and registers', 1, '{\"active\":false,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false}', '2026-08-04 09:14:50', '2026-08-07 01:41:01', NULL),
('crew_admin', 'Crew Admin', 'Crew maintenance and lookup data', 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL),
('hq_admin', 'HQ Admin', 'Headquarters operations', 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL),
('station_officer', 'Station Officer', 'Station and depot operations', 1, '{\"active\":false,\"canLogin\":true,\"isCrewMember\":true,\"isUser\":false}', '2026-08-04 09:14:50', '2026-08-07 01:40:27', NULL),
('super_admin', 'Super Admin', 'Full system control', 1, '[]', '2026-08-04 09:14:50', '2026-08-04 09:14:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_code` varchar(255) NOT NULL,
  `permission_code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `route_code` varchar(255) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `origin_depot_code` varchar(255) DEFAULT NULL,
  `destination_depot_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shift_templates`
--

CREATE TABLE `shift_templates` (
  `shift_code` varchar(255) NOT NULL,
  `shift_name` varchar(255) NOT NULL,
  `starts_at` time DEFAULT NULL,
  `ends_at` time DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shift_templates`
--

INSERT INTO `shift_templates` (`shift_code`, `shift_name`, `starts_at`, `ends_at`, `sort_order`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('day', 'Day', '06:00:00', '18:00:00', 10, 1, NULL, '2026-08-04 09:55:27', '2026-08-04 09:55:27', NULL),
('night', 'Night', '18:00:00', '06:00:00', 20, 1, NULL, '2026-08-04 09:55:27', '2026-08-04 09:55:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `status_codes`
--

CREATE TABLE `status_codes` (
  `status_code` varchar(255) NOT NULL,
  `status_label` varchar(255) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_terminal` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `status_codes`
--

INSERT INTO `status_codes` (`status_code`, `status_label`, `sort_order`, `is_terminal`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
('ABS', 'Absent', 550, 0, '{\"bg\":\"#FEF2F2\",\"fg\":\"#991B1B\",\"active\":true}', '2026-08-07 02:52:32', '2026-08-07 02:52:32', NULL),
('BK', 'Booked', 100, 0, '{\"bg\":\"#E8F5E9\",\"fg\":\"#1B5E20\",\"active\":true}', '2026-07-14 04:39:52', '2026-07-14 04:39:52', NULL),
('L', 'Leave', 400, 0, '{\"bg\":\"#FFF3E0\",\"fg\":\"#E65100\",\"active\":true}', '2026-07-14 04:39:52', '2026-07-14 04:39:52', NULL),
('NTB', 'NTB', 700, 0, '{\"bg\":\"#ECEFF1\",\"fg\":\"#37474F\",\"active\":true}', '2026-07-14 04:39:52', '2026-07-14 04:39:52', NULL),
('R', 'Resting', 300, 0, '{\"bg\":\"#F3F5FF\",\"fg\":\"#4A148C\",\"active\":true}', '2026-07-14 04:39:52', '2026-07-14 04:39:52', NULL),
('SB', 'Stand By', 200, 0, '{\"bg\":\"#E3F2FD\",\"fg\":\"#0D47A1\",\"active\":true}', '2026-07-14 04:39:52', '2026-07-14 04:39:52', NULL),
('SK', 'Sick', 500, 0, '{\"bg\":\"#FFEBEE\",\"fg\":\"#B71C1C\",\"active\":true}', '2026-07-14 04:39:52', '2026-07-14 04:39:52', NULL),
('T', 'Training', 600, 0, '{\"bg\":\"#E0F2F1\",\"fg\":\"#00695C\",\"active\":true}', '2026-07-14 04:39:52', '2026-07-14 04:39:52', NULL),
('TO', 'Trip Off', 800, 0, '{\"bg\":\"#FCE4EC\",\"fg\":\"#AD1457\",\"active\":true}', '2026-07-14 04:39:52', '2026-07-14 04:39:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `train_types`
--

CREATE TABLE `train_types` (
  `train_type_code` varchar(255) NOT NULL,
  `train_type_name` varchar(255) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `username` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `depot_code` varchar(255) DEFAULT NULL,
  `role_code` varchar(255) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `pw` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_hq` tinyint(1) NOT NULL DEFAULT 0,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`username`, `email`, `name`, `depot_code`, `role_code`, `permissions`, `pw`, `password`, `is_hq`, `is_super_admin`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`, `remember_token`, `last_login_at`) VALUES
('rsf_cgw', 'rsfcgw@krc.co.ke', 'Changamwe', 'CGW', 'booking_officer', '[]', '$2y$10$rJ.vXFpq.xT.NFkt9RsOe.9MuPOuyEl4vMQY2I2NHt.NzpX61nXHC', '$2y$10$rJ.vXFpq.xT.NFkt9RsOe.9MuPOuyEl4vMQY2I2NHt.NzpX61nXHC', 0, 0, 1, '[]', '2026-08-04 09:55:27', '2026-08-07 02:52:32', NULL, NULL, NULL),
('rsf_eld', 'rsfeld@krc.co.ke', 'Eldoret', 'ELD', 'booking_officer', '[]', '$2y$10$eAlGeQmnNdc1uuk/2mE4f.6h6mwNzntNqCzL2Wr1frNggFiYXpjXO', '$2y$10$eAlGeQmnNdc1uuk/2mE4f.6h6mwNzntNqCzL2Wr1frNggFiYXpjXO', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', NULL, NULL, NULL),
('rsf_ksm', 'rsfksm@krc.co.ke', 'Kisumu', 'KSM', 'booking_officer', '[]', '$2y$10$tLB.wbcs2Ic5094ZPn3g5enItS8/9N7BZ5XKndF..e9TQFf220hGa', '$2y$10$tLB.wbcs2Ic5094ZPn3g5enItS8/9N7BZ5XKndF..e9TQFf220hGa', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', NULL, NULL, NULL),
('rsf_mkr', 'rsfmkr@krc.co.ke', 'Makadara', 'MKR', 'booking_officer', '[]', '$2y$10$nBMd1fccC21vO.s4L/QXH.xLM9ZWxZvClOsUbhQo3HzA9DD/BebVW', '$2y$10$nBMd1fccC21vO.s4L/QXH.xLM9ZWxZvClOsUbhQo3HzA9DD/BebVW', 0, 0, 1, '[]', '2026-08-04 09:55:27', '2026-08-07 02:52:32', NULL, NULL, NULL),
('rsf_mlb', 'rsfmlb@krc.co.ke', 'Malaba', 'MLB', 'booking_officer', '[]', '$2y$10$qyR6IyTnhp.7EwuEyg8NHeLrpnwHrIvZcF1zV8myv0bLMbuOP6Hsa', '$2y$10$qyR6IyTnhp.7EwuEyg8NHeLrpnwHrIvZcF1zV8myv0bLMbuOP6Hsa', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', NULL, NULL, NULL),
('rsf_mto', 'rsfmto@krc.co.ke', 'MTITO', 'MTO', 'booking_officer', '[]', '$2y$10$p6x9ZsviC7TPOsxAzP8c7.cjjmh.i.QuSavvLnfeJB8dJWx9NveI6', '$2y$10$p6x9ZsviC7TPOsxAzP8c7.cjjmh.i.QuSavvLnfeJB8dJWx9NveI6', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', NULL, NULL, NULL),
('rsf_nro', 'rsfnro@krc.co.ke', 'Nakuru', 'NRO', 'booking_officer', '[]', '$2y$10$52H7juSr.eA5PLNfZh2F1OkHiZItMNPzWJF6HO5r9IkplTerLgkNW', '$2y$10$52H7juSr.eA5PLNfZh2F1OkHiZItMNPzWJF6HO5r9IkplTerLgkNW', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', NULL, NULL, NULL),
('rsf_nuk', 'rsfnuk@krc.co.ke', 'Nanyuki', 'MKR', 'booking_officer', '[]', '$2y$10$MD0f4L8tbwOneH9XiSDOo.xdzQ.LQ1HyHKbDEy4HzOMQADFis/hGW', '$2y$10$MD0f4L8tbwOneH9XiSDOo.xdzQ.LQ1HyHKbDEy4HzOMQADFis/hGW', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 03:11:25', NULL, NULL, NULL),
('stn_cgw', 'stationmastercgw@krc.co.ke', 'Changamwe Station Officer', 'CGW', 'station_officer', '[]', '$2y$10$kdwYnhwlnB/DcLfqfO87y.VM2149VIu7JLcQ9yELQ1f0s/K/GP4Xq', '$2y$10$kdwYnhwlnB/DcLfqfO87y.VM2149VIu7JLcQ9yELQ1f0s/K/GP4Xq', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', '2026-08-07 02:33:29', NULL, NULL),
('stn_eld', 'stationmastereld@krc.co.ke', 'Eldoret Station Officer', 'ELD', 'station_officer', '[]', '$2y$10$h6Q5NbnzkSpvBX8lDToga.JfeKAVbfGCzwleBbavW01hRIR0duXL6', '$2y$10$h6Q5NbnzkSpvBX8lDToga.JfeKAVbfGCzwleBbavW01hRIR0duXL6', 0, 0, 1, '[]', '2026-08-04 09:55:29', '2026-08-07 02:52:32', NULL, NULL, NULL),
('stn_ksm', 'stationmasterksm@krc.co.ke', 'Kisumu Station Officer', 'KSM', 'station_officer', '[]', '$2y$10$lKs5Yny.Rk8MKS0HQufC2eneowu16z4rIlqojx1SOTP.wVDLp.aa2', '$2y$10$lKs5Yny.Rk8MKS0HQufC2eneowu16z4rIlqojx1SOTP.wVDLp.aa2', 0, 0, 1, '[]', '2026-08-04 09:55:29', '2026-08-07 02:52:32', NULL, NULL, NULL),
('stn_mkr', 'stationmastermkr@krc.co.ke', 'Makadara Station Officer', 'MKR', 'station_officer', '[]', '$2y$10$0CO4VYrwFK6W8.LLrdCqA.YMInPsg8oPwu7csYbqtzQeTVFai9dEq', '$2y$10$0CO4VYrwFK6W8.LLrdCqA.YMInPsg8oPwu7csYbqtzQeTVFai9dEq', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', NULL, NULL, NULL),
('stn_mlb', 'stationmastermlb@krc.co.ke', 'Malaba Station Officer', 'MLB', 'station_officer', '[]', '$2y$10$ysyFBfF./3MvR1fT06H1ZOc69sg58AQoiWa.V95l8KnsOIHU5UUTW', '$2y$10$ysyFBfF./3MvR1fT06H1ZOc69sg58AQoiWa.V95l8KnsOIHU5UUTW', 0, 0, 1, '[]', '2026-08-04 09:55:29', '2026-08-07 02:52:32', NULL, NULL, NULL),
('stn_mto', 'stationmastermto@krc.co.ke', 'MTITO Station Officer', 'MTO', 'station_officer', '[]', '$2y$10$x/wseCenu0nLX/Ubn.fXhe1zKL9MX8gDAEHSU9EnswZz/danHDO/6', '$2y$10$x/wseCenu0nLX/Ubn.fXhe1zKL9MX8gDAEHSU9EnswZz/danHDO/6', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', NULL, NULL, NULL),
('stn_nro', 'stationmasternro@krc.co.ke', 'Nakuru Station Officer', 'NRO', 'station_officer', '[]', '$2y$10$VVJtvfLIqIq6uzRbwKi43.HFiXBXi7FOVVqfbTsexEPZVDgOA9afa', '$2y$10$VVJtvfLIqIq6uzRbwKi43.HFiXBXi7FOVVqfbTsexEPZVDgOA9afa', 0, 0, 1, '[]', '2026-08-04 09:55:28', '2026-08-07 02:52:32', NULL, NULL, NULL),
('stn_nuk', 'stationmasternuk@krc.co.ke', 'Nanyuki Station Officer', 'NUK', 'station_officer', '[]', '$2y$10$/xywxl/eosJXF8VTmPIite/.OtZTLsOratNx7Fo7GR.fZLTl7ZTt.', '$2y$10$/xywxl/eosJXF8VTmPIite/.OtZTLsOratNx7Fo7GR.fZLTl7ZTt.', 0, 0, 1, '[]', '2026-08-04 09:55:29', '2026-08-07 02:52:32', NULL, NULL, NULL),
('superadmin', 'superadmin@example.com', 'Super Admin', 'HQ', 'super_admin', '[\"manage_depots\",\"manage_users\",\"manage_crew\",\"manage_roles\",\"manage_rosters\",\"manage_reports\"]', '$2y$10$cULZ/Ei8DI3p4k7oCs7ueeMlOERDYEzcsrKay.JmuMrrk8zhmIoqy', '$2y$10$cULZ/Ei8DI3p4k7oCs7ueeMlOERDYEzcsrKay.JmuMrrk8zhmIoqy', 1, 1, 1, '[]', '2026-08-07 03:19:20', '2026-08-07 03:19:20', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `username` varchar(255) NOT NULL,
  `permission_code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `username` varchar(255) NOT NULL,
  `role_code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_meta`
--
ALTER TABLE `admin_meta`
  ADD PRIMARY KEY (`collection`,`record_id`);

--
-- Indexes for table `crew_members`
--
ALTER TABLE `crew_members`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `crew_members_crew_id_unique` (`crew_id`),
  ADD UNIQUE KEY `crew_members_staff_number_unique` (`staff_number`),
  ADD KEY `crew_members_depot_code_index` (`depot_code`),
  ADD KEY `crew_members_display_name_index` (`display_name`),
  ADD KEY `crew_members_designation_code_index` (`designation_code`),
  ADD KEY `crew_members_employment_status_code_index` (`employment_status_code`),
  ADD KEY `crew_members_hire_date_index` (`hire_date`),
  ADD KEY `crew_members_email_index` (`email`),
  ADD KEY `crew_members_is_active_index` (`is_active`);

--
-- Indexes for table `crew_records`
--
ALTER TABLE `crew_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `crew_records_depot_index` (`depot`),
  ADD KEY `crew_records_crew_id_index` (`crew_id`),
  ADD KEY `crew_records_staff_number_index` (`staff_number`);

--
-- Indexes for table `crew_shift_assignments`
--
ALTER TABLE `crew_shift_assignments`
  ADD PRIMARY KEY (`crew_record_id`),
  ADD KEY `crew_shift_assignments_record_id_index` (`record_id`),
  ADD KEY `crew_shift_assignments_crew_id_index` (`crew_id`),
  ADD KEY `crew_shift_assignments_depot_index` (`depot`);

--
-- Indexes for table `crew_status_history`
--
ALTER TABLE `crew_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `crew_status_history_crew_record_id_index` (`crew_record_id`),
  ADD KEY `crew_status_history_crew_id_index` (`crew_id`),
  ADD KEY `crew_status_history_depot_code_index` (`depot_code`),
  ADD KEY `crew_status_history_status_code_index` (`status_code`),
  ADD KEY `crew_status_history_reason_code_index` (`reason_code`),
  ADD KEY `crew_status_history_effective_at_index` (`effective_at`);

--
-- Indexes for table `crew_status_segments`
--
ALTER TABLE `crew_status_segments`
  ADD PRIMARY KEY (`segment_id`),
  ADD KEY `crew_status_segments_crew_record_id_index` (`crew_record_id`),
  ADD KEY `crew_status_segments_crew_id_index` (`crew_id`),
  ADD KEY `crew_status_segments_depot_code_index` (`depot_code`),
  ADD KEY `crew_status_segments_month_key_index` (`month_key`),
  ADD KEY `crew_status_segments_day_index` (`day`),
  ADD KEY `crew_status_segments_sort_order_index` (`sort_order`),
  ADD KEY `crew_status_segments_status_code_index` (`status_code`);

--
-- Indexes for table `depots`
--
ALTER TABLE `depots`
  ADD PRIMARY KEY (`depot_code`),
  ADD KEY `depots_region_index` (`region`),
  ADD KEY `depots_is_hq_index` (`is_hq`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`designation_code`),
  ADD KEY `designations_sort_order_index` (`sort_order`),
  ADD KEY `designations_is_active_index` (`is_active`);

--
-- Indexes for table `duty_rosters`
--
ALTER TABLE `duty_rosters`
  ADD PRIMARY KEY (`roster_id`),
  ADD KEY `duty_rosters_depot_code_index` (`depot_code`),
  ADD KEY `duty_rosters_roster_date_index` (`roster_date`),
  ADD KEY `duty_rosters_period_label_index` (`period_label`),
  ADD KEY `duty_rosters_status_index` (`status`);

--
-- Indexes for table `duty_roster_items`
--
ALTER TABLE `duty_roster_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `duty_roster_items_roster_id_index` (`roster_id`),
  ADD KEY `duty_roster_items_crew_record_id_index` (`crew_record_id`),
  ADD KEY `duty_roster_items_crew_id_index` (`crew_id`),
  ADD KEY `duty_roster_items_shift_code_index` (`shift_code`),
  ADD KEY `duty_roster_items_train_type_code_index` (`train_type_code`),
  ADD KEY `duty_roster_items_route_code_index` (`route_code`),
  ADD KEY `duty_roster_items_rest_location_code_index` (`rest_location_code`),
  ADD KEY `duty_roster_items_duty_date_index` (`duty_date`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_code`),
  ADD KEY `permissions_is_system_index` (`is_system`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`region_code`),
  ADD KEY `regions_is_active_index` (`is_active`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reports_slug_unique` (`slug`);

--
-- Indexes for table `rest_locations`
--
ALTER TABLE `rest_locations`
  ADD PRIMARY KEY (`rest_location_code`),
  ADD KEY `rest_locations_depot_code_index` (`depot_code`),
  ADD KEY `rest_locations_is_active_index` (`is_active`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_code`),
  ADD KEY `roles_is_system_index` (`is_system`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_code`,`permission_code`),
  ADD KEY `role_permissions_role_code_index` (`role_code`),
  ADD KEY `role_permissions_permission_code_index` (`permission_code`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`route_code`),
  ADD KEY `routes_origin_depot_code_index` (`origin_depot_code`),
  ADD KEY `routes_destination_depot_code_index` (`destination_depot_code`),
  ADD KEY `routes_is_active_index` (`is_active`);

--
-- Indexes for table `shift_templates`
--
ALTER TABLE `shift_templates`
  ADD PRIMARY KEY (`shift_code`),
  ADD KEY `shift_templates_sort_order_index` (`sort_order`),
  ADD KEY `shift_templates_is_active_index` (`is_active`);

--
-- Indexes for table `status_codes`
--
ALTER TABLE `status_codes`
  ADD PRIMARY KEY (`status_code`),
  ADD KEY `status_codes_sort_order_index` (`sort_order`),
  ADD KEY `status_codes_is_terminal_index` (`is_terminal`);

--
-- Indexes for table `train_types`
--
ALTER TABLE `train_types`
  ADD PRIMARY KEY (`train_type_code`),
  ADD KEY `train_types_sort_order_index` (`sort_order`),
  ADD KEY `train_types_is_active_index` (`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_depot_code_index` (`depot_code`),
  ADD KEY `users_role_code_index` (`role_code`),
  ADD KEY `users_is_hq_index` (`is_hq`),
  ADD KEY `users_is_super_admin_index` (`is_super_admin`),
  ADD KEY `users_is_active_index` (`is_active`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`username`,`permission_code`),
  ADD KEY `user_permissions_username_index` (`username`),
  ADD KEY `user_permissions_permission_code_index` (`permission_code`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`username`,`role_code`),
  ADD KEY `user_roles_username_index` (`username`),
  ADD KEY `user_roles_role_code_index` (`role_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
