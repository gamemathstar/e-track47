# UI Pages Description for E-Track47 (Performance Delivery Coordination Unit)

This document provides comprehensive descriptions of all pages/screens in the E-Track47 application for UI redesign purposes.

## Application Overview
E-Track47 is a Performance Delivery Coordination Unit (PDCU) management system for tracking and monitoring government sector performance, commitments, deliverables, KPIs, and quarterly assessments. The system supports multiple user roles: System Admin, Governor, Sector Head, Sector Admin, and Delivery Department.

---

## 1. PUBLIC PAGES (No Authentication Required)

### 1.1 Welcome/Home Page (`welcome.blade.php`)
**Route:** `/`
**Description:** Public landing page for the PDCU system
- **Header:** Logo and site title "Performance Delivery Coordination Unit (PDCU)"
- **Navigation:** 
  - Home link
  - Gallery link (public gallery)
  - Login link
- **Content:** Welcome message and introduction to the system
- **Footer:** Standard footer information
- **Design Notes:** Should be welcoming, professional, government-appropriate

### 1.2 Public Gallery (`pages/public/gallery.blade.php`)
**Route:** `/gallery`
**Description:** Public-facing image gallery displaying active gallery items
- **Layout:** 2-column responsive grid (1 column on mobile)
- **Header:** Consistent header similar to other public pages
- **Content:** 
  - Grid of gallery images with thumbnails
  - Each item shows image preview
  - Clickable to view details
- **Features:** Pagination for large galleries
- **Design Notes:** Clean, modern gallery layout with hover effects

### 1.3 Public Gallery Detail (`pages/public/gallery-show.blade.php`)
**Route:** `/gallery/{gallery}`
**Description:** Individual gallery image detail view
- **Header:** Consistent header with navigation
- **Content:**
  - Full-size image display
  - Image title (if provided)
  - Caption/description
  - Upload date
  - Uploader information
- **Navigation:** Previous/Next buttons to navigate between images
- **Design Notes:** Focus on image display with minimal distractions

---

## 2. AUTHENTICATION PAGES

### 2.1 Login Page (`pages/auth/login.blade.php`)
**Route:** `/login`
**Description:** User authentication page
- **Layout:** Split-screen design (desktop) / stacked (mobile)
- **Left Side (Desktop):**
  - Logo and "Performance Delivery Coordination Unit (PDCU)" branding
  - Large map/image of Jigawa state
- **Right Side:**
  - "Sign In" heading
  - Email input field
  - Password input field
  - Remember me checkbox
  - Login button
  - Error message display area
- **Design Notes:** Professional, secure appearance with clear call-to-action

---

## 3. DASHBOARD & MAIN NAVIGATION

### 3.1 Dashboard (`pages/dashboard/index.blade.php`)
**Route:** `/dashboard`
**Description:** Main dashboard showing quarterly performance overview
- **Access:** All authenticated users (role-based content)
- **Layout:** Grid-based layout with cards and tables
- **Content:**
  - **General Report Section:**
    - Table showing all sectors/MDAs
    - Quarterly performance scores (Q1, Q2, Q3, Q4) for each sector
    - Performance indicators with up/down arrows (green for ≥50%, red for <50%)
    - Displays "-" for sectors with no data
  - **Statistics Cards:** (if applicable)
    - Total commitments
    - Total KPIs
    - Budget information
- **Features:**
  - Year selector
  - Real-time performance calculations
- **Design Notes:** Data-dense but organized, clear visual hierarchy

### 3.2 Sidebar Navigation (`commons/menu/sidebar.blade.php`)
**Description:** Main application sidebar menu
- **Structure:**
  - Dashboard link
  - Users Management (System Admin only)
  - Sectors/MDAs Management
  - Reports
  - Gallery Management (System Admin only)
  - Logout
- **Features:** 
  - Role-based menu items
  - Active state indicators
  - Icons for each menu item
- **Design Notes:** Collapsible, responsive, clear hierarchy

### 3.3 Topbar Navigation (`commons/menu/topbar.blade.php`)
**Description:** Top navigation bar
- **Content:**
  - User profile photo (with fallback)
  - User full name
  - Sector name (if applicable)
  - Notifications (if applicable)
  - User menu dropdown
- **Design Notes:** Clean, minimal, always visible

---

## 4. USER MANAGEMENT PAGES

### 4.1 Users List (`pages/users/index.blade.php`)
**Route:** `/users`
**Description:** User management page (System Admin only)
- **Layout:** Table with action buttons
- **Content:**
  - User list table with columns:
    - Name
    - Email
    - Role
    - Sector (if applicable)
    - Status
    - Actions (Edit, Delete)
  - "Add User" button/modal
