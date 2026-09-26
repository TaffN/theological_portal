# CLAUDE.md: Theological Center Learning Portal

Handover notes so any Claude session can continue this project without losing context.
Last updated: 26 September 2026 (after Portal **v8** = Stage 6 Results, database migration **17**).

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
| 5 | **Online exams**: timed, open/close window, MCQ + short answer, pools/shuffling, device lock, activity flags, live invigilation, marking, release results | ✅ Done (v7) |
| 6 | **Results**: per-course weighting, calculated overall result + grade, publish/withdraw, student results page, printable statement of results with QR verification, admin overview + CSV | ✅ Done (v8) |
| **Next** | **"Ezra" AI assistant** (see §9, §11). The user asked to be reminded once Stage 6 was done: **reminded at the end of v8**. | ⏭️ |
| Go-live | Hosting, HTTPS, SMTP email, production hardening (see §8) | ⏳ |

**Current state:** v7 (Stage 5 exams + paste hardening) is merged into `main` and on the laptop (DB at 16).
v8 (Stage 6 results) is on branch `claude/inspiring-ramanujan-y9isjf`, waiting for the user to test.
Laptop steps: `git checkout claude/inspiring-ramanujan-y9isjf` → `git pull origin claude/inspiring-ramanujan-y9isjf`
(the local branch already exists, so **pull is required**; a bare checkout just switches to the stale copy, which
happened once) → back up DB → `/migrate` (→ 17) → test → merge into `main` (checkout main, pull the branch, push main).
Note: `/migrate` calls `migration->latest()`, so it always goes *up* to the newest file; the number in
`config/migration.php` is only what the page prints. There is no "go back a version" button.

