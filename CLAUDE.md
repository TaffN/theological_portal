# CLAUDE.md: Theological Center Learning Portal

Handover notes so any Claude session can continue this project without losing context.
Last updated: 26 September 2026 (after Portal **v5**, database migration **14**).

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

### Testing limitation

Claude's sandbox **has no PHP**, so the code can't be executed there. Past sessions compensated by:
- structural checks (bracket balance, `if/endif`/`foreach/endforeach` pairs, PHP tag pairs) on every file,
- cross-checking every `base_url('controller/method')` against real controller methods,
- `node --check` on JS files,
- rendering static HTML mocks with the real `app.css` in headless Chromium (Playwright at
  `/opt/pw-browsers/chromium-1194/chrome-linux/chrome`, with a Bootstrap 5.3.2 copy from
  `/usr/local/lib/python3.12/dist-packages/mkdocs/themes/mkdocs/css/bootstrap.min.css`),
- decoding generated QR codes with OpenCV (`cv2.QRCodeDetector`).

The user's laptop is the first real run, so expect them to report PHP notices or errors
and fix them quickly.

---

## 3. Roadmap and status

| Stage | Scope | Status |
|---|---|---|
| 1 | DB schema, users/roles, courses, enrollments, role-gated base controllers | ✅ Done |
| 2 | Fees & access gating: proof-of-payment upload, admin approve/reject | ✅ Done |
| 3 | Course materials + notifications (in-app, optional email) | ✅ Done |
| (extras) | Admin screens, dashboards + charts, modern UI + dark mode, error reporting, audit trail, IDs, photos, QR ID cards, org settings, extended profiles, help page | ✅ Done (v4/v5) |
| **4** | **Assignments**: lecturers set them, students submit, lecturers mark + feedback | ⏭️ **NEXT** |
| 5 | Online exams: timed, open/close window, MCQ + short answer | ⏳ |
| 6 | Results: publish assignment/exam results per enrollment | ⏳ |
| After 6 | **"Ezra" AI assistant** (see §9). **Remind the user to start Ezra once Stage 6 is done**; they asked for this reminder. | ⏳ |
| Go-live | Hosting, HTTPS, SMTP email, production hardening (see §8) | ⏳ |

