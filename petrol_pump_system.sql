-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 08:29 PM
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
-- Database: `petrol_pump_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendants`
--

CREATE TABLE `attendants` (
  `attendant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL,
  `joining_date` date NOT NULL,
  `assigned_pump` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendants` (Updated with complete data)
--

INSERT INTO `attendants` (`attendant_id`, `name`, `email`, `phone`, `address`, `salary`, `joining_date`, `assigned_pump`, `status`) VALUES
(1, 'Ali Raza', 'ali.raza@gmail.com', '0311-2233445', 'House #123, Block A, Gulberg, Lahore', 30000.00, '2023-01-15', 1, 'active'),
(2, 'Usman Khan', 'usman.khan@gmail.com', '0322-3344556', 'Flat #45, Sector F-7, Islamabad', 32000.00, '2023-03-10', 2, 'active'),
(3, 'Bilal Ahmed', 'bilal.ahmed@gmail.com', '0333-4455667', 'House #78, DHA Phase 5, Karachi', 30000.00, '2023-05-20', 3, 'active'),
(4, 'Farhan Malik', 'farhan.malik@gmail.com', '0344-5566778', 'Flat #12, Gulistan-e-Jauhar, Karachi', 35000.00, '2023-07-05', 4, 'active'),
(5, 'Zubair Hassan', 'zubair.hassan@gmail.com', '0355-6677889', 'House #34, Model Town, Lahore', 30000.00, '2023-09-15', 5, 'active');

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `attendant_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','leave','half-day') NOT NULL DEFAULT 'present',
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`attendance_id`),
  KEY `attendant_id` (`attendant_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`attendant_id`) REFERENCES `attendants` (`attendant_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `customer_type` enum('regular','commercial') DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `phone`, `email`, `customer_type`) VALUES
(1, 'Ahmed Khan', '0300-7788990', 'ahmed.khan@gmail.com', 'regular'),
(2, 'Shafiq Transport Company', '0311-8899001', 'shafiq.transport@gmail.com', 'commercial'),
(3, 'Karachi Logistics', '0322-9900112', 'info@karachilogistics.com', 'commercial'),
(4, 'Tariq Mehmood', '0333-0011223', 'tariq.mehmood@hotmail.com', 'regular'),
(5, 'Allied Transport', '0344-1122334', 'info@alliedtransport.com', 'commercial');

-- --------------------------------------------------------

--
-- Table structure for table `daily_readings`
--

CREATE TABLE `daily_readings` (
  `reading_id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,
  `reading_date` date NOT NULL,
  `opening_reading` decimal(12,2) NOT NULL,
  `closing_reading` decimal(12,2) NOT NULL DEFAULT 0.00,
  `recorded_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_readings` (Updated with recorded_by data)
--

INSERT INTO `daily_readings` (`reading_id`, `pump_id`, `reading_date`, `opening_reading`, `closing_reading`, `recorded_by`) VALUES
(1, 1, '2025-05-14', 50450.00, 50500.00, 'Ali Raza'),
(2, 2, '2025-05-14', 32780.00, 32880.00, 'Usman Khan'),
(3, 3, '2025-05-14', 41230.00, 41330.00, 'Bilal Ahmed'),
(4, 4, '2025-05-14', 18760.00, 18860.00, 'Farhan Malik'),
(5, 1, '2025-05-15', 50780.00, 50800.00, 'Ali Raza');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `expense_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `expense_date` date NOT NULL,
  `recorded_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `expense_type`, `amount`, `description`, `expense_date`, `recorded_by`) VALUES