- **Features:**
  - User creation form (modal or separate page)
  - User editing
  - User deletion
  - Role assignment
  - Sector assignment (for Sector Head/Admin)
- **Design Notes:** Standard admin table with search/filter capabilities

### 4.2 User Detail/View (`pages/users/show.blade.php`)
**Route:** `/users/view/{user}`
**Description:** Individual user profile page
- **Content:**
  - User profile photo
  - User information (name, email, role, sector)
  - Change password functionality
  - Upload/change photo option
  - Activity history (if applicable)
- **Design Notes:** Profile-focused layout

### 4.3 Awaiting Verification (`pages/users/awaiting.blade.php`)
**Route:** `/delivery/tracking/awaiting/`
**Description:** List of performance tracking items awaiting verification (Delivery Department)
- **Content:**
  - List/table of tracking items pending review
  - Filter options
  - Action buttons to view details
- **Design Notes:** Task list style, clear status indicators

### 4.4 Awaiting Verification - Commitment View (`pages/users/awaiting_commitment.blade.php`)
**Route:** `/delivery/tracking/awaiting/comm/{id}/view`
**Description:** View commitment details for verification
- **Content:** Commitment information with deliverables list

### 4.5 Awaiting Verification - Deliverable View (`pages/users/awaiting_deliverables.blade.php`)
**Route:** `/delivery/tracking/awaiting/del/{id}/view`
**Description:** View deliverable details for verification
- **Content:** Deliverable information with KPIs

### 4.6 Awaiting Verification - KPI View (`pages/users/awaiting_kpis.blade.php`)
**Route:** `/delivery/tracking/awaiting/{id}/view`
**Description:** View KPI tracking details for verification
- **Content:** KPI performance tracking data, evidence, approval actions

---

## 5. SECTOR/MDA MANAGEMENT PAGES

### 5.1 Sectors List (`pages/sector/index.blade.php`)
**Route:** `/sectors`
**Description:** List of all sectors/MDAs
- **Content:**
  - Table/card grid of sectors
  - Sector name, description
  - Actions: View, Edit, Delete
  - "Add Sector" button
- **Features:**
  - Sector creation form
  - Sector editing
  - Sector deletion
- **Design Notes:** Clean list/grid layout

### 5.2 Sector Detail View (`pages/sector/view.blade.php`)
**Route:** `/sectors/{id}/details/{id2?}`
**Description:** Comprehensive sector overview page
- **Layout:** Tabbed or sectioned interface
- **Content Sections:**
  - **Sector Information:**
    - Sector name, description
    - Sector head assignment button
    - Export options (Word, etc.)
  - **Commitments List:**
    - All commitments under this sector
    - Add commitment button
    - Commitment cards/list with:
      - Commitment name
      - Status
      - Deliverables count
      - Progress indicators
  - **Budget Information:**
    - Budget allocation
    - Budget tracking
  - **Documents:**
    - Uploaded documents
    - Document management
- **Features:**
  - Add/edit/delete commitments
  - Navigate to commitment details
  - Budget management
- **Design Notes:** Information-rich but well-organized, clear navigation

### 5.3 Sector Show (`pages/sector/show.blade.php`)
**Route:** `/sectors/show/{id}/`
**Description:** Alternative sector display (may be modal or simplified view)

### 5.4 Sector Budget (`pages/sector/commitent_budget.blade.php`)
**Route:** `/sectors/budget/`
**Description:** Sector budget management page
- **Content:**
  - Budget allocation table
  - Budget by commitment breakdown
  - Add/edit budget entries
  - Budget summary statistics
- **Design Notes:** Financial data presentation, clear tables

---

## 6. COMMITMENT MANAGEMENT PAGES

### 6.1 Commitments List (`pages/sector/deliverables.blade.php` or similar)
**Route:** `/commitment`
**Description:** List of all commitments
- **Content:**
  - Commitments table/list
  - Filter by sector
  - Add commitment button
  - Commitment details (name, sector, status, deliverables count)
- **Design Notes:** Standard list view with filters

### 6.2 Commitment Deliverables (`pages/sector/deliverable.blade.php`)
**Route:** `/commitment/deliverables/{commitment}`
**Description:** Deliverables management for a specific commitment
- **Content:**
  - Commitment header information
  - Deliverables list/table
  - Add deliverable button
  - Each deliverable shows:
    - Name/description
    - Status
    - End date
    - KPIs count
    - Actions (Edit, Delete, View KPIs)
- **Features:**
  - Add/edit/delete deliverables
  - Navigate to KPIs
  - Performance tracking
