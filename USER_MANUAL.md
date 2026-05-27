## e-Track47 User Manual

### 1. Introduction

e-Track47 is a performance management and tracking system for planning, monitoring, and reporting government performance across Ministries, Departments, and Agencies (MDAs) / Sectors.

This manual explains how to use the system from the perspective of the **seven supported roles**:

- **System Admin**
- **Governor**
- **Sector Head**
- **Data Admin**
- **Coordinator**
- **Deputy Coordinator**
- **Facilitator**

and covers:

- **Frameworks** (annual performance frameworks and inheritance)
- **MDAs/Sectors, Commitments, Deliverables, and KPIs**
- **Performance tracking, verification, and facilitator decisions**
- **Dashboards and statistics**
- **Reports and exports (print & Excel)**
- **Role-based workflows and best practices**

> **Note**: Screens and labels may vary slightly between deployments, but the workflows and roles described here remain the same.  
> Only the seven roles listed above are used and should be referenced anywhere user roles are discussed.

---

### 2. Roles and Permissions Overview

Each user is assigned one or more roles. Your **active role** determines:

- Which menus you see.
- Which pages you can access.
- What actions you can perform (view, edit, approve, verify, export, etc.).

The system uses exactly these roles:

1. **System Admin**
2. **Governor**
3. **Sector Head**
4. **Data Admin**
5. **Coordinator**
6. **Deputy Coordinator**
7. **Facilitator**

Below is a practical description of each.

#### 2.1 System Admin

- **Purpose**
  - Manages the platform configuration, users, and access.
  - Oversees frameworks and high-level system integrity.

- **Key Responsibilities**
  - Create, edit, and deactivate **user accounts**.
  - Assign and revoke **roles** (Governor, Sector Head, Data Admin, Coordinator, Deputy Coordinator, Facilitator).
  - Create, activate, and archive **frameworks** (annual performance frameworks).
  - Configure general system settings (branding, basic options).
  - Support troubleshooting and liaison with technical support.

#### 2.2 Governor

- **Purpose**
  - Senior executive user who consumes high-level performance information.

- **Key Responsibilities**
  - Access the **Governor’s Dashboard** (statistics) via the **Dashboard** menu.
  - Review **overall average performance** for the active framework/year.
  - Review **performance breakdown by MDA** (ranked from best to worst).
  - Review **KPI status mix** (On Track / At Risk / Delayed).
  - Use filtered views (by year and quarter) where permitted.
  - Use reports and exports prepared by other roles for decision-making.

#### 2.3 Sector Head

- **Purpose**
  - Owns and is accountable for performance within a specific sector/MDA.

- **Key Responsibilities**
  - View and manage **assigned sectors** and their **KPIs**.
  - Review data entered by Data Admins for their sector.
  - Provide narrative explanations and sector-level validation of results.
  - Collaborate with Coordinator and Deputy Coordinator on indicator definitions and targets.
  - Use dashboards and reports to monitor sector performance and drive action.

#### 2.4 Data Admin

- **Purpose**
  - Primary data entry and maintenance role for KPI performance information.

- **Key Responsibilities**
  - Record and update **performance tracking data** by KPI, quarter, and year.
  - Enter actual values, comments/narrative, and attach evidence (where enabled).
  - Ensure entries are complete and submitted before agreed cut-off dates.
  - Respond to comments and correction requests from Sector Heads, Facilitators, Coordinators, and Deputy Coordinators.

#### 2.5 Coordinator

- **Purpose**
  - Leads the design and management of the annual performance framework.
  - Coordinates planning and performance management processes across sectors.

- **Key Responsibilities**
  - Create and update **frameworks** (structure, sectors, commitments, deliverables, KPIs).
  - Manage **framework inheritance** between years.
  - Collaborate with Sector Heads to ensure KPIs are well-defined and aligned.
  - Monitor overall data completeness and quality with Deputy Coordinator and Facilitator support.
  - Use dashboards and comprehensive reports for performance reviews and strategic planning.

#### 2.6 Deputy Coordinator

- **Purpose**
  - Supports the Coordinator in the day-to-day coordination, monitoring, and follow-up.

