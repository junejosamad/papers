# Project Structure

## Root Pages

- `index.php` - public landing page
- `login/index.php` - user login, signup, and forgot-password UI
- `dashboard.php` - authenticated paper browser and dashboard
- `upload.php` - paper upload flow
- `view.php` - paper preview page
- `download.php` - paper download handler and download counter
- `profile.php` - profile and account update page
- `requests.php` - community paper request board
- `report.php` - report paper flow
- `verify.php` - email verification and password setup
- `reset-password.php` - password reset form
- `maintenance.php` - maintenance-mode page
- `logout.php` - session logout

## Shared Code

- `config.php` - environment loading, database connection, app constants, shared helpers, verification/reset email templates
- `includes/brevo_mailer.php` - Brevo API email transport
- `includes/header.php` - authenticated app header and notification dropdown
- `ajax/get_notifications.php` - browser AJAX notification fetch

## API

- `api/index.php` - API router
- `api/config.php` - API config, CORS, security headers
- `api/middleware/auth.php` - JWT authentication
- `api/helpers/` - API response, JWT, cache helpers
- `api/endpoints/` - endpoint implementations by resource
- `api/docs/index.html` - generated/static API docs page
- `api/cache/` - runtime API cache directory, ignored by Git

## Assets

- `assets/css/style.css` - authenticated app/dashboard styles
- `assets/js/darkmode.js` - dark-mode behavior

## Database

- `database/schema.php` - base SQL schema
- `api/safe_migrate.php` - idempotent schema updater for newer feature tables
- `api/migrate.php` - older API migration helper

## Third-Party Code

- `lib/` - vendored PHPMailer library. Application email currently uses Brevo API, but the library is retained for compatibility/history.

## Runtime Directories

- `uploads/` - uploaded papers/profile images. Ignored except placeholder/security files.
- `api/cache/` - generated cache JSON files. Ignored except `.gitkeep`.

## Excluded from Open Source

- `admin/` - private admin portal, intentionally excluded
- `.env` - deployment secrets
- logs, cache files, user uploads, database dumps
