# Gallery Feature Documentation

## Overview
This document describes the gallery feature implementation that allows administrators to upload images to a public gallery and display them on the frontend without authentication.

## Database Structure

### `galleries` Table

The gallery feature uses a single table `galleries` with the following structure:

```sql
CREATE TABLE galleries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,              -- Path to the uploaded image file
    title VARCHAR(255) NULL,                       -- Optional title for the image
    caption TEXT NULL,                             -- Optional description/caption
    status ENUM('active', 'inactive') DEFAULT 'active',  -- Visibility status
    display_order INT DEFAULT 0,                   -- Order for sorting/display
    uploaded_by BIGINT UNSIGNED NULL,             -- Foreign key to users table
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### Field Descriptions:

- **`id`**: Primary key, auto-incrementing
- **`image_path`**: Relative path to the image file (e.g., `uploads/gallery/1234567890_abc123.jpg`)
- **`title`**: Optional title for the image (max 255 characters)
- **`caption`**: Optional detailed description or caption (TEXT field, max 1000 characters in validation)
- **`status`**: Controls visibility - `active` images appear in public gallery, `inactive` are hidden
- **`display_order`**: Integer for custom sorting (lower numbers appear first)
- **`uploaded_by`**: Foreign key to `users` table, tracks who uploaded the image
- **`created_at` / `updated_at`**: Timestamps for record tracking

## File Structure

### Models
- **`app/Models/Gallery.php`**: Eloquent model with relationships and scopes

### Controllers
- **`app/Http/Controllers/GalleryController.php`**: Admin-side CRUD operations
- **`app/Http/Controllers/PublicGalleryController.php`**: Public-facing gallery display

### Views (Admin)
- **`resources/views/pages/gallery/index.blade.php`**: List all gallery items
- **`resources/views/pages/gallery/create.blade.php`**: Upload new image form
- **`resources/views/pages/gallery/edit.blade.php`**: Edit existing image form

### Views (Public)
- **`resources/views/pages/public/gallery.blade.php`**: Public gallery grid view
- **`resources/views/pages/public/gallery-show.blade.php`**: Single image detail view

### Migration
- **`database/migrations/2026_01_28_113628_create_galleries_table.php`**: Database schema

## Routes

### Admin Routes (Requires Authentication)
- `GET /admin/gallery` - List all gallery items
- `GET /admin/gallery/create` - Show upload form
- `POST /admin/gallery` - Store new image
- `GET /admin/gallery/{gallery}/edit` - Show edit form
- `PUT/PATCH /admin/gallery/{gallery}` - Update image
- `DELETE /admin/gallery/{gallery}` - Delete image
- `GET /admin/gallery/{gallery}` - View single image (admin)

### Public Routes (No Authentication Required)
- `GET /gallery` - Public gallery grid view
- `GET /gallery/{gallery}` - Public single image detail view

## Features

### Admin Features
1. **Upload Images**: Upload images with title, caption, status, and display order
2. **Image Preview**: Real-time preview of selected image before upload
3. **Edit Images**: Update image details or replace the image file
4. **Delete Images**: Remove images (also deletes the physical file)
5. **Status Management**: Toggle between active/inactive to control public visibility
6. **Display Ordering**: Set custom order for image display
7. **Pagination**: Gallery list is paginated (20 items per page)

### Public Features
1. **Grid View**: Responsive grid layout showing all active images
2. **Image Detail View**: Full-size image with title, caption, and metadata
3. **Navigation**: Previous/Next navigation between images
4. **No Authentication**: Fully accessible without login
5. **Pagination**: Public gallery is paginated (12 items per page)

## Image Storage

- **Location**: `public/uploads/gallery/`
- **Naming**: `{timestamp}_{unique_id}.{extension}`
- **Supported Formats**: JPEG, PNG, JPG, GIF, WEBP
- **Max File Size**: 5MB
- **Auto-cleanup**: When an image is deleted, the physical file is also removed

## Access Control

- **Admin Gallery Management**: Only accessible to authenticated users (System Admins see it in sidebar)
- **Public Gallery**: Accessible to everyone, no authentication required
- **Image Visibility**: Only images with `status = 'active'` appear in public gallery

## Usage

### For Administrators:

1. Navigate to **Gallery Management** in the sidebar (System Admin only)
2. Click **Upload New Image**
3. Select an image file
4. Fill in optional title and caption
5. Set status (Active = visible in public gallery)
6. Set display order (optional)
7. Click **Upload Image**

### For Public Users:

1. Navigate to **Gallery** from the top menu or visit `/gallery`
2. Browse images in grid view
3. Click any image to view details
4. Use Previous/Next buttons to navigate between images

## Database Migration

To create the gallery table, run:

```bash
php artisan migrate
```

This will create the `galleries` table with all necessary fields and relationships.

## Model Relationships

- **Gallery → User**: `belongsTo(User::class, 'uploaded_by')` - Each gallery item can have an uploader
- **User → Gallery**: Can be added if needed: `hasMany(Gallery::class, 'uploaded_by')`

## Model Scopes

- **`active()`**: Returns only active gallery items
- **`ordered()`**: Orders by display_order (asc) then created_at (desc)

## Security Considerations

1. **File Upload Validation**: Only image files are accepted (jpeg, png, jpg, gif, webp)
2. **File Size Limit**: Maximum 5MB per image
3. **Authentication**: Admin routes require authentication
4. **File Deletion**: Physical files are deleted when gallery items are removed
5. **Public Access**: Only active images are visible in public gallery

## Future Enhancements

Potential improvements:
- Image resizing/optimization on upload
- Multiple image upload
- Image categories/tags
- Image search functionality
- Image metadata (EXIF data)
- Image cropping/editing tools
- Bulk operations (activate/deactivate multiple)
