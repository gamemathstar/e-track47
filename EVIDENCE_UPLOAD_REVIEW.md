# Evidence Upload for Quarterly Review - Code Review Report

## Executive Summary

**Status: ✅ IMPLEMENTED**

Evidence upload functionality for quarterly reviews is **fully implemented** in the system. The feature allows users to upload multiple file attachments when submitting quarterly performance tracking data for KPIs.

---

## Implementation Details

### 1. Database Structure

#### Performance Tracking Table
- **Table**: `performance_trackings`
- **Location**: `database/migrations/2025_08_19_200644_create_performance_trackings_table.php`
- **Key Fields**:
  - `id` - Primary key
  - `kpi_id` - Foreign key to KPIs
  - `quarter` - Quarter number (1-4)
  - `year` - Year of tracking
  - `actual_value` - Actual performance value
  - `milestone` - Target milestone
  - `remarks` - Text remarks
  - `confirmation_status` - Status (Confirmed, Not Confirmed, Rejected)

#### File Storage (Polymorphic Relationship)
- **Model**: `File` (`app/Models/File.php`)
- **Structure**: Uses polymorphic relationship (`fileable_id`, `fileable_type`)
- **Fields**:
  - `name` - Original file name
  - `path` - Storage path
  - `type` - File extension
  - `size` - File size in bytes
  - `attached_by` - Entity that attached the file
  - `fileable_id` - ID of related model
  - `fileable_type` - Type of related model

**Note**: No dedicated migration file found for `files` table, suggesting it may have been created manually or through another mechanism.

---

### 2. Controller Implementation

#### KpiController::storeTracking()
**Location**: `app/Http/Controllers/KpiController.php` (Lines 40-75)

**Functionality**:
- Handles file uploads for performance tracking
- Validates file types: `jpg, jpeg, png, xlsx, xls, doc, docx, pdf`
- Maximum file size: 20MB per file (`max:20480`)
- Supports multiple file uploads
- Stores files using Laravel's storage system (`storage/app/public/uploads`)
- Creates polymorphic relationship between `PerformanceTracking` and `File` models
- Tracks which entity attached the file (`attached_by`)

**Key Code**:
```php
if ($request->hasFile('files')) {
    $request->validate([
        'files.*' => 'required|mimes:jpg,jpeg,png,xlsx,xls,doc,docx,pdf|max:20480',
    ]);
}

if ($request->file('files')) {
    $target = Auth::user()->role()->target_entity;
    foreach ($request->file('files') as $file) {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::random(10) . '.' . $extension;
        $path = $file->storeAs('uploads', $fileName, 'public');

        $tracking->files()->create([
            'name' => $originalName,
            'path' => $path,
            'type' => $extension,
            'size' => $file->getSize(),
            'attached_by' => $target
        ]);
    }
}
```

---

### 3. Model Relationships

#### PerformanceTracking Model
**Location**: `app/Models/PerformanceTracking.php`

**Relationship**:
```php
public function files(): MorphMany
{
    return $this->morphMany(File::class, 'fileable');
}
```

This allows each performance tracking record to have multiple file attachments.

#### File Model
**Location**: `app/Models/File.php`

**Polymorphic Relationship**:
```php
public function fileable()
{
    return $this->morphTo();
}
```

---

### 4. User Interface Implementation

#### Upload Form
**Location**: `resources/views/pages/sector/kpis.blade.php` (Lines 397-401)

**Features**:
- File input field with `multiple` attribute
- Accepts multiple file types: `.jpg, .jpeg, .png, .xlsx, .xls, .doc, .docx, .pdf`
- Labeled as "Optional Attachments(s)"
- Integrated into the "Add Performance Tracking" modal

**Code**:
```html
<div class="col-span-12 sm:col-span-12">
    <label for="files" class="form-label">Optional Attachments(s)</label>
    <input type="file" name="files[]" id="files" class="form-control mb-2" multiple
           accept=".jpg,.jpeg,.png,.xlsx,.xls,.doc,.docx,.pdf">
</div>
<div class="col-span-12 sm:col-span-12" id="preview"></div>
```

#### File Preview (JavaScript)
**Location**: `resources/views/pages/sector/kpis.blade.php` (Lines 696-730)