- **Design Notes:** Hierarchical view (Commitment → Deliverables)

---

## 7. DELIVERABLE & KPI PAGES

### 7.1 Deliverable KPIs (`pages/sector/kpis.blade.php`)
**Route:** `/deliverable/kpis/{deliverable}`
**Description:** KPIs management for a specific deliverable
- **Content:**
  - Deliverable header information
  - KPIs list/table
  - Add KPI button
  - Each KPI shows:
    - KPI name/description
    - Unit of measurement
    - Target value
    - Start/end dates
    - Performance tracking status
    - Actions (Edit, Delete, Track Performance)
- **Features:**
  - Add/edit/delete KPIs
  - Set KPI targets
  - Navigate to performance tracking
- **Design Notes:** Hierarchical view (Deliverable → KPIs)

### 7.2 Performance Tracking (`pages/sector/performance.blade.php`)
**Route:** `/commitment/deliverable/kpi/{kpi}/{track}`
**Description:** Quarterly performance tracking for a KPI
- **Content:**
  - KPI information header
  - Quarterly tracking form (Q1, Q2, Q3, Q4)
  - For each quarter:
    - Actual value input
    - Milestone/target display
    - Performance percentage calculation
    - Evidence upload
    - Remarks/notes
    - Status (Not Confirmed, Confirmed, etc.)
  - Submit for review button
- **Features:**
  - Real-time performance calculation
  - File upload for evidence
  - Status tracking
  - Review workflow
- **Design Notes:** Form-heavy page, clear quarter sections, visual performance indicators

### 7.3 KPI Targets (`pages/sector/targets.blade.php`)
**Route:** Related to `/deliverable/kpi/target/save`
**Description:** Set annual targets for KPIs
- **Content:**
  - KPI information
  - Year selector
  - Target value input
  - Save target button
- **Design Notes:** Simple form interface

---

## 8. REPORTS PAGES

### 8.1 Reports Index (`pages/reports/index.blade.php`)
**Route:** `/reports`
**Description:** Reports hub page
- **Content:**
  - Report generation form
  - Filter options:
    - Start Quarter (Q1-Q4)
    - End Quarter (Q1-Q4)
    - Year selector
    - Sector multi-select (for non-sector heads)
  - "Generate Report" button
  - "Download Report" button
  - Link to Comprehensive KPI Report
- **Features:**
  - Quarter-based filtering
  - Sector filtering
  - Role-based access (sector heads see only their sector)
- **Design Notes:** Form-focused, clear filter options

### 8.2 Comprehensive KPI Report (`pages/reports/comprehensive.blade.php`)
**Route:** `/reports/comprehensive`
**Description:** Comprehensive KPI tracking report interface
- **Content:**
  - Report title with sector indicator (if sector head)
  - Export section with form:
    - Sector multi-select dropdown (if not sector head)
    - Start Quarter selector
    - End Quarter selector
    - Year selector
    - "Export to Excel" button
    - "Print Report" button
  - Info alerts explaining access level
- **Features:**
  - Quarter-based date range filtering
  - Multi-sector selection
  - Excel export
  - Print functionality
- **Design Notes:** Clean form interface, prominent action buttons

### 8.3 Comprehensive Report Print View (`pages/reports/comprehensive-print.blade.php`)
**Route:** `/reports/comprehensive/print` (POST)
**Description:** Print-optimized view of comprehensive report
- **Layout:** Print-friendly, landscape orientation
- **Content:**
  - Multiple "sheets" as separate pages:
    1. Overall Summary sheet
    2. Grand Summary sheet
    3. Sector Summary Details sheet
    4. Individual Sector sheets (one per sector)
  - Each sheet contains:
    - Header with PDCU title
    - Quarter range subtitle
    - Data tables with performance metrics
    - Performance ratings and counts
- **Features:**
  - Page breaks between sheets
  - Print button
  - Optimized for A4 landscape printing
- **Design Notes:** Table-heavy, print-optimized styling

---

## 9. GALLERY MANAGEMENT PAGES (Admin)

### 9.1 Gallery List (`pages/gallery/index.blade.php`)
**Route:** `/admin/gallery`
**Description:** Admin gallery management page (System Admin only)
- **Content:**
  - Table/list of all gallery items
  - Columns:
    - Thumbnail image
    - Title
    - Caption (truncated)
    - Status (Active/Inactive)
    - Display Order
    - Upload Date
    - Actions (Edit, Delete)
  - "Add New Image" button
  - Pagination
- **Design Notes:** Standard admin table with image thumbnails

