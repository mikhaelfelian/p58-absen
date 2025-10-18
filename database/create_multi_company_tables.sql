-- ============================================================
-- CREATE MULTI-COMPANY TABLES
-- Database: db_p58_absen
-- Created: 2025-10-18
-- ============================================================

USE `db_p58_absen`;

-- ============================================================
-- 1. CREATE COMPANY TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS `company` (
  `id_company` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_company` VARCHAR(255) NOT NULL,
  `alamat` TEXT DEFAULT NULL,
  `id_wilayah_kelurahan` INT(10) UNSIGNED DEFAULT NULL,
  `latitude` VARCHAR(50) DEFAULT NULL,
  `longitude` VARCHAR(50) DEFAULT NULL,
  `radius_nilai` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `radius_satuan` ENUM('m','km') NOT NULL DEFAULT 'km',
  `email` VARCHAR(255) DEFAULT NULL,
  `no_telp` VARCHAR(50) DEFAULT NULL,
  `contact_person` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `keterangan` TEXT DEFAULT NULL,
  `id_user_input` INT(10) UNSIGNED DEFAULT NULL,
  `tgl_input` DATETIME DEFAULT NULL,
  `id_user_update` INT(10) UNSIGNED DEFAULT NULL,
  `tgl_update` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_company`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 2. CREATE USER_COMPANY TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS `user_company` (
  `id_user_company` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` INT(10) UNSIGNED NOT NULL,
  `id_company` INT(10) UNSIGNED NOT NULL,
  `tanggal_mulai` DATE DEFAULT NULL,
  `tanggal_selesai` DATE DEFAULT NULL,
  `status` ENUM('active','inactive','completed') NOT NULL DEFAULT 'active',
  `keterangan` TEXT DEFAULT NULL,
  `id_user_input` INT(10) UNSIGNED DEFAULT NULL,
  `tgl_input` DATETIME DEFAULT NULL,
  `id_user_update` INT(10) UNSIGNED DEFAULT NULL,
  `tgl_update` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_user_company`),
  KEY `id_user` (`id_user`),
  KEY `id_company` (`id_company`),
  KEY `status` (`status`),
  CONSTRAINT `fk_user_company_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_company_company` FOREIGN KEY (`id_company`) REFERENCES `company` (`id_company`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. CREATE ACTIVITY TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS `activity` (
  `id_activity` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` INT(10) UNSIGNED NOT NULL,
  `id_company` INT(10) UNSIGNED NOT NULL,
  `id_user_presensi` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Reference to attendance record',
  `tanggal` DATE NOT NULL,
  `waktu` TIME NOT NULL,
  `judul_activity` VARCHAR(255) NOT NULL,
  `deskripsi_activity` TEXT NOT NULL,
  `foto_activity` VARCHAR(255) DEFAULT NULL,
  `latitude` VARCHAR(50) DEFAULT NULL,
  `longitude` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` INT(10) UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_activity`),
  KEY `id_user` (`id_user`),
  KEY `id_company` (`id_company`),
  KEY `tanggal` (`tanggal`),
  KEY `status` (`status`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_activity_company` FOREIGN KEY (`id_company`) REFERENCES `company` (`id_company`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_activity_presensi` FOREIGN KEY (`id_user_presensi`) REFERENCES `user_presensi` (`id_user_presensi`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 4. ADD COMPANY COLUMN TO USER_PRESENSI
-- ============================================================

-- Check if column exists first
SET @dbname = DATABASE();
SET @tablename = 'user_presensi';
SET @columnname = 'id_company';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT(10) UNSIGNED NULL AFTER id_user')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key if not exists
SET @fkname = 'fk_user_presensi_company';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (constraint_name = @fkname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD CONSTRAINT ', @fkname, ' FOREIGN KEY (id_company) REFERENCES company(id_company) ON DELETE SET NULL ON UPDATE CASCADE')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index if not exists
SET @indexname = 'idx_company';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD INDEX ', @indexname, ' (id_company)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================
-- 5. CREATE ACTIVITY IMAGES DIRECTORY
-- ============================================================

SELECT 'Tables created successfully!' AS Result;
SELECT 'Remember to create directory: public/images/activity/' AS Note;

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

SELECT 'Verifying tables...' AS Status;
SELECT TABLE_NAME, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'db_p58_absen' 
AND TABLE_NAME IN ('company', 'user_company', 'activity')
ORDER BY TABLE_NAME;