- **Key Responsibilities**
  - Assist in maintaining **framework structure** and sector/KPI configuration.
  - Track the status of data entry and verification across sectors.
  - Follow up with Sector Heads, Data Admins, and Facilitators to resolve gaps or delays.
  - Prepare working versions of reports and exports for Coordinator and Governor-level review.

#### 2.7 Facilitator

- **Purpose**
  - Acts as a quality assurance and support role within the performance verification workflow.

- **Key Responsibilities**
  - Review KPI performance entries and evidence before they are fully accepted.
  - Provide **facilitator decisions** (accept/confirm, request correction, escalate) with comments.
  - Ensure KPI calculations match the agreed indicator definitions and targets.
  - Support Data Admins and Sector Heads with interpretations and clarifications.

---

### 3. Logging In, Active Role, and Landing Page

#### 3.1 Logging In

1. Open the system URL in your browser.
2. Enter your **email/username** and **password**.
3. Click **Login**.

If you cannot log in:

- Confirm that your credentials are correct.
- Contact the **System Admin** if your account needs to be activated or your password reset.

#### 3.2 Active Role (For Multi-role Users)

Some users may have more than one of the seven roles (e.g., Sector Head + Data Admin).

- If a **role switcher** is available in the header or profile menu:
  - Use it to select the role matching the task you are performing.
  - The navigation menus and permissions will adjust accordingly.

> **Tip**: If you can’t find a screen described in this manual, first verify which role is currently active.

#### 3.3 Welcome / Landing Page

After login, you may see a **welcome or landing page** with high-level information such as:

- **Average Performance** for all MDAs under the **active framework**:
  - Each MDA’s performance is first capped so that any value **above 100% is treated as 101%**.
  - The displayed “Average Performance” is computed from these capped values.

This provides a quick snapshot of overall progress under the current year’s framework.

---

### 4. Frameworks and Annual Cycles

Frameworks define the structure for a specific **year (Fiscal Year)**, including:

- The sectors/MDAs participating.
- Their commitments and deliverables.
- The KPIs used to track performance.

Only **one framework is active** at a time; dashboards and reports default to the active framework.

#### 4.1 Viewing Frameworks

> **Roles**: System Admin, Coordinator, Deputy Coordinator (and read-only for some others depending on configuration).

- Go to the **Frameworks** or **Coordinator** section.
- You will see a list of frameworks with:
  - **Year**
  - **Status** (Draft, Active, Archived)
  - Counts of **Sectors**, **Commitments**, **Deliverables**, and **KPIs**

#### 4.2 Creating a Framework (System Admin / Coordinator)

1. Click **Create Framework**.
2. Provide:
   - Year (Fiscal Year).
   - Name and description.
   - Any other required information.
3. Save to create the framework in **Draft** status.

You can then build the framework structure (sectors, commitments, deliverables, KPIs).

#### 4.3 Framework Inheritance (Coordinator / Deputy Coordinator)

To reuse a previous year’s structure:

1. In the framework list, choose the **source framework**.
2. Click **Inherit / Copy** (label may vary).
3. On the **Confirm Inherit** page:
   - Review the list of **sectors** that can be inherited.
   - Use **Select All** to choose every sector, or select individual sectors.
   - Check the displayed counts:
     - Number of **Sectors** to be copied.
     - Number of **Commitments**, **Deliverables**, and **KPIs**.
     - Number of **KPIs to be inherited** (often highlighted on a summary card).
4. Confirm to start the inheritance process.

The system will:

- Create sectors for the **target framework** mirroring the source framework’s structure.
- Copy all selected sectors’ commitments, deliverables, and KPIs.
- Update links and `framework_id` so all new items belong to the target framework.

> **Important**
> - Only **completed (non-Draft)** frameworks should be used as sources.
> - The system validates that selected sectors belong to the specified source framework.

#### 4.4 Viewing Framework Detail / Structure

From the framework list, click **View / Show** on a specific framework.

The framework detail page typically includes:

- **Header**: framework name, year, status.
- **Summary cards**: counts for sectors, commitments, deliverables, KPIs.
- **Structure Navigator**:
  - Expandable **sectors**.
  - Under each sector: its **commitments**.
  - Under each commitment: **deliverables**.
  - Under each deliverable: **KPIs**.