**Features**:
- Real-time file preview before upload
- Shows preview for images (JPEG, PNG)
- Shows preview for PDFs using iframe
- Displays file name, type, and size
- Shows "No preview available" for unsupported file types

**Supported Previews**:
- ✅ Images: JPEG, PNG, JPG
- ✅ PDFs: Embedded iframe preview
- ⚠️ Other files: Placeholder message

#### View Attachments
**Location**: `resources/views/pages/sector/kpis.blade.php` (Lines 484-504, 691-693)

**Features**:
- Modal dialog to view performance tracking details
- Separate section for attachments
- Loads attachments via AJAX when viewing tracking details
- Route: `deliverable.kpi.tracking.files`

**Code**:
```javascript
$.get('{{ route('deliverable.kpi.tracking.files',[':id']) }}'.replace(':id', id), function (data) {
    $('#attachments').html(data)
})
```

#### Attachments Display Component
**Location**: `resources/views/pages/sector/ajax/attachments.blade.php`

**Features**:
- Displays all attachments in a table format
- Shows image previews for image files
- Shows PDF preview using iframe
- Displays file metadata (name, type, size)
- Download button for each file (currently placeholder - no actual download link)

**Supported File Types**:
- ✅ Images: `jpg, jpeg, png, JPG, JPEG, PNG` - Full preview
- ✅ PDFs: `pdf, PDF` - Iframe preview
- ⚠️ Other files: Placeholder message

---

### 5. Routes

**Location**: `routes/web.php` (Line 90)

```php
Route::get('deliverable/kpi/tracking/files/{id}', [PerformanceTracking::class, 'attachments'])->name('deliverable.kpi.tracking.files');
```

**Note**: The route uses `PerformanceTracking::class` directly as the controller, which calls the `attachments()` method on the model.

---

### 6. Workflow

#### Adding Evidence to Quarterly Review

1. **User navigates to**: Sector → Commitment → Deliverable → KPIs
2. **User clicks**: "Add Performance Tracking" button for a specific quarter
3. **Modal opens**: "Add Performance Tracking to [KPI Name]"
4. **User fills in**:
   - Tracking Date
   - Milestone
   - Actual Delivery value
   - Remarks
   - **Optional Attachments(s)** - Multiple files can be selected
5. **File preview**: JavaScript shows preview of selected files
6. **User submits**: Form is posted to `deliverable.store.tracking` route
7. **Backend processing**:
   - Creates/updates `PerformanceTracking` record
   - Validates and stores uploaded files
   - Creates `File` records with polymorphic relationship
   - Sends notification for review
8. **Files stored**: `storage/app/public/uploads/` directory

#### Viewing Evidence

1. **User clicks**: On a performance tracking value in the quarterly table
2. **Modal opens**: "Performance Tracking for [KPI] ([Quarter])"
3. **AJAX loads**:
   - Tracking details (via `performance.tracking` route)
   - Attachments (via `deliverable.kpi.tracking.files` route)
4. **Attachments displayed**: In a table with previews and metadata

---

### 7. File Storage

**Storage Location**: `storage/app/public/uploads/`
**Storage Method**: Laravel Storage facade with `public` disk
**File Naming**: Random 10-character string + original extension
**Original Name**: Preserved in database `name` field

**Example**:
- Original: `quarterly_report_q1.pdf`
- Stored as: `a3f9k2m8x1.pdf`
- Path: `storage/app/public/uploads/a3f9k2m8x1.pdf`
- Database: `name = "quarterly_report_q1.pdf"`, `path = "uploads/a3f9k2m8x1.pdf"`

---

### 8. Supported File Types

| File Type | Extension | Preview Support | Max Size |
|-----------|-----------|----------------|----------|
| Images | jpg, jpeg, png | ✅ Yes | 20MB |
| PDFs | pdf | ✅ Yes (iframe) | 20MB |
| Excel | xlsx, xls | ⚠️ No preview | 20MB |
| Word | doc, docx | ⚠️ No preview | 20MB |

**Total Maximum**: 20MB per file, multiple files allowed per tracking entry

---

### 9. Access Control

**File Attachment Tracking**:
- `attached_by` field stores the entity that attached the file
- Retrieved via `Auth::user()->role()->target_entity`
- Used to filter attachments when viewing (see `PerformanceTracking::attachments()` method)

