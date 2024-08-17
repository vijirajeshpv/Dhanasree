-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2024 at 03:31 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fin`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_borrower`
--

CREATE TABLE `tbl_borrower` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `nid` bigint(30) NOT NULL,
  `rejected` int(11) NOT NULL DEFAULT 0,
  `gender` varchar(20) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `dob` date NOT NULL,
  `address` varchar(255) NOT NULL,
  `working_status` varchar(50) NOT NULL,
  `image` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_borrower`
--

INSERT INTO `tbl_borrower` (`id`, `name`, `nid`, `rejected`, `gender`, `mobile`, `email`, `dob`, `address`, `working_status`, `image`) VALUES
(7, 'tipu', 199661456, 0, 'Male', '01834564564', 'raihan@mail.com', '1997-11-27', 'Uttara, Dhaka', 'Student', 'admin/uploads/a72f84b339.jpg'),
(8, 'samdani', 19996456456, 0, 'Male', '0163456455', 'kamalkarim@mail.com', '1995-06-14', 'Mirpur, Dhaka', 'Owner', 'admin/uploads/4afa45893c.jpg'),
(9, 'Md Raihan Uddin', 151030582, 0, 'Male', '01834564564', 'kamalkarim@mail.com', '2018-08-22', 'Uttara, Dhaka', 'Unemployed', 'admin/uploads/810b73db93.jpg'),
(10, 'Seethamma', 450045457500, 0, 'Female', '09446765521', 'vijirajeshpv@gmail.com', '2023-07-04', 'Parayil house', 'Employee', 'admin/uploads/b389e22833.jpg'),
(11, 'Saju', 302369854, 0, 'Male', '9363253236', 'varifier@gmail.com', '2023-07-15', 'madathiparambil', 'other', 'admin/uploads/d9d5c82761.jpeg'),
(12, 'Rajesh P M', 45879635793, 0, 'Male', '09400635298', 'varifier@gmail.com', '2009-02-03', 'pullil', 'Employee', 'admin/uploads/bfefb3cd39.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer`
--

CREATE TABLE `tbl_customer` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` int(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `image` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_customer`
--

