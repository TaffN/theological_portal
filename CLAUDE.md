# CLAUDE.md: Theological Center Learning Portal

Handover notes so any Claude session can continue this project without losing context.
Last updated: 27 September 2026 (after Portal **v11** = documents + admin reports; database migration **20**).

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
| 7 | **Ezra** AI study assistant (students first): chat page, own-data context, statement of faith, monthly cap + daily limit, exam pause, admin usage/settings page (see §11) | ✅ Built (v9), needs an API key |
| 8 | **Campus modules** (v10): Discussions (course boards + General), Calendar (events + auto due dates/exams), Library (college-wide), Attendance (registers, rates, CSV); menus for all roles; Ezra knows them | ✅ Built (v10) |
| 9 | **Documents + Reports** (v11): students/lecturers upload ID copies and qualifications, admins verify/reject, required documents per role; admin Reports (pass rates by province with pie + bars, grades, students by region/gender/…, attendance, fees, documents; print + CSV) | ✅ Built (v11) |
| Go-live | Hosting, HTTPS, SMTP email, production hardening (see §8) | ⏳ |

**Current state:** v8 (Stage 6 results) and v9 (Ezra) were **merged into `main` on 27 September 2026** (fast-forward,
at the user's request). The laptop still has to pull `main`, back up the DB and run `/migrate` (16 → 18) to get them.
For Ezra they also need an Anthropic API key in `application/config/ezra.php` (see §11); without it Ezra says
"being set up" and everything else works. Laptop steps: `git checkout main` → `git pull origin main` → back up DB →
`/migrate` (→ 18) → test. New work goes on a fresh branch again.
**v10 (the four campus modules, migration 19)** was tested by the user and **merged into `main`** (27 Sep 2026).
**v11 (documents + reports, migration 20)** was tested by the user and **merged into `main`** (27 Sep 2026). `main` is now at
migration 20; laptop: `git checkout main` → `git pull origin main` → back up DB → `/migrate` (→ 20). New work goes on the branch again. Manuals v2.0 (v10 + v11) follow on `docs/user-manuals`.
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
│   ├── js/ezra.js             (Ezra chat page only: fetch, typing dots, Enter to send, suggestion chips)
│   (the attendance register's "All present" + live count is a small inline script in attendance/take.php)
│   ├── img/                   (favicon.svg, icon-192/512.png, apple-touch-icon.png)
│   └── manifest.json          (PWA "Add to Home screen")
├── uploads/
│   ├── proofs/     (payment proof files; .htaccess "Require all denied", served by controller)
│   ├── materials/  (lecturer uploads)
│   ├── assignments/ (lecturer question papers; denied, served by the assignments controllers)
│   ├── submissions/ (students' handed-in work; denied, served by the assignments controllers)
│   ├── photos/     (profile photos; .htaccess deny; served by Photo controller)
│   ├── library/    (college library files; deny; served by Library/open after the access check)
│   └── documents/  (ID copies/qualifications; deny; served by Documents/file to the owner or an admin)
└── application/
    ├── config/     autoload, config, database, migration (version 18), routes, ezra.sample.php (→ copy to git-ignored ezra.php with the API key),
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
    │                 Result_model (weighting, calculation, grades, publishing, statement tokens),
    │                 Discussion_model, Calendar_model (events + auto items), Library_model, Attendance_model (registers, rates),
    │                 Document_model (types, checklist, missing, queue), Report_model (pass rates, grades, students_by, fees, documents)
    ├── libraries/    Audit.php, Notifier.php, Settings.php, Ezra_ai.php (Claude API over cURL, context, limits, cost)
    ├── helpers/      ui_helper.php (icons, nav, badges, avatars, settings, time_ago...),
    │                 chart_helper.php (server-side SVG bar + donut charts)
    ├── migrations/   001–020 (see §7)
    └── views/
        ├── templates/  header.php, footer.php   (the whole app shell)
        ├── partials/   id_card.php, result_breakdown.php
        ├── dashboard/  admin, student, lecturer, _announcements, _checklist, _campus (v10: coming up + discussions)
        ├── admin/      payments_pending, courses, students, lecturers, admins, user_card, _credentials,
        │               announcements, errors, error_view, audit, settings, results, results_course, ezra
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
        ├── ezra/       index (the chat page)
        ├── discussions/ index (boards, search, new topic), view (thread, replies, moderation)
        ├── calendar/   index (month grid + day-by-day list), form (add/edit event)
        ├── library/    index (categories, search, add form, cards)
        ├── attendance/ lecturer_index, course (shared with admin), take (register), student, admin_index
        ├── documents/  index (My documents: required checklist, upload, list)
        ├── admin/reports/ _nav (tabs + print/CSV), _filters, index, pass_rates, grades, students, attendance, fees, documents
        │   (+ admin/documents.php review queue, partials/user_documents.php on the admin user card)
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
| `Ezra` | Auth_Controller (role must be in `ezra_roles`) | `index` (chat, current conversation, pause reason), POST `ask` (JSON: `{ok,status,html,left}`), POST `new_thread`. Note: the controller is `Ezra`, so the library is **`Ezra_ai`** (same class name would clash) |
| `Admin_ezra` | Admin_Controller | `index` (spend vs cap, 6-month chart, most active by count only, settings form), POST `save`, `test` (tiny API call), `purge` |
| `Discussions` | Auth_Controller (all roles) | `index` (?board=general\|{course}, ?q=), POST `create`, `view/{id}`, POST `reply/{id}`, `delete/{id}`, `delete_reply/{reply}`, `pin/{id}`, `lock/{id}`. Students: General (once ≥1 paid-up course) + paid-up courses; lecturers moderate their courses + General; admins everything. Topic by staff → notify_course; by student → course lecturers; reply → all participants |
| `Calendar` | Auth_Controller (all roles) | `index` (?m=YYYY-MM), `add` (?date=), `edit/{id}`, POST `save[/{id}]`, `delete/{id}`. Lecturers: events on their courses; admins: any course or whole college (course_id NULL). New/moved course events notify students |
| `Library` | Auth_Controller (all roles) | `index` (?q=, ?category=, ?course=), POST `upload` (staff; file ≤ 20 MB and/or link), `open/{id}` (counts downloads; file or redirect), POST `delete/{id}` (uploader or admin). Students need ≥1 paid-up course |
| `Lecturer_attendance` | Lecturer_Controller | `index`, `course/{id}`, `take/{course}[/{session}]` (GET form, POST save), POST `delete/{session}`; `is_assigned()` |
| `Student_attendance` | Student_Controller | `index` (rate + every mark per paid-up course) |
| `Admin_attendance` | Admin_Controller | `index` (per course), `course/{id}` (read-only), `export/{course}` (CSV) |
| `Documents` | Auth_Controller | `index` (My documents; admins → admin_documents), POST `upload` (pdf/jpg/png ≤ 5 MB; notifies admins), `file/{id}` (owner or admin; admin opens are audited), POST `delete/{id}` (owner unless verified; admins any) |
| `Admin_documents` | Admin_Controller | `index` (?tab=pending\|verified\|rejected\|missing, ?role=, ?q=), POST `review/{id}` (action=verify\|reject, note required to reject, back=card), POST `required` (settings `docs_required_student/lecturer`) |
| `Admin_reports` | Admin_Controller | `index`, `pass_rates` (?by=province\|gender\|city\|denomination\|education_level\|ministry_role, ?course=, ?year=), `grades`, `students` (?by, ?course, ?scope=enrolled\|all), `attendance`, `fees` (?year), `documents`, `export/{report}` (CSV, same filters) |
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
  Items with `'ezra' => true` ("Ask Ezra") are filtered out unless `ezra_offered($role)` (table exists, `ezra_enabled`, role in `ezra_roles`).
  Since v10 the menus are built in `_nav_items_for()` and have a **Campus** section (Calendar, Discussions, Library, + Ask Ezra) for every role;
  Attendance sits in each role's Menu section. Shared-module access goes through `Course_model::for_user / ids_for_user / user_can_see /
  user_can_manage($courseId, $userId, $role)` (student = active enrollments, lecturer = assigned, admin = all).
  Badge keys (computed in `layout_context()`): `notifications, payments, errors, resets` (admin),
  `marking` (lecturer: submissions to mark), `assignments` (student: to do + overdue), `exam_marking` (lecturer:
  exam scripts to mark), `exams` (student: open to start or in progress). Phone bottom bar fits **5 items** (+ "More");
  students' bar is Dashboard, Assignments, Exams, Materials, Alerts (Courses and Payments moved to "More").
- The **Ctrl+K quick-search palette** entries come from `palette_items($role)`. Add new pages and actions there.
- Page structure: `.page-head` > `.page-title` + `.page-sub`; cards with `.card-head` / `.card-heading`;
  lists `.people-list` / `.issue-list`; status via `status_badge($status)` → `.pill`.
- Helpers: `icon($name, $size)` (inline SVG, Feather style), `avatar_html(...)`, `money()`, `time_ago()`,
  `greeting()`, `initials()`, `receipt_no()`, `wa_link($phone, $text)` (normalises `07…` → `2637…`),
  `status_badge()`, `svg_bar_chart()`, `svg_donut_chart()`, v10: `post_format()` (escape + clickable links + line breaks),
  `bytes_fmt()`, `rate_tone()` (attendance colour class). New icons: calendar, library, chat, check-square, pin, download, link, map-pin, headphones, video, chart.
  v11: `svg_pie_chart($labels, $values, ['legend' => [...html]])` (solid pie, % on slices ≥ 6%, stacked legend); palette now 11 colours (`--c0..c10`).
  Badges `documents` (admin: pending) and `docs_todo` (student/lecturer: required documents missing or rejected).
- **CodeIgniter gotcha (bit us in v11):** `where('YEAR(x)', $v, false)` with escaping off does **not** add `=`; write `where('YEAR(x) =', $v, false)`.
  `Error_model::record()` now calls `reset_query()` first, because a half-built failed query otherwise leaked into the error logger's own query.

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
- CSS/JS links carry a version: `app.css?v=10` (header + both verify pages), `app.js?v=6` (footer), `exam.js?v=2`, `ezra.js?v=1`
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

## 7. Database (migration 20)

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
| `ezra_messages` | user_id (FK cascade), thread_no (conversation), role enum(user, assistant), content (wiped to '' after `ezra_retention_days`; row kept for costs), status enum(ok, refused, error), model (as served), input/cache_write/cache_read/output_tokens, cost_usd DECIMAL(10,6), created_at |
| `discussions` | course_id (NULL = General board, FK cascade), user_id, title, body, is_pinned, is_locked, reply_count (kept by `recount()`), last_activity_at |
| `discussion_replies` | discussion_id (FK cascade), user_id, body, created_at |
| `calendar_events` | course_id (NULL = whole college), title, description, event_type enum(class, event, holiday, deadline, other), location, meeting_link, starts_at, ends_at (NULL = no end), all_day, created_by. Assignment due dates and exam windows are **not** stored here: `Calendar_model::items()` reads them live |
| `library_files` | title, author, category (Books, Articles, Commentaries, Sermons, Theses, Audio, Video, Other), description, course_id (optional "recommended for", SET NULL), file_path/original_name/file_size and/or external_link, downloads, uploaded_by |
| `attendance_sessions` | course_id, session_date, topic, taken_by (one register) |
| `attendance` | session_id + student_id (**unique**), status enum(present, late, absent, excused), note, marked_at. Rate = (present + late) / (present + late + absent) |
| `user_documents` | user_id (FK cascade), doc_type (national_id, passport, birth_certificate, qualification, transcript, ordination, reference, other), title, file_path, original_name, file_size, status enum(pending, verified, rejected), review_note, reviewed_by/at, uploaded_at |
| `migrations` | version = 20 |

Migrations: 001 users · 002 courses · 003 course_lecturers · 004 enrollments · 005 seed admin
(`admin@example.com`) · 006 payments · 007 materials · 008 notifications · 009 error_reports ·
010 audit_log · 011 announcements · 012 last_login/reset_requested · 013 id_number + photos (backfills IDs) ·
014 settings + user_profiles + verify_token (backfills tokens, carries over `portal.php` payment details) ·
015 assignments + assignment_submissions · 016 exams, exam_questions, exam_attempts, exam_answers, exam_events
(+ appends an exam-monitoring paragraph to the `privacy_notice` setting) · 017 course_grading, course_results,
users.results_token, settings `grade_distinction` (75), `grade_merit` (60), `grade_pass` (50), `statement_note` ·
018 ezra_messages + settings `ezra_enabled` (1), `ezra_roles` (student), `ezra_monthly_cap_usd` (50), `ezra_daily_limit` (25),
`ezra_model` (claude-opus-5), `ezra_effort` (low), `ezra_bible_version` (NKJV), `ezra_retention_days` (365), `ezra_warned_month`,
`ezra_statement_of_faith` (AG 16 Fundamental Truths summary) (+ appends an Ezra paragraph to `privacy_notice`). `ezra_purged_on` is created on first use ·
019 discussions, discussion_replies, calendar_events, library_files, attendance_sessions, attendance (also `epub` added to config/mimes.php) ·
020 user_documents + settings `docs_required_student` (national_id), `docs_required_lecturer` (national_id,qualification) (+ privacy_notice paragraph).

**The next schema change is migration 021.**

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

**Ezra (v9):** students get **Ask Ezra** (sidebar + "More" on phones, dashboard card, Ctrl+K). A chat page: greeting, suggestion
chips, answers formatted (bold, lists, headings; everything escaped first), typing dots, questions left today, "New
conversation". Ezra knows the student's courses, lecturers, recent material titles, assignments (due, status, marks, feedback),
exams (window, released results) and published overall results, follows the statement of faith, quotes the chosen Bible
version, replies in English/Shona/Ndebele, won't write assessed work, can't change anything. Paused during an exam attempt,
when the month's cap is reached, and at the daily limit. Admins: **System → Ezra (AI)** page.

**Campus modules (v10):** **Discussions**: a board per course + General; start topics, reply (links clickable), search; lecturers/admins pin, close,
delete; authors delete their own posts; notifications for new topics (to the course or its lecturers) and replies (to participants).
**Calendar**: month grid (dots on phones) + day-by-day list; staff add classes/events/holidays (all-day, multi-day, place, online Join link);
assignment due dates and exam open/close times appear automatically; students notified of new/moved course events.
**Library**: college-wide books/articles/commentaries/sermons/theses/audio/video (file ≤ 20 MB or link), category tabs with counts, course filter,
search, download counter. **Attendance**: lecturer register (everyone starts present; tap Late/Absent/Excused; notes; All present/All absent;
correct or delete later), per-student rate with a WhatsApp check-in button under 75%; students see their rate and every mark; admins see every
course and export CSV. Student and lecturer dashboards get a **Coming up** + **Latest discussions / Questions waiting for a reply** row.

**Documents + Reports (v11):** students and lecturers: **My documents** (menu badge when something required is missing) with a checklist of
what the Center requires, upload a photo/scan, see Verified / Waiting / Not accepted (+ reason), remove unverified copies. Admins: **Documents**
review queue (image previews, Verify / Reject with reason, tabs Verified/Rejected/Missing with WhatsApp Remind, required documents per role),
and a Documents card on each user card. **Reports** (admin): overview KPIs; pass rates grouped by province (or gender, city, denomination,
education, ministry role) with a pie of where passes come from + pass-rate bars + table; grades (donut + per-course bars/table); students by
profile field (pie + table); attendance per course; fees per month/course; documents. Filters by course and year; Print/PDF and CSV on every report.
Pass = published result with grade ≠ Fail; region comes from `user_profiles.province` ("Not given" when blank).

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
    Adds a per-message cost line to the client's running costs. **Built in v9** (§11). The Center is Pentecostal
    (Assemblies of God); students first; the user had no budget in mind, so Claude suggested $50/month + 25 questions a day.

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
- [ ] Possible v10 extras (not built): iCal/Google Calendar feed, attendance tied to calendar classes or QR self check-in,
      editing posts and attachments in Discussions, tracking borrowed physical books in the Library. The user manuals
      cover v10 and v11 since manuals v2.0.
- [ ] Possible v11 extras (not built): document expiry dates / renewal reminders, reports over time (pass-rate trends by year),
      a map of provinces, scheduled report emails to the board.
- [ ] **User manuals** (Student, Lecturer, Administrator) live on branch **`docs/user-manuals`** in `docs/manuals/`
      (Markdown + `images/`, README with change log; v1.0 = `a56c06b`, v2.0 (v10 + v11) = `70be078`). The editing copies are Claude Docs
      (links in that README). When the user says to version them: re-read each doc (the markdown export drops images,
      so rewrite the .md with `images/...` links), bump the version in each header + README table + change log,
      commit to `docs/user-manuals` with the version in the message (this sandbox can't push git tags). Retake screenshots (sandbox, made-up data, never the real
      proofs/photos) when pages change. Built from `main`, so it merges into `main` cleanly on its own.
- [ ] The user may circle back to the exams guide doc ("Online Exams: How They Work", a Claude Doc) with changes.

---

## 11. Ezra (AI assistant), built in v9

**Decisions:** the Center is Pentecostal, under the Assemblies of God → default statement of faith = summary of the AG
16 Fundamental Truths (editable on the admin page; the Center should check it against its own). Students first
(`ezra_roles`, lecturers can be ticked on; they get `lecturer_context()` and help preparing teaching). Cap suggested for
<100 students: **$50/month + 25 questions/person/day**, alert to admins at 80%. Model default **Claude Opus 5**
(`claude-opus-5`, $5/$25 per M tokens), Sonnet 5 (`claude-sonnet-5`, $2/$10) selectable. ~2–4 cents per answer on Opus.

**How it works (`libraries/Ezra_ai.php`):**
- Raw cURL to `https://api.anthropic.com/v1/messages` (official PHP SDK needs PHP 8.1+). Headers `x-api-key`,
  `anthropic-version: 2023-06-01`; on Opus 5 also `fallbacks: "default"` + `anthropic-beta: server-side-fallback-2026-07-01`
  (a harmless question declined by Opus's safety filter is retried on the recommended model instead of refused).
- Body: `max_tokens` 16000, `thinking: {type: adaptive}`, `output_config.effort` (setting; low default), two `system`
  blocks with `cache_control` (1: `instructions()`, identical for everyone; 2: the user's own context), then the last
  completed turns of the conversation (`HISTORY_MESSAGES` = 12) + the question (max 2000 chars). Not streamed (short answers).
- Handles curl errors / 401 / 429 / 5xx / 529 / out of credit (friendly text, status `error`, logged), `stop_reason`
  `refusal` (status `refused`), `max_tokens` (note appended). Errors/refusals are not sent back as history.
- Cost from `usage`: input × price + cache writes × 1.25 + cache reads × 0.1 + output × out price (`Ezra_ai::cost`).
- `availability()`: offered to role → configured (key + table + curl) → no exam attempt in progress (`submitted_at` NULL,
  `deadline_at` > now−60 s) → month cost < cap → today's questions < daily limit.
- The controller releases the PHP session lock during the API call (`session_write_close`, then reopens) and blocks a
  second question while one is running (`ezra_busy`).
- Retention: `maybe_purge()` once a day (when the chat opens) wipes the **text** of old messages; rows/costs stay.
- Admins see counts and costs only, never conversation text (privacy). Audit actions: `ezra.settings_saved`,
  `ezra.tested`, `ezra.purged` (questions themselves are not audited).

**Setup on a machine:** console.anthropic.com → add card, buy credit (optionally set a spend limit there too) → create
an API key → copy `application/config/ezra.sample.php` to `application/config/ezra.php` (git-ignored) and paste the key →
admin **Ezra (AI)** page → **Test connection**. PHP's cURL extension must be on (XAMPP usually has it).

**Testing in the sandbox:** no real key. Point `ezra_api_url` in a throwaway `ezra.php` at a fake PHP server (e.g.
`php -S 127.0.0.1:8090`) that records the request and returns canned Messages-API JSON (normal / `refusal` / 529 /
`max_tokens`). Delete the throwaway `ezra.php` afterwards.

**v10 knowledge:** `instructions()` now ends with `portal_guide()`: how every page works (incl. the four campus modules) + the **library
catalogue** (80 newest items, shared, so it's cached). Each user's context adds, per course, attendance (student: rate + recent absences;
lecturer: registers + students under 75%) and recent discussion topics, then the next 30 days of calendar events (multi-day events once, "until"),
the General board, the user's own topics and (lecturers) unanswered questions. v11: the guide explains My documents, and the context lists
the user's required documents with their state and any rejection reasons.

**Possible next steps (not built):** streaming answers, Ezra for lecturers by default, letting Ezra read material
*contents* (PDF text) rather than titles, a per-course "ask about this material" button, Shona/Ndebele UI text.