- **Right-hand sidebar** (if implemented):
  - Metadata (created by, created at, archived by, archived at).
  - Audit information.
  - Export tools (e.g., export framework structure).

Coordinators and Deputy Coordinators should use this view to confirm that the structure is correct each year.

#### 4.5 Activating and Archiving Frameworks

- **Activate** (System Admin / Coordinator):
  - When framework setup is complete, you can mark it as **Active**.
  - Dashboards, MDAs/Sectors pages, and reports default to the active framework.

- **Archive** (System Admin / Coordinator):
  - At the end of a reporting year, once the data is signed off, archive the framework.
  - Archived frameworks remain available for historical reporting but are generally not editable.

> **Best Practice**: Keep one **Active** framework per year, and avoid having multiple frameworks active for the same reporting period.

---

### 5. Managing MDAs / Sectors, Commitments, Deliverables, and KPIs

#### 5.1 MDAs/Sectors List

> **Roles**: Coordinator, Deputy Coordinator, Sector Head (for own sectors), Data Admin (read), Facilitator (read), Governor (read if permitted).

- Navigate to **MDAs/Sectors**.
- The page lists sectors associated with the **active framework**.
- Typical columns:
  - Sector code.
  - Sector/MDA name.
  - Ministry / Department / Agency.
  - Status.

From this page you can:

- **View** sector profiles.
- **Edit** sectors (Coordinator, Deputy Coordinator).
- Navigate to sector-specific KPIs and performance pages.

#### 5.2 Adding a Sector/MDA (Coordinator / Deputy Coordinator)

1. On the MDAs/Sectors page, click **Add MDA/Sector**.
2. Fill required fields: code, name, MDA details, and status.
3. Save.

The system will:

- Automatically link the new sector to the **active framework**.
- Block creation if there is no active framework (ensure one is activated first).

#### 5.3 Sector Details and Structure

Within a sector page, you can typically see:

- Sector information (code, name, MDA type, status).
- Associated **commitments**, **deliverables**, and **KPIs**.

Depending on your role:

- **Coordinator / Deputy Coordinator**
  - Add and edit commitments (with timelines).
  - Add and edit deliverables (descriptions, deadlines).
  - Add and edit KPIs (descriptions, targets, units, status).

- **Sector Head**
  - Review all KPIs, ensure they reflect sector priorities.
  - Provide input on changes where permitted.

- **Data Admin / Facilitator**
  - View KPI definitions to correctly input and review performance.

---

### 6. Performance Tracking, Verification, and Facilitator Decisions

Performance data is typically maintained per **KPI**, per **quarter** (or period), and per **framework/year**.

#### 6.1 Performance Concepts

- **Raw performance value**
  - Often calculated as (actual ÷ target) × 100.
  - May exceed 100% if performance surpasses targets.

- **Adjusted / Average Performance**
  - For reporting consistency, any value **above 100% is capped at 101%**.
  - This capped value is used in averages, rankings, and summary cards.

#### 6.2 Data Entry (Data Admin)

> **Role**: Data Admin (with Sector Head or Facilitator sometimes entering data in small deployments).

1. Navigate to the relevant **performance tracking** or KPI data entry page.
2. Filter by:
   - Year/framework (usually the active framework).
   - Sector and KPI.
   - Quarter or period.
3. For each KPI:
   - Enter actual performance results.
   - Add narrative comments or explanations.
   - Attach supporting documents (if allowed).
4. Save entries, and if applicable, mark them as **submitted** or ready for review.

#### 6.3 Sector Head Review

> **Role**: Sector Head

Sector Heads should:

- Regularly review data for their assigned sector.
- Check:
  - That values are reasonable compared to on-ground activities.
  - That narratives and documents are adequate.
- Provide feedback to Data Admins and, where necessary, request corrections.

This gives each sector a strong internal validation layer before data is used at Governor or coordinator level.

#### 6.4 Facilitator Decisions

> **Role**: Facilitator

Facilitators support quality assurance by reviewing KPI data and evidence.

