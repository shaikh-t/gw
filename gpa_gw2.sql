-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 06, 2026 at 02:15 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gpa_gw2`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int UNSIGNED DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_actor` (`actor_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_queue`
--

DROP TABLE IF EXISTS `onboarding_queue`;
CREATE TABLE IF NOT EXISTS `onboarding_queue` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` int UNSIGNED NOT NULL,
  `status` enum('pending','in_review','needs_info','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assigned_user_id` int UNSIGNED DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_onboarding_provider` (`provider_id`),
  KEY `idx_onboarding_status` (`status`),
  KEY `idx_onboarding_assigned` (`assigned_user_id`),
  KEY `idx_onboarding_provider_status` (`provider_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `label`, `description`, `created_at`) VALUES
(1, 'users.view', 'Users: View', 'View user list and profiles', '2026-01-12 11:50:49'),
(2, 'users.manage', 'Users: Manage', 'Create, update, delete users', '2026-01-12 11:50:49'),
(3, 'roles.view', 'Roles: View', 'View roles and permissions', '2026-01-12 11:50:49'),
(4, 'roles.manage', 'Roles: Manage', 'Create and edit roles and permissions', '2026-01-12 11:50:49'),
(5, 'dashboard.view', 'Dashboard: View', 'Access dashboard', '2026-01-12 11:50:49'),
(6, 'settings.view', 'Settings: View', 'View global settings', '2026-01-12 11:50:49'),
(7, 'settings.manage', 'Settings: Manage', 'Change global configuration', '2026-01-12 11:50:49'),
(8, 'providers.view', 'Providers: View', 'View providers list and profiles', '2026-01-12 12:41:24'),
(9, 'providers.manage', 'Providers: Manage', 'Create, update, delete providers', '2026-01-12 12:41:24'),
(10, 'services.view', 'Services: View', 'View services and listings', '2026-01-12 13:54:01'),
(11, 'services.manage', 'Services: Manage', 'Create, edit, delete services', '2026-01-12 13:54:01'),
(12, 'reviews.view', '', 'View published reviews and moderation queue', '2026-01-12 18:46:58'),
(13, 'reviews.manage', 'Review Control', 'Moderate, approve, hide, reject reviews', '2026-01-12 18:46:58'),
(14, 'reviews.create_admin', '', 'Create manual reviews from admin panel', '2026-01-12 18:46:58'),
(17, 'permissions.manage', 'Permissions: Manage', '', '2026-01-14 08:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

DROP TABLE IF EXISTS `providers`;
CREATE TABLE IF NOT EXISTS `providers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `verification_status` enum('unverified','pending','verified','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unverified',
  `verification_docs` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `rating_avg` decimal(4,2) DEFAULT NULL,
  `rating_count` int UNSIGNED DEFAULT '0',
  `settings` json DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_providers_city` (`city`),
  KEY `idx_providers_state` (`state`),
  KEY `idx_providers_country` (`country`),
  KEY `idx_providers_owner` (`owner_user_id`),
  KEY `idx_providers_rating_count` (`rating_count`),
  KEY `idx_providers_owner_user` (`owner_user_id`),
  KEY `idx_providers_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `owner_user_id`, `name`, `slug`, `email`, `phone`, `address`, `city`, `state`, `country`, `latitude`, `longitude`, `logo`, `description`, `status`, `verification_status`, `verification_docs`, `created_at`, `updated_at`, `rating_avg`, `rating_count`, `settings`, `created_by`) VALUES
(1, 0, 'GoProAlpha', 'goproalpha', 'tahir@example.com', '03333092281', 'House #70-A 2nd floor, Shah Faisal Colony Block 2', 'Karachi', 'Sindh', 'Pakistan', NULL, NULL, '/public/uploads/providers/a0805473647f28e0281c6f32.png', 'Complete visa services', 'draft', 'unverified', NULL, '2026-01-14 08:22:05', NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `provider_onboarding`
--

DROP TABLE IF EXISTS `provider_onboarding`;
CREATE TABLE IF NOT EXISTS `provider_onboarding` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` int UNSIGNED DEFAULT NULL,
  `owner_user_id` int UNSIGNED DEFAULT NULL,
  `step` enum('start','profile','documents','verification','complete','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'start',
  `progress` json DEFAULT NULL,
  `duplicate_check_status` enum('unchecked','possible_duplicate','no_duplicate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unchecked',
  `risk_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_onb_provider` (`provider_id`),
  KEY `idx_onb_owner` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_verification_logs`
--

DROP TABLE IF EXISTS `provider_verification_logs`;
CREATE TABLE IF NOT EXISTS `provider_verification_logs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` int UNSIGNED NOT NULL,
  `actor_user_id` int UNSIGNED DEFAULT NULL,
  `action` enum('submitted','admin_approved','admin_rejected','admin_requested_more') COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pvl_provider` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `provider_id` int UNSIGNED DEFAULT NULL,
  `service_id` int UNSIGNED DEFAULT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','published','hidden','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `helpful_count` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_provider` (`provider_id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_reviews_provider_status_created` (`provider_id`,`status`,`created_at`),
  KEY `idx_reviews_service_status_created` (`service_id`,`status`,`created_at`)
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `provider_id`, `service_id`, `rating`, `title`, `body`, `status`, `helpful_count`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 5, 'Title', 'Body here', 'pending', 0, '2026-01-15 13:48:33', NULL),
(2, 3, NULL, 1, 4, 'some title', 'review description', 'pending', 0, '2026-01-16 07:51:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `review_flags`
--

DROP TABLE IF EXISTS `review_flags`;
CREATE TABLE IF NOT EXISTS `review_flags` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `review_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_flag_review` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_moderation_logs`
--

DROP TABLE IF EXISTS `review_moderation_logs`;
CREATE TABLE IF NOT EXISTS `review_moderation_logs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `review_id` int UNSIGNED NOT NULL,
  `actor_user_id` int UNSIGNED DEFAULT NULL,
  `action` enum('approve','reject','hide','unhide','flag_spam') COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_review` (`review_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `review_moderation_logs`
--

INSERT INTO `review_moderation_logs` (`id`, `review_id`, `actor_user_id`, `action`, `note`, `created_at`) VALUES
(1, 1, 1, 'approve', 'Created by admin id 1 as pending', '2026-01-15 13:48:33'),
(2, 2, 1, 'approve', 'Created by admin id 1 as pending', '2026-01-16 07:51:04');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `label`, `description`, `created_at`) VALUES
(1, 'admin', 'Administrator', 'Full system access', '2026-01-12 11:50:49'),
(2, 'manager', 'Manager', 'Manage operations', '2026-01-12 11:50:49'),
(3, 'viewer', 'Viewer', 'Read-only access', '2026-01-12 11:50:49'),
(4, 'Super Admin', 'Full Control', 'This user controls all', '2026-01-14 07:58:34');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `idx_rp_role` (`role_id`),
  KEY `idx_rp_permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 1, '2026-01-14 08:01:08'),
(1, 3, '2026-01-14 08:01:08'),
(1, 4, '2026-01-14 08:01:08'),
(1, 5, '2026-01-14 08:01:08'),
(1, 6, '2026-01-14 08:01:08'),
(1, 7, '2026-01-14 08:01:08'),
(1, 8, '2026-01-14 08:01:08'),
(1, 9, '2026-01-14 08:01:08'),
(1, 10, '2026-01-14 08:01:08'),
(1, 11, '2026-01-14 08:01:08'),
(1, 12, '2026-01-14 08:01:08'),
(1, 13, '2026-01-14 08:01:08'),
(1, 14, '2026-01-14 08:01:08'),
(2, 1, '2026-01-12 18:48:12'),
(2, 2, '2026-01-12 18:48:12'),
(2, 5, '2026-01-12 18:48:12'),
(2, 6, '2026-01-12 18:48:12'),
(2, 12, '2026-01-12 18:48:19'),
(2, 13, '2026-01-12 18:48:19'),
(3, 1, '2026-01-12 18:48:12'),
(3, 3, '2026-01-12 18:48:12'),
(3, 5, '2026-01-12 18:48:12'),
(3, 6, '2026-01-12 18:48:12'),
(4, 1, '2026-01-14 08:13:10'),
(4, 2, '2026-01-14 08:13:10'),
(4, 3, '2026-01-14 08:13:10'),
(4, 4, '2026-01-14 08:13:10'),
(4, 5, '2026-01-14 08:13:10'),
(4, 6, '2026-01-14 08:13:10'),
(4, 7, '2026-01-14 08:13:10'),
(4, 8, '2026-01-14 08:13:10'),
(4, 9, '2026-01-14 08:13:10'),
(4, 10, '2026-01-14 08:13:10'),
(4, 11, '2026-01-14 08:13:10'),
(4, 12, '2026-01-14 08:13:10'),
(4, 13, '2026-01-14 08:13:10'),
(4, 14, '2026-01-14 08:13:10'),
(4, 17, '2026-01-14 08:13:10');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `duration_minutes` int DEFAULT NULL,
  `images` json DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `rating_avg` decimal(4,2) DEFAULT NULL,
  `rating_count` int UNSIGNED DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_services_provider` (`provider_id`),
  KEY `idx_services_category` (`category_id`),
  KEY `idx_services_rating_count` (`rating_count`),
  KEY `idxservices_provider_status` (`provider_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `provider_id`, `category_id`, `title`, `slug`, `short_description`, `description`, `price`, `currency`, `duration_minutes`, `images`, `status`, `created_at`, `updated_at`, `rating_avg`, `rating_count`) VALUES
(1, 1, 1, 'Visa', 'visa', '-', '-', 100.00, 'USD', 0, '[\"/public/uploads/services/c044f768e071b898b634edc2.png\"]', 'draft', '2026-01-15 13:45:30', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

DROP TABLE IF EXISTS `service_categories`;
CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Immigration Services', 'im', 'something here1', '2026-01-15 13:37:18'),
(2, 'Immigration', 'imm', 'some description', '2026-01-16 07:44:41');

-- --------------------------------------------------------

--
-- Table structure for table `service_tags`
--

DROP TABLE IF EXISTS `service_tags`;
CREATE TABLE IF NOT EXISTS `service_tags` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_tags`
--

INSERT INTO `service_tags` (`id`, `name`, `created_at`) VALUES
(1, 'Visa', '2026-01-12 14:19:55'),
(2, 'Business', '2026-01-15 13:31:52'),
(3, 'xyz', '2026-01-16 07:44:18');

-- --------------------------------------------------------

--
-- Table structure for table `service_tag_map`
--

DROP TABLE IF EXISTS `service_tag_map`;
CREATE TABLE IF NOT EXISTS `service_tag_map` (
  `service_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`service_id`,`tag_id`),
  KEY `idx_stm_service` (`service_id`),
  KEY `idx_stm_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_tag_map`
--

INSERT INTO `service_tag_map` (`service_id`, `tag_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `avatar`, `phone`, `bio`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'tahir@goproalpha.com', '$2y$10$r1B0a3FsLhNNZIGUg.R1i.RdkQ46oYAnnFBhnMAe0rpCyppA7y5VO', NULL, NULL, NULL, '2026-07-06 18:37:15', '2026-01-12 11:50:49', '2026-07-06 13:37:15'),
(3, 'Khurram', 'khurram@goproalpha.com', '$2y$10$M2gQgMQ4i9z674RB0zY.3.1FhhAotqA6bXlYm96Pi4HEZ5aQumsS2', '/public/uploads/avatars/f56f9be74aa815aec3fbabfa.png', NULL, NULL, NULL, '2026-01-16 07:43:51', '2026-01-16 07:43:51');

-- --------------------------------------------------------

--
-- Table structure for table `user_invites`
--

DROP TABLE IF EXISTS `user_invites`;
CREATE TABLE IF NOT EXISTS `user_invites` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `fk_invite_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `idx_user_roles_user` (`user_id`),
  KEY `idx_user_roles_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_at`) VALUES
(1, 4, '2026-01-12 11:50:49'),
(3, 1, '2026-01-16 07:43:51');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `onboarding_queue`
--
ALTER TABLE `onboarding_queue`
  ADD CONSTRAINT `fk_onboarding_assigned_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_onboarding_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `provider_onboarding`
--
ALTER TABLE `provider_onboarding`
  ADD CONSTRAINT `fk_onb_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `provider_verification_logs`
--
ALTER TABLE `provider_verification_logs`
  ADD CONSTRAINT `fk_pvl_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `review_flags`
--
ALTER TABLE `review_flags`
  ADD CONSTRAINT `fk_flag_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `review_moderation_logs`
--
ALTER TABLE `review_moderation_logs`
  ADD CONSTRAINT `fk_mod_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_services_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `service_tag_map`
--
ALTER TABLE `service_tag_map`
  ADD CONSTRAINT `fk_stm_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stm_tag` FOREIGN KEY (`tag_id`) REFERENCES `service_tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_invites`
--
ALTER TABLE `user_invites`
  ADD CONSTRAINT `fk_invite_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
