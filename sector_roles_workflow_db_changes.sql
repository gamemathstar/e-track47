-- =====================================================
-- SQL Queries for Sector Roles and Workflow Changes
-- =====================================================

-- =====================================================
-- 1. RENAME "Sector Admin" TO "Data Admin"
-- =====================================================

-- Step 1: Update existing 'Sector Admin' records to 'Data Admin'
UPDATE `user_roles`
SET `role` = 'Data Admin'
WHERE `role` = 'Sector Admin';

-- Step 2: Modify the enum column to replace 'Sector Admin' with 'Data Admin'
-- Note: MySQL doesn't support direct enum modification, so we need to use ALTER TABLE
ALTER TABLE `user_roles` 
MODIFY COLUMN `role` ENUM(
    'Governor',
    'Sector Head',
    'Data Admin',
    'Coordinator',
    'Deputy Coordinator',
    'Facilitator',
    'System Admin'
) NOT NULL;

-- =====================================================
-- 2. ADD APPROVAL WORKFLOW TO PERFORMANCE_TRACKINGS
-- =====================================================

-- Step 1: Add approval workflow fields
ALTER TABLE `performance_trackings`
ADD COLUMN `sector_head_approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `confirmation_status`,
ADD COLUMN `sector_head_approved_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `sector_head_approved_at`,
ADD COLUMN `facilitator_confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `sector_head_approved_by`,
ADD COLUMN `facilitator_confirmed_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `facilitator_confirmed_at`,
ADD COLUMN `coordinator_confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `facilitator_confirmed_by`,
ADD COLUMN `coordinator_confirmed_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `coordinator_confirmed_at`;

-- Step 2: Add foreign key constraints
ALTER TABLE `performance_trackings`
ADD CONSTRAINT `fk_perf_track_sector_head_approved_by`
    FOREIGN KEY (`sector_head_approved_by`)
    REFERENCES `users` (`id`)
    ON DELETE SET NULL,
ADD CONSTRAINT `fk_perf_track_facilitator_confirmed_by`
    FOREIGN KEY (`facilitator_confirmed_by`)
    REFERENCES `users` (`id`)
    ON DELETE SET NULL,
ADD CONSTRAINT `fk_perf_track_coordinator_confirmed_by`
    FOREIGN KEY (`coordinator_confirmed_by`)
    REFERENCES `users` (`id`)
    ON DELETE SET NULL;

-- Step 3: Update confirmation_status enum to include new workflow statuses
ALTER TABLE `performance_trackings`
MODIFY COLUMN `confirmation_status` ENUM(
    'Not Confirmed',
    'Pending Sector Head Approval',
    'Pending Facilitator',
    'Pending Coordinator',
    'Confirmed',
    'Rejected'
) DEFAULT 'Not Confirmed';

-- =====================================================
-- ROLLBACK QUERIES (if needed to reverse changes)
-- =====================================================

-- To rollback approval workflow changes:
/*
-- Step 1: Drop foreign key constraints
ALTER TABLE `performance_trackings`
DROP FOREIGN KEY `fk_perf_track_sector_head_approved_by`,
DROP FOREIGN KEY `fk_perf_track_facilitator_confirmed_by`,
DROP FOREIGN KEY `fk_perf_track_coordinator_confirmed_by`;

-- Step 2: Drop columns
ALTER TABLE `performance_trackings`
DROP COLUMN `sector_head_approved_at`,
DROP COLUMN `sector_head_approved_by`,
DROP COLUMN `facilitator_confirmed_at`,
DROP COLUMN `facilitator_confirmed_by`,
DROP COLUMN `coordinator_confirmed_at`,
DROP COLUMN `coordinator_confirmed_by`;

-- Step 3: Restore original enum
ALTER TABLE `performance_trackings`
MODIFY COLUMN `confirmation_status` ENUM(
    'Confirmed',
    'Not Confirmed',
    'Rejected'
) DEFAULT 'Not Confirmed';
*/

-- To rollback role rename:
/*
-- Step 1: Update 'Data Admin' records back to 'Sector Admin'
UPDATE `user_roles`
SET `role` = 'Sector Admin'
WHERE `role` = 'Data Admin';

-- Step 2: Restore original enum
ALTER TABLE `user_roles`
MODIFY COLUMN `role` ENUM(
    'Governor',
    'Sector Head',
    'Sector Admin',
    'Coordinator',
    'Deputy Coordinator',
    'Facilitator',
    'System Admin'
) NOT NULL;
*/

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Verify role changes
SELECT `role`, COUNT(*) as count
FROM `user_roles`
WHERE `role` IN ('Sector Admin', 'Data Admin')
GROUP BY `role`;

-- Verify performance_trackings structure
DESCRIBE `performance_trackings`;

-- Check for any existing data that might need migration
SELECT 
    `id`,
    `kpi_id`,
    `confirmation_status`,
    `sector_head_approved_at`,
    `sector_head_approved_by`,
    `facilitator_confirmed_at`,
    `facilitator_confirmed_by`,
    `coordinator_confirmed_at`,
    `coordinator_confirmed_by`
FROM `performance_trackings`
LIMIT 10;
