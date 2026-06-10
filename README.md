# Bahria Papers Portal

An open-source PHP/cPanel-style past papers portal for students to upload, browse, preview, and download examination papers.

## Features

- Student registration and email verification
- Password reset emails through Brevo Transactional Email API
- Paper upload, search, preview, download, favorites, and activity tracking
- JSON API endpoints for mobile/web clients
- Request and notification workflows

## Open-Source Scope

This public version intentionally excludes the private admin portal and production runtime data. The `admin/` source, uploaded files, logs, cache contents, and deployment-specific secrets should not be committed.

## Requirements

- PHP 8.0 or newer
- MySQL or MariaDB
- PHP extensions: `pdo_mysql`, `curl`, `json`, `mbstring`
- A Brevo Transactional Email API key for outbound email

## Local Setup

1. Clone the repository.
2. Create a database named `papers`.
3. Import `database/schema.php`.
4. Copy `.env.example` to `.env` or configure the same variables in your hosting environment.
5. Set `DB_*`, `SITE_URL`, `JWT_SECRET`, and `BREVO_API_KEY`.
6. Ensure `uploads/` is writable by PHP.
7. Serve the project from your PHP web server.

Example with PHP's built-in server:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000
```

## Configuration

The project reads configuration from environment variables in `config.php` and `api/config.php`.

Important variables:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `SITE_URL`
- `BREVO_API_KEY`
- `FROM_EMAIL`, `FROM_NAME`
- `JWT_SECRET`
- `CORS_ALLOWED_ORIGINS`

Never commit real credentials or production values.

More details are available in `docs/CONFIGURATION.md` and `docs/ARCHITECTURE.md`.

## Documentation

Start with `docs/README.md`.

Core docs:

- `docs/INSTALLATION.md` - local setup
- `docs/PROJECT_STRUCTURE.md` - folder and file map
- `docs/ARCHITECTURE.md` - architecture overview
- `docs/CONFIGURATION.md` - environment variables
- `docs/DATABASE.md` - schema and migrations
- `docs/API.md` - API routes and auth
- `docs/EMAIL.md` - Brevo email implementation
- `docs/USER_FLOWS.md` - main app workflows
- `docs/DEVELOPMENT.md` - contributor development guide
- `docs/DEPLOYMENT.md` - production/cPanel deployment
- `docs/TROUBLESHOOTING.md` - common issues
- `docs/PRIORITIES.md` - prioritized issues for contributors

## Email Sending

Application email is sent through Brevo Transactional Email API over HTTPS using cURL:

```text
POST https://api.brevo.com/v3/smtp/email
```

SMTP is intentionally not used for application delivery.

## Admin Portal

The private admin portal is not included in this open-source release. If you build an admin interface for your fork, keep privileged operations protected and do not expose admin routes, logs, queue secrets, or credentials in public repositories.

## GitHub Remote

After reviewing the sanitized files:

```bash
git init
git remote add origin https://github.com/junejosamad/papers.git
git add .
git commit -m "Prepare open-source release"
git branch -M main
git push -u origin main
```

## Security

Please read `SECURITY.md` before reporting vulnerabilities.