### 9.2 Gallery Create (`pages/gallery/create.blade.php`)
**Route:** `/admin/gallery/create`
**Description:** Upload new gallery image form
- **Content:**
  - Image upload field with preview
  - Title input
  - Caption/description textarea
  - Status selector (Active/Inactive)
  - Display order input
  - Save button
  - Cancel button
- **Features:**
  - Image preview before upload
  - File validation
- **Design Notes:** Standard form layout with image preview

### 9.3 Gallery Edit (`pages/gallery/edit.blade.php`)
**Route:** `/admin/gallery/{gallery}/edit`
**Description:** Edit existing gallery item
- **Content:**
  - Current image preview
  - Image replacement option
  - Pre-filled form fields (title, caption, status, display order)
  - Update button
  - Cancel button
- **Design Notes:** Similar to create form with pre-filled data

---

## 10. PUBLIC PROJECT/MDA PAGES

### 10.1 Public MDA Details (`pages/comments/sectors.blade.php` or related)
**Route:** `/mdas/{commitment}/details`
**Description:** Public-facing MDA/commitment details page
- **Content:**
  - MDA/Sector header
  - Commitment information
  - Deliverables list
  - Public comments section (if applicable)
- **Design Notes:** Public-facing, informative layout

### 10.2 Public Project Details (`pages/comments/project-details.blade.php`)
**Route:** `/projects/{commitment}/details`
**Description:** Public-facing project/commitment details
- **Content:**
  - Project header
  - Project description
  - Progress information
  - Comments section
- **Design Notes:** Public information display

---

## 11. COMMON COMPONENTS

### 11.1 Main Layout (`layouts/app.blade.php`)
**Description:** Base layout template for authenticated pages
- **Structure:**
  - Sidebar navigation
  - Topbar navigation
  - Main content area
  - Footer (if applicable)
- **Features:**
  - Responsive design
  - Dark mode support (if implemented)
  - JavaScript/CSS asset loading
- **Design Notes:** Standard admin dashboard layout

### 11.2 Mobile Menu (`commons/menu/mobile.blade.php`)
**Description:** Mobile navigation menu
- **Content:**
  - Hamburger menu toggle
  - Collapsible menu items
  - Same navigation as sidebar
- **Design Notes:** Mobile-optimized, slide-in menu

---

## DESIGN REQUIREMENTS SUMMARY

### Color Scheme
- Primary: Government/professional colors (likely green based on #008751 references)
- Secondary: Complementary colors for actions
- Status Colors: Green (success), Yellow (warning), Red (error)

### Typography
- Headings: Bold, clear hierarchy
- Body: Readable, professional font
- Tables: Smaller font for data density

### Components Needed
1. **Data Tables:** Sortable, filterable, paginated
2. **Forms:** Clean, validated, with clear labels
3. **Modals:** For quick actions (add/edit)
4. **Cards:** For dashboard statistics
5. **Charts/Graphs:** For performance visualization (if applicable)
6. **File Upload:** With preview capabilities
7. **Multi-select Dropdowns:** For sector selection
8. **Quarter Selectors:** Custom dropdowns for Q1-Q4
9. **Status Badges:** For performance ratings, confirmation status
10. **Progress Indicators:** For completion tracking

### Key User Flows
1. **Login → Dashboard → Sector View → Commitment → Deliverable → KPI → Performance Tracking**
2. **Dashboard → Reports → Generate/Download**
3. **Admin → Users → Create/Edit User**
4. **Admin → Gallery → Upload/Manage Images**
5. **Delivery Department → Awaiting Verification → Review → Approve**

### Accessibility Considerations
- Keyboard navigation
- Screen reader support
- High contrast options
- Clear focus states
- Error message clarity

### Responsive Breakpoints
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

---

## NOTES FOR UI REDESIGN

1. **Data Density:** Many pages contain large amounts of data (tables, lists). Design should balance information display with readability.

2. **Hierarchical Navigation:** The system has deep hierarchies (Sector → Commitment → Deliverable → KPI). Breadcrumbs or clear navigation paths are essential.

3. **Role-Based UI:** Different user roles see different content. Design should accommodate conditional elements gracefully.

4. **Performance Focus:** The system is performance-tracking focused. Visual indicators (colors, icons, charts) should clearly communicate performance status.

5. **Government Context:** Professional, trustworthy, accessible design appropriate for government/public sector use.

6. **Print Functionality:** Several reports need print-optimized views. Consider print stylesheets.

7. **Form Heavy:** Many pages are form-intensive. Design should make forms feel approachable and not overwhelming.

8. **Real-time Calculations:** Performance percentages calculate in real-time. Visual feedback is important.

---

**End of Document**
