# Mobile App — Feature-Based Folder Structure (Flutter)

> Companion to [`MOBILE_APP_STITCH_PROMPT.md`](./MOBILE_APP_STITCH_PROMPT.md). This document explains how the 65 designed screens map into a small number of cohesive features, defines the folder layout, and provides a paste‑ready prompt for Claude Code to scaffold the structure inside an existing Flutter project.

---

## 1. Goals

The structure has to satisfy four constraints, in order of priority:

1. **Map cleanly to business capabilities.** Each top‑level folder under `features/` should answer a real product question ("approvals", "reports", "frameworks") — not a technical category. A new engineer should be able to find KPI tracking by guessing `features/kpi_tracking/`, not by chasing nav metadata.
2. **Match the approval pipeline.** The most important and most complex flow in the app is the four‑stage workflow (Data Admin → Sector Head → Facilitator → Coordinator). That domain deserves a dedicated home — splitting it across role‑named folders would scatter what is really one workflow.
3. **Keep cross‑cutting things out of features.** Theming, networking, navigation, status badges, KPI tiles, evidence chips — these belong in `core/` and `shared/widgets/`, not duplicated inside each feature.
4. **Stay shallow enough to navigate.** Each feature has at most three internal layers (`data/`, `domain/`, `presentation/`). No nested feature folders. Avoid Clean‑Architecture maximalism — only add a layer when the feature actually needs it.

---

## 2. Feature catalogue (screens → features)

The 65 screens in the Stitch prompt collapse into **13 features** plus a `system/` group for global/utility screens.

| Feature | Purpose | Screens (from Stitch prompt) |
|---|---|---|
| `auth` | Sign‑in, password, role‑switch — anything tied to who the user *is*. | #1 Splash, #2 Welcome, #3 Sign In, #4 Forced Password Change, #5 Role Picker, #58 Switch Role |
| `dashboard` | Role‑aware home tab. Each role gets a screen file; shared widgets handle the cards/charts. | #6 Governor, #7 Coordinator, #8 Sector Head, #9 Data Admin, #10 Facilitator, #11 System Admin |
| `sectors` | The Framework → Sector → Commitment → Deliverable browse hierarchy (read‑heavy navigation). | #17 Sectors List, #18 Sector Overview, #19 Commitment Card component, #20 Commitment Detail, #21 Deliverable Detail, #22 KPI Quick View |
| `kpi_tracking` | Create, edit, view performance‑tracking rows. The actual data entry surface. | #23 Set Milestone, #24 Add Tracking, #28 KPI Tracking Detail, #29 Attachment Preview |
| `approvals` | Every review queue and decision sheet across all four roles in the workflow. | #12 Sector Head Queue, #13 Facilitator Queue, #14 Facilitator Review, #15 Coordinator Final Review Queue, #16 Data Admin Queue, #25 Bulk Sector Head Approval, #26 Facilitator Decision Sheet, #27 Coordinator Final Verify |
| `reports` | Reporting and analytics surfaces. | #30 Reports Hub, #31 Comprehensive Setup, #32 Comprehensive Viewer, #33 Word Generator, #34 Print Preview |
| `frameworks` | Annual framework lifecycle. | #35 Framework List, #36 Create Framework, #37 Framework Detail, #38 Confirm Inherit |
| `data_entry_windows` | Coordinator lock/unlock administration. | #39 Data Entry Management (+ Grant Override sheet) |
| `users` | User CRUD, profile, security log. | #40 Users List, #41 Add User (3 steps), #42 User Profile, #43 Change Password, #44 Update Photo, #59 Security Log |
| `gallery` | Gallery admin and public viewer. | #45 Gallery Management, #46 Upload/Edit Gallery Image, #47 Public Gallery, #48 Public Gallery Detail |
| `discussions` | Sector / project comment threads. | #49 Sector Discussion Hub, #50 Project Comment Feeds, #51 Thread Detail |
| `notifications` | Inbox + preferences. | #52 Notifications Inbox, #53 Notification Preferences |
| `profile` | "Me" — the current user's view. Reuses `users` widgets internally. | #54 My Profile |
| `settings` | App settings, help, about. | #55 Settings, #56 Help & Support, #57 About PDCU |
| `system` *(not a feature)* | Cross‑cutting error/utility screens with no business domain. | #60 Offline, #61 Maintenance, #62 Permission Denied, #63 Not Found, #64 Force Update, #65 Onboarding Tour |

### 2.1 Boundary calls worth explaining

