# API v2 Implementation Notes

Living document for the `/api/v2` mobile API build. Source contract:
[`docs/API_REFERENCE.md`](./API_REFERENCE.md) (reverse-engineered from the Flutter
client). This file is updated at the end of every phase.

- **Phase 1 — Analysis:** ✅ complete & approved.
- **Phase 2 — Foundation:** ✅ complete (see §11).
- **Phase 2.5 — Test-DB baseline (B1 resolved):** ✅ complete (see §15).
- **Phase 3 — Feature implementation:** 🔄 in progress.
  - **F1 Auth + Profile:** ✅ complete (see §19).
  - **F2 Sectors → Commitments → Deliverables (read hierarchy):** ✅ complete (see §20).
  - **F3 KPI tracking:** ✅ complete (see §21).
  - **F4 Approvals workflow:** ✅ complete (see §22).
  - **F5 Dashboards:** ✅ complete (see §23).
  - **F6 Data-entry windows:** ✅ complete (see §24).
  - **F7 Frameworks:** ✅ complete (see §25).
  - **F8 Users & security:** ✅ complete (see §26).
  - **F9 Gallery:** 🟡 code complete + lint-clean; tests written but **last run blocked by MySQL outage on the host** (see §27). Awaiting MySQL restart to verify.
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

## 9a. Web-app impact verification (pre-build gate)

Goal: prove **none** of A1–A8 or the §7 schema changes alter how the **web app**
(session-based Blade UI) functions. The web app and v2 share only **models + tables**,
so that is where any risk would live. Verified against the code:

**Method & evidence**
- Web auth is **session-only** (`AuthLoginController@login` → `Auth::attempt()` +
  `session()->regenerate()`; logout → `Auth::logout()` + `session()->invalidate()`).
  No tokens. → all auth assumptions (A1/A6) are in a separate lane.
- **No shared-model landmines:** a grep across `app/Models` for `addGlobalScope`,
  `protected $appends`, `protected $guarded`, custom `booted()` → **no matches**.
  Models use explicit `$fillable` + minimal `$casts`. Adding nullable columns cannot
  change their serialization or query behavior.
- **New columns are referenced nowhere in the web app:** grep of `app/` and
  `resources/views/` for `is_default`, `avatar_key`, `must_change_password`,
  `gradient_keys`, `icon_key`, `is_public`, `deep_link` → **no matches**. The web app
  is blind to every column we add.
- Web framework/gallery logic keys off **existing** columns only (`status='Active'`,
  `status='active'`), independent of any new `is_default`/`is_public`.

**Per-assumption verdict**

| Assumption | Web-app effect | Why |
| --- | --- | --- |
| A1 refresh tokens | **None** | Web uses `web` session guard; A1 only touches Passport `api` guard + new table. |
| A2 string ids | **None** | Lives in v2 Resources only; no model/DB change. |
| A3 presenters | **None** | v2-only classes; shared models not modified. |
| A4 accent/icon vocab | **None** | v2 serialization only. |
| A5 report downloadUrl | **None** | New route under `/api/v2` + `storage/app/public/reports`; no web route touched. |
| A6 public system endpoints | **None** | New `/api/v2` routes; optional-auth variant is v2-scoped. |
| A7 no pagination | **None** | v2 response shape only. |
| A8 v1 untouched | **None** | By definition; also no web edits. |

**Per-schema-change verdict** (all additive, nullable/defaulted, guarded with
`Schema::hasTable/hasColumn`)

| Change | Web-app effect | Note |
| --- | --- | --- |
| `users +must_change_password,+avatar_key` | None | nullable/default 0; web never reads them; not added to web flows. |
| `frameworks +subtitle,+is_default,+inherited_from_framework_id` | None* | web reads `status` only. *Guardrail GR2. |
| `galleries +category,+is_public,+icon_key,+gradient_keys` | None | web filters on `status` only. |
| `notifications +kind,+deep_link_*` | None | nullable; web creation sets its own columns. |
| New tables (`api_refresh_tokens`, `user_settings`, `notification_preferences`, `security_events`, `discussion_*`, `system_settings`) | None | web references none of them. |

**Guardrails I will hold to in Phases 2–3 (this is where risk would be introduced, not in the current state):**
- **GR1 — Do not mutate shared models in breaking ways.** No `$appends`, no global
  scopes, no `$casts` changes to existing columns, no relationship renames on
  `User/Sector/Commitment/Deliverable/Kpi/PerformanceTracking/Framework/Gallery/Notification`.
  New columns may be added to `$fillable` and new (nullable) `$casts`/relationship
  methods added — both are additive and inert to the web app. v2 presentation lives in
  Resources/Presenters, never on the model.
- **GR2 — `is_default` mirrors, never overrides, web semantics.** v2 "set-default"
  reuses the web's `activate()` behavior (`status='Active'` + archive others); `is_default`
  is a derived mirror so the web's `status='Active'` source of truth never diverges.
