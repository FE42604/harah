-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 05, 2025 at 03:18 AM
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
-- Database: `chronological_database-sis-1`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_back_order_list`
--

CREATE TABLE `tbl_back_order_list` (
  `back_order_id` int(11) NOT NULL,
  `purchase_item_id` int(11) DEFAULT NULL,
  `quantity_back_ordered` int(11) DEFAULT 0,
  `backorder_expected_delivery_date` date DEFAULT NULL,
  `backorder_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_categories`
--

CREATE TABLE `tbl_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_categories`
--

INSERT INTO `tbl_categories` (`category_id`, `category_name`, `category_description`, `created_at`, `updated_at`) VALUES
(18, 'Products', 'Products', '2025-03-04 17:11:54', '2025-03-04 17:11:54');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer`
--

CREATE TABLE `tbl_customer` (
  `cust_id` int(11) NOT NULL,
  `customer_type` enum('individual','business') NOT NULL,
  `customer_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_employee`
--

CREATE TABLE `tbl_employee` (
  `employee_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `hired_date` date DEFAULT NULL,
  `address_info` varchar(255) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_employee`
--

INSERT INTO `tbl_employee` (`employee_id`, `first_name`, `middle_name`, `last_name`, `email`, `gender`, `hired_date`, `address_info`, `job_id`) VALUES
(0, 'harah', 'rubina', 'del dios', 'harah@gmail.com', 'female', '2025-03-04', 'manolo', 0),
(1, 'Mar Louis', 'Pimentel', 'Go', 'margo@gmail.com', 'male', '2025-02-28', 'ph', 6),
(8, 'clemenz', 'clemenz', 'clemenz', 'clemenz@gmail.com', 'male', '2025-03-04', 'qwqass', 1),
(9, 'francis', 'francis', 'francis', 'francis@gmail.com', 'female', '2025-03-04', 'bayanga', 2),
(12, 'cedrick', 'cedrick', 'cedrick', 'cedrick@gmail.com', 'male', '2025-03-04', 'idk', 2),
(15, 'khendal', 'khendal', 'khendal', 'khendal@gmail.com', 'male', '2025-03-05', '12312', 2),
(16, 'johnbert', 'johnbert', 'johnbert', 'johnbert@gmail.com', 'male', '2025-03-05', 'aaa', 6),
(17, 'love', 'love', 'love', 'love@gmail.com', 'female', '2025-03-05', '1352g', 5);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ingredient_usage`
--

CREATE TABLE `tbl_ingredient_usage` (
  `usage_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `raw_ingredient_id` int(11) DEFAULT NULL,
  `usage_quantity_used` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_jobs`
--

CREATE TABLE `tbl_jobs` (
  `job_id` int(11) NOT NULL,
  `job_name` varchar(255) NOT NULL,
  `job_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_jobs`
--

INSERT INTO `tbl_jobs` (`job_id`, `job_name`, `job_created_at`) VALUES
(0, 'Main Admin', '2025-03-04 11:22:57'),
(1, 'Cashier', '2025-03-04 11:00:35'),
(2, 'Stock Clerk', '2025-03-04 11:23:51'),
(3, 'Gardner', '2025-03-04 11:24:11'),
(4, 'Kitchen Staff', '2025-03-04 11:24:30'),
(5, 'Maintenance', '2025-03-04 11:25:01'),
(6, 'Admin', '2025-03-04 11:25:18');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payments`
--

CREATE TABLE `tbl_payments` (
  `payment_id` int(11) NOT NULL,
  `pos_order_id` int(11) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('cash','credit','debit','gcash','online') NOT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pos_orders`
--

CREATE TABLE `tbl_pos_orders` (
  `pos_order_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `cust_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_total_amount` decimal(10,2) DEFAULT NULL,
  `order_status` enum('pending','completed','cancelled') NOT NULL,
  `order_type` enum('qr_code','counter') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pos_order_items`
--

CREATE TABLE `tbl_pos_order_items` (
  `pos_order_item_id` int(11) NOT NULL,
  `pos_order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity_sold` int(11) NOT NULL,
  `item_price` decimal(10,2) DEFAULT NULL,
  `item_total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_products`
--

CREATE TABLE `tbl_products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_selling_price` decimal(10,2) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `product_quantity` int(11) DEFAULT 0,
  `product_restock_qty` int(11) DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `product_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_items`
--

CREATE TABLE `tbl_purchase_items` (
  `purchase_item_id` int(11) NOT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `raw_ingredient_id` int(11) DEFAULT NULL,
  `quantity_ordered` int(11) NOT NULL DEFAULT 0,
  `quantity_received` int(11) DEFAULT 0,
  `back_ordered_quantity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_order_list`
--

CREATE TABLE `tbl_purchase_order_list` (
  `purchase_order_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('ordered','received','partially_received','back_ordered') NOT NULL,
  `purchase_expected_delivery_date` date DEFAULT NULL,
  `purchase_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_raw_ingredients`
--

CREATE TABLE `tbl_raw_ingredients` (
  `raw_ingredient_id` int(11) NOT NULL,
  `raw_name` varchar(255) NOT NULL,
  `raw_description` text DEFAULT NULL,
  `raw_unit_of_measure` varchar(50) DEFAULT NULL,
  `raw_stock_quantity` int(11) DEFAULT 0,
  `raw_cost_per_unit` decimal(10,2) DEFAULT NULL,
  `raw_reorder_level` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `raw_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL,
  `raw_stock_in` int(11) DEFAULT 0,
  `raw_stock_out` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_receipts`
--

CREATE TABLE `tbl_receipts` (
  `receipt_id` int(11) NOT NULL,
  `pos_order_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `receipt_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_total_amount` decimal(10,2) DEFAULT NULL,
  `receipt_status` enum('paid','unpaid','refunded') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_receiving_list`
--

CREATE TABLE `tbl_receiving_list` (
  `receiving_id` int(11) NOT NULL,
  `purchase_item_id` int(11) DEFAULT NULL,
  `quantity_received` int(11) DEFAULT 0,
  `receiving_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `receiving_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_return_list`
--

CREATE TABLE `tbl_return_list` (
  `return_id` int(11) NOT NULL,
  `purchase_item_id` int(11) DEFAULT NULL,
  `quantity_returned` int(11) DEFAULT 0,
  `return_reason` text DEFAULT NULL,
  `return_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = pending, 1 = completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sales`
--

CREATE TABLE `tbl_sales` (
  `sale_id` int(11) NOT NULL,
  `receipt_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_sale_amount` decimal(10,2) DEFAULT NULL,
  `sale_status` enum('completed','cancelled','refunded') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_suppliers`
--

CREATE TABLE `tbl_suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transaction_log`
--

CREATE TABLE `tbl_transaction_log` (
  `transaction_log_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `transaction_type` enum('purchase','refund','adjustment') NOT NULL,
  `transaction_amount` decimal(10,2) DEFAULT NULL,
  `transaction_description` text DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transaction_log`
--

INSERT INTO `tbl_transaction_log` (`transaction_log_id`, `payment_id`, `transaction_type`, `transaction_amount`, `transaction_description`, `transaction_date`) VALUES
(1, NULL, '', NULL, 'Created new employee: clemenz clemenz as Cashier', '2025-03-04 12:24:38'),
(2, NULL, '', NULL, 'Created user account for clemenz clemenz (Cashier)', '2025-03-04 12:26:40'),
(3, NULL, '', NULL, 'Created new employee: francis francis as Stock Clerk', '2025-03-04 12:33:25'),
(4, NULL, '', NULL, 'Created user account for francis francis (Stock Clerk)', '2025-03-04 12:33:40'),
(5, NULL, '', NULL, 'Created new employee: cedrick cedrick as Stock Clerk', '2025-03-04 15:30:49'),
(9, NULL, '', NULL, 'Created new employee: khendal khendal as Stock Clerk (by harah del dios)', '2025-03-04 16:17:20'),
(10, NULL, '', NULL, 'Created new user account for khendal khendal as Stock Clerk (by Mar Louis Go)', '2025-03-04 16:28:27'),
(11, NULL, '', NULL, 'Created new employee: johnbert johnbert as Admin (by harah del dios)', '2025-03-04 16:36:23'),
(12, NULL, '', NULL, 'Created new user account for johnbert johnbert as Admin (by harah del dios)', '2025-03-04 16:37:35'),
(13, NULL, '', NULL, 'Created new employee: love love as Maintenance (by Mar Louis Go)', '2025-03-04 16:38:43'),
(14, NULL, '', NULL, 'Created new user account for cedrick cedrick as Stock Clerk (by Mar Louis Go)', '2025-03-04 16:53:00'),
(15, NULL, '', NULL, 'Created new category: Products (by harah del dios)', '2025-03-04 17:11:54'),
(16, NULL, '', NULL, 'User khendal khendal was deactivated', '2025-03-04 17:52:16'),
(17, NULL, '', NULL, 'User khendal khendal was activated', '2025-03-04 17:52:18'),
(18, NULL, '', NULL, 'User khendal khendal was deactivated', '2025-03-04 17:52:19'),
(19, NULL, '', NULL, 'User khendal khendal was activated', '2025-03-04 17:52:20'),
(20, NULL, '', NULL, 'User khendal khendal was deactivated', '2025-03-04 17:52:20'),
(21, NULL, '', NULL, 'User khendal khendal was activated', '2025-03-04 17:52:20'),
(22, NULL, '', NULL, 'User khendal khendal was deactivated', '2025-03-04 17:52:21'),
(23, NULL, '', NULL, 'User khendal khendal was activated', '2025-03-04 17:54:51'),
(24, NULL, '', NULL, 'User khendal khendal was deactivated', '2025-03-04 17:54:52'),
(25, NULL, '', NULL, 'User khendal khendal was activated', '2025-03-04 17:54:53'),
(26, NULL, '', NULL, 'User khendal khendal was deactivated', '2025-03-04 17:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`user_id`, `employee_id`, `user_name`, `user_password`, `user_created`) VALUES
(1, 1, 'margo', '7110eda4d09e062aa5e4a390b0a572ac0d2c0220', '2025-03-04 11:29:04'),
(6, 8, 'clemenz', '7110eda4d09e062aa5e4a390b0a572ac0d2c0220', '2025-03-04 12:26:40'),
(7, 9, 'francis', '7110eda4d09e062aa5e4a390b0a572ac0d2c0220', '2025-03-04 12:33:40'),
(8, 0, 'harah', '7110eda4d09e062aa5e4a390b0a572ac0d2c0220', '2025-03-04 14:29:57'),
(12, 15, 'khendal', 'INACTIVE_7110eda4d09e062aa5e4a390b0a572ac0d2c0220', '2025-03-04 16:28:27'),
(14, 16, 'johnbert', '7110eda4d09e062aa5e4a390b0a572ac0d2c0220', '2025-03-04 16:37:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_back_order_list`
--
ALTER TABLE `tbl_back_order_list`
  ADD PRIMARY KEY (`back_order_id`),
  ADD KEY `purchase_item_id` (`purchase_item_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_categories`
--
ALTER TABLE `tbl_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  ADD PRIMARY KEY (`cust_id`);

--
-- Indexes for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `tbl_ingredient_usage`
--
ALTER TABLE `tbl_ingredient_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `raw_ingredient_id` (`raw_ingredient_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_jobs`
--
ALTER TABLE `tbl_jobs`
  ADD PRIMARY KEY (`job_id`);

--
-- Indexes for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `pos_order_id` (`pos_order_id`);

--
-- Indexes for table `tbl_pos_orders`
--
ALTER TABLE `tbl_pos_orders`
  ADD PRIMARY KEY (`pos_order_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `cust_id` (`cust_id`);

--
-- Indexes for table `tbl_pos_order_items`
--
ALTER TABLE `tbl_pos_order_items`
  ADD PRIMARY KEY (`pos_order_item_id`),
  ADD KEY `pos_order_id` (`pos_order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_purchase_items`
--
ALTER TABLE `tbl_purchase_items`
  ADD PRIMARY KEY (`purchase_item_id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `raw_ingredient_id` (`raw_ingredient_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_purchase_order_list`
--
ALTER TABLE `tbl_purchase_order_list`
  ADD PRIMARY KEY (`purchase_order_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_raw_ingredients`
--
ALTER TABLE `tbl_raw_ingredients`
  ADD PRIMARY KEY (`raw_ingredient_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_receipts`
--
ALTER TABLE `tbl_receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `pos_order_id` (`pos_order_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_receiving_list`
--
ALTER TABLE `tbl_receiving_list`
  ADD PRIMARY KEY (`receiving_id`),
  ADD KEY `purchase_item_id` (`purchase_item_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_return_list`
--
ALTER TABLE `tbl_return_list`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `purchase_item_id` (`purchase_item_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_sales`
--
ALTER TABLE `tbl_sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `receipt_id` (`receipt_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_suppliers`
--
ALTER TABLE `tbl_suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `tbl_transaction_log`
--
ALTER TABLE `tbl_transaction_log`
  ADD PRIMARY KEY (`transaction_log_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_back_order_list`
--
ALTER TABLE `tbl_back_order_list`
  MODIFY `back_order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_categories`
--
ALTER TABLE `tbl_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  MODIFY `cust_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tbl_ingredient_usage`
--
ALTER TABLE `tbl_ingredient_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_jobs`
--
ALTER TABLE `tbl_jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_pos_orders`
--
ALTER TABLE `tbl_pos_orders`
  MODIFY `pos_order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_pos_order_items`
--
ALTER TABLE `tbl_pos_order_items`
  MODIFY `pos_order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_products`
--
ALTER TABLE `tbl_products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_purchase_items`
--
ALTER TABLE `tbl_purchase_items`
  MODIFY `purchase_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_purchase_order_list`
--
ALTER TABLE `tbl_purchase_order_list`
  MODIFY `purchase_order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_raw_ingredients`
--
ALTER TABLE `tbl_raw_ingredients`
  MODIFY `raw_ingredient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_receipts`
--
ALTER TABLE `tbl_receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_receiving_list`
--
ALTER TABLE `tbl_receiving_list`
  MODIFY `receiving_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_return_list`
--
ALTER TABLE `tbl_return_list`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sales`
--
ALTER TABLE `tbl_sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_suppliers`
--
ALTER TABLE `tbl_suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_transaction_log`
--
ALTER TABLE `tbl_transaction_log`
  MODIFY `transaction_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_back_order_list`
--
ALTER TABLE `tbl_back_order_list`
  ADD CONSTRAINT `fk_back_order_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`),
  ADD CONSTRAINT `fk_back_order_purchase_item` FOREIGN KEY (`purchase_item_id`) REFERENCES `tbl_purchase_items` (`purchase_item_id`);

--
-- Constraints for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  ADD CONSTRAINT `fk_employee_job` FOREIGN KEY (`job_id`) REFERENCES `tbl_jobs` (`job_id`);

--
-- Constraints for table `tbl_ingredient_usage`
--
ALTER TABLE `tbl_ingredient_usage`
  ADD CONSTRAINT `fk_ingredient_usage_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`),
  ADD CONSTRAINT `fk_ingredient_usage_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`),
  ADD CONSTRAINT `fk_ingredient_usage_raw_ingredient` FOREIGN KEY (`raw_ingredient_id`) REFERENCES `tbl_raw_ingredients` (`raw_ingredient_id`);

--
-- Constraints for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  ADD CONSTRAINT `fk_payment_pos_order` FOREIGN KEY (`pos_order_id`) REFERENCES `tbl_pos_orders` (`pos_order_id`);

--
-- Constraints for table `tbl_pos_orders`
--
ALTER TABLE `tbl_pos_orders`
  ADD CONSTRAINT `fk_pos_order_customer` FOREIGN KEY (`cust_id`) REFERENCES `tbl_customer` (`cust_id`),
  ADD CONSTRAINT `fk_pos_order_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`);

--
-- Constraints for table `tbl_pos_order_items`
--
ALTER TABLE `tbl_pos_order_items`
  ADD CONSTRAINT `fk_pos_order_item_order` FOREIGN KEY (`pos_order_id`) REFERENCES `tbl_pos_orders` (`pos_order_id`),
  ADD CONSTRAINT `fk_pos_order_item_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`);

--
-- Constraints for table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`category_id`),
  ADD CONSTRAINT `fk_product_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`);

--
-- Constraints for table `tbl_purchase_items`
--
ALTER TABLE `tbl_purchase_items`
  ADD CONSTRAINT `fk_purchase_item_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`),
  ADD CONSTRAINT `fk_purchase_item_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `tbl_purchase_order_list` (`purchase_order_id`),
  ADD CONSTRAINT `fk_purchase_item_raw_ingredient` FOREIGN KEY (`raw_ingredient_id`) REFERENCES `tbl_raw_ingredients` (`raw_ingredient_id`);

--
-- Constraints for table `tbl_purchase_order_list`
--
ALTER TABLE `tbl_purchase_order_list`
  ADD CONSTRAINT `fk_purchase_order_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`),
  ADD CONSTRAINT `fk_purchase_order_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `tbl_suppliers` (`supplier_id`);

--
-- Constraints for table `tbl_raw_ingredients`
--
ALTER TABLE `tbl_raw_ingredients`
  ADD CONSTRAINT `fk_raw_ingredient_category` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`category_id`),
  ADD CONSTRAINT `fk_raw_ingredient_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`),
  ADD CONSTRAINT `fk_raw_ingredient_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `tbl_suppliers` (`supplier_id`);

--
-- Constraints for table `tbl_receipts`
--
ALTER TABLE `tbl_receipts`
  ADD CONSTRAINT `fk_receipt_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`),
  ADD CONSTRAINT `fk_receipt_pos_order` FOREIGN KEY (`pos_order_id`) REFERENCES `tbl_pos_orders` (`pos_order_id`);

--
-- Constraints for table `tbl_receiving_list`
--
ALTER TABLE `tbl_receiving_list`
  ADD CONSTRAINT `fk_receiving_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`),
  ADD CONSTRAINT `fk_receiving_purchase_item` FOREIGN KEY (`purchase_item_id`) REFERENCES `tbl_purchase_items` (`purchase_item_id`);

--
-- Constraints for table `tbl_return_list`
--
ALTER TABLE `tbl_return_list`
  ADD CONSTRAINT `fk_return_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_return_purchase_item` FOREIGN KEY (`purchase_item_id`) REFERENCES `tbl_purchase_items` (`purchase_item_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sales`
--
ALTER TABLE `tbl_sales`
  ADD CONSTRAINT `fk_sale_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`),
  ADD CONSTRAINT `fk_sale_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `tbl_receipts` (`receipt_id`);

--
-- Constraints for table `tbl_transaction_log`
--
ALTER TABLE `tbl_transaction_log`
  ADD CONSTRAINT `fk_transaction_log_payment` FOREIGN KEY (`payment_id`) REFERENCES `tbl_payments` (`payment_id`);

--
-- Constraints for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD CONSTRAINT `fk_user_employee` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`employee_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
