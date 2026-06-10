# Email System

The project sends application email through Brevo Transactional Email API over HTTPS.

SMTP is intentionally not used for production application delivery.

## Files

- `includes/brevo_mailer.php`
- `config.php`

## Public Helper

Use:

```php
sendAppEmail($to, $subject, $htmlBody, $plainBody = '', $toName = '');
```

This wraps:

```php
sendBrevoEmail($to, $subject, $htmlBody, $plainBody = '', $toName = '');
```

## Brevo API Call

Endpoint:

```text
POST https://api.brevo.com/v3/smtp/email
```

Headers:

```text
accept: application/json
api-key: <BREVO_API_KEY>
content-type: application/json
```

Payload:

```json
{
  "sender": {
    "name": "Bahria Papers Portal",
    "email": "papers@example.com"
  },
  "to": [
    {
      "email": "student@example.com",
      "name": "Student"
    }
  ],
  "subject": "Subject",
  "htmlContent": "<p>Hello</p>",
  "textContent": "Hello"
}
```

## Configuration

Set:

- `BREVO_API_KEY`
- `FROM_EMAIL`
- `FROM_NAME`

The sender email must be verified/authenticated in Brevo.

## Existing Email Flows

- `sendVerificationEmail()` in `config.php`
- `sendPasswordResetEmail()` in `config.php`

Both functions:

- Build a secure link using `SITE_URL`
- URL-encode tokens
- Escape user-facing values in HTML
- Send HTML and plain-text content
- Log structured Brevo failures without logging API keys

## Return Shape

`sendBrevoEmail()` returns:

```php
[
    'success' => true,
    'http_code' => 201,
    'response' => '...',
    'error' => ''
]
```

On failure:

```php
[
    'success' => false,
    'http_code' => 401,
    'response' => '{"message":"..."}',
    'error' => ''
]
```

## Common Brevo Errors

- `Brevo API key is not configured.` - missing `.env` value or system setting
- `HTTP 401 Key not found` - wrong key type or invalid Brevo API key
- `HTTP 401 unrecognised IP address` - add server outbound IP in Brevo authorized IPs
- `PHP cURL extension is not enabled.` - enable cURL in PHP

## Rules for Contributors

- Do not add SMTP sending for application email.
- Do not log API keys.
- Do not expose the API key to frontend JavaScript.
- Keep all outgoing app email behind `sendAppEmail()`.