Typical steps:

1. Open the **Awaiting KPIs** or **Pending Facilitator Decisions** view (label may vary).
2. For each KPI/record:
   - Review indicator definitions (target, baseline, calculation).
   - Verify that the performance value and evidence match the definition.
3. Record a **Facilitator decision**, for example:
   - Accept / Confirm (data is sound).
   - Request correction (Data Admin must revise).
   - Escalate (requires Coordinator/Deputy Coordinator or Sector Head attention).
4. Add detailed comments to document reasoning and guide corrections.

Facilitator decisions:

- Help ensure data integrity and consistency.
- Provide a transparent trail of who confirmed each KPI and when.

#### 6.5 Coordination and Final Verification

> **Roles**: Coordinator, Deputy Coordinator (with input from Sector Heads and Facilitators)

Coordinators and Deputy Coordinators should:

- Monitor summary views showing:
  - Which sectors/KPIs have missing data.
  - Which require facilitator decisions.
  - Which have pending sector-level validation.
- Follow up with Sector Heads, Data Admins, and Facilitators to close gaps.
- Confirm that performance data for the selected quarter/year is **ready for final reporting**.

---

### 7. Dashboards

Dashboards give visual and tabular summaries of performance for quick assessment and decision-making.

#### 7.1 Accessing Dashboards

> **Roles**: All (content differs by role; Governor sees the Governor’s Dashboard by default).

- Use the **Dashboard** menu.
- Depending on your role:
  - **Governor**: sees the **Governor’s Dashboard / statistics** view.
  - **Coordinator / Deputy Coordinator / Sector Head**: may see summary views tailored to coordination or sector monitoring.

#### 7.2 Governor’s Dashboard (Statistics)

> **Primary Role**: Governor (also useful to Coordinator, Deputy Coordinator, and sometimes Sector Heads).

Key properties:

- Focuses on the **active framework** by default.
- All queries filter by `framework_id` of the active framework (or the selected year’s framework).
- If a **Fiscal Year** dropdown is available:
  - It defaults to the **active framework’s year**.

Typical sections:

- **Overall Average Performance**
  - Shows how MDAs are performing on average under the framework.
  - Uses adjusted values with **101% capping** for fairness.

- **Top Performing MDAs/Sectors**
  - Ranks MDAs from **best to worst**.
  - Based on adjusted/average performance (capped at 101%).

- **Performance Breakdown by MDA**
  - Table or chart listing:
    - Each MDA.
    - Performance score.
    - Status or category indicators.
  - Ordered from highest to lowest performance.

- **KPI Status Mix**
  - Distribution of KPIs by category (On Track, At Risk, Delayed).
  - Handles quarters:
    - If “All” is selected, uses the **latest quarter** per KPI.
  - Uses framework-based filtering and 101% capping where relevant.

- **Pending Items / Alerts**
  - Highlights data or verification gaps that require follow-up by Data Admin, Facilitator, Sector Head, Coordinator, or Deputy Coordinator.

Use filters (year, quarter, sector) to adjust the view; the underlying logic always respects the selected framework and period.

---

### 8. Reports and Exports

The **Reports** module provides deeper analytic and printable views, plus Excel exports for offline analysis.

#### 8.1 Reports Index

> **Roles**: Coordinator, Deputy Coordinator, Sector Head, Data Admin, Facilitator, Governor (read-focused).

Steps:

1. Go to **Reports → Index**.
2. Choose:
   - **Fiscal Year** (defaults to **active framework’s year**).
   - Filters such as sector, quarter, or status as needed.

Key elements:

- **Average Performance** card:
  - Uses the same adjusted performance logic (cap at 101%) applied in dashboards and comprehensive reports.

- **KPI Status Mix** card:
  - Mirror of the Governor’s Dashboard logic:
    - Framework-based.
    - Quarter-aware (“All” uses latest quarter per KPI).
    - 101% capping applied where necessary.

This page is ideal for quick high-level analysis before drilling into detailed reports.

#### 8.2 Comprehensive Reports (On-screen)

> **Roles**: Coordinator, Deputy Coordinator, Governor, Sector Head, Data Admin, Facilitator (viewing with different purposes).