**Git workflow (the user is new to Git, learning as we go):** `main` = the working version on the laptop. Each piece of
work goes on a branch; the user tests it with `git fetch origin` + `git checkout <branch>`, then it's merged into
`main` (a pull request they merge on GitHub, or pulling the branch into `main` and pushing). Give them exact
commands, one step at a time, with a one-line explanation of each. Remind them the database isn't versioned:
back up before every `/migrate`.

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
│   ├── js/exam.js             (Stage 5 only: question editor toggle, invigilation auto-refresh, exam timer/autosave/monitoring)
│   ├── img/                   (favicon.svg, icon-192/512.png, apple-touch-icon.png)
│   └── manifest.json          (PWA "Add to Home screen")
├── uploads/
│   ├── proofs/     (payment proof files; .htaccess "Require all denied", served by controller)
│   ├── materials/  (lecturer uploads)
│   ├── assignments/ (lecturer question papers; denied, served by the assignments controllers)
│   ├── submissions/ (students' handed-in work; denied, served by the assignments controllers)
│   └── photos/     (profile photos; .htaccess deny; served by Photo controller)
└── application/
    ├── config/     autoload, config, database, migration (version 17), routes,
    │               email.php (SMTP, off by default), portal.php (legacy; replaced by settings table)
    ├── core/
    │   ├── MY_Controller.php        Auth_Controller (+ _send_file, _store_upload), Admin_/Lecturer_/Student_Controller
    │   ├── MY_Exceptions.php        routes PHP errors / exceptions / DB errors / internal 404s into error_reports
    │   └── Portal_error_handlers.php  replacement _exception_handler (friendly 500 + reference code)
    ├── controllers/  (see §5)
    ├── models/       Course_model, Course_lecturer_model, Enrollment_model, Payment_model,
    │                 Material_model, Notification_model, User_model, Dashboard_model,
    │                 Error_model, Receipt_model, Assignment_model (assignments + submissions),
    │                 Exam_model (exams + questions), Exam_attempt_model (sitting, clock, marking, activity),
    │                 Result_model (weighting, calculation, grades, publishing, statement tokens)
    ├── libraries/    Audit.php, Notifier.php, Settings.php
    ├── helpers/      ui_helper.php (icons, nav, badges, avatars, settings, time_ago...),
    │                 chart_helper.php (server-side SVG bar + donut charts)
    ├── migrations/   001–017 (see §7)
    └── views/
        ├── templates/  header.php, footer.php   (the whole app shell)
        ├── partials/   id_card.php, result_breakdown.php
        ├── dashboard/  admin, student, lecturer, _announcements, _checklist
        ├── admin/      payments_pending, courses, students, lecturers, admins, user_card, _credentials,
        │               announcements, errors, error_view, audit, settings, results, results_course
        ├── student/    courses, upload_payment, payments, materials_index, materials_course,
        │               assignments_index, assignment_view, exams_index, exam_view, exam_take, exam_blocked,
        │               results, results_statement
        ├── lecturer/   materials_index, materials_course, assignments_index, assignment_form,
        │               assignment_view, exams_index, exam_form, exam_view, exam_question_form,
        │               exam_invigilate, _invigilate_rows (refreshed via AJAX), exam_attempt, results_index,
        │               results_course, dashboard (legacy)
        ├── profile/    index, _about_form
        ├── payments/   receipt
        ├── support/    help, report, forgot
        ├── notifications/ index
        ├── verify/     index (ID card), results (statement of results)   (standalone public pages, no app shell)
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
| `Lecturer_exams` | Lecturer_Controller | `index`, `create/{course}`, `edit/{id}`, `view/{id}` (questions + students + results), `question/{exam}[/{q}]`, `delete_question`, `move_question/{q}/up\|down`, `publish`, `unpublish`, `delete` (last three POST; unpublish/delete only before anyone starts), `invigilate/{id}`, `live/{id}` (HTML fragment polled every 10 s), `reset_device/{attempt}`, `attempt/{attempt}` (script, marking, activity log), `release/{id}` |
| `Student_exams` | Student_Controller | `index`, `view/{id}` (rules + pledge / continue / waiting / result), `start/{id}` (POST), `take/{id}`, AJAX POST `save/{attempt}` `ping/{attempt}` `event/{attempt}` (JSON), `submit/{attempt}` (POST) |
| `Lecturer_results` | Lecturer_Controller | `index`, `course/{id}` (weighting + everyone's calculated result + remarks), POST `weights/{course}`, `publish/{course}` (ticked students), `withdraw/{result}` |
| `Student_results` | Student_Controller | `index` (published results with breakdown), `statement` (printable, QR) |
| `Admin_results` | Admin_Controller | `index` (per-course overview), `course/{id}`, `export[/{course}]` (CSV) |
| `Result_verify` | CI_Controller (public) | `/results/verify/{token}` (route): the statement's QR page, lists currently published results |
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

Routes: `default_controller = auth/login`, `login`, `logout`, `register` shortcuts, `verify/(:any) → verify/index/$1`,
`results/verify/(:any) → result_verify/index/$1`.

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
  `soon` (greyed "coming soon"). No `soon` items remain since v8 (Results is live for all three roles).
  Badge keys (computed in `layout_context()`): `notifications, payments, errors, resets` (admin),
  `marking` (lecturer: submissions to mark), `assignments` (student: to do + overdue), `exam_marking` (lecturer:
  exam scripts to mark), `exams` (student: open to start or in progress). Phone bottom bar fits **5 items** (+ "More");
  students' bar is Dashboard, Assignments, Exams, Materials, Alerts (Courses and Payments moved to "More").
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
- JS adds these to **every POST form** automatically: busy spinner + double-submit lock. Since v7 this is one
  *delegated* `submit` listener on `document`, so forms injected later (e.g. the live invigilation table) get
  `data-confirm` too. Forms that handle their own submit must `preventDefault()` (the report modal does).
  `data-confirm="..."` (+ `data-confirm-ok`, `data-confirm-danger`) gives a styled confirm modal on forms or links.
- Other data-attributes: `data-copy`, `data-table-filter="#table"`, `data-report-open`, `data-palette-open`,
  `data-theme-toggle`, `data-theme-choice`, `data-sidebar-toggle`, `data-photo-form`/`data-photo-input`,
  `data-print-card`, `data-id-flip`, `data-qr="text"`, `data-dropzone`, `data-strength`, `data-announcement`.

### Cache busting
- CSS/JS links carry a version: `app.css?v=7` (header + both verify pages), `app.js?v=6` (footer), `exam.js?v=2`
  (the three exam views). **Bump the number whenever you change the file**, or browsers keep the old copy
  (v6/v7 forgot to, so the laptop may have run stale CSS/JS until v7.1).

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
  so pages don't crash before the user migrates (this happened with `photo_path`/`id_number` notices; v8's
  results controllers redirect to the dashboard with "needs a database update" until `course_results` exists).
  To test that state in the sandbox, `RENAME TABLE` the new table away and back (`/migrate` can't go down).
- The user once installed code **without backing up**. Always remind them: phpMyAdmin → Export → Go first.

---

## 7. Database (migration 17)

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
| `exams` | course_id, lecturer_id, title, instructions, **opens_at / closes_at** (window), duration_minutes, question_count (pool: NULL = all), shuffle, status enum(draft, published), published_at, results_released, released_at |
| `exam_questions` | exam_id, position, type enum(mcq, short), prompt, options (JSON array), correct_option (index), marks |
| `exam_attempts` | exam_id + student_id (**unique pair** = one attempt), question_ids (JSON, this student's questions in order), option_orders (JSON {qid: [display order]}), **session_token** (device lock; NULL = next device takes over), pledge_at, started_at, **deadline_at** = min(start + duration, closes_at), submitted_at, submit_reason enum(student, time_up), max_score, auto_score (MCQ), total_score (set when fully marked), feedback, graded_by/at, flag_count, away_seconds, ip_address, user_agent, last_seen_at |
| `exam_answers` | attempt_id + question_id (unique), answer (option index or text), is_correct, marks_awarded |
| `exam_events` | attempt_id, type (started, left, returned, paste, bulk_insert, copy, device_blocked, device_reset, network_changed, offline, submitted), detail, seconds, created_at. **Flags** = left, paste, bulk_insert, device_blocked |
| `course_grading` | course_id (PK), assignment_weight, exam_weight (sum 100; default 40/60 when no row) |
| `course_results` | enrollment_id (**unique**), course_id, student_id, assignment_pct, exam_pct (NULL = none counted), assignment_weight, exam_weight (snapshotted), final_pct, grade, remarks, breakdown (JSON list of every item that counted, with mark and %), status enum(published, withdrawn), published_by/at |
| `users.results_token` | 20-char secret in the statement-of-results QR, created on first publish (separate from `verify_token`, so reissuing an ID card doesn't break statements) |
| `migrations` | version = 17 |

Migrations: 001 users · 002 courses · 003 course_lecturers · 004 enrollments · 005 seed admin
(`admin@example.com`) · 006 payments · 007 materials · 008 notifications · 009 error_reports ·
010 audit_log · 011 announcements · 012 last_login/reset_requested · 013 id_number + photos (backfills IDs) ·
014 settings + user_profiles + verify_token (backfills tokens, carries over `portal.php` payment details) ·
015 assignments + assignment_submissions · 016 exams, exam_questions, exam_attempts, exam_answers, exam_events
(+ appends an exam-monitoring paragraph to the `privacy_notice` setting) · 017 course_grading, course_results,
users.results_token, settings `grade_distinction` (75), `grade_merit` (60), `grade_pass` (50), `statement_note`.

**The next schema change is migration 018.**

Submission rules live in `Assignment_model::can_submit()` / `student_state()` (todo, overdue, missed, submitted,
graded): resubmit freely until the due date; after it, only a first submission and only if `allow_late`; never once marked.

**Exam rules** (`Exam_attempt_model`): the server's `deadline_at` is the only clock. Saves are accepted until
deadline + `GRACE_SECONDS` (30). `finalize()` is idempotent (UPDATE … WHERE submitted_at IS NULL) and auto-marks MCQs;
papers with no short answers are fully marked at once. `finalize_expired()` hands in abandoned attempts whenever an
exam page is opened (no cron needed). The device lock is PHP session userdata `exam_lock_{attemptId}` compared with
`exam_attempts.session_token`: a student who logs out or clears cookies mid-exam is locked out of their own
attempt until the lecturer presses **New device** (menus are hidden in exam mode, so logging out is unlikely).
Questions lock once any attempt exists. Correct answers are never sent to the browser before results are released.

**Result rules** (`Result_model::compute_for_course`): assignment % = mean of per-assignment % over assignments whose
due date has passed (not handed in = 0); exam % = mean of per-exam % over published exams that have closed (not sat = 0);
final = weighted (a missing part → the other counts 100%); grade from settings. **Blockers** (row can't be published):
handed-in-but-unmarked assignment, exam attempt not fully marked or exam results not released, or nothing finished yet.
Publishing snapshots the numbers + breakdown; the lecturer page flags rows whose live calculation now differs and
pre-ticks them (never pre-ticks withdrawn ones). Publishing does **not** set the enrollment to `completed`: that would
fail `has_active_access()` and lock the student out of materials.

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

**Exams (v7):** lecturers create an exam (window, time allowed, optional question pool, shuffle), add multiple-choice
(2–6 choices, auto-marked) and short-answer questions (reorder, edit), then publish (checks listed in plain words;
students notified). **Invigilate live**: not started / writing / handed in / flagged counts, per student answered x/y,
time left, "no signal", flags + time away, WhatsApp button, **New device**. Each script shows answers, marks short
answers (+ "Save & next script"), overall feedback, and a timestamped activity log. **Release results** once
everyone has handed in and everything is marked (students notified; re-marks after release notify again).
Students: exam list (open now / coming up / finished), rules + **integrity pledge** + Start (warns if the window
closes before the full time), a distraction-free paper (menus hidden) with a sticky countdown, answered count and
save status; answers autosave (radios instantly, text after 1.5 s), are queued in `localStorage` while offline and
sent when the signal returns; pasting is blocked; leaving the page, pasting, copying, going offline ≥ 10 s and network
changes are reported; auto-hand-in at zero;
**paste hardening (v7.1, after the user found "Force paste" got through):** `beforeinput` insertFromPaste/Drop/Yank
and any non-composition `insertText` ≥ 40 chars are cancelled; the `input` handler and a 1.5 s watchdog undo any
jump of ≥ 40 characters that isn't phone voice/IME composition (flag `bulk_insert`, detail shows the attempted text);
drop and the right-click menu are blocked in the paper; question text can't be selected or copied; result page with %, feedback and a per-question breakdown with correct
answers. Dashboard cards for both roles.

**Results (v8):** lecturers set the assignment/exam weighting per course (slider), see every student's calculated
assignment %, exam %, final % and grade with the reasons any result isn't ready, expand the marks each result is based
on, add remarks, and publish the ticked ones (students notified; re-publishing notifies "updated"; withdraw hides it and
tells the student it's under review). Students: **Results** page (score circle, grade, parts and weights, remarks,
breakdown) and a printable **statement of results** (letterhead, student details, all courses, QR → public
`/results/verify/{token}` page showing what is published *now*). Admins: overview per course (students, published,
average, last published), per-course list, CSV export; grade boundaries + statement note under **Settings → Results**
(kept in order: Distinction ≥ Merit ≥ Pass).

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
- [ ] Possible Stage 5 extras: bulk-import questions from pasted text, extra time for one student, webcam snapshots
      (discussed and deliberately not built: data cost, privacy, needs HTTPS). The Center still has to decide its
      **policy for flagged attempts** (suggested: lecturer reviews, may call the student for a short oral check).
- [ ] Changing an exam's closing time doesn't move the deadline of students already writing (their deadline was
      fixed when they started). Fine in practice; mention it if the user asks about extending time.
- [ ] Possible Stage 6 extras: mark an enrollment "completed" / archive a course without locking materials (needs
      `has_active_access` to accept `completed` for read-only access), per-assignment weights, a combined transcript
      across years, certificates. Not built.
- [ ] The user may circle back to the exams guide doc ("Online Exams: How They Work", a Claude Doc) with changes.

---

## 11. Ezra (AI assistant): agreed starting point

The user asked to be reminded to start Ezra once Stage 6 was done (reminded at the end of v8). Before building,
confirm with them (and the Center) the open decisions: **doctrinal voice / statement of faith** Ezra must follow,
**monthly spend cap**, and which roles get Ezra first (suggested: students, then lecturers).

Agreed shape (§9 item 14):
- A chat panel in the app shell (phone-friendly, like the Ctrl+K palette), answering from the logged-in user's
  **own data only**: the server gathers it (courses, materials titles, assignments due, exam dates, released
  results) and sends it with the question. **Read-only** at first: Ezra explains and guides, it never changes data.
- Server-side PHP calls the AI API with cURL (PHP 7.3); the API key lives in a config file outside git, never in
  the browser. Log each call (tokens, cost) in a table; stop answering when the month's cap is reached.
- **Disabled while the student has an exam attempt in progress** (and the exam page never loads it).
- Messages and answers stored per user (retention decided by the Center); an admin usage page with cost per month.
- Read the Claude API skill/docs before writing the integration (current model IDs, pricing, prompt caching).
