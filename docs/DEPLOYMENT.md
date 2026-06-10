# Deployment

This project is designed to work on typical PHP/cPanel hosting.

## Production Checklist

1. Upload source files.
2. Do not upload `.env.example` as `.env` with placeholder values.
3. Create a real `.env` or configure environment variables in hosting.
4. Import the database schema.
5. Run `api/safe_migrate.php` once.
6. Make `uploads/` writable.
7. Confirm `uploads/.htaccess` prevents PHP execution.
8. Configure Brevo API key and verified sender.
9. Set `API_ENV=production`.
10. Set a long random `JWT_SECRET`.
11. Set `CORS_ALLOWED_ORIGINS`.
12. Disable PHP error display in production.
13. Use HTTPS.

## cPanel Notes

Use cPanel tools for:

- MySQL Database Wizard
- phpMyAdmin import
- File Manager permissions
- MultiPHP INI Editor if extensions/settings need adjustment
- Terminal, if available, for `php -l`

## Environment Variables

If the host does not expose environment-variable management, create a `.env` file in the project root.

Never expose `.env` publicly. The `.gitignore` prevents committing it.

## Upload Directory

`uploads/` must be writable by PHP. The committed `.htaccess` blocks direct PHP execution inside that folder.

## Email

Brevo API must be configured:

```env
BREVO_API_KEY=replace-with-brevo-api-key
FROM_EMAIL=papers@example.com
FROM_NAME=Bahria Papers Portal
```

If Brevo blocks the server IP, add the hosting server outbound IP to Brevo authorized IPs.

## API CORS

For production:

```env
API_ENV=production
CORS_ALLOWED_ORIGINS=https://example.com,https://app.example.com
```

## After Deployment

Test:

- Landing page
- Signup email
- Password reset email
- Login
- Paper upload
- Paper preview/download
- API `/api`
- API login route

## Optional Support/Donation Display

The dashboard support prompt is configured through environment variables:

```env
SUPPORT_PAYMENT_LABEL=Support
SUPPORT_PAYMENT_VALUE=Configure payment details
SUPPORT_PAYMENT_NOTE=Set this in your deployment
```

Use public-facing values only. Do not commit real personal/payment details to Git.