- **GR3 — v2 error JSON is route-scoped.** The structured `{code,message,fieldErrors}`
  renderer fires **only** for `api/v2/*` (in `Handler::register()` via a guarded
  `renderable`), leaving web HTML error pages and v1 responses untouched.
- **GR4 — No edits to `web` middleware group, `config/auth.php` defaults, or the web
  route group.** v2 adds a new route group + (optional) new guard/alias only.
- **GR5 — Migrations are additive & reversible**, guarded against the SQL-dump schema,
  with no NOT-NULL-without-default on populated tables and no FKs that reject existing rows.

**Conclusion:** with GR1–GR5 observed, none of the assumptions or schema changes alter
web-app behavior. The current codebase has no global scopes/appends/guarded models and
no web references to any new column, so the additive plan is verifiably inert to the web
UI. The only behavior-coupling point is framework default/active (GR2), handled by
reusing the existing activate path.

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

---

# Phase 2 — Foundation

Goal: stand up the v2 lane and its shared plumbing so Phase 3 features only add
routes/requests/services/resources. No feature endpoints yet (only a health
`ping`). All work honors GR1–GR5; v1 and web are untouched.

## 11. What was built

**Versioning & wiring**
- `routes/api_v2.php` — new v2 route file (currently: `GET /api/v2/ping` health
  check + documented placeholders for Phase 3 groups).
- `app/Providers/RouteServiceProvider.php` — **edited** (additive): registers the
  v2 group at prefix `api/v2` using the existing `api` middleware group, *before*
  the unchanged v1 `api` group; adds a `v2-login` rate limiter (5/min by IP). The
  v1 `api` group and the `web` group are byte-for-byte unchanged (GR4).

**Response / error system**
- `app/Support/V2/ApiResponse.php` — raw success helpers (`noContent` 204,
  `accepted` 202) + `exception()` mapper producing the §6 contract
  `{ code, message, fieldErrors? }` with correct status codes. Framework
  HttpExceptions emit stable per-status messages (no route-path leakage); domain
  errors carry their own message via `ApiException`.
- `app/Exceptions/Handler.php` — **edited** (additive): a `renderable` that fires
  **only** for `api/v2/*` (returns null otherwise), so web HTML error pages and v1
  responses are untouched (GR3). Existing `$dontFlash` and `reportable` unchanged.
- `app/Exceptions/V2/ApiException.php` — domain exception (status + machine code +
  optional fieldErrors) with named constructors (`notFound`/`conflict`/`forbidden`/
  `unprocessable`/`badRequest`).

**Base classes**
- `app/Http/Controllers/Api/V2/BaseController.php` — thin-controller base with
  `noContent()`/`accepted()` command helpers.
- `app/Http/Resources/V2/BaseResource.php` — sets `public static $wrap = null` so
  v2 resources serialize **raw** (no `data` envelope), scoped to v2 subclasses
  only (does **not** call the global `withoutWrapping()`; v1/web unaffected).
- `app/Http/Requests/V2/BaseFormRequest.php` — always throws `ValidationException`
  (→ 422 + fieldErrors via the handler) and `ApiException::forbidden()` on failed
  authorization; centralizes validation so controllers stay thin.

**Support layer**
- `app/Support/V2/WireEnums.php` — two-way maps: DB role ↔ wire (`Sector Head` ↔
  `sector_head`, …), confirmation_status ↔ lifecycle state, quarter int ↔ `qN`.
- `app/Support/V2/Presenters/Presenter.php` — base presenter: `id()` (PK→string,
  A2), `relativeTime()`, `initials()`, `money()` (₦, optional abbrev),
  `fraction()`, `cappedPercent()` (101% cap), and the shared `ACCENTS` vocab (A4).

**Auth infrastructure (A1)**
- `app/Models/ApiRefreshToken.php` — opaque rotating refresh token (stores SHA-256
  hash only; `isActive()`/`revoke()`/`hashToken()`).
- `database/migrations/2026_05_27_100000_add_api_v2_columns_to_users_table.php` —
  additive, guarded: `must_change_password` (bool default false), `avatar_key`
  (nullable).
- `database/migrations/2026_05_27_100100_create_api_refresh_tokens_table.php` —
  new table; guarded; no DB-level FK (model-enforced) per GR5.
- `app/Http/Middleware/OptionalApiAuth.php` + alias `auth.optional` in
  `app/Http/Kernel.php` (**edited**, additive — new alias only) for the
  public-capable system endpoints (A6).

> The auth **endpoints** (`/auth/login|refresh|me|logout|password/force-change`)
> are Phase 3 (feature 1). Phase 2 only lays their infrastructure. Migrations are
> **created but not yet run** (pending the B1 test-DB decision); they are
> idempotent and safe to run via `php artisan migrate`.

## 12. Verification (this phase)

