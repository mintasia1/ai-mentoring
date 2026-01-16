-- Add profile fields to mentor table
ALTER TABLE `mentor` 
ADD COLUMN `nama_lengkap` VARCHAR(100) NULL AFTER `password`,
ADD COLUMN `no_telepon` VARCHAR(20) NULL AFTER `nama_lengkap`,
ADD COLUMN `alamat` TEXT NULL AFTER `no_telepon`,
ADD COLUMN `bio` TEXT NULL AFTER `alamat`,
ADD COLUMN `foto_profil` VARCHAR(255) NULL AFTER `bio`,
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `foto_profil`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;