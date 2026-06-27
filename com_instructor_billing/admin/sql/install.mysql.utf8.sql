CREATE TABLE IF NOT EXISTS `#__instructor_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `phone` varchar(40) NULL DEFAULT NULL,
  `sage_contact_id` varchar(80) NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_instructor_profiles_user_id` (`user_id`),
  KEY `idx_instructor_profiles_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__driving_sessions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `instructor_user_id` int unsigned NOT NULL,
  `student_name` varchar(255) NULL DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NULL DEFAULT NULL,
  `duration_minutes` int unsigned NOT NULL DEFAULT 0,
  `start_lat` decimal(10,7) NULL DEFAULT NULL,
  `start_lng` decimal(10,7) NULL DEFAULT NULL,
  `end_lat` decimal(10,7) NULL DEFAULT NULL,
  `end_lng` decimal(10,7) NULL DEFAULT NULL,
  `notes` text NULL DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `approved_by` int unsigned NULL DEFAULT NULL,
  `approved_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_driving_sessions_instructor` (`instructor_user_id`),
  KEY `idx_driving_sessions_period` (`start_time`, `end_time`),
  KEY `idx_driving_sessions_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__gps_points` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int unsigned NOT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  `recorded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gps_points_session` (`session_id`),
  CONSTRAINT `fk_gps_points_session` FOREIGN KEY (`session_id`) REFERENCES `#__driving_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(60) NOT NULL,
  `instructor_user_id` int unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `sage_invoice_id` varchar(80) NULL DEFAULT NULL,
  `sage_invoice_number` varchar(80) NULL DEFAULT NULL,
  `sage_synced_at` datetime NULL DEFAULT NULL,
  `sage_sync_status` varchar(30) NULL DEFAULT NULL,
  `sage_sync_error` text NULL DEFAULT NULL,
  `created_by` int unsigned NULL DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_invoices_invoice_number` (`invoice_number`),
  KEY `idx_invoices_instructor` (`instructor_user_id`),
  KEY `idx_invoices_period` (`period_start`, `period_end`),
  KEY `idx_invoices_status` (`status`),
  KEY `idx_invoices_sage_status` (`sage_sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__invoice_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int unsigned NOT NULL,
  `session_id` int unsigned NULL DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity_hours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_items_invoice` (`invoice_id`),
  KEY `idx_invoice_items_session` (`session_id`),
  CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__billing_audit_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NULL DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` int unsigned NULL DEFAULT NULL,
  `details` text NULL DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_billing_audit_user` (`user_id`),
  KEY `idx_billing_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_billing_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__instructor_billing_sage_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text NULL DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_sage_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
