# Google Stitch Prompt — PDCU e‑Track47 Mobile App

> Source web application: Laravel 10 "e‑Track47" — the **Performance Delivery Coordination Unit (PDCU)** management system for Jigawa State (Nigeria). This document is a single, self‑contained, paste‑ready prompt for Google Stitch. It is written from the perspective of a senior product designer and mobile architect and is organised so a designer (or generative tool) can produce every screen of the mobile app without needing to reference the original codebase.

---

## How to use this document

Paste the contents below into Google Stitch as a single prompt, or paste it section‑by‑section if Stitch limits prompt length. Stitch should treat each numbered screen as one artboard and each role variation as a sibling artboard with the suffix listed.

Recommended Stitch settings:
- Platform: **Mobile — iOS & Android (single shared design)**
- Orientation: **Portrait primary; landscape only for charts & comprehensive reports**
- Density: **@1x, @2x, @3x**
- Theme: **Light primary, Dark variant**
- Font: **Public Sans** (Google Fonts) — fallbacks `Inter`, `SF Pro`, `Roboto`
- Icon set: **Material Symbols Outlined** (FILL 0, weight 400)
- Component library: **Material 3 patterns blended with iOS Human Interface Guidelines** (rounded cards, large titles, bottom sheets, segmented controls)

---

# 1. Product context (read this first)

**Product name:** PDCU e‑Track47 Mobile
**Product purpose:** A mobile companion for the Jigawa State Performance Delivery Coordination Unit. It lets government officials track Ministry / Department / Agency (MDA) performance against an annual Framework of Commitments, Deliverables, and KPIs — capture quarterly actual results, attach evidence, route data through a four‑stage review chain (Sector Head → Facilitator → Coordinator), and surface dashboards for executives.

**Target users (7 roles):**
1. **System Admin** — manages users, gallery, and platform settings.
2. **Governor** — read‑only executive dashboards and reports.
3. **Coordinator** — owns the annual Framework, runs the final approval queue, oversees Data Entry windows.
4. **Deputy Coordinator** — assists the Coordinator; same screens with a subset of edit rights.
5. **Sector Head** — accountable for one MDA/Sector; approves Data Admin submissions in bulk.
6. **Data Admin** — primary KPI data entry for one MDA/Sector; uploads evidence.
7. **Facilitator** — quality assurance reviewer assigned to one or more sectors; accepts or rejects after Sector Head approval.

Plus an unauthenticated **Public** persona that can browse the public gallery only.

**Core hierarchy of data:**
`Framework (annual)` → `Sector / MDA` → `Commitment (project)` → `Deliverable (output)` → `KPI` → `PerformanceTracking (per quarter, per year)`.

**Approval pipeline for every PerformanceTracking row:**
`PDCU sets milestone → Data Admin enters actual value & evidence → Sector Head bulk‑approves → Facilitator Accept/Reject (with delivery dept. value) → Coordinator Confirms/Rejects (final)`.

**Status values for PerformanceTracking** (use as badges everywhere):
- `Not Confirmed` (grey) — milestone only.
- `Pending Sector Head Approval` (amber).
- `Pending Facilitator` (blue).
- `Pending Coordinator` (violet).
- `Confirmed` (emerald) — locked, appears in reports.
- `Rejected` (red) — with rejection reason.

**Performance bands** (used in charts, tiles, badges):
- Excellent ≥100 % (emerald)
- Good 70–99 % (teal)
- Fair 40–69 % (amber)
- Poor <40 % (red)

> Note: Performance is **capped at 101 %** before aggregation for averages and rankings; show raw % on the row and adjusted % on the aggregate.

---

# 2. Design system

## 2.1 Brand & palette

- **Primary** `#008751` (Nigeria green — the brand colour). Use for actions, focused states, active nav.
- **Primary variants**: `primary/10`, `primary/20`, `primary/30` (alpha tints) for chip backgrounds and progress trails.
- **Surface light** `#F6F6F8`, surface dark `#101622`.
- **Card**: `#FFFFFF` light / `#1A2233` dark, 1 px `primary/10` border, 16 px radius, soft shadow `0 1px 2px rgba(0,0,0,.04)`.
- **Semantic colours**:
  - Success / Confirmed / Excellent: `#10B981` (emerald‑500)
  - Info / Pending Facilitator: `#3B82F6`
  - Warning / Pending Sector Head / Fair: `#F59E0B` (amber‑500)
  - Danger / Rejected / Poor: `#EF4444`
  - Neutral text 900 `#0F172A`, 600 `#475569`, 400 `#94A3B8`.
- **Sector accent**: each sector gets a deterministic accent from an 8‑colour palette (used for avatar badges and chart series).

## 2.2 Typography

- **Display**: Public Sans, 700, –1 % tracking. Use for screen titles (28 / 24 / 20 sp).
- **Body**: Public Sans 400 / 500 at 14 sp; small 12 sp.
- **Numerals**: tabular figures for all KPI numbers, percentages, currency.
- **Currency**: Nigerian Naira, ₦, thousands separated.

## 2.3 Spacing & layout

- 4 pt base unit. Page padding 16 dp. Section gap 16–24 dp. Card padding 16 dp.
- **Safe areas** respected on all screens (notch, home indicator).
- **Touch targets** ≥ 44 × 44 dp.
- **Radii**: cards 16, chips 999, inputs 12, buttons 12.

## 2.4 Components (must be reusable across the app)

