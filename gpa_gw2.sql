/*M!999999\- enable the sandbox mode */
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: gpa_gw2
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `actor_user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int(10) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_actor` (`actor_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `reading_time` varchar(50) DEFAULT '5 min read',
  `author_user_id` int(10) unsigned DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_user_id` (`author_user_id`),
  CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
INSERT INTO `blog_posts` VALUES
(1,'7b3ba3e9-e2b9-4546-827f-b2aa406af4bb','Why Scaling Breaks Businesses Without Operating Discipline','scaling-breaks-businesses','Growth exposes weak systems, not market demand. Learn how to build operational discipline before you scale.','<p class=\"lead\">Growth exposes weak systems, not market demand. When revenue grows faster than your operations, cracks appear everywhere — and the businesses that survive are the ones that build structure before they scale.</p><h2>The False Comfort of Demand</h2><p>High demand often masks operational risk. When teams chase top-line growth without strong delivery systems, churn and service failures follow. Revenue can climb while customer satisfaction quietly erodes.</p><h2>Misaligned Leadership Compounds the Problem</h2><p>Strategy fails when execution ownership is unclear. Define accountability across teams and map every process handoff. Without shared visibility, each department optimizes locally while the customer experience breaks globally.</p><h2>Complexity as a Risk Factor</h2><p>As service lines increase, complexity multiplies. Standardized workflows and clear decision rights are the only way to scale sustainably. Every new offering should come with a defined owner and a measurable outcome.</p>','Consultancy','5 min read',1,'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1200&q=85','UAE,Documentation,Marketing,GlobalWays,2026','published','2026-07-13 09:03:27',NULL),
(2,'560f3e21-e5ac-4355-add7-c0ef4373c8e7','Strategy Fails When Execution Lacks Structural Accountability','strategy-fails-without-accountability','Most strategy documents fail not because the ideas are wrong, but because no one owns the outcome.','<p class=\"lead\">Most strategy documents fail not because the ideas are wrong, but because no one owns the outcome.</p><h2>The Accountability Gap</h2><p>When leadership rolls out major changes without individual owners, alignment decays. Accountability isn\'t about blame; it\'s about knowing exactly who is responsible for driving each milestone to completion.</p>','Consultancy','5 min read',1,'https://images.unsplash.com/photo-1518684079-3c830dcef090?w=1200&q=85','UAE,Business Setup,Strategy','published','2026-07-13 09:03:27',NULL),
(3,'917f8b09-792b-4646-80d2-0e80246fcdf3','Growth Exposes Weak Systems, Not Market Demand','growth-exposes-weak-systems','When revenue grows faster than your operations, cracks appear everywhere. Here\'s how to stay ahead.','<p class=\"lead\">When revenue grows faster than your operations, cracks appear everywhere. Here\'s how to stay ahead.</p><p>We focus on simplifying ownership, strengthening systems, and aligning leadership around what actually moves your UAE journey forward.</p>','Marketing','4 min read',1,'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&q=85','Escrow,Payments,Secure','published','2026-07-13 09:03:27',NULL);
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_pages`
--

DROP TABLE IF EXISTS `cms_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_name` varchar(50) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_name` (`page_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_pages`
--

LOCK TABLES `cms_pages` WRITE;
/*!40000 ALTER TABLE `cms_pages` DISABLE KEYS */;
INSERT INTO `cms_pages` VALUES
(1,'about','{\"stats\": [{\"label\": \"Happy Customers\", \"number\": \"50,000+\", \"highlight\": false}, {\"label\": \"Verified Vendors\", \"number\": \"500+\", \"highlight\": true}, {\"label\": \"Success Rate\", \"number\": \"99.8%\", \"highlight\": false}, {\"label\": \"Nationalities Served\", \"number\": \"150+\", \"highlight\": false}], \"values\": [{\"desc\": \"Every decision we make starts with ‘does this protect our customers?’\", \"icon\": \"bi-shield\", \"title\": \"Trust First\"}, {\"desc\": \"No hidden fees, no surprises. What you see is exactly what you get.\", \"icon\": \"bi-lightning\", \"title\": \"Radical Transparency\"}, {\"desc\": \"Behind every application is a person’s dream. We take that seriously.\", \"icon\": \"bi-heart\", \"title\": \"People-Centred\"}, {\"desc\": \"We obsess over making every part of the platform better, every day.\", \"icon\": \"bi-graph-up-arrow\", \"title\": \"Continuous Excellence\"}], \"journey\": [{\"desc\": \"Started as a PRO services comparison tool by two frustrated expats\", \"year\": \"2020\", \"title\": \"Founded in Dubai\"}, {\"desc\": \"Reached our first 100 verified vendor partners across 5 Emirates\", \"year\": \"2021\", \"title\": \"100 Vendors\"}, {\"desc\": \"Crossed 10,000 customers and launched our Document Vault\", \"year\": \"2022\", \"title\": \"10,000 Customers\"}, {\"desc\": \"Launched industry-first escrow payment protection for UAE services\", \"year\": \"2023\", \"title\": \"Escrow Payments\"}, {\"desc\": \"Raised AED 16M to expand our vendor network and tech platform\", \"year\": \"2024\", \"title\": \"Series A Raised\"}, {\"desc\": \"Trusted by 50,000+ customers across 150+ nationalities\", \"year\": \"2026\", \"title\": \"50,000 Customers\"}], \"story_sub\": \"Two expats — tired of chasing PRO agents, losing documents, and paying without any guarantee — decided to build a marketplace that puts customers first.\", \"story_title\": \"We Built the Platform We Wished Existed\", \"mission_copy\": \"We connect individuals and businesses with verified UAE service providers — with escrow payments, real-time tracking, and transparent pricing at every step. Every vendor on our platform is vetted for licensing, success rate, and customer satisfaction. We don\'t just list services; we guarantee accountability.\", \"story_kicker\": \"Our Story\", \"values_title\": \"Our Values\", \"journey_title\": \"Six Years of Impact\", \"mission_proof\": [\"Verified all 500+ vendors for licensing, credentials, and compliance\", \"Protected over AED 48M in escrow payments for UAE customers\", \"Maintained a 99.8% application success rate across services\", \"Served customers from 150+ nationalities with real-time tracking\", \"Built transparent pricing with no hidden fees at any step\"], \"mission_title\": \"Making UAE Documentation Simple, Safe & Stress-Free\", \"story_cta_url\": \"services.php\", \"values_kicker\": \"What We Stand For\", \"journey_kicker\": \"Our Journey\", \"mission_kicker\": \"Our Mission\", \"story_cta_text\": \"Explore Services\"}'),
(2,'contact','{\"email\": \"hello@globalways.ae\", \"hours\": [{\"days\": \"Sun – Thu\", \"time\": \"8:00 AM – 6:00 PM GST\", \"closed\": false}, {\"days\": \"Friday\", \"time\": \"8:00 AM – 1:00 PM GST\", \"closed\": false}, {\"days\": \"Saturday\", \"time\": \"Closed\", \"closed\": true}], \"phone\": \"+971 4 400 0000\", \"hero_sub\": \"Our team responds within 2 hours during business hours. For urgent matters, reach us on WhatsApp.\", \"hq_title\": \"Dubai, UAE\", \"whatsapp\": \"+971 4 400 0000\", \"email_meta\": \"Avg. reply · under 2 hours\", \"hero_title\": \"Get in Touch\", \"hq_address\": \"GlobalWays Advisory\\nDubai International Financial Centre\\nLevel 6, Gate Avenue\\nDubai, UAE 000001\", \"phone_meta\": \"Sun–Thu · 8:00 AM – 6:00 PM GST\", \"hero_kicker\": \"Contact\", \"whatsapp_url\": \"https://wa.me/97144000000\", \"whatsapp_meta\": \"Avg. reply · under 30 min\"}'),
(3,'how_it_works','{\"steps\": [{\"num\": \"01\", \"desc\": \"Search 500+ verified vendors by service type, rating, price, language, and location. Every vendor has been background-checked and has real verified reviews.\", \"icon\": \"bi-search\", \"title\": \"Browse & Compare Vendors\", \"bullets\": [\"Filter by service type, city, language & rating\", \"Read real reviews from verified customers\", \"Compare price, timeline & success rate side-by-side\", \"View vendor credentials and certifications\"], \"mockup_items\": [{\"sub\": \"Golden Visa Specialist\", \"name\": \"Emirates Pro Services\", \"active\": false, \"avatar\": \"E\", \"rating\": \"4.9\"}, {\"sub\": \"Business Setup Expert\", \"name\": \"Dubai Business Hub\", \"active\": true, \"avatar\": \"D\", \"rating\": \"4.9\"}, {\"sub\": \"PRO Services Leader\", \"name\": \"Al Maha Consultants\", \"active\": false, \"avatar\": \"A\", \"rating\": \"4.9\"}], \"mockup_label\": \"Top Vendors\"}, {\"num\": \"02\", \"desc\": \"Select your vendor and pay through our secure platform. Your payment is held in escrow — the vendor only receives it after you confirm the work is done.\", \"icon\": \"bi-credit-card\", \"title\": \"Book & Pay via Secure Escrow\", \"bullets\": [\"Pay by card, bank transfer or Apple/Google Pay\", \"Funds held in escrow until you confirm completion\", \"No upfront risk — money returned if vendor fails\", \"VAT receipts issued automatically\"], \"mockup_label\": \"Order Summary\", \"mockup_lines\": [{\"label\": \"Vendor quote\", \"amount\": \"AED 5,000\"}, {\"label\": \"Platform Fee (3%)\", \"amount\": \"AED 150\"}, {\"label\": \"Government Fees (est.)\", \"amount\": \"AED 2,720\"}], \"mockup_title\": \"Golden Visa — Emirates Pro Services\", \"mockup_total\": \"AED 7,870\"}, {\"num\": \"03\", \"desc\": \"Once booked, your vendor starts working and updates each milestone in real-time. You\'ll receive WhatsApp and email notifications at every stage.\", \"icon\": \"bi-bell\", \"title\": \"Track Your Application Live\", \"bullets\": [\"FedEx-style milestone tracking dashboard\", \"WhatsApp & email notifications at every step\", \"Direct in-app messaging with your vendor\", \"Estimated completion date always visible\"], \"mockup_label\": \"Application Tracker\", \"mockup_statuses\": [{\"label\": \"Submitted\", \"status\": \"done\"}, {\"label\": \"Docs Verified\", \"status\": \"done\"}, {\"label\": \"Gov. Submitted\", \"status\": \"done\"}, {\"label\": \"Biometrics\", \"status\": \"in-progress\"}, {\"label\": \"Approved\", \"status\": \"pending\"}]}, {\"num\": \"04\", \"desc\": \"Your documents are delivered to your encrypted Document Vault. Confirm delivery, rate your vendor, and access your documents forever.\", \"icon\": \"bi-check2-circle\", \"title\": \"Receive & Review\", \"bullets\": [\"Documents stored in encrypted cloud vault\", \"Download, share or forward to other services\", \"Rate your vendor to help other customers\", \"Renewal reminders sent before documents expire\"], \"mockup_docs\": [{\"name\": \"🏆 Golden Visa Certificate.pdf\", \"expires\": \"Expires 2031\"}, {\"name\": \"🪪 Emirates ID.pdf\", \"expires\": \"Expires 2028\"}, {\"name\": \"✈️ Entry Stamp.pdf\", \"expires\": \"Expires —\"}], \"mockup_label\": \"Document Vault\"}], \"cta_sub\": \"Browse 500+ verified vendors and find the right one for your UAE documentation needs — for free.\", \"hero_sub\": \"We\'ve simplified UAE bureaucracy into a transparent, guaranteed process — whether you need a visa, a trade license, or an Emirates ID.\", \"cta_title\": \"UAE Documentation, Simplified.\", \"cta_kicker\": \"Ready to Start?\", \"hero_title\": \"From Application to Approval in 4 Steps\", \"vendor_sub\": \"Becoming a verified vendor is simple. Get discovered by thousands of customers actively searching for your services.\", \"hero_kicker\": \"How It Works\", \"vendor_steps\": [{\"desc\": \"Submit your business details, license and certifications. Reviewed within 48 hours.\", \"icon\": \"bi-cloud-arrow-up\", \"step\": \"STEP 1\", \"title\": \"Apply & Get Verified\"}, {\"desc\": \"List services, set pricing, upload portfolio, and connect your availability.\", \"icon\": \"bi-globe2\", \"step\": \"STEP 2\", \"title\": \"Set Up Your Profile\"}, {\"desc\": \"Customers discover and book you directly. Respond and accept new orders.\", \"icon\": \"bi-file-earmark-text\", \"step\": \"STEP 3\", \"title\": \"Receive Orders\"}, {\"desc\": \"Update milestones, deliver outcomes, and get paid to your bank quickly.\", \"icon\": \"bi-arrow-repeat\", \"step\": \"STEP 4\", \"title\": \"Deliver & Get Paid\"}], \"vendor_title\": \"How Vendors Join & Grow\", \"vendor_kicker\": \"For Vendors\"}');
/*!40000 ALTER TABLE `cms_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `topic` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `reply_text` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landing_features`
--

DROP TABLE IF EXISTS `landing_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `landing_features` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `title` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_features`
--

LOCK TABLES `landing_features` WRITE;
/*!40000 ALTER TABLE `landing_features` DISABLE KEYS */;
INSERT INTO `landing_features` VALUES
(1,'631d450e-5d6b-4317-8379-27fd09f9fc05','Escrow Payments','Pay securely. Funds are held until you confirm satisfaction with your service.','bi-shield-check',1),
(2,'81f17006-0b2f-4176-a708-0a0cb15eed97','Real-Time Tracking','Watch your application progress live — every stage, every update, instantly.','bi-lightning',2),
(3,'46550107-3cd2-4223-b96f-b6b5649a96d2','Document Vault','Encrypted cloud storage for all your UAE documents, accessible anytime.','bi-lock',3),
(4,'27c0173a-86d7-458c-88d3-e502beac6e4c','WhatsApp Messaging','Chat directly with your vendor through encrypted in-app messenger.','bi-chat-dots',4),
(5,'9a00295e-a501-4485-ac78-a6c0d2182709','Vendor Analytics','Compare vendors by success rate, response time, reviews, and price.','bi-bar-chart',5),
(6,'c695054d-15f6-492f-99c9-a1fb454428c3','24/7 Support','Round-the-clock human support in English, Arabic, Hindi, and 10+ languages.','bi-headset',6);
/*!40000 ALTER TABLE `landing_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `url` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_menu_id` (`menu_id`),
  CONSTRAINT `fk_menu_items_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES
(1,1,NULL,'Services','services.php',1),
(2,1,NULL,'Vendors','vendors.php',2),
(3,1,NULL,'Pricing','pricing.php',3),
(4,1,NULL,'How It Works','how-it-works.php',4),
(5,1,NULL,'Insights','blog.php',5),
(6,1,NULL,'About','about.php',6),
(7,2,NULL,'Home','index.php',1),
(8,2,NULL,'About','about.php',2),
(9,2,NULL,'Services','services.php',3),
(10,2,NULL,'Case Studies','blog.php',4),
(11,2,NULL,'Pricing','pricing.php',5),
(12,2,NULL,'Insights','blog.php',6),
(13,2,NULL,'Contact','contact.php',7),
(14,3,NULL,'Golden Visa','services.php',1),
(15,3,NULL,'Business Setup','services.php',2),
(16,3,NULL,'Family Visa','services.php',3),
(17,3,NULL,'Emirates ID','services.php',4),
(18,3,NULL,'PRO Services','services.php',5),
(19,3,NULL,'Work Permit','services.php',6),
(20,4,NULL,'Terms & Conditions','#',1),
(21,4,NULL,'Privacy Policy','#',2),
(22,4,NULL,'Compliance','#',3),
(23,4,NULL,'License','#',4),
(24,4,NULL,'Style Guide','#',5),
(25,4,NULL,'Change Log','#',6),
(26,4,NULL,'Become a Partner','register-vendor.php',7);
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `location` (`location`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES
(1,'Header Menu','header'),
(2,'Footer Pages','footer_pages'),
(3,'Footer Services','footer_services'),
(4,'Footer Utility','footer_utility');
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_queue`
--

DROP TABLE IF EXISTS `onboarding_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` int(10) unsigned NOT NULL,
  `status` enum('pending','in_review','needs_info','approved','rejected') NOT NULL DEFAULT 'pending',
  `assigned_user_id` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_onboarding_provider` (`provider_id`),
  KEY `idx_onboarding_status` (`status`),
  KEY `idx_onboarding_assigned` (`assigned_user_id`),
  KEY `idx_onboarding_provider_status` (`provider_id`,`status`),
  CONSTRAINT `fk_onboarding_assigned_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_onboarding_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_queue`
--

LOCK TABLES `onboarding_queue` WRITE;
/*!40000 ALTER TABLE `onboarding_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registration_attempts`
--

DROP TABLE IF EXISTS `registration_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registration_attempts`
--

LOCK TABLES `registration_attempts` WRITE;
/*!40000 ALTER TABLE `registration_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `registration_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `label` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'6d210674-f11a-47ee-8d56-1118414805f4','users.view','Users: View','View user list and profiles','2026-01-12 11:50:49'),
(2,'86d60bc9-052b-4ca7-8b3f-404467663059','users.manage','Users: Manage','Create, update, delete users','2026-01-12 11:50:49'),
(3,'f9a3f9d9-01da-4060-8c8c-61def89e981e','roles.view','Roles: View','View roles and permissions','2026-01-12 11:50:49'),
(4,'fb14c333-f0bb-4767-aad6-d677088199d3','roles.manage','Roles: Manage','Create and edit roles and permissions','2026-01-12 11:50:49'),
(5,'27e66130-dec6-49bc-928a-287f22d6dbec','dashboard.view','Dashboard: View','Access dashboard','2026-01-12 11:50:49'),
(6,'c3f9f858-7410-4439-ac0d-07209964071e','settings.view','Settings: View','View global settings','2026-01-12 11:50:49'),
(7,'1eb4507c-360d-4c06-a713-a538c0af7c80','settings.manage','Settings: Manage','Change global configuration','2026-01-12 11:50:49'),
(8,'631d450e-5d6b-4317-8379-27fd09f9fc05','providers.view','Providers: View','View providers list and profiles','2026-01-12 12:41:24'),
(9,'81f17006-0b2f-4176-a708-0a0cb15eed97','providers.manage','Providers: Manage','Create, update, delete providers','2026-01-12 12:41:24'),
(10,'46550107-3cd2-4223-b96f-b6b5649a96d2','services.view','Services: View','View services and listings','2026-01-12 13:54:01'),
(11,'27c0173a-86d7-458c-88d3-e502beac6e4c','services.manage','Services: Manage','Create, edit, delete services','2026-01-12 13:54:01'),
(12,'9a00295e-a501-4485-ac78-a6c0d2182709','reviews.view','','View published reviews and moderation queue','2026-01-12 18:46:58'),
(13,'c695054d-15f6-492f-99c9-a1fb454428c3','reviews.manage','Review Control','Moderate, approve, hide, reject reviews','2026-01-12 18:46:58'),
(14,'39b51ae5-fe11-4b24-89fa-a9341406e55a','reviews.create_admin','','Create manual reviews from admin panel','2026-01-12 18:46:58'),
(17,'b761ef43-86f5-41d4-9315-cd18ce733f36','permissions.manage','Permissions: Manage','','2026-01-14 08:12:52'),
(18,'320330b9-02dd-4f12-994a-2801cda86c2f','cms.manage','CMS: Manage','Allows managing cms.manage','2026-07-13 09:03:27'),
(19,'f2f0433f-41e3-45f9-8216-555109636862','blog.manage','Blog: Manage','Allows managing blog.manage','2026-07-13 09:03:27'),
(20,'4dbbaaa3-3fc3-43cf-8682-023701b495a7','messages.manage','Contact Messages: Manage','Allows managing messages.manage','2026-07-13 09:03:27'),
(21,'3a2b7245-21d3-488b-a45e-b8d4f40cf12b','can_manage_ads','Manage Ads','Allows access to admin/settings/bot_ads.php and ad creation forms','2026-07-17 12:00:00'),
(22,'f43d22bf-829d-488c-9eb9-bcfd48383cb2','can_view_failed_queries','View Failed Queries','Allows access to admin/crm/failed-questions.php','2026-07-17 12:00:00'),
(23,'ad7e06e3-ab78-4061-9c3f-c906f21c22cb','can_edit_knowledge_base','Edit Knowledge Base','Allows access to our local PDF/text CRUD manager at admin/crm/knowledge-base.php','2026-07-17 12:00:00');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provider_onboarding`
--

DROP TABLE IF EXISTS `provider_onboarding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_onboarding` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` int(10) unsigned DEFAULT NULL,
  `owner_user_id` int(10) unsigned DEFAULT NULL,
  `step` enum('start','profile','documents','verification','complete','rejected') NOT NULL DEFAULT 'start',
  `progress` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`progress`)),
  `duplicate_check_status` enum('unchecked','possible_duplicate','no_duplicate') NOT NULL DEFAULT 'unchecked',
  `risk_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_onb_provider` (`provider_id`),
  KEY `idx_onb_owner` (`owner_user_id`),
  CONSTRAINT `fk_onb_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provider_onboarding`
--

LOCK TABLES `provider_onboarding` WRITE;
/*!40000 ALTER TABLE `provider_onboarding` DISABLE KEYS */;
/*!40000 ALTER TABLE `provider_onboarding` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provider_verification_logs`
--

DROP TABLE IF EXISTS `provider_verification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_verification_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` int(10) unsigned NOT NULL,
  `actor_user_id` int(10) unsigned DEFAULT NULL,
  `action` enum('submitted','admin_approved','admin_rejected','admin_requested_more') NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pvl_provider` (`provider_id`),
  CONSTRAINT `fk_pvl_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provider_verification_logs`
--

LOCK TABLES `provider_verification_logs` WRITE;
/*!40000 ALTER TABLE `provider_verification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `provider_verification_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `providers`
--

DROP TABLE IF EXISTS `providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `providers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `owner_user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','active','inactive') NOT NULL DEFAULT 'draft',
  `verification_status` enum('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
  `verification_docs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_docs`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `rating_avg` decimal(4,2) DEFAULT NULL,
  `rating_count` int(10) unsigned DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_by` int(10) unsigned DEFAULT NULL,
  `team_size` int(11) DEFAULT 1,
  `languages` varchar(255) DEFAULT 'English',
  `starting_price` decimal(10,2) DEFAULT NULL,
  `specialties` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_providers_city` (`city`),
  KEY `idx_providers_state` (`state`),
  KEY `idx_providers_country` (`country`),
  KEY `idx_providers_owner` (`owner_user_id`),
  KEY `idx_providers_rating_count` (`rating_count`),
  KEY `idx_providers_owner_user` (`owner_user_id`),
  KEY `idx_providers_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `providers`
--

LOCK TABLES `providers` WRITE;
/*!40000 ALTER TABLE `providers` DISABLE KEYS */;
INSERT INTO `providers` VALUES
(1,'59ee7db4-44e7-4254-8b97-8fbe6c289005',3,'GoProAlpha','goproalpha','tahir@example.com','03333092281','House #70-A 2nd floor, Shah Faisal Colony Block 2','Karachi','Sindh','Pakistan',NULL,NULL,'/public/uploads/providers/a0805473647f28e0281c6f32.png','Complete visa services','draft','unverified',NULL,'2026-01-14 08:22:05','2026-07-15 09:22:02',NULL,0,NULL,NULL,1,'English',NULL,NULL);
/*!40000 ALTER TABLE `providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_flags`
--

DROP TABLE IF EXISTS `review_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_flags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_flag_review` (`review_id`),
  CONSTRAINT `fk_flag_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_flags`
--

LOCK TABLES `review_flags` WRITE;
/*!40000 ALTER TABLE `review_flags` DISABLE KEYS */;
/*!40000 ALTER TABLE `review_flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_moderation_logs`
--

DROP TABLE IF EXISTS `review_moderation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_moderation_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` int(10) unsigned NOT NULL,
  `actor_user_id` int(10) unsigned DEFAULT NULL,
  `action` enum('approve','reject','hide','unhide','flag_spam') NOT NULL,
  `note` varchar(1024) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_review` (`review_id`),
  CONSTRAINT `fk_mod_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_moderation_logs`
--

LOCK TABLES `review_moderation_logs` WRITE;
/*!40000 ALTER TABLE `review_moderation_logs` DISABLE KEYS */;
INSERT INTO `review_moderation_logs` VALUES
(1,1,1,'approve','Created by admin id 1 as pending','2026-01-15 13:48:33'),
(2,2,1,'approve','Created by admin id 1 as pending','2026-01-16 07:51:04');
/*!40000 ALTER TABLE `review_moderation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `provider_id` int(10) unsigned DEFAULT NULL,
  `service_id` int(10) unsigned DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `status` enum('pending','published','hidden','rejected') NOT NULL DEFAULT 'pending',
  `helpful_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_user` (`user_id`),
  KEY `idx_provider` (`provider_id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_reviews_provider_status_created` (`provider_id`,`status`,`created_at`),
  KEY `idx_reviews_service_status_created` (`service_id`,`status`,`created_at`),
  CONSTRAINT `fk_review_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_review_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES
(1,'21020e21-284b-43c3-9b62-f1c30414f5d8',1,1,NULL,5,'Title','Body here','pending',0,'2026-01-15 13:48:33',NULL),
(2,'a273a233-0cdd-46da-863e-00e5e54aecc1',3,NULL,1,4,'some title','review description','pending',0,'2026-01-16 07:51:04',NULL);
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `idx_rp_role` (`role_id`),
  KEY `idx_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES
(1,1,'2026-01-14 08:01:08'),
(1,3,'2026-01-14 08:01:08'),
(1,4,'2026-01-14 08:01:08'),
(1,5,'2026-01-14 08:01:08'),
(1,6,'2026-01-14 08:01:08'),
(1,7,'2026-01-14 08:01:08'),
(1,8,'2026-01-14 08:01:08'),
(1,9,'2026-01-14 08:01:08'),
(1,10,'2026-01-14 08:01:08'),
(1,11,'2026-01-14 08:01:08'),
(1,12,'2026-01-14 08:01:08'),
(1,13,'2026-01-14 08:01:08'),
(1,14,'2026-01-14 08:01:08'),
(1,18,'2026-07-13 09:03:27'),
(1,19,'2026-07-13 09:03:27'),
(1,20,'2026-07-13 09:03:27'),
(1,21,'2026-07-17 12:00:00'),
(1,22,'2026-07-17 12:00:00'),
(1,23,'2026-07-17 12:00:00'),
(2,1,'2026-01-12 18:48:12'),
(2,2,'2026-01-12 18:48:12'),
(2,5,'2026-01-12 18:48:12'),
(2,6,'2026-01-12 18:48:12'),
(2,12,'2026-01-12 18:48:19'),
(2,13,'2026-01-12 18:48:19'),
(3,1,'2026-01-12 18:48:12'),
(3,3,'2026-01-12 18:48:12'),
(3,5,'2026-01-12 18:48:12'),
(3,6,'2026-01-12 18:48:12'),
(4,1,'2026-01-14 08:13:10'),
(4,2,'2026-01-14 08:13:10'),
(4,3,'2026-01-14 08:13:10'),
(4,4,'2026-01-14 08:13:10'),
(4,5,'2026-01-14 08:13:10'),
(4,6,'2026-01-14 08:13:10'),
(4,7,'2026-01-14 08:13:10'),
(4,8,'2026-01-14 08:13:10'),
(4,9,'2026-01-14 08:13:10'),
(4,10,'2026-01-14 08:13:10'),
(4,11,'2026-01-14 08:13:10'),
(4,12,'2026-01-14 08:13:10'),
(4,13,'2026-01-14 08:13:10'),
(4,14,'2026-01-14 08:13:10'),
(4,17,'2026-01-14 08:13:10'),
(4,18,'2026-07-13 09:03:27'),
(4,19,'2026-07-13 09:03:27'),
(4,20,'2026-07-13 09:03:27');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `label` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'20044733-5f4b-43be-9f1d-5b8f81db6173','admin','Administrator','Full system access','2026-01-12 11:50:49'),
(2,'a86cb5e4-752d-4519-a82b-58db6ba1a9d1','manager','Manager','Manage operations','2026-01-12 11:50:49'),
(3,'01741da9-0710-421e-8e02-5b572bfcab02','viewer','Viewer','Read-only access','2026-01-12 11:50:49'),
(4,'99e998fe-7b10-464e-ad7b-1391d8ef07b7','Super Admin','Full Control','This user controls all','2026-01-14 07:58:34'),
(5,'7c9e6679-dec5-4423-a5c9-9430c00d4567','provider','Provider','Service Provider Owner','2026-07-15 10:38:51');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_categories`
--

DROP TABLE IF EXISTS `service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_categories`
--

LOCK TABLES `service_categories` WRITE;
/*!40000 ALTER TABLE `service_categories` DISABLE KEYS */;
INSERT INTO `service_categories` VALUES
(1,'a0cdb1ff-ee84-4367-8125-75698f9910f3','Immigration Services','im','something here1','2026-01-15 13:37:18'),
(2,'ee8788ff-13d4-4ba7-a02e-888509070c05','Immigration','imm','some description','2026-01-16 07:44:41');
/*!40000 ALTER TABLE `service_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_tag_map`
--

DROP TABLE IF EXISTS `service_tag_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_tag_map` (
  `service_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`service_id`,`tag_id`),
  KEY `idx_stm_service` (`service_id`),
  KEY `idx_stm_tag` (`tag_id`),
  CONSTRAINT `fk_stm_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stm_tag` FOREIGN KEY (`tag_id`) REFERENCES `service_tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_tag_map`
--

LOCK TABLES `service_tag_map` WRITE;
/*!40000 ALTER TABLE `service_tag_map` DISABLE KEYS */;
INSERT INTO `service_tag_map` VALUES
(1,1);
/*!40000 ALTER TABLE `service_tag_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_tags`
--

DROP TABLE IF EXISTS `service_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_tags`
--

LOCK TABLES `service_tags` WRITE;
/*!40000 ALTER TABLE `service_tags` DISABLE KEYS */;
INSERT INTO `service_tags` VALUES
(1,'60bd89c8-a0bf-4407-8c3e-6a3bc9b1d7a4','Visa','2026-01-12 14:19:55'),
(2,'37821471-0b87-4d4a-8de6-fc6a91ce4312','Business','2026-01-15 13:31:52'),
(3,'968fd9f3-d26a-4faa-823a-e8797f9cbd45','xyz','2026-01-16 07:44:18');
/*!40000 ALTER TABLE `service_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `provider_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `duration_minutes` int(11) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `rating_avg` decimal(4,2) DEFAULT NULL,
  `rating_count` int(10) unsigned DEFAULT 0,
  `icon_class` varchar(100) DEFAULT 'bi-award',
  `duration_text` varchar(100) DEFAULT '5–7 days',
  `master_service_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_services_provider` (`provider_id`),
  KEY `idx_services_category` (`category_id`),
  KEY `idx_services_rating_count` (`rating_count`),
  KEY `idxservices_provider_status` (`provider_id`,`status`),
  KEY `fk_services_master_service` (`master_service_id`),
  CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_services_master_service` FOREIGN KEY (`master_service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_services_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES
(1,'8a38d478-ee47-424d-ba74-a02ca0ce6a6f',1,1,'Visa','visa','-','-',100.00,'USD',0,'[\"/public/uploads/services/c044f768e071b898b634edc2.png\"]','draft','2026-01-15 13:45:30','2026-07-15 09:09:20',NULL,0,'bi-award','5–7 days',10),
(2,'89345c61-7173-48a3-8373-3a020b08e678',1,1,'Golden Visa','golden-visa','Long-term residency for investors & talent','-',5000.00,'AED',0,NULL,'published','2026-07-13 09:12:43','2026-07-15 09:09:20',NULL,0,'bi-award','5–7 days',11),
(3,'e6233cb0-18e8-480b-899a-83fb62945eb8',1,1,'Business Setup','business-setup','Full company formation & bank account opening','-',8000.00,'AED',0,NULL,'published','2026-07-13 09:12:43','2026-07-15 09:09:20',NULL,0,'bi-building','3–5 days',12),
(4,'31f30d4b-f245-41e3-9469-a821ce1d73c8',1,1,'Family Visa','family-visa','Sponsor your family with streamlined processing','-',3000.00,'AED',0,NULL,'published','2026-07-13 09:12:43','2026-07-15 09:09:20',NULL,0,'bi-heart','5–7 days',13),
(5,'9e2d2f1a-34fe-4f9e-a0d2-89148c491258',1,1,'Emirates ID','emirates-id','National ID, biometrics & renewal','-',500.00,'AED',0,NULL,'published','2026-07-13 09:12:43','2026-07-15 09:09:20',NULL,0,'bi-credit-card','2–3 days',14),
(6,'3c47c214-165a-4332-a5b1-b3d7456ddc56',1,1,'PRO Services','pro-services','Government liaison, attestation & stamping','-',1500.00,'AED',0,NULL,'published','2026-07-13 09:12:43','2026-07-15 09:09:20',NULL,0,'bi-clipboard-check','1–2 days',15),
(7,'32ac1800-2b9c-4aac-9a24-1aeac6e177c5',1,1,'Work Permit','work-permit','Employment visa for all nationalities & sectors','-',2500.00,'AED',0,NULL,'published','2026-07-13 09:12:43','2026-07-15 09:09:20',NULL,0,'bi-person-hard-hat','4–6 days',16),
(8,'426e4e8e-8674-426e-ad0a-ad79c797381b',1,1,'Mainland License','mainland-license','Trade license & mainland company formation','-',12000.00,'AED',0,NULL,'published','2026-07-13 09:12:43','2026-07-15 09:09:20',NULL,0,'bi-shop','7–10 days',17),
(9,'bbd4df6c-19ed-4b02-995c-1cbe47e63830',1,1,'Free Zone Setup','free-zone-setup','100% foreign ownership in 40+ free zones','-',9500.00,'AED',0,NULL,'published','2026-07-13 09:12:43','2026-07-15 09:09:20',NULL,0,'bi-bank','5–7 days',18),
(10,'38fa54d2-1907-437a-b0b8-b7985c0ace37',NULL,1,'Visa','master-visa','-','-',NULL,'USD',NULL,'[\"/public/uploads/services/c044f768e071b898b634edc2.png\"]','draft','2026-07-15 09:09:20',NULL,NULL,0,'bi-award','5–7 days',NULL),
(11,'62d6ba4e-d72c-439a-814d-711c689bd7f0',NULL,1,'Golden Visa','master-golden-visa','Long-term residency for investors & talent','-',NULL,'USD',NULL,'[]','published','2026-07-15 09:09:20',NULL,NULL,0,'bi-award','5–7 days',NULL),
(12,'19653018-f9ba-41ba-93b5-cc6dccb210c2',NULL,1,'Business Setup','master-business-setup','Full company formation & bank account opening','-',NULL,'USD',NULL,'[]','published','2026-07-15 09:09:20',NULL,NULL,0,'bi-building','3–5 days',NULL),
(13,'592561ad-bb63-4d01-aeab-d5d1b64481df',NULL,1,'Family Visa','master-family-visa','Sponsor your family with streamlined processing','-',NULL,'USD',NULL,'[]','published','2026-07-15 09:09:20',NULL,NULL,0,'bi-heart','5–7 days',NULL),
(14,'50a742b1-bf51-4a6f-a15e-2cd47f5e4b7a',NULL,1,'Emirates ID','master-emirates-id','National ID, biometrics & renewal','-',NULL,'USD',NULL,'[]','published','2026-07-15 09:09:20',NULL,NULL,0,'bi-credit-card','2–3 days',NULL),
(15,'9d08b95c-41c6-4b3b-a2b1-e6c0b293ff1e',NULL,1,'PRO Services','master-pro-services','Government liaison, attestation & stamping','-',NULL,'USD',NULL,'[]','published','2026-07-15 09:09:20',NULL,NULL,0,'bi-clipboard-check','1–2 days',NULL),
(16,'c152b6f7-daa9-477a-b19a-bc5ff75aa28f',NULL,1,'Work Permit','master-work-permit','Employment visa for all nationalities & sectors','-',NULL,'USD',NULL,'[]','published','2026-07-15 09:09:20',NULL,NULL,0,'bi-person-hard-hat','4–6 days',NULL),
(17,'64d0a0bb-8339-4f1d-8ab7-b9e449e9c32f',NULL,1,'Mainland License','master-mainland-license','Trade license & mainland company formation','-',NULL,'USD',NULL,'[]','published','2026-07-15 09:09:20',NULL,NULL,0,'bi-shop','7–10 days',NULL),
(18,'118bc7f6-195c-4ac3-aa5f-67a66f1eca27',NULL,1,'Free Zone Setup','master-free-zone-setup','100% foreign ownership in 40+ free zones','-',NULL,'USD',NULL,'[]','published','2026-07-15 09:09:20',NULL,NULL,0,'bi-bank','5–7 days',NULL);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `type` enum('text','longtext','image','url') DEFAULT 'text',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES
(1,'hero_title','Your Ultimate UAE Marketplace for Documentation & Advisory','Hero Title','text'),
(2,'hero_subtitle','Access a vetted network of top-tier legal, business, and administrative professionals. Secure escrow payments, live tracking, and verified results.','Hero Subtitle','longtext'),
(3,'hero_bg_image','https://images.unsplash.com/photo-1582653280643-e395afdf1f1c?w=1400&q=85','Hero Background','image'),
(4,'hero_cta_text','Explore Services','Hero CTA Text','text'),
(5,'hero_cta_url','services.php','Hero CTA URL','url'),
(6,'stat_vendors','150+','Stats: Vendors','text'),
(7,'stat_cases','10K+','Stats: Cases','text'),
(8,'stat_success_rate','99.8%','Stats: Success Rate','text'),
(9,'cta_banner_title','Ready to take your UAE journey with us!','CTA Banner Title','text'),
(10,'cta_banner_bg','https://images.unsplash.com/photo-1539630417222-d685b659ffcc?w=1400&q=85','CTA Banner Background','image'),
(11,'contact_address','GlobalWays Advisory\nDubai International Financial Centre\nLevel 6, Gate Avenue\nDubai, UAE 000001','HQ Address','longtext'),
(12,'contact_email','hello@globalways.ae','Contact Email','text'),
(13,'social_facebook','#','Facebook URL','url'),
(14,'social_linkedin','#','LinkedIn URL','url'),
(15,'social_instagram','#','Instagram URL','url'),
(16,'social_behance','#','Behance URL','url'),
(17,'footer_newsletter_text','Exclusive 20% discount when you sign up','Newsletter Text','text'),
(18,'footer_disclaimer','GlobalWays is a trusted marketplace built for individuals and companies navigating UAE documentation complexity that comes with growth. We work with government-approved vendors who have moved beyond experimentation and now face the challenge of scaling processes, teams, and compliance without losing control. Our approach is execution-led, verified, and grounded in operational reality — not theory. We focus on simplifying ownership, strengthening systems, and aligning leadership around what actually moves your UAE journey forward.','Footer Disclaimer','longtext'),
(19,'hero_title_gradient','Measurable','Hero Title Gradient Word','text'),
(20,'hero_title_rest','Performance for businesses','Hero Title Rest of Text','text'),
(21,'trust_bar_partners','Dubai Economy, GDRFA, Ministry of Labour, MOHRE, AMER Centers, Tas\'heel, ICP UAE','Trust Bar Partners (comma-separated)','text'),
(22,'stat_result_label','Consultancy Result','Stat Section Label','text'),
(23,'stat_result_heading_gradient','99.8%','Stat Heading Gradient Word','text'),
(24,'stat_result_heading_rest','success rate across every UAE service. Once we verify your vendor, track your application, and secure your payment — friction disappears.','Stat Heading Rest of Text','longtext'),
(25,'stat_card1_number','500+','Stat Card 1 Number','text'),
(26,'stat_card1_label','Verified Partners','Stat Card 1 Label','text'),
(27,'stat_card1_desc','By connecting you with verified vendors, removing redundant searches, and aligning your needs around a unified marketplace model.','Stat Card 1 Description','longtext'),
(28,'stat_card2_number','3x','Stat Card 2 Number','text'),
(29,'stat_card2_label','Faster Processing','Stat Card 2 Label','text'),
(30,'stat_card2_desc','Our framework reduces ambiguity and brings clarity to every layer of the application.','Stat Card 2 Description','longtext'),
(31,'stat_card3_number','150+','Stat Card 3 Number','text'),
(32,'stat_card3_label','Supported Globally','Stat Card 3 Label','text'),
(33,'stat_card3_desc','We\'ve worked with customers across SaaS, fintech, agencies, and high-growth companies worldwide.','Stat Card 3 Description','longtext');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `testimonials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `client_name` varchar(191) NOT NULL,
  `client_role` varchar(191) DEFAULT NULL,
  `client_location` varchar(191) DEFAULT NULL,
  `quote` text DEFAULT NULL,
  `avatar_text` varchar(10) DEFAULT NULL,
  `stars` tinyint(4) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonials`
--

LOCK TABLES `testimonials` WRITE;
/*!40000 ALTER TABLE `testimonials` DISABLE KEYS */;
INSERT INTO `testimonials` VALUES
(1,'e1346380-60b7-4180-879e-716801933f7c','Ahmed Al-Rashidi','Head of Product','Dubai','Their structured approach cut our operational waste in half. Golden Visa processed in 5 days.','AH',5,1,'2026-07-13 09:03:15'),
(2,'3587db44-05e8-406e-8e02-db6ba1a9d180','Sarah Thompson','Business Owner','Abu Dhabi','Set up my mainland company in under a week. Escrow payment gave me total peace of mind.','ST',5,1,'2026-07-13 09:03:15'),
(3,'0fc59ff7-5304-4be7-a0f9-36c388d783d1','Priya Sharma','HR Director','Sharjah','Managing 30+ employee visas has never been easier. Saves our team 10+ hours every month.','PS',5,1,'2026-07-13 09:03:15');
/*!40000 ALTER TABLE `testimonials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_invites`
--

DROP TABLE IF EXISTS `user_invites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_invites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` char(64) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `fk_invite_user` (`user_id`),
  CONSTRAINT `fk_invite_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_invites`
--

LOCK TABLES `user_invites` WRITE;
/*!40000 ALTER TABLE `user_invites` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_invites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `idx_user_roles_user` (`user_id`),
  KEY `idx_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES
(1,4,'2026-01-12 11:50:49'),
(3,1,'2026-01-16 07:43:51');
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `nationality` varchar(100) DEFAULT NULL,
  `goal` varchar(100) DEFAULT NULL,
  `emirate` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'d82ee114-2e59-441c-871b-c037d552dc44','Admin','tahir@goproalpha.com','$2y$10$r1B0a3FsLhNNZIGUg.R1i.RdkQ46oYAnnFBhnMAe0rpCyppA7y5VO',NULL,NULL,NULL,'2026-07-06 18:37:15','2026-01-12 11:50:49','2026-07-06 13:37:15',NULL,NULL,NULL),
(3,'fad6d2b3-700f-44dc-96f3-8a3a1a5809c2','Khurram','khurram@goproalpha.com','$2y$10$M2gQgMQ4i9z674RB0zY.3.1FhhAotqA6bXlYm96Pi4HEZ5aQumsS2','/public/uploads/avatars/f56f9be74aa815aec3fbabfa.png',NULL,NULL,NULL,'2026-01-16 07:43:51','2026-01-16 07:43:51',NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

--
-- Table structure for table `provider_documents`
--

DROP TABLE IF EXISTS `provider_documents`;
CREATE TABLE `provider_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `provider_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `show_on_frontend` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `fk_provider_documents_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `provider_team_members`
--

DROP TABLE IF EXISTS `provider_team_members`;
CREATE TABLE `provider_team_members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `provider_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `specialties` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `fk_provider_team_members_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `cases`
--

DROP TABLE IF EXISTS `cases`;
CREATE TABLE `cases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `customer_user_id` int(10) unsigned NOT NULL,
  `provider_id` int(10) unsigned NOT NULL,
  `service_id` int(10) unsigned NOT NULL,
  `status` enum('Pending','Quoted','Booked','Declined') NOT NULL DEFAULT 'Pending',
  `customer_message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `customer_user_id` (`customer_user_id`),
  KEY `provider_id` (`provider_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `fk_cases_customer` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cases_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cases_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `payment_gateways`
--

DROP TABLE IF EXISTS `payment_gateways`;
CREATE TABLE `payment_gateways` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `public_key` varchar(255) DEFAULT NULL,
  `secret_key` varchar(255) DEFAULT NULL,
  `sandbox_mode` tinyint(1) NOT NULL DEFAULT 1,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_gateways`
--

LOCK TABLES `payment_gateways` WRITE;
INSERT INTO `payment_gateways` (`id`, `name`, `public_key`, `secret_key`, `sandbox_mode`, `is_enabled`) VALUES
(1,'Stripe','pk_test_mock','sk_test_mock',1,1),
(2,'PayPal','pk_test_mock','sk_test_mock',1,1),
(3,'Authorize.net','pk_test_mock','sk_test_mock',1,1);
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `bot_ads`
--

DROP TABLE IF EXISTS `bot_ads`;
CREATE TABLE `bot_ads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_name` varchar(255) NOT NULL,
  `ad_source_type` enum('direct_sponsor','network_programmatic') NOT NULL,
  `placement_zone` enum('bot_internal_chat','site_header_leaderboard','site_sidebar_banner','site_footer_banner') NOT NULL,
  `target_page_context` varchar(255) DEFAULT 'global_fallback',
  `target_category_id` int(10) unsigned DEFAULT NULL,
  `language_iso` varchar(10) NOT NULL DEFAULT 'en',
  `banner_text` text DEFAULT NULL,
  `audio_speech_text` text DEFAULT NULL,
  `destination_url` varchar(255) DEFAULT NULL,
  `network_script_code` longtext DEFAULT NULL,
  `click_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_budget` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_spend` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ad_billing_model` enum('ppc','ppi','flat_rate_temporal') NOT NULL DEFAULT 'ppc',
  `max_impressions` int(10) unsigned NOT NULL DEFAULT 0,
  `current_impressions` int(10) unsigned NOT NULL DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `target_category_id` (`target_category_id`),
  CONSTRAINT `fk_bot_ads_category` FOREIGN KEY (`target_category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `bot_ad_clicks`
--

DROP TABLE IF EXISTS `bot_ad_clicks`;
CREATE TABLE `bot_ad_clicks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ad_id` int(10) unsigned NOT NULL,
  `session_id` int(10) unsigned DEFAULT NULL,
  `earned_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `clicked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ad_id` (`ad_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `fk_bot_ad_clicks_ad` FOREIGN KEY (`ad_id`) REFERENCES `bot_ads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bot_ad_clicks_session` FOREIGN KEY (`session_id`) REFERENCES `bot_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dump completed on 2026-07-15  9:42:30
