-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2026 at 11:32 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vaccination_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `recommended_age` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `name`, `recommended_age`, `description`, `status`, `created_at`) VALUES
(1, 'zaid', '3weeks', '', 'Available', '2026-11-17 12:46:44'),
(2, 'BCG Vaccine', 'At Birth (within 24h)', 'Protects newborns against severe forms of childhood tuberculosis, including TB meningitis.', 'Available', '2026-11-18 06:49:56'),
(3, 'Oral Polio Vaccine (OPV & IPV)', 'Birth, 6, 10, 14 Weeks & 9 Months', 'Protects children from crippling poliovirus which attacks nerve cells and causes permanent paralysis.', 'Available', '2026-11-18 06:49:56'),
(4, 'Hepatitis B Vaccine', 'Birth Dose + 6, 10, 14 Weeks', 'Shields infants against chronic Hepatitis B viral infection that can cause severe liver damage.', 'Unavailable', '2026-11-18 06:49:56'),
(5, 'Pentavalent Vaccine (DTP-HepB-Hib)', '6, 10 & 14 Weeks', 'A single 5-in-1 shot against diphtheria, pertussis, tetanus, hepatitis B, and Haemophilus influenzae type B.', 'Available', '2026-11-18 06:49:56'),
(6, 'Pneumococcal Vaccine (PCV)', '6, 10 & 14 Weeks', 'Protects children against streptococcus bacteria causing severe pneumonia, bacteremia, and meningitis.', 'Available', '2026-11-18 06:49:56'),
(7, 'Rotavirus Vaccine', '6 & 10 Weeks', 'Oral drops protecting infants against rotavirus, the most common cause of severe dehydrating diarrhea.', 'Unavailable', '2026-11-18 06:49:56'),
(8, 'Measles-Rubella (MR) Vaccine', '9 Months & 15 Months', 'Provides critical immunity against highly contagious measles rash, high fever, and rubella birth defects.', 'Available', '2026-11-18 06:49:56'),
(9, 'Typhoid Conjugate Vaccine (TCV)', '9 Months', 'Pakistan official vaccine guarding children against extensively drug-resistant (XDR) Salmonella typhoid fever.', 'Available', '2026-11-18 06:49:56'),
(10, 'DTP Booster Vaccine', '18-24 Months & 4-5 Years', 'Reinforces immunity against diphtheria, tetanus, and pertussis as the child grows into preschool age.', 'Available', '2026-11-18 06:49:56'),
(11, 'Varicella (Chickenpox) Vaccine', '12-15 Months & 4-6 Years', 'Prevents itchy blister rash, fever, and skin infections caused by the varicella-zoster virus in toddlers.', 'Available', '2026-11-18 06:49:56'),
(12, 'Hepatitis A Vaccine', '12 Months & 18 Months', 'Guards toddlers from acute liver inflammation and jaundice spread through contaminated food or water.', 'Available', '2026-11-18 06:49:56'),
(13, 'Seasonal Influenza (Flu) Vaccine', '6 Months Onwards (Annual)', 'Annual pediatric shot protecting children respiratory airways against seasonal influenza strains and chest infections.', 'Available', '2026-11-18 06:49:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
