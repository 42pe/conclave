# Conclave Forum — Implementation Plan

## Terminology
- **Topics** — Admin-defined categories (predefined)
- **Discussions** — User-created posts within a Topic
- **Replies** — User-created responses to a Discussion (nested, max 3 levels)

## Overview

Hierarchy: **Topics > Discussions > Replies**

16 phases, each independently deployable with its own migrations, models, controllers, pages, factories, seeders, Pest tests, and Playwright E2E tests.

---

## Local Development Environment

This project runs locally inside a **DDEV container**. All `php`, `composer`, and `artisan` commands must be prefixed with `ddev` when executed on the host machine.

| Standard Command | DDEV Equivalent |
|-----------------|-----------------|
| `php artisan ...` | `ddev php artisan ...` |
| `composer require ...` | `ddev composer require ...` |
| `vendor/bin/pint ...` | `ddev exec vendor/bin/pint ...` |
| `php artisan test ...` | `ddev php artisan test ...` |
| `npm run build` | `npm run build` (runs on host, not in container) |
| `npm run dev` | `npm run dev` (runs on host, not in container) |
| `npx playwright test` | `npx playwright test` (runs on host, not in container) |

**Note:** `npm` commands run on the host machine, not inside the DDEV container. Only PHP/Composer commands need the `ddev` prefix.

---

## Agent Roles

Each phase is executed by a team of specialized agents working in parallel where possible:

### Senior Engineer (Supervisor)
- **Role:** Technical lead and code reviewer
- **Responsibilities:**
  - Reviews implementation approach before code is written
  - Validates architectural decisions align with existing codebase patterns
  - Reviews completed code for quality, security, and adherence to Laravel/React conventions
  - Ensures each phase integrates cleanly with previous phases
  - Signs off on phase completion before moving to the next
  - Resolves blockers and makes design trade-off decisions

### Senior Developer (Fullstack Implementation)
- **Role:** Primary code author for backend and frontend
- **Responsibilities:**
  - Writes all migrations, models, controllers, form requests, policies, and observers (backend)
  - Writes all React pages, components, layouts, and TypeScript types (frontend)
  - Creates factories and seeders for each phase
  - Follows existing codebase conventions (Inertia patterns, Wayfinder routes, shadcn components, Pest factories)
  - Runs `ddev exec vendor/bin/pint --dirty --format agent` after PHP changes
  - Runs `npm run build` and `npm run types` to verify frontend compiles

### QA Engineer (Testing)
- **Role:** Test author and quality gatekeeper
- **Responsibilities:**
  - Writes Pest unit and feature tests for all backend logic (policies, validation, CRUD, edge cases)
  - Writes Playwright E2E tests for all user-facing flows
  - Maintains test fixtures (auth helpers, database seeders for tests)
  - Runs full test suites after each phase: `ddev php artisan test --compact` and `npx playwright test`
  - Reports test failures and regressions to the Senior Engineer
  - Ensures test coverage for authorization, role-based access, and error states

### Instrumentation Engineer (Logging & Analytics)
- **Role:** Observability and event tracking specialist
- **Responsibilities:**
  - Implements PostHog server-side event tracking in controllers/actions (Phase 10 primary, but reviewed in earlier phases)
  - Implements PostHog client-side tracking via `posthog-js` (pageviews, UI interactions)
  - Adds structured logging for critical operations (user moderation, content changes)
  - Reviews each phase for trackable events and prepares event specifications
  - Ensures proper user identification in PostHog (identify calls on login/register)
  - Creates PostHog dashboards and insights for forum metrics
  - Implements email notification system (Phase 10)

### Product Owner (Browser Reviewer)
- **Role:** Visual verification and user experience gatekeeper
- **Responsibilities:**
  - Opens the running app via chrome-devtools MCP (screenshots, navigation, clicks, form fills)
  - Verifies each feature works visually — layout, spacing, responsiveness, correct data display
  - Tests happy-path user flows end-to-end in the browser (not just automated tests)
  - Checks error states and edge cases render correctly (validation errors, empty states, deleted users, locked discussions)
  - Verifies UI regressions from previous phases haven't been introduced
  - Checks loading states, skeleton placeholders, and animations render properly
  - Must approve the phase before Senior Engineer gives final technical sign-off
  - Reports visual bugs, UX issues, and broken interactions back to the Senior Developer

### Phase Execution Flow
```
For each phase:
  1. Senior Engineer      → Reviews phase requirements, confirms approach
  2. Senior Developer     → Implements backend + frontend code
  3. QA Engineer           → Writes and runs Pest + Playwright tests (can start in parallel once interfaces are defined)
  4. Instrumentation Eng  → Reviews for tracking opportunities, adds event specs
  5. Product Owner         → Browser review: verifies features visually, tests user flows
  6. Senior Engineer       → Final technical review and phase sign-off
```

---

## Frontend UI Strategy