(1, 'Utilities', 15000.00, 'Electricity bill for April 2025', '2025-05-10', 1),
(2, 'Maintenance', 8500.00, 'Pump 5 repair cost', '2025-05-12', 1),
(3, 'Salaries', 157000.00, 'Staff salaries for April 2025', '2025-05-01', 1),
(4, 'Office Supplies', 3500.00, 'Stationery and printer cartridge', '2025-05-15', 1),
(5, 'Cleaning', 2000.00, 'Monthly cleaning service fee', '2025-05-18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `fuel_pumps`
--

CREATE TABLE `fuel_pumps` (
  `pump_id` int(11) NOT NULL,
  `pump_name` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_pumps`
--

INSERT INTO `fuel_pumps` (`pump_id`, `pump_name`, `product_id`, `status`) VALUES
(1, 'Pump 1 - Petrol', 1, 'active'),
(2, 'Pump 2 - Petrol', 1, 'active'),
(3, 'Pump 3 - Diesel', 2, 'active'),
(4, 'Pump 4 - Hi-Octane', 3, 'active'),
(5, 'Pump 5 - Diesel', 2, 'maintenance');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_type` enum('fuel','non-fuel') NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `stock_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'item',
  `reorder_level` decimal(12,2) NOT NULL DEFAULT 5.00,
  `supplier_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_type`, `category`, `description`, `purchase_price`, `selling_price`, `stock_quantity`, `unit`, `reorder_level`, `supplier_id`) VALUES
(1, 'Royal Petrol', 'fuel', 'Motor Gasoline', 'Regular unleaded petrol for vehicles', 240.00, 269.00, 10000.00, 'liter', 1000.00, 1),
(2, 'Royal Diesel', 'fuel', 'High Speed Diesel', 'High-speed diesel for commercial vehicles', 245.00, 275.00, 8000.00, 'liter', 800.00, 1),
(3, 'Royal Hi-Octane', 'fuel', 'Premium Gasoline', 'High octane fuel for premium vehicles', 260.00, 290.00, 5000.00, 'liter', 500.00, 1),
(4, 'Engine Oil', 'non-fuel', 'Lubricants', 'Premium motor oil for all vehicles', 1200.00, 1500.00, 200.00, 'liter', 20.00, 1),
(5, 'Air Freshener', 'non-fuel', 'Car Accessories', 'Car air freshener', 150.00, 250.00, 100.00, 'item', 10.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','credit') NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`purchase_id`, `product_id`, `supplier_id`, `quantity`, `price_per_unit`, `total_amount`, `purchase_date`, `payment_method`, `notes`, `recorded_by`) VALUES
(1, 1, 1, 5000.00, 240.00, 1200000.00, '2025-05-01', 'bank_transfer', 'Monthly petrol supply', 1),
(2, 2, 1, 4000.00, 245.00, 980000.00, '2025-05-01', 'bank_transfer', 'Monthly diesel supply', 1),
(3, 3, 1, 2000.00, 260.00, 520000.00, '2025-05-01', 'bank_transfer', 'Monthly hi-octane supply', 1),
(4, 4, 1, 50.00, 1200.00, 60000.00, '2025-05-10', 'cash', 'Engine oil stock replenishment', 1),
(5, 5, 2, 30.00, 150.00, 4500.00, '2025-05-15', 'cash', 'Air freshener stock', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `pump_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','card','credit') DEFAULT 'cash',
  `sale_date` datetime DEFAULT current_timestamp(),
  `recorded_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales` (Updated with customer_id and recorded_by as Muzamil Zaman)
--

INSERT INTO `sales` (`sale_id`, `pump_id`, `product_id`, `customer_id`, `quantity`, `rate`, `total_amount`, `payment_method`, `sale_date`, `recorded_by`) VALUES
(1, 1, 1, 1, 40.00, 269.00, 10760.00, 'cash', '2025-05-15 09:15:20', 'Muzamil Zaman'),
(2, 3, 2, 2, 200.00, 275.00, 55000.00, 'credit', '2025-05-15 10:30:45', 'Muzamil Zaman'),
(3, 4, 3, 4, 35.00, 290.00, 10150.00, 'card', '2025-05-15 14:22:18', 'Muzamil Zaman'),
(4, 2, 1, 3, 150.00, 269.00, 40350.00, 'credit', '2025-05-16 08:45:30', 'Muzamil Zaman'),
(5, NULL, 4, 1, 4.00, 1500.00, 6000.00, 'cash', '2025-05-16 09:30:00', 'Muzamil Zaman');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'Royal Fuels', 'Arif Khan', '0300-1234567', 'arif.khan@royalfuels.com.pk', 'Royal House, Khayaban-e-Iqbal, Clifton, Karachi'),
(2, 'Energy Express', 'Fahad Ahmed', '0301-9876543', 'fahad.ahmed@energyexpress.com.pk', 'Energy House, I.I. Chundrigar Road, Karachi'),
(3, 'Prime Petroleum', 'Imran Shah', '0333-5554433', 'imran.shah@primepetro.com.pk', 'Prime House, Shahrah-e-Faisal, Karachi'),
(4, 'Star Energy', 'Naveed Malik', '0321-3335577', 'naveed.malik@starenergy.com.pk', 'The Forum, G-20, Block 9, Clifton, Karachi'),
(5, 'National Fuels', 'Shahid Mehmood', '0333-1112233', 'shahid.mehmood@nationalfuels.com.pk', 'National House, Morgah, Rawalpindi');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `created_at`, `status`) VALUES
(1, 'muzamil', 'muz@pump.com', '$2y$10$Y0i0NDSoNPyrIlsC9i3URuv/3qq./wyXg7XtJjDLfpbI56BbNUWmy', 'Muzamil Zaman', '03034012525', 'admin', '2025-05-18 18:06:15', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendants`
--
ALTER TABLE `attendants`
  ADD PRIMARY KEY (`attendant_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_attendant_pump` (`assigned_pump`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `daily_readings`
--
ALTER TABLE `daily_readings`
  ADD PRIMARY KEY (`reading_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`);

--
-- Indexes for table `fuel_pumps`
--
ALTER TABLE `fuel_pumps`
  ADD PRIMARY KEY (`pump_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_product_supplier` (`supplier_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `pump_id` (`pump_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

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
-- AUTO_INCREMENT for table `attendants`
--
ALTER TABLE `attendants`
  MODIFY `attendant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `daily_readings`
--
ALTER TABLE `daily_readings`
  MODIFY `reading_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fuel_pumps`
--
ALTER TABLE `fuel_pumps`
  MODIFY `pump_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendants`
--
ALTER TABLE `attendants`
  ADD CONSTRAINT `fk_attendant_pump` FOREIGN KEY (`assigned_pump`) REFERENCES `fuel_pumps` (`pump_id`);

--
-- Constraints for table `fuel_pumps`
--
ALTER TABLE `fuel_pumps`
  ADD CONSTRAINT `fuel_pumps_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_4` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `sales_ibfk_5` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;