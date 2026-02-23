-- ============================================================================
-- SQL Script: Update Delivery Unit Roles
-- ============================================================================
-- Description: 
--   This script updates the user_roles table to replace "Delivery Department" 
--   role with three new roles: Coordinator, Deputy Coordinator, and Facilitator
--
-- IMPORTANT: 
--   1. Backup your database before running this script
--   2. Run these queries in order
--   3. Test in a development environment first
--
-- Date: 2026-02-21
-- ============================================================================

-- ============================================================================
-- STEP 1: BACKUP RECOMMENDATION
-- ============================================================================
-- Before proceeding, create a backup:
-- mysqldump -u [username] -p [database_name] > backup_before_role_update.sql
-- OR
-- CREATE TABLE user_roles_backup AS SELECT * FROM user_roles;


-- ============================================================================
-- STEP 2: UPDATE EXISTING DATA
-- ============================================================================
-- Convert all existing "Delivery Department" roles to "Coordinator"
-- This preserves existing user assignments while updating to the new role structure

UPDATE `user_roles`
SET `role` = 'Coordinator',
    `updated_at` = NOW()
WHERE `role` = 'Delivery Department'
    AND `role_status` = 'Active';

-- Verify the update
SELECT 
    COUNT(*) as updated_records,
    'Delivery Department roles converted to Coordinator' as status
FROM `user_roles`
WHERE `role` = 'Coordinator'
    AND `updated_at` >= DATE_SUB(NOW(), INTERVAL 1 MINUTE);


-- ============================================================================
-- STEP 3: MODIFY TABLE STRUCTURE
-- ============================================================================
-- Update the enum column to include the new delivery unit roles
-- Note: This will fail if there are any records with values not in the new enum

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


-- ============================================================================
-- STEP 4: VERIFICATION QUERIES
-- ============================================================================
-- Run these queries to verify the changes were applied successfully

-- 4.1: Check the column definition
SHOW COLUMNS FROM `user_roles` WHERE Field = 'role';

-- 4.2: Verify no "Delivery Department" roles remain (should return 0)
SELECT 
    COUNT(*) as remaining_delivery_dept_roles
FROM `user_roles`
WHERE `role` = 'Delivery Department';

-- 4.3: Check all delivery unit roles
SELECT 
    ur.id,
    u.full_name,
    u.email,
    ur.role,
    ur.target_entity,
    ur.entity_id,
    ur.role_status,
    ur.created_at,
    ur.updated_at
FROM `user_roles` ur
INNER JOIN `users` u ON ur.user_id = u.id
WHERE ur.role IN ('Coordinator', 'Deputy Coordinator', 'Facilitator')
ORDER BY ur.role, u.full_name;

-- 4.4: Summary of role distribution
SELECT 
    ur.role,
    COUNT(*) as total_count,
    SUM(CASE WHEN ur.role_status = 'Active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN ur.role_status = 'Revoked' THEN 1 ELSE 0 END) as revoked_count
FROM `user_roles` ur
WHERE ur.role IN ('Coordinator', 'Deputy Coordinator', 'Facilitator', 'Delivery Department')
GROUP BY ur.role
ORDER BY ur.role;


-- ============================================================================
-- STEP 5: OPTIONAL - UPDATE FACILITATOR ASSIGNMENTS
-- ============================================================================
-- If you need to assign Facilitators to specific sectors, use these queries:
--
-- Example: Assign a Facilitator to Sector ID 1
-- UPDATE `user_roles`
-- SET `target_entity` = 'Sector',
--     `entity_id` = 1,  -- Replace with actual sector ID
--     `updated_at` = NOW()
-- WHERE `user_id` = :user_id  -- Replace with actual user ID
--     AND `role` = 'Facilitator'
--     AND `role_status` = 'Active';
--
-- Note: Coordinators and Deputy Coordinators should have entity_id = 0 (all sectors)


-- ============================================================================
-- ROLLBACK SCRIPT (Use only if you need to revert changes)
-- ============================================================================
-- WARNING: Only run this if you need to rollback the changes
--
-- Step 1: Convert new roles back to "Delivery Department"
-- UPDATE `user_roles`
-- SET `role` = 'Delivery Department',
--     `updated_at` = NOW()
-- WHERE `role` IN ('Coordinator', 'Deputy Coordinator', 'Facilitator');
--
-- Step 2: Restore original enum
-- ALTER TABLE `user_roles` 
-- MODIFY COLUMN `role` ENUM(
--     'Governor',
--     'Sector Head',
--     'Sector Admin',
--     'Delivery Department',
--     'System Admin'
-- ) NOT NULL;
-- ============================================================================