- `tests/Feature/Api/V2/FoundationTest.php` — **4 tests, all passing** (DB-free):
  1. `GET /api/v2/ping` → 200, raw JSON, **no `data` wrapper**.
  2. unknown `/api/v2/*` → 404 `{ code:"not_found", message }` (error contract).
  3. unknown **web** route → default 404, **not** the v2 JSON body (GR3 scoping).
  4. `GET /login` (web) → 200 (web entry point unaffected).
- Full **Feature** suite green (5/5 — includes the default `GET /` test, extra
  evidence the web app boots/serves).
- `route:list` confirms `api/v2/ping` registered **and** v1 `api/login` +
  `api/projects` intact.
- `php -l` clean on every new/edited PHP file.

## 13. Files touched in Phase 2

*New:* `routes/api_v2.php`; `app/Support/V2/{ApiResponse,WireEnums}.php`;
`app/Support/V2/Presenters/Presenter.php`; `app/Exceptions/V2/ApiException.php`;
`app/Http/Controllers/Api/V2/BaseController.php`;
`app/Http/Resources/V2/BaseResource.php`;
`app/Http/Requests/V2/BaseFormRequest.php`;
`app/Http/Middleware/OptionalApiAuth.php`; `app/Models/ApiRefreshToken.php`;
two migrations; `tests/Feature/Api/V2/FoundationTest.php`.

*Edited (all additive):* `app/Providers/RouteServiceProvider.php`,
`app/Http/Kernel.php` (alias), `app/Exceptions/Handler.php` (scoped renderable).

*Untouched:* `routes/api.php`, `routes/web.php`, `Api/AuthController`,
`Api/ProjectController`, all web controllers/models/views, `config/auth.php`.

## 14. Backward-compatibility note

No v1 route, controller, model, or response shape changed. The handler change is
inert outside `api/v2/*`. The `BaseResource` unwrapping is per-class (v1 uses no
Resources). New migrations are additive/guarded and not yet executed. Web verified
serving (`GET /` and `GET /login` both 200 in the suite).

---

# Phase 2.5 — Test-DB baseline (resolves B1)

Goal: make `migrate:fresh` reproduce a **correct** PDCU schema on a clean database
so Phase 3 features get real DB-backed tests — without touching the live DB or the
web app.

## 15. What we found and did

**Why B1 existed (verified against the live DB):**
- `trackerx` is a **shared/polluted dev DB**: 63 tables, ~30 from an unrelated
  academic system. Its `migrations` table records only **18** of the repo's 35
  files. So `migrate:fresh` never reproduced it.
- The repo had **no create-migration** for `users`, `password_reset_tokens` (both
  misplaced under `database/factories/`), `user_roles`, `files`, `notifications`,
  or the budget tables — they only ever came from SQL import.
- Two existing migrations had **latent fresh-run bugs**: `2026_02_15…` dropped a
  never-created `duration_in_days`; `2026_02_22…` created `data_entry_access`
  (singular) while the model + live DB use `data_entry_accesses` (plural).
- The live DB is also **stale** (e.g. `performance_trackings.confirmation_status`
  still the old 3-value enum) — so we corrected to the *intended* schema, not the
  live copy (per your decision).

**Built (all from live `SHOW CREATE TABLE` DDL, corrected to intended schema):**
- 8 guarded create-migrations (early timestamps so the existing defensive ALTERs
  apply cleanly): `users`, `password_reset_tokens`, `user_roles`, `files`,
  `notifications`, `sector_budgets`, `commitment_budgets`, `fund_releases`. Each
  guarded with `Schema::hasTable()` → **no-op on the live DB**.
- Fixed 2 existing migrations (you approved "update existing"): `2026_02_15…`
  drops only columns that exist; `2026_02_22…` now creates `data_entry_accesses`
  (plural) + `hasTable` guard. Both already-run on the live DB, so editing affects
  **only** fresh/test builds.

**Isolated test DB (no `.env` change):**
- Added a **`mysql_test`** connection in `config/database.php` (mirrors `mysql`,
  DB defaults to `trackerx_test`). Additive — the web app/v1 always use the default
  `mysql` connection.
- `phpunit.xml` now sets `DB_CONNECTION=mysql_test` (+ `DB_TEST_DATABASE`). Test
  runs target `trackerx_test`; the web app never reads phpunit.xml.

## 16. Verification

- `php artisan migrate:fresh --database=mysql_test` runs the **entire** 43-migration
  chain green.
- Test DB `trackerx_test`: 28 tables; `performance_trackings.confirmation_status`
  has the **correct 6-value** enum; `users` has `must_change_password` + `avatar_key`.
- **Live `trackerx` provably untouched**: still 63 tables, 12 users, stale 3-value
  enum unchanged, `data_entry_accesses` unchanged, no stray singular table.
- Full **Feature suite 9/9** (4 foundation + 4 `DatabaseBaselineTest` using
  `RefreshDatabase` + 1 default). `php -l` clean on all new/edited files.

