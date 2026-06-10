# Troubleshooting

## Blank Page

Check PHP error logs. Common causes:

- Missing PHP extension
- Database connection failure
- Syntax error
- `.env` missing required values

Temporarily enable error display only in local development.

## Database Connection Failed

Check:

- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- Database user privileges

Confirm the database exists and schema has been imported.

## Login Fails

Check:

- User exists in `users`
- `is_verified = 1`
- Password hash is present
- Account `status` is not banned

## Verification Email Not Sent

Check:

- `BREVO_API_KEY` is configured
- `FROM_EMAIL` is verified in Brevo
- PHP cURL extension is enabled
- Brevo Transactional logs
- Server outbound IP is authorized in Brevo if IP restrictions are enabled

## Brevo `Key not found`

You are probably using the SMTP password instead of a Brevo API key.

Use an API key from Brevo's API Keys page, not the SMTP login/password.

## Brevo `unrecognised IP address`

Add the server outbound IP shown in the error response to Brevo authorized IPs.

## Upload Fails

Check:

- `uploads/` is writable
- File size is below `MAX_FILE_SIZE`
- PHP `upload_max_filesize` and `post_max_size`
- Allowed file type

## Uploaded PHP Execution Risk

The repo includes `uploads/.htaccess` to deny PHP execution in uploads.

On Nginx or other servers, configure equivalent rules.

## API Returns CORS Errors

Check:

- `API_ENV`
- `CORS_ALLOWED_ORIGINS`
- Exact protocol/domain/port of the frontend origin

## API Auth Fails

Check:

- `JWT_SECRET` is consistent across requests
- Authorization header is present
- Token is not expired
- Refresh token exists in `api_tokens`

## Notifications Do Not Show

Check:

- User is logged in
- `notifications` table exists
- `ajax/get_notifications.php` returns JSON
- Browser console for fetch errors

## `php -l` Not Found

PHP is not on PATH locally. Use:

- cPanel Terminal
- Full PHP binary path from hosting
- Local PHP installation
