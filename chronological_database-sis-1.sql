-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 06, 2025 at 03:22 AM
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
(18, 'Products', 'Products', '2025-03-04 17:11:54', '2025-03-04 17:11:54'),
(19, 'RI_Meat', 'Meat', '2025-03-05 03:20:44', '2025-03-05 03:20:44'),
(20, 'RI_Vegetable', 'vegetable', '2025-03-05 03:27:31', '2025-03-05 03:27:31');

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
(17, 'love', 'love', 'love', 'love@gmail.com', 'female', '2025-03-05', '1352g', 5),
(18, 'kittim', 'kittim', 'kittim', 'kittim@gmail.com', 'male', '2025-03-05', '123', 1),
(19, 'ignalig', 'ignalig', 'ignalig', 'ignalig@gmail.com', 'male', '2025-03-06', 'hello world', 5);

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

--
-- Dumping data for table `tbl_products`
--

INSERT INTO `tbl_products` (`product_id`, `product_name`, `product_selling_price`, `product_image`, `product_quantity`, `product_restock_qty`, `category_id`, `product_created_at`, `employee_id`) VALUES
(6, 'Chicken Ala KIng', 79.00, '67c8ff1ab670a.jpg', 50, 6, 18, '2025-03-06 01:49:14', 9);

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

--
-- Dumping data for table `tbl_raw_ingredients`
--

