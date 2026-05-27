# API v2 Implementation Notes

Living document for the `/api/v2` mobile API build. Source contract:
[`docs/API_REFERENCE.md`](./API_REFERENCE.md) (reverse-engineered from the Flutter
client). This file is updated at the end of every phase.

- **Phase 1 — Analysis:** ✅ complete (this document). Awaiting review.
- **Phase 2 — Foundation:** ⏳ not started.
- **Phase 3 — Feature implementation:** ⏳ not started.
- **Phase 4 — QA & optimization:** ⏳ not started.

---

# Phase 1 — Analysis

## 1. Existing architecture (as-is)

| Aspect | Finding |
| --- | --- |
| Framework | Laravel 10, PHP 8.1+ |
| Auth packages | **Passport 12** (OAuth2) and **Sanctum 3.3** both installed; `auth:api` guard uses the **passport** driver (`config/auth.php`). Default guard is `web` (session). |
| Existing API | `routes/api.php`, loaded by `RouteServiceProvider` with URL prefix `api` and the `api` middleware group (`throttle:api` = 60/min by user-id-or-IP, + `SubstituteBindings`). |
| API controllers | `app/Http/Controllers/Api/AuthController.php`, `…/ProjectController.php`. |
| v1 response shape | **Enveloped:** `{ "success": bool, "message": string, "data": … }`. Login embeds a Passport access token in `data.token`. **No refresh token is exposed today.** |
| v1 auth flow | `Auth::attempt()` → `createToken('eTrackerX8nE@9')->accessToken` (Passport personal access token). Logout: `$request->user()->token()->revoke()`. |
| Business logic | Lives almost entirely in **web controllers** (`KpiController`, `UserController`, `DashboardController`, `ReportController`, `FrameworkController`, `DataEntryAccessController`, `SectorController`, `CommitmentController`, `DeliverableController`, `GalleryController`, `NotificationController`). Thin/no service layer today. |
| Approval workflow | Implemented across `KpiController` (`storeTracking`, `approveData`, `facilitatorConfirm`, `coordinatorConfirm`) and `UserController` (awaiting/coordinator-final queues). State machine matches the v2 spec exactly (see §4). |
| Role model | `user_roles` table; **Title Case** role strings (`'Sector Head'`, `'Data Admin'`, `'Coordinator'`, `'Deputy Coordinator'`, `'Facilitator'`, `'Governor'`, `'System Admin'`). Rich role helpers on `User` (`isSectorHead()` etc.). Facilitators map to many sectors via `facilitator_sectors`. |
| Encrypted URLs | Web uses `Crypt::encrypt` URL helpers (`sector_view_url`, …) — **web-only**, irrelevant to v2 (the API will use plain ids). |

### 1.1 Critical finding — schema source of truth

The production database was **not** built purely from migrations. The real `users`
table (per `etracker_db.sql`) is:

```
users(id, full_name, email, phone_number, role INT, password, image_url,
      token, fcm_token, deleted_at, created_at, updated_at)
```

…but the base `create_users_table` migration (which defines `name`, no `full_name`)
is **misplaced under `database/factories/`** and does not reflect production. Several
tables (`notifications`, `comments`, budgets, `reports`) exist only in the SQL dumps,
not in migrations.

**Implications:**
- `php artisan migrate:fresh` will **not** reproduce production. A test database must
  be seeded from the SQL dump or a dedicated test schema (see §7, testing).
- New v2 schema must be added as **additive, idempotent** migrations that tolerate
  columns/tables already present (guard with `Schema::hasColumn`/`hasTable`).
- The User model's wire `name` (v2) maps to the DB `full_name`; v2 `phone` → `phone_number`.

---

## 2. The v2 contract in one paragraph

`/api/v2/**`, **raw** JSON (single resource = object, collection = bare array — **no
envelope**), Bearer access token on every call except `POST /auth/login`, login also
returns a `refresh_token`, mutations may return `204`/`202` with an ignored body.
~80 endpoints across 15 modules. Heavy use of **pre-formatted presentation fields**
(labels, accents, icon keys, relative times, currency strings, initials). Enum values
are **snake_case** wire tokens; quarters are `q1`–`q4`. IDs are **strings** on the wire.
Errors should return `{ code, message, fieldErrors? }` (client maps on status code only
today, but we standardize the body now).

---

## 3. v1 → v2 gap analysis (the big rocks)

