# Architecture

## Runtime Entry Points

- `index.php` - public landing page
- `login/index.php` - user login, signup, and forgot-password forms
- `dashboard.php` - authenticated paper browser
- `upload.php` - paper upload form
- `view.php` and `download.php` - paper preview/download flow
- `api/index.php` - JSON API router

## Shared Configuration

- `config.php` starts the session, loads `.env`, defines application constants, creates the PDO connection, and defines shared helpers.
- `api/config.php` adds API-specific settings, CORS, security headers, and JWT configuration.
- `includes/brevo_mailer.php` sends application email through Brevo Transactional Email API over HTTPS.

## Data Storage

- MySQL/MariaDB stores users, papers, requests, notifications, activity logs, and API tokens.
- Uploaded files are stored under `uploads/`.
- API cache files are stored under `api/cache/`.

Runtime files are ignored by Git. Only placeholder files are committed for required directories.

## Email

All application email should use:

```php
sendAppEmail($to, $subject, $htmlBody, $plainBody, $toName);
```

Do not add SMTP sending for application email.

## Admin Portal

The private admin portal is intentionally excluded from this open-source release. Public contributors should not rely on admin-only routes or tables unless they are reintroduced in a separate, reviewed module.