INSERT INTO `tbl_customer` (`id`, `name`, `mobile`, `address`, `image`) VALUES
(1, 'Seethamma', 2147483647, 'Parayil house', 'admin/uploads/b340ac00c5.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_gold_loan`
--

CREATE TABLE `tbl_gold_loan` (
  `gl_no` int(11) NOT NULL,
  `item_description` text NOT NULL,
  `gross_weight` float NOT NULL,
  `stone_weight` float NOT NULL,
  `net_weight` float NOT NULL,
  `market_value` decimal(11,0) NOT NULL,
  `loan_amnt` int(11) NOT NULL,
  `b_id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `date` date NOT NULL,
  `closing_date` datetime DEFAULT NULL,
  `due_date` date NOT NULL DEFAULT current_timestamp(),
  `status` int(11) DEFAULT 0,
  `file` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_gold_loan`
--

INSERT INTO `tbl_gold_loan` (`gl_no`, `item_description`, `gross_weight`, `stone_weight`, `net_weight`, `market_value`, `loan_amnt`, `b_id`, `name`, `date`, `closing_date`, `due_date`, `status`, `file`) VALUES
(0, 'Chain', 22, 0.25, 21.75, 5822, 101303, 1, 'Seethamma', '2024-02-10', NULL, '0000-00-00', 0, 'admin/uploads/documents/9a6fc77588.png'),
(1, 'Chain', 10, 0.01, 9.99, 5855, 10000, 2, 'Rajesh P M', '2023-09-13', NULL, '2024-01-14', 0, 'admin/uploads/documents/2479c587e1.pdf'),
(2, 'Bangle', 24, 0.01, 23.99, 5855, 20000, 1, 'Seethamma', '2023-09-13', NULL, '2024-01-14', 0, 'admin/uploads/documents/2178e30e52.pdf'),
(12, 'ear ring, others', 12.4, 0.01, 12.39, 5485, 50000, 3, 'shiji vn', '2023-09-13', NULL, '2024-01-14', 0, 'admin/uploads/documents/b3b225a2c4.pdf'),
(13, 'Bangle', 24.65, 0, 24.65, 5485, 108164, 2, 'Rajesh', '2023-09-13', NULL, '2024-01-14', 0, 'admin/uploads/documents/1fe8d9cdc7.pdf'),
(14, 'Chain', 8.9, 0, 8.9, 5458, 20000, 2, 'Rajesh', '2023-09-13', NULL, '2024-01-14', 0, 'admin/uploads/documents/4130f44ac4.pdf'),
(15, 'Chain, Bangle', 24, 0, 24, 5460, 10000, 4, 'sanju ar', '2023-09-16', NULL, '2024-01-14', 0, 'admin/uploads/documents/43cde3b280.pdf'),
(16, 'Bangle', 2, 0, 2, 5421, 8674, 1, 'Seethamma', '2023-10-02', NULL, '2024-01-14', 0, 'admin/uploads/documents/866cd01601.pdf'),
(17, 'Bangle', 3, 0, 3, 5400, 12960, 2, 'Rajesh', '2023-10-02', NULL, '2024-01-14', 0, 'admin/uploads/documents/40a85bbd84.pdf'),
(18, 'Bangle', 8, 0, 8, 5400, 34000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/67acc19dcd.jpg'),
(19, 'Bangle', 8, 0, 8, 5400, 34000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/8020327a51.jpg'),
(20, 'Bangle', 12, 0, 12, 5430, 25000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/44be31f227.jpg'),
(21, 'Bangle', 12, 0, 12, 5430, 25000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/5692eb418b.jpg'),
(22, 'Chain', 16, 0.2, 15.8, 5430, 50000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/05c39e1803.jpg'),
(23, 'Ring', 3, 0, 3, 5400, 10000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/eb6f063040.jpg'),
(24, 'Ring', 3, 0, 3, 5430, 10000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/4b123e855d.jpg'),
(25, 'ear ring', 3, 0, 3, 5430, 10000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/7ba2267c44.jpeg'),
(26, 'Bangle', 8, 0, 8, 5400, 30000, 1, 'Seethamma', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/b7bcee2e14.jpeg'),
(27, 'Ring', 2, 0.2, 1.8, 5430, 5000, 2, 'Rajesh', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/136dff5ca3.jpeg'),
(28, 'ear ring', 5, 0.3, 4.7, 5430, 20000, 2, 'Rajesh', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/9926f8d3a1.jpeg'),
(29, 'others', 16, 0, 16, 5430, 60000, 2, 'Rajesh', '2023-10-26', NULL, '2024-01-14', 0, 'admin/uploads/documents/a925b9199f.jpeg'),
(30, 'Chain', 5, 0, 5, 5430, 21720, 3, 'shiji vn', '2023-10-29', NULL, '2024-01-14', 0, 'admin/uploads/documents/b4d5d99dea.jpeg'),
(31, 'Chain', 12, 0, 12, 5400, 50000, 1, 'Seethamma', '2023-10-30', NULL, '2024-01-14', 0, 'admin/uploads/documents/691158f149.jpeg'),
(32, 'Ring', 2, 0, 2, 5400, 8000, 3, 'shiji vn', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/df38f7e446.jpeg'),
(33, 'Chain', 18, 0, 18, 5430, 750000, 4, 'sanju ar', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/8ac03d4720.jpeg'),
(34, 'Ring', 1, 0, 1, 5430, 3000, 4, 'sanju ar', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/bd797ad661.jpeg'),
(35, 'Bangle', 6, 0, 6, 5430, 25000, 4, 'sanju ar', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/eedd35e59d.jpg'),
(36, 'others', 2, 0, 2, 5430, 8688, 2, 'Rajesh', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/844643c898.png'),
(37, 'Ring', 2, 0, 2, 5430, 7500, 2, 'Rajesh', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/2a3a921054.jpg'),
(38, 'others', 2, 0, 2, 5430, 7500, 2, 'Rajesh', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/575a58e74e.jpeg'),
(39, 'Ring', 2, 0, 2, 5430, 8688, 2, 'Rajesh', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/89b9c1aa17.jpg'),
(40, 'Ring', 2, 0, 2, 5430, 5000, 1, 'Seethamma', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/e3d6949d1a.jpeg'),
(41, 'Ring', 1, 0.2, 0.8, 5430, 3000, 1, 'Seethamma', '2023-11-01', NULL, '2024-01-14', 0, 'admin/uploads/documents/7f1260779b.jpg'),
(42, 'Bangle', 9, 0, 9, 5430, 35000, 1, 'Seethamma', '2023-11-03', NULL, '2024-01-14', 0, 'admin/uploads/documents/5e13f7ffda.jpg'),
(43, 'others', 2, 0, 2, 5430, 8000, 2, 'Rajesh', '2023-11-03', NULL, '2024-01-14', 0, 'admin/uploads/documents/9e425c12ae.jpg'),
(44, 'Ring', 2, 0.4, 1.6, 5430, 5000, 3, 'shiji vn', '2023-11-03', NULL, '2024-01-14', 0, 'admin/uploads/documents/6c804d9089.jpg'),
(45, 'Bangle', 12, 0, 12, 5430, 50000, 3, 'shiji vn', '2023-11-03', NULL, '2024-01-14', 0, 'admin/uploads/documents/5fbe77421a.png'),
(46, 'Chain', 12, 0, 12, 5430, 50000, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/ca0428598a.jpg'),
(47, 'Ring', 1, 0, 1, 5430, 4000, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/b2a93edf91.png'),
(48, 'Bangle', 6, 0, 6, 5430, 25000, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/5c6002d08a.png'),
(49, 'Ring', 2, 0.2, 1.8, 5430, 75000, 4, 'sanju ar', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/92de153d75.png'),
(50, 'Chain', 4, 0, 4, 5430, 15000, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/5a86b14a1f.png'),
(51, 'Bangle', 6, 0, 6, 5430, 25000, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/d4a24755b3.png'),
(52, 'Chain', 5, 0, 5, 5430, 20000, 4, 'sanju ar', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/463b4ffacd.png'),
(53, 'Bangle', 6, 0, 6, 5430, 25000, 4, 'sanju ar', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/1d3c650d50.jpeg'),
(54, 'others', 5, 0, 5, 5430, 20000, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/e24ec3b310.jpeg'),
(55, 'ear ring', 2, 0, 2, 5430, 7500, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/a244591f95.jpeg'),
(56, 'Ring', 3, 0, 3, 5430, 10000, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/244dc3b1cb.jpeg'),
(57, 'Bangle', 8, 0, 8, 5430, 34752, 1, 'Seethamma', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/b85271aaa5.jpeg'),
(58, 'Ring', 1, 0.2, 0.8, 5430, 2500, 3, 'shiji vn', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/1d3e42a644.jpeg'),
(59, 'ear ring', 3, 0.3, 2.7, 5430, 10000, 3, 'shiji vn', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/45051bc5b1.jpeg'),
(60, 'Bangle', 16, 0, 16, 5430, 65000, 2, 'Rajesh', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/32f26b6a21.jpeg'),
(61, 'Ring', 5, 0, 5, 5430, 20000, 2, 'Rajesh', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/fe102736a9.jpeg'),
(62, 'Ring', 5, 0, 5, 5430, 20000, 2, 'Rajesh', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/d8ca569ee3.jpeg'),
(63, 'Ring', 3, 0, 3, 5430, 10000, 2, 'Rajesh', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/3c4491b06d.jpeg'),
(64, 'Ring', 2, 0, 2, 5430, 7500, 2, 'Rajesh', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/bf689fac67.jpg'),
(65, 'ear ring', 2, 0, 2, 5430, 8000, 4, 'sanju ar', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/e61e3ec98b.jpeg'),
(66, 'Ring', 2, 0, 2, 5430, 8000, 3, 'shiji vn', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/57206dd289.jpeg'),
(67, 'Ring', 4, 0, 4, 5430, 15000, 3, 'shiji vn', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/f0cbe9ac14.jpeg'),
(68, 'ear ring', 4, 0, 4, 5430, 15000, 3, 'shiji vn', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/7f20447912.jpg'),
(69, 'Ring', 4, 0, 4, 5430, 15000, 3, 'shiji vn', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/0801f2fde9.png'),
(70, 'Ring', 5, 0, 5, 5430, 20000, 4, 'sanju ar', '2023-11-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/a7b5290903.jpeg'),
(71, 'ear ring', 3, 0.01, 2.99, 5430, 10000, 1, 'Seethamma', '2023-12-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/f8410fd2f5.jpeg'),
(72, 'Bangle', 8, 0, 8, 5430, 30000, 2, 'Rajesh', '2023-12-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/229ed87ac1.jpeg'),
(73, 'Ring', 6, 0.02, 5.98, 5340, 25000, 3, 'shiji vn', '2023-12-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/1ef9d15a16.jpeg'),
(74, 'Ring 2', 4, 0, 4, 5430, 15000, 3, 'shiji vn', '2023-12-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/04f7f93bfd.png'),
(75, 'Ring 3', 4, 0, 4, 5400, 15500, 3, 'shiji vn', '2023-12-06', NULL, '2024-01-14', 0, 'admin/uploads/documents/fa61c469e0.png'),
(76, 'anklet 1', 8, 0, 8, 5400, 34000, 4, 'sanju ar', '2023-12-07', NULL, '2024-01-14', 0, 'admin/uploads/documents/474a71817f.png'),
(77, 'ear ring 3', 12, 0.02, 11.98, 5400, 50000, 4, 'sanju ar', '2023-12-07', NULL, '2024-01-14', 0, 'admin/uploads/documents/08e9d507fd.png'),
(78, 'Chain1', 12, 0, 12, 5400, 51840, 4, 'sanju ar', '2023-12-07', NULL, '2024-01-14', 0, 'admin/uploads/documents/1edc853c5c.png'),
(79, 'Ring3', 6, 0, 6, 5400, 25000, 4, 'sanju ar', '2023-12-07', NULL, '2024-01-14', 0, 'admin/uploads/documents/9b07a6ed0c.png'),
(80, 'Chain2,ear ring1', 14, 0.2, 13.8, 5400, 55000, 4, 'sanju ar', '2023-12-07', NULL, '2024-01-14', 0, 'admin/uploads/documents/332d639209.png'),
(81, 'Chain', 10, 0, 10, 5422, 40000, 1, 'Seethamma', '2024-02-08', NULL, '0000-00-00', 0, 'admin/uploads/documents/c02a71e791.jpg'),
(82, 'Bangle', 22, 5, 17, 5800, 78880, 1, 'Seethamma', '2024-02-08', NULL, '0000-00-00', 0, 'admin/uploads/documents/986e2c885d.jpg'),
(83, 'Bangle', 12, 0, 12, 5800, 55680, 1, 'Seethamma', '2024-02-08', NULL, '0000-00-00', 0, 'admin/uploads/documents/9d0547022a.jpg'),
(84, 'Chain', 4, 0, 4, 5820, 18624, 1, 'Seethamma', '2024-02-10', NULL, '0000-00-00', 0, 'admin/uploads/documents/e30c2d6595.jpg'),
(85, 'Bangle,ear ring', 25, 1.5, 23.5, 5820, 100000, 1, 'Seethamma', '2024-02-10', NULL, '0000-00-00', 0, 'admin/uploads/documents/1b8b2602b4.jpeg'),
(86, 'Bangle', 20, 0, 20, 5820, 93120, 1, 'Seethamma', '2024-02-11', NULL, '0000-00-00', 0, 'admin/uploads/documents/06df9e30d5.jpeg'),
(87, 'Bangle', 28, 2, 26, 5820, 121056, 1, 'Seethamma', '2024-02-11', NULL, '0000-00-00', 0, 'admin/uploads/documents/e1273070e1.jpeg'),
(88, 'Bangle', 8, 0, 8, 5820, 37248, 1, 'Seethamma', '2024-02-11', NULL, '0000-00-00', 0, 'admin/uploads/documents/45d072241d.jpeg'),
(89, 'Bangle', 8, 0, 8, 5820, 37248, 1, 'Seethamma', '2024-02-11', NULL, '0000-00-00', 0, 'admin/uploads/documents/23860136b2.jpeg'),
(90, 'Chain', 3, 0, 3, 5820, 13968, 1, 'Seethamma', '2024-02-11', NULL, '0000-00-00', 0, 'admin/uploads/documents/0f35f25dc9.jpeg'),
(91, 'Bangle', 4, 0, 4, 5820, 18624, 1, 'Seethamma', '2024-02-14', NULL, '0000-00-00', 0, 'admin/uploads/documents/5a2b22e94b.png'),
(92, 'Bangle', 12, 0, 12, 5820, 55872, 1, 'Seethamma', '2024-02-14', NULL, '0000-00-00', 0, 'admin/uploads/documents/7f8f5251c6.png'),
(93, 'Bangle', 12, 0, 12, 5820, 55872, 1, 'Seethamma', '2024-02-14', NULL, '0000-00-00', 0, 'admin/uploads/documents/a06990f691.jpg'),
(94, 'Bangle', 12, 0, 12, 5820, 55872, 1, 'Seethamma', '2024-02-14', NULL, '0000-00-00', 0, 'admin/uploads/documents/5fb14b743b.jpeg'),
(96, 'Bangle', 5, 0, 5, 5840, 23360, 1, 'Seethamma', '2024-02-18', NULL, '0000-00-00', 0, 'admin/uploads/documents/643528d00a.jpg'),
(97, 'Ring', 3, 0.02, 2.98, 5820, 13875, 1, 'Seethamma', '2024-02-18', NULL, '0000-00-00', 0, 'admin/uploads/documents/ba61338567.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_liability`
--

CREATE TABLE `tbl_liability` (
  `id` int(11) NOT NULL,
  `b_id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `property_name` varchar(255) NOT NULL,
  `property_details` text NOT NULL,
  `price` bigint(50) NOT NULL,
  `pay_remaining_loan` bigint(50) NOT NULL,
  `return_money` bigint(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_loan_application`
--

CREATE TABLE `tbl_loan_application` (
  `id` int(11) NOT NULL,
  `b_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `expected_loan` bigint(50) NOT NULL,
  `loan_percentage` int(11) NOT NULL,
  `installments` int(11) NOT NULL,
  `total_loan` bigint(50) NOT NULL,
  `emi_loan` int(11) NOT NULL,
  `amount_paid` bigint(50) DEFAULT 0,
  `amount_remain` bigint(50) DEFAULT NULL,
  `current_inst` int(11) DEFAULT 0,
  `remain_inst` int(11) DEFAULT NULL,
  `next_date` date DEFAULT NULL,
  `files` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_loan_application`
--

INSERT INTO `tbl_loan_application` (`id`, `b_id`, `status`, `name`, `expected_loan`, `loan_percentage`, `installments`, `total_loan`, `emi_loan`, `amount_paid`, `amount_remain`, `current_inst`, `remain_inst`, `next_date`, `files`) VALUES
(9, 8, 3, 'TIPU SULTAM', 40000, 12, 8, 44800, 5600, 11200, 33600, 2, 6, '2018-02-13', 'admin/uploads/documents/ff4bff1a9b.docx'),
(10, 7, 3, 'Samdani', 8000, 5, 5, 8400, 1680, 3360, 5040, 2, 3, '2018-06-14', 'admin/uploads/documents/f2c5766143.docx');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment`
--

CREATE TABLE `tbl_payment` (
  `id` int(11) NOT NULL,
  `b_id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `pay_amount` int(11) NOT NULL,
  `pay_date` date NOT NULL,
  `current_inst` int(11) NOT NULL,
  `remain_inst` int(11) NOT NULL,
  `fine` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_payment`
--

INSERT INTO `tbl_payment` (`id`, `b_id`, `loan_id`, `pay_amount`, `pay_date`, `current_inst`, `remain_inst`, `fine`) VALUES
(1, 8, 9, 5600, '2018-04-15', 1, 7, 0),
(2, 7, 10, 1680, '2018-04-15', 1, 4, 0),
(3, 7, 10, 1680, '2018-05-10', 2, 3, 0),
(4, 8, 9, 5600, '2018-08-30', 2, 6, 112);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repledge`
--

CREATE TABLE `tbl_repledge` (
  `bank_gl_no` varchar(11) NOT NULL,
  `date` date NOT NULL,
  `due_date` date NOT NULL,
  `finance_gl_no` int(11) NOT NULL,
  `Status` int(11) NOT NULL,
  `customer_name` varchar(50) NOT NULL,
  `amount_bank` decimal(11,0) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `interest` decimal(11,0) NOT NULL DEFAULT 0,
  `others` decimal(11,0) NOT NULL DEFAULT 0,
  `total_amount` decimal(11,0) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_repledge`
--

INSERT INTO `tbl_repledge` (`bank_gl_no`, `date`, `due_date`, `finance_gl_no`, `Status`, `customer_name`, `amount_bank`, `customer_id`, `interest`, `others`, `total_amount`) VALUES
('1', '2023-08-28', '2023-09-04', 2, 1, 'Seethamma', 5500, 10, 0, 0, 0),
('2', '2023-09-04', '2023-09-04', 1, 1, 'Md Raihan Uddin', 10000, 9, 0, 0, 0),
('3', '2023-09-04', '2023-09-10', 3, 1, 'tipu', 10000, 7, 0, 0, 0),
('3', '2023-09-10', '2023-09-10', 8, 1, 'Md Raihan Uddin', 1000, 9, 0, 0, 0),
('1/2023', '2023-12-11', '2023-12-30', 25, 0, 'seethamma', 5000, 1, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pass` varchar(50) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `role` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`id`, `name`, `email`, `pass`, `designation`, `role`) VALUES
(2, 'Md Niamul Haque', 'head@gmail.com', '202cb962ac59075b964b07152d234b70', 'Head Officer', 3),
(3, 'Rajesh P V', 'branch@gmail.com', '202cb962ac59075b964b07152d234b70', 'Proprietor', 2),
(4, 'Md Faizul Haque', 'varifier@gmail.com', '202cb962ac59075b964b07152d234b70', 'Varifier', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_borrower`
--
ALTER TABLE `tbl_borrower`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nid` (`nid`);

--
-- Indexes for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_gold_loan`
--
ALTER TABLE `tbl_gold_loan`
  ADD PRIMARY KEY (`gl_no`);

--
-- Indexes for table `tbl_liability`
--
ALTER TABLE `tbl_liability`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_loan_application`
--
ALTER TABLE `tbl_loan_application`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_payment`
--
ALTER TABLE `tbl_payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_borrower`
--
ALTER TABLE `tbl_borrower`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_loan_application`
--
ALTER TABLE `tbl_loan_application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_payment`
--
ALTER TABLE `tbl_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
