-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 13, 2025 at 07:02 PM
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
-- Database: `asrp`
--

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `Client_ID` int(11) NOT NULL,
  `Client_fn` varchar(255) DEFAULT NULL COMMENT 'First name',
  `Client_ln` varchar(255) DEFAULT NULL COMMENT 'Last name',
  `Client_Email` varchar(255) NOT NULL COMMENT 'Email address',
  `Client_Phone` varchar(20) DEFAULT NULL COMMENT 'Phone number',
  `C_username` varchar(50) NOT NULL COMMENT 'Username (unique)',
  `C_password` varchar(255) NOT NULL COMMENT 'Password (hashed)',
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Account status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`Client_ID`, `Client_fn`, `Client_ln`, `Client_Email`, `Client_Phone`, `C_username`, `C_password`, `Status`) VALUES
(32, 'Luke', 'Tolentino', 'dadada@gmail.com', '09668257301', '111', '$2y$10$9zovzj.wzOLwPL90b3fQUuGzPQ98w8EkpChzHvDoEWzkWjiMaYW2O', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `clientfeedback`
--

CREATE TABLE `clientfeedback` (
  `Feedback_ID` int(11) NOT NULL,
  `CS_ID` int(11) DEFAULT NULL,
  `Rating` int(11) DEFAULT NULL CHECK (`Rating` between 1 and 5),
  `Comments` text DEFAULT NULL,
  `Dates` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clientfeedback`
--

INSERT INTO `clientfeedback` (`Feedback_ID`, `CS_ID`, `Rating`, `Comments`, `Dates`) VALUES
(20, 1155, 5, 'adadwad', '2025-08-10');

-- --------------------------------------------------------

--
-- Table structure for table `clientspace`
--

CREATE TABLE `clientspace` (
  `CS_ID` int(11) NOT NULL,
  `Space_ID` int(11) DEFAULT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `BusinessPhoto` varchar(255) DEFAULT NULL,
  `BusinessPhoto1` varchar(255) DEFAULT NULL,
  `BusinessPhoto2` varchar(255) DEFAULT NULL,
  `BusinessPhoto3` varchar(255) DEFAULT NULL,
  `BusinessPhoto4` varchar(255) DEFAULT NULL,
  `BusinessPhoto5` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clientspace`
--

INSERT INTO `clientspace` (`CS_ID`, `Space_ID`, `Client_ID`, `active`, `BusinessPhoto`, `BusinessPhoto1`, `BusinessPhoto2`, `BusinessPhoto3`, `BusinessPhoto4`, `BusinessPhoto5`) VALUES
(68, 56, 32, 1, NULL, 'unit_56_client_32_689c2b5fc2d7d.jpg', NULL, NULL, NULL, NULL),
(69, 55, 32, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(71, 54, 32, 1, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `free_message`
--

CREATE TABLE `free_message` (
  `Message_ID` int(11) NOT NULL,
  `Client_Name` varchar(255) NOT NULL,
  `Client_Email` varchar(255) NOT NULL,
  `Client_Phone` varchar(30) DEFAULT NULL,
  `Message_Text` text NOT NULL,
  `Sent_At` datetime DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `free_message`
--

INSERT INTO `free_message` (`Message_ID`, `Client_Name`, `Client_Email`, `Client_Phone`, `Message_Text`, `Sent_At`, `is_deleted`) VALUES
(1, 'Romeo Paolo', 'romeo@gmail.com', '096682532', 'tanga kaba', '2025-08-11 06:42:45', 1),
(2, 'fawfa', 'romeopaolotolen@gmail.com', 'dwadwa', 'gago kaba', '2025-08-11 06:45:30', 1),
(3, 'Prinz', 'djahdjahw@GMAIL.com', '09090', 'kupal', '2025-08-11 12:35:16', 1),
(4, 'titi', 'romeo@gmail.com', '09090', 'adwadwa', '2025-08-13 13:55:07', 1),
(5, 'sdadwa', 'dwadwa@gmail.com', '09090', 'adwadwa', '2025-08-14 01:01:45', 0);

-- --------------------------------------------------------

--
-- Table structure for table `handyman`
--

CREATE TABLE `handyman` (
  `Handyman_ID` int(11) NOT NULL,
  `Handyman_fn` varchar(255) DEFAULT NULL,
  `Handyman_ln` varchar(255) DEFAULT NULL,
  `Phone` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `handymanjob`
--

CREATE TABLE `handymanjob` (
  `HJ_ID` int(11) NOT NULL,
  `Handyman_ID` int(11) DEFAULT NULL,
  `JobType_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `Invoice_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `InvoiceDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `InvoiceTotal` decimal(10,2) DEFAULT NULL,
  `Status` varchar(10) DEFAULT 'unpaid',
  `Space_ID` int(11) DEFAULT NULL,
  `Flow_Status` varchar(10) NOT NULL DEFAULT 'new',
  `Chat_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`Invoice_ID`, `Client_ID`, `InvoiceDate`, `EndDate`, `InvoiceTotal`, `Status`, `Space_ID`, `Flow_Status`, `Chat_ID`) VALUES
(1151, 32, '2025-08-10', '2025-09-10', 11000.00, 'paid', 56, 'done', NULL),
(1152, 32, '2025-09-11', '2025-10-10', 11000.00, 'paid', 56, 'done', NULL),
(1153, 32, '2025-08-10', '2025-08-15', 11000.00, 'paid', 55, 'done', NULL),
(1154, 32, '2025-08-16', '2025-09-15', 11000.00, 'paid', 55, 'done', NULL),
(1155, 32, '2025-08-10', '2025-08-10', 11000.00, 'kicked', 54, 'done', NULL),
(1156, 32, '2025-10-11', '2025-11-10', 11000.00, 'paid', 56, 'done', NULL),
(1157, 32, '2025-11-11', '2025-12-10', 11000.00, 'unpaid', 56, 'new', NULL),
(1158, 32, '2025-08-12', '2025-08-15', 11000.00, 'paid', 54, 'done', NULL),
(1159, 32, '2025-08-16', '2025-09-15', 11000.00, 'unpaid', 54, 'new', NULL),
(1160, 32, '2025-09-16', '2025-10-15', 11000.00, 'unpaid', 55, 'new', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_chat`
--

CREATE TABLE `invoice_chat` (
  `Chat_ID` int(11) NOT NULL,
  `Invoice_ID` int(11) NOT NULL,
  `Sender_Type` enum('admin','client','system') NOT NULL,
  `Sender_ID` int(11) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `Image_Path` varchar(255) DEFAULT NULL,
  `Created_At` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_chat`
--

INSERT INTO `invoice_chat` (`Chat_ID`, `Invoice_ID`, `Sender_Type`, `Sender_ID`, `Message`, `Image_Path`, `Created_At`) VALUES
(213, 1151, 'system', NULL, 'This rent has been PAID on 2025-08-09.', NULL, '2025-08-10 05:05:11'),
(214, 1152, 'system', NULL, 'This rent has been PAID on 2025-08-09.', NULL, '2025-08-10 05:05:11'),
(215, 1152, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-10 05:05:11'),
(216, 1153, 'system', NULL, 'This rent has been PAID on 2025-08-09.', NULL, '2025-08-10 05:06:01'),
(217, 1154, 'system', NULL, 'This rent has been PAID on 2025-08-09.', NULL, '2025-08-10 05:06:01'),
(218, 1154, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-10 05:06:01'),
(219, 1152, 'admin', 1, 'sup', NULL, '2025-08-11 06:48:32'),
(220, 1152, 'client', 32, 'nigga what', NULL, '2025-08-11 06:48:41'),
(221, 1152, 'system', NULL, 'This rent has been PAID on 2025-08-11.', NULL, '2025-08-11 12:38:20'),
(222, 1156, 'system', NULL, 'This rent has been PAID on 2025-08-09.', NULL, '2025-08-10 05:05:11'),
(223, 1156, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-10 05:05:11'),
(224, 1156, 'admin', 1, 'sup', NULL, '2025-08-11 06:48:32'),
(225, 1156, 'client', 32, 'nigga what', NULL, '2025-08-11 06:48:41'),
(226, 1156, 'system', NULL, 'This rent has been PAID on 2025-08-11.', NULL, '2025-08-11 12:38:20'),
(227, 1156, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-11 12:38:20'),
(228, 1156, 'admin', 1, '', 'uploads/invoice_chat/1754955714_mark.jpg', '2025-08-12 07:41:54'),
(229, 1156, 'system', NULL, 'This rent has been PAID on 2025-08-12.', NULL, '2025-08-12 07:58:07'),
(230, 1157, 'system', NULL, 'This rent has been PAID on 2025-08-09.', NULL, '2025-08-10 05:05:11'),
(231, 1157, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-10 05:05:11'),
(232, 1157, 'admin', 1, 'sup', NULL, '2025-08-11 06:48:32'),
(233, 1157, 'client', 32, 'nigga what', NULL, '2025-08-11 06:48:41'),
(234, 1157, 'system', NULL, 'This rent has been PAID on 2025-08-11.', NULL, '2025-08-11 12:38:20'),
(235, 1157, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-11 12:38:20'),
(236, 1157, 'admin', 1, '', 'uploads/invoice_chat/1754955714_mark.jpg', '2025-08-12 07:41:54'),
(237, 1157, 'system', NULL, 'This rent has been PAID on 2025-08-12.', NULL, '2025-08-12 07:58:07'),
(238, 1157, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-12 07:58:07'),
(239, 1158, 'system', NULL, 'This rent has been PAID on 2025-08-12.', NULL, '2025-08-12 08:22:50'),
(240, 1159, 'system', NULL, 'This rent has been PAID on 2025-08-12.', NULL, '2025-08-12 08:22:50'),
(241, 1159, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-12 08:22:50'),
(242, 1154, 'system', NULL, 'This rent has been PAID on 2025-08-13.', NULL, '2025-08-13 14:06:33'),
(243, 1160, 'system', NULL, 'This rent has been PAID on 2025-08-09.', NULL, '2025-08-10 05:06:01'),
(244, 1160, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-10 05:06:01'),
(245, 1160, 'system', NULL, 'This rent has been PAID on 2025-08-13.', NULL, '2025-08-13 14:06:33'),
(246, 1160, 'system', NULL, 'Conversation continued from previous invoice.', NULL, '2025-08-13 14:06:33'),
(247, 1157, 'client', 32, 'titi', NULL, '2025-08-14 01:00:31'),
(248, 1157, 'client', 32, 'nigga', 'uploads/invoice_chat/1755104460_WIN_20250812_15_13_58_Pro.jpg', '2025-08-14 01:01:00');

-- --------------------------------------------------------

--
-- Table structure for table `jobtype`
--

CREATE TABLE `jobtype` (
  `JobType_ID` int(11) NOT NULL,
  `JobType_Name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenancerequest`
--

CREATE TABLE `maintenancerequest` (
  `Request_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Space_ID` int(11) DEFAULT NULL,
  `Handyman_ID` int(11) DEFAULT NULL,
  `RequestDate` date DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenancerequest`
--

INSERT INTO `maintenancerequest` (`Request_ID`, `Client_ID`, `Space_ID`, `Handyman_ID`, `RequestDate`, `Status`) VALUES
(1, 32, 56, NULL, '2025-08-12', 'In Progress');

-- --------------------------------------------------------

--
-- Table structure for table `maintenancerequeststatushistory`
--

CREATE TABLE `maintenancerequeststatushistory` (
  `MRSH_ID` int(11) NOT NULL,
  `Request_ID` int(11) DEFAULT NULL,
  `StatusChangeDate` date DEFAULT NULL,
  `NewStatus` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenancerequeststatushistory`
--

INSERT INTO `maintenancerequeststatushistory` (`MRSH_ID`, `Request_ID`, `StatusChangeDate`, `NewStatus`) VALUES
(1, 1, '2025-08-12', 'Submitted'),
(2, 1, '2025-08-12', 'In Progress');

-- --------------------------------------------------------

--
-- Table structure for table `paymenthistory`
--

CREATE TABLE `paymenthistory` (
  `PaymentHistory_ID` int(11) NOT NULL,
  `Invoice_ID` int(11) NOT NULL,
  `PaymentDate` date NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rentalrequest`
--

CREATE TABLE `rentalrequest` (
  `Request_ID` int(11) NOT NULL,
  `Client_ID` int(11) NOT NULL,
  `Space_ID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `Status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `Flow_Status` varchar(10) NOT NULL DEFAULT 'new',
  `Requested_At` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rentalrequest`
--

INSERT INTO `rentalrequest` (`Request_ID`, `Client_ID`, `Space_ID`, `StartDate`, `EndDate`, `Status`, `Flow_Status`, `Requested_At`) VALUES
(1154, 32, 56, '2025-08-10', '2025-09-10', 'Accepted', 'done', '2025-08-10 05:05:00'),
(1155, 32, 55, '2025-08-10', '2025-08-15', 'Accepted', 'done', '2025-08-10 05:05:51'),
(1156, 32, 54, '2025-08-10', '2025-08-10', 'Rejected', 'new', '2025-08-10 05:06:19'),
(1157, 32, 54, '2025-08-12', '2025-08-15', 'Accepted', 'done', '2025-08-12 08:22:26');

-- --------------------------------------------------------

--
-- Table structure for table `space`
--

CREATE TABLE `space` (
  `Space_ID` int(11) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `SpaceType_ID` int(11) DEFAULT NULL,
  `UA_ID` int(11) DEFAULT NULL,
  `Street` varchar(255) DEFAULT NULL,
  `Brgy` varchar(255) DEFAULT NULL,
  `City` varchar(255) DEFAULT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `Flow_Status` varchar(10) NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `space`
--

INSERT INTO `space` (`Space_ID`, `Name`, `SpaceType_ID`, `UA_ID`, `Street`, `Brgy`, `City`, `Photo`, `Price`, `Flow_Status`) VALUES
(54, 'Space 1', 2, 1, 'General Luna Strt', '10', 'Lipa City', 'adminunit_1754773420.jpg', 11000.00, 'old'),
(55, 'Space 3', 2, 1, 'General Luna Strt', '10', 'Lipa City', 'adminunit_1754773441.jpg', 11000.00, 'old'),
(56, 'Space 2', 2, 1, 'General Luna Strt', '10', 'Lipa City', 'adminunit_1754773459.jfif', 11000.00, 'old'),
(57, 'Space 4', 2, 1, 'General Luna Strt', '10', 'Lipa City', 'adminunit_1754886853.jpg', 11000.00, 'new');

-- --------------------------------------------------------

--
-- Table structure for table `spaceavailability`
--

CREATE TABLE `spaceavailability` (
  `Availability_ID` int(11) NOT NULL,
  `Space_ID` int(11) DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spaceavailability`
--

INSERT INTO `spaceavailability` (`Availability_ID`, `Space_ID`, `StartDate`, `EndDate`, `Status`) VALUES
(176, 54, NULL, NULL, 'Available'),
(177, 55, NULL, NULL, 'Available'),
(178, 56, NULL, NULL, 'Available'),
(179, 56, '2025-08-10', '2025-09-10', 'Occupied'),
(180, 55, '2025-08-10', '2025-08-15', 'Occupied'),
(181, 54, '2025-08-10', '2025-08-10', 'Available'),
(182, 57, NULL, NULL, 'Available'),
(183, 54, '2025-08-12', '2025-08-15', 'Occupied');

-- --------------------------------------------------------

--
-- Table structure for table `spacetype`
--

CREATE TABLE `spacetype` (
  `SpaceType_ID` int(11) NOT NULL,
  `SpaceTypeName` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spacetype`
--

INSERT INTO `spacetype` (`SpaceType_ID`, `SpaceTypeName`) VALUES
(1, 'Unit'),
(2, 'Space'),
(3, 'Apartment');

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `Transaction_ID` int(11) NOT NULL,
  `Space_ID` int(11) DEFAULT NULL,
  `Invoice_ID` int(11) DEFAULT NULL,
  `TransactionDate` date DEFAULT NULL,
  `Total_Amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`Transaction_ID`, `Space_ID`, `Invoice_ID`, `TransactionDate`, `Total_Amount`) VALUES
(58, 56, 1151, '2025-08-10', 11000.00),
(59, 55, 1153, '2025-08-10', 11000.00),
(60, 56, 1152, '2025-08-11', 11000.00),
(61, 56, 1156, '2025-08-12', 11000.00),
(62, 54, 1158, '2025-08-12', 11000.00),
(63, 55, 1154, '2025-08-13', 11000.00);

-- --------------------------------------------------------

--
-- Table structure for table `useraccounts`
--

CREATE TABLE `useraccounts` (
  `UA_ID` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `useraccounts`
--

INSERT INTO `useraccounts` (`UA_ID`, `username`, `password`, `Type`) VALUES
(1, 'rom_telents', '$2y$10$ezxUEy057HjkAVPMHoxGt.wyV2yVygiMgjonr5k9Ydkz5vraHobyG', 'Admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`Client_ID`),
  ADD UNIQUE KEY `C_username_UNIQUE` (`C_username`),
  ADD UNIQUE KEY `Client_Email_UNIQUE` (`Client_Email`);

--
-- Indexes for table `clientfeedback`
--
ALTER TABLE `clientfeedback`
  ADD PRIMARY KEY (`Feedback_ID`),
  ADD KEY `CS_ID` (`CS_ID`);

--
-- Indexes for table `clientspace`
--
ALTER TABLE `clientspace`
  ADD PRIMARY KEY (`CS_ID`),
  ADD KEY `Space_ID` (`Space_ID`),
  ADD KEY `Client_ID` (`Client_ID`);

--
-- Indexes for table `free_message`
--
ALTER TABLE `free_message`
  ADD PRIMARY KEY (`Message_ID`);

--
-- Indexes for table `handyman`
--
ALTER TABLE `handyman`
  ADD PRIMARY KEY (`Handyman_ID`);

--
-- Indexes for table `handymanjob`
--
ALTER TABLE `handymanjob`
  ADD PRIMARY KEY (`HJ_ID`),
  ADD KEY `Handyman_ID` (`Handyman_ID`),
  ADD KEY `JobType_ID` (`JobType_ID`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`Invoice_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `idx_status_date` (`Status`,`InvoiceDate`),
  ADD KEY `idx_client` (`Client_ID`),
  ADD KEY `idx_space` (`Space_ID`);

--
-- Indexes for table `invoice_chat`
--
ALTER TABLE `invoice_chat`
  ADD PRIMARY KEY (`Chat_ID`),
  ADD KEY `Invoice_ID` (`Invoice_ID`);

--
-- Indexes for table `jobtype`
--
ALTER TABLE `jobtype`
  ADD PRIMARY KEY (`JobType_ID`);

--
-- Indexes for table `maintenancerequest`
--
ALTER TABLE `maintenancerequest`
  ADD PRIMARY KEY (`Request_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `Space_ID` (`Space_ID`),
  ADD KEY `Handyman_ID` (`Handyman_ID`);

--
-- Indexes for table `maintenancerequeststatushistory`
--
ALTER TABLE `maintenancerequeststatushistory`
  ADD PRIMARY KEY (`MRSH_ID`),
  ADD KEY `Request_ID` (`Request_ID`);

--
-- Indexes for table `paymenthistory`
--
ALTER TABLE `paymenthistory`
  ADD PRIMARY KEY (`PaymentHistory_ID`),
  ADD KEY `Invoice_ID` (`Invoice_ID`);

--
-- Indexes for table `rentalrequest`
--
ALTER TABLE `rentalrequest`
  ADD PRIMARY KEY (`Request_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `Space_ID` (`Space_ID`);

--
-- Indexes for table `space`
--
ALTER TABLE `space`
  ADD PRIMARY KEY (`Space_ID`),
  ADD KEY `SpaceType_ID` (`SpaceType_ID`),
  ADD KEY `UA_ID` (`UA_ID`);

--
-- Indexes for table `spaceavailability`
--
ALTER TABLE `spaceavailability`
  ADD PRIMARY KEY (`Availability_ID`),
  ADD KEY `Space_ID` (`Space_ID`);

--
-- Indexes for table `spacetype`
--
ALTER TABLE `spacetype`
  ADD PRIMARY KEY (`SpaceType_ID`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`Transaction_ID`),
  ADD KEY `Space_ID` (`Space_ID`),
  ADD KEY `Invoice_ID` (`Invoice_ID`);

--
-- Indexes for table `useraccounts`
--
ALTER TABLE `useraccounts`
  ADD PRIMARY KEY (`UA_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `Client_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `clientfeedback`
--
ALTER TABLE `clientfeedback`
  MODIFY `Feedback_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `clientspace`
--
ALTER TABLE `clientspace`
  MODIFY `CS_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `free_message`
--
ALTER TABLE `free_message`
  MODIFY `Message_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `handyman`
--
ALTER TABLE `handyman`
  MODIFY `Handyman_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `handymanjob`
--
ALTER TABLE `handymanjob`
  MODIFY `HJ_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `Invoice_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1161;

--
-- AUTO_INCREMENT for table `invoice_chat`
--
ALTER TABLE `invoice_chat`
  MODIFY `Chat_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT for table `jobtype`
--
ALTER TABLE `jobtype`
  MODIFY `JobType_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenancerequest`
--
ALTER TABLE `maintenancerequest`
  MODIFY `Request_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `maintenancerequeststatushistory`
--
ALTER TABLE `maintenancerequeststatushistory`
  MODIFY `MRSH_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `paymenthistory`
--
ALTER TABLE `paymenthistory`
  MODIFY `PaymentHistory_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rentalrequest`
--
ALTER TABLE `rentalrequest`
  MODIFY `Request_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1158;

--
-- AUTO_INCREMENT for table `space`
--
ALTER TABLE `space`
  MODIFY `Space_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `spaceavailability`
--
ALTER TABLE `spaceavailability`
  MODIFY `Availability_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `spacetype`
--
ALTER TABLE `spacetype`
  MODIFY `SpaceType_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `Transaction_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `useraccounts`
--
ALTER TABLE `useraccounts`
  MODIFY `UA_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clientfeedback`
--
ALTER TABLE `clientfeedback`
  ADD CONSTRAINT `clientfeedback_ibfk_1` FOREIGN KEY (`CS_ID`) REFERENCES `invoice` (`Invoice_ID`);

--
-- Constraints for table `clientspace`
--
ALTER TABLE `clientspace`
  ADD CONSTRAINT `clientspace_ibfk_1` FOREIGN KEY (`Space_ID`) REFERENCES `space` (`Space_ID`),
  ADD CONSTRAINT `clientspace_ibfk_2` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`);

--
-- Constraints for table `handymanjob`
--
ALTER TABLE `handymanjob`
  ADD CONSTRAINT `fk_handymanjob_handyman` FOREIGN KEY (`Handyman_ID`) REFERENCES `handyman` (`Handyman_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_handymanjob_jobtype` FOREIGN KEY (`JobType_ID`) REFERENCES `jobtype` (`JobType_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `handymanjob_ibfk_1` FOREIGN KEY (`Handyman_ID`) REFERENCES `handyman` (`Handyman_ID`),
  ADD CONSTRAINT `handymanjob_ibfk_2` FOREIGN KEY (`JobType_ID`) REFERENCES `jobtype` (`JobType_ID`);

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `fk_invoice_space` FOREIGN KEY (`Space_ID`) REFERENCES `space` (`Space_ID`);

--
-- Constraints for table `invoice_chat`
--
ALTER TABLE `invoice_chat`
  ADD CONSTRAINT `invoice_chat_ibfk_1` FOREIGN KEY (`Invoice_ID`) REFERENCES `invoice` (`Invoice_ID`) ON DELETE CASCADE;

--
-- Constraints for table `maintenancerequest`
--
ALTER TABLE `maintenancerequest`
  ADD CONSTRAINT `maintenancerequest_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `maintenancerequest_ibfk_2` FOREIGN KEY (`Space_ID`) REFERENCES `space` (`Space_ID`),
  ADD CONSTRAINT `maintenancerequest_ibfk_3` FOREIGN KEY (`Handyman_ID`) REFERENCES `handyman` (`Handyman_ID`) ON DELETE CASCADE;

--
-- Constraints for table `maintenancerequeststatushistory`
--
ALTER TABLE `maintenancerequeststatushistory`
  ADD CONSTRAINT `maintenancerequeststatushistory_ibfk_1` FOREIGN KEY (`Request_ID`) REFERENCES `maintenancerequest` (`Request_ID`) ON DELETE CASCADE;

--
-- Constraints for table `paymenthistory`
--
ALTER TABLE `paymenthistory`
  ADD CONSTRAINT `paymenthistory_ibfk_1` FOREIGN KEY (`Invoice_ID`) REFERENCES `invoice` (`Invoice_ID`) ON DELETE CASCADE;

--
-- Constraints for table `rentalrequest`
--
ALTER TABLE `rentalrequest`
  ADD CONSTRAINT `rentalrequest_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `rentalrequest_ibfk_2` FOREIGN KEY (`Space_ID`) REFERENCES `space` (`Space_ID`);

--
-- Constraints for table `space`
--
ALTER TABLE `space`
  ADD CONSTRAINT `space_ibfk_1` FOREIGN KEY (`SpaceType_ID`) REFERENCES `spacetype` (`SpaceType_ID`),
  ADD CONSTRAINT `space_ibfk_2` FOREIGN KEY (`UA_ID`) REFERENCES `useraccounts` (`UA_ID`);

--
-- Constraints for table `spaceavailability`
--
ALTER TABLE `spaceavailability`
  ADD CONSTRAINT `spaceavailability_ibfk_1` FOREIGN KEY (`Space_ID`) REFERENCES `space` (`Space_ID`) ON DELETE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`Space_ID`) REFERENCES `space` (`Space_ID`),
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`Invoice_ID`) REFERENCES `invoice` (`Invoice_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
