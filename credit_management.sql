-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 15, 2026 at 12:58 PM
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
-- Database: `credit_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$I7ml4WYmvghw2U9EzOXp7.vFMdPnrrsJn12vdT4no8tSC8KPqqbhC', '2026-03-15 11:43:13');

-- --------------------------------------------------------

--
-- Stand-in structure for view `aging_report_view`
-- (See below for the actual view)
--
CREATE TABLE `aging_report_view` (
`customer_id` int(11)
,`full_name` varchar(100)
,`transaction_id` int(11)
,`total_amount` decimal(10,2)
,`due_date` date
,`days_overdue` int(7)
,`aging_bucket` varchar(10)
,`status` enum('unpaid','partially_paid','paid')
);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `action`, `description`, `timestamp`) VALUES
(1, 'LOGIN', 'Admin admin logged in', '2026-03-15 11:47:59'),
(2, 'CUSTOMER_CREATED', 'New customer created: asdasda', '2026-03-15 11:49:41'),
(3, 'TRANSACTION_CREATED', 'Credit transaction created for customer ID: 1, Amount: 2000', '2026-03-15 11:50:21'),
(4, 'PAYMENT_RECEIVED', 'Payment received: 2000 for transaction ID: 1', '2026-03-15 11:51:08');

-- --------------------------------------------------------

--
-- Table structure for table `credit_transactions`
--

CREATE TABLE `credit_transactions` (
  `transaction_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `item_description` text NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('unpaid','partially_paid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credit_transactions`
--

INSERT INTO `credit_transactions` (`transaction_id`, `customer_id`, `item_description`, `quantity`, `unit_price`, `total_amount`, `transaction_date`, `due_date`, `status`, `created_at`) VALUES
(1, 1, 'cqwecwqecwq', 1, 2000.00, 2000.00, '2026-03-15', '2026-04-14', 'paid', '2026-03-15 11:50:21');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `payment_terms` int(11) DEFAULT 30,
  `risk_classification` enum('low','medium','high') DEFAULT 'medium',
  `current_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `full_name`, `phone_number`, `address`, `credit_limit`, `payment_terms`, `risk_classification`, `current_balance`, `created_at`) VALUES
(1, 'asdasda', 'asdsda', 'asdasd', 2323.00, 30, 'medium', 0.00, '2026-03-15 11:49:41');

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_balance_view`
-- (See below for the actual view)
--
CREATE TABLE `customer_balance_view` (
`customer_id` int(11)
,`full_name` varchar(100)
,`phone_number` varchar(20)
,`credit_limit` decimal(10,2)
,`current_balance` decimal(10,2)
,`payment_terms` int(11)
,`risk_classification` enum('low','medium','high')
,`total_transactions` bigint(21)
,`outstanding_amount` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','check','mobile_money') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `transaction_id`, `customer_id`, `amount_paid`, `payment_date`, `payment_method`, `notes`, `created_at`) VALUES
(1, 1, 1, 2000.00, '2026-03-15', 'cash', '', '2026-03-15 11:51:08');

-- --------------------------------------------------------

--
-- Stand-in structure for view `payment_summary_view`
-- (See below for the actual view)
--
CREATE TABLE `payment_summary_view` (
`payment_date` date
,`payment_count` bigint(21)
,`total_collected` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure for view `aging_report_view`
--
DROP TABLE IF EXISTS `aging_report_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `aging_report_view`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`full_name` AS `full_name`, `ct`.`transaction_id` AS `transaction_id`, `ct`.`total_amount` AS `total_amount`, `ct`.`due_date` AS `due_date`, to_days(curdate()) - to_days(`ct`.`due_date`) AS `days_overdue`, CASE WHEN to_days(curdate()) - to_days(`ct`.`due_date`) <= 0 THEN 'Current' WHEN to_days(curdate()) - to_days(`ct`.`due_date`) <= 30 THEN '0-30 Days' WHEN to_days(curdate()) - to_days(`ct`.`due_date`) <= 60 THEN '31-60 Days' WHEN to_days(curdate()) - to_days(`ct`.`due_date`) <= 90 THEN '61-90 Days' ELSE '90+ Days' END AS `aging_bucket`, `ct`.`status` AS `status` FROM (`customers` `c` join `credit_transactions` `ct` on(`c`.`customer_id` = `ct`.`customer_id`)) WHERE `ct`.`status` <> 'paid' ;

-- --------------------------------------------------------

--
-- Structure for view `customer_balance_view`
--
DROP TABLE IF EXISTS `customer_balance_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `customer_balance_view`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`full_name` AS `full_name`, `c`.`phone_number` AS `phone_number`, `c`.`credit_limit` AS `credit_limit`, `c`.`current_balance` AS `current_balance`, `c`.`payment_terms` AS `payment_terms`, `c`.`risk_classification` AS `risk_classification`, count(`ct`.`transaction_id`) AS `total_transactions`, sum(case when `ct`.`status` <> 'paid' then `ct`.`total_amount` else 0 end) AS `outstanding_amount` FROM (`customers` `c` left join `credit_transactions` `ct` on(`c`.`customer_id` = `ct`.`customer_id`)) GROUP BY `c`.`customer_id` ;

-- --------------------------------------------------------

--
-- Structure for view `payment_summary_view`
--
DROP TABLE IF EXISTS `payment_summary_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_summary_view`  AS SELECT cast(`payments`.`payment_date` as date) AS `payment_date`, count(`payments`.`payment_id`) AS `payment_count`, sum(`payments`.`amount_paid`) AS `total_collected` FROM `payments` GROUP BY cast(`payments`.`payment_date` as date) ORDER BY cast(`payments`.`payment_date` as date) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `credit_transactions`
--
ALTER TABLE `credit_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `idx_customer_name` (`full_name`),
  ADD KEY `idx_phone` (`phone_number`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_payment_transaction` (`transaction_id`),
  ADD KEY `idx_payment_customer` (`customer_id`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `credit_transactions`
--
ALTER TABLE `credit_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `credit_transactions`
--
ALTER TABLE `credit_transactions`
  ADD CONSTRAINT `credit_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `credit_transactions` (`transaction_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