| # | Gap | Decision (proposed) |
| --- | --- | --- |
| G1 | **Response shape**: v1 enveloped, v2 raw. | Build a **separate v2 stack** (controllers + API Resources). Never reuse v1's envelope. v1 untouched. |
| G2 | **Refresh tokens**: v1 has none; v2 needs `access_token`+`refresh_token` and `POST /auth/refresh`. | Keep **Passport** issuing the access token (so v1 stays valid). Add a **rotating opaque refresh token** stored hashed in a new `api_refresh_tokens` table; `/auth/refresh` validates+rotates it and mints a new Passport access token. (Confirm — see A1.) |
| G3 | **String IDs**: v2 ids are strings (`"u_001"`, `"health"`); DB uses integer PKs. | v2 Resources **cast PK → string**; v2 routes accept the string and resolve by PK. The doc's slug ids are illustrative; we will **not** add slug columns. (Confirm — A2.) |
| G4 | **Presentation fields**: v2 wants display strings/accents/iconKeys the DB doesn't store. | Add a **Presenter layer** (per-feature) that derives labels, `accent`/`iconKey` slots (deterministic mapping from sector/role/status), relative-time and currency formatting. Comply with the doc rather than pushing formatting to the client. (Confirm — A3.) |
| G5 | **Enum/value mapping**: DB Title Case ↔ wire snake_case; quarter `1..4` ↔ `q1..q4`; confirmation_status ↔ lifecycle states. | Central **`WireEnums`/mapping** helpers (two-way). See §6. |
| G6 | **`mustChangePassword`**: no column. | Additive migration: `users.must_change_password BOOL DEFAULT 0`. Endpoint `POST /auth/password/force-change` clears it. |
| G7 | **Pagination**: client expects bare arrays today. | Return **full collections** for v2 now (matches client). Leave a seam for cursor pagination later (Appendix B item 5). No envelope. |
| G8 | **Error body**: v1 returns `200` with `success:false`; v2 wants real status codes + `{code,message,fieldErrors}`. | v2 exception handler renders the structured body with **correct** HTTP codes (`401/403/404/409/422/5xx`). v1 handler untouched. |

---

## 4. Approval workflow — already matches v2

The DB state machine maps 1:1 to the v2 lifecycle (just different casing):

```
DB confirmation_status                wire state (v2)
─────────────────────────────────    ─────────────────────
Not Confirmed                          pending_entry
Pending Sector Head Approval           pending_sector_head
Pending Facilitator                    pending_facilitator
Pending Coordinator                    pending_coordinator
Confirmed                              confirmed
Rejected                               rejected
```

Transitions (reusable from `KpiController`): Data Admin submit → sector-head approve
(`pending_facilitator`) → facilitator accept (`pending_coordinator`, sets
`delivery_department_value`) → coordinator accept (`confirmed`). Reject at any reviewer
→ `rejected`, with the reject reason fields. **The v2 `POST /approvals/submissions/{id}/review`
carries an explicit `role` + `decision`** — we route to the existing transition for that
role and cross-check against the token's role.

---

## 5. Endpoint → reuse map (all 15 modules)

Legend: **Reuse** = existing logic to adapt · **New** = build · **Schema** = needs a migration.

### 11.1 Auth (`/auth/*`)
| v2 endpoint | Reuse / New |
| --- | --- |
| `POST /auth/login` | Reuse `AuthController@login` logic (re-shape to raw + refresh token). |
| `GET /auth/me`, `POST /auth/logout` | Reuse Passport user/revoke. |
| `POST /auth/password/force-change` | New (clears `must_change_password` — **G6 schema**). |
| `POST /auth/refresh` | New (**G2 schema** `api_refresh_tokens`). |
| `POST /auth/register` | Reserved/stub per doc (not wired client-side). |

### 11.2 Profile · 11.9 Users & security
| | |
| --- | --- |
| `GET /profile/me`, `GET /users`, `GET /users/{id}` | Reuse `User`/`UserRole` + new Presenters. |
| `POST /users` (multipart, avatar) | Reuse `UserController@store`+`uploadPhoto`. Needs `avatarKey` storage → **schema** (`users.avatar_key`). |
| `POST /users/me/password` | Reuse `changePassword`. |
| `POST /users/me/photo` | Reuse `uploadPhoto`. |
| `GET /users/security-log` | **New** — no audit table → **schema** `security_events` (or derive). |

### 11.3 Sectors / commitments / deliverables (all GET)
Reuse `Sector`/`Commitment`/`Deliverable` models + access scoping
(`getAssignedSectorIds`, `canAccessAllSectors`). All the counts/percent/accent/icon
fields are **derived** by Presenters. No schema changes.

### 11.4 KPI tracking
Reuse `Kpi`/`KpiTarget`/`PerformanceTracking` + `KpiController` mutation logic.
`submissions[]`, `supportingDocuments[]` from `performanceTracking` + polymorphic
`files`. Submit/milestone/tracking-entry → reuse `storeTracking`. No schema changes
(quarter int↔qN mapping only).

### 11.5 Frameworks
Reuse `FrameworkController` (list/stats/detail/sectors/create/archive/activate=set-default).
Missing wire fields → **schema** on `frameworks`: `subtitle`, `is_default`,
`inherited_from_framework_id`. (`reportingYear`→`year`, `statusLabel`/counts derived.)