1. **AppBar** — large title variant (collapses to compact on scroll), back chevron, optional trailing icon button. Right side may host the framework/year chip and a notifications bell with red‑dot badge.
2. **Bottom navigation** — 4–5 tabs, role‑dependent (see §4 Navigation).
3. **Side drawer (optional)** — for less frequent destinations (My Profile, Help, Logout).
4. **Status badge** — pill with dot icon, 6 semantic variants matching the workflow + performance.
5. **KPI tile** — KPI name, target & unit, four quarter dots (Q1‑Q4) each coloured by status, footer with percentage achieved.
6. **Sector card** — sector name, ministry chip, overall performance ring, completed/at‑risk counts.
7. **Commitment card** — commitment title, status pill, progress bar, deliverable count, due date.
8. **Quarter selector** — segmented control (Annual / Q1 / Q2 / Q3 / Q4) — primary filter UI.
9. **Year selector** — chip dropdown; defaults to active Framework's year; archived years are visually muted.
10. **Sector picker** — bottom‑sheet searchable list with multi‑select (for Coordinator/Governor filters).
11. **Performance ring** — 64 / 96 / 144 dp variants; colour follows performance band.
12. **Evidence chip** — file thumbnail, name, size, remove icon (or download icon in read‑only).
13. **Comment row** — avatar, name, role chip, timestamp, body, optional reply button.
14. **Empty state** — illustration (line art on primary/5), 1‑line headline, helpful sub‑copy, primary CTA.
15. **Loading state** — skeleton blocks matching the card hierarchy (no spinners on lists).
16. **Error state** — inline banner with retry; full‑screen variant with illustration for hard failures.
17. **Toast / Snackbar** — bottom of screen, 3.5 s, swipeable, with semantic colour stripe.
18. **Bottom sheet** — used in place of every Bootstrap modal from the web app (90 % of the time).
19. **Stepper** — used for the multi‑section "Confirm Performance" flow.
20. **Inline alert** — info, success, warning, danger.

## 2.5 Motion

- Use platform‑native page transitions (push from right on iOS, shared‑axis on Android).
- Status changes animate the badge dot (pulse for "pending", solid for "confirmed").
- Charts animate on first render (300 ms ease‑out); skip animation on filter changes >2 chars to keep typing snappy.

## 2.6 Accessibility

- AA contrast minimum; AAA for body text on white.
- Every chart has a "View as table" toggle.
- Every numeric performance value reads as `"82 percent, fair"` (band included) for screen readers.
- Form errors are programmatically associated with inputs and announced.
- Support Dynamic Type / large font scaling up to 200 %.
- All status colours are also conveyed with an icon and label — never colour alone.
- RTL layout safe (the project may later support Hausa/Arabic transliterations).

## 2.7 Localisation & data formatting

- Default language: English (Nigeria). All copy in plain English, no jargon.
- Dates: `21 May 2026` (long) or `21/05/2026` (compact in tables).
- Numbers: thousand‑separators; percentages with one decimal where the value < 10 %.
- Currency: ₦ prefix, no decimals for sums ≥ ₦1,000.

---

# 3. Information architecture

```
Public
 ├─ Welcome / Marketing
 ├─ Public Gallery
 │   └─ Public Gallery Detail
 └─ Sign in

Authenticated app (role‑gated)
 ├─ Tab 1: Dashboard            (all authenticated except System Admin)
 ├─ Tab 2: My Work / Queue      (role‑specific contents)
 │   ├─ Sector Head: Approval queue
 │   ├─ Data Admin: My KPIs & submissions
 │   ├─ Facilitator: Awaiting Verification
 │   ├─ Coordinator / Deputy: Final Review queue
 │   └─ Governor: My alerts
 ├─ Tab 3: Sectors / Browse     (the data hierarchy)
 │   └─ Sector → Commitment → Deliverable → KPI → Tracking detail
 ├─ Tab 4: Reports & Analytics
 └─ Tab 5: More
     ├─ Framework Management    (Coordinator only)
     ├─ Data Entry Windows      (Coordinator / Deputy)
     ├─ Users                   (System Admin)
     ├─ Gallery Management      (System Admin)
     ├─ Notifications
     ├─ My Profile
     ├─ Settings
     └─ Help & About
```

Role-to-tab visibility matrix (use this verbatim):

| Tab           | SysAdmin | Governor | Coordinator | Dep. Coord. | Sector Head | Data Admin | Facilitator |
|---------------|:--------:|:--------:|:-----------:|:-----------:|:-----------:|:----------:|:-----------:|
| Dashboard     |     —    |     ✓    |      ✓      |      ✓      |      ✓      |      ✓     |      ✓      |
| My Work       |     —    |     ✓    |      ✓      |      ✓      |      ✓      |      ✓     |      ✓      |
| Sectors       |     ✓    |     ✓    |      ✓      |      ✓      |     own     |    own     |  assigned   |
| Reports       |     —    |     ✓    |      ✓      |      ✓      |      ✓      |      ✓     |      ✓      |
| More          |     ✓    |     ✓    |      ✓      |      ✓      |      ✓      |      ✓     |      ✓      |

System Admin's primary tabs are: **Users / Gallery / Settings / More** instead of the standard set.

---

# 4. Global navigation specification

