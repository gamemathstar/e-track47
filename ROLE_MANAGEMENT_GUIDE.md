# Role Management System - Implementation Guide

## Overview

This document provides a comprehensive guide to the improved role management system in the e-track47 application. The system has been enhanced to provide robust role assignment, update, and revocation capabilities with proper validation and audit trails.

## Table of Contents

1. [System Architecture](#system-architecture)
2. [Role Types](#role-types)
3. [Where to Manage Roles](#where-to-manage-roles)
4. [How to Update Roles](#how-to-update-roles)
5. [How to Revoke Roles](#how-to-revoke-roles)
6. [API Reference](#api-reference)
7. [Database Schema](#database-schema)
8. [Security Considerations](#security-considerations)

---

## System Architecture

### Models

#### UserRole Model (`app/Models/UserRole.php`)

The `UserRole` model manages user role assignments with the following features:

**Constants:**
- **Roles**: `ROLE_GOVERNOR`, `ROLE_SYSTEM_ADMIN`, `ROLE_SECTOR_HEAD`, `ROLE_SECTOR_ADMIN`, `ROLE_DELIVERY_DEPARTMENT`
- **Target Entities**: `ENTITY_SYSTEM`, `ENTITY_STATE`, `ENTITY_SECTOR`, `ENTITY_PROJECT`, `ENTITY_DELIVERABLE`
- **Status**: `STATUS_ACTIVE`, `STATUS_REVOKED`

**Key Methods:**
- `isActive()` - Check if role is active
- `isRevoked()` - Check if role is revoked
- `revoke()` - Revoke the role
- `activate()` - Activate the role
- `getRoleToEntityMapping()` - Get role to entity mapping

**Scopes:**
- `active()` - Get only active roles
- `revoked()` - Get only revoked roles

**Relationships:**
- `user()` - Belongs to User
- `sector()` - Belongs to Sector (if applicable)

#### User Model (`app/Models/User.php`)

Enhanced with role management capabilities:

**Key Methods:**
- `roles()` - Get all roles (relationship)
- `activeRole()` - Get active role (relationship)
- `getCurrentRole()` - Get current active role
- `activeRoles()` - Get all active roles
- `revokedRoles()` - Get all revoked roles
- `hasRole($role)` - Check if user has specific role
- `isSystemAdmin()`, `isGovernor()`, `isSectorHead()`, `isDeliveryDepartment()` - Role check methods

---

## Role Types

The system supports the following role types:

1. **Governor** - State-level access
   - Target Entity: State
   - Entity ID: 0 (all)

2. **System Admin** - System-wide administrative access
   - Target Entity: System
   - Entity ID: 0 (all)

3. **Sector Head** - Sector-level management
   - Target Entity: Sector
   - Entity ID: Specific sector ID (required)

4. **Sector Admin** - Sector-level administrative access
   - Target Entity: Sector
   - Entity ID: Specific sector ID (required)

5. **Delivery Department** - Deliverable-level access
   - Target Entity: Deliverable
   - Entity ID: 0 (all)

---

## Where to Manage Roles

### 1. User Profile Page - Settings Tab

**Location:** `Users → View Details → Settings Tab`

**Features:**
- View current active role with status badge
- Update user role via form
- View complete role history (active and revoked)
- Revoke or reactivate roles from history table

**Access:**
- Navigate to Users list
- Click "View Details" on any user
- Click on "Settings" tab
- Scroll to "Role Management" section

### 2. User Profile Page - Edit Profile Tab

**Location:** `Users → View Details → Edit Profile Tab`

**Features:**
- Update user information and role simultaneously
- Same validation and role assignment logic

**Access:**
- Navigate to Users list
- Click "View Details" on any user
- Click on "Edit Profile" tab
- Update role in the form

### 3. Add New User Modal

**Location:** `Users → Add New User`

**Features:**
- Assign role when creating a new user
- Automatic role assignment with validation
- Sector selection for sector-based roles

**Access:**
- Navigate to Users list
- Click "Add New User" button
- Fill in user details and select role

---

## How to Update Roles

### Method 1: Via Settings Tab (Recommended)

1. Navigate to the user's profile page
2. Click on the **Settings** tab
3. Scroll to **Role Management** section
4. In the **Update Role** form:
   - Select the new role from dropdown
   - If role is "Sector Head" or "Sector Admin", select a sector
   - Click **Update Role** button

**What Happens:**
- Current active role is automatically revoked
- New role is assigned with "Active" status
- Previous role is preserved in history with "Revoked" status
- User immediately has the new role permissions

### Method 2: Via Edit Profile Tab

1. Navigate to the user's profile page
2. Click on the **Edit Profile** tab
3. Update user information and/or role
4. Click **Save** button

**What Happens:**
- User information is updated
- If role is changed, same process as Method 1 occurs

### Method 3: Programmatically

```php
// In a controller or service
$user = User::find($userId);

// Update role via controller method
$request = new Request([
    'role' => 'Sector Head',
    'sector_id' => 1
]);

$userController = new UserController();
$userController->updateRole($request, $user);
```

---

## How to Revoke Roles

### Method 1: Via Settings Tab - Role History

1. Navigate to the user's profile page
2. Click on the **Settings** tab
3. Scroll to **Role History** table
4. Find the role you want to revoke
5. Click **Revoke** button
6. Confirm the action

**What Happens:**
- Role status changes from "Active" to "Revoked"
- User loses permissions associated with that role
- Role remains in history for audit purposes
- If this was the only active role, user will have no active role

### Method 2: Programmatically

```php
// In a controller or service
$user = User::find($userId);
$role = UserRole::find($roleId);

// Revoke via controller method
$request = new Request(['role_id' => $roleId]);
$userController = new UserController();
$userController->revokeRole($request, $user);

// Or directly on the model
$role->revoke();
```

---

## How to Reactivate Roles

### Via Settings Tab - Role History

1. Navigate to the user's profile page
2. Click on the **Settings** tab
3. Scroll to **Role History** table
4. Find the revoked role you want to reactivate
5. Click **Reactivate** button

**What Happens:**
- All other active roles for the user are automatically revoked
- Selected role status changes from "Revoked" to "Active"
- User immediately has the reactivated role permissions

---

## API Reference

### Routes

#### Update User Role
```
POST /users/{user}/role/update
```

**Request Body:**
```json
{
    "role": "Sector Head",
    "sector_id": 1  // Required if role is Sector Head or Sector Admin
}
```

**Response:**
- Success: Redirects back with success message
- Error: Redirects back with error message

#### Revoke User Role
```
POST /users/{user}/role/revoke
```

**Request Body:**
```json
{
    "role_id": 123
}
```

**Response:**
- Success: Redirects back with success message
- Error: Redirects back with error message

#### Reactivate User Role
```
POST /users/{user}/role/reactivate
```

**Request Body:**
```json
{
    "role_id": 123
}
```

**Response:**
- Success: Redirects back with success message
- Error: Redirects back with error message

### Controller Methods

#### UserController::updateRole()

Updates a user's role by revoking existing active roles and assigning a new one.

**Parameters:**
- `Request $request` - Contains role and sector_id
- `User $user` - The user to update

**Validation Rules:**
- `role`: Required, must be one of the valid roles
- `sector_id`: Required if role is Sector Head or Sector Admin, must exist in sectors table

#### UserController::revokeRole()

Revokes a specific role for a user.

**Parameters:**
- `Request $request` - Contains role_id
- `User $user` - The user whose role to revoke

**Validation Rules:**
- `role_id`: Required, must exist in user_roles table and belong to the user

#### UserController::reactivateRole()

Reactivates a revoked role for a user.

**Parameters:**
- `Request $request` - Contains role_id
- `User $user` - The user whose role to reactivate

**Validation Rules:**
- `role_id`: Required, must exist in user_roles table and belong to the user

---

## Database Schema

### user_roles Table

```sql
CREATE TABLE `user_roles` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `role` enum('Governor','Sector Head','Sector Admin','Delivery Department','System Admin') NOT NULL,
  `target_entity` enum('System','State','Sector','Project','Deliverable') NOT NULL,
  `entity_id` bigint(20) NOT NULL COMMENT '0 for all',
  `role_status` enum('Active','Revoked') NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Fields:**
- `user_id`: Foreign key to users table
- `role`: The role type assigned
- `target_entity`: The entity type the role applies to
- `entity_id`: Specific entity ID (0 for all, or sector ID for sector roles)
- `role_status`: Active or Revoked
- `deleted_at`: Soft delete timestamp (for future use)

---

## Security Considerations

### Current Implementation

1. **Authentication Required**: All role management routes are protected by `auth` middleware
2. **Validation**: All inputs are validated before processing
3. **Authorization**: Currently, any authenticated user can manage roles (consider restricting to System Admins)

### Recommended Enhancements

1. **Authorization Middleware**: Add middleware to restrict role management to System Admins only:

```php
Route::middleware(['auth', 'can:manage-roles'])->group(function () {
    Route::post('users/{user}/role/update', [UserController::class, 'updateRole']);
    Route::post('users/{user}/role/revoke', [UserController::class, 'revokeRole']);
    Route::post('users/{user}/role/reactivate', [UserController::class, 'reactivateRole']);
});
```

2. **Audit Logging**: Consider adding an audit log table to track:
   - Who changed the role
   - When it was changed
   - What was changed (old role → new role)
   - Reason for change (optional)

3. **Role Change Notifications**: Send email notifications when:
   - A role is assigned
   - A role is revoked
   - A role is reactivated

4. **Prevent Self-Revocation**: Prevent users from revoking their own roles

---

## Best Practices

1. **Always Validate**: Always validate role assignments before saving
2. **Maintain History**: Never delete role records; use status changes instead
3. **One Active Role**: Ensure only one role is active per user at a time
4. **Audit Trail**: Keep complete history of all role changes
5. **Clear Communication**: Notify users when their roles change
6. **Regular Reviews**: Periodically review role assignments for accuracy

---

## Troubleshooting

### Issue: User has no active role

**Solution:**
1. Navigate to user's profile → Settings tab
2. Check Role History table
3. If roles exist but are revoked, click "Reactivate" on the appropriate role
4. If no roles exist, use "Update Role" form to assign a new role

### Issue: Cannot assign sector role without sector

**Solution:**
- Ensure a sector is selected when assigning "Sector Head" or "Sector Admin" roles
- Verify the sector exists in the sectors table

### Issue: Role update not working

**Solution:**
1. Check validation errors in the response
2. Verify user exists
3. Verify sector exists (if applicable)
4. Check database connection
5. Review application logs for errors

---

## Future Enhancements

1. **Multiple Active Roles**: Support for users to have multiple active roles simultaneously
2. **Role Permissions**: Granular permission system tied to roles
3. **Role Templates**: Predefined role templates for common scenarios
4. **Bulk Role Management**: Update roles for multiple users at once
5. **Role Expiration**: Automatic role expiration and renewal
6. **Role Delegation**: Temporary role delegation capabilities

---

## Support

For questions or issues related to role management, please contact the system administrator or refer to the application documentation.

---

**Last Updated:** {{ date('Y-m-d') }}
**Version:** 1.0