## 17. Local/CI setup (one-time)

```bash
# create the throwaway test DB, then build its schema
php artisan tinker --execute="DB::statement('CREATE DATABASE IF NOT EXISTS trackerx_test')"
php artisan migrate:fresh --database=mysql_test
# run tests (uses mysql_test via phpunit.xml)
php vendor/bin/phpunit
```

`RefreshDatabase` tests re-migrate `trackerx_test` automatically. **Never** run
`migrate` against the default connection for tests — the live `trackerx` is for the
web app only.

## 18. Backward-compatibility note (Phase 2.5)

Every new migration is `hasTable`-guarded → inert on the live DB. The two edited
migrations are already recorded as run on the live DB, so they will not re-execute
there; the edits only affect fresh databases. No model/route/controller/view/`.env`
changed. `config/database.php` gained one additive connection used only by tests.
Live DB confirmed unchanged after the full exercise.

> Separately flagged for the team (not addressed here, non-blocking): the live
> `trackerx` is missing the approval-workflow enum expansion and carries a foreign
> app's `notifications` shape — worth reconciling in production independently.

---

# Phase 3 — Feature implementation

## 19. F1 — Auth + Profile

Implements API_REFERENCE.md §11.1 (Authentication) and §11.2 (Profile). First
feature, so it also proves the full Phase 2 stack end-to-end against the test DB.

**Endpoints**

| Method & path | Auth | Handler | Notes |
| --- | --- | --- | --- |
| `POST /api/v2/auth/login` | public | `AuthController@login` | `throttle:v2-login` (5/min/IP); returns `{access_token, refresh_token, user}` raw |
| `POST /api/v2/auth/refresh` | public | `AuthController@refresh` | rotates refresh token; returns `{access_token, refresh_token}` |
| `GET /api/v2/auth/me` | bearer | `AuthController@me` | compact User object |
| `POST /api/v2/auth/logout` | bearer | `AuthController@logout` | revokes access + all refresh tokens; 204 |
| `POST /api/v2/auth/password/force-change` | bearer | `AuthController@forcePasswordChange` | clears `must_change_password`; 204 |
| `GET /api/v2/profile/me` | bearer | `ProfileController@me` | full profile object |

> `POST /auth/register` is **reserved/not exposed** (the client doesn't call it;
> open registration would be a security hole). Add behind admin control later.

**Added (new files)**
- `app/Services/V2/AuthService.php` — login/refresh/logout/forceChangePassword;
  Passport access token + rotating opaque refresh token (A1).
- `app/Http/Requests/V2/Auth/{LoginRequest,RefreshRequest,ForcePasswordChangeRequest}.php`.
- `app/Http/Resources/V2/{UserResource,AuthSessionResource,ProfileResource}.php`.
- `app/Http/Controllers/Api/V2/{AuthController,ProfileController}.php`.
- `tests/Concerns/InteractsWithPdcuAuth.php` (user + personal-access-client helpers).
- `tests/Feature/Api/V2/{AuthTest,ProfileTest}.php`.

**Edited**
- `routes/api_v2.php` — auth + profile groups (replacing the Phase 2 placeholders).
- `app/Support/V2/ApiResponse.php` + `BaseController.php` — `noContent()` now emits a
  truly empty 204 body (clean `assertNoContent`); `accepted()` empty-body safe.

**Field mapping (DB → wire)**
- User object: `id`→string PK, `name`→`full_name`, `role`→wire role (snake_case or
  **null** when unassigned → client shows role picker), `mustChangePassword`→bool.
- Profile: `fullName`/`phone`/`email` direct; `joinDate`←`created_at` (`Y-m-d`);
  `department`←assigned sector name; `avatarUrl`←`image_url`; `organization` a
  deterministic constant; unknown optional fields pruned (omitted) rather than null.

**Token strategy**
- Access token: Passport `createToken('pdcu-mobile-v2')` (keys generated via
  `php artisan passport:keys`; required for the API to sign tokens — see §17a).
- Refresh: opaque 80-char random, **SHA-256 hashed at rest** in `api_refresh_tokens`,
  30-day TTL, **single-use** (revoked + reissued on every `/auth/refresh`).
- Logout revokes the current Passport token (when real) + all the user's refresh tokens.

**Verification**
- `AuthTest` (9) + `ProfileTest` (2) — all green: login raw shape & wire role,
  null-role path, 401 bad creds, 422 fieldErrors contract, `me`, auth-required 401,
  force-change clears flag (204), refresh rotation invalidates the old token, logout.
- Full suite **21/21** (incl. foundation, DB baseline, default). `php -l` clean.
- `route:list` confirms the 6 new v2 routes; v1 `api/login` + `api/projects` intact.

**Backward-compatibility**
- No v1/web file touched. `passport:keys` only adds local signing keys
  (gitignored; web app uses session auth, unaffected — and it also repairs v1
  token issuance, which was inoperable without keys).