- **Bottom tab bar**: 5 items, labelled icons, primary colour for selected. On Android, follow Material 3 navigation bar; on iOS, native tab bar.
- **Top app bar**: large title that collapses to standard on scroll. Right side shows a **framework/year chip** (`FY 2026 ▾`) and a **notifications bell**. Tapping the year chip opens a bottom sheet listing available Frameworks (Active highlighted, Archived muted).
- **Universal search**: pull‑down on Dashboard or via search icon in app bar. Searches across Sectors, Commitments, Deliverables, KPIs, and Users (admin only).
- **Global FAB**: only on screens where a primary "Create" action exists for the role (e.g. Data Admin on Deliverable → "Add Tracking"; Coordinator on Framework → "Create Framework").

Transitions:
- Drill‑down (Sector → Commitment → Deliverable → KPI → Tracking) uses a push transition with shared‑element animation of the title text.
- Approve / Reject decisions trigger a confetti‑less success animation (checkmark draw) followed by an automatic return to the queue.

---

# 5. Screen catalogue

Each screen below is fully specified. Stitch should produce **the screen as described, plus all the listed states and role variations**.

> **Notation:**
> - **#** = screen number (use as artboard ID).
> - "States" = every variant Stitch must render (Loading, Empty, Error, Success, Disabled, Offline where relevant).
> - "Roles" = which roles see this exact screen, and what changes per role.

## 5.1 Onboarding & Auth

### #1 Splash
- Centred Jigawa State crest with `PDCU` lockup beneath. Subtitle: *"Performance Delivery Coordination Unit"*. Brand‑green background with subtle Jigawa state map watermark at 10 % opacity.
- 1.2 s hold then animated transition to Welcome.
- States: Loading (default), Update Required (forced update card with link to store), Network Down (retry).

### #2 Welcome / Marketing
- Hero image of state infrastructure (use stock placeholder).
- Headline: *"Tracking delivery for every Naira."*
- Sub: *"Sign in to view your sector's performance, or browse the public gallery."*
- Primary CTA: **Sign In**. Secondary CTA: **Browse Public Gallery**.
- Footer: version number, build, "© Jigawa State Government".

### #3 Sign In
- Card with large title **Sign In**.
- Fields: **Email Address** (with mail icon, validates email format), **Password** (with lock icon + show/hide eye toggle).
- Row: **Remember me** checkbox (left), **Forgot password?** link (right — UI only; backend route hidden).
- Primary button: **Sign In** (full width).
- Below: small text *"By signing in you accept the PDCU terms of use."* with link.
- Error state: red inline banner above the form ("Email or password incorrect.").
- Success state: brief progress overlay then routes to role‑specific landing.
- Offline state: yellow banner *"You're offline. You can review previously synced data after sign‑in."*

### #4 Forced Password Change
- Triggered on first sign‑in for newly created accounts. Same layout, two fields: New Password, Confirm Password, with live strength meter.

### #5 Role Picker (multi‑role users)
- Appears only if the user has more than one active role (e.g. Coordinator who is also Sector Head).
- Vertical list of role cards (icon, role name, scope ("Sector: Ministry of Health" / "All sectors")).
- Tap to enter the app with that role; user can switch later from More → Switch Role.

## 5.2 Dashboards (role‑specific landings)

### #6 Governor Dashboard
- Top: Greeting *"Good morning, Your Excellency."* with date.
- **Filter row** (sticky): sector multi‑select chip, year chip, quarter segmented control (Annual / Q1 / Q2 / Q3 / Q4).
- **Hero scorecard (large)**: Average performance ring (96 dp), big % number, mini bar sparkline, sub‑label *"Q[n] FY 20XX"*.
- Two stacked cards:
  - **Top performing sector** — sector name, KPI count, progress bar.
  - **Pending verifications** — large number, link to Final Review queue.
- **Sector‑wide comparison chart** — horizontal bar list, planned vs actual %, sortable. Use 8 sector accent colours.
- **KPI status mix donut** — total KPIs centred, segments: On Track / At Risk / Delayed with counts and %.
- **Top 5 / Bottom 5 sectors** — toggle, list rows with avatar circle, sector name, % delta arrow.
- States: Loading skeleton, Empty (no framework selected), Error.

### #7 Coordinator / Deputy Coordinator Dashboard
- Same shell as Governor but adds two action tiles at the top:
  - **Final Review queue** with red badge count.
  - **Data Entry windows** showing how many sectors are currently open.
- "Submission rate" tile — % of expected entries received this quarter, with progress arc.
- "Framework status" card — active framework name, year, last archive date, "Manage" CTA.

### #8 Sector Head Dashboard
- Hero card: **My Sector — [Sector Name]**, performance ring, KPI count.
- **Approval queue tile**: large pending count + **Review & Approve** CTA. If zero, tile shows empty state with green check.
- Quick stats grid: Total Commitments / Completed / In Progress / At Risk (4 mini cards).
- **Quarterly performance table**: rows = commitments, cols = Q1–Q4, cells colour‑coded by status.
- Year + Quarter selectors above the table.

### #9 Data Admin Dashboard
- Hero card: **My Sector — [Sector Name]** with submission progress arc ("3 of 12 KPIs entered for Q2").
- **My next deadlines** card list — KPI name, due date, "Enter actual" CTA.
- Recent activity feed: my submissions and their current status with timeline icon.
- Quick action: floating **Add Tracking** button taking user straight to KPI picker.