Concepts:

- **Framework-based**
  - When you select a year, the system identifies the **framework** for that year.
  - All queries use that framework’s `id` to filter sectors, commitments, deliverables, KPIs, performance trackings, and KPI targets.
  - If there is no framework for the year, an error or message is shown.

- **Consistent performance calculation**
  - All average or “adjusted performance” metrics cap any underlying performance > 100% at **101%**.

Usage:

1. Go to **Reports → Comprehensive** (or equivalent).
2. Select:
   - Year (mapped to a framework).
   - Optionally a sector or other filters.
3. Click **Generate/View**.

You will see:

- Overall summary of performance for the framework.
- Grand summary across sectors.
- Sector-level summaries and detailed breakdowns.
- KPI-level performance, often with counts and status splits.

#### 8.3 Printable Comprehensive Report

> **Roles**: Coordinator, Deputy Coordinator, Governor, Sector Head, Facilitator (for review).

From the comprehensive report:

1. Click **Print** or **Printable Version**.
2. A print-ready view opens:
   - Important text columns for MDAs (e.g., “Sector/MDA”, “Ministries and Agencies”) are **left-aligned**.
   - Layout is optimized for A4/Letter printing.
3. Use your browser’s **Print** dialog to print or **Save as PDF**.

#### 8.4 Excel Export

> **Roles**: Coordinator, Deputy Coordinator, Governor (for high-level packs), Sector Head (for deep dives), Facilitator (for quality review), and Data Admin (for technical checks).

1. From the comprehensive reports area, select the desired **Fiscal Year** and filters.
2. Click **Download Excel** (or similar).
3. Confirm export.

The system:

- Resolves the framework associated with the selected year.
- Filters all queries by this framework.
- Populates one or more sheets (Overall Summary, Grand Summary, Sector summaries, individual sector detail).
- Uses adjusted performance (101% capping) consistently.

Open the resulting `.xlsx` file in Excel or any compatible tool to conduct more detailed analysis or share in meetings.

---

### 9. Data Integrity and Performance Logic

To ensure that all charts and reports tell a consistent story, e-Track47 applies standardized rules across the platform.

#### 9.1 Framework Filtering

- Every major performance query:
  - Filters sectors, commitments, deliverables, KPIs, performance_trackings, and related tables by **framework_id**.
  - Uses either:
    - The **active framework**, or
    - The **framework resolved from the selected year**.

#### 9.2 101% Capping

- When calculating averages or adjusted performance:
  - Any performance value **above 100% is capped at 101%** before aggregation or ranking.
- This prevents extreme over-performance on a few KPIs from distorting overall averages or comparisons.

#### 9.3 Ranking MDAs

- In views like **Performance Breakdown by MDA**:
  - MDAs are **sorted from highest to lowest** using the adjusted performance score.
  - The 101% cap is applied before sorting.

These rules apply to:

- **Welcome / landing page** average performance.
- **Governor’s Dashboard / statistics** view.
- **Reports index** cards (Average Performance, KPI Status Mix).
- **Comprehensive reports** (on-screen, print, and Excel).

If numbers appear inconsistent between pages, always:

1. Confirm that you are comparing the **same framework/year**.
2. Confirm that filters (sector, quarter, etc.) are identical.
3. Check whether any KPIs have newly updated or unverified data.

---

### 10. Common Workflows by Role

#### 10.1 Governor

1. Log in.
2. Go to **Dashboard** (Governor’s Dashboard).
3. Adjust **year** and **quarter** filters if needed.
4. Review:
   - Overall average performance.
   - Top and bottom MDAs.
   - KPI status mix.
5. Request supporting reports or explanations from Coordinator or Deputy Coordinator.

#### 10.2 System Admin

1. Manage user accounts:
   - Create new users.
   - Assign roles (System Admin, Governor, Sector Head, Data Admin, Coordinator, Deputy Coordinator, Facilitator) as appropriate.
2. Manage frameworks:
   - Create frameworks for new years.
   - Coordinate with Coordinators and Deputy Coordinators to verify framework structures.
   - Activate/Archive frameworks at the right time.
