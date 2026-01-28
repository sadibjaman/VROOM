-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 11:20 PM
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
-- Database: `motorbike_ride_sharing`
--

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

CREATE TABLE `passengers` (
  `passengerID` int(11) NOT NULL,
  `totalRides` int(11) DEFAULT 0,
  `avgRating` decimal(3,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `paymentID` int(11) NOT NULL,
  `rideID` int(11) NOT NULL,
  `paymentMethod` enum('credit_card','debit_card','paypal','cash','wallet') NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `payStatus` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `bookingID` varchar(50) DEFAULT NULL,
  `paymentDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `ratingID` int(11) NOT NULL,
  `rideID` int(11) NOT NULL,
  `ratedBy` int(11) NOT NULL,
  `ratedTo` int(11) NOT NULL,
  `ratingValue` decimal(2,1) DEFAULT NULL CHECK (`ratingValue` >= 1.0 and `ratingValue` <= 5.0),
  `comment` text DEFAULT NULL,
  `ratingDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riders`
--

CREATE TABLE `riders` (
  `riderID` int(11) NOT NULL,
  `experience` int(11) DEFAULT 0,
  `avgRating` decimal(3,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rides`
--

CREATE TABLE `rides` (
  `rideID` int(11) NOT NULL,
  `passengerID` int(11) NOT NULL,
  `riderID` int(11) DEFAULT NULL,
  `vehicleID` int(11) NOT NULL,
  `routeID` int(11) NOT NULL,
  `dateTime` datetime NOT NULL,
  `actualFare` decimal(8,2) DEFAULT NULL,
  `status` enum('requested','accepted','in_progress','completed','cancelled') DEFAULT 'requested',
  `rideType` enum('motorbike','scooter','ebike') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `routeID` int(11) NOT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `estimatedTime` int(11) NOT NULL,
  `estimatedDistance` decimal(8,2) NOT NULL,
  `estimatedFare` decimal(8,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `walletBalance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicleID` int(11) NOT NULL,
  `riderID` int(11) NOT NULL,
  `rideType` enum('motorbike','scooter','ebike') NOT NULL,
  `model` varchar(50) DEFAULT NULL,
  `registration` varchar(20) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','out_of_service') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`passengerID`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`paymentID`),
  ADD KEY `rideID` (`rideID`),
  ADD KEY `idx_payments_status` (`payStatus`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`ratingID`),
  ADD UNIQUE KEY `unique_ride_rating` (`rideID`,`ratedBy`),
  ADD KEY `ratedBy` (`ratedBy`),
  ADD KEY `ratedTo` (`ratedTo`),
  ADD KEY `idx_ratings_value` (`ratingValue`);

--
-- Indexes for table `riders`
--
ALTER TABLE `riders`
  ADD PRIMARY KEY (`riderID`);

--
-- Indexes for table `rides`
--
ALTER TABLE `rides`
  ADD PRIMARY KEY (`rideID`),
  ADD KEY `passengerID` (`passengerID`),
  ADD KEY `vehicleID` (`vehicleID`),
  ADD KEY `routeID` (`routeID`),
  ADD KEY `idx_rides_status` (`status`),
  ADD KEY `idx_rides_datetime` (`dateTime`),
  ADD KEY `rides_ibfk_2` (`riderID`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`routeID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicleID`),
  ADD UNIQUE KEY `registration` (`registration`),
  ADD KEY `riderID` (`riderID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `paymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `ratingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `rides`
--
ALTER TABLE `rides`
  MODIFY `rideID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `routeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `passengers`
--
ALTER TABLE `passengers`
  ADD CONSTRAINT `passengers_ibfk_1` FOREIGN KEY (`passengerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`rideID`) REFERENCES `rides` (`rideID`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`rideID`) REFERENCES `rides` (`rideID`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`ratedBy`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`ratedTo`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `riders`
--
ALTER TABLE `riders`
  ADD CONSTRAINT `riders_ibfk_1` FOREIGN KEY (`riderID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `rides`
--
ALTER TABLE `rides`
  ADD CONSTRAINT `rides_ibfk_1` FOREIGN KEY (`passengerID`) REFERENCES `passengers` (`passengerID`),
  ADD CONSTRAINT `rides_ibfk_2` FOREIGN KEY (`riderID`) REFERENCES `riders` (`riderID`) ON DELETE SET NULL,
  ADD CONSTRAINT `rides_ibfk_3` FOREIGN KEY (`vehicleID`) REFERENCES `vehicles` (`vehicleID`),
  ADD CONSTRAINT `rides_ibfk_4` FOREIGN KEY (`routeID`) REFERENCES `routes` (`routeID`);

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`riderID`) REFERENCES `riders` (`riderID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