### #10 Facilitator Dashboard
- Hero: number awaiting my review (red badge), **Open Review** CTA.
- Sector list (only the sectors I'm assigned to): each row shows sector name, # awaiting, last reviewed timestamp.
- Recent decisions feed (Accepted / Rejected with sector and date).

### #11 System Admin Dashboard
- Tiles: Users (total count + active vs revoked), Gallery items, Frameworks (active count), Login activity (last 24 h).
- Quick actions: Add User, Add Gallery Image, Lock all data entry, Create framework.
- "Security log" feed (last 10 entries).

## 5.3 My Work / Queue screens

### #12 Sector Head — Approval Queue
- Title: **Awaiting my approval**.
- Sticky filter: year, quarter, search.
- List rows grouped by Commitment → Deliverable → KPI. Each row shows KPI name, quarter, actual value submitted, submitting Data Admin's name & timestamp, evidence count icon, and an action checkbox for bulk select.
- Floating action bar appears on selection: **Approve N selected** (primary) and **Open detail** if exactly one selected.
- States: Empty ("All clear! No submissions awaiting your approval."), Error, Pull‑to‑refresh.
- Bottom sheet on Approve confirms the count and asks for optional notes.

### #13 Facilitator — Awaiting Verification (list)
- Title: **Awaiting Verification**.
- Filters: sector picker (multi if facilitator has multiple sectors), year, quarter.
- Two view modes: **By Sector** (grouped cards) and **By KPI** (flat list).
- Each row: KPI name, sector, quarter chip, actual value, sector head approval badge, "Review" CTA.
- Empty state copy varies by role (Facilitator: *"No records awaiting your review. All records have been processed or there are none approved by Sector Heads yet."* | Coordinator: *"No records currently awaiting confirmation."*).

### #14 Facilitator — KPI Review Sheet (detail)
- Reached by tapping a row on #13. Implemented as a **full‑screen route with a sticky bottom action bar**, not a modal.
- Header: KPI title, sector, quarter (Q1 / Q2 / Q3 / Q4) chip, year.
- **Submission section** (read‑only): Quarter, Year, Tracking date, Milestone (PDCU set), Actual value (Data Admin), Remarks, Attachments list (tap to preview).
- **Decision section** (interactive): segmented control **Accept** / **Reject**.
  - On **Accept**: required fields appear — Delivery Department Value (number) and Remarks (textarea).
  - On **Reject**: required field — Rejection reason (textarea, 500 char limit, live counter).
- Bottom sticky bar: **Cancel** and **Submit Review**.
- States: Loading, Error, Submitting (button shows progress), Success (returns to #13 with toast).

### #15 Coordinator — Final Review Queue
- Title: **Final Coordinator Review**.
- Three grouped tabs at the top: **Commitments**, **Deliverables**, **KPIs** — reflect the three coordinator drill levels.
- Each tab shows a list of items awaiting final approval. KPI tab is the canonical one and most used.
- Same filter row (sector picker / year / quarter).
- Tapping a KPI opens a screen mirroring #14 but the bottom action becomes **Confirm** / **Reject (final)**. Confirming locks the record into reports.

### #16 Data Admin — My KPIs (queue)
- Title: **My KPIs — [Sector]**.
- Year + quarter selectors. Status filter chips: All / Pending entry / Pending sector head / Pending facilitator / Pending coordinator / Confirmed / Rejected.
- List of KPI cards: KPI name, target & unit, the four quarter dots, last update.
- Tap to enter the KPI Tracking screen (#28).

## 5.4 Sectors & data hierarchy (browse)

### #17 Sectors List
- Title: **MDAs / Sectors**.
- Search bar.
- For Coordinator/Governor: full list. For Sector Head/Data Admin: redirected straight to their own sector. For Facilitator: list of assigned sectors only.
- Each row: sector accent avatar, sector name, ministry, overall % ring, completed/at‑risk badges, chevron.
- Empty state for new framework: "No sectors yet. Inherit from previous year?" with CTA → #39.

### #18 Sector Overview
- AppBar large title: sector name. Sub‑title: short description.
- **Quick summary** grid (2x2 on mobile): Total Commitments / Completed / In Progress / At Risk.
- **Approval row** (visible only to Sector Head): pending count + **Review & Approve** CTA.
- **Facilitator row** (visible only to Facilitator): pending count + **Awaiting Verification** CTA.
- Year + quarter selectors (sticky below the AppBar).
- **Search** input for commitments.
- **Commitment list** — cards (see #19). On tap → #20.
- FAB (Coordinator/Deputy/System Admin only): **Add Commitment**.

### #19 Commitment card (used inside #18)
- Card: title, status pill, progress bar, KPI count, due date, sector accent stripe on the left edge.
- Long‑press shows context menu: Edit / View / Delete (role‑gated).

### #20 Commitment Detail (Deliverables list)
- AppBar: commitment title, breadcrumb mini text *"Sector › Commitment"*.
- Header card: status pill, sector, description (collapsible after 3 lines), overall progress arc, completed/total, **Next milestone** date.
- Stat strip: Total Deliverables, Active KPIs, At Risk count, Next milestone (mini).
- **Deliverable list** — cards with name, KPI count, progress bar, status pill.
- FAB (role‑gated): **Add Deliverable**.

### #21 Deliverable Detail (KPIs list)
- AppBar: deliverable name. Sub: parent commitment.
- Card row: budget (if any), year selector chip.
- **Progress stats** mini‑grid: Total KPIs / Average progress / Status / Last updated.
- **KPI list** — KPI tile component (see §2.4 #5). Tapping → #28.
- FAB (Coordinator/Deputy/Data Admin where applicable): **Add KPI** or **Set Target** (separate options in a small popup menu).

### #22 KPI Quick View (long‑press preview)
- Bottom sheet preview: KPI name, target & unit, year, four quarter dots, last comment, **Open** CTA.

## 5.5 Performance tracking flows

These are the most important flows in the app — design them with extra care.

### #23 PDCU "Set Milestone" (Coordinator / Deputy / Facilitator pre‑Data Admin)
- Bottom sheet. Title: **Set milestone — [KPI name]**.
- Fields: Quarter (segmented), Year (chip), Milestone value (number), Tracking date (optional, date picker), Remarks (textarea).
- Inline help: *"Milestone is the planned achievement for this quarter. Data Admin will enter actual results later."*
- Submit → returns to KPI screen with toast: *"Milestone set."*

### #24 Data Admin "Add / Edit Tracking"
- Full‑screen for clarity. Title: **Add Performance Tracking** (or **Update Performance Tracking** if editing).
- Header chip strip: KPI name (read‑only), Quarter (read‑only), Year (read‑only).
- Fields (* = required):
  - **Tracking date*** (date picker, max = today).
  - **Milestone** (read‑only, set by PDCU).
  - **Actual value*** (number, decimal allowed, suffix shows unit of measurement).
  - **Remarks** (textarea, 1000 char limit, counter).
- **Attachments** section: drag‑and‑drop on tablet; on phone an "Add evidence" button opens a sheet (Camera / Photo Library / Files). Each added attachment shows the **Evidence chip** component (filename, size, remove).
  - Allowed types: jpg, jpeg, png, xlsx, xls, doc, docx, pdf.
  - Max 20 MB per file. Multiple files allowed.
  - Live total size meter.
- **Auto‑save** indicator: small text *"Saved draft 2 s ago"* below the title; restores on reopen.
- Bottom sticky bar: **Save draft** (secondary) and **Submit for review** (primary).
- States: Loading (skeleton), Saving (button disabled with spinner), Validation error (red inline), Submitted success (toast + status badge updates to *Pending Sector Head Approval*).
- **Offline**: form remains editable; submit queues for sync; show banner *"Offline — will sync when online."* and queued item icon.

### #25 Bulk Sector Head Approval
- Reached from #12 or Sector Head Dashboard.
- Header card: count of items, year + quarter pill.
- List rows checkable; "Select all" top row.
- Filters: by Commitment, by Deliverable.
- Sticky bottom bar: **Approve [n] selected** primary + **Cancel**.
- Confirmation bottom sheet: shows list summary and optional Sector Head notes (textarea). Confirm posts and returns with success toast and pulse animation on each row that disappears.

### #26 Facilitator Decision Sheet
- Same as #14, listed here for completeness because it's reachable from multiple paths (Dashboard, Queue, KPI detail). Ensure the sheet is visually identical regardless of entry point.

### #27 Coordinator Final Verify Sheet
- Visual layout same as #26 but the action buttons are **Confirm** (emerald) and **Reject (final)** (red).
- Adds a section above the buttons: **Facilitator decision summary** — date, facilitator name, decision pill, delivery dept. value, facilitator notes. Read‑only.
- On Confirm: success animation + the tracking record becomes **Locked** (lock icon appears next to its quarter in all screens henceforth).

### #28 KPI Tracking Detail Screen
- Single screen showing all four quarters of a KPI for a chosen year.
- Header: KPI name, deliverable, commitment, sector breadcrumb. Target value + unit. Year chip.
- **Quarter tabs** (segmented Q1 / Q2 / Q3 / Q4) — selecting jumps to the corresponding section.
- For each quarter (also shown as cards stacked vertically for one‑hand scroll):
  - Quarter title with status badge.
  - Milestone value (planned).
  - Actual value (or "Awaiting Data Admin").
  - Tracking date.
  - Remarks.
  - Delivery Department value (if facilitator accepted).
  - Facilitator decision badge + reason (if rejected).
  - Coordinator final status with locked icon when confirmed.
  - **Attachments** carousel — thumbnails (PDF/Image/Office icons), tap to preview, long‑press to download.
  - **Action button** dynamic by role/state:
    - Data Admin + no actual yet → **Enter actual value** (opens #24).
    - Data Admin + record rejected → **Resubmit** (opens #24 with fields prefilled).
    - Sector Head + actual present, not approved → **Approve** (single‑item shortcut).
    - Facilitator + sector head approved → **Review** (opens #26).
    - Coordinator → **Verify** (opens #27).
- States: Loading, Empty (no tracking yet — shows CTA depending on role), Error.

### #29 Attachment Preview
- Full‑screen viewer for images and PDFs; office files open with a pre‑fetched preview thumbnail and a **Open externally** CTA.
- Bottom action bar: Share (where allowed), Download, Delete (if uploader and not yet approved).

## 5.6 Reports & analytics

### #30 Reports Hub
- AppBar: **Reports**.
- Top action row: **Comprehensive KPI Report** (large card with icon, CTA), **Generate Word Report** (card).
- **Filter card**: Sectors multi‑select chip, Fiscal year chip, Quarter segmented control.
- Below: identical scorecards and charts as #6 Governor Dashboard, but in a "Reports" framing (Average Performance / Top Sector / Pending Verifications, Comparison bar chart, KPI Status Mix donut).
- Sticky bottom bar: **Export** popup menu (Excel, Word, PDF, Print).
- For Sector Head/Data Admin: filter is pre‑constrained to own sector and not editable; an info banner says *"You can only export your sector."*

### #31 Comprehensive Report — Setup
- Form to choose: sectors (multi or all), fiscal year, optional quarter, include evidence? (toggle).
- Inline preview card: "Report will contain 12 commitments, 47 deliverables, 132 KPIs."
- Primary CTA: **Generate** (loading state with progress bar because the report can be heavy).

### #32 Comprehensive Report — Viewer
- Long horizontally scrollable table (mobile pinches/zooms). On phones default to **List view** that turns each KPI into a card with the 10 columns rendered as label/value rows:
  1. No.
  2. Deliverable.
  3. KPI.
  4. Results No.
  5. Target.
  6. Jan–Dec Results.
  7. Performance %.
  8. Adjusted Performance % (capped 101 %).
  9. Evidence count (tap to expand).
  10. Notes.
- Header strip: sector / commitment groupings — sticky.
- Right‑side index (FAB tray) to jump between sectors.
- Download as Excel / Word / PDF actions from the AppBar overflow menu.

### #33 Word Report Generator
- Step 1: choose sector, year, quarter.
- Step 2: cover page metadata (report title, prepared by, date).
- Step 3: preview pages — long scroll.
- Step 4: download with progress indicator.

### #34 Print Preview
- A read‑only paginated view of the report with page numbers; phones default to **Share to printer** action.

## 5.7 Framework management (Coordinator)

### #35 Framework List
- Title: **Frameworks**.
- Stats trio: Active count (animated pulse dot), Archived count, Latest framework name.
- List of frameworks: name, year, status pill (Active / Archived), sector count, created date, kebab menu (View / Archive / Restore / Set active).
- FAB: **Create framework**.

### #36 Create Framework
- Step 1 — Basics: Name, Year (number), Description.
- Step 2 — Sectors: choose to start blank or **Inherit from previous year** (toggle). If inheriting, choose source framework and confirm in #38.
- Step 3 — Review & create.

### #37 Framework Detail
- Header card: name, year, status pill, created by, archived by + date if archived.
- Tabs: **Sectors**, **Commitments**, **Deliverables**, **KPIs**.
- Each tab is a searchable list with counts. Coordinator can edit; Deputy can view.
- Actions: **Archive** or **Activate** (visible based on status).

### #38 Confirm Inherit Framework
- A confirmation step before the heavy copy operation.
- Shows what will be copied (counts table), warns about archiving the prior year, with an explicit "Type the year to confirm" input to prevent accidents.

## 5.8 Data Entry windows (Coordinator / Deputy)

### #39 Data Entry Window Management
- Header: **Data Entry Window Management**.
- Two pill buttons: **Global Lock All** (danger ghost), **Global Unlock All** (primary ghost).
- Stats trio: Total Sectors / Currently Open / Submission rate.
- Year + Quarter selectors.
- List of sectors with status badge (Open / Locked), active quarter, deadline date, last action timestamp.
- Each row: long‑press → context menu (Open this quarter / Lock this quarter / Grant override / Set custom deadline).
- Bottom sheet for **Grant override**: Sector (preselected), Reason (textarea required), Expiry (datetime), Submit.

## 5.9 User & gallery management (System Admin)

### #40 Users List
- Title: **System Users**. Total count chip.
- Filters: search by name/email, role multiselect chip, sector chip.
- Grid of user cards (1‑col on phone): avatar (16 dp), name, email small, current role chip, sector chip (or "facilitator sectors" stack).
- Card kebab menu: View profile / Update role / Revoke / Reactivate / Change photo.
- FAB: **Add user**.

### #41 Add / Edit User
- Steps: Personal info (Full name, Email, Phone), Sector & Role (Role select, Target entity, Sector select where relevant, Multi‑sector picker for Facilitators), Photo (camera / library).
- Validation: email uniqueness check on blur; role/scope combination shown as helper text.

### #42 User Profile
- Header: avatar (96 dp), name, role chip, sector chip, contact row.
- Tabs: **About** (bio fields), **Activity** (timeline), **Sessions** (recent logins, device, location), **Roles history**.
- Admin actions row: Revoke role, Reactivate, Reset password, Send invite.
- Self view (My Profile): same but tabs simplified (About / My Activity / Security).

### #43 Change Password
- Fields: Current password, New password, Confirm new password. Strength meter.

### #44 Update Photo
- Camera or library; cropper with 1:1 enforce; circular preview.

### #45 Gallery Management List
- Title: **Gallery Management**. Count chip + **Upload new image** primary button.
- 3‑col tile grid (2‑col on phone). Each tile: image (aspect 4:3), title, drag handle, kebab (Edit / Delete / Toggle active / Reorder).
- Drag‑and‑drop reorder with haptic feedback.
- Empty state: "No images yet."

### #46 Upload / Edit Gallery Image
- Form: Title*, Description (optional), Category (optional select), Status toggle (Active / Inactive), Display order (number), Image picker.
- Image picker shows live preview and EXIF data hidden by default.

### #47 Public Gallery (unauthenticated)
- Title: **Gallery**.
- Reachable from Welcome.
- 2‑col masonry on phone, 3‑col on tablet.
- Tap to open #48.

### #48 Public Gallery — Detail
- Hero image, title, description, date.
- **Comments** list (read‑only for anonymous; comment composer if signed in).
- Share button (deep link to image).

## 5.10 Comments & discussion

### #49 Comments — Sectors
- Title: **Sector discussion**. List of sectors with comment counts. Tap → #50.

### #50 Comments — Project list (commitments)
- Within a sector: list of commitments with comment counts and last comment preview.

### #51 Comments — Project details thread
- Header: commitment summary card.
- Thread of comments with nested replies (1 level only on mobile).
- Composer at the bottom (sticky), supports attachments (image only).
- Each comment: avatar, name, role chip, timestamp, body, reply CTA, like icon, kebab (Edit / Delete if author).
- States: Empty ("Start the discussion."), Loading skeleton, Error.

## 5.11 Notifications

### #52 Notifications Inbox
- Tabs: **All**, **Unread**, **Mentions**.
- Row: icon (workflow event), title, sector / KPI, body preview, timestamp, unread dot.
- Swipe actions: Mark read, Archive.
- Bulk select with multi‑select toolbar.
- Tap → routes to the relevant screen (KPI detail, Sector Head review, etc.).
- Empty: "You're all caught up."
- **FCM push** payload should set this list as the deep‑link target.

### #53 Notification Preferences
- Per‑category toggles: New submissions, Approvals, Rejections, Mentions, Deadlines.
- Per‑channel toggles: Push, Email, SMS.
- Quiet hours.

## 5.12 Profile, settings & system

### #54 My Profile
- Same shell as #42 (User Profile) but with **Edit my info** primary action and **Switch role** if multi‑role.

### #55 Settings
- Sections:
  - **Appearance**: Theme (System / Light / Dark), Font size.
  - **Language**: English (Nigeria) default.
  - **Notifications**: link to #53.
  - **Security**: Change password, Biometric sign‑in toggle, Active sessions, Sign out everywhere.
  - **Data & sync**: When on cellular allow uploads? Wi‑Fi only?, Clear cache, Sync now.
  - **About**: app version, build, privacy policy, terms, open source licenses.
- Each row in a card group with chevron or switch.

### #56 Help & Support
- FAQs accordion. "Contact PDCU" CTA opens email composer pre‑filled. **Submit feedback** form (subject, message, attach screenshot).

### #57 About PDCU
- Short paragraph, leadership crest, contact, social links, version.

### #58 Switch Role
- Bottom sheet listing all active roles the user has; current marked. Tap to switch and reload the app shell.

### #59 Security Log (System Admin)
- List of events: user, action (Login / Logout / Role updated / Failed login / Password reset), IP, device, timestamp. Filterable by user and date.

## 5.13 Errors, edges & utility screens

### #60 Offline / No connection
- Illustration + body copy explaining offline mode; lists what's available cached (last viewed sectors, drafts).

### #61 Maintenance
- Branded screen explaining the system is in maintenance with ETA pulled from a remote config flag.

### #62 Permission Denied
- Branded message + a brief explanation of which role can perform this action, with CTA **Switch role** (if multi‑role) or **Return to dashboard**.

### #63 Not Found
- 404 illustration, CTA to go home.

### #64 Force Update
- Card with current version, required version, **Update now** CTA (deep links to App Store / Play Store).

### #65 Onboarding tour (first login)
- 3‑slide swipe walkthrough customised per role with the exact actions they'll perform most.

---

# 6. State catalogue per screen (cross‑cutting)

For every screen above, Stitch must produce these state variants (artboards):

1. **Default** — populated with realistic placeholder data.
2. **Loading** — skeleton placeholders, no spinner on lists.
3. **Empty** — bespoke per screen (copy provided where called out above).
4. **Error** — inline banner where appropriate; full‑screen for hard failures.
5. **Offline** — sticky yellow banner under the AppBar; primary actions become disabled with a tooltip ("Available when online").
6. **Submitting / Pending action** — primary buttons show a spinner and disable; the rest of the screen remains interactive.
7. **Success** — toast + any in‑place data refresh.
8. **Permission denied for this view** — see #62.
9. **Locked record** — for any KPI/quarter that has reached **Confirmed** status; show a small lock icon and read‑only styling.

---

# 7. Role‑variation artboards

For every screen that changes by role (e.g. Sector Overview, KPI detail, Reports Hub), Stitch must output one artboard per role suffixed with `— SysAdmin`, `— Governor`, `— Coordinator`, `— Deputy`, `— SectorHead`, `— DataAdmin`, `— Facilitator`. If a screen is identical for all roles, output a single artboard.

---

# 8. Approval pipeline reference (use as ground truth for all status logic)

```
[PDCU sets milestone]            confirmation_status = "Not Confirmed"
            ↓
[Data Admin enters actual]       confirmation_status = "Pending Sector Head Approval"
            ↓
[Sector Head approves]           confirmation_status = "Pending Facilitator"
            ↓ Accept                                ↓ Reject
[Facilitator decision]      "Pending Coordinator"   "Rejected" (returns to Data Admin)
            ↓ Confirm                              ↓ Reject
[Coordinator final]         "Confirmed" (LOCK)     "Rejected" (returns to Data Admin)
```

Visual rules:
- **Awaiting** statuses use a pulsing dot in their colour.
- **Confirmed** shows a lock icon and a solid emerald pill.
- **Rejected** always carries the rejection reason on the same line or in a tooltip.
- When the rejected record is reopened by Data Admin, the prior facilitator/coordinator fields show as muted history within the screen (collapsible "Previous review" section).

---

# 9. Numerical & data behaviours to bake into the visual design

- Show percentages with one decimal under 10 % and integers above.
- "Adjusted" performance (capped at 101 %) must always be paired with a small `i` info icon revealing a tooltip: *"Capped at 101 % to avoid extreme over‑performance distorting averages."*
- Currency uses ₦ and thousands separators; abbreviate above ₦1 million ("₦1.2 M") in tight tiles only — never in tables.
- Dates are absolute; relative time only in feeds & notifications ("2 h ago"), with the absolute date on long press.
- Empty numeric cells render as `—` (em dash), never `0`.

---

# 10. Push notifications (FCM) — visual specification

Each notification is one of these templates. Stitch should produce a row variant for each.

1. **New submission ready for review** (Sector Head) — icon `inbox`, title *"[Sector] has 3 KPIs awaiting your approval."*, sub *"Q2 FY 2026"*.
2. **Sector Head approved** (Facilitator) — icon `check_circle`, title *"Approved by Sector Head — ready for your review."*.
3. **Facilitator accepted** (Coordinator) — icon `verified`, title *"Facilitator accepted — please verify."*.
4. **Facilitator rejected** (Data Admin) — icon `error`, title *"Submission rejected — reason inside."*.
5. **Coordinator confirmed** (Data Admin + Sector Head) — icon `task_alt`, title *"Locked into report — Confirmed."*.
6. **Coordinator rejected** (Data Admin) — same shape as #4.
7. **Deadline approaching** (Data Admin) — icon `schedule`, title *"3 KPIs due in 24 hours."*.
8. **Framework activated** (all) — icon `flag`, title *"FY 20XX framework is now active."*.
9. **Data entry window opened / closed** (Sector Head, Data Admin) — icon `lock_open` / `lock`.

---

# 11. Mobile‑specific UX considerations (not present on the web)

Build these explicitly — they are improvements on the existing web app:

1. **Offline‑first KPI entry** — drafts auto‑save locally; queue submits when online.
2. **Camera evidence capture** — quick action on #24 to capture a photo, auto‑compresses to <2 MB, names file `KPI‑[id]‑Qn‑YYYYMMDD.jpg`.
3. **Bulk approve via swipe** — Sector Head row swipe‑left reveals quick "Approve" action.
4. **Universal sticky filter** — year + quarter + sector chosen anywhere remain remembered across screens until changed.
5. **One‑hand reachability** — primary actions sit in the lower third of the screen (sticky bars over bottom sheets over modal dialogs).
6. **Search anywhere** — global search recognises KPI IDs and routes directly.
7. **Pull‑to‑refresh** on every list.
8. **Long‑press preview** on cards everywhere (#22 pattern) instead of immediate navigation.
9. **Biometric sign‑in** option for returning users.
10. **Push deep links** open the exact screen at the exact item.
11. **Accessibility**: every chart has a "View as table" toggle, all colour cues paired with shapes.
12. **Data saver mode** — opt‑in toggle that swaps high‑res images for thumbnails and disables auto chart animation.

---

# 12. Hand‑off deliverables for Stitch

Stitch should produce, in this order:

1. **Cover sheet** — product name, version, palette swatches, font specimens, role matrix, status legend.
2. **Component library** — every reusable component listed in §2.4 with usage notes.
3. **Screens** — one artboard per screen ID `#1`–`#65` in default state.
4. **States** — for each screen, additional artboards titled `#n — Loading`, `#n — Empty`, `#n — Error`, `#n — Offline`, `#n — Success`, `#n — Locked` where applicable.
5. **Role variations** — see §7. Group these in a "Role variations" section.
6. **Flows** — annotated user flows for the seven core journeys (one per role):
   - F1 Sign in & first‑run onboarding.
   - F2 Data Admin enters Q2 actual value & uploads evidence.
   - F3 Sector Head bulk‑approves Q2 submissions.
   - F4 Facilitator reviews & accepts a KPI (and a separate reject path).
   - F5 Coordinator final verification & lock.
   - F6 Governor reviews dashboard and exports report.
   - F7 System Admin creates a user and a gallery image.
7. **Notification gallery** — visual list of all push templates from §10.
8. **Empty‑state illustrations** — a coherent set in line art using the primary colour.

---

# 13. Tone of voice

- Direct, plain‑English, formal but warm. No jargon. No abbreviations beyond MDA, PDCU, KPI, FY.
- Buttons start with a verb. Empty‑state copy is reassuring.
- Errors describe the problem and the next action, never the technical cause.
- Address the user by role context where relevant ("As Sector Head, you can approve these.").

---

# 14. Out of scope (do not design)

- Web admin parity beyond what's needed for mobile.
- Custom report builder (use the existing Comprehensive Report flow).
- In‑app chat (use Notifications + Comments threads only).
- Payment, finance, or budget reallocation flows (read‑only budget context only).

---

# 15. Final note to Stitch

Treat this prompt as the **single source of truth**. Where Stitch is unsure, prefer **clarity over cleverness**, **density over decoration**, and **established native patterns** over invented ones. Every screen should feel like a tool a busy government professional can use on a 5.5‑inch phone with one hand, in poor connectivity, under time pressure — because that is exactly the audience.

Render now.
