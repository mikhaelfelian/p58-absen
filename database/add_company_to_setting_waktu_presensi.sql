-- Add company column to setting_waktu_presensi table
ALTER TABLE `setting_waktu_presensi` 
ADD COLUMN `id_company` INT(11) NOT NULL AFTER `nama_setting`;

-- Add foreign key constraint
ALTER TABLE `setting_waktu_presensi` 
ADD CONSTRAINT `fk_setting_waktu_presensi_company` 
FOREIGN KEY (`id_company`) REFERENCES `company` (`id_company`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Add index for better performance
ALTER TABLE `setting_waktu_presensi` 
ADD INDEX `idx_setting_waktu_presensi_company` (`id_company`);
