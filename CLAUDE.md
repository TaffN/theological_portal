# CLAUDE.md: Theological Center Learning Portal

Handover notes so any Claude session can continue this project without losing context.
Last updated: 26 September 2026 (after Portal **v6** = Stage 4 Assignments + Administrators screen, database migration **15**).

---

## 1. Who and what

**The client** is an online Theological Center in Zimbabwe. Today it shares course
materials and collects student work over **WhatsApp**. This portal replaces that with a
proper learning platform that works well on phones (most students use mobile data).

**The developer** (the person you're talking to) is an experienced **desktop application
programmer** (databases, data structures) who is **new to web development** and learning
as the project goes.

- They want **Claude to lead the technical decisions**: propose the approach, explain
  briefly, and build it. Don't hand them lists of options to choose from.
- They test everything on their own laptop and send **phone photos of the screen** when
  something breaks.
- Explain web-specific concepts (routing, sessions, .htaccess, etc.) in plain terms when
  they come up.

A plain-language client report (features, how the system works, monthly hosting costs of
roughly **$10–$20/month** shared hosting plus a yearly domain) was already delivered as a
Word document.

---

## 2. Tech stack and environment

| Item | Detail |
|---|---|
| Framework | **CodeIgniter 3.1.13** (the user already had a CI3 environment; CI4 was started, then dropped) |
| PHP | 7.3.6 (XAMPP). **Don't use PHP 7.4+ syntax**: no arrow functions `fn()`, no typed properties, no `match`, no `str_contains` |
| Database | MariaDB 10.3.16, database name `theological_portal`, user `root`, no password (local) |
| Web server | Apache 2.4.39 on Windows via **XAMPP** |
| Editor | VS Code |
| Frontend | **Bootstrap 5.3** (self-hosted in `assets/`, CDN fallback) + custom `assets/css/app.css` + vanilla JS `assets/js/app.js`. **No build step, no npm, no JS framework.** |
| Font | Inter from Google Fonts, falls back to the system font when offline |
| Local URL | `http://localhost/theological_portal/` (project folder: `C:\xampp\htdocs\theological_portal`) |

### Environment gotchas already solved (don't reintroduce)

- **`.htaccess`** in the project root strips `index.php` from URLs. It must be named exactly
  `.htaccess` (Windows once saved it as `.htaccess.txt`). Apache needs `AllowOverride All` for htdocs.
- `config.php`: `$config['index_page'] = '';` (it was wrongly `'index.html'`, which broke every redirect).
- `base_url` is **dynamic**, built from `$_SERVER['HTTP_HOST']`, so `localhost`, `127.0.0.1` and the
  LAN IP all work, and session cookies stay on one host.
- Sessions last **3 days** (`sess_expiration = 259200`) so phone users aren't logged out constantly.
- Autoload: libraries `database, session, form_validation, audit, settings`; helpers `url, form`.
- `index.php` has one added line before CodeIgniter boots:
  `require_once APPPATH.'core/Portal_error_handlers.php';` (custom uncaught-exception handler).

### Testing in Claude's sandbox

Older sandboxes had no PHP. The v6 session's sandbox had **PHP 8.4 + (apt-installed) MariaDB 10.11**, which
allowed a real run of the whole app. Recipe (all throwaway, nothing committed):
- `apt-get install -y mariadb-server`, start `mariadbd --user=root &`, create DB `theological_portal`, make root
  use `mysql_native_password` with an empty password.
- `php -d mysqli.default_socket=/run/mysqld/mysqld.sock -S 127.0.0.1:8080 router.php`, where the router strips the
  `/theological_portal` prefix (base_url hard-codes it), serves static files, sets `$_SERVER['CI_ENV'] = 'testing'`
  (CI3 on PHP 8.4 floods the page with deprecation notices in development mode; the laptop's PHP 7.3 doesn't)
  and `require`s `index.php`. Visit `/migrate`, then drive it with `curl` cookie jars (log in via POST `/login`).
- After each run, `SELECT * FROM error_reports`: MY_Exceptions records every PHP/DB error there.
- Screenshots: global Node Playwright (`require(npm root -g + '/playwright')`). Bootstrap isn't in the repo
  (the user's copy is local), so `npm pack bootstrap@5.3.3` into the scratchpad and `context.route()` the jsdelivr
  URLs to it; abort Google Fonts.
- **PHP 8.4 is not the target.** Still write PHP 7.3 code, and grep changed files for `fn(`, `??=`, `match(`,
  `str_contains`, typed properties, `?->` before committing.

If PHP isn't available, fall back to structural checks (bracket/`endif` balance), cross-checking every
`base_url('controller/method')` against real methods, `node --check` on JS, and static HTML mocks.

The user's laptop is still the first *real* run, so expect them to report PHP notices or errors and fix them quickly.

---

## 3. Roadmap and status

| Stage | Scope | Status |
|---|---|---|
| 1 | DB schema, users/roles, courses, enrollments, role-gated base controllers | ✅ Done |
| 2 | Fees & access gating: proof-of-payment upload, admin approve/reject | ✅ Done |
| 3 | Course materials + notifications (in-app, optional email) | ✅ Done |
| (extras) | Admin screens, dashboards + charts, modern UI + dark mode, error reporting, audit trail, IDs, photos, QR ID cards, org settings, extended profiles, help page | ✅ Done (v4/v5) |
| 4 | **Assignments**: lecturers set them, students submit, lecturers mark + feedback (+ Administrators screen) | ✅ Done (v6) |
| **5** | **Online exams**: timed, open/close window, MCQ + short answer | ⏭️ **NEXT** |
| 6 | Results: publish assignment/exam results per enrollment | ⏳ |
| After 6 | **"Ezra" AI assistant** (see §9). **Remind the user to start Ezra once Stage 6 is done**; they asked for this reminder. | ⏳ |
| Go-live | Hosting, HTTPS, SMTP email, production hardening (see §8) | ⏳ |

**Current state:** v6 pushed to branch `claude/inspiring-ramanujan-y9isjf` (not yet on the laptop when written).
The laptop needs: copy the files, **back up the DB first**, visit `/migrate` (→ 15), then delete `Migrate.php` again.
Admin login was recovered with `reset_admin.php`; the file was removed from the repo in v6, but the user said the
**laptop copy still existed: confirm they deleted it** from `C:\xampp\htdocs\theological_portal`.

---

## 4. Folder structure (application code)

```
theological_portal/
├── index.php                  (+1 line: loads core/Portal_error_handlers.php)
├── .htaccess                  (rewrite rules; strips index.php)
├── system/                    (CodeIgniter 3 core, untouched)
├── assets/
│   ├── css/bootstrap.min.css  (user-downloaded, self-hosted)
│   ├── css/app.css            (ALL custom styling, token-based light/dark theme)
│   ├── js/bootstrap.bundle.min.js
│   ├── js/app.js              (all UX behaviour, see §6)
│   ├── js/qr.js               (QR generator, Kazuhiko Arase MIT lib bundled; TCQR.svg())
│   ├── img/                   (favicon.svg, icon-192/512.png, apple-touch-icon.png)
│   └── manifest.json          (PWA "Add to Home screen")
├── uploads/
│   ├── proofs/     (payment proof files; .htaccess "Require all denied", served by controller)
│   ├── materials/  (lecturer uploads)
│   ├── assignments/ (lecturer question papers; denied, served by the assignments controllers)
│   ├── submissions/ (students' handed-in work; denied, served by the assignments controllers)
│   └── photos/     (profile photos; .htaccess deny; served by Photo controller)
└── application/
    ├── config/     autoload, config, database, migration (version 15), routes,
    │               email.php (SMTP, off by default), portal.php (legacy; replaced by settings table)
    ├── core/
    │   ├── MY_Controller.php        Auth_Controller (+ _send_file, _store_upload), Admin_/Lecturer_/Student_Controller
    │   ├── MY_Exceptions.php        routes PHP errors / exceptions / DB errors / internal 404s into error_reports
    │   └── Portal_error_handlers.php  replacement _exception_handler (friendly 500 + reference code)
    ├── controllers/  (see §5)
    ├── models/       Course_model, Course_lecturer_model, Enrollment_model, Payment_model,
    │                 Material_model, Notification_model, User_model, Dashboard_model,
    │                 Error_model, Receipt_model, Assignment_model (assignments + submissions)
    ├── libraries/    Audit.php, Notifier.php, Settings.php
    ├── helpers/      ui_helper.php (icons, nav, badges, avatars, settings, time_ago...),
    │                 chart_helper.php (server-side SVG bar + donut charts)
    ├── migrations/   001–015 (see §7)
    └── views/
        ├── templates/  header.php, footer.php   (the whole app shell)
        ├── partials/   id_card.php
        ├── dashboard/  admin, student, lecturer, _announcements, _checklist
        ├── admin/      payments_pending, courses, students, lecturers, admins, user_card, _credentials,
        │               announcements, errors, error_view, audit, settings
        ├── student/    courses, upload_payment, payments, materials_index, materials_course,
        │               assignments_index, assignment_view
        ├── lecturer/   materials_index, materials_course, assignments_index, assignment_form,
        │               assignment_view, dashboard (legacy)
        ├── profile/    index, _about_form
        ├── payments/   receipt
        ├── support/    help, report, forgot
        ├── notifications/ index
        ├── verify/     index   (standalone public page, no app shell)
        ├── auth/       login, register
        └── errors/html/ _portal_error (shared branded page), error_404, error_general, error_db,
                         error_exception (dev; shows error reference), error_php (CI default)
```

---

## 5. Controllers and routes (all flat names, no sub-folders)

| Controller | Base | Purpose |
|---|---|---|
| `Auth` | CI_Controller | login (lockout, `after_login` redirect, session regenerate), register (privacy consent), logout |
| `Dashboard` | Auth_Controller | one URL, different dashboard per role |
| `Courses` | Student_Controller | browse/apply; shows lecturers, rejection reasons |
| `Payments` | Student_Controller | `upload/{enrollment_id}`, `index` (My Payments), `receipt/{id}` |
| `Student_materials` | Student_Controller | `course/{id}`, `download/{id}`; both re-check `has_active_access()` |
| `Lecturer_materials` | Lecturer_Controller | post/delete materials; `is_assigned()` guard; notifies students |
| `Lecturer_assignments` | Lecturer_Controller | `index`, `create/{course}`, `edit/{id}`, `delete/{id}` (POST, only with no submissions), `view/{id}` (marking sheet), `grade/{submission}` (POST), `submission_file/{submission}`, `attachment/{id}`; `is_assigned()` on everything |
| `Student_assignments` | Student_Controller | `index` (to hand in / done), `view/{id}`, `submit/{id}` (POST), `attachment/{id}`, `my_file/{id}`; `has_active_access()` on everything |
| `Notifications` | Auth_Controller | inbox (grouped Today/Earlier), `open/{id}` |
| `Profile` | Auth_Controller | details, `save_more` (extended profile), `change_password` |
| `Photo` | Auth_Controller | `view/{id}` (permission-checked), `upload`, `remove`, `upload_for/{id}` (admin) |
| `Support` | CI_Controller (public) | `help`, `report` (form + AJAX), `js` (JS-error beacon), `forgot` |
| `Verify` | CI_Controller (public) | `/verify/{token}`: ID-card QR verification page |
| `Admin_payments` | Admin_Controller | pending cards (inline proof preview), `history`, `approve`, `reject` (reason), `view_proof`, `receipt` |
| `Admin_courses` | Admin_Controller | create course, assign/unassign lecturers |
| `Admin_users` | Admin_Controller | `students`, `lecturers`, `card/{id}`, `update_account`, `save_profile`, `reissue_card`, `export_students` (CSV), `create_lecturer`, `reset_password`, `toggle_status`; **admins**: `admins`, `create_admin`, `update_admin/{id}` (name/email/phone, self allowed), `reset_admin_password/{id}`, `toggle_admin_status/{id}` (POST only, never on yourself, so there's always an admin left) |
| `Admin_announcements` | Admin_Controller | create / toggle / delete |
| `Admin_errors` | Admin_Controller | list (tabs open/resolved/ignored, source filter), `view/{id}`, `update/{id}` |
| `Admin_audit` | Admin_Controller | filterable timeline + `export` (CSV) |
| `Admin_settings` | Admin_Controller | organisation settings form (`$groups` defines all fields) |
| `Migrate` | CI_Controller | visit `/migrate` to run pending migrations. **Delete after use; keep a copy outside the project** |
| `Lecturer_dashboard`, `Welcome` | legacy | unused leftovers, safe to delete |

Routes: `default_controller = auth/login`, `login`, `logout`, `register` shortcuts, `verify/(:any) → verify/index/$1`.

---

## 6. Architecture conventions (follow these in new stages)

### Access control
- Extend the right base controller (`Admin_Controller`, `Lecturer_Controller`, `Student_Controller`,
  or `Auth_Controller` for any logged-in user). The role check happens in the constructor.
  Use `$this->current_user_id` / `$this->current_role`.
- **Student access to course content always goes through `Enrollment_model::has_active_access($userId, $courseId)`.**
  Check it on every page *and* every file download.
- Lecturers can only touch courses where `Course_lecturer_model::is_assigned()` is true.

### Cross-cutting libraries (autoloaded or loaded on demand)
- **Audit**: `$this->audit->log('area.action', 'entity', $id, 'Human description', [meta])`.
  Log every meaningful action in new stages (e.g. `assignment.created`, `submission.graded`).
  Action prefixes drive icons and filters. The table is append-only.
- **Notifier**: `$this->notifier->notify_course($courseId, $msg, $link)` (all active students)
  and `notify_user($userId, $msg, $link)`. Creates in-app notifications; sends email only if
  `config/email.php` has `smtp_configured = true`.
- **Settings**: `$this->settings->get('org_name')`, or the `setting('key', 'default')` helper in views.

### Views and layout
- Every page: `$this->load->view('templates/header', ['title' => '...'])` → page view → `templates/footer`.
- Header and footer get their shared data from **`layout_context()`** (in `ui_helper`): user, role,
  photo, ID number, nav items, badge counts. **CI3 doesn't share local variables between views**, so
  anything the layout needs must come from there.
- In views, access models and the DB through `$CI =& get_instance()`, not `$this->...` (models loaded
  after the controller ran aren't visible on the view loader).
- Variables passed to one view *are* cached for later partials (e.g. `$checklist` reaches `_checklist.php`).
- **Menus** are defined once in `nav_items($role)` (`ui_helper`). Fields: `key, label, url, icon, section,
  mobile` (shown in phone bottom bar), `badge`, `exact` (for controllers shared by two pages),
  `soon` (greyed "coming soon"). For Stage 5, change the `exams` items from `soon` to real links.
  Badge keys (computed in `layout_context()`): `notifications, payments, errors, resets` (admin),
  `marking` (lecturer: submissions to mark), `assignments` (student: to do + overdue). Phone bottom bar fits
  **5 items** (+ "More" if anything is left over); students' Payments was moved off it to make room for Assignments.
- The **Ctrl+K quick-search palette** entries come from `palette_items($role)`. Add new pages and actions there.
- Page structure: `.page-head` > `.page-title` + `.page-sub`; cards with `.card-head` / `.card-heading`;
  lists `.people-list` / `.issue-list`; status via `status_badge($status)` → `.pill`.
- Helpers: `icon($name, $size)` (inline SVG, Feather style), `avatar_html(...)`, `money()`, `time_ago()`,
  `greeting()`, `initials()`, `receipt_no()`, `wa_link($phone, $text)` (normalises `07…` → `2637…`),
  `status_badge()`, `svg_bar_chart()`, `svg_donut_chart()`.

### Private files
- Save uploads with `$this->_store_upload($field, $folder, $types, $maxKb, $error)` (Auth_Controller): returns
  `['path','name']`, `null` (no file chosen) or `false` (+ `$error`). Folder goes under `uploads/`, add a deny `.htaccess`.
- Serve them with `$this->_send_file($path, $friendlyName)` **after** the access check. Images/PDF open inline,
  the rest download; names are cleaned for Windows.
- `uploads/*/*` is git-ignored (except `index.html` / `.htaccess`). Older proofs/photos committed in the
  initial commit are still tracked: don't `git rm` them (a pull would delete them from the laptop).

### Forms and feedback
- Set flashdata, then **`redirect()`**. Flashdata only shows on the *next* request (this was a real bug
  twice, and nearly a third time in v6: if you re-render a form in the same request, pass the error to the view). Validation errors re-rendered in the same request show automatically via the header.
- CI's `decimal` rule rejects whole numbers ("50"); use `numeric` (fixed for course fees in v6).
- JS adds these to **every POST form** automatically: busy spinner + double-submit lock.
  `data-confirm="..."` (+ `data-confirm-ok`, `data-confirm-danger`) gives a styled confirm modal on forms or links.
- Other data-attributes: `data-copy`, `data-table-filter="#table"`, `data-report-open`, `data-palette-open`,
  `data-theme-toggle`, `data-theme-choice`, `data-sidebar-toggle`, `data-photo-form`/`data-photo-input`,
  `data-print-card`, `data-id-flip`, `data-qr="text"`, `data-dropzone`, `data-strength`, `data-announcement`.

### Styling
- All colours are **CSS variables** in `app.css` with light and `[data-bs-theme="dark"]` values
  (`--surface-0..3`, `--text-1..3`, `--border`, `--accent`, `--soft-*-bg/fg`, chart `--c0..c7`).
  **Never hard-code colours in new components**, or dark mode breaks.
- Brand: navy `#1F3864` + gold `#C9A227` (matches the client report).
- Charts are **server-rendered SVG** (no JS chart library) so they work offline and re-colour with the theme.
- Mobile-first: sidebar on desktop (collapsible to an icon rail), floating bottom tab bar on phones.

### Database
- Schema changes **only via numbered migrations** in `application/migrations/` (sequential type).
  Bump `$config['migration_version']` in `config/migration.php`, then the user visits `/migrate`.
- Code that touches new tables should guard with `$this->db->table_exists()` or `field_exists()`
  so pages don't crash before the user migrates (this happened with `photo_path`/`id_number` notices).
- The user once installed code **without backing up**. Always remind them: phpMyAdmin → Export → Go first.

---

## 7. Database (migration 15)

| Table | Key columns / notes |
|---|---|
| `users` | id, **id_number** (unique, `TCS-/TCL-/TCA-YYYY-NNNN`), **verify_token** (unique, 20 chars, QR secret), name, email (unique), phone, photo_path, photo_updated_at, password_hash (bcrypt), role enum(admin, lecturer, student), status enum(active, inactive), last_login_at, reset_requested_at, timestamps |
| `user_profiles` | 1:1 with users (FK cascade): title, date_of_birth, gender, national_id, alt_phone, address_line1/2, city, province, country, postal_code, emergency_name/relationship/phone, church_name, denomination, ministry_role, education_level, occupation, referral_source, qualifications, bio, privacy_consent_at |
| `courses` | name, description, fee_amount, duration_text, status |
| `course_lecturers` | course_id, user_id (unique pair) |
| `enrollments` | user_id, course_id (unique pair), status enum(pending_payment, active, completed, suspended), enrolled_at |
| `payments` | enrollment_id, amount, method enum(ecocash, bank_transfer), proof_file_path, proof_original_name, status enum(pending, approved, rejected), admin_note (rejection reason), submitted_at, reviewed_by, reviewed_at |
| `materials` | course_id, lecturer_id, title, description, file_path, external_link |
| `notifications` | user_id, message, link, is_read |
| `error_reports` | reference (`ER-XXXXXX`), fingerprint (sha1 groups repeats), source enum(user, php, exception, not_found, javascript, database), severity, title, details, url, user_id, occurrences, status enum(open, resolved, ignored), admin_note, resolved_by/at, first/last_seen |
| `audit_log` | user_id, user_name, role, action, entity_type, entity_id, description, meta (JSON), ip_address, user_agent, created_at. Failed logins use `entity_type = 'email:<address>'` for lockout counting |
| `announcements` | title, body, audience enum(all, student, lecturer), tone enum(info, success, warning), is_active, expires_at, created_by |
| `settings` | setting_key (PK), setting_value, updated_at. Org name/short name/initials/tagline/registration no., phone, WhatsApp, email, website, office hours, address fields, **payment details** (`pay_*`), receipt footer, ID-card validity/note, privacy notice |
| `assignments` | course_id, lecturer_id (who set it; any lecturer on the course can manage it), title, instructions, attachment_path/name, **due_at**, max_score (default 100), allow_late (1 = a *first* submission is still accepted after the due date, flagged late) |
| `assignment_submissions` | assignment_id + student_id (**unique pair**; resubmitting replaces the row and old file, `attempts`++), file_path, original_name, answer_text (typed answer), submitted_at, is_late, score DECIMAL(6,2), feedback, graded_by, graded_at (NULL = waiting to be marked) |
| `migrations` | version = 15 |

Migrations: 001 users · 002 courses · 003 course_lecturers · 004 enrollments · 005 seed admin
(`admin@example.com`) · 006 payments · 007 materials · 008 notifications · 009 error_reports ·
010 audit_log · 011 announcements · 012 last_login/reset_requested · 013 id_number + photos (backfills IDs) ·
014 settings + user_profiles + verify_token (backfills tokens, carries over `portal.php` payment details) ·
015 assignments + assignment_submissions.

**Stage 5 will start at migration 016.**

Submission rules live in `Assignment_model::can_submit()` / `student_state()` (todo, overdue, missed, submitted,
graded): resubmit freely until the due date; after it, only a first submission and only if `allow_late`; never once marked.

---

## 8. What works today, by role

**Everyone**
- Branded split-screen login/register (privacy consent at registration). Forgot password notifies
  admins, who reset it and share the new password on WhatsApp (no email needed).
- Brute-force lockout: **5 failed logins in 15 minutes locks that email for 15 minutes**.
- Dashboards with a welcome banner, stat cards, checklists and announcements. Notifications with an
  unread badge. My Profile: details, extended "About you" with a completeness meter, photo upload
  (phone camera, cropped and resized in the browser, re-encoded by the server to strip EXIF/GPS),
  password change, Appearance (Light/Dark/Auto).
- **Two-sided flip ID card** with photo, ID number, courses and validity; the back has a **QR code**
  linking to `/verify/{token}` plus the org address. Print prints only the card.
- Ctrl+K quick search, dark mode toggle (top bar + menu + profile), collapsible sidebar, phone bottom
  tabs, offline banner, page-load progress bar, caps-lock warning, Help & Contact page (WhatsApp/call/
  email/map, FAQ, privacy notice), Report a problem (AJAX, attaches the current page, returns a reference).
- Installable as a phone app (manifest + icons; full install prompt needs HTTPS).

**Students:** apply for courses (multiple courses allowed), drag-and-drop proof upload with preview and
size check, see rejection reasons and re-upload, My Payments with printable **receipts** (`TC-000012`),
materials per paid course, onboarding checklist. **Assignments** page (to hand in / handed in & marked) with a
nav badge; hand in a document (up to 10MB) and/or a typed answer; replace it until the due date; late hand-in if
allowed; mark + % + feedback once marked. Dashboard "Assignments due" card lists what's outstanding.

**Lecturers:** dashboard (courses, students-per-course chart, recent posts, **work to mark**), post/delete
materials (file and/or link), which notifies students. **Assignments:** set work per course (instructions,
optional question paper, due date/time, marks out of, accept-late switch), which notifies paid-up students;
changing the due date notifies them again. Marking sheet per assignment: every student on the course (not yet
marked first), open their file or typed answer, give a mark + feedback (student notified; re-marking allowed and
logged with the old mark), **WhatsApp "Remind"** button for students who haven't handed in, stats (handed in,
to mark, average). Can't delete an assignment once anyone has handed in.

**Admins:** dashboard (fees-per-month bar chart, enrollments-by-course donut, pipeline, recent payments,
newest students, **setup checklist** that flags the default password, `admin@example.com` and a missing second admin, **Recent activity**, **System
health**); payments (cards with inline proof images, approve, reject with reason, history, receipts);
courses and lecturer assignment; **Students** list (search, last seen, reset requests highlighted, reset
password, deactivate, CSV export); lecturers (auto-generated temp passwords like `Cedar-4827`, one-time
credentials card with **Send on WhatsApp**); **Administrators** page (add admins with the same temp-password
card, edit any admin's name/login email/phone including your own, reset or deactivate *other* admins); user card pages (photo upload, full profile edit, reissue ID
card); announcements; **Error reports**; **Audit trail** (+ CSV); organisation **Settings**.

**Automatic error capture:** PHP errors and warnings, uncaught exceptions (custom handler), DB errors,
internal broken links (404s with an internal referer only, to ignore bots), and JavaScript errors
(beacon to `support/js`, capped per page/session). Repeats are grouped by fingerprint; resolved errors
that recur reopen themselves. Branded error pages show the reference code (technical details only in
`ENVIRONMENT === 'development'`).

---

## 9. Key decisions (and why)

1. **CodeIgniter 3, not 4**: the user already had CI3 set up; lowest friction.
2. **Multiple courses per student** from day one, which avoids a painful retrofit later.
3. **No payment gateway.** Students pay by EcoCash or bank transfer and upload proof; an admin approves
   it. Approving calls `Enrollment_model::activate()`, which is the single moment access turns on.
4. **One access-gate method** (`has_active_access`) used everywhere content is shown or downloaded.
5. **Private files are served through controllers** (proofs, photos). Upload folders have deny `.htaccess`.
6. **Flat controller names** (`Admin_payments`), not sub-folders; this avoided routing bugs.
7. **Self-hosted Bootstrap + no JS framework + server-side SVG charts + bundled QR library**, so
   everything works offline and on weak mobile data.
8. **Design language:** Linear/Notion-style light sidebar + Stripe/Vercel-style calm surfaces + Coursera-style
   course cards; navy/gold brand; token-based dark mode, **default "Auto"** (follows the device), stored
   per device in `localStorage` (`tc-theme`, `tc-sidebar`, `tc-dismissed`).
9. **Phone-first admin flows:** WhatsApp handoff for credentials, readable temp passwords, `+263` normalisation.
10. **IDs:** `TCS/TCL/TCA-YEAR-####`, assigned on creation, never reused. **QR uses a random `verify_token`,
    not the ID number**, so people can't be enumerated. The public verify page shows no photo or contact
    details; logged-in staff see the photo. Reissuing a card rotates the token.
11. **Org details in a `settings` table** (admin-editable), not config files. The hard-coded "Theological
    Center" was replaced by `setting('org_short_name')` etc. (the error pages and `manifest.json` still use defaults).
12. **Extended profile in a separate `user_profiles` table** to keep `users` (the login table) lean.
    Registration stays short; the checklist nudges people to complete their profile.
13. **Security baseline:** bcrypt, session regeneration on login, lockout, audit trail, re-encoded image
    uploads, content-type checks on uploads, no account enumeration on forgot password.
14. **Ezra (AI assistant)**: the user wants a resident assistant named **Ezra**. Agreed plan: build it
    **after Stage 6**, server-side PHP calling an AI API (key never in the browser), **read-only at first**,
    sees only the logged-in user's own data (the server fetches it and passes it to the model),
    **disabled during a student's active exam**, a monthly spend cap, and the Center decides its doctrinal voice.
    Adds a per-message cost line to the client's running costs.

---

## 10. Open items and go-live checklist

- [ ] Confirm the laptop copy of **`reset_admin.php` is deleted** (removed from the repo in v6). Change the admin
      password, and set a real login email on **Administrators → Edit** (no longer stuck on `admin@example.com`).
- [x] ~~Screen to add a second admin account~~ (done in v6: Administrators page). Encourage the user to add one.
- [ ] Fill in **Settings** (org details, payment details) and post a welcome announcement.
- [ ] Delete `Migrate.php` after each migration run (keep a copy outside the project); delete the unused `Welcome.php` and `Lecturer_dashboard.php`.
- [ ] Real people's payment proofs and photos were committed in the initial commit (`uploads/proofs`, `uploads/photos`).
      New uploads are git-ignored now; consider making the GitHub repo private (if it isn't) rather than rewriting history.
- [ ] SMTP in `config/email.php` (`smtp_configured = true`) for email notifications. (`Notifier` email subject still says "Theological Center".)
- [ ] Go-live: hosting (shared plan to start), domain, **HTTPS** (needed for the PWA install prompt and secure
      cookies), set `ENVIRONMENT` to `production`, consider enabling CI's **CSRF protection** (currently off;
      AJAX and beacon endpoints would need exceptions), set `cookie_secure`, and test file-upload limits in `php.ini`
      (`upload_max_filesize` / `post_max_size` must be ≥ 20M for lecturer attachments, 10M for submissions).
- [ ] Several older admin/lecturer actions (e.g. `toggle_status`, `courses/apply`) still work via plain GET links;
      new code requires POST for changes. Tighten the old ones together with CSRF at go-live.
- [ ] To test ID-card QR codes from a phone locally, open the portal via the laptop's LAN IP (e.g.
      `http://192.168.x.x/theological_portal`); a QR made while on `localhost` isn't reachable from a phone.
- [ ] Possible Stage 4 extras if the user asks: "download all submissions as ZIP", returning work for a redo,
      plagiarism notes. Not built.

---

## 11. Stage 5 (Online exams): suggested starting point

Requirements from the roadmap: timed, open/close window, MCQ + short answer. Suggested shape (adapt as needed):
- `exams`: course_id, lecturer_id, title, instructions, opens_at, closes_at, duration_minutes, max attempts (1),
  show_results (after close / never / immediately), status (draft/published)
- `exam_questions`: exam_id, position, type enum(mcq, short), prompt, options (JSON for MCQ), correct_option, marks
- `exam_attempts`: exam_id, student_id (unique pair), started_at, **deadline_at** (min(started + duration, closes_at),
  enforced **server-side**), submitted_at, auto_score, manual_score, graded_at
- `exam_answers`: attempt_id, question_id, answer, is_correct, marks_awarded

Wire-up checklist (same pattern as Stage 4):
- migration **016**; `has_active_access` / `is_assigned` guards; `Notifier` when an exam is published and when results
  are released; `audit->log` (`exam.*`, `attempt.*`); nav `exams` items (remove `soon`) + badges + palette entries;
  replace the "Upcoming exams" placeholder card on the student dashboard; lecturer dashboard counts.
- Phones on weak data: autosave answers (small AJAX POST per answer), a visible countdown, and accept the final
  submit even if the page reloads; the server's `deadline_at` is the only clock that counts.
- MCQ auto-marked on submit; short answers marked by the lecturer on a sheet like the assignments one.
- Stage 6 (Results) will combine `assignment_submissions.score` and exam scores per enrollment.
