# Development Guide

## Principles

- Keep the project compatible with shared hosting/cPanel.
- Avoid adding build steps unless they are optional.
- Prefer plain PHP and PDO patterns already used in the project.
- Keep secrets in environment variables or database settings.
- Keep uploaded files and generated cache out of Git.

## PHP Style

- Use prepared statements for database input.
- Validate request data before using it.
- Escape output with `htmlspecialchars()` in HTML contexts.
- Use `filter_var($email, FILTER_VALIDATE_EMAIL)` for email validation.
- Return JSON from API endpoints through `ApiResponse`.

## Shared Helpers

From `config.php`:

- `envValue()` - read environment value with default
- `sanitize()` - basic form input sanitation
- `getSystemSetting()` - read optional DB-backed setting
- `logActivity()` - write activity log
- `isLoggedIn()` / `requireLogin()` - session auth guard
- `sendVerificationEmail()` - send account verification email
- `sendPasswordResetEmail()` - send password reset email

From `includes/brevo_mailer.php`:

- `sendAppEmail()` - standard app email helper
- `sendBrevoEmail()` - low-level Brevo API sender

## Adding a Page

1. Include `config.php`.
2. Call `requireLogin()` if authentication is required.
3. Fetch data with prepared statements.
4. Escape all dynamic HTML output.
5. Keep page-specific JavaScript small and local.

## Adding an API Endpoint

1. Create a file under `api/endpoints/{resource}/`.
2. Register the route in `api/index.php`.
3. Use `getRequestInput()` for JSON/form input.
4. Validate input.
5. Use `AuthMiddleware::requireAuth()` when user identity is required.
6. Return through `ApiResponse`.

## Email Changes

Do not instantiate SMTP clients for app email. Use:

```php
sendAppEmail($to, $subject, $htmlBody, $plainBody, $toName);
```

## Testing Checklist

Run syntax checks:

```bash
find . -name "*.php" -not -path "./lib/*" -print0 | xargs -0 -n1 php -l
```

Manually test:

- Signup and verification
- Login/logout
- Forgot/reset password
- Upload paper
- View/download paper
- Favorites
- Request board
- API login and authenticated route

## Git Hygiene

Before committing:

```bash
git status --short
git diff
```

Do not commit:

- `.env`
- uploaded files
- cache files
- logs
- database dumps
- private admin portal source
