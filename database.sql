-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: db_city_water_supply
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `db_city_water_supply`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `db_city_water_supply` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `db_city_water_supply`;

--
-- Table structure for table `billing_cycles`
--

DROP TABLE IF EXISTS `billing_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing_cycles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cycle_code` varchar(20) NOT NULL,
  `month` int(11) NOT NULL,
  `year_be` int(11) NOT NULL,
  `reading_start_date` date NOT NULL,
  `reading_end_date` date NOT NULL,
  `due_date` date NOT NULL,
  `tariff_rate_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'OPEN',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cycle_code` (`cycle_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_cycles`
--

LOCK TABLES `billing_cycles` WRITE;
/*!40000 ALTER TABLE `billing_cycles` DISABLE KEYS */;
INSERT INTO `billing_cycles` VALUES (1,'8-2567',8,2567,'2567-08-25','2567-08-28','2567-09-10',1,'OPEN','2026-09-27 14:57:32');
/*!40000 ALTER TABLE `billing_cycles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!50001 DROP VIEW IF EXISTS `customers`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `customers` AS SELECT
 1 AS `id`,
  1 AS `customer_code`,
  1 AS `seq_no`,
  1 AS `first_name`,
  1 AS `last_name`,
  1 AS `fullname`,
  1 AS `house_no`,
  1 AS `zone`,
  1 AS `zone_id`,
  1 AS `phone`,
  1 AS `meter_serial`,
  1 AS `meter_size`,
  1 AS `install_type_id`,
  1 AS `status`,
  1 AS `meter_installed_date`,
  1 AS `notes`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `meter_readings`
--

DROP TABLE IF EXISTS `meter_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meter_readings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `billing_cycle_id` int(11) NOT NULL,
  `customer_id` int(4) unsigned zerofill NOT NULL,
  `previous_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `units_used` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_per_unit` decimal(10,2) NOT NULL DEFAULT 7.00,
  `water_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maintenance_fee` decimal(10,2) NOT NULL DEFAULT 10.00,
  `current_total` decimal(10,2) NOT NULL DEFAULT 10.00,
  `previous_arrears` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 10.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `remaining_balance` decimal(10,2) DEFAULT 0.00,
  `payment_status` varchar(20) DEFAULT 'UNPAID',
  `payment_date` date DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `reading_date` date NOT NULL,
  `reader_name` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cycle_customer` (`billing_cycle_id`,`customer_id`),
  KEY `idx_reading_cycle` (`billing_cycle_id`),
  KEY `idx_reading_cust` (`customer_id`),
  CONSTRAINT `fk_readings_customer` FOREIGN KEY (`customer_id`) REFERENCES `tb_customers` (`cus_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_readings_cycle` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meter_readings`
--

LOCK TABLES `meter_readings` WRITE;
/*!40000 ALTER TABLE `meter_readings` DISABLE KEYS */;
INSERT INTO `meter_readings` VALUES (1,1,0001,150.00,170.00,20.00,7.00,140.00,10.00,150.00,0.00,150.00,0.00,0.00,'PAID',NULL,'8-2567/501','2567-08-27',NULL,NULL),(2,1,0002,210.00,235.00,25.00,7.00,175.00,10.00,185.00,0.00,185.00,0.00,0.00,'PAID',NULL,'8-2567/502','2567-08-27',NULL,NULL),(3,1,0003,380.00,412.00,32.00,7.00,224.00,10.00,234.00,150.00,384.00,0.00,0.00,'UNPAID',NULL,NULL,'2567-08-27',NULL,NULL),(4,1,0004,95.00,110.00,15.00,7.00,105.00,10.00,115.00,0.00,115.00,0.00,0.00,'PAID',NULL,'8-2567/503','2567-08-27',NULL,NULL),(5,1,0005,512.00,540.00,28.00,7.00,196.00,10.00,206.00,0.00,206.00,0.00,0.00,'PAID',NULL,'8-2567/504','2567-08-27',NULL,NULL),(6,1,0006,180.00,198.00,18.00,7.00,126.00,10.00,136.00,80.00,216.00,0.00,0.00,'UNPAID',NULL,NULL,'2567-08-27',NULL,NULL),(7,1,0007,304.00,326.00,22.00,7.00,154.00,10.00,164.00,0.00,164.00,0.00,0.00,'PAID',NULL,'8-2567/505','2567-08-27',NULL,NULL),(8,1,0008,420.00,455.00,35.00,7.00,245.00,10.00,255.00,0.00,255.00,0.00,0.00,'PAID',NULL,'8-2567/506','2567-08-27',NULL,NULL);
/*!40000 ALTER TABLE `meter_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_financial_reports`
--

DROP TABLE IF EXISTS `monthly_financial_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_financial_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `billing_cycle_id` int(11) NOT NULL,
  `rev_water_maintenance` decimal(12,2) DEFAULT 0.00,
  `rev_collected_arrears` decimal(12,2) DEFAULT 0.00,
  `rev_new_meter_fee` decimal(12,2) DEFAULT 0.00,
  `rev_bank_interest` decimal(12,2) DEFAULT 0.00,
  `rev_other` decimal(12,2) DEFAULT 0.00,
  `total_revenue` decimal(12,2) NOT NULL,
  `exp_caretaker` decimal(12,2) DEFAULT 0.00,
  `exp_committee` decimal(12,2) DEFAULT 0.00,
  `exp_collector` decimal(12,2) DEFAULT 0.00,
  `exp_electricity` decimal(12,2) DEFAULT 0.00,
  `exp_supplies_repairs` decimal(12,2) DEFAULT 0.00,
  `exp_other` decimal(12,2) DEFAULT 0.00,
  `total_expense` decimal(12,2) NOT NULL,
  `net_profit_loss` decimal(12,2) NOT NULL,
  `prev_accumulated_balance` decimal(12,2) NOT NULL,
  `total_accumulated_balance` decimal(12,2) NOT NULL,
  `cash_in_hand` decimal(12,2) DEFAULT 5000.00,
  `bank_deposit` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_cycle_id` (`billing_cycle_id`),
  CONSTRAINT `monthly_financial_reports_ibfk_1` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_financial_reports`
--

LOCK TABLES `monthly_financial_reports` WRITE;
/*!40000 ALTER TABLE `monthly_financial_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `monthly_financial_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_vouchers`
--

DROP TABLE IF EXISTS `payment_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(50) NOT NULL,
  `billing_cycle_id` int(11) NOT NULL,
  `voucher_date` date NOT NULL,
  `recipient_name` varchar(150) NOT NULL,
  `recipient_position` varchar(100) NOT NULL,
  `voucher_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_text_th` varchar(255) NOT NULL,
  `calculation_basis` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_no` (`voucher_no`),
  KEY `billing_cycle_id` (`billing_cycle_id`),
  CONSTRAINT `payment_vouchers_ibfk_1` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_vouchers`
--

LOCK TABLES `payment_vouchers` WRITE;
/*!40000 ALTER TABLE `payment_vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_tickets`
--

DROP TABLE IF EXISTS `service_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(50) NOT NULL,
  `reporter_name` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `house_no` varchar(50) NOT NULL,
  `zone` varchar(100) NOT NULL,
  `issue_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `repair_notes` text DEFAULT NULL,
  `repair_cost` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_no` (`ticket_no`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_tickets`
--

LOCK TABLES `service_tickets` WRITE;
/*!40000 ALTER TABLE `service_tickets` DISABLE KEYS */;
INSERT INTO `service_tickets` VALUES (1,'TK-2567-0801','นายสมพร ใจกล้า','081-999-1234','15/2 หมู่ 3','โซนเหนือ - ซอย 1','ท่อเมนแตก/รั่ว','มีน้ำเอ่อล้นบริเวณหน้าบ้าน คาดว่าข้อต่อท่อ PVC แตกใต้ดิน','PENDING','2026-09-27 16:34:22',NULL,NULL,0.00),(2,'TK-2567-0802','นางบัวลอย บุญมี','089-777-5544','22 หมู่ 3','โซนกลาง - ซอยวัด','น้ำไม่ไหล/ไหลอ่อน','น้ำประปาไหลอ่อนมากตั้งแต่ช่วงเช้า','IN_PROGRESS','2026-09-27 16:34:22',NULL,NULL,0.00),(3,'TK-2567-0803','นายคำดี รุ่งเรือง','086-444-8899','40 หมู่ 3','โซนใต้ - ท้ายบ้าน','มาตรวัดน้ำชำรุด','หน้าปัดมิเตอร์แตก ตัวเลขไม่เดิน','RESOLVED','2026-09-27 16:34:22',NULL,NULL,0.00),(4,'TK-20260927-377','นายสมพร นครพนม','0819876543','12/1 หมู่ 3','โซนเหนือ','ท่อแตก/รั่ว','ท่อเมนหน้าบ้านแตก น้ำพุ่งท่วมทางเข้า','PENDING','2026-09-27 16:41:08',NULL,NULL,0.00);
/*!40000 ALTER TABLE `service_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tariff_rates`
--

DROP TABLE IF EXISTS `tariff_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tariff_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `rate_per_unit` decimal(10,2) NOT NULL,
  `maintenance_fee` decimal(10,2) NOT NULL DEFAULT 10.00,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `resolution_details` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tariff_rates`
--

LOCK TABLES `tariff_rates` WRITE;
/*!40000 ALTER TABLE `tariff_rates` DISABLE KEYS */;
INSERT INTO `tariff_rates` VALUES (1,'เธกเธเธดเธเธตเนเธเธฃเธฐเธเธธเธกเธชเธฑเธเธเธฃ เธเธฃเธฑเธเธเนเธฒเธเนเธณ 7 เธเธฒเธ/เธฅเธ',7.00,10.00,'2567-01-01',NULL,1,'เธกเธเธดเธเธเธฐเธเธฃเธฃเธกเธเธฒเธฃเธเธฒเธฃเธเธฃเธฐเธเธฒเธซเธกเธนเนเธเนเธฒเธเธงเธฑเธเธขเธฒเธ เธเนเธฒเธเธฃเธดเธเธฒเธฃเธกเธฒเธเธฃ 10 เธเธฒเธ/เนเธเธทเธญเธ');
/*!40000 ALTER TABLE `tariff_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_customers`
--

DROP TABLE IF EXISTS `tb_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_customers` (
  `cus_id` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `cus_firstname` varchar(20) NOT NULL,
  `cus_surname` varchar(30) NOT NULL,
  `install_type_id` int(1) unsigned zerofill NOT NULL,
  `house_id` varchar(20) NOT NULL,
  `cus_tel` varchar(10) NOT NULL,
  `zone_id` int(2) unsigned zerofill NOT NULL,
  PRIMARY KEY (`cus_id`),
  KEY `fk_customers_installation` (`install_type_id`),
  KEY `fk_customers_zone` (`zone_id`),
  CONSTRAINT `fk_customers_installation` FOREIGN KEY (`install_type_id`) REFERENCES `tb_installation` (`install_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_customers_zone` FOREIGN KEY (`zone_id`) REFERENCES `tb_zone` (`zone_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_customers`
--

LOCK TABLES `tb_customers` WRITE;
/*!40000 ALTER TABLE `tb_customers` DISABLE KEYS */;
INSERT INTO `tb_customers` VALUES (0001,'สมเกียรติ','เจริญผล',1,'10/1 ม.1','0810001111',01),(0002,'สมชาย','ไชยรัก',1,'12/3 ม.1','0812345678',01),(0003,'สุดา','วังยาง',2,'45 ม.2','0898765432',02),(0004,'บุญส่ง','เกษมสุข',1,'25 ม.2','0843338899',02),(0005,'อนันต์','เกตุดี',1,'8/1 ม.1','0811112233',01),(0006,'วิภาดา','ชลธาร',3,'90/7 ม.3','0865556677',03),(0007,'กัญญา','ศรีสวัสดิ์',2,'52/1 ม.3','0874442211',03),(0008,'ชูเกียรติ','รุ่งเรือง',1,'60 ม.3','0839993344',03);
/*!40000 ALTER TABLE `tb_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_installation`
--

DROP TABLE IF EXISTS `tb_installation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_installation` (
  `install_id` int(1) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `install_name` varchar(50) NOT NULL,
  PRIMARY KEY (`install_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_installation`
--

LOCK TABLES `tb_installation` WRITE;
/*!40000 ALTER TABLE `tb_installation` DISABLE KEYS */;
INSERT INTO `tb_installation` VALUES (1,'มิเตอร์ขนาด 5/8 นิ้ว'),(2,'มิเตอร์ขนาด 1 นิ้ว'),(3,'มิเตอร์ขนาด 1.5 นิ้ว');
/*!40000 ALTER TABLE `tb_installation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_users`
--

DROP TABLE IF EXISTS `tb_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_users` (
  `user_id` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'staff',
  `cus_id` int(4) unsigned zerofill DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_customers` (`cus_id`),
  CONSTRAINT `fk_users_customers` FOREIGN KEY (`cus_id`) REFERENCES `tb_customers` (`cus_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_users`
--

LOCK TABLES `tb_users` WRITE;
/*!40000 ALTER TABLE `tb_users` DISABLE KEYS */;
INSERT INTO `tb_users` VALUES (0001,'admin','81dc9bdb52d04dc20036dbd8313ed055','ผู้ดูแลระบบประปา','admin',NULL,'2026-09-29 17:54:38'),(0002,'staff','81dc9bdb52d04dc20036dbd8313ed055','เจ้าหน้าที่น้ำประปา','staff',NULL,'2026-09-29 17:54:38'),(0003,'member','81dc9bdb52d04dc20036dbd8313ed055','นายสมชาย ไชยรัก (สมาชิกประปา)','member',0002,'2026-09-29 17:54:38'),(0007,'user','81dc9bdb52d04dc20036dbd8313ed055','ประชาชนทั่วไป (User)','user',NULL,'2026-10-01 00:36:13');
/*!40000 ALTER TABLE `tb_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_zone`
--

DROP TABLE IF EXISTS `tb_zone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_zone` (
  `zone_id` int(2) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(50) NOT NULL,
  PRIMARY KEY (`zone_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_zone`
--

LOCK TABLES `tb_zone` WRITE;
/*!40000 ALTER TABLE `tb_zone` DISABLE KEYS */;
INSERT INTO `tb_zone` VALUES (01,'โซน 1 วังยางเหนือ'),(02,'โซน 2 วังยางกลาง'),(03,'โซน 3 วังยางใต้');
/*!40000 ALTER TABLE `tb_zone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `water_loss_logs`
--

DROP TABLE IF EXISTS `water_loss_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `water_loss_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cycle_code` varchar(20) NOT NULL,
  `production_volume` decimal(12,2) NOT NULL,
  `billed_volume` decimal(12,2) NOT NULL,
  `loss_volume` decimal(12,2) NOT NULL,
  `loss_percentage` decimal(5,2) NOT NULL,
  `estimated_loss_cost` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `water_loss_logs`
--

LOCK TABLES `water_loss_logs` WRITE;
/*!40000 ALTER TABLE `water_loss_logs` DISABLE KEYS */;
INSERT INTO `water_loss_logs` VALUES (1,'8-2567',240.00,197.00,43.00,17.92,301.00,'ตรวจพบจุดรั่วซึมท่อเมนคุ้มเหนือ กำลังเข้าซ่อม','2026-09-27 16:34:22');
/*!40000 ALTER TABLE `water_loss_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `db_city_water_supply`
--

USE `db_city_water_supply`;

--
-- Final view structure for view `customers`
--

/*!50001 DROP VIEW IF EXISTS `customers`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `customers` AS select `c`.`cus_id` AS `id`,concat('WY-',lpad(`c`.`cus_id` + 0,3,'0')) AS `customer_code`,`c`.`cus_id` + 0 AS `seq_no`,`c`.`cus_firstname` AS `first_name`,`c`.`cus_surname` AS `last_name`,concat(`c`.`cus_firstname`,' ',`c`.`cus_surname`) AS `fullname`,`c`.`house_id` AS `house_no`,coalesce(`z`.`zone_name`,concat('โซน ',`c`.`zone_id`)) AS `zone`,`c`.`zone_id` AS `zone_id`,`c`.`cus_tel` AS `phone`,concat('MTR-',lpad(`c`.`cus_id` + 0,4,'0')) AS `meter_serial`,coalesce(`i`.`install_name`,'มิเตอร์ขนาด 5/8 นิ้ว') AS `meter_size`,`c`.`install_type_id` AS `install_type_id`,'ACTIVE' AS `status`,'2026-01-01' AS `meter_installed_date`,NULL AS `notes`,current_timestamp() AS `created_at`,current_timestamp() AS `updated_at` from ((`tb_customers` `c` left join `tb_zone` `z` on(`c`.`zone_id` = `z`.`zone_id`)) left join `tb_installation` `i` on(`c`.`install_type_id` = `i`.`install_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-01 14:18:52