- All auth state is additive (`api_refresh_tokens`, additive `users` columns).

## 17a. Setup addendum — Passport keys

Token issuance requires Passport signing keys (absent on this checkout). One-time:

```bash
php artisan passport:keys        # generates storage/oauth-*.key (gitignored)
```

Tests seed a personal-access client per run (see `InteractsWithPdcuAuth`); for a
real environment run `php artisan passport:install` once.

## 20. F2 — Sectors → Commitments → Deliverables (read hierarchy)

Implements API_REFERENCE.md §11.3 — six read-only endpoints.

**Endpoints (all bearer)**

| Method & path | Handler |
| --- | --- |
| `GET /api/v2/sectors` | `SectorController@index` |
| `GET /api/v2/sectors/{id}` | `SectorController@show` |
| `GET /api/v2/sectors/{id}/commitments` | `SectorController@commitments` |
| `GET /api/v2/commitments/{id}` | `CommitmentController@show` |
| `GET /api/v2/commitments/{id}/deliverables` | `CommitmentController@deliverables` |
| `GET /api/v2/deliverables/{id}` | `DeliverableController@show` |

**Added**
- `app/Services/V2/HierarchyService.php` — the 6 reads; role-based access (reuses
  `canAccessAllSectors`/`getAssignedSectorIds`/`isSectorHead`/`isDataAdmin`) + active-framework scoping; attaches derived metrics as transient model attributes.
- `app/Services/V2/HierarchyMetrics.php` — **grouped** aggregate queries (no N+1)
  for commitment counts, KPI counts, deliverable counts, pending approvals, and a
  0–1 progress fraction (`COALESCE(delivery_department_value, actual_value)/milestone`,
  capped 1.0, averaged over the subtree).
- `app/Support/V2/Presenters/SectorPresenter.php` — deterministic `icon` (+ accent) slot.
- `WireEnums` — `commitmentStatusToWire` (→ on_track/delayed/critical) and
  `deliverableStatusToWire` (→ active/delayed).
- Resources `SectorResource`/`CommitmentResource`/`DeliverableResource`; controllers
  `SectorController`/`CommitmentController`/`DeliverableController`.
- `tests/Concerns/InteractsWithPdcuAuth.php` — hierarchy seeding helpers (+ `makeSectorHead`).
- `tests/Feature/Api/V2/HierarchyTest.php` (6 tests).

**Important fix — raw resource collections.** Per-class `$wrap = null` only unwraps
*single* resources; `AnonymousResourceCollection` reads wrapping from the base
`JsonResource`, so list endpoints were emitting `{ "data": [...] }`. Added
`app/Http/Middleware/ForceRawJsonResources.php` (`JsonResource::withoutWrapping()`)
and applied it to the **`api/v2` group only** (RouteServiceProvider). v1/web use no
API Resources, so this is inert to them. Singles remain unwrapped.

**Access scoping (security):** sector lists/details are role-scoped — all-access
roles (coordinator/governor/etc.) see the active framework's sectors; facilitators
see assigned sectors; sector head/data admin see only their own. Cross-sector access
returns **404** (no existence leak). Commitment/deliverable reads authorize via the
parent sector.

**Derived-field decisions (B3):** `icon` from a keyword map; `progressPercent`/
`avgProgress` from the performance fraction; commitment `completionStatus` = "N of M"
deliverables; `dueDate`/`nextMilestone` from max deliverable `due_date`. Fields with
no data source (`budgetAmount`, `year`, `staffId`-style) are pruned (omitted).

**Verification:** HierarchyTest 6/6 (shapes, derived metrics incl. progress 0.8,
role scoping, 404s, auth). Full suite **27/27**. `php -l` clean. v1 `api/login` +
`api/projects` intact.

**Backward-compatibility:** no v1/web file changed. The unwrap middleware and the
new connection/migrations remain v2/test-scoped.

## 21. F3 — KPI tracking

Implements API_REFERENCE.md §11.4 — two reads + three queued command endpoints.

**Endpoints (all bearer)**

| Method & path | Handler | Notes |
| --- | --- | --- |
| `GET /api/v2/deliverables/{id}/kpis` | `KpiController@index` | KPI summary list |
| `GET /api/v2/kpis/{id}` | `KpiController@show` | detail + submissions[] + supportingDocuments[] + hero |
| `POST /api/v2/kpis/{id}/submissions` | `KpiController@submit` | Data Admin submits actual → Pending Sector Head; 202 |
| `POST /api/v2/kpis/{id}/milestones` | `KpiController@setMilestone` | PDCU sets milestone; 202 |
| `POST /api/v2/kpis/{id}/tracking-entries` | `KpiController@addTracking` | interim data point; 202 |

**Added**
- `app/Services/V2/SectorAccessService.php` — extracted shared role-based sector
  access (reused by F2 + F3); `HierarchyService` refactored to delegate to it
  (F2 tests re-run green, behavior unchanged).