- **`approvals` is one feature, not four role‑folders.** Even though Sector Head, Facilitator, and Coordinator each have their own queue, they all act on the same `performance_trackings` row and reuse the same evidence viewer and comments. Splitting by role would force three copies of one widget tree.
- **`profile` vs `users`.** "My Profile" (#54) is conceptually distinct from "User admin" (#40‑#44). They share entities and some widgets, but the entry points and permissions diverge. Keeping them apart prevents `users` from becoming a god‑feature.
- **#43 Change Password lives in `users`**, not `auth`, because admins also change other users' passwords from the same screen.
- **#19 Commitment Card** is a *widget* used inside `sectors`, not its own screen — it lives in `features/sectors/presentation/widgets/`.
- **`system/` is not under `features/`** because the screens have no domain — they're navigation outcomes (offline, 404) rather than capabilities.

---

## 3. Folder tree

```
lib/
├── main.dart
├── app.dart                         # MaterialApp/CupertinoApp shell, theme switch
│
├── core/                            # framework-level plumbing — no business logic
│   ├── api/                         # Dio client, interceptors, base response types
│   │   ├── api_client.dart
│   │   ├── auth_interceptor.dart
│   │   ├── error_interceptor.dart
│   │   └── api_endpoints.dart       # all /api/* paths in one place
│   ├── config/                      # env, flavors (dev/staging/prod)
│   ├── di/                          # GetIt registrations (or Riverpod providers)
│   ├── error/                       # Failure, exception mapping
│   ├── network/                     # connectivity stream, offline detector
│   ├── router/                      # GoRouter config, route names, guards
│   │   ├── app_router.dart
│   │   ├── route_paths.dart
│   │   └── route_guards.dart        # auth guard, role guard
│   ├── storage/                     # secure storage (token), Hive boxes (cache, drafts)
│   ├── theme/                       # design system tokens
│   │   ├── app_colors.dart          # #008751, semantic colours
│   │   ├── app_typography.dart      # Public Sans scale
│   │   ├── app_spacing.dart
│   │   └── app_theme.dart
│   ├── localization/                # ARB files, l10n delegate
│   └── utils/                       # formatters (currency, date), validators
│
├── shared/                          # reusable across features — but NOT framework-level
│   ├── models/                      # domain entities reused by ≥2 features
│   │   ├── framework.dart
│   │   ├── sector.dart
│   │   ├── commitment.dart
│   │   ├── deliverable.dart
│   │   ├── kpi.dart
│   │   ├── performance_tracking.dart
│   │   ├── kpi_target.dart
│   │   ├── attachment.dart
│   │   ├── user.dart
│   │   ├── user_role.dart
│   │   ├── notification.dart
│   │   └── confirmation_status.dart # enum: NotConfirmed, PendingSectorHead, ...
│   ├── widgets/                     # the §2.4 component library from the Stitch prompt
│   │   ├── app_scaffold.dart
│   │   ├── primary_app_bar.dart
│   │   ├── bottom_nav.dart
│   │   ├── status_badge.dart        # 6 semantic variants
│   │   ├── performance_ring.dart    # 64/96/144 dp variants
│   │   ├── performance_band_chip.dart
│   │   ├── kpi_tile.dart
│   │   ├── sector_card.dart
│   │   ├── commitment_card.dart
│   │   ├── quarter_selector.dart    # segmented Annual/Q1-Q4
│   │   ├── year_chip.dart
│   │   ├── sector_picker_sheet.dart
│   │   ├── evidence_chip.dart
│   │   ├── comment_row.dart
│   │   ├── empty_state.dart
│   │   ├── error_state.dart
│   │   ├── loading_skeleton.dart
│   │   ├── inline_alert.dart
│   │   ├── stepper.dart
│   │   └── bottom_sheet_scaffold.dart
│   └── extensions/                  # BuildContext, DateTime, num formatting
│
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── datasources/auth_remote_datasource.dart
│   │   │   ├── models/auth_response_model.dart
│   │   │   └── repositories/auth_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/auth_repository.dart
│   │   │   └── usecases/                       # sign_in, switch_role, change_password, etc.
│   │   └── presentation/
│   │       ├── controllers/auth_controller.dart  # state mgmt
│   │       ├── screens/
│   │       │   ├── splash_screen.dart                  # #1
│   │       │   ├── welcome_screen.dart                 # #2
│   │       │   ├── sign_in_screen.dart                 # #3
│   │       │   ├── forced_password_change_screen.dart  # #4
│   │       │   ├── role_picker_screen.dart             # #5
│   │       │   └── switch_role_sheet.dart              # #58
│   │       └── widgets/
│   │
│   ├── dashboard/
│   │   ├── data/                                       # dashboard_repository_impl, stats datasource
│   │   ├── domain/                                     # get_role_dashboard usecase
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── dashboard_shell_screen.dart         # role router — picks the right screen
│   │       │   ├── governor_dashboard_screen.dart      # #6
│   │       │   ├── coordinator_dashboard_screen.dart   # #7
│   │       │   ├── sector_head_dashboard_screen.dart   # #8
│   │       │   ├── data_admin_dashboard_screen.dart    # #9
│   │       │   ├── facilitator_dashboard_screen.dart   # #10
│   │       │   └── system_admin_dashboard_screen.dart  # #11
│   │       └── widgets/
│   │           ├── scorecard.dart
│   │           ├── kpi_status_donut.dart
│   │           ├── sector_comparison_bar_chart.dart
│   │           └── deadline_card.dart
│   │
│   ├── sectors/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── sectors_list_screen.dart            # #17
│   │       │   ├── sector_overview_screen.dart         # #18
│   │       │   ├── commitment_detail_screen.dart       # #20
│   │       │   ├── deliverable_detail_screen.dart      # #21
│   │       │   └── kpi_quick_view_sheet.dart           # #22
│   │       └── widgets/
│   │           └── commitment_card.dart                # #19 (feature-local variant)
│   │
│   ├── kpi_tracking/
│   │   ├── data/
│   │   │   ├── datasources/
│   │   │   ├── models/
│   │   │   └── repositories/
│   │   ├── domain/
│   │   │   ├── repositories/
│   │   │   └── usecases/
│   │   │       ├── set_milestone.dart
│   │   │       ├── submit_tracking.dart
│   │   │       ├── save_draft.dart
│   │   │       ├── upload_evidence.dart
│   │   │       └── get_kpi_tracking_year.dart
│   │   └── presentation/
│   │       ├── controllers/tracking_form_controller.dart
│   │       ├── screens/
│   │       │   ├── set_milestone_sheet.dart            # #23
│   │       │   ├── add_tracking_screen.dart            # #24
│   │       │   ├── kpi_tracking_detail_screen.dart     # #28
│   │       │   └── attachment_preview_screen.dart      # #29
│   │       └── widgets/
│   │           ├── tracking_form.dart
│   │           ├── evidence_picker.dart
│   │           └── quarter_tracking_card.dart
│   │
│   ├── approvals/
│   │   ├── data/
│   │   ├── domain/
│   │   │   └── usecases/
│   │   │       ├── sector_head_bulk_approve.dart
│   │   │       ├── facilitator_decide.dart
│   │   │       └── coordinator_verify.dart
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── sector_head_queue_screen.dart       # #12
│   │       │   ├── facilitator_queue_screen.dart       # #13
│   │       │   ├── facilitator_review_screen.dart      # #14 + #26
│   │       │   ├── coordinator_final_queue_screen.dart # #15
│   │       │   ├── data_admin_queue_screen.dart        # #16
│   │       │   ├── bulk_sector_head_approval_screen.dart # #25
│   │       │   └── coordinator_verify_screen.dart      # #27
│   │       └── widgets/
│   │           ├── approval_row.dart
│   │           ├── decision_buttons.dart
│   │           └── previous_review_section.dart
│   │
│   ├── reports/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── reports_hub_screen.dart                  # #30
│   │       │   ├── comprehensive_setup_screen.dart          # #31
│   │       │   ├── comprehensive_viewer_screen.dart         # #32
│   │       │   ├── word_report_generator_screen.dart        # #33
│   │       │   └── print_preview_screen.dart                # #34
│   │       └── widgets/
│   │
│   ├── frameworks/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── frameworks_list_screen.dart              # #35
│   │       │   ├── create_framework_screen.dart             # #36
│   │       │   ├── framework_detail_screen.dart             # #37
│   │       │   └── confirm_inherit_framework_screen.dart    # #38
│   │       └── widgets/
│   │
│   ├── data_entry_windows/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   └── data_entry_management_screen.dart        # #39
│   │       └── widgets/
│   │           └── grant_override_sheet.dart                # #39 sub-sheet
│   │
│   ├── users/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── users_list_screen.dart                   # #40
│   │       │   ├── add_user_step_basics_screen.dart         # #41 (step 1)
│   │       │   ├── add_user_step_role_screen.dart           # #41 (step 2)
│   │       │   ├── add_user_step_avatar_screen.dart         # #41 (step 3)
│   │       │   ├── user_profile_screen.dart                 # #42
│   │       │   ├── change_password_screen.dart              # #43
│   │       │   ├── update_photo_screen.dart                 # #44
│   │       │   └── security_log_screen.dart                 # #59
│   │       └── widgets/
│   │
│   ├── gallery/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── gallery_management_screen.dart           # #45
│   │       │   ├── gallery_upload_screen.dart               # #46
│   │       │   ├── public_gallery_screen.dart               # #47
│   │       │   └── public_gallery_detail_screen.dart        # #48
│   │       └── widgets/
│   │
│   ├── discussions/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── sector_discussion_screen.dart            # #49
│   │       │   ├── project_comments_screen.dart             # #50
│   │       │   └── thread_detail_screen.dart                # #51
│   │       └── widgets/
│   │
│   ├── notifications/
│   │   ├── data/
│   │   │   └── fcm_service.dart                             # FCM token, payload routing
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   ├── notifications_inbox_screen.dart          # #52
│   │       │   └── notification_preferences_screen.dart     # #53
│   │       └── widgets/
│   │           └── notification_row.dart
│   │
│   ├── profile/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       ├── screens/
│   │       │   └── my_profile_screen.dart                   # #54
│   │       └── widgets/
│   │
│   └── settings/
│       ├── data/
│       ├── domain/
│       └── presentation/
│           ├── controllers/
│           ├── screens/
│           │   ├── settings_screen.dart                     # #55
│           │   ├── help_support_screen.dart                 # #56
│           │   └── about_screen.dart                        # #57
│           └── widgets/
│
└── system/
    └── presentation/
        ├── screens/
        │   ├── offline_screen.dart                          # #60
        │   ├── maintenance_screen.dart                      # #61
        │   ├── permission_denied_screen.dart                # #62
        │   ├── not_found_screen.dart                        # #63
        │   ├── force_update_screen.dart                     # #64
        │   └── onboarding_tour_screen.dart                  # #65 (PageView, 3 slides)
        └── widgets/
```

---

## 4. Per‑feature internal layout

Every feature folder follows the same skeleton — but **layers are added only when needed**.

```
features/<feature>/
├── data/
│   ├── datasources/        # Dio calls hitting routes/api.php
│   ├── models/             # DTOs with fromJson/toJson — wrap shared entities when responses differ
│   └── repositories/       # impls of domain repositories
├── domain/
│   ├── entities/           # ONLY if the feature has entities not used elsewhere
│   ├── repositories/       # abstract contracts
│   └── usecases/           # single-method classes that orchestrate one user action
└── presentation/
    ├── controllers/        # state notifiers / blocs / providers
    ├── screens/            # one .dart file per screen ID from the Stitch prompt
    └── widgets/            # widgets used only inside this feature
```

Rules:
- **Don't create empty directories.** A `profile` feature with two screens doesn't need `data/datasources/` until it actually fetches its own data — it can reuse `users` repositories.
- **Each screen file** corresponds to exactly one numbered screen from the Stitch prompt; the prompt's screen ID is at the top of the file as a comment.
- **Cross‑feature reuse goes through `shared/`** — never import `features/a/...` from `features/b/...`. If you find yourself wanting to, hoist the shared part to `shared/widgets/` or `core/`.

---

## 5. Naming conventions

| Kind | Convention | Example |
|---|---|---|
| Folders | `lower_snake_case` | `features/kpi_tracking/` |
| Screen files | `<thing>_screen.dart` (or `_sheet.dart` for bottom sheets, `_dialog.dart` for dialogs) | `add_tracking_screen.dart`, `set_milestone_sheet.dart` |
| Widget files | `<thing>.dart` (no `_widget` suffix) | `kpi_tile.dart`, `status_badge.dart` |
| Controller files | `<feature>_controller.dart` or `<thing>_controller.dart` | `auth_controller.dart`, `tracking_form_controller.dart` |
| Repository contract | `<thing>_repository.dart` in `domain/repositories/` | `auth_repository.dart` |
| Repository impl | `<thing>_repository_impl.dart` in `data/repositories/` | `auth_repository_impl.dart` |
| Use cases | one verb per file, `<verb>_<noun>.dart` | `submit_tracking.dart`, `bulk_approve.dart` |
| Enums | `lower_snake_case` filename, `UpperCamel` type | `confirmation_status.dart` → `enum ConfirmationStatus` |
| Route names | `kebab-case` constants in `core/router/route_paths.dart` | `'/kpi/:id/tracking'` |

---

## 6. State management & routing — choices to make

The structure above is agnostic, but `controllers/` should land on one of:

- **Riverpod 2.x** (`StateNotifier` / `AsyncNotifier`) — recommended for greenfield, lightweight DI built in.
- **Bloc / Cubit** — heavier, more ceremony, but explicit and well‑tooled.
- **GetX** — fastest to start, but couples DI/routing/state in ways that hurt later.

Pair it with **GoRouter** for navigation (declarative, deep‑linkable — required for FCM push routing per §10 of the Stitch prompt).

---

## 7. Out of scope for this scaffold

These are intentionally **not** created by the prompt below; pull them in when you reach them:

- Tests (`test/features/<feature>/...` mirror) — should mirror the `lib/` tree but is added by the engineer writing the test.
- Localisation ARBs beyond a placeholder `app_en.arb`.
- CI/CD configuration.
- Platform‑specific overrides (`android/`, `ios/`).
- Firebase configuration files.

---

## 8. Claude Code prompt — paste this verbatim

The prompt below is self‑contained. Hand it to Claude Code from inside your Flutter project root (i.e. the directory containing `pubspec.yaml`). It will create the folders, generate empty‑but‑valid Dart stub files (each with a header comment naming the source screen), and leave the rest of the project untouched.

````markdown
You are scaffolding a feature-based folder structure inside an existing Flutter project. The mobile app is "PDCU e-Track47 Mobile" — a companion to a Laravel performance-tracking backend. The full design spec is in `docs/MOBILE_APP_STITCH_PROMPT.md` and the structural decisions are in `docs/MOBILE_APP_FEATURE_STRUCTURE.md` (read both before touching files).

## Pre-flight

1. Confirm the working directory contains a `pubspec.yaml` and a `lib/` directory. If not, stop and ask the user where the Flutter project lives.
2. Do NOT modify `pubspec.yaml`, `main.dart`, `android/`, `ios/`, `web/`, `linux/`, `macos/`, `windows/`, or any existing file outside `lib/`. If `lib/app.dart` doesn't exist, create it as a stub; if it does exist, leave it alone.
3. If any of the folders/files you are about to create already exist, skip them silently (do not overwrite).

## What to create — the full tree

Create the following directory tree under `lib/`. Every leaf `.dart` file should be created with the stub content described in the "Stub file content" section below.

```
lib/
├── app.dart                                        (stub only if missing)
├── core/
│   ├── api/
│   │   ├── api_client.dart
│   │   ├── auth_interceptor.dart
│   │   ├── error_interceptor.dart
│   │   └── api_endpoints.dart
│   ├── config/
│   │   └── env.dart
│   ├── di/
│   │   └── injector.dart
│   ├── error/
│   │   ├── failure.dart
│   │   └── exceptions.dart
│   ├── network/
│   │   └── connectivity_service.dart
│   ├── router/
│   │   ├── app_router.dart
│   │   ├── route_paths.dart
│   │   └── route_guards.dart
│   ├── storage/
│   │   ├── secure_storage_service.dart
│   │   └── local_cache_service.dart
│   ├── theme/
│   │   ├── app_colors.dart
│   │   ├── app_typography.dart
│   │   ├── app_spacing.dart
│   │   └── app_theme.dart
│   ├── localization/
│   │   └── l10n.dart
│   └── utils/
│       ├── formatters.dart
│       └── validators.dart
├── shared/
│   ├── models/
│   │   ├── framework.dart
│   │   ├── sector.dart
│   │   ├── commitment.dart
│   │   ├── deliverable.dart
│   │   ├── kpi.dart
│   │   ├── performance_tracking.dart
│   │   ├── kpi_target.dart
│   │   ├── attachment.dart
│   │   ├── user.dart
│   │   ├── user_role.dart
│   │   ├── notification.dart
│   │   └── confirmation_status.dart
│   ├── widgets/
│   │   ├── app_scaffold.dart
│   │   ├── primary_app_bar.dart
│   │   ├── bottom_nav.dart
│   │   ├── status_badge.dart
│   │   ├── performance_ring.dart
│   │   ├── performance_band_chip.dart
│   │   ├── kpi_tile.dart
│   │   ├── sector_card.dart
│   │   ├── commitment_card.dart
│   │   ├── quarter_selector.dart
│   │   ├── year_chip.dart
│   │   ├── sector_picker_sheet.dart
│   │   ├── evidence_chip.dart
│   │   ├── comment_row.dart
│   │   ├── empty_state.dart
│   │   ├── error_state.dart
│   │   ├── loading_skeleton.dart
│   │   ├── inline_alert.dart
│   │   ├── stepper.dart
│   │   └── bottom_sheet_scaffold.dart
│   └── extensions/
│       ├── context_extensions.dart
│       ├── datetime_extensions.dart
│       └── num_extensions.dart
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── datasources/auth_remote_datasource.dart
│   │   │   ├── models/auth_response_model.dart
│   │   │   └── repositories/auth_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/auth_repository.dart
│   │   │   └── usecases/
│   │   │       ├── sign_in.dart
│   │   │       ├── sign_out.dart
│   │   │       ├── change_password.dart
│   │   │       └── switch_role.dart
│   │   └── presentation/
│   │       ├── controllers/auth_controller.dart
│   │       └── screens/
│   │           ├── splash_screen.dart                       # screen #1
│   │           ├── welcome_screen.dart                      # screen #2
│   │           ├── sign_in_screen.dart                      # screen #3
│   │           ├── forced_password_change_screen.dart       # screen #4
│   │           ├── role_picker_screen.dart                  # screen #5
│   │           └── switch_role_sheet.dart                   # screen #58
│   │
│   ├── dashboard/
│   │   ├── data/
│   │   │   ├── datasources/dashboard_remote_datasource.dart
│   │   │   └── repositories/dashboard_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/dashboard_repository.dart
│   │   │   └── usecases/get_dashboard_stats.dart
│   │   └── presentation/
│   │       ├── controllers/dashboard_controller.dart
│   │       ├── screens/
│   │       │   ├── dashboard_shell_screen.dart
│   │       │   ├── governor_dashboard_screen.dart           # screen #6
│   │       │   ├── coordinator_dashboard_screen.dart        # screen #7
│   │       │   ├── sector_head_dashboard_screen.dart        # screen #8
│   │       │   ├── data_admin_dashboard_screen.dart         # screen #9
│   │       │   ├── facilitator_dashboard_screen.dart        # screen #10
│   │       │   └── system_admin_dashboard_screen.dart       # screen #11
│   │       └── widgets/
│   │           ├── scorecard.dart
│   │           ├── kpi_status_donut.dart
│   │           ├── sector_comparison_bar_chart.dart
│   │           └── deadline_card.dart
│   │
│   ├── sectors/
│   │   ├── data/
│   │   │   ├── datasources/sectors_remote_datasource.dart
│   │   │   └── repositories/sectors_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/sectors_repository.dart
│   │   │   └── usecases/
│   │   │       ├── list_sectors.dart
│   │   │       ├── get_sector_overview.dart
│   │   │       ├── get_commitment_detail.dart
│   │   │       └── get_deliverable_detail.dart
│   │   └── presentation/
│   │       ├── controllers/sectors_controller.dart
│   │       ├── screens/
│   │       │   ├── sectors_list_screen.dart                 # screen #17
│   │       │   ├── sector_overview_screen.dart              # screen #18
│   │       │   ├── commitment_detail_screen.dart            # screen #20
│   │       │   ├── deliverable_detail_screen.dart           # screen #21
│   │       │   └── kpi_quick_view_sheet.dart                # screen #22
│   │       └── widgets/
│   │           └── commitment_card.dart                     # screen #19
│   │
│   ├── kpi_tracking/
│   │   ├── data/
│   │   │   ├── datasources/tracking_remote_datasource.dart
│   │   │   ├── models/tracking_request_model.dart
│   │   │   └── repositories/tracking_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/tracking_repository.dart
│   │   │   └── usecases/
│   │   │       ├── set_milestone.dart
│   │   │       ├── submit_tracking.dart
│   │   │       ├── save_draft.dart
│   │   │       ├── upload_evidence.dart
│   │   │       └── get_kpi_tracking_year.dart
│   │   └── presentation/
│   │       ├── controllers/tracking_form_controller.dart
│   │       ├── screens/
│   │       │   ├── set_milestone_sheet.dart                 # screen #23
│   │       │   ├── add_tracking_screen.dart                 # screen #24
│   │       │   ├── kpi_tracking_detail_screen.dart          # screen #28
│   │       │   └── attachment_preview_screen.dart           # screen #29
│   │       └── widgets/
│   │           ├── tracking_form.dart
│   │           ├── evidence_picker.dart
│   │           └── quarter_tracking_card.dart
│   │
│   ├── approvals/
│   │   ├── data/
│   │   │   ├── datasources/approvals_remote_datasource.dart
│   │   │   └── repositories/approvals_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/approvals_repository.dart
│   │   │   └── usecases/
│   │   │       ├── list_awaiting.dart
│   │   │       ├── sector_head_bulk_approve.dart
│   │   │       ├── facilitator_decide.dart
│   │   │       └── coordinator_verify.dart
│   │   └── presentation/
│   │       ├── controllers/approvals_controller.dart
│   │       ├── screens/
│   │       │   ├── sector_head_queue_screen.dart            # screen #12
│   │       │   ├── facilitator_queue_screen.dart            # screen #13
│   │       │   ├── facilitator_review_screen.dart           # screens #14 + #26
│   │       │   ├── coordinator_final_queue_screen.dart      # screen #15
│   │       │   ├── data_admin_queue_screen.dart             # screen #16
│   │       │   ├── bulk_sector_head_approval_screen.dart    # screen #25
│   │       │   └── coordinator_verify_screen.dart           # screen #27
│   │       └── widgets/
│   │           ├── approval_row.dart
│   │           ├── decision_buttons.dart
│   │           └── previous_review_section.dart
│   │
│   ├── reports/
│   │   ├── data/
│   │   │   ├── datasources/reports_remote_datasource.dart
│   │   │   └── repositories/reports_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/reports_repository.dart
│   │   │   └── usecases/
│   │   │       ├── get_reports_summary.dart
│   │   │       ├── generate_comprehensive_report.dart
│   │   │       └── generate_word_report.dart
│   │   └── presentation/
│   │       ├── controllers/reports_controller.dart
│   │       └── screens/
│   │           ├── reports_hub_screen.dart                  # screen #30
│   │           ├── comprehensive_setup_screen.dart          # screen #31
│   │           ├── comprehensive_viewer_screen.dart         # screen #32
│   │           ├── word_report_generator_screen.dart        # screen #33
│   │           └── print_preview_screen.dart                # screen #34
│   │
│   ├── frameworks/
│   │   ├── data/
│   │   │   ├── datasources/frameworks_remote_datasource.dart
│   │   │   └── repositories/frameworks_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/frameworks_repository.dart
│   │   │   └── usecases/
│   │   │       ├── list_frameworks.dart
│   │   │       ├── create_framework.dart
│   │   │       ├── archive_framework.dart
│   │   │       ├── activate_framework.dart
│   │   │       └── inherit_framework.dart
│   │   └── presentation/
│   │       ├── controllers/frameworks_controller.dart
│   │       └── screens/
│   │           ├── frameworks_list_screen.dart              # screen #35
│   │           ├── create_framework_screen.dart             # screen #36
│   │           ├── framework_detail_screen.dart             # screen #37
│   │           └── confirm_inherit_framework_screen.dart    # screen #38
│   │
│   ├── data_entry_windows/
│   │   ├── data/
│   │   │   ├── datasources/windows_remote_datasource.dart
│   │   │   └── repositories/windows_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/windows_repository.dart
│   │   │   └── usecases/
│   │   │       ├── lock_all_windows.dart
│   │   │       ├── unlock_all_windows.dart
│   │   │       └── grant_sector_override.dart
│   │   └── presentation/
│   │       ├── controllers/windows_controller.dart
│   │       ├── screens/
│   │       │   └── data_entry_management_screen.dart        # screen #39
│   │       └── widgets/
│   │           └── grant_override_sheet.dart                # screen #39 sub-sheet
│   │
│   ├── users/
│   │   ├── data/
│   │   │   ├── datasources/users_remote_datasource.dart
│   │   │   └── repositories/users_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/users_repository.dart
│   │   │   └── usecases/
│   │   │       ├── list_users.dart
│   │   │       ├── create_user.dart
│   │   │       ├── update_role.dart
│   │   │       ├── revoke_role.dart
│   │   │       ├── reactivate_role.dart
│   │   │       ├── change_user_password.dart
│   │   │       └── update_user_photo.dart
│   │   └── presentation/
│   │       ├── controllers/users_controller.dart
│   │       └── screens/
│   │           ├── users_list_screen.dart                   # screen #40
│   │           ├── add_user_step_basics_screen.dart         # screen #41 step 1
│   │           ├── add_user_step_role_screen.dart           # screen #41 step 2
│   │           ├── add_user_step_avatar_screen.dart         # screen #41 step 3
│   │           ├── user_profile_screen.dart                 # screen #42
│   │           ├── change_password_screen.dart              # screen #43
│   │           ├── update_photo_screen.dart                 # screen #44
│   │           └── security_log_screen.dart                 # screen #59
│   │
│   ├── gallery/
│   │   ├── data/
│   │   │   ├── datasources/gallery_remote_datasource.dart
│   │   │   └── repositories/gallery_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/gallery_repository.dart
│   │   │   └── usecases/
│   │   │       ├── list_gallery.dart
│   │   │       ├── upload_gallery_image.dart
│   │   │       └── delete_gallery_image.dart
│   │   └── presentation/
│   │       ├── controllers/gallery_controller.dart
│   │       └── screens/
│   │           ├── gallery_management_screen.dart           # screen #45
│   │           ├── gallery_upload_screen.dart               # screen #46
│   │           ├── public_gallery_screen.dart               # screen #47
│   │           └── public_gallery_detail_screen.dart        # screen #48
│   │
│   ├── discussions/
│   │   ├── data/
│   │   │   ├── datasources/discussions_remote_datasource.dart
│   │   │   └── repositories/discussions_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/discussions_repository.dart
│   │   │   └── usecases/
│   │   │       ├── list_threads.dart
│   │   │       ├── post_comment.dart
│   │   │       └── reply_to_comment.dart
│   │   └── presentation/
│   │       ├── controllers/discussions_controller.dart
│   │       └── screens/
│   │           ├── sector_discussion_screen.dart            # screen #49
│   │           ├── project_comments_screen.dart             # screen #50
│   │           └── thread_detail_screen.dart                # screen #51
│   │
│   ├── notifications/
│   │   ├── data/
│   │   │   ├── datasources/notifications_remote_datasource.dart
│   │   │   ├── fcm_service.dart
│   │   │   └── repositories/notifications_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── repositories/notifications_repository.dart
│   │   │   └── usecases/
│   │   │       ├── list_notifications.dart
│   │   │       ├── mark_read.dart
│   │   │       └── save_fcm_token.dart
│   │   └── presentation/
│   │       ├── controllers/notifications_controller.dart
│   │       ├── screens/
│   │       │   ├── notifications_inbox_screen.dart          # screen #52
│   │       │   └── notification_preferences_screen.dart     # screen #53
│   │       └── widgets/
│   │           └── notification_row.dart
│   │
│   ├── profile/
│   │   └── presentation/
│   │       ├── controllers/profile_controller.dart
│   │       └── screens/
│   │           └── my_profile_screen.dart                   # screen #54
│   │
│   └── settings/
│       └── presentation/
│           ├── controllers/settings_controller.dart
│           └── screens/
│               ├── settings_screen.dart                     # screen #55
│               ├── help_support_screen.dart                 # screen #56
│               └── about_screen.dart                        # screen #57
│
└── system/
    └── presentation/
        └── screens/
            ├── offline_screen.dart                          # screen #60
            ├── maintenance_screen.dart                      # screen #61
            ├── permission_denied_screen.dart                # screen #62
            ├── not_found_screen.dart                        # screen #63
            ├── force_update_screen.dart                     # screen #64
            └── onboarding_tour_screen.dart                  # screen #65
```

## Stub file content

For every `.dart` file you create, use a compilable stub. Choose the template by file type — never leave a file empty.

### Screen files (filenames ending in `_screen.dart`)

```dart
// Stitch screen ID: <NUMBER>
// Source: docs/MOBILE_APP_STITCH_PROMPT.md §<SECTION> "<SCREEN TITLE>"
// TODO: implement per the Stitch design.

import 'package:flutter/material.dart';

class <PascalCaseName>Screen extends StatelessWidget {
  const <PascalCaseName>Screen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(child: Text('<PascalCaseName>Screen — not yet implemented')),
    );
  }
}
```

Derive `<PascalCaseName>` from the filename minus the `_screen` suffix (`add_tracking_screen.dart` → `AddTrackingScreen`). Look up `<NUMBER>` and `<SECTION>` in the tree above and in `docs/MOBILE_APP_STITCH_PROMPT.md`.

### Sheet files (filenames ending in `_sheet.dart`)

Same as above but use `<PascalCaseName>Sheet` and return:

```dart
return const Padding(
  padding: EdgeInsets.all(16),
  child: Text('<PascalCaseName> — not yet implemented'),
);
```

### Widget files (in `widgets/` directories)

```dart
import 'package:flutter/material.dart';

/// TODO: implement per the Stitch design system §2.4.
class <PascalCaseName> extends StatelessWidget {
  const <PascalCaseName>({super.key});

  @override
  Widget build(BuildContext context) {
    return const SizedBox.shrink();
  }
}
```

### Controller files (filenames ending in `_controller.dart`)

```dart
// TODO: replace ChangeNotifier with the chosen state-management primitive
// (Riverpod AsyncNotifier / Bloc / Cubit) once the team picks one.

import 'package:flutter/foundation.dart';

class <PascalCaseName>Controller extends ChangeNotifier {
  // state and methods go here
}
```

### Repository abstract (in `domain/repositories/`)

```dart
abstract class <PascalCaseName>Repository {
  // contracts go here
}
```

### Repository impl (in `data/repositories/`, filename ends with `_repository_impl.dart`)

```dart
import '../../domain/repositories/<thing>_repository.dart';

class <PascalCaseName>RepositoryImpl implements <PascalCaseName>Repository {
  // implementation goes here
}
```

(Strip `_impl` from the filename when computing the import path.)

### Use case files (in `domain/usecases/`)

```dart
class <PascalCaseName> {
  // call(...) goes here
}
```

### Data source files (in `data/datasources/`)

```dart
abstract class <PascalCaseName> {
  // API methods go here
}
```

### Model files (in `shared/models/` or `data/models/`)

```dart
class <PascalCaseName> {
  const <PascalCaseName>();
  // fields, fromJson, toJson go here
}
```

For `confirmation_status.dart` specifically, use this content (it's an enum referenced from the Stitch prompt §1):

```dart
enum ConfirmationStatus {
  notConfirmed,
  pendingSectorHeadApproval,
  pendingFacilitator,
  pendingCoordinator,
  confirmed,
  rejected,
}
```

### Core/shared utility files

For anything in `core/` or `shared/extensions/` that doesn't fit the templates above, create a single-line comment stub:

```dart
// TODO: implement.
```

### `app.dart` (only if it does not already exist)

```dart
import 'package:flutter/material.dart';

class App extends StatelessWidget {
  const App({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'PDCU e-Track47',
      home: const Scaffold(
        body: Center(child: Text('App shell — wire up GoRouter and theme.')),
      ),
    );
  }
}
```

## After scaffolding

1. Run `flutter analyze` to confirm the project still compiles. If errors appear, report them — do not silently change other files to make them go away.
2. Print a tree summary of what you created (folder count and file count, plus any pre-existing files you skipped).
3. Do NOT run `flutter pub get`, `flutter pub add`, install dependencies, or modify `pubspec.yaml` — picking state management, routing, networking, and storage libraries is a separate decision the team will make.

## Constraints

- Use the Edit/Write tools — no shell `mkdir`/`touch` loops.
- Do not create test files, ARB files, CI configs, or platform folders.
- Do not import packages that aren't in the existing `pubspec.yaml`.
- Each created file MUST be valid Dart — i.e. compilable on its own with just the imports it declares.
````

---

## 9. Summary

- **13 features** + a `system/` group cover all 65 designed screens.
- **One feature per business capability** — not per role, not per route, not per visual section.
- **`approvals` deliberately spans roles** because the workflow is one domain, not four.
- **Shared widgets and models live in `shared/`**; framework plumbing lives in `core/`. Features never import from one another.
- **Each feature follows the same three‑layer skeleton** (`data` / `domain` / `presentation`), but layers are added only when the feature needs them.
- **Every screen file ties back to a numbered screen** in `MOBILE_APP_STITCH_PROMPT.md` via a header comment, so designers and engineers stay aligned.
- The §8 prompt is paste‑ready for Claude Code: it scaffolds the entire tree, leaves existing files alone, and produces compilable stubs that won't break `flutter analyze`.
