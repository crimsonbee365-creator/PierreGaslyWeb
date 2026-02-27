-- ============================================================
-- SECURITY MIGRATION - Run this in phpMyAdmin SQL tab
-- Adds: email_verified, failed_login_attempts, locked_until
-- ============================================================

-- Add email_verified column (0 = not verified, 1 = verified)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) NOT NULL DEFAULT 0
    AFTER `status`;

-- Add brute force protection columns
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `failed_login_attempts` INT NOT NULL DEFAULT 0
    AFTER `email_verified`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL DEFAULT NULL
    AFTER `failed_login_attempts`;

-- Master admin should have email verified by default
UPDATE `users`
    SET `email_verified` = 1
    WHERE `role` IN ('master_admin', 'sub_admin');

SELECT 'Migration complete! Columns added successfully.' AS result;