3. Address access or configuration issues raised by other users.

#### 10.3 Coordinator

1. Design or update the **framework** at the start of each cycle (introduce or adjust sectors, commitments, deliverables, KPIs).
2. Use **inheritance** to copy structure from the previous year if appropriate.
3. Work with Sector Heads to finalize KPI sets and targets.
4. Throughout the year:
   - Monitor data completeness and quality.
   - Use comprehensive reports and dashboards to support reviews.
   - Coordinate with Deputy Coordinators, Data Admins, and Facilitators to resolve data issues.

#### 10.4 Deputy Coordinator

1. Support the Coordinator in day-to-day coordination:
   - Monitor dashboards and reports for missing or inconsistent data.
   - Track which sectors are lagging and follow up.
2. Help prepare materials (print reports, Excel exports) for review meetings.
3. Assist in confirming that all sectors have complete and verified data for each quarter.

#### 10.5 Sector Head

1. Go to **MDAs/Sectors** and select your sector.
2. Review sector KPIs and confirm they are aligned with your mandate.
3. Periodically review:
   - KPI data entered by Data Admins.
   - Facilitator comments and decisions.
4. Provide sector-level interpretations, clarifications, and feedback.
5. Use dashboards and reports to manage performance and communicate with your team.

#### 10.6 Data Admin

1. Use performance tracking screens to enter/update KPI data for the correct framework, sector, and quarter.
2. Add detailed comments and upload evidence as required.
3. Mark entries ready for review where the workflow requires it.
4. Respond quickly to Facilitator and Sector Head comments or correction requests.

#### 10.7 Facilitator

1. Open the pages showing KPIs that **await facilitator review**.
2. For each KPI:
   - Confirm that collected data matches the KPI’s definition and target.
   - Validate that evidence is adequate.
3. Record a **facilitator decision** and comments.
4. Coordinate with Data Admins, Sector Heads, and Coordinators when issues arise.
5. Help ensure that only validated data is used in final dashboards and reports.

---

### 11. Troubleshooting and Support

Common issues and actions:

- **Cannot access a page or feature**
  - Check your active role.
  - Confirm with **System Admin** that your role has the necessary permissions.

- **No sectors listed under MDAs/Sectors**
  - Ensure that a **framework is active**.
  - Check if you have filters activated (e.g., incorrect year).

- **Numbers in different reports don’t match**
  - Make sure you are:
    - Looking at the same **framework/year**.
    - Using the same **filters** (sector, quarter).
  - Remember the **101% capping** affects aggregate metrics.

- **Excel or print report missing data**
  - Confirm that:
    - The correct year and sectors are selected.
    - Data has been entered and verified for the chosen period.

When you report a problem, always provide:

- Your **username** and **role**.
- The **page name / URL**.
- The **framework/year**, sector, and quarter selected.
- Screenshots and a short description of what you expected vs. what you saw.

---

### 12. Best Practices

- Maintain a clear **annual cycle**:
  - Finalize frameworks before data entry.
  - Keep one active framework per reporting year.
- Clarify roles:
  - Who enters data (Data Admin).
  - Who verifies quality (Facilitator).
  - Who validates sector results (Sector Head).
  - Who coordinates across sectors (Coordinator and Deputy Coordinator).
  - Who oversees access and configuration (System Admin).
  - Who consumes executive summaries (Governor).
- Use **comprehensive reports and Excel exports** for formal reviews and performance boards.
- Encourage detailed narrative and documentation alongside numerical results.
- Review dashboards regularly, not just at year-end, to make timely course corrections.

---

### 13. Getting Help

If you need help:

- For **how-to / workflow questions**:
  - Speak with your **Coordinator**, **Deputy Coordinator**, or **Sector Head**.
- For **access or configuration issues**:
  - Contact the **System Admin**.
- For **technical problems** (errors, page not loading, performance issues):
  - Capture a screenshot and error message.
  - Note the steps you took and the time.
  - Share these details with your System Admin or technical support channel.

e-Track47 is built to make performance management structured, transparent, and comparable across years and sectors. Using the system according to the roles and workflows in this manual will help you get the most value from it.

