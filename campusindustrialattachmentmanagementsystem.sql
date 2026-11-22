-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2026 at 03:45 PM
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
-- Database: `campusindustrialattachmentmanagementsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `opportunity_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `date_applied` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `student_id`, `opportunity_id`, `cover_letter`, `status`, `date_applied`) VALUES
(1, 1, 7, 'Dear Sir/Madam,\r\nI am writing to formally express my interest in securing a Medical Laboratory Research Attachment at KEMRI for the period starting from 1st May 2026 to 31st July 2026.\r\nI am currently a student pursuing a Bachelor of Science in Medical Laboratory Sciences at Daystar University. As part of my academic requirements and school project, I am eager to apply my theoretical knowledge in microbiology, hematology, biochemistry and lab safety within a professional research or clinical setting.\r\nAn attachment with your esteemed organization will allow me to gain hands-on technical skills, observe standard operating procedures (SOPs), and contribute positively to your team\'s day-to-day operations and project goals.\r\nI have attached my curriculum vitae (CV), university/college introduction letter, student ID copy, and academic transcripts for your review.\r\nThank you for your time, consideration, and ongoing support of student mentorship. I look forward to the possibility of discussing this opportunity further.\r\nYours sincerely,\r\nWarren Maina\r\nwarren.maina@gmail.com', 'pending', '2026-11-13 08:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `opportunities`
--

CREATE TABLE `opportunities` (
  `opportunity_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `date_posted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `opportunities`
--

INSERT INTO `opportunities` (`opportunity_id`, `organization_id`, `title`, `description`, `requirements`, `location`, `duration`, `status`, `date_posted`) VALUES
(7, 1, 'Medical Laboratory Research Attachment', 'Assist laboratory personnel with specimen handling, basic laboratory procedures, preparation of research materials and maintenance of laboratory records.', 'Basic knowledge of medical laboratory science, biology or biomedical science. Attention to detail and willingness to learn.', 'Nairobi', '3 months', 'open', '2026-11-12 12:26:32'),
(8, 1, 'Public Health Research Attachment', 'Support public health research activities, assist with health data collection, organize research information and participate in field research activities.', 'Basic knowledge of public health, community health or health sciences. Good communication and research skills.', 'Nairobi', '6 months', 'open', '2026-11-12 12:26:32'),
(9, 1, 'Biostatistics and Data Analysis Attachment', 'Assist research teams with data entry, data cleaning, basic statistical analysis and preparation of research reports.', 'Basic knowledge of statistics, mathematics and data analysis. Familiarity with Microsoft Excel is an added advantage.', 'Nairobi', '3 months', 'open', '2026-11-12 12:26:32');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `organization_id` int(11) NOT NULL,
  `organization_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`organization_id`, `organization_name`, `email`, `password_hash`, `phone`, `address`, `description`, `created_at`) VALUES
(1, 'Kenya Medical Research Institute (KEMRI)', 'info@kemri.go.ke', '$2y$10$uJhoazyw/jRTOlLox2exKeBu38Oczymsx2DLjjL66wYWVurYsh.6K', '0202722541', 'P.O. Box 54840 - 00200, Nairobi, Kenya', 'The Kenya Medical Research Institute (KEMRI) is a state corporation established in 1979 as the national body responsible for human health research in Kenya. It conducts biomedical, clinical, and public health research, disease surveillance, outbreak response (such as cholera, Ebola, and COVID-19), and advises the government on health policies.', '2026-11-10 06:21:10');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `full_name`, `email`, `password_hash`, `phone`, `course`, `year_of_study`, `resume_path`, `created_at`) VALUES
(1, 'Warren Maina', 'warren.maina@strathmore.edu', '$2y$10$LWOI0c/kCdb1gPeaTK0bLu4.xiWWg/lem1f/IC9JXuIM0TN6Fwjmq', '0783461930', 'BBIT', 2, NULL, '2026-11-10 06:12:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `unique_student_opportunity` (`student_id`,`opportunity_id`),
  ADD KEY `fk_application_opportunity` (`opportunity_id`);

--
-- Indexes for table `opportunities`
--
ALTER TABLE `opportunities`
  ADD PRIMARY KEY (`opportunity_id`),
  ADD KEY `fk_opportunity_organization` (`organization_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`organization_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `opportunities`
--
ALTER TABLE `opportunities`
  MODIFY `opportunity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_application_opportunity` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`opportunity_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_application_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE;

--
-- Constraints for table `opportunities`
--
ALTER TABLE `opportunities`
  ADD CONSTRAINT `fk_opportunity_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