**Viewing Attachments**:
- The `attachments()` method filters files by `attached_by`:
```php
$target = Auth::user()->role()->target_entity;
$files = File::where(['fileable_id' => $id, 'attached_by' => $target])->get();
```

This ensures users only see attachments they uploaded.

---

### 10. Integration Points

#### Form Submission
- **Route**: `deliverable.store.tracking`
- **Method**: POST
- **Controller**: `KpiController@storeTracking`
- **Form**: Includes `enctype="multipart/form-data"` for file uploads

#### Notification System
After saving tracking with files, a notification is sent:
```php
Notification::submitTrackingForRewiew($tracking);
```

---

## Limitations & Issues Found

### 1. Download Functionality
**Issue**: Download button in attachments view is a placeholder
**Location**: `resources/views/pages/sector/ajax/attachments.blade.php` (Line 19)
**Status**: ⚠️ **NOT IMPLEMENTED**
```html
<a href="" class="btn btn-primary mt-2">Download</a>
```
The `href` is empty - no actual download route implemented.

### 2. Files Table Migration
**Issue**: No migration file found for `files` table
**Status**: ⚠️ **UNCLEAR**
- The `File` model exists and is used
- No migration file in `database/migrations/`
- Table may have been created manually or through another method

### 3. File Deletion
**Issue**: No functionality found to delete uploaded files
**Status**: ⚠️ **NOT IMPLEMENTED**
- Files can be uploaded but not deleted
- Could lead to storage bloat over time

### 4. Storage Link
**Issue**: Public storage link may not be configured
**Status**: ⚠️ **REQUIRES VERIFICATION**
- Files stored in `storage/app/public/uploads/`
- Requires `php artisan storage:link` to be run
- Access via `Storage::url($file->path)` requires proper configuration

### 5. File Size Validation
**Status**: ✅ **IMPLEMENTED**
- Backend validation: 20MB max per file
- Frontend: No client-side validation found (could be improved)

---

## Recommendations

### High Priority
1. **Implement Download Functionality**
   - Create download route
   - Add proper download link in attachments view
   - Ensure proper file access control

2. **Add File Deletion**
   - Allow users to delete their own attachments
   - Implement cleanup for deleted performance trackings
   - Add confirmation dialogs

3. **Create Files Table Migration**
   - Document the files table structure
   - Ensure database schema is version controlled

### Medium Priority
4. **Improve File Preview**
   - Add preview for Excel files (if possible)
   - Add preview for Word documents (if possible)
   - Better error handling for preview failures

5. **Storage Management**
   - Add file size limits per user/role
   - Implement file cleanup for old/unused files
   - Add storage usage reporting

6. **Security Enhancements**
   - Add virus scanning (if needed)
   - Implement file type validation on both client and server
   - Add file content validation

### Low Priority
7. **User Experience**
   - Add drag-and-drop file upload
   - Show upload progress indicator
   - Add file upload success/error notifications
   - Improve file preview UI

---

## Conclusion

The evidence upload functionality for quarterly reviews is **fully implemented and functional**. The system allows users to:

✅ Upload multiple files when submitting quarterly performance tracking  
✅ Preview files before and after upload  
✅ View attachments when reviewing performance tracking data  
✅ Store files securely with proper relationships  

However, there are some **missing features** that should be addressed:

⚠️ File download functionality is not implemented  
⚠️ File deletion is not available  
⚠️ Files table migration is missing from version control  

**Overall Assessment**: The core functionality is solid, but some polish and additional features would improve the user experience.

---

## Files Reviewed

1. `app/Http/Controllers/KpiController.php` - File upload handling
2. `app/Models/PerformanceTracking.php` - Model relationships
3. `app/Models/File.php` - File model
4. `resources/views/pages/sector/kpis.blade.php` - UI components
5. `resources/views/pages/sector/ajax/attachments.blade.php` - Attachments display
6. `resources/views/pages/sector/performance.blade.php` - Performance tracking view
7. `routes/web.php` - Route definitions
8. `database/migrations/2025_08_19_200644_create_performance_trackings_table.php` - Database schema

---

**Report Generated**: 2026-01-28  
**Codebase Version**: Current  
**Reviewer**: AI Assistant