### 11.6 Approvals workflow
Reuse the §4 state machine + `UserController` queues. Queue/stat/bulk/verification
group shapes are **Presenter-built** over `PerformanceTracking`. No schema changes.

### 11.7 Data-entry windows
Reuse `DataEntryAccessController` (`DataEntryAccess` model). `open`/`lock`/`lock-all`/
`unlock-all`/`override` map to existing status + override fields (`expiresAt`→
`override_deadline`). No schema changes.

### 11.8 Reports
Reuse `ReportController` performance math + `phpspreadsheet`/`phpword`. Generation
endpoints return a `GeneratedReportModel` (`downloadUrl`) — need a **file-serving
route** for generated artifacts (store under `storage/app/public/reports`, return URL).
No schema changes (optional `generated_reports` table for bookkeeping — defer).

### 11.10 System
**New** screens-config endpoints (status/update/offline/onboarding). Recommend a
small **`system_settings`** key/value table (or config) for maintenance/version/onboarding,
made **publicly reachable** (status/update/onboarding) per Appendix B item 10. `retry`
and `onboarding/complete` are trivial commands.

### 11.11 Dashboards (6 role snapshots)
Reuse `DashboardController` + `ReportController` aggregation math (the 101%-cap,
on-track/at-risk/delayed buckets). All six are Presenter-assembled. No schema changes.

### 11.12 Settings
**New** — no preferences storage → **schema** `user_settings` (theme, fontScale,
biometric, cellularUploads, syncOnWifiOnly, language). FAQs + About → `system_settings`
or seeded tables. `clear-cache`/`sync`/`sign-out-all` are commands (sign-out-all = revoke
Passport tokens + refresh tokens).

### 11.13 Gallery
Reuse `Gallery`/`GalleryComment`. Missing wire fields → **schema** on `galleries`:
`category`, `is_public`, `icon_key`, `gradient_keys` (JSON). Tabs/filters map to
`status`/`category`. Stats on detail are derived/seeded.

### 11.14 Notifications
Reuse `Notification` model for inbox. **New** preferences storage → **schema**
`notification_preferences` (5 category + 3 channel toggles + quiet hours). Inbox needs
`kind`/`accent`/`iconKey`/`isUnread`/deep-link → map from `Notification.type` + derive;
may add nullable columns (`kind`, `deep_link_route`, `deep_link_params` JSON).

### 11.15 Discussions
**Largest new build.** Existing `comments` (commitment-scoped, `likes` int, no threads,
no per-user likes) is insufficient. **Schema**: `discussion_threads`,
`discussion_comments` (with `parent_id`, `author_role`), `discussion_comment_likes`
(per-user toggle). Hub/trending can be derived or seeded.

---

## 6. Proposed v2 architecture

```
routes/
  api.php            # unchanged (v1) — keeps loading at /api/*
  api_v2.php         # NEW — registered by RouteServiceProvider at prefix /api/v2

app/Http/Controllers/Api/V2/      # thin controllers, one per module
app/Http/Requests/V2/             # FormRequest validation (centralized)
app/Http/Resources/V2/            # API Resources (raw shapes, string ids)
app/Services/V2/                  # business actions (reused/extracted logic)
app/Support/V2/
  WireEnums.php                   # two-way enum/role/quarter/status mapping
  Presenters/                     # derive labels, accents, iconKeys, money, relative time
app/Repositories/V2/              # query builders (scoping, eager-load) where useful
app/Exceptions/V2/                # ApiException + handler rendering {code,message,fieldErrors}
```

- **Versioning**: `RouteServiceProvider::boot()` adds a third group:
  `Route::middleware('api')->prefix('api/v2')->group(routes/api_v2.php)`. v1 group is
  left exactly as-is.
- **Auth middleware**: reuse `auth:api` (Passport) for v2 protected routes; system
  status/update/onboarding use an **optional-auth** variant (public-capable).
- **Response/raw**: Resources return bare arrays/objects; `JsonResource::withoutWrapping()`
  for v2 so Laravel doesn't add a `data` key.
- **Errors**: a v2 exception renderer maps `ValidationException`→`422 {fieldErrors}`,
  `AuthenticationException`→`401`, `AuthorizationException`→`403`,
  `ModelNotFound`→`404`, domain conflicts→`409`, else `500` — all as
  `{code,message,fieldErrors?}`. v1 paths unaffected (scoped by request path/route).
- **Throttle**: keep `throttle:api`; add a tighter limiter on `POST /auth/login`.
- **Logic reuse without breaking v1**: extract shared logic into `Services/V2` that
  call the **same models**; do not modify v1 controllers. Where v1 logic is suitable
  as-is, the service wraps it; we avoid editing `KpiController` etc.

---

## 7. Required schema changes (all additive & guarded)