- `app/Services/V2/KpiTrackingService.php` — reads (summary/detail derivation:
  status active/stable/lagging/pending, quartersOverview, submissions, supporting
  docs from polymorphic files, hero copy, target from `kpi_targets`) + the three
  mutations over `PerformanceTracking` using the §4 state machine.
- `app/Http/Resources/V2/KpiResource.php` (summary + detail via attached `v_*` attrs).
- `app/Http/Requests/V2/Kpi/{SubmitPerformanceRequest,SetMilestoneRequest,AddTrackingEntryRequest}.php`.
- `app/Http/Controllers/Api/V2/KpiController.php`.
- `tests/Concerns/InteractsWithPdcuAuth.php` — `makeKpiTarget` helper.
- `tests/Feature/Api/V2/KpiTrackingTest.php` (10 tests).

**Workflow & rules**
- `submit` (re)submits a quarter to the Sector Head, clearing any downstream
  review state (so a resubmission after rejection restarts cleanly); a `Confirmed`
  quarter is **locked** → `409`. `setMilestone`/`addTrackingEntry` upsert the
  quarter's tracking without advancing the workflow.
- Year: `submit` derives it (KPI year → active framework year → current year);
  `milestone`/`tracking-entry` take it from the request. Quarter `qN` ↔ int.
- Evidence: `evidenceDocumentIds` re-point existing `File` rows' polymorphic
  `fileable` to the tracking (lenient; unknown ids skipped). No upload endpoint
  exists in §11.4, matching the contract.

**Authorization**
- Reads: caller must have sector access (else 404, no existence leak).
- `submit`/`tracking-entry`: Data Admin of that sector or an all-access role; others 403.
- `setMilestone`: PDCU (delivery unit / all-access); others 403.

**Verification:** KpiTrackingTest 10/10 (list shape + targetLabel, detail with
submissions/docs, submit→pending, 403 for non-data-admin, 422 fieldErrors,
409 locked, milestone, tracking entry, auth 401, cross-sector 404). Full suite
**37/37**. `php -l` clean; v1 routes intact.

**Backward-compatibility:** no v1/web file changed; `KpiController` (web) untouched
— v2 reuses the models + the new service. The `HierarchyService` refactor is v2-only
and covered by the green F2 suite.

## 22. F4 — Approvals workflow

Implements API_REFERENCE.md §11.6 — the four-role review lifecycle. Highest reuse:
runs directly on the §4 `PerformanceTracking` state machine.

**Endpoints (all bearer)**

| Method & path | Handler |
| --- | --- |
| `GET /approvals/coordinator/queue` | `ApprovalController@coordinatorQueue` |
| `GET /approvals/sector-head/queue` (`?quarter`) | `@sectorHeadQueue` |
| `GET /approvals/sector-head/bulk` (`?grouping=by_commitment\|by_deliverable`) | `@sectorHeadBulk` |
| `GET /approvals/facilitator/queue` (`?grouping=by_sector\|by_kpi`) | `@facilitatorQueue` |
| `GET /approvals/data-admin/my-kpis` (`?filter,?quarter,?year`) | `@myKpis` |
| `GET /approvals/submissions/{kpiId}` | `@submissionDetail` |
| `POST /approvals/submissions/{submissionId}/review` | `@review` (202) |
| `POST /approvals/submissions/bulk-approve` | `@bulkApprove` (202) |

**Added**
- `app/Services/V2/ApprovalService.php` — queues (filtered by accessible sectors +
  lifecycle state, grouped where required), submission detail, `review`, `bulkApprove`.
- `SectorAccessService::accessibleSectorIds()` (null = all, [] = none) for row filtering.
- `app/Http/Requests/V2/Approvals/{ReviewSubmissionRequest,BulkApproveRequest}.php`.
- `app/Http/Controllers/Api/V2/ApprovalController.php` (query‑param validation inline).
- `tests/Concerns/InteractsWithPdcuAuth.php` — `makeDataAdmin`, `makeFacilitator`.
- `tests/Feature/Api/V2/ApprovalsTest.php` (14 tests).

**Mapping & transitions**
- A "submission" is a `PerformanceTracking` row: queue `id` = tracking id (used for
  review/bulk); `kpiId` = KPI id (used for submission detail). `state` =
  `confirmation_status` → wire lifecycle.
- `review` (role + decision) advances/returns the lifecycle per §4: sector_head accept
  → Pending Facilitator (stamps approver); facilitator accept → Pending Coordinator
  (sets `delivery_department_value`/remark from `validatedValue`/`acceptRemarks`);
  coordinator accept → Confirmed. Any reject → Rejected (facilitator/coordinator store
  their reason column; sector-head reason recorded on `remarks`, no dedicated column).
