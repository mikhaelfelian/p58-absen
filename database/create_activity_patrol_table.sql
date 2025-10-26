-- Create activity_patrol table for QR code validation
CREATE TABLE IF NOT EXISTS `activity_patrol` (
  `id_activity_patrol` int(11) NOT NULL AUTO_INCREMENT,
  `id_activity` int(11) NOT NULL,
  `id_patrol` int(11) NOT NULL,
  `barcode_scanned` varchar(100) NOT NULL,
  `scan_time` datetime NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_activity_patrol`),
  KEY `idx_activity` (`id_activity`),
  KEY `idx_patrol` (`id_patrol`),
  KEY `idx_barcode` (`barcode_scanned`),
  CONSTRAINT `fk_activity_patrol_activity` FOREIGN KEY (`id_activity`) REFERENCES `activity` (`id_activity`) ON DELETE CASCADE,
  CONSTRAINT `fk_activity_patrol_patrol` FOREIGN KEY (`id_patrol`) REFERENCES `company_patrol` (`id_patrol`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