Each migration will guard with `Schema::hasTable/hasColumn` so it is safe against the
SQL-dump-derived production DB.

| Migration | Change |
| --- | --- |
| `users` | + `must_change_password` (bool, default 0), + `avatar_key` (string, null) |
| `api_refresh_tokens` (new) | `id, user_id, token_hash, access_token_id (null), expires_at, revoked_at, created_at` |
| `frameworks` | + `subtitle` (null), + `is_default` (bool default 0), + `inherited_from_framework_id` (null, self-FK) |
| `galleries` | + `category` (string, null), + `is_public` (bool default 1), + `icon_key` (string, null), + `gradient_keys` (json, null) |
| `user_settings` (new) | per-user app preferences (theme, font_scale, biometric, cellular_uploads, sync_wifi_only, language_code, language_label) |
| `notification_preferences` (new) | 5 category + 3 channel bools + quiet_hours_enabled + quiet_from/quiet_to |
| `notifications` | + `kind` (null), + `deep_link_route` (null), + `deep_link_params` (json null) — only if absent |
| `security_events` (new) | audit log: kind, icon_key, title, user_label, ip_address, device_label, created_at |
| `discussion_threads` (new) | id, sector_id, title, status, lead_* , timestamps |
| `discussion_comments` (new) | id, thread_id, parent_id (null), author fields, body, like_count, timestamps |
| `discussion_comment_likes` (new) | comment_id, user_id (unique) — per-user toggle |
| `system_settings` (new) | key/value for maintenance/version/onboarding/about/faqs (or seed) |

No column is renamed, dropped, or retyped. No FK is added to legacy tables that would
reject existing rows (new FKs are nullable).

---

## 8. Assumptions & decisions needing sign-off

- **A1 — Refresh tokens via Passport + rotating opaque token table.** Keeps v1's Passport
  access token valid; adds the refresh capability the doc reserves. Alternative: switch
  v2 to the OAuth password grant (issues refresh tokens natively) — heavier, needs a
  password-grant client. **Proposed: the opaque-table approach.**
- **A2 — String ids = stringified integer PKs** (no slug columns). The client only needs
  ids to round-trip as strings; slugs in the doc are examples.
- **A3 — Presentation computed server-side.** We honor the doc's pre-formatted fields
  (accents, icon keys, labels, currency, relative time) rather than requesting a client
  change. Mapping tables (sector→icon/accent, status→label) will be deterministic and
  documented.
- **A4 — `accent`/`iconKey` slot vocab.** We will define and document a fixed slot
  vocabulary (e.g. `primary/secondary/tertiary/error/performance_fair`) matching the
  examples; unknown enums fall back per the doc's lenient rules.
- **A5 — Reports return a `downloadUrl`** served from `storage/app/public/reports` via a
  public file route; generation reuses existing spreadsheet/word builders.
- **A6 — System status/update/onboarding made public-capable** (optional auth) so the
  app can show them pre-login (Appendix B item 10).
- **A7 — No pagination yet** (bare arrays) to match the current client; pagination is a
  later, client-coordinated change.
- **A8 — v1 stays byte-for-byte.** No edits to `routes/api.php`, `Api/AuthController`,
  `Api/ProjectController`, or any web controller. v2 reuses **models and new services
  only.**

## 9. Risks / blockers

- **B1 — Test DB fidelity.** Because prod came from SQL dumps, our migrations alone don't
  reproduce it. Plan: build a v2 test schema by importing `etracker_db.sql` into a test
  DB in CI, then running only the new additive migrations; or author a consolidated
  baseline migration set. **Needs a call on which** before Phase 4.
- **B2 — Discussions & security-log volume.** These are net-new domains with no existing
  data; hub/trending/feeds will need seeders to be demoable.
- **B3 — Presenter fidelity.** Some example fields (e.g. `breadcrumb`, `heroSubtext`,
  trend sparkline points) are UI-specific; we'll produce sensible deterministic values
  and flag any that need product input.
- **B4 — `role` may be null** in the user object (client routes to a role picker) — our
  resources must allow null role.

---

## 10. Proposed Phase 3 build order (feature-by-feature)

1. **Auth + Profile** (foundation-critical; unblocks everything).
2. **Sectors → Commitments → Deliverables → KPIs** (read hierarchy).
3. **KPI tracking + Approvals** (the core workflow; highest reuse).
4. **Dashboards** (aggregation; depends on 2–3).
5. **Data-entry windows**.
6. **Frameworks**.
7. **Reports** (file generation).
8. **Users & security log**.
9. **Gallery**.
10. **Notifications**.
11. **Settings**.
12. **Discussions** (largest net-new; last).
13. **System** (cross-cutting; alongside as needed).

Each feature ships: routes → FormRequests → Service → Resource/Presenter →
feature tests, with a backward-compat note.
