# Contributor Priorities

This file lists issues contributors should fix first. Maintainers should convert these into GitHub Issues and apply the matching labels.

## Labels

- `priority: critical` - security, data loss, broken install, or broken auth/email
- `priority: high` - core user workflow is broken or unreliable
- `priority: medium` - important improvement with clear user value
- `priority: low` - polish, cleanup, or non-blocking enhancement
- `good first issue` - small scoped fix suitable for new contributors
- `help wanted` - maintainers welcome outside implementation
- `area: setup`
- `area: security`
- `area: database`
- `area: frontend`
- `area: api`
- `area: email`
- `area: docs`

## Priority 1: Critical

### 1. Replace Browser-Callable Migration Scripts

Labels: `priority: critical`, `area: security`, `area: database`

Files:

- `api/safe_migrate.php`
- `api/migrate.php`

Problem:

Migration scripts are callable over HTTP. That is risky for public deployments.

Expected fix:

- Make migrations CLI-only, or require a strong one-time migration token from env.
- Document the migration command in `docs/INSTALLATION.md`.
- Ensure public requests cannot run schema changes by default.

Acceptance criteria:

- Visiting migration scripts in a browser does not modify the database.
- Local setup docs still explain how to run migrations safely.

### 2. Consolidate the Database Schema

Labels: `priority: critical`, `area: database`, `area: setup`

Files:

- `database/schema.php`
- `api/safe_migrate.php`

Problem:

The base schema is older than the application. Some features rely on tables/columns created by `api/safe_migrate.php`.

Expected fix:

- Update `database/schema.php` to include all current required tables and columns.
- Keep migrations idempotent for existing installs.
- Update `docs/DATABASE.md`.

Acceptance criteria:

- A fresh install works after importing `database/schema.php`.
- `api/safe_migrate.php` reports no missing required schema on a fresh install.

### 3. Harden File Upload Validation

Labels: `priority: critical`, `area: security`

Files:

- `upload.php`
- `api/endpoints/papers/upload.php`

Problem:

Uploads must be robustly validated before files are stored.

Expected fix:

- Validate extension and MIME type.
- Enforce max file size consistently.
- Generate safe server-side filenames.
- Confirm PHP execution is blocked in `uploads/`.
- Add clear user/API errors for invalid uploads.

Acceptance criteria:

- PHP files cannot be uploaded/executed as papers.
- Only allowed PDF/image formats are accepted.
- Upload errors are user-friendly.

## Priority 2: High

### 4. Add CSRF Protection to Web Forms

Labels: `priority: high`, `area: security`

Files:

- `login/index.php`
- `upload.php`
- `profile.php`
- `requests.php`
- `report.php`
- `reset-password.php`
- `verify.php`

Problem:

State-changing web forms should include CSRF tokens.

Expected fix:

- Add shared CSRF helper functions.
- Add hidden CSRF token inputs.
- Validate token on POST.
- Keep API bearer-token auth separate.

Acceptance criteria:

- Normal forms work.
- Missing/invalid CSRF token rejects POST requests.

### 5. Normalize Auth Rules Across Web and API

Labels: `priority: high`, `area: security`, `area: api`

Problem:

Web routes use sessions; API routes use JWT. Contributors need clearer boundaries and consistent authorization checks.

Expected fix:

- Document which endpoints require session vs JWT.
- Audit API endpoints for `AuthMiddleware::requireAuth()`.
- Audit web routes for `requireLogin()`.

Acceptance criteria:

- Protected routes cannot be accessed anonymously.
- Docs clearly state auth model per route group.

### 6. Improve Error Handling for Production

Labels: `priority: high`, `area: setup`, `area: security`

Problem:

Some code paths may expose raw exception messages or fail with generic output.

Expected fix:

- Ensure production mode logs details server-side.
- Show user-safe messages in UI/API.
- Add guidance in `docs/TROUBLESHOOTING.md`.

Acceptance criteria:

- API errors use `ApiResponse::error()`.
- Production pages do not expose credentials, SQL, or stack traces.

## Priority 3: Medium

### 7. Add Automated Syntax Check Workflow

Labels: `priority: medium`, `area: docs`, `good first issue`

Problem:

Contributors need fast feedback on PHP syntax.

Expected fix:

- Add GitHub Actions workflow for `php -l`.
- Exclude `lib/`.
- Document workflow in `CONTRIBUTING.md`.

Acceptance criteria:

- Pull requests run PHP syntax checks automatically.

### 8. Improve Mobile Responsiveness on Auth and Dashboard Pages

Labels: `priority: medium`, `area: frontend`, `help wanted`

Files:

- `login/index.php`
- `dashboard.php`
- `assets/css/style.css`

Problem:

The landing page was improved, but authenticated pages still need a responsive audit.

Expected fix:

- Test common mobile widths.
- Fix overflow and cramped controls.
- Keep forms usable on small screens.

Acceptance criteria:

- No horizontal overflow at 320px width.
- Main dashboard actions remain reachable on mobile.

### 9. Document API Request/Response Examples

Labels: `priority: medium`, `area: api`, `area: docs`, `good first issue`

Files:

- `docs/API.md`
- `api/docs/index.html`

Problem:

The route map exists, but contributors need concrete example payloads.

Expected fix:

- Add example JSON requests/responses for auth, papers, favorites, and requests.
- Keep examples free of real tokens/user data.

Acceptance criteria:

- A contributor can test core API routes from the docs.

## Priority 4: Low

### 10. Extract Repeated UI Styles

Labels: `priority: low`, `area: frontend`

Problem:

Some pages use inline styles heavily.

Expected fix:

- Move repeated styles into `assets/css/style.css`.
- Avoid large visual rewrites.

Acceptance criteria:

- UI remains visually consistent.
- Inline styles are reduced in touched pages.

### 11. Add Screenshots to README

Labels: `priority: low`, `area: docs`, `good first issue`

Problem:

The README explains features but does not show the app.

Expected fix:

- Add sanitized screenshots under `docs/assets/`.
- Update README with images.
- Do not include real student data or uploaded papers.

Acceptance criteria:

- README has at least landing, login, and dashboard screenshots.