- `bulk-approve` = sector-head accept applied transactionally to many; strict — any
  non‑approvable id ⇒ `409`.

**Authorization & errors**
- Every read/mutation requires sector access (else `404`, no leak). `review`
  cross-checks the **claimed role against the token** (sector head of that sector /
  facilitator assigned to it / coordinator|deputy), else `403`. Reviewing a submission
  not in the role's precursor state ⇒ `409`. Validation ⇒ `422 fieldErrors`.

**Design note — response shape.** Queue/detail responses are computed aggregates
(grouping, nested stats/items), so the service returns **raw associative arrays** the
controller returns directly (still raw JSON, no envelope). This is a deliberate, minor
divergence from the Resource-per-model pattern used by F1–F3.

**Deferred (flagged, non-blocking):** workflow **notifications** (FCM/in-app) are NOT
dispatched by these endpoints yet — they belong to the Notifications feature; the web
app still sends them for web-initiated actions. State transitions are complete.

**Verification:** ApprovalsTest 14/14 (all five queues incl. groupings, submission
detail, the three accept transitions, coordinator reject, 409 state-mismatch, 403
wrong-role, 422 validation, bulk-approve, auth 401). Full suite **51/51**. `php -l`
clean; v1 routes intact.

**Backward-compatibility:** no v1/web file changed (the web `KpiController`/
`UserController` approval paths are untouched; v2 reuses the models + new service).

## 23. F5 — Dashboards

Implements API_REFERENCE.md §11.11 — six role-specific snapshot endpoints, each
resolved from the authenticated user and **gated to its role** (403 otherwise).

**Endpoints (all bearer)**

| Method & path | Role | Handler |
| --- | --- | --- |
| `GET /dashboard/governor` | Governor | `DashboardController@governor` |
| `GET /dashboard/coordinator` | Coordinator / Deputy | `@coordinator` |
| `GET /dashboard/facilitator` | Facilitator | `@facilitator` |
| `GET /dashboard/sector-head` | Sector Head | `@sectorHead` |
| `GET /dashboard/data-admin` | Data Admin | `@dataAdmin` |
| `GET /dashboard/system-admin` | System Admin | `@systemAdmin` |

**Added**
- `app/Services/V2/DashboardService.php` — the six snapshots (raw arrays, as §11.6),
  reusing `HierarchyMetrics` (sector/commitment progress + counts) and direct
  aggregates (KPI status buckets, submission rate, pending counts, recent activity,
  user/gallery/framework counts, logins-24h + security rows from `oauth_access_tokens`).
- `app/Http/Controllers/Api/V2/DashboardController.php` (6 role-gated methods).

**Derivation & placeholders (B3):** `overallPercent`/`actualPercent` from the
performance fraction (101% cap); `planPercent` = expected progress by current quarter;
KPI buckets on‑track ≥70 / at‑risk 40–69 / delayed <40 (no‑data KPIs count as delayed);
greetings are time‑of‑day based. A few **ops/vanity metrics with no DB source** use
clearly‑commented deterministic placeholders: `overallDeltaLabel` (`+0.0%`),
facilitator `avgResponseDays`/`reviewAccuracyPercent`, system‑admin
`serverHealthPercent`/`apiResponseLabel`/`storageLabel`.

**Role gating note:** `isGovernor()`/`isSystemAdmin()` key off `target_entity`
(`State`/`System`), while coordinator/facilitator/sector-head/data-admin use the role
helpers — the service calls the matching helper per endpoint.

**Verification:** DashboardTest 8/8 (all six snapshot shapes, raw — no `data` wrapper —,
403 role gating, 401 auth). Full suite **59/59**. `php -l` clean; v1 routes intact.

**Backward-compatibility:** no v1/web file changed (web `DashboardController` untouched;
v2 reuses models + `HierarchyMetrics` + the new service).

## 24. F6 — Data-entry windows

Implements API_REFERENCE.md §11.7 — coordinator-only management of the per-sector
data-entry windows. 7 endpoints; reuses the `DataEntryAccess` model.

| Method & path | Handler |
| --- | --- |
| `GET /data-entry/windows` (`?year,?quarter`) | `DataEntryWindowController@index` |
| `GET /data-entry/stats` (`?year,?quarter`) | `@stats` |
| `POST /data-entry/windows/lock-all` | `@lockAll` (202) |
| `POST /data-entry/windows/unlock-all` | `@unlockAll` (202) |
| `POST /data-entry/windows/{sectorId}/open` | `@open` (202) |
| `POST /data-entry/windows/{sectorId}/lock` | `@lock` (202) |
| `POST /data-entry/windows/{sectorId}/override` | `@override` (202) |

