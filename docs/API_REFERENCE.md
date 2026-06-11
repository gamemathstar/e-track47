# PDCU REST API Reference

**Audience:** backend engineering team.
**Status:** implementation reference, reverse-engineered from the mobile
client's data layer (Dio data sources, request/response DTOs, interceptors,
and the centralized error mapper). Every endpoint, path, payload, and field
type below reflects what the app **actually sends and parses today** — not an
aspirational design. Where the client's behavior diverges from a more robust
ideal, a **⚠ Backend note** calls it out.

> **Source of truth.** This document is derived from:
> `lib/core/constants/api_endpoints.dart`, `lib/core/network/` (client +
> interceptors), `lib/core/errors/` (exception/failure mapping), and each
> feature's `data/datasources/*_remote_data_source.dart` + `data/models/*`.

---

## Table of contents

1. [Overview](#1-overview)
2. [Base URL & environments](#2-base-url--environments)
3. [Authentication strategy](#3-authentication-strategy)
4. [Common request headers](#4-common-request-headers)
5. [Response conventions](#5-response-conventions)
6. [Error handling conventions](#6-error-handling-conventions)
7. [Status codes](#7-status-codes)
8. [Timeouts & retries](#8-timeouts--retries)
9. [Pagination, filtering & sorting](#9-pagination-filtering--sorting)
10. [File uploads](#10-file-uploads)
11. [Endpoint reference](#11-endpoint-reference)
    - [11.1 Authentication](#111-authentication)
    - [11.2 Profile](#112-profile)
    - [11.3 Sectors, commitments & deliverables](#113-sectors-commitments--deliverables)
    - [11.4 KPI tracking](#114-kpi-tracking)
    - [11.5 Frameworks](#115-frameworks)
    - [11.6 Approvals workflow](#116-approvals-workflow)
    - [11.7 Data-entry windows](#117-data-entry-windows)
    - [11.8 Reports](#118-reports)
    - [11.9 Users & security](#119-users--security)
    - [11.10 System](#1110-system)
    - [11.11 Dashboards](#1111-dashboards)
    - [11.12 Settings](#1112-settings)
    - [11.13 Gallery](#1113-gallery)
    - [11.14 Notifications](#1114-notifications)
    - [11.15 Discussions](#1115-discussions)
12. [Appendix A — enum wire values](#12-appendix-a--enum-wire-values)
13. [Appendix B — backend recommendations](#13-appendix-b--backend-recommendations)

---

## 1. Overview

The PDCU (Performance Delivery & Coordination Unit) mobile app is a Flutter
client consuming a JSON-over-HTTP REST API. The client is built on Clean
Architecture; all network access funnels through a single `ApiClient` (a thin
`Dio` wrapper) and every endpoint path is declared centrally in
`ApiEndpoints`. There is **one** HTTP client, **one** auth interceptor, **one**
error interceptor, and **one** retry interceptor — so the conventions in
sections 3–10 apply uniformly to **every** endpoint unless an endpoint's own
entry says otherwise.

The API surface spans 15 functional modules: authentication, profile, the
sector/commitment/deliverable hierarchy, KPI tracking, frameworks, the
multi-role approvals workflow, data-entry window management, reports, user
administration, system/maintenance signals, role dashboards, settings, gallery,
notifications, and discussions.

---

## 2. Base URL & environments

All paths in this document are **relative** to a base URL resolved at build
time. The client never inlines absolute URLs.

| Environment | Default base URL | Notes |
| --- | --- | --- |
| `dev` | `https://api.dev.pdcu.local` | developer backend |
| `staging` | `https://api.staging.pdcu.local` | pre-prod |
| `prod` | `https://api.pdcu.example` | production (placeholder — confirm real host) |

The base URL is overridable per build via `--dart-define=API_BASE_URL=…`. A
fourth client mode, `mock`, serves in-memory fixtures and makes **no** network
calls (used for offline development). All real traffic uses one of the three
environments above.

---

## 3. Authentication strategy

- **Scheme:** Bearer token (JWT-style access/refresh pair).
- The login response returns an **access token** and a **refresh token**.
  Tokens are persisted in the device secure store (Keychain / encrypted
  SharedPreferences).
- On **every** outbound request, the `AuthInterceptor` attaches:
  `Authorization: Bearer <accessToken>`.
- An endpoint can opt **out** of the bearer header (used for `POST /auth/login`).
  The login call is the only unauthenticated endpoint in the app today.
- A `mustChangePassword` flag carried on the user object gates a forced
  password-change screen; until cleared (server-side), the user is held on that
  screen by the client router.

⚠ **Backend note — token refresh.** `POST /auth/refresh` is declared in the
client (`ApiEndpoints.refresh`) but **no data source currently calls it** — the
silent-refresh-on-401 flow is not yet implemented client-side. Today a 401
surfaces as an auth failure and the user is routed to re-authenticate. Please
still implement `/auth/refresh` per §11.1 so the client can adopt it without a
contract change.

⚠ **Backend note — registration.** `POST /auth/register` is declared
(`ApiEndpoints.register`) but is **not wired** to any client call yet. Treat it
as reserved.

---

## 4. Common request headers

Sent on every request via the shared client configuration:

| Header | Value | Applies to |
| --- | --- | --- |
| `Accept` | `application/json` | all requests |
| `Content-Type` | `application/json` | all requests (except multipart uploads — see §10) |
| `Authorization` | `Bearer <accessToken>` | all requests **except** `POST /auth/login` |

For multipart file uploads, Dio sets `Content-Type: multipart/form-data` with
the boundary automatically; do not expect a JSON content type on those calls
(see §10).

---

## 5. Response conventions

**The client consumes unwrapped JSON.** For a successful response it reads the
HTTP body directly as the resource:

- **Single resource** → a top-level JSON **object** (`{ … }`).
- **Collection** → a top-level JSON **array** (`[ … ]`).

There is **no** `{ "success", "message", "data" }` envelope. The client parses
`response.data` as the resource itself and will raise a parse error if it
receives a wrapper where an object/array is expected.

For **command/mutation** endpoints (most `POST`/`PUT`/`DELETE` that return
`void`), the client **ignores the response body entirely** — any `2xx` is
treated as success. Such endpoints may return `200`, `201`, or `204`; a body is
optional.

⚠ **Backend note — envelope.** If the project standard requires a
`{ success, message, data }` envelope, the **client must be updated** to unwrap
it; as implemented, the client expects raw representations. This document
specifies the **raw** contract the current app depends on. Do not introduce an
envelope without a coordinated client change.

### Field-naming conventions (observed)

Naming is **not** globally consistent — document and follow per-field:

- Auth token fields are **snake_case**: `access_token`, `refresh_token`.
- The user object mixes cases: `id`, `email`, `name`, `role` (snake_case
  *values*), and `mustChangePassword` (**camelCase** key).
- Most other DTOs use **camelCase** keys (e.g. `fullName`, `joinDate`,
  `staffId`). Enum-typed fields are serialized as their **wire string** (see
  [Appendix A](#12-appendix-a--enum-wire-values)).

Each endpoint entry lists exact keys; treat those as authoritative.

---

## 6. Error handling conventions

The client maps failures on the **HTTP status code and transport error type
only — it does not parse the error response body today.** That means error
*copy* shown to users is client-side; the server's error `message` is not
surfaced. Codes still matter: they drive routing (e.g. 401 → re-auth) and
retry.

Recommended (forward-compatible) error body — return this for all non-2xx so
the client can adopt body parsing later without a contract change:

```json
{
  "code": "validation_error",
  "message": "Human-readable summary.",
  "fieldErrors": {
    "email": "Email is already in use."
  }
}
```

`fieldErrors` is optional and only meaningful for `400`/`422` validation
responses.

### How the client reacts to each status

| Server response | Client failure type | UX |
| --- | --- | --- |
| `401`, `403` | `AuthFailure` | session treated as invalid → routed to re-auth |
| `400`, `404`, `409`, `422`, other `4xx` | `ServerFailure` (HTTP status preserved) | generic "request could not be completed" copy |
| `5xx` | `ServerFailure` | generic "server error" copy |
| connect/send/receive **timeout** | `NetworkFailure` | offline / try-again UX |
| connection error (DNS, refused, offline) | `NetworkFailure` | offline UX |
| request cancelled | `NetworkFailure` | silent |
| malformed/unexpected success body | `ServerFailure` (parse) | generic error |

⚠ **Backend note — validation.** The domain layer validates inputs
**client-side before sending** (e.g. email shape, password ≥ 8 chars), so many
bad requests never reach you. The current client maps a server `400`/`422` to a
**generic** `ServerFailure`, **not** to field-level errors — it does not read
`fieldErrors` yet. Still validate server-side and return `422 + fieldErrors`;
treat client-side validation as a convenience, not a security boundary.

---

## 7. Status codes

| Code | Meaning in this API |
| --- | --- |
| `200 OK` | successful read, or successful command returning a body |
| `201 Created` | resource created (client treats same as 200) |
| `204 No Content` | successful command with no body (preferred for `void` mutations) |
| `400 Bad Request` | malformed request / failed validation (see §6) |
| `401 Unauthorized` | missing/invalid/expired access token |
| `403 Forbidden` | authenticated but not permitted for this role/resource |
| `404 Not Found` | unknown path parameter (id) |
| `409 Conflict` | state conflict (e.g. duplicate, already-locked window) |
| `422 Unprocessable Entity` | semantic validation failure (recommended for field errors) |
| `502/503/504` | transient upstream errors — **client auto-retries** (see §8) |
| `5xx` | server error |

---

## 8. Timeouts & retries

| Setting | Value |
| --- | --- |
| Connect timeout | 20 s |
| Receive timeout | 30 s |
| Send timeout | 30 s |

**Automatic retry** (client-side, transparent to the backend):

- Retried automatically: **`GET`/`HEAD`** requests only.
- Retry triggers: connect/send/receive timeout, connection error, or HTTP
  `502`/`503`/`504`.
- Up to **2** retries, exponential backoff (base 400 ms → 400 ms, then 800 ms).
- Non-idempotent methods (`POST`/`PUT`/`DELETE`) are **not** retried
  automatically.

⚠ **Backend note — idempotency.** Because the client may replay `GET`s, ensure
all `GET`s are side-effect free. For command endpoints, consider supporting an
idempotency key if you later enable client retries for mutations.

---

## 9. Pagination, filtering & sorting

**No pagination is implemented in the client today.** List endpoints return the
**full collection** as a JSON array; the client applies any further slicing in
memory. Filtering/search/scoping is done via **query parameters** on specific
list endpoints (documented per endpoint), e.g. `?filter=`, `?tab=`, `?search=`,
`?sector=`, `?quarter=`, `?year=`.

⚠ **Backend note — pagination.** Returning unbounded collections will not scale.
Recommended forward path (requires a future client change): cursor or
offset/limit pagination with a documented envelope, e.g.
`?limit=50&cursor=…` → `{ "items": [...], "nextCursor": "…" }`. Coordinate
before introducing it, since the current client expects a bare array.

---

## 10. File uploads

Two endpoints accept binary uploads, sent as `multipart/form-data` (Dio
`FormData`):

- `POST /gallery/items` — gallery media upload (see §11.13).
- `POST /users` — create user with optional avatar (see §11.9).
- `POST /users/me/photo` — profile photo update (see §11.9).
- `POST /kpis/{id}/evidence` — performance-tracking evidence upload (see §11.4.8).

Scalar fields are sent as form fields alongside the file part. The file part is
attached only when the user selected a local asset; when absent, the multipart
request carries the scalar fields and no file. Exact field names are listed in
each endpoint entry.

---

## 11. Endpoint reference

Each entry documents: purpose, method, path, auth, params, request body (with
field types and required/optional), success response shape, applicable status
codes, business rules, and examples. **Error responses follow §6 globally** and
are only restated per-endpoint when an endpoint has notable status semantics.

---

### 11.1 Authentication

Module: `features/auth`. Data source: `auth_remote_data_source.dart`. DTOs:
`auth_session_model.dart`, `user_model.dart`.

#### 11.1.1 Login

| | |
| --- | --- |
| **Purpose** | Exchange credentials for an access/refresh token pair + user. |
| **Method / Path** | `POST /auth/login` |
| **Auth** | **None** — this is the only endpoint sent without a bearer token. |
| **Headers** | `Content-Type: application/json` |

**Request body**

| Field | Type | Req. | Validation (client-side pre-flight) |
| --- | --- | --- | --- |
| `email` | string | ✅ | valid email shape |
| `password` | string | ✅ | ≥ 8 characters |

```json
{ "email": "amina.egbe@pdcu.gov.ng", "password": "s3cretpass" }
```

**Success — `200 OK`** (raw object). Note the **snake_case** token keys and the
nested `user` object:

```json
{
  "access_token": "eyJhbGciOiJIUzI1Ni␣…",
  "refresh_token": "def50200a1b2c3␣…",
  "user": {
    "id": "u_001",
    "email": "amina.egbe@pdcu.gov.ng",
    "name": "Amina Egbe",
    "role": "coordinator",
    "mustChangePassword": false
  }
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `access_token` | string | ✅ | bearer token for subsequent calls |
| `refresh_token` | string | ✅ | used by the (planned) refresh flow |
| `user` | object | ✅ | see [User object](#user-object) |

The client raises a parse error if `access_token`, `refresh_token` are not
strings or `user` is not an object.

**Status codes:** `200` success · `401` invalid credentials · `400` malformed.

**Business rules**
- If `user.mustChangePassword` is `true`, the client immediately routes to the
  forced password-change screen and blocks all other navigation until the
  server clears the flag (see §11.1.4).
- `role` may be `null` — the client then routes the user to a role picker.

<a name="user-object"></a>**User object** (shared by login & `/auth/me`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `email` | string | ✅ | |
| `name` | string | ✅ | display name |
| `role` | string \| null | ❌ | wire enum (snake_case), null when unassigned — see [Appendix A](#12-appendix-a--enum-wire-values) |
| `mustChangePassword` | bool | ❌ | defaults to `false` when omitted |

#### 11.1.2 Get current user

| | |
| --- | --- |
| **Purpose** | Resolve the authenticated user (boot/session restore). |
| **Method / Path** | `GET /auth/me` |
| **Auth** | Bearer required |

**Success — `200 OK`**: the [User object](#user-object) as a raw object.

```json
{ "id": "u_001", "email": "amina.egbe@pdcu.gov.ng", "name": "Amina Egbe", "role": "coordinator", "mustChangePassword": false }
```

**Status codes:** `200` · `401` token invalid/expired.

#### 11.1.3 Logout

| | |
| --- | --- |
| **Purpose** | Invalidate the current session server-side. |
| **Method / Path** | `POST /auth/logout` |
| **Auth** | Bearer required |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `204` preferred) |

The client clears local tokens and session flags regardless of the response
body. Status `401` is acceptable (already-invalid token) and still results in a
local clear.

#### 11.1.4 Force password change

| | |
| --- | --- |
| **Purpose** | Set a new password during the forced-change flow (admin reset / expiry). |
| **Method / Path** | `POST /auth/password/force-change` |
| **Auth** | Bearer required (no current-password challenge) |

**Request body**

| Field | Type | Req. | Validation (client pre-flight) |
| --- | --- | --- | --- |
| `newPassword` | string | ✅ | ≥ 8 characters |

```json
{ "newPassword": "n3wStrongPass" }
```

**Success:** any `2xx` (`204` preferred). On success the server **must clear**
the account's `mustChangePassword` flag; the client then clears its local flag
and releases the user from the forced-change screen.

**Status codes:** `204` success · `400`/`422` weak/invalid password · `401`
token invalid.

#### 11.1.5 Refresh token *(reserved — not yet called)*

| | |
| --- | --- |
| **Method / Path** | `POST /auth/refresh` |
| **Auth** | refresh token (scheme TBD — header vs body) |
| **Status** | Declared client-side; **not invoked yet**. Implement per the recommended contract below so the client can adopt silent refresh without a breaking change. |

**Recommended request body**

```json
{ "refresh_token": "def50200a1b2c3␣…" }
```

**Recommended success — `200 OK`**

```json
{ "access_token": "…", "refresh_token": "…" }
```

---

### 11.2 Profile

Module: `features/profile`. Data source: `profile_remote_data_source.dart`.
DTO: `profile_model.dart`.

#### 11.2.1 Get my profile

| | |
| --- | --- |
| **Purpose** | Fetch the signed-in user's full profile for the profile screen. |
| **Method / Path** | `GET /profile/me` |
| **Auth** | Bearer required |
| **Params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "id": "u_001",
  "fullName": "Amina Egbe",
  "email": "amina.egbe@pdcu.gov.ng",
  "phone": "+234 803 000 0000",
  "role": "coordinator",
  "organization": "Jigawa State Government",
  "department": "Performance Delivery & Coordination",
  "address": "PDCU Wing, 4th Floor, Dutse",
  "staffId": "PDCU-2023-441",
  "joinDate": "2023-10-12",
  "bio": "Lead Governance Analyst…",
  "avatarUrl": null
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `fullName` | string | ✅ | |
| `email` | string | ✅ | |
| `phone` | string | ❌ | |
| `role` | string | ❌ | wire enum (snake_case) — see [Appendix A](#12-appendix-a--enum-wire-values) |
| `organization` | string | ❌ | |
| `department` | string | ❌ | |
| `address` | string | ❌ | |
| `staffId` | string | ❌ | |
| `joinDate` | string | ❌ | ISO-8601 date (`YYYY-MM-DD`) |
| `bio` | string | ❌ | |
| `avatarUrl` | string | ❌ | absolute URL or null |

The client requires `id`, `fullName`, `email` to be strings; every optional
field must be a string when present (a non-string raises a parse error).

**Status codes:** `200` · `401`.

---

### 11.3 Sectors, commitments & deliverables

Module: `features/sectors`. Data source: `sectors_remote_data_source.dart`.
DTOs: `sector_model.dart`, `commitment_model.dart`, `deliverable_model.dart`.

This feature exposes a three-level read hierarchy — sector → commitment →
deliverable — with both a list and a detail endpoint at each level. All
endpoints are read-only (`GET`). The list endpoints return the summary block;
the detail endpoints (`…/:id`) additionally populate the optional trailing
fields documented per model below.

#### 11.3.1 List sectors

| | |
| --- | --- |
| **Purpose** | Fetch all sectors for the sectors overview screen. |
| **Method / Path** | `GET /sectors` |
| **Auth** | Bearer required |
| **Query params** | none |

**Success — `200 OK`** (raw array; each item is a `SectorModel`, list form
typically omits the detail-only trailing fields):

```json
[
  {
    "id": "education",
    "name": "Education",
    "ministry": "Ministry of Education",
    "icon": "school",
    "progressPercent": 0.88,
    "completedCommitments": 5,
    "atRiskCommitments": 0
  }
]
```

**Response fields** (per item — see [§11.3.2](#1132-get-sector-detail) table)

The client requires `id`, `name`, `ministry`, `icon` to be strings and
`progressPercent`, `completedCommitments`, `atRiskCommitments` to be numbers;
malformed items raise a parse error.

**Status codes:** `200` · `401`.

#### 11.3.2 Get sector (detail)

| | |
| --- | --- |
| **Purpose** | Fetch one sector with overview counts for the sector detail screen. |
| **Method / Path** | `GET /sectors/{id}` |
| **Auth** | Bearer required |
| **Path params** | `id` — sector identifier (e.g. `health`) |
| **Query params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "id": "health",
  "name": "Health Sector",
  "ministry": "Ministry of Health",
  "icon": "medical_services",
  "progressPercent": 0.75,
  "completedCommitments": 2,
  "atRiskCommitments": 1,
  "totalCommitments": 5,
  "inProgressCommitments": 2,
  "pendingApprovals": 8
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `name` | string | ✅ | |
| `ministry` | string | ✅ | |
| `icon` | string | ✅ | icon token used by the client (e.g. `medical_services`, `school`) |
| `progressPercent` | number | ✅ | fraction `0.0`–`1.0` |
| `completedCommitments` | int | ✅ | |
| `atRiskCommitments` | int | ✅ | |
| `totalCommitments` | int | ❌ | detail-only |
| `inProgressCommitments` | int | ❌ | detail-only |
| `pendingApprovals` | int | ❌ | detail-only |

**Status codes:** `200` · `401` · `404`.

#### 11.3.3 List commitments for a sector

| | |
| --- | --- |
| **Purpose** | Fetch the commitments belonging to a sector. |
| **Method / Path** | `GET /sectors/{id}/commitments` |
| **Auth** | Bearer required |
| **Path params** | `id` — sector identifier |
| **Query params** | none |

**Success — `200 OK`** (raw array; each item is a `CommitmentModel`, list form
typically omits the detail-only trailing fields):

```json
[
  {
    "id": "rural-connectivity",
    "sectorId": "health",
    "title": "Rural Medical Connectivity",
    "status": "delayed",
    "progressPercent": 0.45,
    "kpiCount": 8,
    "dueDate": "2024-11-15"
  }
]
```

**Response fields** (per item — see [§11.3.4](#1134-get-commitment-detail) table)

**Status codes:** `200` · `401` · `404`.

#### 11.3.4 Get commitment (detail)

| | |
| --- | --- |
| **Purpose** | Fetch one commitment with detail metrics for the commitment detail screen. |
| **Method / Path** | `GET /commitments/{id}` |
| **Auth** | Bearer required |
| **Path params** | `id` — commitment identifier (e.g. `maternal-health`) |
| **Query params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "id": "maternal-health",
  "sectorId": "health",
  "title": "Maternal Health Expansion",
  "status": "on_track",
  "progressPercent": 0.72,
  "kpiCount": 12,
  "dueDate": "2024-12-31",
  "description": "Improve maternal health services across rural sectors through clinic digitization, staff training, and rapid response capability.",
  "deliverableCount": 5,
  "atRiskCount": 1,
  "completionStatus": "3 of 5",
  "nextMilestone": "2024-11-15"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `sectorId` | string | ✅ | parent sector id |
| `title` | string | ✅ | |
| `status` | string | ✅ | enum wire string — `on_track` / `delayed` / `critical` |
| `progressPercent` | number | ✅ | fraction `0.0`–`1.0` |
| `kpiCount` | int | ✅ | |
| `dueDate` | string | ❌ | ISO-8601 date (`YYYY-MM-DD`) |
| `description` | string | ❌ | detail-only |
| `deliverableCount` | int | ❌ | detail-only |
| `atRiskCount` | int | ❌ | detail-only |
| `completionStatus` | string | ❌ | free-form label (e.g. `"3 of 5"`) |
| `nextMilestone` | string | ❌ | ISO-8601 date or free-form label |

**Status codes:** `200` · `401` · `404`.

#### 11.3.5 List deliverables for a commitment

| | |
| --- | --- |
| **Purpose** | Fetch the deliverables belonging to a commitment. |
| **Method / Path** | `GET /commitments/{id}/deliverables` |
| **Auth** | Bearer required |
| **Path params** | `id` — commitment identifier |
| **Query params** | none |

**Success — `200 OK`** (raw array; each item is a `DeliverableModel`, list form
typically omits the detail-only trailing fields):

```json
[
  {
    "id": "staff-training-program",
    "commitmentId": "maternal-health",
    "title": "Staff Training Program",
    "status": "active",
    "kpiCount": 3,
    "progressPercent": 0.45,
    "parentCommitmentTitle": "Maternal Health Expansion"
  }
]
```

**Response fields** (per item — see [§11.3.6](#1136-get-deliverable-detail) table)

**Status codes:** `200` · `401` · `404`.

#### 11.3.6 Get deliverable (detail)

| | |
| --- | --- |
| **Purpose** | Fetch one deliverable with budget/progress metadata for the deliverable detail screen. |
| **Method / Path** | `GET /deliverables/{id}` |
| **Auth** | Bearer required |
| **Path params** | `id` — deliverable identifier (e.g. `rural-clinic-digitization`) |
| **Query params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "id": "rural-clinic-digitization",
  "commitmentId": "maternal-health",
  "title": "Rural Clinic Digitization",
  "status": "active",
  "kpiCount": 4,
  "progressPercent": 0.80,
  "budgetAmount": "₦120M",
  "year": 2024,
  "avgProgress": 0.78,
  "lastUpdated": "2024-11-12T10:00:00Z",
  "parentCommitmentTitle": "Maternal Health Expansion"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `commitmentId` | string | ✅ | parent commitment id |
| `title` | string | ✅ | |
| `status` | string | ✅ | enum wire string — `active` / `delayed` |
| `kpiCount` | int | ✅ | |
| `progressPercent` | number | ✅ | fraction `0.0`–`1.0` |
| `budgetAmount` | string | ❌ | pre-formatted display string (e.g. `"₦120M"`) |
| `year` | int | ❌ | |
| `avgProgress` | number | ❌ | fraction `0.0`–`1.0` |
| `lastUpdated` | string | ❌ | ISO-8601 timestamp |
| `parentCommitmentTitle` | string | ❌ | denormalized title of the parent commitment |

**Status codes:** `200` · `401` · `404`.

**Enums in this section**

- Commitment `status` → `on_track` · `delayed` · `critical`
- Deliverable `status` → `active` · `delayed`


---

### 11.4 KPI tracking

Module: `features/kpi_tracking`. Data source: `kpis_remote_data_source.dart`.
DTOs: `kpi_model.dart`, `kpi_submission_model.dart`,
`kpi_supporting_doc_model.dart`. Request entities:
`submit_performance_params.dart`, `set_milestone_params.dart`,
`add_tracking_entry_params.dart`.

Two read endpoints expose KPIs (per-deliverable list + detail). Three command
endpoints mutate a KPI's performance record; all three are `POST`, return no
body the client uses (the data source ignores the response), and are queued
server-side (the design notes a `202` queue response).

#### 11.4.1 List KPIs for a deliverable

| | |
| --- | --- |
| **Purpose** | Fetch the KPIs attached to a deliverable for the KPI list. |
| **Method / Path** | `GET /deliverables/{id}/kpis` |
| **Auth** | Bearer required |
| **Path params** | `id` — deliverable identifier (e.g. `rural-clinic-digitization`) |
| **Query params** | none |

**Success — `200 OK`** (raw array; each item is a `KpiModel`, list form
typically populates only the required summary fields):

```json
[
  {
    "id": "solar-power",
    "deliverableId": "rural-clinic-digitization",
    "title": "Solar Power Installation",
    "targetLabel": "Target: 50 sites",
    "statusLabel": "Stable",
    "status": "stable",
    "quartersOverview": ["completed", "completed", "completed", "pending"],
    "lastUpdatedLabel": "Updated Oct 30"
  }
]
```

**Response fields** (per item — see [§11.4.2](#1142-get-kpi-detail) table)

**Status codes:** `200` · `401` · `404`.

#### 11.4.2 Get KPI (detail)

| | |
| --- | --- |
| **Purpose** | Fetch one KPI with hero copy, submissions, supporting docs and mutation-form labels for the KPI detail / data-entry screens. |
| **Method / Path** | `GET /kpis/{id}` |
| **Auth** | Bearer required |
| **Path params** | `id` — KPI identifier (e.g. `ehr-coverage`) |
| **Query params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "id": "ehr-coverage",
  "deliverableId": "rural-clinic-digitization",
  "title": "Percentage of clinics with EHR",
  "targetLabel": "Target: 85%",
  "statusLabel": "Active",
  "status": "active",
  "quartersOverview": ["completed", "completed", "in_progress", "pending"],
  "lastUpdatedLabel": "Updated Nov 12",
  "targetValue": "85% Annual",
  "year": 2024,
  "breadcrumb": "Maternal Health Expansion › Health Sector",
  "parentCommitmentTitle": "Maternal Health Expansion",
  "progressPercent": 0.84,
  "heroEyebrow": "CURRENT PERFORMANCE",
  "heroValue": "84%",
  "heroSuffix": "submitted",
  "heroSubtext": "Q3 Pending Approval",
  "activeQuarter": "q3",
  "submissions": [
    {
      "quarter": "q3",
      "status": "pending",
      "title": "Q3 Submission",
      "milestone": "Phase 2 Integration",
      "actual": "84%",
      "date": "Sept 28, 2024",
      "remarks": "Lagos and Kano regional hubs successfully migrated to cloud-based EHR; awaiting final report from Delta state team.",
      "statusLabel": "PENDING SECTOR HEAD",
      "reviewCtaLabel": "Review Submission"
    },
    {
      "quarter": "q2",
      "status": "confirmed",
      "title": "Q2 Finalized",
      "milestone": "Database Setup",
      "actual": "82%",
      "statusLabel": "CONFIRMED"
    }
  ],
  "supportingDocuments": [
    { "id": "ehr-doc-pdf", "filename": "EHR_Report_Q3.pdf", "kind": "pdf" },
    { "id": "ehr-doc-image", "filename": "Clinic_Lagos_A1.jpg", "kind": "image" }
  ],
  "activeMilestoneValue": "85%",
  "activeTrackingDateLabel": "15 Sept 2024"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `deliverableId` | string | ✅ | parent deliverable id |
| `title` | string | ✅ | |
| `targetLabel` | string | ✅ | pre-formatted target label (e.g. `"Target: 85%"`) |
| `statusLabel` | string | ✅ | display label for `status` |
| `status` | string | ✅ | enum wire string — `active` / `stable` / `lagging` / `pending` |
| `quartersOverview` | string[] | ✅ | Always 4 entries; indices 0–3 → Q1–Q4. Each — `completed` / `in_progress` / `pending` (see Enums for the source conditions). |
| `lastUpdatedLabel` | string | ✅ | pre-formatted "updated" label |
| `unit` | string | ❌ | measurement unit (e.g. `"# of boreholes"`) |
| `targetValue` | string | ❌ | (e.g. `"85% Annual"`) |
| `year` | int | ❌ | |
| `breadcrumb` | string | ❌ | detail breadcrumb path |
| `sectorEyebrow` | string | ❌ | uppercase sector eyebrow (e.g. `"WATER & SANITATION"`) |
| `parentCommitmentTitle` | string | ❌ | |
| `progressPercent` | number | ❌ | fraction `0.0`–`1.0` |
| `heroEyebrow` | string | ❌ | |
| `heroValue` | string | ❌ | |
| `heroSuffix` | string | ❌ | |
| `heroSubtext` | string | ❌ | |
| `activeQuarter` | string | ❌ | `QuarterIndex` wire string — `q1`/`q2`/`q3`/`q4` |
| `submissions` | object[] | ❌ | list of submission objects — see table below |
| `supportingDocuments` | object[] | ❌ | list of document objects — see table below |
| `actualValueLabel` | string | ❌ | data-entry field label (e.g. `"Actual Value (Q1)"`) |
| `actualValueHint` | string | ❌ | data-entry field hint |
| `actualValueSuffix` | string | ❌ | data-entry field suffix (e.g. `"boreholes"`) |
| `activeMilestoneValue` | string | ❌ | pre-filled milestone value |
| `activeTrackingDateLabel` | string | ❌ | pre-filled tracking date label |

**`submissions[]` item fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `quarter` | string | ✅ | quarter token (e.g. `q1`…`q4`) |
| `status` | string | ✅ | submission state — `pending` / `confirmed` (see Notes) |
| `title` | string | ✅ | |
| `milestone` | string | ✅ | |
| `actual` | string | ✅ | reported actual value |
| `date` | string | ❌ | display date label |
| `remarks` | string | ❌ | |
| `statusLabel` | string | ❌ | display label for `status` |
| `reviewCtaLabel` | string | ❌ | review call-to-action label |

**`supportingDocuments[]` item fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `filename` | string | ✅ | |
| `kind` | string | ✅ | document kind — `pdf` / `image` / `word` (see Notes). Decoding is lenient: `jpg`/`jpeg`/`png`/`gif`/`webp` → image, `doc`/`docx`/`document` → word, anything else → pdf. |
| `sizeLabel` | string | ❌ | pre-formatted file-size label |
| `url` | string | ❌ | Absolute URL to the stored file. Required for **image** docs so the detail page can render a thumbnail and an enlarged tap-to-zoom preview; omit for pdf/word (rendered as a type icon). Should be directly fetchable by the client (signed/public GET). |

**Status codes:** `200` · `401` · `404`.

#### 11.4.3 Submit performance

| | |
| --- | --- |
| **Purpose** | Submit a quarter's actual performance value (with optional evidence + remarks) for review. |
| **Method / Path** | `POST /kpis/{id}/submissions` |
| **Auth** | Bearer required |
| **Path params** | `id` — KPI identifier (built from `params.kpiId`) |

**Request body**

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `quarter` | string | ✅ | `QuarterIndex` wire string — `q1`/`q2`/`q3`/`q4` |
| `actualValue` | string | ✅ | raw user input; the use case validates it parses to a non-negative number |
| `evidenceDocumentIds` | string[] | ✅ | ids of previously-uploaded evidence; sent always (defaults to `[]`) |
| `remarks` | string | ❌ | omitted when null |

**Request example**

```json
{
  "quarter": "q3",
  "actualValue": "84",
  "evidenceDocumentIds": ["ehr-doc-pdf"],
  "remarks": "Awaiting Delta state final report."
}
```

**Success:** any 2xx (body ignored; `202` queue response per design contract).

**Status codes:** `2xx`/`202` · `401` · `404` · `409` (state conflict, e.g. window closed / already submitted).

#### 11.4.4 Set milestone

| | |
| --- | --- |
| **Purpose** | Set the milestone target value for a KPI quarter. |
| **Method / Path** | `POST /kpis/{id}/milestones` |
| **Auth** | Bearer required |
| **Path params** | `id` — KPI identifier (built from `params.kpiId`) |

**Request body**

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `quarter` | string | ✅ | `QuarterIndex` wire string — `q1`/`q2`/`q3`/`q4` |
| `year` | int | ✅ | |
| `value` | string | ✅ | raw milestone value (e.g. `"85"`); the use case rejects non-numeric input |
| `trackingDate` | string | ❌ | ISO-8601 timestamp (`DateTime.toIso8601String()`); omitted when null |
| `remarks` | string | ❌ | omitted when null |

**Request example**

```json
{
  "quarter": "q3",
  "year": 2024,
  "value": "85",
  "trackingDate": "2024-09-15T00:00:00.000",
  "remarks": "Phase 2 integration target."
}
```

**Success:** any 2xx (body ignored; `202` queue response per design contract).

**Status codes:** `2xx`/`202` · `401` · `404` · `409`.

#### 11.4.5 Get milestone

| | |
| --- | --- |
| **Purpose** | Read the existing milestone value for a KPI + quarter + year, to pre-fill the "Set Milestone" sheet when it opens. |
| **Method / Path** | `GET /kpis/{id}/milestones?quarter={q1..q4}&year={year}` |
| **Auth** | Bearer required |
| **Path params** | `id` — KPI identifier |
| **Query params** | `quarter` — `QuarterIndex` wire string (`q1`–`q4`); `year` — int. Both always sent. |

**Success — `200 OK`** (raw object):

```json
{ "value": "85" }
```

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `value` | string \| null | ❌ | The saved milestone value as a string. `null` (or omitted) when no milestone has been set for that KPI/quarter/year. |

The client reads **only** `value` and treats a missing / `null` / non-string
value as "no milestone set yet" → blank field. Return **`200` with
`value: null`** for the not-set case — **not** `404`.

⚠ **Backend note.** This `GET` shares the path with the `POST` in
[§11.4.4](#1144-set-milestone). The backend currently registers **only** the
`POST` route there, so a read returns **`405 Method Not Allowed`**. Add the
`GET` handler on the same path (or expose it at a dedicated path and tell the
mobile team so we repoint the client).

**Status codes:** `200` · `401` · `404` (unknown KPI).

#### 11.4.6 Add tracking entry

| | |
| --- | --- |
| **Purpose** | Record an interim tracking data point ahead of the formal quarter submission. |
| **Method / Path** | `POST /kpis/{id}/tracking-entries` |
| **Auth** | Bearer required |
| **Path params** | `id` — KPI identifier (built from `params.kpiId`) |

**Request body**

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `quarter` | string | ✅ | `QuarterIndex` wire string — `q1`/`q2`/`q3`/`q4` |
| `year` | int | ✅ | |
| `trackingDate` | string | ✅ | ISO-8601 timestamp (`DateTime.toIso8601String()`); required for tracking entries |
| `actualValue` | string | ✅ | raw user input; validated by the use case (non-empty) |
| `evidenceDocumentIds` | string[] | ✅ | ids of previously-uploaded evidence; sent always (defaults to `[]`) |
| `remarks` | string | ❌ | omitted when null (max 1000 chars enforced client-side) |

**Request example**

```json
{
  "quarter": "q1",
  "year": 2024,
  "trackingDate": "2024-09-15T00:00:00.000",
  "actualValue": "62",
  "evidenceDocumentIds": [],
  "remarks": "Interim count after first deployment wave."
}
```

**Success:** any 2xx (body ignored; `202` queue response per design contract).

**Status codes:** `2xx`/`202` · `401` · `404` · `409`.

#### 11.4.7 Tracking-entry context (read)

| | |
| --- | --- |
| **Purpose** | The minimal slice the "Add Performance Tracking" sheet needs — a purpose-built read so the sheet doesn't pull the heavy [§11.4.2](#1142-get-kpi-detail) detail payload just to render a few labels. |
| **Method / Path** | `GET /kpis/{id}/tracking-context` |
| **Auth** | Bearer required |
| **Path params** | `id` — KPI identifier |

**Success — `200 OK`** (raw object):

```json
{
  "kpiId": "ehr-coverage",
  "kpiTitle": "Percentage of clinics with EHR",
  "commitmentLabel": "Maternal Health Expansion",
  "quarter": "q3",
  "year": 2024,
  "unit": "%",
  "currentMilestoneValue": "85%"
}
```

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `kpiId` | string | ✅ | |
| `kpiTitle` | string | ✅ | shown as the sheet's header subtitle |
| `commitmentLabel` | string | ✅ | parent commitment name — the first context chip |
| `quarter` | string | ✅ | `QuarterIndex` wire token (`q1`–`q4`); the quarter chip + the submit payload's `quarter` |
| `year` | int | ✅ | the year chip + the submit payload's `year` |
| `unit` | string | ❌ | suffix for the actual-value field (e.g. `%`, `boreholes`); omit when none |
| `currentMilestoneValue` | string | ❌ | milestone already set for this quarter (read-only readout); omit/null when none |

The client parses `kpiId`/`kpiTitle`/`commitmentLabel`/`quarter` as strings and
`year` as an int (strict); `unit`/`currentMilestoneValue` are optional. The
submit still POSTs to [§11.4.6](#1146-add-tracking-entry) using the `quarter`
and `year` from this payload.

**Status codes:** `200` · `401` · `404` (unknown KPI).

#### 11.4.8 Upload evidence

| | |
| --- | --- |
| **Purpose** | Upload one evidence file for a KPI; the returned document id is then submitted in [§11.4.6](#1146-add-tracking-entry)'s `evidenceDocumentIds`. |
| **Method / Path** | `POST /kpis/{id}/evidence` |
| **Auth** | Bearer required |
| **Content-Type** | `multipart/form-data` (Dio `FormData`; boundary set automatically — see §10) |
| **Path params** | `id` — KPI identifier |

**Form parts**

| Part | Req. | Notes |
| --- | --- | --- |
| `file` | ✅ | the picked file (`MultipartFile.fromFile`); the client sends the original filename. Images are downscaled/re-encoded on-device before upload. |

**Success — `200`/`201`** (raw object):

```json
{ "id": "doc-1716800000000" }
```

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | server document id; the **only** field the client reads. Include it in the tracking-entry submit's `evidenceDocumentIds`. |

The client uploads each file as it is picked (one request per file), shows a
per-attachment uploading / failed (retryable) state, and blocks the submit
until every upload settles. A non-string/missing `id` is a parse error.

**Status codes:** `200`/`201` · `400`/`422` (bad/oversized file) · `401` ·
`404` (unknown KPI).

#### 11.4.9 Annual targets (list + save)

Backs the "Set Annual Targets" sheet
(`ui-designs/kpi_tracking/#23 set_target_bottom_sheet.html`) — set the
annual benchmark for each KPI under a deliverable, for a fiscal year.

| | |
| --- | --- |
| **Purpose** | List the deliverable's KPIs (baseline + current target) and save edited targets in one batch. |
| **List** | `GET /deliverables/{deliverableId}/annual-targets?year={year}` |
| **Save** | `POST /deliverables/{deliverableId}/annual-targets` |
| **Auth** | Bearer required |
| **Path params** | `deliverableId` — deliverable identifier |
| **Query params** | `year` (int, list only) — fiscal year scope |

**List success — `200 OK`** (array of `AnnualTargetItemModel`):

```json
[
  {
    "kpiId": "kpi-classroom-blocks",
    "category": "Infrastructure & Works",
    "title": "Construction of new classroom blocks in prioritized LGAs",
    "baselineValue": "48",
    "baselineUnit": "blocks",
    "targetUnit": "blocks",
    "targetValue": "120"
  }
]
```

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `kpiId` | string | ✅ | |
| `category` | string | ✅ | grouping eyebrow — the **parent commitment's name** |
| `title` | string | ✅ | KPI description |
| `baselineValue` | string | ✅ | the KPI's **latest confirmed `actual_value`** ("where you stand today"), as a display-ready string (e.g. `"48"`, `"250k"`) |
| `baselineUnit` | string | ✅ | baseline unit suffix |
| `targetUnit` | string | ✅ | target input unit suffix |
| `targetValue` | string | ❌ | last-saved target; omitted when none is set |

**Save request body** — from `SaveAnnualTargetsParams`

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `year` | int | ✅ | 2000..2100 (client-enforced) |
| `targets` | object[] | ✅ | **only KPIs the user changed** are sent; omitted KPIs = no change. A cleared target is not sent (no clear endpoint yet). |
| `targets[].kpiId` | string | ✅ | |
| `targets[].value` | string | ✅ | raw input; client validates as a non-negative number (per-KPI errors keyed by `kpiId`) |

```json
{
  "year": 2024,
  "targets": [
    { "kpiId": "kpi-classroom-blocks", "value": "120" },
    { "kpiId": "kpi-textbooks", "value": "750" }
  ]
}
```

**Save success:** any 2xx (body ignored).

**Status codes:** `200` (list) · `2xx` (save) · `401` · `404` (unknown deliverable) · `422` (target validation).

**Enums in this section**

- `QuarterIndex` (request `quarter`; response `activeQuarter`) → `q1` · `q2` · `q3` · `q4`
- KPI `status` (response) → `active` · `stable` · `lagging` · `pending`
- KPI `quartersOverview[]` entry (response) — fixed 4-element array, indices 0–3 = Q1–Q4:
  - `pending` — no actual value submitted yet (no row, or `actual_value` null/empty)
  - `in_progress` — actual value submitted but not finalised; collapses Pending SH / Pending Facilitator / Pending Coordinator / Rejected into one bucket (`confirmation_status` ≠ `Confirmed`)
  - `completed` — coordinator finalised the quarter (`confirmation_status` = `Confirmed`)
  - (finer-grained badges would arrive as a sibling `quartersStatusDetail` field, not new enum values here)
- Submission `status` (response, nested) → `pending` · `confirmed`
- Supporting-doc `kind` (response, nested) → `pdf` · `image` · `word` (lenient decoding — see the `supportingDocuments[].kind` note above)


---

### 11.5 Frameworks

Module: `features/frameworks`. Data source: `frameworks_remote_data_source.dart`.
DTOs: `framework_model.dart`, `framework_sector_model.dart`,
`framework_stats_model.dart`. Request entity: `create_framework_draft.dart`.

Four read endpoints (list, stats, detail, per-framework sectors), one `POST`
create returning the new framework, and two `POST` command endpoints
(archive, set-default) that return no body the client uses.

#### 11.5.1 List frameworks

| | |
| --- | --- |
| **Purpose** | Fetch all performance frameworks for the frameworks list screen. |
| **Method / Path** | `GET /frameworks` |
| **Auth** | Bearer required |
| **Query params** | none |

**Success — `200 OK`** (raw array; each item is a `FrameworkModel`, list form
typically returns only the required summary block):

```json
[
  {
    "id": "fy-2023",
    "title": "FY 2023 Performance Framework",
    "subtitle": "Federal Executive Council Roadmap",
    "status": "archived",
    "statusLabel": "Archived",
    "sectorCountLabel": "16 Sectors",
    "dateLabel": "Jan 10, 2023",
    "reportingYear": 2023,
    "sectorCount": 16
  }
]
```

**Response fields** (per item — see [§11.5.3](#1153-get-framework-detail) table)

**Status codes:** `200` · `401`.

#### 11.5.2 Get framework stats

| | |
| --- | --- |
| **Purpose** | Fetch the aggregate counters shown in the frameworks list header. |
| **Method / Path** | `GET /frameworks/stats` |
| **Auth** | Bearer required |
| **Query params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "activeCount": 1,
  "archivedCount": 2,
  "latestUpdateLabel": "Latest Update",
  "latestUpdateValue": "FY 2024 Strategic Plan"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `activeCount` | int | ✅ | accepts any number; coerced to int |
| `archivedCount` | int | ✅ | accepts any number; coerced to int |
| `latestUpdateLabel` | string | ✅ | |
| `latestUpdateValue` | string | ✅ | |

**Status codes:** `200` · `401`.

#### 11.5.3 Get framework (detail)

| | |
| --- | --- |
| **Purpose** | Fetch one framework with totals, creator metadata and inheritance pointer for the framework detail screen. |
| **Method / Path** | `GET /frameworks/{id}` |
| **Auth** | Bearer required |
| **Path params** | `id` — framework identifier (e.g. `fy-2024`) |
| **Query params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "id": "fy-2024",
  "title": "FY 2024 Performance Framework",
  "subtitle": "Federal Executive Council Roadmap",
  "status": "active",
  "statusLabel": "Active",
  "sectorCountLabel": "18 Sectors",
  "dateLabel": "Jan 12, 2024",
  "description": "A comprehensive performance framework focused on infrastructure and healthcare delivery milestones for the 2025 fiscal year.",
  "reportingYear": 2024,
  "sectorCount": 18,
  "kpiCount": 245,
  "commitmentsCount": 47,
  "deliverablesCount": 158,
  "createdAt": "2024-01-12T00:00:00Z",
  "createdBy": "Ibrahim Gambo",
  "creatorInitials": "IG",
  "isDefault": true,
  "inheritedFromFrameworkId": "fy-2023",
  "inheritedFromTitle": "Inherited from FY 2024"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `title` | string | ✅ | |
| `subtitle` | string | ✅ | |
| `status` | string | ✅ | enum wire string — `active` / `archived` |
| `statusLabel` | string | ✅ | display label for `status` |
| `sectorCountLabel` | string | ✅ | pre-formatted (e.g. `"18 Sectors"`) |
| `dateLabel` | string | ✅ | pre-formatted date label |
| `description` | string | ❌ | detail-only |
| `reportingYear` | int | ❌ | |
| `sectorCount` | int | ❌ | |
| `kpiCount` | int | ❌ | detail-only |
| `commitmentsCount` | int | ❌ | detail-only |
| `deliverablesCount` | int | ❌ | detail-only |
| `createdAt` | string | ❌ | ISO-8601 timestamp |
| `createdBy` | string | ❌ | creator display name |
| `creatorInitials` | string | ❌ | |
| `isDefault` | bool | ❌ | defaults to `false`; only truthy when the value is the boolean `true` |
| `inheritedFromFrameworkId` | string | ❌ | source framework id when inherited |
| `inheritedFromTitle` | string | ❌ | denormalized source framework title |

**Status codes:** `200` · `401` · `404`.

#### 11.5.4 List sectors for a framework

| | |
| --- | --- |
| **Purpose** | Fetch the sectors configured under a framework. |
| **Method / Path** | `GET /frameworks/{id}/sectors` |
| **Auth** | Bearer required |
| **Path params** | `id` — framework identifier |
| **Query params** | none |

**Success — `200 OK`** (raw array):

```json
[
  {
    "id": "health",
    "frameworkId": "fy-2024",
    "name": "Health & Human Services",
    "meta": "5 Commitments • 12 Deliverables",
    "accent": "error"
  }
]
```

**Response fields** (per item)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `frameworkId` | string | ✅ | parent framework id |
| `name` | string | ✅ | |
| `meta` | string | ✅ | pre-formatted summary line |
| `accent` | string | ✅ | accent token — `error` / `secondary` / `tertiary` / `performance_fair` |

**Status codes:** `200` · `401` · `404`.

#### 11.5.5 Create framework

| | |
| --- | --- |
| **Purpose** | Create a new framework from the three-step create wizard draft. |
| **Method / Path** | `POST /frameworks` |
| **Auth** | Bearer required |
| **Query params** | none |

**Request body** (built from `CreateFrameworkDraft`)

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `name` | string | ✅ | sent always (draft `name`) |
| `sectorMethod` | string | ✅ | `SectorMethod` wire string — `blank` / `inherit` |
| `reportingYear` | int | ❌ | omitted when null |
| `description` | string | ❌ | omitted when empty string |
| `inheritedFromFrameworkId` | string | ❌ | omitted when null; expected when `sectorMethod` is `inherit` |

**Request example**

```json
{
  "name": "FY 2025 Performance Framework",
  "reportingYear": 2025,
  "description": "Carry forward the 2024 health and infrastructure milestones.",
  "sectorMethod": "inherit",
  "inheritedFromFrameworkId": "fy-2024"
}
```

**Success — `201 Created` / `200 OK`** (raw object): the newly created
`FrameworkModel` — same shape as [§11.5.3](#1153-get-framework-detail).

**Status codes:** `2xx`/`201` · `401` · `409` (e.g. duplicate name / reporting year).

#### 11.5.6 Archive framework

| | |
| --- | --- |
| **Purpose** | Archive a framework. |
| **Method / Path** | `POST /frameworks/{id}/archive` |
| **Auth** | Bearer required |
| **Path params** | `id` — framework identifier |
| **Request body** | none |

**Success:** any 2xx (body ignored; 204 preferred; `202` per design contract).

**Status codes:** `2xx`/`204` · `401` · `404` · `409` (already archived).

#### 11.5.7 Set framework as default

| | |
| --- | --- |
| **Purpose** | Mark a framework as the default/active one. |
| **Method / Path** | `POST /frameworks/{id}/set-default` |
| **Auth** | Bearer required |
| **Path params** | `id` — framework identifier |
| **Request body** | none |

**Success:** any 2xx (body ignored; 204 preferred; `202` per design contract).

**Status codes:** `2xx`/`204` · `401` · `404` · `409`.

**Enums in this section**

- Framework `status` (response) → `active` · `archived`
- `SectorMethod` (request `sectorMethod`) → `blank` · `inherit`
- Framework-sector `accent` (response) → `error` · `secondary` · `tertiary` · `performance_fair`


---

### 11.6 Approvals workflow

Module: `features/approvals`. Data source:
`approvals_remote_data_source.dart` (`ApprovalsRemoteDataSourceImpl`).

This is the four-role submission lifecycle: **Data Admin** enters a value →
**Sector Head** reviews → **Facilitator** verifies → **Coordinator** finally
approves (`confirmed`). A reject at any reviewer step returns the submission to
`rejected` so the Data Admin can adjust and resubmit. Each surface has its own
read endpoint; the two mutation endpoints carry an explicit `role` so the server
routes the correct state transition (the role is still cross-checked against the
auth token).

DTOs: `approval_queue_item_model.dart`, `approval_stat_model.dart`,
`bulk_approval_group_model.dart`, `facilitator_verification_group_model.dart`,
`my_kpi_summary_model.dart`, `submission_detail_model.dart`.
Request params: `review_submission_params.dart` (`ReviewSubmissionParams`,
`BulkApproveParams`).

#### 11.6.1 Coordinator review queue

| | |
| --- | --- |
| **Purpose** | List submissions awaiting final coordinator approval. |
| **Method / Path** | `GET /approvals/coordinator/queue` |
| **Auth** | Bearer required |

**Query parameters** — all optional except `sort` (always sent):

| Param | Req. | Notes |
| --- | --- | --- |
| `sector` | ❌ | Sector id; scopes the queue to a single sector. Omitted for "all sectors". |
| `year` | ❌ | Reporting year (int) — the active framework's year; scopes the queue to that cycle. |
| `quarter` | ❌ | Quarter wire token (`q1`–`q4`); scopes the queue to that quarter. Omitted for "all quarters". |
| `sort` | ✅ | Sort order — `newest` \| `oldest` (by submission/update time). Defaults to `newest`. |

**Example request**

```text
GET /approvals/coordinator/queue?sector=health&year=2024&quarter=q3&sort=newest
```

**Success — `200 OK`** (raw array of `ApprovalQueueItemModel`):

```json
[
  {
    "id": "coord-mmr",
    "kpiId": "kpi-mmr",
    "kpiTitle": "Maternal Mortality Ratio",
    "sectorLabel": "Health",
    "sectorAccent": "secondary",
    "state": "pending_coordinator",
    "submitterName": "Dr. Amina Yusuf",
    "updatedAgo": "Updated 2h ago",
    "metricLabel": "Current rate",
    "metricValue": "512 / 100k"
  },
  {
    "id": "coord-electrification",
    "kpiId": "kpi-electrification",
    "kpiTitle": "Rural Electrification",
    "sectorLabel": "Infrastructure",
    "sectorAccent": "primary",
    "state": "pending_coordinator",
    "submitterName": "Eng. Bashir Lawal",
    "updatedAgo": "Updated 1d ago",
    "metricLabel": "Households",
    "metricValue": "8,400",
    "metricValueColor": "secondary"
  }
]
```

**Response fields** — `ApprovalQueueItemModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | Submission key — the backend's `performance_trackings.id` rendered as a string. **The same value** is returned by the sector-head bulk candidates ([§11.6.3](#1163-sector-head-bulk-candidates)) and accepted by bulk-approve ([§11.6.8](#1168-bulk-approve-submissions)) for the same submission. Distinct from `kpiId`. |
| `kpiId` | string | ✅ | KPI key used for the detail call (distinct from the submission `id`). |
| `kpiTitle` | string | ✅ | |
| `sectorLabel` | string | ✅ | |
| `sectorAccent` | string | ✅ | Colour hint (`primary`/`secondary`/`tertiary`/`error`). |
| `state` | string | ✅ | Lifecycle state — see Enums. |
| `mda` | string | ❌ | Ministry/Department/Agency label. |
| `submitterName` | string | ❌ | |
| `updatedAgo` | string | ❌ | Pre-formatted relative time. |
| `quarter` | string | ❌ | Quarter wire token (`q1`–`q4`). |
| `metricLabel` | string | ❌ | |
| `metricValue` | string | ❌ | |
| `metricValueColor` | string | ❌ | Colour hint for the metric value. |
| `stats` | array&lt;ApprovalStat&gt; | ❌ | Defaults to `[]` if absent/empty. |
| `actualValue` | string | ❌ | |

**`ApprovalStat`** (object inside `stats[]`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `iconKey` | string | ✅ | Icon identifier (e.g. `trending_up`). |
| `label` | string | ✅ | |
| `accent` | string | ❌ | Colour hint. |

A row is rejected at parse time unless `id`, `kpiId`, `kpiTitle`,
`sectorLabel`, `sectorAccent`, `state` are all strings. Every optional string
field must be a string when present.

**Status codes:** `200` · `401`. See [§6](#6-error-handling-conventions).

#### 11.6.2 Sector-head review queue

| | |
| --- | --- |
| **Purpose** | List submissions awaiting sector-head review. |
| **Method / Path** | `GET /approvals/sector-head/queue` |
| **Auth** | Bearer required |
| **Query params** | `quarter` (optional) — quarter wire token; omitted when null. |

**Success — `200 OK`** (raw array of `ApprovalQueueItemModel`; same shape as
[11.6.1](#1161-coordinator-review-queue)). Sector-head rows typically carry
`mda` and a `stats[]` array rather than `submitterName`/`metricValue`:

```json
[
  {
    "id": "sh-mmr",
    "kpiId": "sh-kpi-mmr",
    "kpiTitle": "Maternal Mortality Ratio",
    "sectorLabel": "Primary Healthcare",
    "sectorAccent": "error",
    "state": "pending_sector_head",
    "mda": "State Ministry of Health",
    "stats": [
      { "iconKey": "trending_up", "label": "Target 350 / 100k" },
      { "iconKey": "check_circle", "label": "Actual 512 / 100k", "accent": "primary" }
    ]
  }
]
```

**Status codes:** `200` · `401`.

#### 11.6.3 Sector-head bulk candidates

| | |
| --- | --- |
| **Purpose** | Submissions a sector head can approve in bulk, grouped for batch action. |
| **Method / Path** | `GET /approvals/sector-head/bulk` |
| **Auth** | Bearer required |
| **Query params** | `grouping` (**required**) — `by_commitment` \| `by_deliverable`. |

**Success — `200 OK`** (raw **object** — header metadata + `groups`):

```json
{
  "quarterLabel": "2024 Q3",
  "sectorLabel": "Infrastructure & Transport",
  "groups": [
    {
      "title": "Commitment: Reduce Maternal Mortality",
      "items": [
        {
          "id": "bulk-1",
          "title": "Antenatal coverage",
          "value": "82%",
          "adminName": "F. Ibrahim"
        },
        {
          "id": "bulk-2",
          "title": "Skilled birth attendance",
          "value": "74%",
          "adminName": "F. Ibrahim"
        }
      ]
    }
  ]
}
```

⚠ **Backend note — response shape changed (object, not array).** This endpoint
now returns an **object** so the bulk page's header is fully data-driven (the
item count is derived client-side from `groups`, but the period and sector are
not). Populate `quarterLabel` (the active reporting period, e.g. `"2024 Q3"`)
and `sectorLabel` (the sector head's sector name, **without** a `Sector: `
prefix — the client adds it). The previous bare-array form is no longer parsed.

ℹ **Header quarter when reached from the queue.** When the bulk page is opened
from the sector-head queue's "Approve Selected", the client carries the queue's
selected quarter and **overrides the quarter portion of the header**, keeping
only the **year** from `quarterLabel` (e.g. response `"2024 Q3"` + selected `q1`
→ header `"2024 Q1"`). So `quarterLabel`'s quarter is only authoritative when the
page is opened standalone; its **year is always used**. Keep a 4-digit year in
`quarterLabel` so the override can extract it.

**Response fields** — top level

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `quarterLabel` | string | ✅ | Reporting-period label for the header pill (e.g. `2024 Q3`). |
| `sectorLabel` | string | ✅ | The sector head's sector name (e.g. `Health`); the client renders `Sector: {sectorLabel}`. |
| `groups` | array&lt;BulkApprovalGroup&gt; | ✅ | Grouped candidates (below); may be an empty array. |

**`BulkApprovalGroup`** (object inside `groups[]`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `title` | string | ✅ | Group heading (commitment or deliverable). |
| `items` | array&lt;BulkApprovalItem&gt; | ✅ | May be empty array. |

**`BulkApprovalItem`** (object inside `items[]`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | Submission id; feed these into [11.6.8](#1168-bulk-approve-submissions). |
| `title` | string | ✅ | |
| `value` | string | ✅ | |
| `adminName` | string | ✅ | Submitting Data Admin. |

A group fails to parse unless `title` is a string and `items` is a list; each
item requires all four string fields.

✅ **Id alignment with the review queue — confirmed.** The sector-head approval
queue ([§11.6.2](#1162-sector-head-review-queue)) lets the user tick rows and tap
**"Approve Selected"**, which opens the bulk page carrying the ticked
**`ApprovalQueueItem.id`** values. The client then pre-selects the
**intersection** of those ids with the `BulkApprovalItem.id`s returned here.
Backend confirms all three endpoints — `/sector-head/queue` (§11.6.2),
`/sector-head/bulk` (this endpoint), and `/submissions/bulk-approve`
([§11.6.8](#1168-bulk-approve-submissions)) — return/accept the **same
`performance_trackings.id`** for a given submission, so the carry-through
pre-selects correctly (the carried list is a strict subset of this endpoint's
items). The coordinator path shares the same guarantee.

**Status codes:** `200` · `401`.

#### 11.6.4 Facilitator verification queue

| | |
| --- | --- |
| **Purpose** | Submissions awaiting facilitator verification, grouped for the verification page. |
| **Method / Path** | `GET /approvals/facilitator/queue` |
| **Auth** | Bearer required |

**Query parameters**

| Param | Req. | Notes |
| --- | --- | --- |
| `grouping` | ✅ | `by_sector` \| `by_kpi` — how rows are bucketed into groups. |
| `quarter` | ❌ | Quarter wire token (`q1`–`q4`). Scopes the queue to that quarter; the client always sends the selected quarter chip. |
| `sector` | ❌ | Sector id. Scopes the queue to a single sector; omitted for "all sectors". The client also omits this filter entirely (and never sends `sector`) when the facilitator is assigned only one sector. |

The facilitator's assigned sectors are resolved server-side from the auth token;
`sector` narrows within those. A scoped request returns only the matching
groups, each carrying only its in-scope items; a group left with no items is
omitted.

**Example request**

```text
GET /approvals/facilitator/queue?grouping=by_sector&quarter=q3&sector=agriculture
```

**Success — `200 OK`** (raw array of `FacilitatorVerificationGroupModel`):

```json
[
  {
    "id": "agriculture",
    "title": "Agriculture",
    "accent": "primary",
    "items": [
      {
        "id": "fac-tractor",
        "kpiId": "fac-kpi-tractor",
        "kpiTitle": "Tractors Distributed",
        "sectorLabel": "Agriculture",
        "sectorAccent": "primary",
        "state": "pending_facilitator",
        "quarter": "q3",
        "actualValue": "120 units"
      }
    ]
  }
]
```

**Response fields** — `FacilitatorVerificationGroupModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | Group id (sector or kpi key). |
| `title` | string | ✅ | |
| `accent` | string | ✅ | Colour hint. |
| `items` | array&lt;ApprovalQueueItem&gt; | ✅ | Same shape as [11.6.1](#1161-coordinator-review-queue); a list (may be empty). |

A group fails to parse unless `id`, `title`, `accent` are strings and `items`
is a list.

**Status codes:** `200` · `401`.

#### 11.6.5 Data Admin "My KPIs"

| | |
| --- | --- |
| **Purpose** | The Data Admin's own KPIs with per-quarter status, for the entry dashboard. |
| **Method / Path** | `GET /approvals/data-admin/my-kpis` |
| **Auth** | Bearer required |
| **Query params** | `quarter` (optional, omitted when null); `filter` (**always sent**, default `all`); `year` (optional int, omitted when null). |

`filter` is always present on the wire (defaults to `all`); `quarter` and
`year` are only included when supplied.

**Success — `200 OK`** (raw array of `MyKpiSummaryModel`):

```json
[
  {
    "id": "my-1",
    "kpiId": "da-kpi-maternal",
    "title": "Maternal Mortality Ratio",
    "categoryLabel": "Health Outcomes",
    "targetLabel": "Target: 350 / 100k",
    "lastUpdateLabel": "Updated 3d ago",
    "quarterStates": ["confirmed", "confirmed", "pending_sector_head", "pending_entry"],
    "overallState": "pending_sector_head"
  },
  {
    "id": "my-3",
    "kpiId": "da-kpi-tb",
    "title": "TB Case Detection",
    "categoryLabel": "Disease Control",
    "targetLabel": "Target: 85%",
    "lastUpdateLabel": "Action required",
    "quarterStates": ["confirmed", "rejected", "pending_entry", "pending_entry"],
    "overallState": "rejected",
    "lastUpdateIsError": true
  }
]
```

**Response fields** — `MyKpiSummaryModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `kpiId` | string | ✅ | |
| `title` | string | ✅ | |
| `categoryLabel` | string | ✅ | |
| `targetLabel` | string | ✅ | |
| `lastUpdateLabel` | string | ✅ | |
| `quarterStates` | array&lt;string&gt; | ✅ | One lifecycle state per quarter; every entry must be a string. |
| `overallState` | string | ✅ | Lifecycle state — see Enums. |
| `lastUpdateIsError` | bool | ❌ | Defaults to `false`; only `true` is read (non-bool treated as `false`). |

**Status codes:** `200` · `401`.

#### 11.6.6 Submission detail

| | |
| --- | --- |
| **Purpose** | Full detail of a single submission for the review sheet. |
| **Method / Path** | `GET /approvals/submissions/{kpiId}` |
| **Auth** | Bearer required |
| **Path params** | `kpiId` — the submission/KPI key. |

**Success — `200 OK`** (raw object `SubmissionDetailModel`):

```json
{
  "id": "submission-edu-q3",
  "kpiId": "kpi-mmr",
  "kpiTitle": "Maternal Mortality Ratio",
  "sectorLabel": "Health",
  "quarter": "q3",
  "state": "pending_coordinator",
  "trackingDateLabel": "12 Sept 2024",
  "milestoneValue": "Milestone 3 of 4",
  "actualValue": "512 / 100k",
  "targetValue": "350 / 100k",
  "remarks": "Awaiting verification of field survey data.",
  "attachments": [
    { "name": "field_survey_q3.pdf", "url": "https://cdn.example.com/files/field_survey_q3.pdf" },
    { "name": "site_photo.jpg", "url": "https://cdn.example.com/files/site_photo.jpg" }
  ],
  "deliveryDateLabel": "Oct 12, 2024",
  "deliveryValue": "41.0%",
  "deliveryRemarks": "Cross-checked against field reports; minor variance accepted.",
  "deliveryAttachments": [
    { "name": "verification_report.pdf", "url": "https://cdn.example.com/files/verification_report.pdf" }
  ]
}
```

**Response fields** — `SubmissionDetailModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `kpiId` | string | ✅ | |
| `kpiTitle` | string | ✅ | |
| `sectorLabel` | string | ✅ | |
| `quarter` | string | ✅ | Quarter wire token (`q1`–`q4`). |
| `state` | string | ✅ | Lifecycle state — see Enums. |
| `trackingDateLabel` | string | ✅ | Pre-formatted date string. |
| `milestoneValue` | string | ✅ | |
| `actualValue` | string | ✅ | |
| `targetValue` | string | ✅ | |
| `remarks` | string | ❌ | |
| `attachments` | array&lt;object&gt; | ❌ | Defaults to `[]`. Each entry is `{ "name": string, "url"?: string }` (see **Attachment object** below). A bare string is still accepted for backward compatibility and treated as `name` with no `url`. |
| `deliveryDateLabel` | string | ❌ | Prior-stage delivery-department verification date. |
| `deliveryValue` | string | ❌ | The delivery department's verified value. |
| `deliveryRemarks` | string | ❌ | The delivery department's verification remark. |
| `deliveryAttachments` | array&lt;object&gt; | ❌ | Verification evidence; same **Attachment object** shape as `attachments`. Defaults to `[]`. |

**Attachment object**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `name` | string | ✅ | File name shown on the row (extension drives the image/PDF icon). |
| `url` | string | ❌ | Absolute URL to the stored file. Required for **image** files (`.jpg/.jpeg/.png/.gif/.webp`) so the review sheet can render a thumbnail and an enlarged tap-to-zoom preview; omit (or null) for non-previewable types. Should be directly fetchable by the client (signed/public GET). |

The response must be a JSON object (a non-object body raises a parse error).

The `delivery*` fields carry the prior **delivery-department verification**
(the value/remark/date/attachments captured at the facilitator stage). The
client surfaces them **only on the coordinator's review**, and only those
present — populate them for submissions at `pending_coordinator`.

**Status codes:** `200` · `401` · `404` (unknown `kpiId`).

#### 11.6.7 Review a submission

| | |
| --- | --- |
| **Purpose** | Reviewer accepts or rejects a submission, advancing or returning the lifecycle. |
| **Method / Path** | `POST /approvals/submissions/{submissionId}/review` |
| **Auth** | Bearer required |
| **Path params** | `submissionId` — the submission key. |

**Request body** — from `ReviewSubmissionParams`

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `role` | string | ✅ | Reviewer role wire token — see Enums. |
| `decision` | string | ✅ | `accept` \| `reject`. |
| `validatedValue` | string | ❌ | Omitted when null. Reviewer's numeric override of the actual value (only meaningful on accept). |
| `acceptRemarks` | string | ❌ | Omitted when null. |
| `rejectionReason` | string | ❌ | Omitted when null. Required (non-empty) by the client use-case when `decision == reject`. |

On accept the server transitions to the next lifecycle state
(sector-head → `pending_facilitator`; facilitator → `pending_coordinator`;
coordinator → `confirmed`); on reject it moves to `rejected`.

```json
{
  "role": "coordinator",
  "decision": "accept",
  "validatedValue": "512 / 100k",
  "acceptRemarks": "Verified against field survey."
}
```

**Success:** any 2xx (body ignored; the contract notes `202 Accepted`).

**Status codes:** `2xx` · `401` · `404` (unknown `submissionId`) ·
`409` (submission already decided / not in a reviewable state).

#### 11.6.8 Bulk-approve submissions

| | |
| --- | --- |
| **Purpose** | A reviewer approves multiple submissions in one call. Used by the **sector head** (from the bulk candidates) and the **coordinator** (final review queue — accepting in bulk, finalizing each to `confirmed`). |
| **Method / Path** | `POST /approvals/submissions/bulk-approve` |
| **Auth** | Bearer required |
| **Params** | none |

**Request body** — from `BulkApproveParams`

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `submissionIds` | array&lt;string&gt; | ✅ | Submission ids to approve — the `BulkApprovalItem.id` values from [11.6.3](#1163-sector-head-bulk-candidates) (sector head) or the row `id` values from the coordinator queue [11.6.1](#1161-coordinator-review-queue) (coordinator). These are the **same `performance_trackings.id`** the queues return, so the client carries a queue selection straight into the bulk action — see the (confirmed) id-alignment note in [§11.6.3](#1163-sector-head-bulk-candidates). |
| `role` | string | ✅ | Reviewer role wire token — `sector_head` \| `coordinator` (see Enums). The server applies that role's accept transition to **every** listed submission (sector head → `pending_facilitator`; **coordinator → `confirmed`**), so the role must match the submissions' current stage. |

A coordinator finalizing a multi-select from the review queue:

```json
{
  "submissionIds": ["coord-mmr", "coord-electrification"],
  "role": "coordinator"
}
```

**Success:** any 2xx (body ignored; the contract notes `202 Accepted`).

**Status codes:** `2xx` · `401` · `403` (role not permitted for these
submissions) · `409` (one or more submissions already decided / not
approvable for this role's stage).

---

**Enums in this section**

- **Submission lifecycle state** (`state`, `overallState`, `quarterStates[]`) — observed wire values: `pending_entry`, `pending_sector_head`, `pending_facilitator`, `pending_coordinator`, `confirmed`, `rejected`.
- **Reviewer role** (`role`) — `sector_head`, `facilitator`, `coordinator`.
- **Review decision** (`decision`) — `accept`, `reject`.
- **Bulk grouping** (`grouping`, [11.6.3](#1163-sector-head-bulk-candidates)) — `by_commitment`, `by_deliverable`.
- **Verification grouping** (`grouping`, [11.6.4](#1164-facilitator-verification-queue)) — `by_sector`, `by_kpi`.
- **My-KPIs filter** (`filter`, [11.6.5](#1165-data-admin-my-kpis)) — `all`, `pending_entry`, `pending_sector_head`, `pending_facilitator`, `pending_coordinator`, `confirmed`, `rejected`. Every non-`all` value equals a submission-lifecycle `overallState` token, so the server filters on `overall_state == filter`.
- **Quarter** (`quarter`) — `q1`, `q2`, `q3`, `q4`.


---

### 11.7 Data-entry windows

Module: `features/data_entry`. Data source:
`data_entry_windows_remote_data_source.dart`
(`DataEntryWindowsRemoteDataSourceImpl`).

The coordinator controls per-sector data-entry windows (open / locked) for a
given quarter, plus blanket lock/unlock and a per-sector override grant. Reads
return the window list and an aggregate stats card; the rest are command
endpoints that mutate server-side window state.

DTOs: `data_entry_window_model.dart`, `data_entry_stats_model.dart`.
Request params: `grant_override_params.dart` (`GrantOverrideParams`).

#### 11.7.1 List data-entry windows

| | |
| --- | --- |
| **Purpose** | Per-sector data-entry window list for the management page. |
| **Method / Path** | `GET /data-entry/windows` |
| **Auth** | Bearer required |
| **Query params** | `year` (optional int, omitted when null); `quarter` (optional, quarter wire token, omitted when null). |

**Success — `200 OK`** (raw array of `DataEntryWindowModel`):

```json
[
  {
    "sectorId": "health",
    "sectorName": "Health",
    "accent": "primary",
    "status": "open",
    "lastUpdatedLabel": "Updated 2h ago",
    "quarterLabel": "Q3 2024",
    "deadlineLabel": "Due 30 Sept"
  },
  {
    "sectorId": "education",
    "sectorName": "Education",
    "accent": "secondary",
    "status": "locked",
    "lastUpdatedLabel": "Updated 1d ago",
    "quarterLabel": "Q3 2024",
    "deadlineLabel": "Due 30 Sept"
  }
]
```

**Response fields** — `DataEntryWindowModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `sectorId` | string | ✅ | Path key for the open/lock/override calls. |
| `sectorName` | string | ✅ | |
| `accent` | string | ✅ | Colour hint (`primary`/`secondary`/`tertiary`/`error`). |
| `status` | string | ✅ | Window status — see Enums. |
| `lastUpdatedLabel` | string | ✅ | Pre-formatted relative time. |
| `quarterLabel` | string | ✅ | Display label (e.g. `Q3 2024`). |
| `deadlineLabel` | string | ✅ | Pre-formatted deadline. |

Every field is required: a row fails to parse unless all seven are strings.
The response body must be a JSON array.

**Status codes:** `200` · `401`. See [§6](#6-error-handling-conventions).

#### 11.7.2 Data-entry stats

| | |
| --- | --- |
| **Purpose** | Aggregate stats card (sector counts + submission rate). |
| **Method / Path** | `GET /data-entry/stats` |
| **Auth** | Bearer required |
| **Query params** | `year` (optional int, omitted when null); `quarter` (optional, quarter wire token, omitted when null). |

**Success — `200 OK`** (raw object `DataEntryStatsModel`):

```json
{
  "totalSectors": 18,
  "openSectors": 12,
  "submissionRateLabel": "68%"
}
```

**Response fields** — `DataEntryStatsModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `totalSectors` | int | ✅ | Sent as a number; coerced via `toInt()`. |
| `openSectors` | int | ✅ | Sent as a number; coerced via `toInt()`. |
| `submissionRateLabel` | string | ✅ | Pre-formatted percentage label. |

`totalSectors`/`openSectors` must be numeric and `submissionRateLabel` a string,
or the response fails to parse. The body must be a JSON object.

**Status codes:** `200` · `401`.

#### 11.7.3 Lock all windows

| | |
| --- | --- |
| **Purpose** | Lock every sector's data-entry window. |
| **Method / Path** | `POST /data-entry/windows/lock-all` |
| **Auth** | Bearer required |
| **Params** | none |

No request body is sent.

**Success:** any 2xx (body ignored; the contract notes `202 Accepted`).

**Status codes:** `2xx` · `401`.

#### 11.7.4 Unlock all windows

| | |
| --- | --- |
| **Purpose** | Unlock (open) every sector's data-entry window. |
| **Method / Path** | `POST /data-entry/windows/unlock-all` |
| **Auth** | Bearer required |
| **Params** | none |

No request body is sent.

**Success:** any 2xx (body ignored; the contract notes `202 Accepted`).

**Status codes:** `2xx` · `401`.

#### 11.7.5 Open a sector window

| | |
| --- | --- |
| **Purpose** | Open the data-entry window for one sector. |
| **Method / Path** | `POST /data-entry/windows/{sectorId}/open` |
| **Auth** | Bearer required |
| **Path params** | `sectorId` — the sector key (`DataEntryWindowModel.sectorId`). |

No request body is sent.

**Success:** any 2xx (body ignored; the contract notes `202 Accepted`).

**Status codes:** `2xx` · `401` · `404` (unknown `sectorId`) ·
`409` (already open).

#### 11.7.6 Lock a sector window

| | |
| --- | --- |
| **Purpose** | Lock the data-entry window for one sector. |
| **Method / Path** | `POST /data-entry/windows/{sectorId}/lock` |
| **Auth** | Bearer required |
| **Path params** | `sectorId` — the sector key. |

No request body is sent.

**Success:** any 2xx (body ignored; the contract notes `202 Accepted`).

**Status codes:** `2xx` · `401` · `404` (unknown `sectorId`) ·
`409` (already locked).

#### 11.7.7 Grant a sector override

| | |
| --- | --- |
| **Purpose** | Grant a temporary entry override for a sector whose window is locked. |
| **Method / Path** | `POST /data-entry/windows/{sectorId}/override` |
| **Auth** | Bearer required |
| **Path params** | `sectorId` — the sector key. |

**Request body** — from `GrantOverrideParams`

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `reason` | string | ✅ | Justification; the client use-case enforces non-empty. |
| `expiresAt` | string | ❌ | ISO-8601 datetime (`DateTime.toIso8601String()`); omitted when null. Null means "until coordinator revokes". |

```json
{
  "reason": "Late submission approved by coordinator.",
  "expiresAt": "2024-09-30T23:59:59.000"
}
```

**Success:** any 2xx (body ignored; the contract notes `202 Accepted`).

**Status codes:** `2xx` · `401` · `404` (unknown `sectorId`) ·
`409` (window not in a state that accepts an override).

---

**Enums in this section**

- **Window status** (`status`, [11.7.1](#1171-list-data-entry-windows)) — observed wire values: `open`, `locked`.
- **Quarter** (`quarter`) — `q1`, `q2`, `q3`, `q4`.


---

### 11.8 Reports

Module: `features/reports`. Data source:
`reports_remote_data_source.dart` (`ReportsRemoteDataSourceImpl`).

Covers the reports hub (performance summary), the comprehensive-report setup
preview and viewer, comprehensive/Word report generation, and the print-preview
document. The setup-preview, viewer and comprehensive endpoints share a common
JSON body built from `ReportSetupParams` (referred to below as the **setup
body**). Generation endpoints return a `GeneratedReportModel` describing the
produced file (id, format, size label and a `downloadUrl`) — **not** raw bytes;
the client fetches the file separately from `downloadUrl`.

DTOs: `reports_hub_data_model.dart` (`ReportsHubDataModel`, `ReportsHubBarModel`,
`ReportsStatusMixModel`), `report_setup_preview_model.dart`,
`reports_viewer_content_model.dart` (`ReportsViewerContentModel`,
`ReportsViewerSectorGroupModel`, `ReportsViewerKpiRowModel`),
`generated_report_model.dart`, `print_preview_document_model.dart`.
Request entities: `report_filter.dart` (`ReportFilter`, `ReportSetupParams`),
`word_report_draft.dart` (`WordReportDraft`), `report_format.dart`
(`ReportFormat`).

**Setup body** (built from `ReportSetupParams`; reused by
[11.8.2](#1182-report-setup-preview), [11.8.3](#1183-report-viewer-content) and
[11.8.4](#1184-generate-comprehensive-report)):

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `sectorIds` | array&lt;string&gt; | ✅ | Selected sectors; always sent (may be `[]`). Serialized from a Set. |
| `year` | int | ❌ | Omitted when null. |
| `quarter` | string | ❌ | Quarter wire token (`q1`–`q4`); omitted when null. |
| `includeEvidence` | bool | ✅ | Always sent; defaults to `true`. |

#### 11.8.1 Reports hub data

| | |
| --- | --- |
| **Purpose** | Performance summary for the reports hub (scorecards + sector bars + status mix). |
| **Method / Path** | `GET /reports/hub` |
| **Auth** | Bearer required |
| **Query params** | `sectorId` (optional, omitted when null — null means "All Sectors"); `quarter` (optional quarter wire token, omitted when null); `year` (optional int, omitted when null). Built from `ReportFilter`. |

**Success — `200 OK`** (raw object `ReportsHubDataModel`):

```json
{
  "avgPerformanceFraction": 0.78,
  "avgPerformanceLabel": "78%",
  "topSectorLabel": "Healthcare",
  "pendingCount": 12,
  "pendingCaption": "Pending review",
  "sectorBars": [
    { "label": "Healthcare", "short": "HLT", "fraction": 0.85, "valueLabel": "85", "accent": "#2E7D32" },
    { "label": "Education", "short": "EDU", "fraction": 0.72, "valueLabel": "72", "accent": "#1565C0" },
    { "label": "Agriculture", "short": "AGR", "fraction": 0.64, "valueLabel": "64", "accent": "#F9A825" },
    { "label": "Public Works", "short": "", "fraction": 0.48, "valueLabel": "48", "accent": "#C62828" }
  ],
  "statusMix": {
    "achievedFraction": 0.6,
    "onTrackFraction": 0.25,
    "criticalFraction": 0.15,
    "totalKpiCount": 124,
    "achievedPctLabel": "60%",
    "onTrackPctLabel": "25%",
    "criticalPctLabel": "15%"
  }
}
```

**Response fields** — `ReportsHubDataModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `avgPerformanceFraction` | number | ✅ | 0–1 fraction (sent as number, read as double). |
| `avgPerformanceLabel` | string | ✅ | |
| `topSectorLabel` | string | ✅ | Full sector name. The top-sector scorecard shows a compact form, sourced client-side from the matching `sectorBars[]` entry's `short` (no dedicated wire field). |
| `pendingCount` | int | ✅ | Number, coerced via `toInt()`. |
| `pendingCaption` | string | ✅ | |
| `sectorBars` | array&lt;ReportsHubBar&gt; | ✅ | Must be a list. |
| `statusMix` | ReportsStatusMix | ✅ | Must be an object. |

**`ReportsHubBar`** (object inside `sectorBars[]`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `label` | string | ✅ | Full sector name. |
| `short` | string | ✅ | Compact sector label (`sector.description`, e.g. `"HLT"`) shown in the comparison-row column. Always present; may be `""` when the sector has no description — the client then derives one (first word of `label`). |
| `fraction` | number | ✅ | 0–1 fraction (double). |
| `valueLabel` | string | ✅ | |
| `accent` | string | ✅ | Colour hint — may be a token (`primary`…) or a hex string (`#2E7D32`). The hub bars use a primary opacity ladder regardless, so non-token values degrade to `primary`. |

**`ReportsStatusMix`** (the `statusMix` object)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `achievedFraction` | number | ✅ | double. |
| `onTrackFraction` | number | ✅ | double. |
| `criticalFraction` | number | ✅ | double. |
| `totalKpiCount` | int | ✅ | Number, coerced via `toInt()`. |
| `achievedPctLabel` | string | ✅ | |
| `onTrackPctLabel` | string | ✅ | |
| `criticalPctLabel` | string | ✅ | |

**Status codes:** `200` · `401`. See [§6](#6-error-handling-conventions).

#### 11.8.2 Report setup preview

| | |
| --- | --- |
| **Purpose** | Counts + estimated file size for the comprehensive-report setup form. |
| **Method / Path** | `POST /reports/setup-preview` |
| **Auth** | Bearer required |
| **Params** | none (selection passed in body). |

**Request body:** the [setup body](#118-reports).

```json
{
  "sectorIds": ["health", "education"],
  "year": 2024,
  "quarter": "q3",
  "includeEvidence": true
}
```

**Success — `200 OK`** (raw object `ReportSetupPreviewModel`):

```json
{
  "commitmentsCount": 12,
  "deliverablesCount": 47,
  "kpisCount": 132,
  "fileSizeLabel": "4.2 MB"
}
```

**Response fields** — `ReportSetupPreviewModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `commitmentsCount` | int | ✅ | Number, coerced via `toInt()`. |
| `deliverablesCount` | int | ✅ | Number, coerced via `toInt()`. |
| `kpisCount` | int | ✅ | Number, coerced via `toInt()`. |
| `fileSizeLabel` | string | ✅ | Pre-formatted size estimate. |

**Status codes:** `200` · `401`.

#### 11.8.3 Report viewer content

| | |
| --- | --- |
| **Purpose** | Full rendered report content (sector groups + KPI rows) for the in-app viewer. |
| **Method / Path** | `POST /reports/viewer` |
| **Auth** | Bearer required |
| **Params** | none (selection passed in body). |

**Request body:** the [setup body](#118-reports) (same shape as
[11.8.2](#1182-report-setup-preview)).

**Success — `200 OK`** (raw object `ReportsViewerContentModel`):

```json
{
  "title": "Q3 2024 Performance Report",
  "subtitle": "Infrastructure & Health",
  "groups": [
    {
      "id": "infra",
      "label": "Infrastructure",
      "accent": "primary",
      "kpiRows": [
        {
          "index": "1.1",
          "title": "Rural Electrification",
          "body": "Households connected to grid.",
          "targetLabel": "Target: 10,000",
          "currentLabel": "8,400",
          "currentAccent": "secondary",
          "percentFraction": 0.85,
          "percentLabel": "85%",
          "percentAccent": "primary",
          "trendPoints": [0.18, 0.30, 0.45, 0.55, 0.72, 0.92, 0.82, 0.92, 0.72, 0.18, 0.18, 0.18],
          "trendStartLabel": "Jan",
          "trendEndLabel": "Dec",
          "perfLabel": "Adjusted performance",
          "perfIconKey": "analytics",
          "perfAccent": "primary",
          "evidenceLabel": "3 attachments",
          "notes": "On track for year-end target."
        }
      ]
    }
  ]
}
```

**Response fields** — `ReportsViewerContentModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `title` | string | ✅ | |
| `subtitle` | string | ✅ | |
| `groups` | array&lt;ReportsViewerSectorGroup&gt; | ✅ | Must be a list. |

**`ReportsViewerSectorGroup`** (object inside `groups[]`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `label` | string | ✅ | |
| `accent` | string | ✅ | Colour hint. |
| `kpiRows` | array&lt;ReportsViewerKpiRow&gt; | ✅ | Must be a list. |

**`ReportsViewerKpiRow`** (object inside `kpiRows[]`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `index` | string | ✅ | Outline index (e.g. `1.1`). |
| `title` | string | ✅ | |
| `body` | string | ✅ | |
| `targetLabel` | string | ✅ | |
| `currentLabel` | string | ✅ | |
| `currentAccent` | string | ✅ | Colour hint. |
| `percentFraction` | number | ✅ | 0–1 fraction (double). |
| `percentLabel` | string | ✅ | |
| `percentAccent` | string | ✅ | Colour hint. |
| `trendPoints` | array&lt;number&gt; | ❌ | Defaults to `[]`; each entry must be a number (read as double). |
| `trendStartLabel` | string | ❌ | |
| `trendEndLabel` | string | ❌ | |
| `perfLabel` | string | ❌ | |
| `perfIconKey` | string | ❌ | Icon identifier. |
| `perfAccent` | string | ❌ | Colour hint. |
| `evidenceLabel` | string | ❌ | |
| `notes` | string | ❌ | |

**Status codes:** `200` · `401`.

#### 11.8.4 Generate comprehensive report

| | |
| --- | --- |
| **Purpose** | Generate a comprehensive report file in the requested format. |
| **Method / Path** | `POST /reports/comprehensive` |
| **Auth** | Bearer required |
| **Params** | none (selection + format in body). |

**Request body:** the [setup body](#118-reports) plus a `format` field.

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `sectorIds` | array&lt;string&gt; | ✅ | See setup body. |
| `year` | int | ❌ | Omitted when null. |
| `quarter` | string | ❌ | Quarter wire token; omitted when null. |
| `includeEvidence` | bool | ✅ | See setup body. |
| `format` | string | ✅ | Output format wire token — see Enums. |

```json
{
  "sectorIds": ["health", "education"],
  "year": 2024,
  "quarter": "q3",
  "includeEvidence": true,
  "format": "pdf"
}
```

**Success — `200 OK`** (raw object `GeneratedReportModel`):

```json
{
  "id": "comp-1716800000000",
  "format": "pdf",
  "fileSizeLabel": "4.2 MB",
  "downloadUrl": "https://api.pdcu.gov.ng/files/reports/comp-1716800000000.pdf"
}
```

**Response fields** — `GeneratedReportModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | Generated report id. |
| `format` | string | ✅ | Output format wire token — see Enums. |
| `fileSizeLabel` | string | ✅ | Pre-formatted size. |
| `downloadUrl` | string | ✅ | URL the client fetches the file from (no bytes inline). |

**Status codes:** `200` · `401`.

#### 11.8.5 Generate Word report

| | |
| --- | --- |
| **Purpose** | Generate a Word document from the word-report wizard draft. |
| **Method / Path** | `POST /reports/word` |
| **Auth** | Bearer required |
| **Params** | none (draft in body). |

**Request body** — from `WordReportDraft`

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `sectorId` | string | ❌ | Omitted when null (single-sector scope). |
| `year` | int | ❌ | Omitted when null. |
| `quarter` | string | ❌ | Quarter wire token; omitted when null. |
| `title` | string | ✅ | Always sent; defaults to `""`. |
| `author` | string | ✅ | Always sent; defaults to `""`. |
| `dateLabel` | string | ✅ | Always sent; display-ready date (e.g. `September 2024`); defaults to `""`. |

```json
{
  "sectorId": "health",
  "year": 2024,
  "quarter": "q3",
  "title": "Health Sector Q3 Review",
  "author": "Dr. Amina Yusuf",
  "dateLabel": "September 2024"
}
```

**Success — `200 OK`** (raw object `GeneratedReportModel`; same shape as
[11.8.4](#1184-generate-comprehensive-report), with `format` typically `word`):

```json
{
  "id": "word-7f3a",
  "format": "word",
  "fileSizeLabel": "2.4 MB",
  "downloadUrl": "https://api.pdcu.gov.ng/files/reports/word-7f3a.docx"
}
```

**Status codes:** `200` · `401`.

#### 11.8.6 Print-preview document

| | |
| --- | --- |
| **Purpose** | Document metadata for the print-preview screen (page count + doc number). |
| **Method / Path** | `GET /reports/print-preview` |
| **Auth** | Bearer required |
| **Params** | none |

**Success — `200 OK`** (raw object `PrintPreviewDocumentModel`):

```json
{
  "pageCount": 12,
  "docNoLabel": "Document No.",
  "docNoValue": "PDCU/RPT/2024/0312"
}
```

**Response fields** — `PrintPreviewDocumentModel`

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `pageCount` | int | ✅ | Number, coerced via `toInt()`. |
| `docNoLabel` | string | ✅ | |
| `docNoValue` | string | ✅ | |

**Status codes:** `200` · `401`.

---

#### 11.8.7 Generate comprehensive Excel / PDF report

| | |
| --- | --- |
| **Purpose** | Mobile equivalent of the web's "Download Excel" / "Print → Save as PDF" buttons on `/reports/comprehensive`. Generates the **same** comprehensive multi-sheet workbook (Overall Summary, Grand Summary, Sector Summary Details, one sheet per sector) as Excel, **or** the same printable view as PDF — driven by `type`. Returns a `downloadUrl` to fetch the artifact. |
| **Method / Path** | `POST /reports/comprehensive-report` |
| **Auth** | Bearer required |
| **Body — `ComprehensiveReportRequest`** | |

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `sectors` | int[] | optional | Sector ids to include. Empty/omitted ⇒ every sector in the framework. Ignored for Sector Head / Data Admin (they're always pinned to their own sector). |
| `year` | int | ✅ | 4-digit year (matches a `frameworks.year`). |
| `start_quarter` | int | ✅ | 1..4. |
| `end_quarter` | int | ✅ | 1..4, must be `>= start_quarter`. |
| `type` | string | ✅ | One of `excel`, `pdf`. |

**Sector scoping**

- **Governor / Coordinator / Deputy Coordinator / System Admin** — `sectors` honoured; empty ⇒ all framework sectors.
- **Sector Head / Data Admin** — pinned to their own sector regardless of what's sent.
- **Other roles** — `403 forbidden`.

**Success — `200 OK`** (raw object `GeneratedReportModel`):

```json
{
  "id": "comp-9k2x8h4l",
  "format": "excel",
  "filename": "All_Sectors_MDAs_Full_Year_Assessment_Reporting_2024.xlsx",
  "fileSizeLabel": "248 KB",
  "downloadUrl": "https://api.pdcu.gov.ng/storage/uploads/reports/comp-9k2x8h4l.xlsx"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | Opaque artifact id (also the filename stem on disk). |
| `format` | string | ✅ | Echoes the requested `type`: `excel` or `pdf`. |
| `filename` | string | ✅ | Suggested filename for the client to save as (extension matches `format`). |
| `fileSizeLabel` | string | ✅ | Human-readable file size, e.g. `"248 KB"`. |
| `downloadUrl` | string | ✅ | Public URL — client fetches the file separately. May be cached by the CDN; treat as opaque. |

**Status codes:** `200` · `401` · `403` · `422` (year has no framework, `end_quarter < start_quarter`, `type` not `excel`/`pdf`, etc.).

> ⚠ Generation is synchronous: large frameworks may take several seconds. Show a spinner; don't time out the request below 30s. The `downloadUrl` is a static file — no auth header required to fetch it.

---

**Enums in this section**

- **Report format** (`format`, request body / `GeneratedReportModel.format`) — `excel`, `word`, `pdf`, `print`.
- **Report type** (`type`, comprehensive-report request) — `excel`, `pdf`.
- **Quarter** (`quarter`) — `q1`, `q2`, `q3`, `q4`.
- **Accent** (colour hint on `accent`/`currentAccent`/`percentAccent`/`perfAccent` fields; client-side `ReportAccent`) — `primary`, `secondary`, `tertiary`, `error`, `on_surface`.


---

### 11.9 Users & security

Module: `features/users`. Data source: `users_remote_data_source.dart`. DTOs:
`user_model.dart`, `user_profile_model.dart`, `security_event_model.dart`.
Request entities: `add_user_draft.dart`, `password_change_request.dart`,
`users_query.dart`. Enums: `users_enums.dart`.

#### 11.9.1 List users

| | |
| --- | --- |
| **Purpose** | List system users for the User Management screen, with search + role/sector filters. |
| **Method / Path** | `GET /users` |
| **Auth** | Bearer required |

**Query parameters**

| Param | Type | Req. | Notes |
| --- | --- | --- | --- |
| `search` | string | ❌ | omitted entirely when empty; matched server-side against name/email |
| `role` | string (enum) | ✅ | `UserRoleFilter` wire value; `all` means no role constraint |
| `sector` | string (enum) | ✅ | `UserSectorFilter` wire value; `all` means no sector constraint |

> The client always sends `role` and `sector` (defaulting to `all`); `search` is
> sent only when non-empty.

Example: `GET /users?search=amina&role=sector_head&sector=health`

**Success — `200 OK`** (raw array of user objects):

```json
[
  {
    "id": "amina-egbe",
    "name": "Amina Egbe",
    "email": "amina.egbe@pdcu.gov.ng",
    "role": "sector_head",
    "sector": "health",
    "roleLabel": "Sector Head",
    "sectorLabel": "Health",
    "initials": "AE",
    "accent": "primary"
  }
]
```

**Response fields** (per element)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `name` | string | ✅ | display name |
| `email` | string | ✅ | |
| `role` | string | ✅ | wire enum (`UserRole`-ish, snake_case) — see end-of-section |
| `sector` | string | ✅ | wire enum (`UserSector`) |
| `roleLabel` | string | ✅ | human label for `role` |
| `sectorLabel` | string | ✅ | human label for `sector` |
| `initials` | string | ✅ | avatar initials |
| `accent` | string | ✅ | wire enum (`UserAccent`) — UI accent slot |

Every field is required and must be a string; any non-string raises a parse
error. The response **must** be a top-level array (an object raises a parse
error).

**Status codes:** `200` · `401`.

#### 11.9.2 Get user profile

| | |
| --- | --- |
| **Purpose** | Fetch a single user's detailed profile for the User Profile screen. |
| **Method / Path** | `GET /users/{id}` |
| **Auth** | Bearer required |

**Path parameters**

| Param | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | user id from §11.9.1 |

**Success — `200 OK`** (raw object):

```json
{
  "id": "amina-egbe",
  "name": "Amina Egbe",
  "initials": "AE",
  "accent": "primary",
  "role": "sector_head",
  "roleLabel": "Administrator",
  "sectorLabel": "Public Sector",
  "email": "amina.egbe@pdcu.gov.ng",
  "phone": "+2348030000001",
  "fullLegalName": "Amina Yusuf Egbe",
  "staffId": "PDCU-2023-441",
  "joinDate": "Oct 12, 2023",
  "bio": "Lead Governance Analyst…",
  "twoFactorStatus": "Enabled (SMS/Email)",
  "isVerified": true
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `name` | string | ✅ | display name |
| `initials` | string | ✅ | avatar initials |
| `accent` | string | ✅ | wire enum (`UserAccent`) |
| `role` | string | ✅ | wire enum (snake_case) |
| `roleLabel` | string | ✅ | human label |
| `sectorLabel` | string | ✅ | human label |
| `email` | string | ❌ | powers the Email quick action (`mailto:`); button disabled when absent |
| `phone` | string | ❌ | powers the Call/SMS quick actions (`tel:`/`sms:`); buttons disabled when absent |
| `fullLegalName` | string | ✅ | |
| `staffId` | string | ✅ | |
| `joinDate` | string | ✅ | display string (not necessarily ISO — e.g. `"Oct 12, 2023"`) |
| `bio` | string | ✅ | |
| `twoFactorStatus` | string | ✅ | display string, e.g. `"Enabled (SMS/Email)"` |
| `isVerified` | bool | ✅ | |

All required fields must be present and correctly typed or the payload
raises a parse error. The optional `email`/`phone` degrade to empty
strings when absent or wrong-typed.

**Status codes:** `200` · `401` · `404` unknown `id`.

#### 11.9.3 Create user

| | |
| --- | --- |
| **Purpose** | Create a user from the Add User wizard, optionally with an avatar. |
| **Method / Path** | `POST /users` |
| **Auth** | Bearer required |
| **Content-Type** | `multipart/form-data` (see §10) |

This is a **multipart** request (Dio `FormData`), built from `AddUserDraft` — it
is **not** a JSON body.

**Form fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `fullName` | string | ✅ | |
| `email` | string | ✅ | |
| `phone` | string | ✅ | |
| `role` | string (enum) | ✅ | `UserRole` wire value |
| `avatarKey` | string | ❌ | system avatar id (`"avatar-1"`..`"avatar-8"`); sent only when a built-in avatar was chosen (i.e. no local image picked) |

**File part**

| Part | Req. | Notes |
| --- | --- | --- |
| `photo` | ❌ | the picked local image; attached **only** when the operator selected a local asset (`localAssetPath` non-empty). When a local image is used, `avatarKey` is omitted; the two are mutually exclusive. |

**Success:** any `2xx` (`201` preferred; body ignored).

**Status codes:** `201` created · `400`/`422` validation · `401` · `409`
duplicate email/user.

#### 11.9.4 Change my password

| | |
| --- | --- |
| **Purpose** | Change the signed-in user's password from the Change Password page. |
| **Method / Path** | `POST /users/me/password` |
| **Auth** | Bearer required |
| **Content-Type** | `application/json` |

**Request body** (built from `PasswordChangeRequest`; note `confirmPassword` is
validated client-side and **not** sent)

| Field | Type | Req. | Validation (client pre-flight) |
| --- | --- | --- | --- |
| `currentPassword` | string | ✅ | non-empty |
| `newPassword` | string | ✅ | ≥ 8 characters; must match the confirm field client-side |

```json
{ "currentPassword": "old-secret-1", "newPassword": "n3wStrongPass" }
```

**Success:** any `2xx` (`204` preferred; body ignored).

**Status codes:** `204` success · `400`/`422` weak/invalid new password · `401`
wrong current password / token invalid.

#### 11.9.5 Update my profile photo

| | |
| --- | --- |
| **Purpose** | Replace the signed-in user's avatar with a picked image. |
| **Method / Path** | `POST /users/me/photo` |
| **Auth** | Bearer required |
| **Content-Type** | `multipart/form-data` (see §10) |

This is a **multipart** request (Dio `FormData`). It carries **no** scalar form
fields — only the file part.

**File part**

| Part | Req. | Notes |
| --- | --- | --- |
| `photo` | ✅ | the picked local image file (always attached — the client only calls this after an asset is selected) |

**Success:** any `2xx` (`204` preferred; body ignored).

**Status codes:** `204` success · `400` invalid/oversized image · `401`.

#### 11.9.6 List security events

| | |
| --- | --- |
| **Purpose** | List audit/security-log entries for the Security Log screen, with filter + search. |
| **Method / Path** | `GET /users/security-log` |
| **Auth** | Bearer required |

**Query parameters**

| Param | Type | Req. | Notes |
| --- | --- | --- | --- |
| `filter` | string (enum) | ✅ | `SecurityLogFilter` wire value; `all` means no constraint |
| `q` | string | ❌ | free-text search; omitted entirely when empty (matched against user/ip/device) |

Example: `GET /users/security-log?filter=logins&q=amina`

**Success — `200 OK`** (raw array):

```json
[
  {
    "id": "evt-1",
    "kind": "success",
    "iconKey": "login",
    "title": "Admin Login Successful",
    "userLabel": "Amina Egbe",
    "timeLabel": "2 minutes ago",
    "ipAddress": "102.89.41.12",
    "deviceLabel": "Chrome on Windows"
  }
]
```

**Response fields** (per element)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `kind` | string | ✅ | wire enum (`SecurityEventKind`) — drives row stripe/icon colour |
| `iconKey` | string | ✅ | icon identifier, e.g. `login`, `cancel`, `shield_lock`, `swap_horiz`, `timer_off` |
| `title` | string | ✅ | event title |
| `userLabel` | string | ✅ | acting user display name |
| `timeLabel` | string | ✅ | relative time display string, e.g. `"2 minutes ago"` |
| `ipAddress` | string | ✅ | |
| `deviceLabel` | string | ✅ | e.g. `"Chrome on Windows"` |

All fields required and must be strings; the response must be a top-level array.

**Status codes:** `200` · `401`.

**Enums in this section**

| Enum | Field(s) | Wire values |
| --- | --- | --- |
| `UserRole` | `role` (form on create; values in user objects) | `governor`, `coordinator`, `sector_head`, `data_admin`, `facilitator`, `field_officer`, `auditor` |
| `UserSector` | `sector` (in user objects) | `any`, `health`, `education`, `infrastructure`, `agriculture` |
| `UserRoleFilter` | `role` query param (§11.9.1) | `all`, `sector_head`, `data_admin`, `field_officer`, `auditor` |
| `UserSectorFilter` | `sector` query param (§11.9.1) | `all`, `health`, `education`, `infrastructure`, `agriculture` |
| `UserAccent` | `accent` | `primary`, `secondary`, `tertiary`, `error` |
| `SecurityEventKind` | `kind` (§11.9.6) | `success`, `warning`, `error` |
| `SecurityLogFilter` | `filter` query param (§11.9.6) | `all`, `logins`, `changes`, `denied` |


---

### 11.10 System

Module: `features/system`. Data source: `system_remote_data_source.dart`. DTOs:
`system_status_model.dart` (`SystemStatusModel`, `AppUpdateInfoModel`),
`offline_snapshot_model.dart` (`OfflineSnapshotModel`, `OfflineCachedCardModel`),
`onboarding_slide_model.dart` (`OnboardingSlideModel`).

These endpoints back the maintenance, force-update, offline, and onboarding
screens.

⚠ **Backend note — auth on system endpoints.** The client sends
`Authorization: Bearer <token>` on **all** of these (no opt-out). Several (status,
update, onboarding slides) would plausibly be needed pre-auth (e.g. on the splash
/ gate screen). If you want them reachable without a valid session, make them
**public** server-side; the client will still send a bearer when it has one.

#### 11.10.1 Get system status

| | |
| --- | --- |
| **Purpose** | Resolve the current operating mode (e.g. maintenance) for the system-status screen. |
| **Method / Path** | `GET /system/status` |
| **Auth** | Bearer required (⚠ candidate to be public — see note above) |
| **Params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "mode": "maintenance",
  "title": "System Maintenance in Progress",
  "body": "We're performing scheduled maintenance…",
  "etaLabel": "2:00 PM (GMT+1)",
  "rotatingStatus": [
    "Updating administrative nodes...",
    "Syncing sector performance data..."
  ],
  "sessionId": "Session ID: PD-2024-MX-04"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `mode` | string | ✅ | operating mode, e.g. `maintenance`; free-form string (no closed client enum) |
| `title` | string | ✅ | |
| `body` | string | ✅ | |
| `etaLabel` | string | ✅ | display string, e.g. `"2:00 PM (GMT+1)"` |
| `rotatingStatus` | string[] | ✅ | rotating status lines; non-string elements are dropped, but the key must be a JSON array |
| `sessionId` | string | ✅ | display label, e.g. `"Session ID: PD-2024-MX-04"` |

A non-array `rotatingStatus`, or any non-string scalar, raises a parse error.

**Status codes:** `200` · `401`.

#### 11.10.2 Get update info

| | |
| --- | --- |
| **Purpose** | Provide current vs required app version for the force-update screen. |
| **Method / Path** | `GET /system/update` |
| **Auth** | Bearer required (⚠ candidate to be public — see note above) |
| **Params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "currentVersion": "v2.3.9",
  "requiredVersion": "v2.4.0",
  "title": "Update Required",
  "body": "A new version of PDCU is required to continue…",
  "releaseNotesUrl": "https://pdcu.gov.ng/release-notes"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `currentVersion` | string | ✅ | version installed on device, e.g. `"v2.3.9"` |
| `requiredVersion` | string | ✅ | minimum required version, e.g. `"v2.4.0"` |
| `title` | string | ✅ | |
| `body` | string | ✅ | |
| `releaseNotesUrl` | string | ✅ | absolute URL |

All fields required and must be strings.

**Status codes:** `200` · `401`.

#### 11.10.3 Get offline snapshot

| | |
| --- | --- |
| **Purpose** | Provide cached summary content shown on the connectivity-lost / offline screen. |
| **Method / Path** | `GET /system/offline-snapshot` |
| **Auth** | Bearer required |
| **Params** | none |

**Success — `200 OK`** (raw object with a nested `cachedCards` array):

```json
{
  "title": "Connectivity Lost",
  "body": "You appear to be offline…",
  "systemVersionLabel": "System Version: 4.7.0.60-OFFLINE",
  "cachedCards": [
    {
      "id": "last-viewed",
      "label": "Last viewed",
      "value": "Health Sector",
      "iconKey": "history",
      "accent": "secondary",
      "pillLabel": "LOCAL"
    },
    {
      "id": "unsynced",
      "label": "Unsynced Drafts",
      "value": "3 unsynced reports",
      "iconKey": "pending_actions",
      "accent": "error"
    }
  ]
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `title` | string | ✅ | |
| `body` | string | ✅ | |
| `systemVersionLabel` | string | ✅ | display label |
| `cachedCards` | object[] | ✅ | array of cached-card objects (below); must be a JSON array |

**`cachedCards[]` (OfflineCachedCardModel)**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `label` | string | ✅ | |
| `value` | string | ✅ | |
| `iconKey` | string | ✅ | icon identifier, e.g. `history`, `pending_actions` |
| `accent` | string | ✅ | UI accent slot, e.g. `secondary`, `error` |
| `pillLabel` | string | ❌ | optional pill caption, e.g. `"LOCAL"`; omit when absent |

**Status codes:** `200` · `401`.

#### 11.10.4 Retry connection

| | |
| --- | --- |
| **Purpose** | Server-side liveness probe triggered by the "Retry" button on the offline screen. |
| **Method / Path** | `POST /system/retry` |
| **Auth** | Bearer required |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `204` preferred) |

The client ignores the response body; any `2xx` is treated as a successful
reconnect probe.

**Status codes:** `2xx` success · `401`.

#### 11.10.5 Get onboarding slides

| | |
| --- | --- |
| **Purpose** | Fetch the carousel slides shown in the first-run onboarding flow. |
| **Method / Path** | `GET /system/onboarding` |
| **Auth** | Bearer required (⚠ candidate to be public — see note above) |
| **Params** | none |

**Success — `200 OK`** (raw array):

```json
[
  {
    "id": "step-1",
    "iconKey": "monitoring",
    "title": "Track MDA Progress",
    "body": "Monitor delivery across all sectors in real time…",
    "pillIconKey": "trending_up",
    "pillLabel": "LIVE PROGRESS",
    "pillValue": "87.4%"
  },
  {
    "id": "step-2",
    "iconKey": "verified",
    "title": "Streamline Approvals",
    "body": "Move submissions through the workflow…",
    "pillIconKey": "agriculture",
    "pillLabel": "Agric Sector"
  },
  {
    "id": "step-3",
    "iconKey": "bar_chart",
    "title": "Generate Reports",
    "body": "Produce comprehensive reports on demand…"
  }
]
```

**Response fields** (per element)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `iconKey` | string | ✅ | icon identifier, e.g. `monitoring`, `verified`, `bar_chart` |
| `title` | string | ✅ | |
| `body` | string | ✅ | |
| `pillIconKey` | string | ❌ | optional pill icon; omit when absent |
| `pillLabel` | string | ❌ | optional pill caption; omit when absent |
| `pillValue` | string | ❌ | optional pill value, e.g. `"87.4%"`; omit when absent |

`id`/`iconKey`/`title`/`body` are required; the three `pill*` fields are
independently optional. The response must be a top-level array.

**Status codes:** `200` · `401`.

#### 11.10.6 Complete onboarding

| | |
| --- | --- |
| **Purpose** | Mark the first-run onboarding flow as complete for the signed-in user. |
| **Method / Path** | `POST /system/onboarding/complete` |
| **Auth** | Bearer required |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `204` preferred) |

**Status codes:** `2xx` success · `401`.

**Enums in this section**

| Enum | Field | Wire values |
| --- | --- | --- |
| (none — `mode`, `iconKey`, `accent` are free-form strings, not closed client enums) | | |


---

### 11.11 Dashboards

Module: `features/dashboard`. Data source:
`dashboard_remote_data_source.dart`. DTOs: `dashboard_snapshot_models.dart`
(the 6 role snapshots) + `dashboard_models.dart` (shared nested rows:
`SectorPerformanceRowModel`, `ActivityEntryModel`, `DataAdminDeadlineModel`,
`FacilitatorSectorQueueModel`, `AdminSecurityRowModel`).

Each role has **one** read-only `GET` endpoint returning a role-specific
snapshot object. There are **no** params and **no** request bodies — the server
resolves the dashboard from the authenticated user. All six are pure reads, so
they are eligible for the client's automatic `GET` retry (§8).

> **Numeric note.** The client distinguishes **int** from **double** per field
> when parsing. Fields parsed as `_num` accept any JSON number but are coerced
> to **double**; fields parsed as `_int` require a JSON **integer** (a non-integer
> number — e.g. `82.0` where an int is expected, or a string — raises a parse
> error). Each field below is tagged `int` or `number` (double) accordingly.
> All scalar fields are **required**; a missing or wrong-typed field fails the
> whole parse.

Three nested row shapes recur across roles; they are defined once here and
referenced from each role's response table.

<a name="dash-sector-row"></a>**`SectorPerformanceRowModel`** (used by Governor
`sectorComparison`/`topInsights`/`bottomInsights` and Sector-Head `commitments`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `sectorId` | string | ✅ | |
| `name` | string | ✅ | display name |
| `iconKey` | string | ✅ | client icon slot (e.g. `stethoscope`, `bolt`) |
| `accent` | string | ✅ | accent slot (e.g. `primary`, `tertiary`, `error`) |
| `actualPercent` | number | ✅ | coerced to double |
| `planPercent` | number | ✅ | coerced to double |
| `deltaLabel` | string | ❌ | pre-formatted delta (e.g. `+4.2`, `-30`) |

<a name="dash-activity"></a>**`ActivityEntryModel`** (used by Coordinator
`recentSubmissions`, Facilitator `recentDecisions`, Data-Admin `recentActivity`)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `title` | string | ✅ | |
| `subtitle` | string | ✅ | |
| `timeLabel` | string | ✅ | pre-formatted (e.g. `2h ago`, `Oct 24`) |
| `accent` | string | ✅ | accent slot |
| `iconKey` | string | ❌ | optional icon slot |

#### 11.11.1 Governor dashboard

| | |
| --- | --- |
| **Purpose** | State-wide performance snapshot for the Governor home. |
| **Method / Path** | `GET /dashboard/governor` |
| **Auth** | Bearer required |
| **Query params** | `sector` (optional) — sector id; omitted = all sectors. `year` (optional int) — fiscal year. `quarter` (optional) — `QuarterIndex` wire token (`q1`–`q4`); omitted = **Annual** (whole year). All omitted on the initial load. |

The dashboard renders a filter row (sector dropdown · fiscal-year dropdown ·
Annual/Q1–Q4 segmented control); changing any of them re-fetches with the
params above. ⚠ **Backend note:** the endpoint must **scope every aggregate**
(hero scorecard, tiles, sector comparison, portfolio donut, insights) to the
supplied `sector`/`year`/`quarter` — they are whole-state server aggregations
the client can't re-derive. The fiscal-year options come from the frameworks'
`reportingYear`s ([§11.5.1](#1151-list-frameworks)); the client defaults the
selected year to the active framework's. Until the endpoint honors these
params, the filters change the selection but the data stays whole-state.

**Response fields** (raw object)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `greeting` | string | ✅ | e.g. `Good morning, Your Excellency.` |
| `greetingDate` | string | ✅ | pre-formatted date label |
| `overallPercent` | number | ✅ | state-wide overall % (double) |
| `overallDeltaLabel` | string | ✅ | e.g. `+2.4%` |
| `topPerformerName` | string | ✅ | top sector name |
| `topPerformerPercent` | number | ✅ | double |
| `topPerformerKpiCount` | int | ✅ | |
| `pendingVerifications` | int | ✅ | |
| `totalKpis` | int | ✅ | |
| `onTrackCount` | int | ✅ | |
| `atRiskCount` | int | ✅ | |
| `delayedCount` | int | ✅ | |
| `sectorComparison` | array | ✅ | list of [`SectorPerformanceRowModel`](#dash-sector-row) |
| `topInsights` | array | ✅ | list of [`SectorPerformanceRowModel`](#dash-sector-row) |
| `bottomInsights` | array | ✅ | list of [`SectorPerformanceRowModel`](#dash-sector-row) |

```json
{
  "greeting": "Good morning, Your Excellency.",
  "greetingDate": "Thursday, October 23, 2025",
  "overallPercent": 78,
  "overallDeltaLabel": "+2.4%",
  "topPerformerName": "Education & Literacy",
  "topPerformerPercent": 94.2,
  "topPerformerKpiCount": 24,
  "pendingVerifications": 14,
  "totalKpis": 142,
  "onTrackCount": 82,
  "atRiskCount": 45,
  "delayedCount": 15,
  "sectorComparison": [
    { "sectorId": "health", "name": "Healthcare", "iconKey": "stethoscope", "accent": "primary", "actualPercent": 72, "planPercent": 80 },
    { "sectorId": "agriculture", "name": "Agriculture", "iconKey": "agriculture", "accent": "tertiary", "actualPercent": 64, "planPercent": 60 },
    { "sectorId": "power", "name": "Power", "iconKey": "bolt", "accent": "error", "actualPercent": 45, "planPercent": 75 }
  ],
  "topInsights": [
    { "sectorId": "education", "name": "Education", "iconKey": "school", "accent": "secondary", "actualPercent": 94.2, "planPercent": 90, "deltaLabel": "+4.2" }
  ],
  "bottomInsights": [
    { "sectorId": "power", "name": "Power", "iconKey": "bolt", "accent": "error", "actualPercent": 45, "planPercent": 75, "deltaLabel": "-30" }
  ]
}
```

**Status codes:** `200` · `401` · `403` (wrong role).

#### 11.11.2 Coordinator dashboard

| | |
| --- | --- |
| **Purpose** | Review-queue and submission-rate snapshot for the Coordinator home. |
| **Method / Path** | `GET /dashboard/coordinator` |
| **Auth** | Bearer required |
| **Params** | none |

**Response fields** (raw object)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `greeting` | string | ✅ | e.g. `Hello, Coordinator.` |
| `reviewQueueCount` | int | ✅ | |
| `dataEntryOpenSectors` | int | ✅ | |
| `submissionRatePercent` | number | ✅ | double |
| `submissionRateTarget` | number | ✅ | double |
| `frameworkBadgeLabel` | string | ✅ | e.g. `FRAMEWORK ACTIVE` |
| `frameworkTitle` | string | ✅ | e.g. `FY 2026 Annual Framework` |
| `recentSubmissions` | array | ✅ | list of [`ActivityEntryModel`](#dash-activity) |

```json
{
  "greeting": "Hello, Coordinator.",
  "reviewQueueCount": 28,
  "dataEntryOpenSectors": 18,
  "submissionRatePercent": 84,
  "submissionRateTarget": 95,
  "frameworkBadgeLabel": "FRAMEWORK ACTIVE",
  "frameworkTitle": "FY 2026 Annual Framework",
  "recentSubmissions": [
    { "id": "sub-health-q3", "title": "Ministry of Health", "subtitle": "Q3 KPI Update", "timeLabel": "2h ago", "accent": "primary", "iconKey": "stethoscope" },
    { "id": "sub-edu-budget", "title": "Ministry of Education", "subtitle": "Budget Variance", "timeLabel": "5h ago", "accent": "secondary", "iconKey": "school" }
  ]
}
```

**Status codes:** `200` · `401` · `403`.

#### 11.11.3 Facilitator dashboard

| | |
| --- | --- |
| **Purpose** | Per-sector review queues and recent decisions for the Facilitator home. |
| **Method / Path** | `GET /dashboard/facilitator` |
| **Auth** | Bearer required |
| **Params** | none |

**Response fields** (raw object)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `awaitingReviewCount` | int | ✅ | |
| `sectorQueues` | array | ✅ | list of [`FacilitatorSectorQueueModel`](#dash-fac-queue) |
| `recentDecisions` | array | ✅ | list of [`ActivityEntryModel`](#dash-activity) |
| `avgResponseDays` | number | ✅ | double (e.g. `1.4`) |
| `reviewAccuracyPercent` | number | ✅ | double |

<a name="dash-fac-queue"></a>**`FacilitatorSectorQueueModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `sectorId` | string | ✅ | |
| `name` | string | ✅ | |
| `iconKey` | string | ✅ | |
| `lastReviewedLabel` | string | ✅ | pre-formatted (e.g. `2h ago`, `yesterday`) |
| `awaitingCount` | int | ✅ | |

```json
{
  "awaitingReviewCount": 12,
  "sectorQueues": [
    { "sectorId": "health", "name": "Health", "iconKey": "stethoscope", "lastReviewedLabel": "2h ago", "awaitingCount": 4 },
    { "sectorId": "education", "name": "Education", "iconKey": "school", "lastReviewedLabel": "yesterday", "awaitingCount": 3 },
    { "sectorId": "agriculture", "name": "Agriculture", "iconKey": "agriculture", "lastReviewedLabel": "3d ago", "awaitingCount": 5 }
  ],
  "recentDecisions": [
    { "id": "dec-healthcare", "title": "Healthcare", "subtitle": "Accepted", "timeLabel": "Oct 24", "accent": "primary" },
    { "id": "dec-fertilizer", "title": "Fertilizer", "subtitle": "Rejected", "timeLabel": "Oct 23", "accent": "error" },
    { "id": "dec-teacher-training", "title": "Teacher Training", "subtitle": "Accepted", "timeLabel": "Oct 22", "accent": "primary" }
  ],
  "avgResponseDays": 1.4,
  "reviewAccuracyPercent": 98
}
```

**Status codes:** `200` · `401` · `403`.

#### 11.11.4 Sector-Head dashboard

| | |
| --- | --- |
| **Purpose** | Single-sector overview and commitment breakdown for the Sector-Head home. |
| **Method / Path** | `GET /dashboard/sector-head` |
| **Auth** | Bearer required |
| **Query params** | `quarter` (optional) — `QuarterIndex` wire token (`q1`–`q4`). Scopes the **`commitments[]`** rows' `actualPercent`/`planPercent` to that quarter. **Omitted** on the initial load. |

The Sector-Head dashboard renders quarter chips above the commitment-tracking
list; tapping one re-fetches with `?quarter=`. ⚠ **Backend note:** the
client currently defaults the selected chip to **Q1** because the response
carries no active-quarter field — please add a `quarterLabel` (or
`activeQuarter`) to the response (echoing the requested/active quarter, e.g.
request `?quarter=q3` → respond `"quarterLabel": "Q3"`) so the chip can seed
correctly, mirroring [§11.11.5](#11115-data-admin-dashboard). Until then the
`quarter` param is accepted but the chip seeds to Q1.

**Response fields** (raw object)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `sectorName` | string | ✅ | e.g. `My Sector — Health`. The client strips a leading `My Sector — ` prefix before display. |
| `overallPercent` | number | ✅ | double |
| `activeKpis` | int | ✅ | |
| `totalCommitments` | int | ✅ | |
| `completedCommitments` | int | ✅ | |
| `inProgressCommitments` | int | ✅ | |
| `atRiskCommitments` | int | ✅ | |
| `pendingApprovals` | int | ✅ | |
| `commitments` | array | ✅ | list of [`SectorPerformanceRowModel`](#dash-sector-row); when `?quarter=` is sent, scope each row's `actualPercent`/`planPercent` to that quarter. |
| `quarterLabel` | string | ❌ | *(recommended — not yet sent)* the active/requested quarter, e.g. `"Q3"`; seeds the chip selection. |

```json
{
  "sectorName": "My Sector — Health",
  "overallPercent": 86,
  "activeKpis": 12,
  "totalCommitments": 5,
  "completedCommitments": 2,
  "inProgressCommitments": 2,
  "atRiskCommitments": 1,
  "pendingApprovals": 8,
  "commitments": [
    { "sectorId": "maternal-health", "name": "Maternal Health", "iconKey": "pregnant_woman", "accent": "primary", "actualPercent": 78, "planPercent": 80 },
    { "sectorId": "rural-clinics", "name": "Rural Clinics", "iconKey": "local_hospital", "accent": "secondary", "actualPercent": 92, "planPercent": 85 },
    { "sectorId": "vaccine-supply", "name": "Vaccine Supply", "iconKey": "vaccines", "accent": "tertiary", "actualPercent": 88, "planPercent": 95 }
  ]
}
```

**Status codes:** `200` · `401` · `403`.

#### 11.11.5 Data-Admin dashboard

| | |
| --- | --- |
| **Purpose** | Quarter completion, upcoming deadlines, and recent activity for the Data-Admin home. |
| **Method / Path** | `GET /dashboard/data-admin` |
| **Auth** | Bearer required |
| **Query params** | `quarter` (optional) — `QuarterIndex` wire token (`q1`–`q4`). Re-scopes the snapshot (hero metrics + deadlines + activity) to that quarter. **Omitted** on the initial load, where the server resolves the active quarter and echoes it in `quarterLabel`. |

The client renders quarter chips between the hero card and the deadlines;
tapping one re-fetches with `?quarter=`. It seeds the selected chip from the
returned `quarterLabel`, so keep that field consistent with the requested
quarter (e.g. request `?quarter=q4` → respond with `quarterLabel: "Q4"`).

**Response fields** (raw object)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `sectorName` | string | ✅ | |
| `quarterLabel` | string | ✅ | e.g. `Q2` |
| `completedKpis` | int | ✅ | |
| `totalKpis` | int | ✅ | |
| `completionPercent` | number | ✅ | double |
| `deadlines` | array | ✅ | list of [`DataAdminDeadlineModel`](#dash-deadline) |
| `recentActivity` | array | ✅ | list of [`ActivityEntryModel`](#dash-activity) |

<a name="dash-deadline"></a>**`DataAdminDeadlineModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `title` | string | ✅ | |
| `dueLabel` | string | ✅ | pre-formatted window-state label — one of `Due {M j}` / `Extended to {M j}` / `Deadline passed` / `Due this period` (fallback). Display text only; don't parse a date out of it. |
| `periodLabel` | string | ❌ | quarter + framework year the deadline refers to (e.g. `"Q2 2024"`). Rendered next to `dueLabel`. The client treats it as optional (older/fallback rows omit it). |
| `ctaLabel` | string | ✅ | call-to-action label (e.g. `Enter Actual`, `Draft`) |
| `accent` | string | ✅ | window-state colour: `primary` (in-window/fallback) · `tertiary` (extension granted) · `error` (deadline passed) |

```json
{
  "sectorName": "Agriculture",
  "quarterLabel": "Q2",
  "completedKpis": 3,
  "totalKpis": 12,
  "completionPercent": 25,
  "deadlines": [
    { "id": "kpi-irrigation", "title": "Irrigation Coverage", "dueLabel": "Due 30 Jun", "periodLabel": "Q2 2024", "ctaLabel": "Enter Actual", "accent": "primary" },
    { "id": "kpi-crop-yield", "title": "Crop Yield Metrics", "dueLabel": "Deadline passed", "periodLabel": "Q2 2024", "ctaLabel": "Enter Actual", "accent": "error" }
  ],
  "recentActivity": [
    { "id": "act-fertilizer", "title": "Fertilizer Dist.", "subtitle": "Pending Sector Head", "timeLabel": "Today 10:45 AM", "accent": "primary" },
    { "id": "act-tractor", "title": "Tractor Allocation", "subtitle": "Draft Saved", "timeLabel": "Yesterday 4:20 PM", "accent": "secondary" }
  ]
}
```

**Status codes:** `200` · `401` · `403`.

#### 11.11.6 System-Admin dashboard

| | |
| --- | --- |
| **Purpose** | Platform health, user counts, and a recent security log for the System-Admin home. |
| **Method / Path** | `GET /dashboard/system-admin` |
| **Auth** | Bearer required |
| **Params** | none |

**Response fields** (raw object)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `totalUsers` | int | ✅ | |
| `activeUsers` | int | ✅ | |
| `revokedUsers` | int | ✅ | |
| `userActivePercent` | number | ✅ | double |
| `loginCount24h` | int | ✅ | logins in the last 24h |
| `galleryImageCount` | int | ✅ | |
| `activeFrameworkCount` | int | ✅ | |
| `serverHealthPercent` | number | ✅ | double |
| `apiResponseLabel` | string | ✅ | pre-formatted (e.g. `24ms`) |
| `storageLabel` | string | ✅ | pre-formatted (e.g. `4.2TB / 10TB`) |
| `securityRows` | array | ✅ | list of [`AdminSecurityRowModel`](#dash-sec-row) |

<a name="dash-sec-row"></a>**`AdminSecurityRowModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `timestampLabel` | string | ✅ | pre-formatted (e.g. `10:42`) |
| `userLabel` | string | ✅ | e.g. `amina.egbe`, `unknown` |
| `actionLabel` | string | ✅ | e.g. `Sign in` |
| `statusLabel` | string | ✅ | e.g. `SUCCESS`, `BLOCKED` |
| `statusAccent` | string | ✅ | accent slot (e.g. `primary`, `error`) |

```json
{
  "totalUsers": 450,
  "activeUsers": 438,
  "revokedUsers": 12,
  "userActivePercent": 97,
  "loginCount24h": 1240,
  "galleryImageCount": 84,
  "activeFrameworkCount": 2,
  "serverHealthPercent": 89,
  "apiResponseLabel": "24ms",
  "storageLabel": "4.2TB / 10TB",
  "securityRows": [
    { "id": "row-1", "timestampLabel": "10:42", "userLabel": "amina.egbe", "actionLabel": "Sign in", "statusLabel": "SUCCESS", "statusAccent": "primary" },
    { "id": "row-2", "timestampLabel": "10:38", "userLabel": "unknown", "actionLabel": "Sign in", "statusLabel": "BLOCKED", "statusAccent": "error" }
  ]
}
```

**Status codes:** `200` · `401` · `403`.

**Enums in this section**

The dashboard payloads carry no enum-typed fields. `accent`, `iconKey`,
`statusAccent`, etc. are free-form **client slot strings** (rendered against the
active theme/icon set), not validated enum wire values — pass the slot keys shown
in the examples above.


---

### 11.12 Settings

Module: `features/settings`. Data source: `settings_remote_data_source.dart`.
DTOs: `settings_preferences_model.dart`, `faq_item_model.dart`,
`about_info_model.dart` (`AboutInfoModel`, `AboutContactChannelModel`,
`AboutSocialChannelModel`). Request entity: `feedback_draft.dart`.

Covers the Settings, Help & Support, and About screens.

#### 11.12.1 Get preferences

| | |
| --- | --- |
| **Purpose** | Fetch the signed-in user's app preferences for the Settings screen. |
| **Method / Path** | `GET /settings/preferences` |
| **Auth** | Bearer required |
| **Params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "themeMode": "system",
  "fontScale": 0.45,
  "biometricEnabled": true,
  "cellularUploadsEnabled": false,
  "syncOnWifiOnly": true,
  "languageCode": "en-NG",
  "languageLabel": "English (Nigeria)",
  "appVersion": "v2.4.0 (Build 2024.11.08)"
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `themeMode` | string | ✅ | theme selection: `system` \| `light` \| `dark` |
| `fontScale` | number | ✅ | text-scale slider position, `0.0`–`1.0` (e.g. `0.45`); accepted as int or double, coerced to double |
| `biometricEnabled` | bool | ✅ | biometric unlock toggle |
| `cellularUploadsEnabled` | bool | ✅ | allow uploads over cellular |
| `syncOnWifiOnly` | bool | ✅ | restrict sync to Wi-Fi |
| `languageCode` | string | ✅ | BCP-47-ish code, e.g. `"en-NG"` |
| `languageLabel` | string | ✅ | human label, e.g. `"English (Nigeria)"` |
| `appVersion` | string | ✅ | display version, e.g. `"v2.4.0 (Build 2024.11.08)"` |

All fields required. `fontScale` may be sent as an integer or float; every other
field must match its type exactly or a parse error is raised.

**Status codes:** `200` · `401`.

#### 11.12.2 Update preferences

| | |
| --- | --- |
| **Purpose** | Persist the full preferences object after a settings change. |
| **Method / Path** | `PUT /settings/preferences` |
| **Auth** | Bearer required |
| **Content-Type** | `application/json` |

**Request body** — the **entire** `SettingsPreferences` object (same shape as the
§11.12.1 response; the client PUTs the full object, not a partial patch):

| Field | Type | Req. | Validation |
| --- | --- | --- | --- |
| `themeMode` | string | ✅ | `system` \| `light` \| `dark` |
| `fontScale` | number | ✅ | `0.0`–`1.0` |
| `biometricEnabled` | bool | ✅ | |
| `cellularUploadsEnabled` | bool | ✅ | |
| `syncOnWifiOnly` | bool | ✅ | |
| `languageCode` | string | ✅ | |
| `languageLabel` | string | ✅ | |
| `appVersion` | string | ✅ | |

```json
{
  "themeMode": "dark",
  "fontScale": 0.6,
  "biometricEnabled": true,
  "cellularUploadsEnabled": true,
  "syncOnWifiOnly": false,
  "languageCode": "en-NG",
  "languageLabel": "English (Nigeria)",
  "appVersion": "v2.4.0 (Build 2024.11.08)"
}
```

**Success:** any `2xx` (body ignored; `204` preferred).

**Status codes:** `204` success · `400`/`422` invalid value · `401`.

#### 11.12.3 Clear cache

| | |
| --- | --- |
| **Purpose** | Server-side clear of the user's cached data (Settings → Clear cache). |
| **Method / Path** | `POST /settings/clear-cache` |
| **Auth** | Bearer required |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `204` preferred) |

**Status codes:** `2xx` success · `401`.

#### 11.12.4 Sync now

| | |
| --- | --- |
| **Purpose** | Trigger an immediate server-side sync (Settings → Sync now). |
| **Method / Path** | `POST /settings/sync` |
| **Auth** | Bearer required |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `204` preferred) |

**Status codes:** `2xx` success · `401`.

#### 11.12.5 Sign out everywhere

| | |
| --- | --- |
| **Purpose** | Revoke all of the user's sessions/devices (Settings → Sign out all devices). |
| **Method / Path** | `POST /settings/sign-out-all` |
| **Auth** | Bearer required |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `204` preferred) |

**Status codes:** `2xx` success · `401`.

#### 11.12.6 Get FAQs

| | |
| --- | --- |
| **Purpose** | Fetch the Help & Support FAQ list. |
| **Method / Path** | `GET /settings/faqs` |
| **Auth** | Bearer required |
| **Params** | none |

**Success — `200 OK`** (raw array):

```json
[
  {
    "id": "faq-kpi-submit",
    "question": "How to submit a KPI?",
    "answer": "Open the deliverable, tap Submit KPI…"
  }
]
```

**Response fields** (per element)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `question` | string | ✅ | |
| `answer` | string | ✅ | |

All required; the response must be a top-level array.

**Status codes:** `200` · `401`.

#### 11.12.7 Submit feedback

| | |
| --- | --- |
| **Purpose** | Submit a Help & Support feedback message, optionally with a screenshot. |
| **Method / Path** | `POST /settings/feedback` |
| **Auth** | Bearer required |
| **Content-Type** | `multipart/form-data` (see §10) |

This is a **multipart** request (Dio `FormData`), built from `FeedbackDraft` — it
is **not** a JSON body.

**Form fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `subject` | string | ✅ | |
| `message` | string | ✅ | |

**File part**

| Part | Req. | Notes |
| --- | --- | --- |
| `screenshot` | ❌ | the attached screenshot; included **only** when the user picked one (`screenshotPath` non-empty). When absent, the multipart request carries just `subject` + `message`. |

**Success:** any `2xx` (`201` preferred; body ignored).

**Status codes:** `201` created · `400`/`422` validation · `401`.

#### 11.12.8 Get about info

| | |
| --- | --- |
| **Purpose** | Fetch the institutional profile shown on the About screen. |
| **Method / Path** | `GET /settings/about` |
| **Auth** | Bearer required |
| **Params** | none |

**Success — `200 OK`** (raw object with nested `contacts` + `socials` arrays):

```json
{
  "heroTitle": "About PDCU",
  "heroSubtitle": "Institutional Profile",
  "mission": "Our mission is to coordinate and accelerate delivery…",
  "contacts": [
    { "iconKey": "email", "label": "Email Address", "value": "info@pdcu.gov.ng", "kind": "email" },
    { "iconKey": "call", "label": "Hotline", "value": "+234 800 000 0000", "kind": "phone" }
  ],
  "socials": [
    { "id": "linkedin", "label": "LinkedIn", "iconKey": "brand_awareness", "url": "https://linkedin.com/company/pdcu" },
    { "id": "x", "label": "X (Twitter)", "iconKey": "public", "url": "https://x.com/pdcu" }
  ],
  "statusLabel": "System Status: Operational",
  "versionLabel": "App Version v2.4.7-stable",
  "copyrightLabel": "© 2024 PDCU. All rights reserved.",
  "isOperational": true
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `heroTitle` | string | ✅ | |
| `heroSubtitle` | string | ✅ | |
| `mission` | string | ✅ | |
| `contacts` | object[] | ✅ | contact channels (below); must be a JSON array |
| `socials` | object[] | ✅ | social channels (below); must be a JSON array |
| `statusLabel` | string | ✅ | display label, e.g. `"System Status: Operational"` |
| `versionLabel` | string | ✅ | display label |
| `copyrightLabel` | string | ✅ | |
| `isOperational` | bool | ✅ | operational flag (independent of `statusLabel`) |

**`contacts[]` (AboutContactChannelModel)**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `iconKey` | string | ✅ | icon identifier, e.g. `email`, `call` |
| `label` | string | ✅ | |
| `value` | string | ✅ | the address/number |
| `kind` | string | ✅ | channel kind, observed: `email`, `phone` |

**`socials[]` (AboutSocialChannelModel)**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `label` | string | ✅ | |
| `iconKey` | string | ✅ | icon identifier |
| `url` | string | ✅ | absolute URL |

All listed fields are required for their object; the response root must be an
object.

**Status codes:** `200` · `401`.

**Enums in this section**

| Enum | Field | Wire values |
| --- | --- | --- |
| `themeMode` (free-form string, no closed client enum) | `themeMode` (§11.12.1/2) | observed: `system`, `light`, `dark` |
| `contacts[].kind` (free-form string) | `kind` (§11.12.8) | observed: `email`, `phone` |


---

### 11.13 Gallery

Module: `features/gallery`. Data source: `gallery_remote_data_source.dart`.
DTOs: `gallery_item_model.dart` (`GalleryItemModel`), `gallery_detail_model.dart`
(`GalleryDetailModel` + nested `GalleryStatModel`, `PublicCommentModel`).
Upload input: `gallery_upload_draft.dart` (`GalleryUploadDraft`). Enums:
`gallery_enums.dart`.

Two list reads (admin + public) are filtered via query params; one detail read
by id; one **multipart** create. The reads are eligible for the `GET` retry (§8).

#### 11.13.1 Management list (admin)

| | |
| --- | --- |
| **Purpose** | Admin grid of gallery items, scoped by tab. |
| **Method / Path** | `GET /gallery/management` |
| **Auth** | Bearer required |
| **Path params** | none |
| **Query params** | `tab` — required; one of `all` \| `recent` \| `archived` (the [`GalleryManagementTab`](#gallery-enums) wire value; always sent) |

**Success — `200 OK`**: a raw JSON **array** of [`GalleryItemModel`](#gallery-item).
The client filters by `tab` server-side; an empty array is valid.

```json
[
  { "id": "lagos-ibadan-expressway", "title": "Lagos-Ibadan Expressway", "category": "infrastructure", "categoryLabel": "Infrastructure", "iconKey": "add_road", "gradientKeys": ["primary", "tertiary"], "isActive": true, "isPublic": true, "displayOrder": 1 },
  { "id": "q3-review-summit", "title": "Q3 Review Summit", "category": "infrastructure", "categoryLabel": "Infrastructure", "iconKey": "groups", "gradientKeys": ["tertiary", "secondary"], "isActive": false, "isPublic": false, "displayOrder": 3 }
]
```

**Status codes:** `200` · `401` · `403`.

#### 11.13.2 Public list

| | |
| --- | --- |
| **Purpose** | Read-only public gallery tiles, scoped by filter chip. |
| **Method / Path** | `GET /gallery/public` |
| **Auth** | Bearer required |
| **Path params** | none |
| **Query params** | `filter` — required; one of `all` \| `roads` \| `healthcare` \| `education` (the [`PublicGalleryFilter`](#gallery-enums) wire value; always sent) |

**Success — `200 OK`**: a raw JSON **array** of [`GalleryItemModel`](#gallery-item)
(same shape as 11.13.1).

```json
[
  { "id": "lekki-viaduct", "title": "Lekki Viaduct Expansion", "category": "infrastructure", "categoryLabel": "FEDERAL INFRASTRUCTURE", "iconKey": "directions_car", "gradientKeys": ["primary", "tertiary"], "isActive": true, "isPublic": true, "displayOrder": 1 },
  { "id": "zonal-hospital", "title": "Zonal Hospital", "category": "health", "categoryLabel": "HEALTHCARE", "iconKey": "local_hospital", "gradientKeys": ["secondary", "primary"], "isActive": true, "isPublic": true, "displayOrder": 2 }
]
```

**Status codes:** `200` · `401` · `403`.

<a name="gallery-item"></a>**`GalleryItemModel`** (shared by both list endpoints)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `title` | string | ✅ | |
| `category` | string | ✅ | bucket key (e.g. `infrastructure`, `health`, `education`) — see [`GalleryCategory`](#gallery-enums); public items may carry free-form buckets |
| `categoryLabel` | string | ✅ | human label (e.g. `Infrastructure`, `FEDERAL INFRASTRUCTURE`) |
| `iconKey` | string | ✅ | client icon slot |
| `gradientKeys` | string[] | ❌ | accent-gradient slots; **defaults to `[]`** if missing/not an array. Non-string entries are dropped |
| `isActive` | bool | ✅ | |
| `isPublic` | bool | ✅ | |
| `displayOrder` | int | ✅ | sort order (JSON integer required) |

The client requires `id`, `title`, `category`, `categoryLabel`, `iconKey`
(strings), `isActive`, `isPublic` (bools) and `displayOrder` (int) to be present
and correctly typed, or the whole list item fails to parse.

#### 11.13.3 Item detail

| | |
| --- | --- |
| **Purpose** | Full detail page for one gallery item (hero + stat grid + read-only comments). |
| **Method / Path** | `GET /gallery/items/{id}` |
| **Auth** | Bearer required |
| **Path params** | `id` — gallery item id |
| **Query params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "id": "lekki-viaduct",
  "title": "Lekki Viaduct Expansion Project",
  "dateLabel": "Oct 12, 2023",
  "descriptionBlocks": [
    "The Lekki Viaduct Expansion is a flagship infrastructure project…",
    "Phase II works are scheduled through Q4…"
  ],
  "isVerified": true,
  "verifiedPillLabel": "Verified Project",
  "heroIconKey": "directions_car",
  "heroGradientKeys": ["primary", "tertiary"],
  "stats": [
    { "iconKey": "speed", "accent": "primary", "label": "COMPLETION", "value": "100%" },
    { "iconKey": "engineering", "accent": "tertiary", "label": "JOBS CREATED", "value": "1,200" }
  ],
  "comments": [
    { "id": "cmt-chidimma", "authorName": "Chidimma O.", "authorInitials": "CO", "timeLabel": "2h ago", "body": "Fantastic to see this completed ahead of schedule." }
  ]
}
```

**Response fields**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `title` | string | ✅ | |
| `dateLabel` | string | ✅ | pre-formatted date label |
| `descriptionBlocks` | string[] | ✅ | must be an array; non-string entries dropped |
| `isVerified` | bool | ✅ | |
| `verifiedPillLabel` | string | ✅ | |
| `heroIconKey` | string | ✅ | client icon slot |
| `heroGradientKeys` | string[] | ✅ | must be an array; non-string entries dropped |
| `stats` | array | ✅ | must be an array of [`GalleryStatModel`](#gallery-stat); may be empty |
| `comments` | array | ✅ | must be an array of [`PublicCommentModel`](#gallery-comment); may be empty |

<a name="gallery-stat"></a>**`GalleryStatModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `iconKey` | string | ✅ | |
| `accent` | string | ✅ | accent slot — see [`GalleryStatAccent`](#gallery-enums) |
| `label` | string | ✅ | e.g. `COMPLETION` |
| `value` | string | ✅ | e.g. `100%` |

<a name="gallery-comment"></a>**`PublicCommentModel`** (read-only)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `authorName` | string | ✅ | |
| `authorInitials` | string | ✅ | avatar fallback initials |
| `timeLabel` | string | ✅ | pre-formatted |
| `body` | string | ✅ | |

**Status codes:** `200` · `401` · `404` (unknown id).

#### 11.13.4 Upload item (multipart)

| | |
| --- | --- |
| **Purpose** | Create a gallery item, optionally with an image binary. |
| **Method / Path** | `POST /gallery/items` |
| **Auth** | Bearer required |
| **Content-Type** | `multipart/form-data` (Dio `FormData`; boundary set automatically — see §10) |
| **Path params** | none |
| **Success** | any `2xx` (body ignored; `201` per the client's contract note, `204` also accepted) |

This is **not** a JSON body. Scalar fields are sent as form fields; the file is
attached as a separate part **only when the user picked a local asset**.

**Form fields**

| Field | Type | Req. | Source / encoding |
| --- | --- | --- | --- |
| `title` | text | ✅ | `draft.title` |
| `description` | text | ✅ | `draft.description` |
| `category` | text | ✅ | `draft.category.wire` — one of `infrastructure` \| `education` \| `health` \| `agriculture` (see [`GalleryCategory`](#gallery-enums)) |
| `displayOrder` | text (int) | ✅ | `draft.displayOrder` — sent as a form field (Dio serializes the int to text) |
| `isPublic` | text (bool) | ✅ | `draft.isPublic` — sent as a form field (Dio serializes the bool to text) |

**File part**

| Part name | When attached | Notes |
| --- | --- | --- |
| `asset` | **Only** when `draft.localAssetPath` is non-null and non-empty | the picked image binary (`MultipartFile.fromFile`); filename/MIME come from the local file. **Omitted entirely** when no asset was picked — the request then carries only the scalar fields. |

**Status codes:** `201`/`2xx` success · `400`/`422` validation · `401`.

⚠ **Backend note.** `displayOrder` and `isPublic` arrive as multipart **text**
fields (Dio stringifies non-file values), so parse `displayOrder` as an integer
and `isPublic` as a boolean from their string forms (e.g. `"true"`/`"false"`).
The create response body is ignored by the client; return `201` with a
`Location`/body if convenient, but it is not consumed today.

**Enums in this section**

<a name="gallery-enums"></a>

| Enum (field) | Wire values |
| --- | --- |
| `GalleryManagementTab` (`?tab=`, 11.13.1) | `all`, `recent`, `archived` |
| `PublicGalleryFilter` (`?filter=`, 11.13.2) | `all`, `roads`, `healthcare`, `education` |
| `GalleryCategory` (`category`, item & upload) | `infrastructure`, `education`, `health`, `agriculture` |
| `GalleryStatAccent` (stat `accent`) | `primary`, `secondary`, `tertiary`, `error` |

Note: `iconKey`, `gradientKeys`/`heroGradientKeys`, and the generic `accent`
slots on items/details are free-form **client slot strings**, not validated
enums. Item `category` is documented against `GalleryCategory` but public items
may carry free-form bucket keys outside that set.


---

### 11.14 Notifications

Module: `features/notifications`. Data source:
`notifications_remote_data_source.dart`. DTOs:
`app_notification_model.dart` (`NotificationsInboxModel`,
`NotificationSectionModel`, `AppNotificationModel`),
`notification_preferences_model.dart` (`NotificationPreferencesModel`,
`TimeOfDayValueModel`). Enums: `notification_kind.dart`.

One inbox read (filtered by tab), one preferences read, one preferences update,
and two read-state commands. The two `GET`s are eligible for retry (§8).

#### 11.14.1 Inbox

| | |
| --- | --- |
| **Purpose** | Fetch the grouped notification inbox, scoped by tab. |
| **Method / Path** | `GET /notifications/inbox` |
| **Auth** | Bearer required |
| **Path params** | none |
| **Query params** | `tab` — required; one of `all` \| `unread` \| `mentions` (the [`NotificationTab`](#notif-enums) wire value; always sent) |

**Success — `200 OK`** (raw object): a `sections` array, each section a labeled
group of notifications. Server-side filtering by `tab` is expected
(`unread` → only unread; `mentions` → only `kind == "mention"`).

```json
{
  "sections": [
    {
      "id": "today",
      "label": "Today",
      "notifications": [
        {
          "id": "notif-1",
          "kind": "approval",
          "iconKey": "check_circle",
          "accent": "primary",
          "title": "KPI Approval Required",
          "timeAgoLabel": "10m ago",
          "contextLabel": "Health / Maternal Center Expansion",
          "body": "A submission is awaiting your review.",
          "isUnread": true
        },
        {
          "id": "notif-2",
          "kind": "discussion",
          "iconKey": "forum",
          "accent": "secondary",
          "title": "New Comment on Health Sector",
          "timeAgoLabel": "32m ago",
          "contextLabel": "Digital Infrastructure",
          "body": "Sani Musa replied to the thread.",
          "isUnread": true,
          "deepLinkRoute": "discussionThreadDetail",
          "deepLinkParams": { "threadId": "thread-digital-infra" }
        }
      ]
    },
    { "id": "yesterday", "label": "Yesterday", "notifications": [] }
  ]
}
```

**Response fields** — top level

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `sections` | array | ✅ | must be an array of [section objects](#notif-section); may be empty |

<a name="notif-section"></a>**`NotificationSectionModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | section key (e.g. `today`, `yesterday`) |
| `label` | string | ✅ | display heading |
| `notifications` | array | ✅ | must be an array of [`AppNotificationModel`](#notif-item); may be empty |

<a name="notif-item"></a>**`AppNotificationModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `kind` | string | ✅ | wire enum — see [`NotificationKind`](#notif-enums) |
| `iconKey` | string | ✅ | client icon slot |
| `accent` | string | ✅ | accent slot — see [`NotificationAccent`](#notif-enums) |
| `title` | string | ✅ | |
| `timeAgoLabel` | string | ✅ | pre-formatted (e.g. `10m ago`) |
| `contextLabel` | string | ✅ | secondary context line |
| `body` | string | ✅ | |
| `isUnread` | bool | ✅ | |
| `deepLinkRoute` | string | ❌ | client route name for tap nav; null/absent when not deep-linked |
| `deepLinkParams` | object | ❌ | **string→string** map of route params; defaults to `{}` if missing. Non-string keys/values are dropped |

**Status codes:** `200` · `401`.

#### 11.14.2 Get preferences

| | |
| --- | --- |
| **Purpose** | Fetch the user's notification preferences for the settings screen. |
| **Method / Path** | `GET /notifications/preferences` |
| **Auth** | Bearer required |
| **Params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "submissions": true,
  "approvals": true,
  "rejections": true,
  "mentions": false,
  "deadlines": true,
  "push": true,
  "email": true,
  "sms": false,
  "quietHoursEnabled": false,
  "quietFrom": { "hour": 22, "minute": 0 },
  "quietTo": { "hour": 6, "minute": 0 }
}
```

<a name="notif-prefs"></a>**Response / request fields** (same shape both ways)

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `submissions` | bool | ✅ | category toggle |
| `approvals` | bool | ✅ | category toggle |
| `rejections` | bool | ✅ | category toggle |
| `mentions` | bool | ✅ | category toggle |
| `deadlines` | bool | ✅ | category toggle |
| `push` | bool | ✅ | channel toggle |
| `email` | bool | ✅ | channel toggle |
| `sms` | bool | ✅ | channel toggle |
| `quietHoursEnabled` | bool | ✅ | |
| `quietFrom` | object | ✅ | [`TimeOfDayValue`](#notif-tod) |
| `quietTo` | object | ✅ | [`TimeOfDayValue`](#notif-tod) |

<a name="notif-tod"></a>**`TimeOfDayValueModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `hour` | int | ✅ | `0`–`23`; out of range raises a parse error |
| `minute` | int | ✅ | `0`–`59`; out of range raises a parse error |

Every scalar above is required and strictly typed (a non-bool toggle or a
non-int hour/minute fails the parse).

**Status codes:** `200` · `401`.

#### 11.14.3 Update preferences

| | |
| --- | --- |
| **Purpose** | Persist the full preferences object. |
| **Method / Path** | `PUT /notifications/preferences` |
| **Auth** | Bearer required |
| **Content-Type** | `application/json` |
| **Success** | any `2xx` (body ignored; `204` preferred) |

**Request body**: the complete [preferences object](#notif-prefs) — the client
sends `prefs.toJson()`, i.e. **all 11 fields** every time (no partial/PATCH
semantics). Nested `quietFrom`/`quietTo` are sent as `{ "hour", "minute" }`.

```json
{
  "submissions": true,
  "approvals": true,
  "rejections": false,
  "mentions": true,
  "deadlines": true,
  "push": true,
  "email": false,
  "sms": false,
  "quietHoursEnabled": true,
  "quietFrom": { "hour": 21, "minute": 30 },
  "quietTo": { "hour": 7, "minute": 0 }
}
```

**Status codes:** `204` success · `400`/`422` validation · `401`.

#### 11.14.4 Mark all as read

| | |
| --- | --- |
| **Purpose** | Mark every notification read. |
| **Method / Path** | `POST /notifications/mark-all-read` |
| **Auth** | Bearer required |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `204` preferred) |

**Status codes:** `204` success · `401`.

#### 11.14.5 Mark one as read

| | |
| --- | --- |
| **Purpose** | Mark a single notification read. |
| **Method / Path** | `POST /notifications/{id}/mark-read` |
| **Auth** | Bearer required |
| **Path params** | `id` — notification id |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `204` preferred) |

**Status codes:** `204` success · `401` · `404` (unknown id).

**Enums in this section**

<a name="notif-enums"></a>

| Enum (field) | Wire values |
| --- | --- |
| `NotificationTab` (`?tab=`, 11.14.1) | `all`, `unread`, `mentions` |
| `NotificationKind` (`kind`) | `submission`, `approval`, `rejection`, `discussion`, `deadline`, `mention`, `system` |
| `NotificationAccent` (`accent`) | `primary`, `secondary`, `tertiary`, `error` |

Unknown `kind`/`tab`/`accent` wire values fall back to `system`/`all`/`primary`
respectively (the client never throws on an unrecognized enum string). `iconKey`
is a free-form client slot string, not an enum.


---

### 11.15 Discussions

Module: `features/discussions`. Data source:
`discussions_remote_data_source.dart`. DTOs:
`discussions_hub_data_model.dart` (`DiscussionsHubDataModel`,
`DiscussionsSectorModel`, `DiscussionsHubTrendingModel`),
`commitment_thread_summary_model.dart` (`CommitmentThreadSummaryModel`),
`discussion_thread_detail_model.dart` (`DiscussionThreadDetailModel`,
`DiscussionCommentModel`). Request input: `post_comment_params.dart`
(`PostCommentParams`). Enums: `discussions_hub_filter.dart`.

Three reads (hub, sector thread feed, thread detail), one comment create, and
one like-toggle command. The three `GET`s are eligible for retry (§8).

#### 11.15.1 Discussions hub

| | |
| --- | --- |
| **Purpose** | Sector cards + trending panel for the discussions landing screen. |
| **Method / Path** | `GET /discussions/hub` |
| **Auth** | Bearer required |
| **Path params** | none |
| **Query params** | `filter` — required; one of `all` \| `priority` \| `recent` (the [`DiscussionsHubFilter`](#disc-enums) wire value; always sent) |

**Success — `200 OK`** (raw object):

```json
{
  "sectors": [
    { "id": "sector-health", "name": "Health", "tagline": "Maternal care, PHC revitalization", "accent": "error", "iconKey": "stethoscope", "countLabel": "1.2k" },
    { "id": "sector-security", "name": "Security", "tagline": "Community policing & response", "accent": "performance_fair", "iconKey": "security", "countLabel": "640" }
  ],
  "trending": {
    "hotTopicTag": "HOT TOPIC",
    "hotTopicTitle": "2024 Seed Subsidy Impact",
    "hotTopicBody": "Debate on subsidy reach across LGAs.",
    "healthBody": "Cold-chain coverage discussion gaining traction.",
    "educationBody": "Teacher training rollout feedback."
  }
}
```

**Response fields** — top level

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `sectors` | array | ✅ | must be an array of [`DiscussionsSectorModel`](#disc-sector); may be empty |
| `trending` | object | ✅ | [`DiscussionsHubTrendingModel`](#disc-trending); must be an object |

<a name="disc-sector"></a>**`DiscussionsSectorModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | sector id (use as `{sectorId}` in 11.15.2) |
| `name` | string | ✅ | |
| `tagline` | string | ✅ | |
| `accent` | string | ✅ | accent slot — see [`DiscussionsSectorAccent`](#disc-enums) |
| `iconKey` | string | ✅ | client icon slot |
| `countLabel` | string | ✅ | pre-formatted count (e.g. `1.2k`) |

<a name="disc-trending"></a>**`DiscussionsHubTrendingModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `hotTopicTag` | string | ✅ | e.g. `HOT TOPIC` |
| `hotTopicTitle` | string | ✅ | |
| `hotTopicBody` | string | ✅ | |
| `healthBody` | string | ✅ | |
| `educationBody` | string | ✅ | |

**Status codes:** `200` · `401`.

#### 11.15.2 Sector thread feed

| | |
| --- | --- |
| **Purpose** | List the commitment/stakeholder threads for one sector. |
| **Method / Path** | `GET /discussions/sectors/{sectorId}/threads` |
| **Auth** | Bearer required |
| **Path params** | `sectorId` — sector id (from the hub `sectors[].id`) |
| **Query params** | `tab` — required; one of `commitments` \| `stakeholders` (the [`CommentFeedTab`](#disc-enums) wire value; always sent) |

**Success — `200 OK`**: a raw JSON **array** of
[`CommitmentThreadSummaryModel`](#disc-thread-summary); may be empty.

```json
[
  {
    "threadId": "thread-digital-infra",
    "title": "Digital Infrastructure Rollout",
    "commentCountLabel": "24",
    "authorName": "Aminu Danladi",
    "timeLabel": "2h ago",
    "previewBody": "Fibre backbone phase 1 is ahead of schedule…"
  }
]
```

<a name="disc-thread-summary"></a>**`CommitmentThreadSummaryModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `threadId` | string | ✅ | use as `{threadId}` in 11.15.3 |
| `title` | string | ✅ | |
| `commentCountLabel` | string | ✅ | pre-formatted count (e.g. `24`) |
| `authorName` | string | ✅ | |
| `timeLabel` | string | ✅ | pre-formatted |
| `previewBody` | string | ✅ | truncated preview |

**Status codes:** `200` · `401` · `404` (unknown `sectorId`).

#### 11.15.3 Thread detail

| | |
| --- | --- |
| **Purpose** | Full thread: summary header + flat/nested comment list. |
| **Method / Path** | `GET /discussions/threads/{threadId}` |
| **Auth** | Bearer required |
| **Path params** | `threadId` — thread id |
| **Query params** | none |

**Success — `200 OK`** (raw object):

```json
{
  "id": "thread-digital-infra",
  "title": "Digital Infrastructure Rollout",
  "status": "in_progress",
  "statusLabel": "In Progress",
  "leadName": "Dr. Adebayo Omotola",
  "leadLabel": "LEAD OFFICER",
  "leadInitials": "AO",
  "comments": [
    {
      "id": "cmt-sani",
      "authorName": "Sani Musa",
      "authorRole": "Sector Head",
      "authorInitials": "SM",
      "timeLabel": "2h ago",
      "body": "Phase 1 fibre is live across three LGAs.",
      "likeCount": 12
    },
    {
      "id": "cmt-reply-chinelo",
      "authorName": "Chinelo J.",
      "authorRole": "Facilitator",
      "authorInitials": "CJ",
      "timeLabel": "1h ago",
      "body": "Great — can we confirm the uptime SLA?",
      "likeCount": 4,
      "parentId": "cmt-sani",
      "isLikedByCurrentUser": true
    }
  ]
}
```

**Response fields** — top level

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `title` | string | ✅ | |
| `status` | string | ✅ | wire enum — see [`DiscussionThreadStatus`](#disc-enums) |
| `statusLabel` | string | ✅ | display label (e.g. `In Progress`) |
| `leadName` | string | ✅ | |
| `leadLabel` | string | ✅ | e.g. `LEAD OFFICER` |
| `leadInitials` | string | ✅ | avatar fallback initials |
| `comments` | array | ✅ | must be an array of [`DiscussionCommentModel`](#disc-comment); may be empty |

<a name="disc-comment"></a>**`DiscussionCommentModel`**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `id` | string | ✅ | |
| `authorName` | string | ✅ | |
| `authorRole` | string | ✅ | free-form role label (e.g. `Sector Head`) |
| `authorInitials` | string | ✅ | |
| `timeLabel` | string | ✅ | pre-formatted |
| `body` | string | ✅ | |
| `likeCount` | number | ✅ | JSON number; coerced to **int** via `toInt()` (a fractional value is truncated) |
| `parentId` | string | ❌ | set on replies → nests under the parent comment. If present it **must** be a string (a non-string raises a parse error); null/absent = top-level |
| `isLikedByCurrentUser` | bool | ❌ | defaults to `false` when missing/non-bool |

**Status codes:** `200` · `401` · `404` (unknown `threadId`).

#### 11.15.4 Post comment / reply

| | |
| --- | --- |
| **Purpose** | Add a comment to a thread, or a reply under an existing comment. |
| **Method / Path** | `POST /discussions/threads/{threadId}/comments` |
| **Auth** | Bearer required |
| **Content-Type** | `application/json` |
| **Path params** | `threadId` — thread id (from `PostCommentParams.threadId`) |
| **Success** | any `2xx` (body ignored; `201` per the client's contract note) |

**Request body**

| Field | Type | Req. | Notes |
| --- | --- | --- | --- |
| `body` | string | ✅ | comment text |
| `parentId` | string | ❌ | **only included when replying**; omitted entirely for a top-level comment |

Top-level comment:

```json
{ "body": "Adding a note on the SLA review timeline." }
```

Reply (note `parentId` present):

```json
{ "body": "Agreed — let's target 99.5%.", "parentId": "cmt-sani" }
```

**Status codes:** `201`/`2xx` success · `400`/`422` validation (empty body) ·
`401` · `404` (unknown `threadId`). The created comment is **not** returned to
the client today; the page refetches the thread (11.15.3).

#### 11.15.5 Toggle comment like

| | |
| --- | --- |
| **Purpose** | Toggle the current user's like on a comment. |
| **Method / Path** | `POST /discussions/comments/{commentId}/toggle-like` |
| **Auth** | Bearer required |
| **Path params** | `commentId` — comment id |
| **Request body** | none |
| **Success** | any `2xx` (body ignored; `202` per the client's contract note) |

Server-side **toggle**: each call flips the like state for the authenticated
user (like ⇄ unlike) and adjusts `likeCount` accordingly. The new state is not
returned to the client; the updated `likeCount`/`isLikedByCurrentUser` surface on
the next thread-detail read (11.15.3).

**Status codes:** `202`/`2xx` success · `401` · `404` (unknown `commentId`).

**Enums in this section**

<a name="disc-enums"></a>

| Enum (field) | Wire values |
| --- | --- |
| `DiscussionsHubFilter` (`?filter=`, 11.15.1) | `all`, `priority`, `recent` |
| `CommentFeedTab` (`?tab=`, 11.15.2) | `commitments`, `stakeholders` |
| `DiscussionThreadStatus` (`status`, 11.15.3) | `in_progress`, `resolved`, `blocked` |
| `DiscussionsSectorAccent` (sector `accent`) | `primary`, `secondary`, `tertiary`, `error`, `performance_fair` |

Unknown wire values fall back to the first variant
(`all` / `commitments` / `in_progress` / `primary`); the client never throws on an
unrecognized enum string. `iconKey` and `authorRole` are free-form strings, not
enums.


---

## 12. Appendix A — enum wire values

Enum-typed fields are serialized as their **wire string** (the value in
parentheses in the Dart `enum`). Feature-local enums are listed in each
section's **"Enums in this section"** block; the cross-cutting ones are
collected here.

### UserRole (cross-cutting: auth, profile, users, dashboards)

| Wire value | Role |
| --- | --- |
| `governor` | Governor |
| `coordinator` | Coordinator |
| `sector_head` | Sector Head |
| `data_admin` | Data Admin |
| `facilitator` | Facilitator |
| `system_admin` | System Admin |

`role` may be `null` on the user/profile objects when the backend has not yet
assigned a role; the client then routes to a role picker.

### QuarterIndex (reports, KPI tracking)

| Wire value | Quarter |
| --- | --- |
| `q1` | Q1 |
| `q2` | Q2 |
| `q3` | Q3 |
| `q4` | Q4 |

### SectorMethod (frameworks)

| Wire value | Meaning |
| --- | --- |
| `blank` | start from an empty sector set |
| `inherit` | inherit sectors from another framework |

> **Enum decoding is lenient.** Most feature enums fall back to a sensible
> default on an **unrecognized** wire string rather than failing the parse
> (e.g. gallery/notifications/discussions category & status tokens). The
> exceptions are the auth/user/profile string fields, where a non-string value
> (not an unknown string) raises a parse error. Send only the documented wire
> values.

---

## 13. Appendix B — backend recommendations

Consolidated from the ⚠ notes throughout. None of these block initial
implementation against the **current** client contract, but adopting them will
require a coordinated client change — please raise them before building so we
can sequence the client work.

1. **Response envelope.** The client consumes **raw** objects/arrays today (no
   `{ success, message, data }` wrapper). If a standard envelope is desired,
   the client must be updated to unwrap it. Pick one and confirm before build.
2. **Error body + field errors.** Return a structured error body
   (`{ code, message, fieldErrors? }`) on every non-2xx. The client maps on
   **status code only** right now and does not read the body; standardizing the
   body now lets the client adopt it later without a contract change. Use
   `422` + `fieldErrors` for validation failures (the client currently shows a
   generic message for `400`/`422`).
3. **Server-side validation.** Client-side validation (email shape, password
   length, etc.) is a convenience, not a security boundary. Validate everything
   server-side.
4. **Token refresh.** Implement `POST /auth/refresh` (§11.1.5) so the client
   can add silent re-auth on `401`. Decide whether the refresh token travels in
   the body or a header and document it.
5. **Pagination.** List endpoints currently return **full collections**. Plan
   cursor/offset pagination for the large lists (users, security log, gallery,
   discussions, notifications). The current client expects a bare array, so
   this needs a paired client change.
6. **Idempotency.** The client auto-retries `GET`s (up to 2×) on transient
   errors and `502/503/504`. Keep all `GET`s side-effect free. If client-side
   retry of mutations is enabled later, support an idempotency key.
7. **Field-naming consistency.** The wire is mixed: `access_token`/
   `refresh_token` (snake_case) vs `mustChangePassword`/`fullName` (camelCase).
   Prefer a single convention for new endpoints; existing keys are pinned by
   the client and must not change without a client update.
8. **Dates.** Some fields are true ISO-8601 (`joinDate`,
   `expiresAt`, milestone/tracking dates), but several display fields are
   pre-formatted strings (e.g. `"Oct 12, 2023"`, `"2 minutes ago"`,
   version labels). These are flagged per-field. Prefer returning machine
   formats (ISO-8601 / epoch) and let the client format; coordinate before
   changing existing display-string fields.
9. **Multipart scalar types.** On multipart endpoints, non-file fields
   (`displayOrder`, `isPublic`, etc.) are transmitted as multipart **text**
   parts (Dio stringifies them). Parse them tolerantly server-side
   (`"true"`/`"1"`).
10. **Auth on system signals.** `GET /system/status`, `/system/update`, and
    `/system/onboarding` are sent **with** a bearer token by the client but are
    conceptually public/pre-auth. They must still succeed for an authenticated
    user; consider also allowing them unauthenticated so the app can show
    maintenance/force-update screens before login.

---

*End of API reference. Generated from the client data layer; keep in sync when
data sources or DTOs change.*
