-- =====================================================
-- SQL Statements to Support Multiple Sectors for Facilitators
-- =====================================================

-- 1. Create the facilitator_sectors pivot table
-- This table stores the many-to-many relationship between Facilitator roles and sectors
-- Note: Using BIGINT (signed) to match existing sectors.id and user_roles.id types
CREATE TABLE IF NOT EXISTS `facilitator_sectors` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `user_role_id` BIGINT(20) NOT NULL,
    `sector_id` BIGINT(20) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `facilitator_sectors_user_role_id_sector_id_unique` (`user_role_id`, `sector_id`),
    KEY `facilitator_sectors_sector_id_foreign` (`sector_id`),
    KEY `facilitator_sectors_user_role_id_foreign` (`user_role_id`),
    CONSTRAINT `facilitator_sectors_sector_id_foreign` 
        FOREIGN KEY (`sector_id`) 
        REFERENCES `sectors` (`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `facilitator_sectors_user_role_id_foreign` 
        FOREIGN KEY (`user_role_id`) 
        REFERENCES `user_roles` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Alternative: If the table already exists, use this to add constraints
-- =====================================================

-- Check if foreign key constraint exists before adding
-- Note: You may need to adjust constraint names based on your database

-- Add foreign key for user_role_id (if it doesn't exist)
-- ALTER TABLE `facilitator_sectors` 
-- ADD CONSTRAINT `facilitator_sectors_user_role_id_foreign` 
-- FOREIGN KEY (`user_role_id`) 
-- REFERENCES `user_roles` (`id`) 
-- ON DELETE CASCADE;

-- Add foreign key for sector_id (if it doesn't exist)
-- ALTER TABLE `facilitator_sectors` 
-- ADD CONSTRAINT `facilitator_sectors_sector_id_foreign` 
-- FOREIGN KEY (`sector_id`) 
-- REFERENCES `sectors` (`id`) 
-- ON DELETE CASCADE;

-- Add unique constraint (if it doesn't exist)
-- ALTER TABLE `facilitator_sectors` 
-- ADD UNIQUE KEY `facilitator_sectors_user_role_id_sector_id_unique` (`user_role_id`, `sector_id`);

-- =====================================================
-- Verification Queries
-- =====================================================

-- Check if table exists
-- SHOW TABLES LIKE 'facilitator_sectors';

-- View table structure
-- DESCRIBE facilitator_sectors;

-- View all foreign keys on the table
-- SELECT 
--     CONSTRAINT_NAME,
--     TABLE_NAME,
--     COLUMN_NAME,
--     REFERENCED_TABLE_NAME,
--     REFERENCED_COLUMN_NAME
-- FROM information_schema.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME = 'facilitator_sectors'
--     AND REFERENCED_TABLE_NAME IS NOT NULL;

-- =====================================================
-- Rollback: Drop the table (if needed)
-- =====================================================

-- DROP TABLE IF EXISTS `facilitator_sectors`;
