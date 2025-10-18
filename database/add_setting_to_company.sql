-- Add setting column to company table
ALTER TABLE `company` 
ADD COLUMN `setting` TEXT NULL AFTER `keterangan` 
COMMENT 'JSON setting data for presensi configuration';