INSERT INTO `tbl_raw_ingredients` (`raw_ingredient_id`, `raw_name`, `raw_description`, `raw_unit_of_measure`, `raw_stock_quantity`, `raw_cost_per_unit`, `raw_reorder_level`, `supplier_id`, `category_id`, `raw_created_at`, `employee_id`, `raw_stock_in`, `raw_stock_out`) VALUES
(3, 'Sausages', 'juicy sausages', 'kg', 0, 20.00, 12, NULL, 19, '2025-03-05 03:33:13', NULL, 0, 0),
(4, 'Carrots', 'orangeee', 'kg', 20, 3.00, 15, NULL, 20, '2025-03-06 01:31:35', NULL, 0, 0),
(5, 'Bacon', 'bacon', 'kg', 80, 15.00, 15, NULL, 19, '2025-03-06 01:32:23', NULL, 0, 0),
(6, 'Cabbage', 'cabbage', 'kg', 10, 14.00, 14, NULL, 20, '2025-03-06 01:33:16', NULL, 0, 0);

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
(26, NULL, '', NULL, 'User khendal khendal was deactivated', '2025-03-04 17:55:41'),
(27, NULL, '', NULL, 'Created new category: RI_Meat', '2025-03-05 03:20:44'),
(28, NULL, '', NULL, 'Created new raw ingredient category: Vegetable', '2025-03-05 03:27:31'),
(29, NULL, '', NULL, 'Added new raw ingredient: Sausages', '2025-03-05 03:33:13'),
(30, NULL, '', NULL, 'Stock out for Sausages: -35', '2025-03-05 03:40:18'),
(31, NULL, '', NULL, 'Stock in for Sausages: +5', '2025-03-05 03:40:27'),
(32, NULL, '', NULL, 'Stock out for Sausages: -9', '2025-03-05 03:41:30'),
(33, NULL, '', NULL, 'Stock in for Sausages: +1', '2025-03-05 03:44:10'),
(34, NULL, '', NULL, 'Stock in for Sausages: +1', '2025-03-05 03:44:13'),
(35, NULL, '', NULL, 'Stock out for Sausages: -1', '2025-03-05 03:46:11'),
(36, NULL, '', NULL, 'Stock in for Sausages: +13', '2025-03-05 03:46:14'),
(37, NULL, '', NULL, 'Stock out for Sausages: -1', '2025-03-05 03:46:19'),
(38, NULL, '', NULL, 'Stock out for Sausages: -2', '2025-03-05 03:46:21'),
(39, NULL, '', NULL, 'Stock out for Sausages: -1', '2025-03-05 03:46:24'),
(40, NULL, '', NULL, 'Stock out for Sausages: -1', '2025-03-05 03:46:26'),
(41, NULL, '', NULL, 'Stock out for Sausages: -1', '2025-03-05 03:46:28'),
(42, NULL, '', NULL, 'Stock out for Sausages: -1', '2025-03-05 03:46:30'),
(43, NULL, '', NULL, 'Stock in for Sausages: +1', '2025-03-05 03:46:36'),
(44, NULL, '', NULL, 'Stock out for Sausages: -5', '2025-03-05 03:48:40'),
(45, NULL, '', NULL, 'Stock out for Sausages: -2', '2025-03-05 03:48:50'),
(46, NULL, '', NULL, 'Stock out for Sausages: -12', '2025-03-05 03:49:51'),
(47, NULL, '', NULL, 'Stock in for Sausages: +11', '2025-03-05 03:50:56'),
(48, NULL, '', NULL, 'Stock in for Sausages: +5', '2025-03-05 03:51:05'),
(49, NULL, '', NULL, 'Stock in for Sausages: +40', '2025-03-05 03:51:18'),
(50, NULL, 'adjustment', NULL, 'User francis francis logged in successfully', '2025-03-05 05:52:51'),
(51, NULL, 'adjustment', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 05:52:59'),
(52, NULL, 'purchase', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 05:54:28'),
(53, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 05:54:39'),
(54, NULL, '', NULL, 'User harah del dios logged in successfully', '2025-03-05 05:55:07'),
(55, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 05:56:06'),
(56, NULL, '', NULL, 'Added new product: Milk Tea By: ', '2025-03-05 06:05:24'),
(57, NULL, '', NULL, 'Added new product: Milk Tea 2 By: ', '2025-03-05 06:06:23'),
(58, NULL, '', NULL, 'Stock out for Milk Tea: 5 units By: ', '2025-03-05 06:08:54'),
(59, NULL, '', NULL, 'Stock out for Milk Tea: 5 units By: ', '2025-03-05 06:09:11'),
(60, NULL, '', NULL, 'Stock out for Milk Tea: 5 units By: ', '2025-03-05 06:09:12'),
(61, NULL, '', NULL, 'Stock out for Milk Tea: 5 units By: ', '2025-03-05 06:09:13'),
(62, NULL, '', NULL, 'Stock out for Milk Tea 2: 1 units By: ', '2025-03-05 06:09:51'),
(63, NULL, 'adjustment', NULL, 'Stock out for Milk Tea 2: 1 units By: ', '2025-03-05 06:11:09'),
(64, NULL, 'adjustment', NULL, 'Stock out for Milk Tea 2: 1 units By: francis francis', '2025-03-05 06:12:04'),
(65, NULL, 'adjustment', NULL, 'Stock out for Milk Tea 2: 1 units By: francis francis', '2025-03-05 06:12:05'),
(66, NULL, 'adjustment', NULL, 'Stock out for Milk Tea 2: 1 units By: francis francis', '2025-03-05 06:12:06'),
(67, NULL, 'adjustment', NULL, 'Stock out for Milk Tea 2: 1 units By: francis francis', '2025-03-05 06:12:07'),
(68, NULL, 'adjustment', NULL, 'Stock in for Milk Tea: 1 units By: francis francis', '2025-03-05 06:12:47'),
(69, NULL, 'adjustment', NULL, 'Stock out for Milk Tea: 1 units By: francis francis', '2025-03-05 06:12:59'),
(70, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 06:14:13'),
(71, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 06:21:45'),
(72, NULL, 'adjustment', NULL, 'Stock in for Milk Tea: 5 units By: Mar Louis Go', '2025-03-05 06:22:47'),
(73, NULL, 'adjustment', NULL, 'Stock in for Milk Tea: 5 units By: Mar Louis Go', '2025-03-05 06:22:54'),
(74, NULL, '', NULL, 'User harah del dios logged in successfully', '2025-03-05 06:24:59'),
(75, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 06:25:10'),
(76, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 06:26:27'),
(77, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 06:29:12'),
(78, NULL, '', NULL, 'Created new employee: kittim kittim as Cashier (by Mar Louis Go)', '2025-03-05 06:29:43'),
(79, NULL, '', NULL, 'Stock in for Milk Tea: 1 units By: Mar Louis Go', '2025-03-05 06:31:49'),
(80, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 06:34:54'),
(81, NULL, '', NULL, 'Stock in for Sausages: +1 By: ', '2025-03-05 06:35:06'),
(82, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 06:35:11'),
(83, NULL, '', NULL, 'Stock in for Milk Tea: 1 units By: Mar Louis Go', '2025-03-05 06:35:40'),
(84, NULL, '', NULL, 'Stock out for Milk Tea: 1 units By: Mar Louis Go', '2025-03-05 06:40:34'),
(85, NULL, '', NULL, 'Stock in for Milk Tea: 1 units (by Mar Louis Go)', '2025-03-05 06:44:42'),
(86, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 06:44:53'),
(87, NULL, '', NULL, 'User clemenz clemenz logged in successfully', '2025-03-05 06:45:00'),
(88, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 06:45:09'),
(89, NULL, '', NULL, 'Stock out for Milk Tea: 1 units (by francis francis)', '2025-03-05 06:45:15'),
(90, NULL, '', NULL, 'Stock in for Sausages: +1 By: ', '2025-03-05 06:45:20'),
(91, NULL, '', NULL, 'Stock out for Sausages: -2 By: ', '2025-03-05 06:45:22'),
(92, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 06:45:34'),
(93, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 06:46:30'),
(94, NULL, '', NULL, 'Stock out for Sausages: -1 (by francis francis)', '2025-03-05 06:47:43'),
(95, NULL, '', NULL, 'Stock in for Sausages: +1 (by francis francis)', '2025-03-05 06:47:45'),
(96, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 06:47:53'),
(97, NULL, '', NULL, 'User harah del dios logged in successfully', '2025-03-05 06:48:10'),
(98, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 07:01:42'),
(99, NULL, '', NULL, 'Stock out for Sausages: -56 (by francis francis)', '2025-03-05 11:39:17'),
(100, NULL, '', NULL, 'Stock in for Sausages: +5 (by francis francis)', '2025-03-05 11:40:15'),
(101, NULL, '', NULL, 'Stock out for Sausages: --1 (by francis francis)', '2025-03-05 11:40:19'),
(102, NULL, '', NULL, 'Stock out for Sausages: --100 (by francis francis)', '2025-03-05 11:40:31'),
(103, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-05 11:42:21'),
(104, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 13:35:02'),
(105, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-05 13:37:01'),
(106, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-06 01:28:00'),
(107, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-06 01:28:42'),
(108, NULL, '', NULL, 'Stock out for Sausages: -105 (by francis francis)', '2025-03-06 01:29:08'),
(109, NULL, '', NULL, 'Stock out for Sausages: -1 (by francis francis)', '2025-03-06 01:29:15'),
(110, NULL, '', NULL, 'Added new raw ingredient: Carrots (by francis francis)', '2025-03-06 01:31:35'),
(111, NULL, '', NULL, 'Stock out for Carrots: -15 (by francis francis)', '2025-03-06 01:31:43'),
(112, NULL, '', NULL, 'Added new raw ingredient: Bacon (by francis francis)', '2025-03-06 01:32:23'),
(113, NULL, '', NULL, 'Stock out for Carrots: -60 (by francis francis)', '2025-03-06 01:32:36'),
(114, NULL, '', NULL, 'Stock out for Carrots: -5 (by francis francis)', '2025-03-06 01:32:43'),
(115, NULL, '', NULL, 'Added new raw ingredient: Cabbage (by francis francis)', '2025-03-06 01:33:16'),
(116, NULL, '', NULL, 'Stock out for Cabbage: -25 (by francis francis)', '2025-03-06 01:33:21'),
(117, NULL, '', NULL, 'Stock out for Cabbage: -15 (by francis francis)', '2025-03-06 01:33:24'),
(118, NULL, '', NULL, 'Added new product: Chicken Ala KIng By: ', '2025-03-06 01:49:14'),
(119, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-06 01:55:10'),
(120, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-06 01:56:22'),
(121, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-06 01:57:20'),
(122, NULL, '', NULL, 'User clemenz clemenz logged in successfully', '2025-03-06 02:00:11'),
(123, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-06 02:01:03'),
(124, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-06 02:05:19'),
(125, NULL, '', NULL, 'Created new employee: ignalig ignalig as Maintenance (by Mar Louis Go)', '2025-03-06 02:07:02'),
(126, NULL, '', NULL, 'User harah del dios logged in successfully', '2025-03-06 02:07:23'),
(127, NULL, '', NULL, 'User Mar Louis Go logged in successfully', '2025-03-06 02:07:38'),
(128, NULL, '', NULL, 'User khendal khendal was activated', '2025-03-06 02:08:01'),
(129, NULL, '', NULL, 'User khendal khendal was deactivated', '2025-03-06 02:08:05'),
(130, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-06 02:09:28'),
(131, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-06 02:09:28'),
(132, NULL, '', NULL, 'User francis francis logged in successfully', '2025-03-06 02:10:18');

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
(7, 9, 'francis', '7c4a8d09ca3762af61e59520943dc26494f8941b', '2025-03-04 12:33:40'),
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
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  MODIFY `cust_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `raw_ingredient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `transaction_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

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
