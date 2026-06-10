# Contributing

Thanks for helping improve Bahria Papers Portal.

## Development Workflow

1. Fork the repository.
2. Create a feature branch from `main`.
3. Configure local environment variables from `.env.example`.
4. Keep changes focused and avoid committing generated files.
5. Run PHP syntax checks before opening a pull request:

```bash
php -l config.php
php -l api/config.php
php -l includes/brevo_mailer.php
```

For changed PHP files, run:

```bash
find . -name "*.php" -not -path "./lib/*" -print0 | xargs -0 -n1 php -l
```

## Code Style

- Keep PHP changes compatible with shared hosting/cPanel environments.
- Prefer small functions with clear input validation.
- Use prepared SQL statements for all database input.
- Do not expose stack traces, credentials, API keys, or tokens to users.
- Keep application email on Brevo API/cURL, not SMTP.

## Pull Requests

Include:

- What changed
- Why it changed
- How you tested it
- Any database migration or configuration changes

## Do Not Commit

- `.env` files
- API keys, passwords, JWT secrets, SMTP credentials
- Uploaded papers or profile images
- Admin logs or runtime cache files
- Private admin portal code