- **shadcn/ui** is the primary component library. The project already uses shadcn-style Radix UI components in `resources/js/components/ui/`. Always check for an existing shadcn component before building a custom one (e.g., `Dialog`, `DropdownMenu`, `Select`, `Sheet`, `Badge`, `Avatar`, `Skeleton`, `Tooltip`, `Button`, `Input`, `Label`, `Checkbox`).
- A **shadcn MCP server** is available to Claude for discovering and installing components. Use it to look up component APIs, check available variants, and install new components into the project.
- Install additional shadcn components as needed (e.g., `Table`, `Tabs`, `Pagination`, `Command`, `Popover`, `Card`, `Textarea`, `AlertDialog`, `Separator`, `ScrollArea`).
- **Motion Primitives** ([ibelick/motion-primitives](https://github.com/ibelick/motion-primitives)) for polished animations and micro-interactions. Use for: page transitions, skeleton loading states, list animations (discussion/reply lists), modal enter/exit, toast notifications, and interactive UI feedback.
- Prefer composing shadcn + Motion Primitives over writing custom styled components with raw Tailwind. Only create custom components when no suitable shadcn component exists.
- All custom components should follow the existing pattern in `resources/js/components/ui/` — use `cn()` utility, support `className` prop, use CVA for variants where appropriate.

---

## Development Data Seeders

Each phase includes a dedicated **Development Seeder** that populates the database with realistic data for manual review, browser testing, and Product Owner sign-off. All dev seeders live in `database/seeders/Development/` and are called from `DevelopmentSeeder`, which is invoked via `DatabaseSeeder` in non-production environments.

**Run:** `ddev php artisan db:seed` (or `ddev php artisan migrate:fresh --seed` for a clean reset)

**Login credentials for all seeded users:** password = `password`

### Per-Phase Seeder Responsibilities

| Phase | Seeder | Seeds |
|-------|--------|-------|
| 1 | `UserSeeder` | Admin, moderator, regular users (with bios, preferred names), suspended user, deleted user, unverified user |
| 2 | `UserSeeder` (update) | Users with avatars, varied privacy settings |
| 3 | `LocationSeeder` + `TopicSeeder` | US states/countries + sample topics (public, private, restricted) with icons/descriptions |
| 4 | — | Media records seeded alongside discussions in Phase 5 |
| 5 | `DiscussionSeeder` | Discussions across topics with Slate.js content, pinned discussions, varied locations |
| 6 | `ReplySeeder` | Nested replies (depth 0, 1, 2) across discussions, replies from different users |
| 7 | — | User profiles already populated from Phase 1-2 seeders |
| 8 | — | Suspended/deleted/banned users already seeded from Phase 1; `BannedEmailSeeder` for banned email list |
| 9 | `ConversationSeeder` | Conversations between users with multiple messages, unread state |
| 10 | — | Notification preferences set on seeded users |
| 11 | — | No new seeders (dashboard uses existing data) |
| 12 | `UserSeeder` (update) | Set `notify_mentions` preferences on some users |
| 13 | `NotificationSeeder` | Sample database notifications for dev users |
| 14 | `LikeSeeder` | Scatter likes across discussions and replies from various users |
| 15 | `BookmarkSeeder` | Bookmark some discussions for dev users |

### Seeder Design Principles
- All seeded users use `password` as their password for easy login
- Named users with predictable usernames/emails for quick access (e.g., `admin@example.com`, `moderator@example.com`)
- Realistic content — bios, discussion titles, and reply text should feel plausible, not lorem ipsum
- Cover edge cases visually: long usernames, long bios, empty optional fields, deleted users mixed with active content

---

## Testing Strategy

- **Pest** — Unit & feature tests (backend logic, policies, validation, API behavior)
- **Playwright** — E2E tests in `e2e-tests/` folder (user flows, UI interactions, cross-page navigation)

### Initial Setup (before Phase 1)

**Motion Primitives Setup:**
1. Install: `npm install motion-primitives` (or follow repo instructions)
2. Available for use in all phases that build UI

**Playwright E2E Setup:**
1. Create `e2e-tests/` subfolder at project root
2. Initialize Playwright inside it: `cd e2e-tests && npm init playwright@latest`
3. Configure `playwright.config.ts` to:
   - Target local Laravel dev server (e.g., `http://localhost:8000`)
   - Use `webServer` config to optionally start the app
   - Set `testDir: './tests'`
   - Configure projects for Chromium (primary), optionally Firefox/WebKit
4. Create base test fixtures:
   - `e2e-tests/tests/fixtures/auth.ts` — login helpers (authenticate as user/admin/moderator)
   - `e2e-tests/tests/fixtures/database.ts` — seed/reset helpers via artisan commands
5. Add MCP server for Playwright (chrome-devtools is already configured in `.mcp.json` — use this for browser debugging alongside Playwright)
6. Add `e2e-tests/node_modules` to `.gitignore`

**`e2e-tests/` folder structure:**
```
e2e-tests/
  package.json
  playwright.config.ts
  tests/
    fixtures/
      auth.ts
      database.ts
    auth/
    settings/
    admin/
    forum/
    users/
    messages/
    editor/
```

---

## Database Schema

### Users Table Extensions (modify existing)
```
username          VARCHAR(40) UNIQUE NOT NULL
first_name        VARCHAR(100) NULLABLE
last_name         VARCHAR(100) NULLABLE
preferred_name    VARCHAR(100) NULLABLE
bio               TEXT NULLABLE
avatar_path       VARCHAR(500) NULLABLE
role              VARCHAR(20) DEFAULT 'user'        -- admin | moderator | user
is_deleted        BOOLEAN DEFAULT FALSE
is_suspended      BOOLEAN DEFAULT FALSE
deleted_at        TIMESTAMP NULLABLE
show_real_name    BOOLEAN DEFAULT TRUE
show_email        BOOLEAN DEFAULT FALSE
show_in_directory BOOLEAN DEFAULT TRUE
```

### locations
```
id, name VARCHAR(255), iso_code VARCHAR(10) UNIQUE, type VARCHAR(20),
is_active BOOLEAN DEFAULT TRUE, sort_order INT DEFAULT 0, timestamps
-- Types: any, us_state, country
-- Seeded: "Any" (ANY), 50 US states (US-CA, US-TX...), Canada (CA), Mexico (MX)
```

### topics
```
id, title VARCHAR(255), slug VARCHAR(255) UNIQUE, description TEXT NULLABLE,
icon VARCHAR(100) NULLABLE, header_image_path VARCHAR(500) NULLABLE,
visibility VARCHAR(20) DEFAULT 'public',  -- public | private | restricted
sort_order INT DEFAULT 0, created_by FK(users), timestamps
```

### discussions
```
id, topic_id FK(topics) CASCADE, user_id FK(users) SET NULL,
location_id FK(locations) NULLABLE, title VARCHAR(255), slug VARCHAR(255),
body JSON NOT NULL,  -- Slate.js document
is_pinned BOOLEAN DEFAULT FALSE, is_locked BOOLEAN DEFAULT FALSE,
reply_count INT DEFAULT 0, last_reply_at TIMESTAMP NULLABLE, timestamps
-- UNIQUE(topic_id, slug)
```

### replies (adjacency list + depth column)
```
id, discussion_id FK(discussions) CASCADE, user_id FK(users) SET NULL,
parent_id FK(replies) CASCADE NULLABLE,  -- NULL = top-level reply
depth TINYINT DEFAULT 0,                 -- 0, 1, 2 (max 3 levels)
body JSON NOT NULL, timestamps
-- CHECK: depth <= 2
```

### media (polymorphic)
```
id, user_id FK(users) SET NULL, mediable_type VARCHAR(255), mediable_id BIGINT,
disk VARCHAR(50) DEFAULT 'public', path VARCHAR(500), original_name VARCHAR(255),
mime_type VARCHAR(100), size BIGINT, timestamps
-- Storage path: uploads/{user_id}/{year}/{month}/{uuid}.{ext}
```

### banned_emails
```
id, email VARCHAR(255) UNIQUE, user_id FK(users) NULLABLE,
banned_by FK(users), reason TEXT NULLABLE, timestamps
```

### conversations
```
id, timestamps
```

### conversation_participants
```
id, conversation_id FK(conversations) CASCADE, user_id FK(users) CASCADE,
last_read_at TIMESTAMP NULLABLE, timestamps
-- UNIQUE(conversation_id, user_id)
```

### messages
```
id, conversation_id FK(conversations) CASCADE, user_id FK(users) SET NULL,
body JSON NOT NULL, timestamps
```

---

## Key Architectural Decisions

**Nested Replies:** Adjacency list with `parent_id` + `depth` column. With max 3 levels, this is simpler than materialized path or closure table. Depth validated on creation.

**Slate.js Storage:** Store raw JSON document in a `JSON` column. Render client-side with a read-only `<SlateRenderer>`. Validate structure server-side with a custom `SlateDocument` rule.

**Deleted Users:** Custom `is_deleted` flag (not Laravel SoftDeletes). On deletion: anonymize personal info, disable login, keep content intact. A `displayName` accessor returns "Deleted User" when flagged. No global scope — deleted users remain in relationships so content displays correctly. The directory explicitly filters them out.

**Media Uploads:** Upload immediately from Slate.js editor to `POST /media/upload`. Store in `public` disk at `uploads/{user_id}/{year}/{month}/{uuid}.{ext}`. Associate with the mediable (Discussion/Reply/Message) on save. Scheduled cleanup for orphaned uploads after 24h.

**PostHog:** Dual approach. Client-side `posthog-js` for pageviews/UI interactions. Server-side `posthog/posthog-php` for business events (discussion_created, reply_created, etc.).

**Private Messaging:** Conversations-based model. Check for existing conversation between two users before creating new. `last_read_at` per participant for unread tracking.

**Topic Visibility:** Public = no auth, Private = auth required, Restricted = admin/mod only. Enforced via policies and middleware.

---

## Phase 1: User Model Extensions & Role System

**Goal:** Extend User with forum fields, add role enum, update registration and profile settings.

**Agent assignments:**
- **Senior Developer** → Migration, User model changes, Fortify updates, frontend profile/register pages
- **QA Engineer** → Pest tests for profile/registration, Playwright E2E for registration + profile flow
- **Instrumentation Engineer** → Spec event: `user_registered` (with username), review user identification setup
- **Senior Engineer** → Review User model design, validate role enum approach, sign off
- **Product Owner** → Verify registration flow with username field, verify profile settings page displays and saves all new fields correctly

### Backend
- Migration: `add_forum_columns_to_users_table`
- `app/Enums/UserRole.php` — enum: Admin, Moderator, User
- Update `app/Models/User.php` — new fillable, casts (role → UserRole, is_deleted/is_suspended → boolean), `displayName` accessor, `isAdmin()`, `isModerator()`, `isAdminOrModerator()` helpers
- Update `app/Concerns/ProfileValidationRules.php` — add username rules
- Update `database/factories/UserFactory.php` — new fields, `admin()`, `moderator()`, `deleted()`, `suspended()` states
- Update `database/seeders/DatabaseSeeder.php` — call `DevelopmentSeeder` in non-production
- Create `database/seeders/DevelopmentSeeder.php` — orchestrates all dev seeders
- Create `database/seeders/UserSeeder.php` — admin, moderator, regular users (with varied profiles), suspended user, deleted user, unverified user
- Update `app/Actions/Fortify/CreateNewUser.php` — accept username
- Update `app/Http/Requests/Settings/ProfileUpdateRequest.php` — new fields
- Update `app/Http/Controllers/Settings/ProfileController.php` — handle new fields

### Frontend
- Update `resources/js/types/auth.ts` — expand User type
- Update `resources/js/pages/settings/profile.tsx` — username, first/last name, preferred name, bio fields
- Update `resources/js/pages/auth/register.tsx` — username field
- Create `resources/js/components/user-display.tsx` — shared component handling deleted user display

### Pest Tests
- Update `tests/Feature/Settings/ProfileUpdateTest.php`
- Update `tests/Feature/Auth/RegistrationTest.php`

### Playwright E2E Tests
- `e2e-tests/tests/auth/registration.spec.ts` — register with username, verify profile fields
- `e2e-tests/tests/settings/profile.spec.ts` — update profile fields (username, names, bio), verify changes persist

| # | File | Test | Description |
|---|------|------|-------------|
| 1.1 | `registration.spec.ts` | Registration includes username field | Verify username input is present on `/register` |
| 1.2 | `registration.spec.ts` | Registration rejects duplicate username | Register, then try same username again — error shown |
| 1.3 | `profile.spec.ts` | Profile settings shows extended fields | `/settings/profile` has first name, last name, preferred name, bio, username |
| 1.4 | `profile.spec.ts` | Profile settings saves extended fields | Fill all fields, save, reload — values persist |

*Already covered by existing tests:* Basic registration (valid + validation errors), profile update + persistence, invalid username.

### Key files modified
- `app/Models/User.php` · `resources/js/types/auth.ts` · `database/factories/UserFactory.php`

---

## Phase 2: Avatar Upload & Privacy Settings

**Goal:** Avatar upload, privacy preferences UI.

**Agent assignments:**
- **Senior Developer** → Avatar upload controller/storage, privacy controller, frontend pages
- **QA Engineer** → Pest tests for upload validation/privacy, Playwright E2E for avatar + privacy flows
- **Instrumentation Engineer** → Spec events: `avatar_uploaded`, `privacy_settings_changed`
- **Senior Engineer** → Review file storage approach, validate privacy toggle design
- **Product Owner** → Verify avatar upload/preview/removal flow, verify privacy toggles persist and affect public profile visibility

### Backend
- `app/Http/Controllers/Settings/AvatarController.php` — store/destroy
- `app/Http/Requests/Settings/AvatarUploadRequest.php` — validate image (max 2MB, jpg/png/webp)
- `app/Http/Controllers/Settings/PrivacyController.php` — update privacy prefs
- `app/Http/Requests/Settings/PrivacyUpdateRequest.php`
- Storage path: `avatars/{user_id}/{uuid}.{ext}` on public disk
- Routes added to `routes/settings.php`

### Frontend
- `resources/js/pages/settings/privacy.tsx` — privacy toggles (show real name, show email, appear in directory)
- `resources/js/components/avatar-upload.tsx` — upload component with preview
- Update `resources/js/pages/settings/profile.tsx` — integrate avatar upload
- Update `resources/js/layouts/settings/layout.tsx` — add Privacy nav item

### Pest Tests
- `tests/Feature/Settings/AvatarUploadTest.php`
- `tests/Feature/Settings/PrivacyUpdateTest.php`

### Playwright E2E Tests
- `e2e-tests/tests/settings/avatar.spec.ts` — upload avatar, verify display in sidebar/profile
- `e2e-tests/tests/settings/privacy.spec.ts` — toggle privacy settings, verify directory visibility

| # | File | Test | Description |
|---|------|------|-------------|
| 2.1 | `avatar.spec.ts` | Can upload avatar | Go to profile settings, upload image, verify preview appears |
| 2.2 | `avatar.spec.ts` | Avatar appears in sidebar after upload | Upload avatar, check sidebar NavUser shows avatar image |
| 2.3 | `avatar.spec.ts` | Can remove avatar | Upload avatar, click remove, verify fallback initials shown |
| 2.4 | `privacy.spec.ts` | Privacy settings page loads | Navigate to `/settings/privacy`, verify toggles present |
| 2.5 | `privacy.spec.ts` | Privacy settings persist | Toggle settings off, save, reload — settings remain off |
| 2.6 | `privacy.spec.ts` | Notification preferences load | Verify notify_replies, notify_messages toggles exist |
| 2.7 | `avatar.spec.ts` | Avatar displays on public profile | Upload avatar, visit `/users/{username}` — avatar visible on profile page |
| 2.8 | `avatar.spec.ts` | Avatar upload rejects invalid file | Upload oversized/wrong-type file — validation error shown |

---

## Phase 3: Locations & Topics (Admin Foundation)

**Goal:** Location seeder, Topics CRUD for admins, admin middleware/gates.

**Agent assignments:**
- **Senior Developer** → Models, migrations, seeders, admin CRUD controllers, admin layout + topic pages
- **QA Engineer** → Pest tests for admin authorization + topic CRUD, Playwright E2E for admin topic management + public listing
- **Instrumentation Engineer** → Spec events: `topic_created`, `topic_updated`, `topic_visibility_changed`
- **Senior Engineer** → Review admin gate/policy approach, validate location ISO codes, review slug generation
- **Product Owner** → Verify admin topic CRUD pages (create/edit/delete), verify topic listing on homepage, verify visibility enforcement (guest vs logged-in vs admin)

### Backend
- Migrations: `create_locations_table`, `create_topics_table`
- `app/Models/Location.php` — scopes: `active()`, `byType()`
- `app/Models/Topic.php` — relationships, slug generation, visibility scope
- `app/Enums/TopicVisibility.php` — Public, Private, Restricted
- `app/Enums/LocationType.php` — Any, UsState, Country
- `database/seeders/LocationSeeder.php` — "Any" + 50 US states + Canada + Mexico
- `database/factories/TopicFactory.php`
- Register `admin` Gate in `AppServiceProvider`
- `app/Http/Controllers/Admin/TopicController.php` — full CRUD
- `app/Http/Requests/Admin/StoreTopicRequest.php`, `UpdateTopicRequest.php`
- Route file: `routes/admin.php` (required from `web.php`)

### Frontend
- `resources/js/layouts/admin-layout.tsx`
- `resources/js/pages/admin/topics/index.tsx` — list topics (shadcn `Table`, `Badge` for visibility)
- `resources/js/pages/admin/topics/create.tsx` — form with icon picker, image upload, visibility `Select`
- `resources/js/pages/admin/topics/edit.tsx`
- Conditionally add Admin links to sidebar for admin users
- **shadcn components to install:** `Table`, `Card`, `Tabs`, `AlertDialog`, `Separator`

### Pest Tests
- `tests/Feature/Admin/TopicManagementTest.php`
- `tests/Unit/Models/TopicTest.php`
- `tests/Unit/Models/LocationTest.php`

### Playwright E2E Tests
- `e2e-tests/tests/admin/topics.spec.ts` — create/edit/delete topics, verify icon & header image, visibility settings
- `e2e-tests/tests/forum/topics.spec.ts` — verify homepage shows topics, public vs private visibility for guests

| # | File | Test | Description |
|---|------|------|-------------|
| 3.1 | `topics.spec.ts` | Homepage shows public topics to guest | Visit `/` without login — see public topics |
| 3.2 | `topics.spec.ts` | Homepage hides restricted topics from guest | Visit `/` without login — "Members Only Lounge" not visible |
| 3.3 | `topics.spec.ts` | Restricted topics visible to logged-in user | Login as testuser, visit `/` — "Members Only Lounge" visible |
| 3.4 | `admin/topics.spec.ts` | Admin can access topic management | Login as admin, navigate to admin topics — topic list visible |
| 3.5 | `admin/topics.spec.ts` | Admin can create a topic | Login as admin, create new topic, verify it appears |
| 3.6 | `admin/topics.spec.ts` | Admin can edit a topic | Login as admin, edit existing topic title, verify change |
| 3.7 | `admin/topics.spec.ts` | Non-admin cannot access admin pages | Login as testuser, navigate to `/admin/topics` — redirected |
| 3.8 | `topics.spec.ts` | Topic card shows discussion count | Homepage topics show discussion count badges |
| 3.9 | `admin/topics.spec.ts` | Admin can delete a topic | Login as admin, delete topic via AlertDialog confirmation, verify removed |
| 3.10 | `topics.spec.ts` | Private topic requires login | Guest visiting private topic URL — redirected to login |

---

## Phase 4: Slate.js Rich Text Editor & Media Uploads

**Goal:** Install Slate.js, build reusable editor & renderer, media upload API.

**Agent assignments:**
- **Senior Developer** → Slate.js component suite (editor, toolbar, renderer), media upload backend, validation rule
- **QA Engineer** → Pest tests for Slate validation + media upload, Playwright E2E for editor interactions + file uploads
- **Instrumentation Engineer** → Spec events: `media_uploaded` (type, size), review for content validation logging
- **Senior Engineer** → Review Slate.js architecture, validate media storage strategy, review JSON validation rule
- **Product Owner** → Verify Slate editor toolbar interactions (bold, italic, headings, lists, links), verify media upload inline display (image/video/document), verify read-only renderer matches editor output

### Frontend (npm packages: `slate`, `slate-react`, `slate-history`, `is-hotkey`)
- `resources/js/components/slate-editor/editor.tsx` — main editor with toolbar
- `resources/js/components/slate-editor/toolbar.tsx` — bold, italic, underline, headings, lists, links, blockquotes, media insert
- `resources/js/components/slate-editor/elements.tsx` — paragraph, heading, list, blockquote, image, video, document embed
- `resources/js/components/slate-editor/leaves.tsx` — bold, italic, underline, code
- `resources/js/components/slate-editor/renderer.tsx` — read-only renderer
- `resources/js/components/slate-editor/types.ts` — custom Slate node types
- `resources/js/components/slate-editor/plugins.ts` — hotkeys, paste handling
- `resources/js/components/slate-editor/index.ts` — barrel exports

### Backend
- Migration: `create_media_table`
- `app/Models/Media.php` — polymorphic relationship
- `app/Rules/SlateDocument.php` — validate Slate JSON structure
- `app/Http/Controllers/MediaController.php` — upload endpoint
- `app/Http/Requests/UploadMediaRequest.php` — validate file types (images: jpg/png/gif/webp, videos: mp4/webm, docs: pdf), max sizes

### Pest Tests
- `tests/Unit/Rules/SlateDocumentTest.php`
- `tests/Feature/MediaUploadTest.php`

### Playwright E2E Tests
- `e2e-tests/tests/editor/slate-editor.spec.ts` — type text, apply formatting (bold/italic/headings), verify toolbar interactions

| # | File | Test | Description |
|---|------|------|-------------|
| 4.1 | `slate-editor.spec.ts` | Editor loads in discussion create | Navigate to create discussion — Slate editor visible with toolbar |
| 4.2 | `slate-editor.spec.ts` | Can type text in editor | Click editor, type text, verify text appears |
| 4.3 | `slate-editor.spec.ts` | Toolbar bold button works | Select text, click bold, verify bold formatting applied |
| 4.4 | `slate-editor.spec.ts` | Toolbar heading button works | Click heading button, type text, verify heading rendered |
| 4.5 | `slate-editor.spec.ts` | Can upload image in editor | Upload image via toolbar, verify image preview in editor |
| 4.6 | `slate-editor.spec.ts` | Toolbar list button works | Click bulleted list, type items, verify list renders |
| 4.7 | `slate-editor.spec.ts` | Toolbar blockquote button works | Click blockquote, type text, verify blockquote renders |
| 4.8 | `slate-editor.spec.ts` | Toolbar link insertion works | Insert link via toolbar, verify link element in editor |

---

## Phase 5: Discussions

**Goal:** Full Discussion CRUD, topic listing page (homepage), discussion detail page.

**Agent assignments:**
- **Senior Developer** → Discussion model/controller/policy, topic listing homepage, discussion pages with Slate editor
- **QA Engineer** → Pest tests for CRUD + authorization + visibility, Playwright E2E for full discussion lifecycle + location filtering
- **Instrumentation Engineer** → Spec events: `discussion_created`, `discussion_viewed`, `discussion_edited`, `discussion_deleted`
- **Senior Engineer** → Review policy logic (topic visibility), validate slug uniqueness, review pagination approach
- **Product Owner** → Verify full discussion lifecycle in browser (create with Slate editor → view → edit → delete), verify topic listing with pagination, verify location filter works, verify pinned discussions appear first

### Backend
- Migration: `create_discussions_table`
- `app/Models/Discussion.php` — relationships, slug generation, scopes (`pinned()`, `byLocation()`)
- `database/factories/DiscussionFactory.php`
- `app/Http/Controllers/DiscussionController.php` — index (by topic), show, store, update, destroy
- `app/Http/Requests/StoreDiscussionRequest.php`, `UpdateDiscussionRequest.php`
- `app/Policies/DiscussionPolicy.php` — respects topic visibility, owner/mod/admin for edit/delete
- Route file: `routes/forum.php`

### Frontend
- Update `resources/js/pages/welcome.tsx` (or new homepage) — topic listing grid (shadcn `Card`, Motion Primitives for list animations)
- `resources/js/pages/topics/show.tsx` — topic header + discussion list with location filter `Select`, shadcn `Pagination`
- `resources/js/pages/discussions/show.tsx` — discussion detail with Slate renderer
- `resources/js/pages/discussions/create.tsx` — Slate editor, topic `Select`, location `Select`
- `resources/js/pages/discussions/edit.tsx`
- `resources/js/components/discussion-card.tsx` — shadcn `Card` with `Badge` for location, `Avatar` for author
- `resources/js/components/topic-header.tsx` — icon, description, header image banner
- **shadcn components to install:** `Pagination`, `Command` (for search), `Popover`, `ScrollArea`

### Pest Tests
- `tests/Feature/DiscussionTest.php` — CRUD, authorization, topic visibility enforcement
- `tests/Feature/DiscussionPolicyTest.php`
- Seeders for sample discussions

### Playwright E2E Tests
- `e2e-tests/tests/forum/discussions.spec.ts` — create discussion with Slate editor, navigate topic→discussion, edit/delete, location filter

| # | File | Test | Description |
|---|------|------|-------------|
| 5.1 | `discussions.spec.ts` | Topic page lists discussions | Navigate to General Discussion topic — see discussion cards |
| 5.2 | `discussions.spec.ts` | Pinned discussions appear first | Pinned discussions (with pin icon) appear before unpinned |
| 5.3 | `discussions.spec.ts` | Can create discussion | Login, navigate to topic, click create, fill title + body, submit — redirected to new discussion |
| 5.4 | `discussions.spec.ts` | Discussion detail page renders | Click discussion card — see title, author, body content, reply section |
| 5.5 | `discussions.spec.ts` | Rich text renders correctly | Discussion body shows formatted text (bold, lists, headings) |
| 5.6 | `discussions.spec.ts` | Can edit own discussion | Edit a discussion owned by testuser — title/body update |
| 5.7 | `discussions.spec.ts` | Cannot edit another user's discussion | Cannot see edit button on admin's discussion |
| 5.8 | `discussions.spec.ts` | Can delete own discussion | Delete own discussion — removed from topic listing |
| 5.9 | `discussions.spec.ts` | Guest can view discussions | Without login, navigate to topic and view a discussion |
| 5.10 | `discussions.spec.ts` | Guest cannot create discussion | Without login, no create discussion button visible |
| 5.11 | `discussions.spec.ts` | Discussion card shows metadata | Cards show author name, time ago, reply count |
| 5.12 | `discussions.spec.ts` | Location filter works | Select location from filter dropdown — discussion list filters to matching location |
| 5.13 | `discussions.spec.ts` | Admin can edit/delete any discussion | Login as admin, edit/delete another user's discussion — succeeds |
| 5.14 | `discussions.spec.ts` | Guest redirected from private topic | Visit `/topics/{private-slug}` without login — redirected to login |

---

## Phase 6: Nested Replies

**Goal:** Reply CRUD with max 3-level nesting, inline reply forms.

**Agent assignments:**
- **Senior Developer** → Reply model/controller/policy/observer, nested thread components, inline reply form
- **QA Engineer** → Pest tests for nesting depth + CRUD + observer, Playwright E2E for nested reply flow + locked discussions
- **Instrumentation Engineer** → Spec events: `reply_created` (with depth level), `reply_edited`, `reply_deleted`
- **Senior Engineer** → Review depth enforcement logic, validate observer for denormalized counts, review recursive rendering
- **Product Owner** → Verify nested reply rendering (3 levels of indentation), verify inline reply form opens/closes correctly, verify reply count updates in real-time, verify locked discussion hides reply form

### Backend
- Migration: `create_replies_table`
- `app/Models/Reply.php` — relationships (`discussion()`, `user()`, `parent()`, `children()`), depth validation
- `database/factories/ReplyFactory.php`
- `app/Http/Controllers/ReplyController.php` — store, update, destroy
- `app/Http/Requests/StoreReplyRequest.php` — validates body, parent_id, depth limit
- `app/Http/Requests/UpdateReplyRequest.php`
- `app/Policies/ReplyPolicy.php`
- `app/Observers/ReplyObserver.php` — update discussion `reply_count` and `last_reply_at`

### Frontend
- `resources/js/components/reply-thread.tsx` — recursive tree rendering (capped at depth 2)
- `resources/js/components/reply-form.tsx` — inline Slate editor for replies
- `resources/js/components/reply-card.tsx` — single reply with user info, timestamp, actions
- Update `resources/js/pages/discussions/show.tsx` — integrate replies section

### Pest Tests
- `tests/Feature/ReplyTest.php` — CRUD, nesting depth enforcement, locked discussion prevention
- `tests/Unit/Observers/ReplyObserverTest.php`

### Playwright E2E Tests
- `e2e-tests/tests/forum/replies.spec.ts` — post reply, reply to reply (nested), edit/delete reply, locked discussion

| # | File | Test | Description |
|---|------|------|-------------|
| 6.1 | `replies.spec.ts` | Can post reply to discussion | Navigate to discussion, write reply, submit — reply appears |
| 6.2 | `replies.spec.ts` | Can reply to a reply (nested) | Post reply, click Reply on it, submit — nested reply appears indented |
| 6.3 | `replies.spec.ts` | Reply count updates | Post reply — discussion reply count increments |
| 6.4 | `replies.spec.ts` | Can edit own reply | Edit reply text, save — updated text shown |
| 6.5 | `replies.spec.ts` | Can delete own reply | Delete reply — reply removed |
| 6.6 | `replies.spec.ts` | Guest cannot reply | Without login, reply form/button not available |
| 6.7 | `replies.spec.ts` | Locked discussion hides reply form | Admin locks discussion — reply form hidden for regular users |
| 6.8 | `replies.spec.ts` | Max depth reply button hidden | Reply button is hidden/absent on depth-2 replies (no 4th level) |
| 6.9 | `replies.spec.ts` | Admin can edit/delete any reply | Login as admin, edit/delete another user's reply — succeeds |

---

## Phase 7: User Profiles & Directory

**Goal:** Public profiles, user directory, paginated user discussions.

**Agent assignments:**
- **Senior Developer** → Profile/directory controllers, profile page, directory page, user card component
- **QA Engineer** → Pest tests for privacy + visibility + deleted user display, Playwright E2E for profile views + directory search
- **Instrumentation Engineer** → Spec events: `profile_viewed`, `directory_searched`
- **Senior Engineer** → Review privacy enforcement, validate deleted user display across all contexts
- **Product Owner** → Verify user profile page (avatar, bio, discussions tab, replies tab), verify directory search and pagination, verify deleted users show "Deleted User" and are excluded from directory, verify privacy settings are honored on profile

### Backend
- `app/Http/Controllers/UserProfileController.php` — show profile by username
- `app/Http/Controllers/DirectoryController.php` — paginated user list
- Routes: `GET /users/{username}`, `GET /directory`

### Frontend
- `resources/js/pages/users/show.tsx` — shadcn `Avatar`, `Card`, `Tabs` (discussions/replies), `Pagination`
- `resources/js/pages/directory/index.tsx` — searchable (shadcn `Command`/`Input`), grid of user cards, excludes deleted users
- `resources/js/components/user-card.tsx` — shadcn `Card` + `Avatar` + `Badge`
- Add "Directory" link to sidebar

### Pest Tests
- `tests/Feature/UserProfileTest.php` — visibility, privacy, deleted user display
- `tests/Feature/DirectoryTest.php` — listing, search, deleted user exclusion

### Playwright E2E Tests
- `e2e-tests/tests/users/profile.spec.ts` — view user profile, verify privacy prefs honored, check paginated discussions
- `e2e-tests/tests/users/directory.spec.ts` — browse directory, search users, verify deleted users excluded

| # | File | Test | Description |
|---|------|------|-------------|
| 7.1 | `users/profile.spec.ts` | User profile page loads | Navigate to `/users/testuser` — profile visible with name, username, bio |
| 7.2 | `users/profile.spec.ts` | Profile shows discussions tab | Profile has discussions tab listing user's discussions |
| 7.3 | `users/profile.spec.ts` | Profile shows replies tab | Profile has replies tab listing user's replies |
| 7.4 | `users/profile.spec.ts` | Deleted user shows "Deleted User" | Navigate to `/users/deleted-user` — shows "Deleted User" |
| 7.5 | `users/directory.spec.ts` | Directory page lists users | Navigate to `/directory` — see user list |
| 7.6 | `users/directory.spec.ts` | Directory search filters users | Type in search box — list filters to matching users |
| 7.7 | `users/directory.spec.ts` | Directory excludes deleted users | Deleted users not visible in directory |
| 7.8 | `users/directory.spec.ts` | Privacy: hidden user not in directory | User with `show_in_directory=false` not shown |
| 7.9 | `users/profile.spec.ts` | Send Message button on profile | Click "Message" button on profile — navigates to conversation with that user |
| 7.10 | `users/profile.spec.ts` | Admin/moderator badge on profile | Admin user profile shows role badge |

---

## Phase 8: Admin User Moderation (Ban, Suspend, Delete)

**Goal:** Admin user management, ban/suspend/delete flows, banned email enforcement.

**Agent assignments:**
- **Senior Developer** → Moderation actions, admin user management UI, suspended middleware, banned email enforcement
- **QA Engineer** → Pest tests for ban/suspend/delete + banned registration, Playwright E2E for moderation flows + suspended user experience
- **Instrumentation Engineer** → Spec events: `user_banned`, `user_suspended`, `user_unsuspended`, `user_deleted`, `user_created_by_admin`, add structured logging for all moderation actions
- **Senior Engineer** → Review anonymization logic, validate banned email enforcement, review middleware placement
- **Product Owner** → Verify admin user management table (status badges, action dropdowns), verify ban/suspend/delete confirmation dialogs, verify suspended user sees appropriate restrictions, verify banned email blocks registration, verify "Deleted User" display on existing content

### Backend
- Migration: `create_banned_emails_table`
- `app/Models/BannedEmail.php`
- `app/Actions/DeleteUser.php` — anonymize: set `is_deleted`, clear personal info, disable login
- `app/Actions/BanUser.php` — calls DeleteUser + adds to `banned_emails`
- `app/Actions/SuspendUser.php` — sets `is_suspended`
- `app/Http/Controllers/Admin/UserModerationController.php` — ban, suspend, unsuspend, delete, create user
- `app/Http/Requests/Admin/BanUserRequest.php`, `CreateUserRequest.php`
- `app/Http/Middleware/EnsureUserIsNotSuspended.php` — applied to discussion/reply creation routes
- Update `CreateNewUser` — check `banned_emails` on registration
- Update `Settings/ProfileController::destroy` — use `DeleteUser` action

### Frontend
- `resources/js/pages/admin/users/index.tsx` — user management (shadcn `Table` with `Badge` for status, `DropdownMenu` for actions)
- `resources/js/pages/admin/users/create.tsx` — admin create user form
- `resources/js/components/moderation-actions.tsx` — shadcn `AlertDialog` for ban/suspend/delete confirmations
- Add moderation buttons to user profile pages via `DropdownMenu` (for admins/mods)

### Pest Tests
- `tests/Feature/Admin/UserModerationTest.php`
- `tests/Feature/Auth/BannedEmailRegistrationTest.php`
- `tests/Feature/SuspendedUserTest.php`

### Playwright E2E Tests
- `e2e-tests/tests/admin/users.spec.ts` — ban/suspend/delete user flows, verify moderation effects

| # | File | Test | Description |
|---|------|------|-------------|
| 8.1 | `admin/users.spec.ts` | Admin can see user management page | Login as admin, navigate to admin users — user table visible |
| 8.2 | `admin/users.spec.ts` | Admin can suspend a user | Suspend a user, verify badge shows "Suspended" |
| 8.3 | `admin/users.spec.ts` | Admin can unsuspend a user | Unsuspend previously suspended user |
| 8.4 | `admin/users.spec.ts` | Suspended user cannot create discussion | Login as suspended user — create button disabled/hidden |
| 8.5 | `admin/users.spec.ts` | Suspended user cannot reply | Login as suspended user — reply form not available |
| 8.6 | `admin/users.spec.ts` | Admin can ban a user | Ban user, verify badge shows "Banned" |
| 8.7 | `admin/users.spec.ts` | Banned email cannot register | Try to register with banned email — error shown |
| 8.8 | `admin/users.spec.ts` | Admin can delete a user (anonymize) | Delete user, verify content shows "Deleted User" |
| 8.9 | `admin/users.spec.ts` | Admin cannot ban/suspend another admin | Attempt to suspend admin user — action blocked (403 or no button) |
| 8.10 | `admin/users.spec.ts` | Admin can create a user | Fill admin create user form, submit — new user appears in table |
| 8.11 | `admin/users.spec.ts` | Suspended user cannot like | Login as suspended, navigate to discussion — like button disabled/hidden |
| 8.12 | `admin/users.spec.ts` | Suspended user cannot send messages | Login as suspended, navigate to messages — new conversation blocked |

---

## Phase 9: Private Messaging

**Goal:** Conversation-based messaging with Slate.js editor.

**Agent assignments:**
- **Senior Developer** → Conversation/message models + controllers, inbox/thread pages, Slate integration
- **QA Engineer** → Pest tests for conversation authorization + messaging, Playwright E2E for full messaging flow + unread badges
- **Instrumentation Engineer** → Spec events: `message_sent`, `conversation_started`, `conversation_read`
- **Senior Engineer** → Review conversation lookup logic, validate unread tracking, review policy security
- **Product Owner** → Verify full messaging flow (start conversation → send messages → receive reply), verify unread badge in sidebar updates, verify conversation list ordering, verify Slate editor works in message composer, verify message thread scroll behavior

### Backend
- Migrations: `create_conversations_table`, `create_conversation_participants_table`, `create_messages_table`
- `app/Models/Conversation.php` — relationships, `latestMessage()`, `forUsers()` scope
- `app/Models/ConversationParticipant.php`
- `app/Models/Message.php`
- Factories for all three models
- `app/Http/Controllers/ConversationController.php` — index, show, store
- `app/Http/Controllers/MessageController.php` — store
- `app/Http/Requests/StoreConversationRequest.php`, `StoreMessageRequest.php`
- `app/Policies/ConversationPolicy.php` — only participants can view/send
- Update `HandleInertiaRequests` — share `unread_messages_count`

### Frontend
- `resources/js/pages/messages/index.tsx` — conversation list (shadcn `Card`, `Badge` for unread count, `ScrollArea`)
- `resources/js/pages/messages/show.tsx` — message thread with `ScrollArea`, Slate editor, Motion Primitives for new message animation
- `resources/js/components/conversation-card.tsx` — shadcn `Card` + `Avatar`
- `resources/js/components/message-bubble.tsx` — styled card with sender info
- Add "Messages" link with unread `Badge` to sidebar

### Pest Tests
- `tests/Feature/ConversationTest.php`
- `tests/Feature/MessageTest.php`

### Playwright E2E Tests
- `e2e-tests/tests/messages/conversations.spec.ts` — start conversation, send messages, verify unread badge

| # | File | Test | Description |
|---|------|------|-------------|
| 9.1 | `conversations.spec.ts` | Messages page loads | Login, navigate to `/messages` — conversation list visible |
| 9.2 | `conversations.spec.ts` | Can start new conversation | Click new conversation, select user, write message, send — created |
| 9.3 | `conversations.spec.ts` | Can reply in conversation | Open existing conversation, write reply, submit — message appears |
| 9.4 | `conversations.spec.ts` | Unread badge shows in sidebar | Second user sends message → first user sees unread badge on Messages nav |
| 9.5 | `conversations.spec.ts` | Conversation list shows latest message preview | Conversation list shows snippet of most recent message |
| 9.6 | `conversations.spec.ts` | Guest cannot access messages | Without login, `/messages` redirects to login |
| 9.7 | `conversations.spec.ts` | Messages page empty state | Fresh user with no conversations sees "No conversations yet" message |
| 9.8 | `conversations.spec.ts` | Cannot view another user's conversation | Navigate to conversation URL user is not a participant of — forbidden/redirected |
| 9.9 | `conversations.spec.ts` | Rich text renders in message thread | Send formatted message — verify bold/list renders in thread |

---

## Phase 10: PostHog Analytics & Email Notifications

**Goal:** Event tracking, email notifications for replies and messages.

**Agent assignments:**
- **Instrumentation Engineer** (PRIMARY) → PostHog PHP SDK setup, client-side posthog-js init, implement all event tracking across controllers, create PostHog dashboards
- **Senior Developer** → Email notification classes, notification preferences page, settings controller
- **QA Engineer** → Pest tests for tracking (mocked) + notifications, Playwright E2E for notification preferences
- **Senior Engineer** → Review event schema, validate notification delivery, final sign-off on full forum
- **Product Owner** → Verify notification preferences page toggles persist, verify PostHog events fire in browser network tab, perform full regression test across all phases (registration → profile → topics → discussions → replies → messaging → admin moderation)

### Backend — PostHog
- `ddev composer require posthog/posthog-php`
- `config/posthog.php`
- `app/Services/PostHogService.php` — wrapper
- Add tracking in: DiscussionController (created/viewed), ReplyController (created), MessageController (sent), moderation actions

### Frontend — PostHog
- `npm install posthog-js`
- `resources/js/lib/posthog.ts` — init with `VITE_POSTHOG_KEY`
- Update `resources/js/app.tsx` — init PostHog, track Inertia navigations

### Backend — Notifications
- Migration: `add_notification_preferences_to_users_table`
- `app/Notifications/NewReplyNotification.php`
- `app/Notifications/NewMessageNotification.php`
- `app/Http/Controllers/Settings/NotificationController.php`
- `app/Http/Requests/Settings/NotificationUpdateRequest.php`

### Frontend — Notifications
- `resources/js/pages/settings/notifications.tsx` — notification preferences
- Update settings layout nav

### Pest Tests
- `tests/Feature/PostHogTrackingTest.php` (mock service)
- `tests/Feature/NotificationTest.php`
- `tests/Feature/Settings/NotificationPreferencesTest.php`

### Playwright E2E Tests
- `e2e-tests/tests/notifications/notifications.spec.ts` — notification preferences portion

| # | File | Test | Description |
|---|------|------|-------------|
| 10.1 | `notifications.spec.ts` | Notification preferences toggles work | Toggle notify_replies off, save, reload — toggle stays off |
| 10.2 | `notifications.spec.ts` | Notification preferences page accessible | Navigate to notification settings — all toggles visible |

*Note: Email delivery cannot be E2E tested via Playwright. Backend Pest tests cover email notification logic.*

---

## Phase 11: Icon Picker & Dashboard Enhancements

**Goal:** Replace text-based icon selection with a searchable Lucide icon picker, fix icon rendering across all pages, and build a real dashboard with activity summaries.

**Agent assignments:**
- **Senior Developer** → Icon picker component, dynamic icon rendering, DashboardController, dashboard page redesign
- **QA Engineer** → Pest tests for dashboard props, icon rendering validation
- **Senior Engineer** → Review icon map approach (tree-shaking), validate dashboard queries for N+1
- **Product Owner** → Verify icon picker UX, verify icons render across all pages, verify dashboard data accuracy

### Backend
- `app/Http/Controllers/DashboardController.php` (new, invokable) — passes userStats, recentReplies (deferred), activeTopics (deferred), recentDiscussions (deferred)
- Update `routes/web.php` — replace inline dashboard closure with DashboardController

### Frontend — Icon System
- `resources/js/lib/lucide-icons.ts` — curated map of ~50 Lucide icons (`Record<string, LucideIcon>`), `getIconComponent(name)` helper
- `resources/js/components/icon-picker.tsx` — searchable icon picker using Dialog + Input + scrollable grid
- `resources/js/components/dynamic-icon.tsx` — renders Lucide component from string name with fallback
- Update `resources/js/pages/admin/topics/create.tsx` — replace Input with IconPicker
- Update `resources/js/pages/admin/topics/edit.tsx` — replace Input with IconPicker
- Update `resources/js/pages/admin/topics/index.tsx` — replace string with DynamicIcon
- Update `resources/js/components/topic-header.tsx` — replace string with DynamicIcon
- Update `resources/js/pages/welcome.tsx` — replace string with DynamicIcon

### Frontend — Dashboard
- Update `resources/js/pages/dashboard.tsx` — replace placeholder with stat cards (Your Stats, Unread Messages, Quick Actions) + deferred sections (Recent Replies, Active Topics, Recent Discussions) with Skeleton loading

### Pest Tests
- Update `tests/Feature/DashboardTest.php` — assert Inertia props (userStats, recentReplies, activeTopics, recentDiscussions)

### Playwright E2E Tests
- `e2e-tests/tests/admin/topics.spec.ts` (extend) — icon picker in admin topic form
- `e2e-tests/tests/dashboard/dashboard.spec.ts` — dashboard loads with stats and deferred sections

| # | File | Test | Description |
|---|------|------|-------------|
| 11.1 | `admin/topics.spec.ts` | Admin topic form has icon picker | Create/edit topic — icon picker button visible, opens dialog with grid |
| 11.2 | `admin/topics.spec.ts` | Icon picker search filters icons | Type in icon search — filtered icon grid updates |
| 11.3 | `admin/topics.spec.ts` | Selected icon renders on topic card | Select icon for topic, verify it appears on homepage topic card |
| 11.4 | `dashboard.spec.ts` | Dashboard loads with stats | Login, visit `/dashboard` — user stats cards visible |
| 11.5 | `dashboard.spec.ts` | Dashboard shows recent discussions | Dashboard has recent discussions section with discussion links |
| 11.6 | `dashboard.spec.ts` | Dashboard skeleton states appear | Deferred sections show skeleton loading before data loads |
| 11.7 | `dashboard.spec.ts` | Dashboard quick action links work | Click "Browse Topics" and "New Message" quick actions — navigate correctly |

---

## Phase 12: @Mentions in Slate Editor

**Goal:** Add @mention autocomplete to Slate editor for discussions and replies (not messages). Store mentions with user_id. Render as profile links. Generate notifications.

**Agent assignments:**
- **Senior Developer** → Mention Slate plugin, autocomplete component, MentionService, notification, user search API
- **QA Engineer** → Pest tests for user search, mention extraction, mention notifications, SlateDocument validation
- **Senior Engineer** → Review Slate inline+void pattern, validate mention node validation, review notification deduplication
- **Product Owner** → Verify @mention autocomplete UX, verify mention links in rendered content, verify notifications fire

### Backend
- `app/Http/Controllers/UserSearchController.php` (invokable) — `GET /users/search?q=...` returns JSON, auth required
- `app/Services/MentionService.php` — extract mention user IDs from Slate document, send MentionNotification
- `app/Notifications/MentionNotification.php` — email notification, respects `notify_mentions` preference
- Migration: `add_notify_mentions_to_users_table` — `notify_mentions BOOLEAN DEFAULT TRUE`
- Update `app/Rules/SlateDocument.php` — allow `mention` inline type, validate `userId` and `username`
- Update `app/Http/Controllers/DiscussionController.php` — inject MentionService, call after store/update
- Update `app/Http/Controllers/ReplyController.php` — inject MentionService, call after store
- Update `app/Models/User.php` — add `notify_mentions` to fillable + casts
- Update `app/Http/Controllers/Settings/NotificationController.php` — include `notify_mentions`
- Update `app/Http/Requests/Settings/NotificationUpdateRequest.php` — add `notify_mentions` validation
- Route: `GET /users/search` in `routes/forum.php` with auth + verified

### Frontend
- `resources/js/components/slate-editor/mention-autocomplete.tsx` — autocomplete dropdown, cursor-positioned, debounced API search
- Update `resources/js/slate.d.ts` — add MentionElement type
- Update `resources/js/components/slate-editor/types.ts` — add `"mention"` to VOID_TYPES
- Update `resources/js/components/slate-editor/plugins.ts` — `withMentions` plugin (isInline + isVoid), `insertMention()` function
- Update `resources/js/components/slate-editor/editor.tsx` — chain `withMentions`, add `enableMentions` prop, render MentionAutocomplete
- Update `resources/js/components/slate-editor/elements.tsx` — add `mention` case (render as `<a>` link)
- Update `resources/js/pages/discussions/create.tsx` — pass `enableMentions={true}`
- Update `resources/js/pages/discussions/edit.tsx` — pass `enableMentions={true}`
- Update `resources/js/components/reply-form.tsx` — pass `enableMentions={true}`
- Update `resources/js/pages/settings/notifications.tsx` — add `notify_mentions` toggle

### Pest Tests
- `tests/Feature/UserSearchTest.php` — search results, excludes deleted/self, min chars, max results, auth
- `tests/Feature/MentionServiceTest.php` — extract IDs, nesting, dedup, excludes author/deleted
- `tests/Feature/MentionNotificationTest.php` — sent on create, no self-mention, respects preference
- Update `tests/Feature/Rules/SlateDocumentTest.php` — valid/invalid mention nodes
- Update `tests/Feature/Settings/NotificationPreferencesTest.php` — include notify_mentions

### Playwright E2E Tests
- `e2e-tests/tests/editor/slate-editor.spec.ts` (extend) — @mention autocomplete and rendering

| # | File | Test | Description |
|---|------|------|-------------|
| 12.1 | `slate-editor.spec.ts` | @mention autocomplete appears | In editor, type `@te` — autocomplete dropdown shows matching users |
| 12.2 | `slate-editor.spec.ts` | Selecting mention inserts chip | Click user in autocomplete — mention chip inserted in editor |
| 12.3 | `slate-editor.spec.ts` | Mention renders as link in content | Submit discussion with mention — renders as clickable user link |
| 12.4 | `slate-editor.spec.ts` | Mention link navigates to profile | Click mention link in discussion body — navigates to user profile |
| 12.5 | `slate-editor.spec.ts` | @mention works in reply form | Open reply form, type `@` — autocomplete appears in reply editor too |
| 12.6 | `slate-editor.spec.ts` | @mention not available in messages | Open message composer, type `@` — NO autocomplete (intentional) |

---

## Phase 13: In-App Notifications Panel

**Goal:** Database-backed notifications with bell icon, notification panel, mark-as-read. All notifications stored in DB; email remains optional per preference.

**Agent assignments:**
- **Senior Developer** → Notifications table, NotificationPanelController, notification panel/bell components, update via() on all notifications
- **QA Engineer** → Pest tests for notification API, update existing notification tests for database channel
- **Senior Engineer** → Review via() changes (breaking test impact), validate notification data structure, review panel UX
- **Product Owner** → Verify notification bell badge, verify panel lists all notification types, verify mark-as-read, verify email preferences still work

### Backend
- Migration: `create_notifications_table` (via `php artisan notifications:table`) — standard Laravel notifications schema
- `app/Http/Controllers/NotificationPanelController.php` — JSON API: index (last 20), markAsRead, markAllAsRead
- Update `app/Notifications/NewReplyNotification.php` — always include `'database'` in via(), add `toArray()`
- Update `app/Notifications/NewMessageNotification.php` — always include `'database'` in via(), add `toArray()`
- Update `app/Notifications/MentionNotification.php` — always include `'database'` in via(), add `toArray()`
- Update `app/Http/Middleware/HandleInertiaRequests.php` — share `unread_notifications_count`
- Routes: `GET /notifications`, `POST /notifications/{id}/read`, `POST /notifications/mark-all-read`

### Frontend
- `resources/js/components/notification-panel.tsx` — Sheet panel fetching from `/notifications` API, renders by type, unread indicators, mark-as-read
- `resources/js/components/notification-bell.tsx` — Bell icon + badge from shared `unread_notifications_count`
- Update `resources/js/components/app-sidebar.tsx` — add NotificationBell
- Update `resources/js/pages/settings/notifications.tsx` — clarify toggles are for email only

### Pest Tests
- `tests/Feature/InAppNotificationTest.php` — fetch, mark read, mark all read, only own, auth required
- Update `tests/Feature/NotificationTest.php` — fix assertNotSentTo → assertSentTo with channel check (via() always returns ['database'] now)
- Update `tests/Feature/MessageTest.php` — unread notifications count shared

### Playwright E2E Tests
- `e2e-tests/tests/notifications/notifications.spec.ts` — notification panel interactions and lifecycle

| # | File | Test | Description |
|---|------|------|-------------|
| 13.1 | `notifications.spec.ts` | Bell icon visible in sidebar | Login — bell icon with notification count visible in sidebar |
| 13.2 | `notifications.spec.ts` | Notification panel opens on click | Click bell icon — sheet panel slides open showing notifications |
| 13.3 | `notifications.spec.ts` | Reply notification appears | User A replies to User B's discussion — User B sees notification |
| 13.4 | `notifications.spec.ts` | Mention notification appears | User A mentions User B — User B sees mention notification |
| 13.5 | `notifications.spec.ts` | Mark single notification as read | Click a notification — it becomes read (visual change) |
| 13.6 | `notifications.spec.ts` | Mark all notifications as read | Click "Mark all as read" — all cleared, badge updates |
| 13.7 | `notifications.spec.ts` | Notification click navigates to content | Click reply notification — navigates to the discussion |
| 13.8 | `notifications.spec.ts` | Unread count badge updates | After marking all read, badge disappears from sidebar |
| 13.9 | `notifications.spec.ts` | Message notification appears | User A sends message to User B — User B sees message notification in panel |
| 13.10 | `notifications.spec.ts` | Notification panel empty state | Mark all read / fresh user — panel shows "No notifications yet" |

### Development Data Seeders
- `database/seeders/Development/NotificationSeeder.php` — seed sample notifications for dev users

---

## Phase 14: Likes (Discussions & Replies)

**Goal:** Users can like a discussion or a reply. Likes are toggled (like/unlike). Like count is visible to all. Polymorphic design so one table handles both.

### Backend
- Migration: `create_likes_table` — `id, user_id (FK cascadeOnDelete), likeable_id, likeable_type (morph), created_at`. Unique index on `[user_id, likeable_id, likeable_type]`.
- `app/Models/Like.php` — `belongsTo: user`, `morphTo: likeable`. No factory needed.
- `app/Http/Controllers/LikeController.php` — Two toggle endpoints (POST, returns JSON):
  - `toggleDiscussionLike(Discussion $discussion)` — creates or deletes like, returns `{ liked, like_count }`
  - `toggleReplyLike(Reply $reply)` — same pattern
- Update `app/Models/Discussion.php` — Add `morphMany(Like::class, 'likeable')`
- Update `app/Models/Reply.php` — Add `morphMany(Like::class, 'likeable')`
- Update `app/Models/User.php` — Add `hasMany(Like::class)`
- Update `app/Http/Controllers/DiscussionController.php` — In `show()`: eager-load `likes_count` on discussion and replies, pass `user_has_liked` for auth user
- Routes in `routes/forum.php`: `POST /discussions/{discussion}/like`, `POST /replies/{reply}/like` (auth + verified + not-suspended)

### Frontend
- Update `resources/js/pages/discussions/show.tsx` — Add like button (Heart icon) in discussion body card footer. Heart filled when liked, outline when not. Count next to icon. Optimistic update on click.
- Update `resources/js/components/reply-card.tsx` — Add like button in hover actions row (before Reply). Same Heart + count pattern. Optimistic update.
- Update `resources/js/components/reply-thread.tsx` — Pass like props through to ReplyCard
- Update types: `Discussion` gets `likes_count`, `user_has_liked`. `ReplyType` gets same.
- Unauthenticated users see counts but no clickable heart.

### Pest Tests
- `tests/Feature/LikeTest.php` — Like/unlike discussion, like/unlike reply, count correctness, toggle behavior (no duplicates), auth required, suspended cannot like, show page includes like data

### Playwright E2E Tests
- `e2e-tests/tests/forum/likes.spec.ts` — like/unlike discussions and replies, counts, persistence

| # | File | Test | Description |
|---|------|------|-------------|
| 14.1 | `likes.spec.ts` | Like button visible on discussion | Navigate to discussion — heart icon with count visible |
| 14.2 | `likes.spec.ts` | Can like a discussion | Click heart — fills red, count increments |
| 14.3 | `likes.spec.ts` | Can unlike a discussion | Click liked heart — unfills, count decrements |
| 14.4 | `likes.spec.ts` | Like persists after page reload | Like discussion, reload — heart still filled, count correct |
| 14.5 | `likes.spec.ts` | Like button visible on reply | Reply shows heart icon with count |
| 14.6 | `likes.spec.ts` | Can like a reply | Click reply heart — fills, count increments |
| 14.7 | `likes.spec.ts` | Guest sees like counts but no button | Without login, counts visible, no clickable heart |
| 14.8 | `likes.spec.ts` | Like count visible on topic listing card | Discussion card on topic page shows heart icon + count |
| 14.9 | `likes.spec.ts` | Like toggle on topic listing card | Click heart on discussion card — toggles without navigating |

### Development Data Seeders
- `database/seeders/Development/LikeSeeder.php` — Scatter likes across discussions and replies from various users

---

## Phase 15: Bookmarks (Follow Discussions) with Notifications

**Goal:** Users can bookmark/follow a discussion. When a bookmarked discussion receives a new reply, the bookmarking user gets a notification. Users can view their bookmarked discussions.

### Backend
- Migration: `create_bookmarks_table` — `id, user_id (FK cascadeOnDelete), discussion_id (FK cascadeOnDelete), created_at`. Unique index on `[user_id, discussion_id]`.
- `app/Models/Bookmark.php` — `belongsTo: user, discussion`. No factory needed.
- `app/Http/Controllers/BookmarkController.php`:
  - `toggle(Discussion $discussion)` — POST, creates or deletes bookmark, returns JSON `{ bookmarked: bool }`
  - `index()` — GET, Inertia page with user's bookmarked discussions (paginated, with topic, reply_count, last_reply_at)
- `app/Notifications/BookmarkActivityNotification.php` — `via()`: always `['database']` (no email). `toArray()`: `{ type: 'bookmark_activity', discussion_id, discussion_title, discussion_slug, topic_id, topic_slug, replier_name, replier_username }`
- `app/Services/BookmarkNotificationService.php` — Called from ReplyController after reply creation. Gets bookmarking users, excludes reply author and users already notified by NewReplyNotification.
- Update `app/Models/Discussion.php` — Add `hasMany(Bookmark::class)`
- Update `app/Models/User.php` — Add `hasMany(Bookmark::class)`
- Update `app/Http/Controllers/DiscussionController.php` — In `show()`: pass `user_has_bookmarked` for auth user
- Update `app/Http/Controllers/ReplyController.php` — Inject BookmarkNotificationService, call after reply creation
- Routes: `POST /discussions/{discussion}/bookmark` (forum.php), `GET /bookmarks` (web.php) — auth + verified

### Frontend
- `resources/js/pages/bookmarks/index.tsx` — Paginated list of bookmarked discussions with topic icon, title (link), reply count, last activity. Empty state.
- Update `resources/js/pages/discussions/show.tsx` — Add bookmark toggle button (Bookmark icon) next to like button. Filled when bookmarked. Optimistic update.
- Update `resources/js/components/app-sidebar.tsx` — Add "Bookmarks" nav item (Bookmark icon) between Directory and Messages
- Update `resources/js/components/notification-panel.tsx` — Add `bookmark_activity` type in icon/text/URL helpers

### Pest Tests
- `tests/Feature/BookmarkTest.php` — Bookmark/unbookmark, toggle behavior, auth required, index page, only own bookmarks, show page includes bookmark data
- `tests/Feature/BookmarkNotificationTest.php` — Notification sent on reply to bookmarked discussion, no self-notification, no duplicate with NewReplyNotification, database-only channel, correct data

### Playwright E2E Tests
- `e2e-tests/tests/forum/bookmarks.spec.ts` — bookmark toggle, bookmarks page, bookmark notifications

| # | File | Test | Description |
|---|------|------|-------------|
| 15.1 | `bookmarks.spec.ts` | Bookmark button visible on discussion | Navigate to discussion — bookmark icon visible |
| 15.2 | `bookmarks.spec.ts` | Can bookmark a discussion | Click bookmark — icon fills |
| 15.3 | `bookmarks.spec.ts` | Can remove bookmark | Click bookmarked icon — unfills |
| 15.4 | `bookmarks.spec.ts` | Bookmark persists after reload | Bookmark, reload — bookmark still active |
| 15.5 | `bookmarks.spec.ts` | Bookmarks page shows bookmarked discussions | Navigate to `/bookmarks` — see bookmarked discussions |
| 15.6 | `bookmarks.spec.ts` | Bookmarks page empty state | Remove all bookmarks, visit `/bookmarks` — empty state message |
| 15.7 | `bookmarks.spec.ts` | Bookmark toggle on topic listing card | Click bookmark on discussion card — toggles without navigating |
| 15.8 | `bookmarks.spec.ts` | Bookmark notification on new reply | User A bookmarks, User B replies — User A sees notification |
| 15.9 | `bookmarks.spec.ts` | Bookmarks sidebar nav item works | Click "Bookmarks" in sidebar — navigates to bookmarks page |
| 15.10 | `bookmarks.spec.ts` | Guest cannot access bookmarks page | Without login, `/bookmarks` redirects to login |

### Development Data Seeders
- `database/seeders/Development/BookmarkSeeder.php` — Bookmark some discussions for dev users

---

## Phase 14b/15b: Like & Bookmark from Topic View

**Goal:** Extend the discussion card in the topic listing to show like counts, bookmark status, and allow toggling likes/bookmarks directly from the topic view without navigating to the discussion.

### Backend
- Update `app/Http/Controllers/DiscussionController.php` — In `index()`: eager-load `withCount('likes')`, and for auth users: compute `user_has_liked` and `user_has_bookmarked` per discussion

### Frontend
- Update `resources/js/components/discussion-card.tsx` — Restructure from single `<Link>` to `<div>` with clickable title. Add like button (Heart + count) and bookmark button. Use optimistic toggle with fetch POST. Show counts to all users; buttons only for authenticated.
- Update `resources/js/pages/topics/show.tsx` — Pass `authUserId` to DiscussionCard. Update Discussion type with `likes_count`, `user_has_liked`, `user_has_bookmarked`.

### Pest Tests
- Update `tests/Feature/LikeTest.php` — Topic index includes like data
- Update `tests/Feature/BookmarkTest.php` — Topic index includes bookmark data

### Playwright E2E Tests
*Tests covered in Phase 14 and 15 tables above (tests 14.8, 14.9, 15.7 specifically test topic listing interactions).*

---

## Phase 16: Discussion View Tracking

**Goal:** Track and display view counts for discussions. Show views, replies, and likes as stats on both topic listing and discussion detail pages.

### Backend
- Migration: `add_view_count_to_discussions_table` — `view_count UNSIGNED INTEGER DEFAULT 0`
- Update `app/Http/Controllers/DiscussionController.php`:
  - In `show()`: increment `view_count` (use `$discussion->increment('view_count')` — atomic, no race condition)
  - In `index()`: `view_count` already available via model (no extra query needed)

### Frontend
- Update `resources/js/components/discussion-card.tsx` — Show view count (Eye icon) alongside reply count and like count
- Update `resources/js/pages/discussions/show.tsx` — Show view count in discussion stats area alongside likes

### Pest Tests
- `tests/Feature/DiscussionViewTest.php` — View count increments on show, view count visible in topic listing, view count visible in discussion show

### Playwright E2E Tests
- `e2e-tests/tests/forum/views.spec.ts` — view count display and incrementing

| # | File | Test | Description |
|---|------|------|-------------|
| 16.1 | `views.spec.ts` | View count displays on discussion card | Topic listing cards show eye icon + view count |
| 16.2 | `views.spec.ts` | View count displays on discussion detail | Discussion detail page shows view count |
| 16.3 | `views.spec.ts` | View count increments on visit | Note count, visit discussion, go back — count incremented by 1 |

---

## Cross-Phase E2E Tests

These tests exercise multi-phase user journeys that span features from different phases. They live in a dedicated spec file.

**File**: `e2e-tests/tests/cross-phase/journeys.spec.ts`

| # | Test | Description | Phases |
|---|------|-------------|--------|
| X.1 | Full notification-to-content flow | Create discussion → another user replies → author sees notification → clicks notification → navigates to discussion with reply visible | 5, 6, 13 |
| X.2 | Bookmark + notification + navigation | User A bookmarks discussion → User B replies → User A opens notification panel → sees bookmark_activity → clicks → navigates to discussion | 6, 13, 15 |
| X.3 | Suspended user full restriction chain | Login as suspended → try create discussion (blocked) → try reply (blocked) → try like (blocked) → can still browse and view content | 5, 6, 8, 14 |
| X.4 | Guest vs authenticated homepage | As guest: see public topics only, no like/bookmark buttons. Login: see private topics, like/bookmark buttons appear on cards | 3, 14, 15 |
| X.5 | User moderation content impact | Admin bans user → navigate to discussion authored by banned user → displays "Deleted User" on discussion and replies | 1, 5, 6, 8 |
| X.6 | Privacy settings chain | User sets show_in_directory=false → directory excludes them → profile page hides email → @mention autocomplete still finds them (username-based) | 2, 7, 12 |

---

## E2E Fixture Requirements

The existing auth/database fixtures need extensions to support the full test suite:

### Auth Fixtures (`e2e-tests/tests/fixtures/auth.ts`)
Extend with additional authenticated page fixtures:
- **`adminPage`** — logs in as `admin@example.com` (needed for Phases 3, 8, 11)
- **`moderatorPage`** — logs in as `moderator@example.com` (needed for Phase 6, 8)
- **`suspendedPage`** — logs in as `suspended@example.com` (needed for Phase 8, cross-phase X.3)
- **`secondUserPage`** — logs in as `minimal@example.com` (needed for two-user interaction tests: messaging, notifications)

### Database Fixtures (`e2e-tests/tests/fixtures/database.ts`)
- Add `artisanCommand(cmd: string)` helper for arbitrary artisan commands (clearing notifications, etc.)

### Seeder Requirements
- Add a dedicated "expendable" user to `UserSeeder` for ban/delete tests (so moderation E2E tests don't affect other seeded users)

---

## Verification Strategy

After each phase, the following checks must pass before the **Senior Engineer** signs off:

| Step | Owner | Command |
|------|-------|---------|
| Pest tests | QA Engineer | `ddev php artisan test --compact` |
| Playwright E2E | QA Engineer | `cd e2e-tests && npx playwright test` |
| Code style | Senior Developer | `ddev exec vendor/bin/pint --dirty --format agent` |
| Frontend build | Senior Developer | `npm run build` |
| TypeScript check | Senior Developer | `npm run types` |
| Event tracking review | Instrumentation Engineer | Verify events fire correctly via PostHog debug mode (Phase 10+) |
| Browser review | Product Owner | Visual verification via chrome-devtools MCP: screenshots, user flow testing, UI regression check |
| Final review | Senior Engineer | Code review, architecture validation, phase sign-off (only after Product Owner approval) |

---

## Critical Existing Files (will be modified across phases)

| File | Phases |
|------|--------|
| `app/Models/User.php` | 1, 2, 7, 8 |
| `resources/js/types/auth.ts` | 1 |
| `database/factories/UserFactory.php` | 1 |
| `database/seeders/DatabaseSeeder.php` | 1, 3, 5 |
| `app/Http/Middleware/HandleInertiaRequests.php` | 9 |
| `resources/js/components/app-sidebar.tsx` | 3, 5, 7, 9 |
| `app/Actions/Fortify/CreateNewUser.php` | 1, 8 |
| `routes/web.php` | 3, 5, 7, 9 |
| `resources/js/pages/settings/profile.tsx` | 1, 2 |
| `resources/js/layouts/settings/layout.tsx` | 2, 10 |
| `bootstrap/app.php` | 8 (suspended middleware) |
| `resources/js/components/slate-editor/editor.tsx` | 12 (mentions plugin) |
| `resources/js/components/slate-editor/elements.tsx` | 12 (mention rendering) |
| `resources/js/components/slate-editor/types.ts` | 12 (mention type) |
| `app/Rules/SlateDocument.php` | 12 (mention validation) |
| `app/Notifications/NewReplyNotification.php` | 13 (database channel) |
| `app/Notifications/NewMessageNotification.php` | 13 (database channel) |
| `resources/js/pages/dashboard.tsx` | 11 (dashboard redesign) |
| `app/Http/Controllers/DiscussionController.php` | 14, 15 (like/bookmark data in show) |
| `resources/js/pages/discussions/show.tsx` | 14, 15 (like/bookmark buttons) |
| `resources/js/components/reply-card.tsx` | 14 (like button) |
| `resources/js/components/reply-thread.tsx` | 14 (like props passthrough) |
| `app/Http/Controllers/ReplyController.php` | 15 (bookmark notifications) |
| `resources/js/components/notification-panel.tsx` | 15 (bookmark_activity type) |
| `resources/js/components/discussion-card.tsx` | 14b/15b (like/bookmark in topic view), 16 (view count) |
| `resources/js/pages/topics/show.tsx` | 14b/15b (like/bookmark props) |