**Current state:** v5 is installed on the laptop, DB at **migration 14**. The user lost the
admin password; a one-time `reset_admin.php` recovery script was given (resets
`admin@example.com` to `Recover-2026!` using PHP's own `password_hash`, clears the lockout).
**Confirm they got back in and deleted `reset_admin.php`** before starting Stage 4.

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
│   └── photos/     (profile photos; .htaccess deny; served by Photo controller)
└── application/
    ├── config/     autoload, config, database, migration (version 14), routes,
    │               email.php (SMTP, off by default), portal.php (legacy; replaced by settings table)
    ├── core/
    │   ├── MY_Controller.php        Auth_Controller, Admin_Controller, Lecturer_Controller, Student_Controller
    │   ├── MY_Exceptions.php        routes PHP errors / exceptions / DB errors / internal 404s into error_reports
    │   └── Portal_error_handlers.php  replacement _exception_handler (friendly 500 + reference code)
    ├── controllers/  (see §5)
    ├── models/       Course_model, Course_lecturer_model, Enrollment_model, Payment_model,
    │                 Material_model, Notification_model, User_model, Dashboard_model,
    │                 Error_model, Receipt_model
    ├── libraries/    Audit.php, Notifier.php, Settings.php
    ├── helpers/      ui_helper.php (icons, nav, badges, avatars, settings, time_ago...),
    │                 chart_helper.php (server-side SVG bar + donut charts)
    ├── migrations/   001–014 (see §7)
    └── views/
        ├── templates/  header.php, footer.php   (the whole app shell)
        ├── partials/   id_card.php
        ├── dashboard/  admin, student, lecturer, _announcements, _checklist
        ├── admin/      payments_pending, courses, students, lecturers, user_card, _credentials,
        │               announcements, errors, error_view, audit, settings
        ├── student/    courses, upload_payment, payments, materials_index, materials_course
        ├── lecturer/   materials_index, materials_course, dashboard (legacy)
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
| `Notifications` | Auth_Controller | inbox (grouped Today/Earlier), `open/{id}` |
| `Profile` | Auth_Controller | details, `save_more` (extended profile), `change_password` |
| `Photo` | Auth_Controller | `view/{id}` (permission-checked), `upload`, `remove`, `upload_for/{id}` (admin) |
| `Support` | CI_Controller (public) | `help`, `report` (form + AJAX), `js` (JS-error beacon), `forgot` |
| `Verify` | CI_Controller (public) | `/verify/{token}`: ID-card QR verification page |
| `Admin_payments` | Admin_Controller | pending cards (inline proof preview), `history`, `approve`, `reject` (reason), `view_proof`, `receipt` |
| `Admin_courses` | Admin_Controller | create course, assign/unassign lecturers |
| `Admin_users` | Admin_Controller | `students`, `lecturers`, `card/{id}`, `update_account`, `save_profile`, `reissue_card`, `export_students` (CSV), `create_lecturer`, `reset_password`, `toggle_status` |
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
  `soon` (greyed "coming soon"). For Stage 4, change the `assignments` items from `soon` to real links.
- The **Ctrl+K quick-search palette** entries come from `palette_items($role)`. Add new pages and actions there.
- Page structure: `.page-head` > `.page-title` + `.page-sub`; cards with `.card-head` / `.card-heading`;
  lists `.people-list` / `.issue-list`; status via `status_badge($status)` → `.pill`.
- Helpers: `icon($name, $size)` (inline SVG, Feather style), `avatar_html(...)`, `money()`, `time_ago()`,
  `greeting()`, `initials()`, `receipt_no()`, `wa_link($phone, $text)` (normalises `07…` → `2637…`),
  `status_badge()`, `svg_bar_chart()`, `svg_donut_chart()`.

### Forms and feedback
- Set flashdata, then **`redirect()`**. Flashdata only shows on the *next* request (this was a real bug
  twice). Validation errors re-rendered in the same request show automatically via the header.
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

## 7. Database (migration 14)

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
| `migrations` | version = 14 |

Migrations: 001 users · 002 courses · 003 course_lecturers · 004 enrollments · 005 seed admin
(`admin@example.com`) · 006 payments · 007 materials · 008 notifications · 009 error_reports ·
010 audit_log · 011 announcements · 012 last_login/reset_requested · 013 id_number + photos (backfills IDs) ·
014 settings + user_profiles + verify_token (backfills tokens, carries over `portal.php` payment details).

**Stage 4 will start at migration 015.**

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
materials per paid course, onboarding checklist.

**Lecturers:** dashboard (courses, students-per-course chart, recent posts), post/delete materials (file
and/or link), which notifies students.

**Admins:** dashboard (fees-per-month bar chart, enrollments-by-course donut, pipeline, recent payments,
newest students, **setup checklist** that flags the default password, **Recent activity**, **System
health**); payments (cards with inline proof images, approve, reject with reason, history, receipts);
courses and lecturer assignment; **Students** list (search, last seen, reset requests highlighted, reset
password, deactivate, CSV export); lecturers (auto-generated temp passwords like `Cedar-4827`, one-time
credentials card with **Send on WhatsApp**); user card pages (photo upload, full profile edit, reissue ID
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

- [ ] Confirm the admin login is recovered; **delete `reset_admin.php`**; change the admin password and set a
      real email instead of `admin@example.com`.
- [ ] **Screen to add a second admin account** (promised; do it at the start of Stage 4). There's currently no UI for adding admins.
- [ ] Fill in **Settings** (org details, payment details) and post a welcome announcement.
- [ ] Delete `Migrate.php` after each migration run (keep a copy outside the project); delete the unused `Welcome.php` and `Lecturer_dashboard.php`.
- [ ] SMTP in `config/email.php` (`smtp_configured = true`) for email notifications.
- [ ] Go-live: hosting (shared plan to start), domain, **HTTPS** (needed for the PWA install prompt and secure
      cookies), set `ENVIRONMENT` to `production`, consider enabling CI's **CSRF protection** (currently off;
      AJAX and beacon endpoints would need exceptions), set `cookie_secure`, and test file-upload limits in `php.ini`.
- [ ] To test ID-card QR codes from a phone locally, open the portal via the laptop's LAN IP (e.g.
      `http://192.168.x.x/theological_portal`); a QR made while on `localhost` isn't reachable from a phone.

---

## 11. Stage 4 (Assignments): suggested starting point

Originally planned tables (adapt to current conventions):
- `assignments`: course_id, lecturer_id, title, instructions, due_date, (max_score, allow_late, attachment)
- `assignment_submissions`: assignment_id, student_id, file_path, submitted_at, grade, feedback, graded_at
  (unique per assignment + student; allow resubmission before the due date)

Wire-up checklist:
- migration **015**
- lecturer screens: create/edit, submissions list, grade + feedback
- student screens: list, due dates, upload (reuse the dropzone), view grade and feedback
- `has_active_access` / `is_assigned` guards on everything
- `Notifier` for new assignment and graded submission
- `audit->log` for every action
- nav items (remove `soon`) and palette entries
- replace the "Assignments due" placeholder card on the student dashboard
- lecturer dashboard counts
- submissions served through a controller from a denied `uploads/submissions/` folder
- dark-mode-safe CSS tokens only