`DataEntryWindowService` lazily **seeds rows** for every active-framework sector for
the requested (year, quarter) if missing (mirroring the web's lazy initialization);
status `closed` → wire `locked`, `override` → wire `open`; coordinator-only (403
otherwise). `DataEntryWindowTest` **8/8**.

## 25. F7 — Frameworks

Implements API_REFERENCE.md §11.5 — 7 endpoints. Schema: additive guarded migration
`2026_05_28_100000_add_api_v2_columns_to_frameworks_table` (`subtitle`, `is_default`,
`inherited_from_framework_id`). `is_default` is a **mirror** of the web's
`status='Active'` source of truth (GR2).

| Method & path | Handler |
| --- | --- |
| `GET /frameworks` | `FrameworkController@index` |
| `GET /frameworks/stats` | `@stats` |
| `GET /frameworks/{id}` | `@show` (counts + creator + inheritance) |
| `GET /frameworks/{id}/sectors` | `@sectors` |
| `POST /frameworks` | `@store` (201; reuses web "activate" semantics, copies hierarchy if `sectorMethod=inherit`) |
| `POST /frameworks/{id}/archive` | `@archive` (202) |
| `POST /frameworks/{id}/set-default` | `@setDefault` (202; archives others, sets active+is_default) |

**Inheritance** copies sectors → commitments → deliverables → KPIs into the new
framework (performance data intentionally not copied). **Duplicate year** ⇒ 409.
**Already-archived archive** ⇒ 409. Coordinator-only for mutations (403 for others).
`FrameworksTest` **9/9**.

**This batch:** F6 + F7. Full suite **76/76**. `php -l` clean; v1 routes intact.

**Backward-compat:** no v1/web file changed. The frameworks migration is additive
and guarded — the web app keys off `status='Active'` exclusively, so the new
columns are inert.

## 26. F8 — Users & security

Implements API_REFERENCE.md §11.9. System-Admin-only directory + multipart create;
authenticated-user `me` flows; security log derived from oauth_access_tokens.

| Method & path | Handler |
| --- | --- |
| `GET /users` (`?search,?role,?sector`) | `UsersController@index` |
| `GET /users/{id}` | `@show` |
| `POST /users` (multipart: `fullName,email,phone,role,avatarKey?,photo?`) | `@store` (202) |
| `POST /users/me/password` | `@changeMyPassword` (204) |
| `POST /users/me/photo` (multipart: `photo`) | `@updateMyPhoto` (204) |
| `GET /users/security-log` (`?filter,?q`) | `@securityLog` |

**Added:** `UsersService`, three FormRequests (`AddUserRequest`/`ChangePasswordRequest`/
`UpdatePhotoRequest`), `UsersController`. Photo storage: `storage/app/public/uploads/users`.
New users get a random password + `must_change_password=true` (admin invitation flow).

**Authorization:** list/detail/create/security-log are System Admin only (403 otherwise).
`me` flows require any authenticated user.

**Limitations (noted, non-blocking):**
- `POST /users` doesn't accept a `sectorId`/role-target (the spec form doesn't expose
  one) — sector-scoped roles are created with `entity_id=0` and must be re-targeted
  via a future admin endpoint.
- Security log returns real data for `filter=all|logins` (recent OAuth token
  issuances). `changes`/`denied` return `[]` until a dedicated `security_events`
  audit table is added.

**Verification:** UsersTest **11/11** (list filtering, detail, create + multipart
upload, duplicate email 409, validation 422, change-password incl. wrong-current
422, update photo, admin gate 403, security log, auth 401).

## 27. F9 — Gallery

Implements API_REFERENCE.md §11.13. Additive guarded migration:
`2026_05_28_100100_add_api_v2_columns_to_galleries_table` adds `category`,
`is_public`, `icon_key`, `gradient_keys` (json) — web app reads `status` only, so
inert.

| Method & path | Handler |
| --- | --- |
| `GET /gallery/management` (`?tab=all\|recent\|archived`) | `GalleryController@management` (System Admin) |
| `GET /gallery/public` (`?filter=all\|roads\|healthcare\|education`) | `@publicList` |
| `GET /gallery/items/{id}` | `@show` |
| `POST /gallery/items` (multipart: `title,description,category,displayOrder,isPublic,asset?`) | `@upload` (202; System Admin) |

**Added:** `GalleryService`, `UploadGalleryRequest`, `GalleryController`. Gallery
model is not mutated (GR1) — new attributes are set directly on the instance, which
works because `$fillable` is not enforced for direct property assignment. Assets:
`storage/app/public/uploads/galleries`.

**Status: ⚠️ tests written but unverified end-to-end this run.** The MySQL service on
the dev host became unreachable mid-run ("SQLSTATE[HY000] [2002] No connection could
be made"), so the last `RefreshDatabase` cycle failed. Once MySQL is back, run:

```bash
php artisan migrate:fresh --database=mysql_test
php vendor/bin/phpunit tests/Feature/Api/V2/GalleryTest.php
php vendor/bin/phpunit                # full suite
```

I expect green — the code is lint-clean (`php -l` all files) and follows the same
patterns as F8 which ran 11/11 right before the outage.
