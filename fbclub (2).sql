-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2025 at 04:09 AM
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
-- Database: `fbclub`
--

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `founded_year` int(11) DEFAULT NULL,
  `stadium` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `founded_year`, `stadium`, `location`, `logo`) VALUES
(1, 'Barcalona', 2005, 'Campnou', 'Spain', 'uploads/club_logos/club_689cc0845d2f0.png'),
(2, 'Kerala Blasters', 2016, 'Jawaharlal nehru', 'Kochi', 'uploads/club_logos/club_689cc1eec32ca.png'),
(3, 'Mancherster City', 1894, 'Eitihad', 'England', 'uploads/club_logos/club_689cc24e7b97d.png'),
(4, 'Real Madrid', 1800, 'Bernabau', 'Spain', 'uploads/club_logos/club_689e1e05dfede.png');

-- --------------------------------------------------------

--
-- Table structure for table `coaches`
--

CREATE TABLE `coaches` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `license` varchar(50) DEFAULT NULL,
  `coach_type` enum('manager','assistant') NOT NULL,
  `experience` text DEFAULT NULL,
  `club_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coaches`
--

INSERT INTO `coaches` (`id`, `user_id`, `first_name`, `last_name`, `license`, `coach_type`, `experience`, `club_id`) VALUES
(4, 7, 'Pep', 'Guardiola', '355-555-766-99', 'manager', 'Pep Guardiola has won a total of 40 trophies as a manager across his coaching career with Barcelona, Bayern Munich, and Manchester City, according to a Facebook post. This includes 14 trophies with Barcelona, 7 with Bayern Munich, and 19 with Manchester City.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `coach_requests`
--

CREATE TABLE `coach_requests` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `request_message` text DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coach_requests`
--

INSERT INTO `coach_requests` (`id`, `coach_id`, `club_id`, `request_message`, `request_date`, `status`) VALUES
(4, 4, 3, '', '2025-08-14 17:35:58', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `join_requests`
--

CREATE TABLE `join_requests` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `join_requests`
--

INSERT INTO `join_requests` (`id`, `player_id`, `club_id`, `request_date`, `status`) VALUES
(4, 3, 3, '2025-08-14 17:32:48', 'approved'),
(5, 4, 3, '2025-08-14 18:03:30', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `match_performance`
--

CREATE TABLE `match_performance` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `match_date` date NOT NULL,
  `opponent` varchar(100) NOT NULL,
  `goals` int(11) DEFAULT 0,
  `assists` int(11) DEFAULT 0,
  `minutes_played` int(11) DEFAULT 0,
  `rating` decimal(3,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `position` enum('Goalkeeper','Left Back','Center Back','Right Back','Left Midfielder','Center Midfielder','Right Midfielder','Left Winger','Center Forward','Right Winger') NOT NULL,
  `skills` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `club_id` int(11) DEFAULT NULL,
  `status` enum('active','pending','inactive') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `user_id`, `first_name`, `last_name`, `date_of_birth`, `position`, `skills`, `photo`, `club_id`, `status`) VALUES
(3, 8, 'Kevin', 'De Bruyne', '1991-06-28', 'Center Midfielder', '6 Premier League titles, 5 League Cups, 2 FA Cups, 3 Community Shields, 1 Champions League, 1 FIFA Club World Cup, and 1 UEFA Super Cup. He also won the German Super Cup and the German Cup with Wolfsburg, and the cup, first division league, and Super Cup with Genk.', 'uploads/689e1d5e68fcd_3834.png', 3, 'active'),
(4, 9, 'Erling', 'Haaland', '2000-07-21', 'Center Forward', 'speed, strength, positioning, and finishing inside the box. In his debut Premier League season, Haaland broke the record for the most goals scored by a player in a single season, with 36', 'uploads/689e24e41ae4a_5689.png', NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `player_stats`
--

CREATE TABLE `player_stats` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `appearances` int(11) DEFAULT 0,
  `goals` int(11) DEFAULT 0,
  `assists` int(11) DEFAULT 0,
  `yellow_cards` int(11) DEFAULT 0,
  `red_cards` int(11) DEFAULT 0,
  `minutes_played` int(11) DEFAULT 0,
  `pass_accuracy` decimal(5,2) DEFAULT 0.00,
  `tackle_success` decimal(5,2) DEFAULT 0.00,
  `shot_accuracy` decimal(5,2) DEFAULT 0.00,
  `duels_won` decimal(5,2) DEFAULT 0.00,
  `shots_on_target` int(11) DEFAULT 0,
  `tackles` int(11) DEFAULT 0,
  `interceptions` int(11) DEFAULT 0,
  `key_passes` int(11) DEFAULT 0,
  `cross_accuracy` decimal(5,2) DEFAULT 0.00,
  `rating` decimal(3,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_sessions`
--

CREATE TABLE `training_sessions` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(100) NOT NULL,
  `focus_area` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','coach','player') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@fcms.com', 'admin', '2025-08-13 09:45:30'),
(7, 'pep2025', '$2y$10$JcZynSHnZJBoPi.3lYdJguXj/5gDqHsYYF29tUKX0zCgzCupxEqZW', 'pep2025@gmail.com', 'coach', '2025-08-14 17:27:58'),
(8, 'KDB2025', '$2y$10$MA6asxm5BXCSMRq0H81Tl.5Pa7cfRBW5pOTAVsNZ0PJIX89s8bRfK', 'kevindb25@gmail.com', 'player', '2025-08-14 17:31:10'),
(9, 'ER2025', '$2y$10$Ps35sQRXkS8wpwJWLQXIT.gM2gtekBmiMpB0UbR47tMRq.579EEzC', 'erlinghala@gmail.com', 'player', '2025-08-14 18:03:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coaches`
--
ALTER TABLE `coaches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `club_id` (`club_id`);

--
-- Indexes for table `coach_requests`
--
ALTER TABLE `coach_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coach_id` (`coach_id`),
  ADD KEY `club_id` (`club_id`);

--
-- Indexes for table `join_requests`
--
ALTER TABLE `join_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `club_id` (`club_id`);

--
-- Indexes for table `match_performance`
--
ALTER TABLE `match_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `club_id` (`club_id`);

--
-- Indexes for table `player_stats`
--
ALTER TABLE `player_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_id` (`club_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coach_requests`
--
ALTER TABLE `coach_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `join_requests`
--
ALTER TABLE `join_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `match_performance`
--
ALTER TABLE `match_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `player_stats`
--
ALTER TABLE `player_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_sessions`
--
ALTER TABLE `training_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `coaches`
--
ALTER TABLE `coaches`
  ADD CONSTRAINT `coaches_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coaches_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coach_requests`
--
ALTER TABLE `coach_requests`
  ADD CONSTRAINT `coach_requests_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coach_requests_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `join_requests`
--
ALTER TABLE `join_requests`
  ADD CONSTRAINT `join_requests_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `join_requests_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `match_performance`
--
ALTER TABLE `match_performance`
  ADD CONSTRAINT `match_performance_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `players_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `player_stats`
--
ALTER TABLE `player_stats`
  ADD CONSTRAINT `player_stats_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD CONSTRAINT `training_sessions_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
