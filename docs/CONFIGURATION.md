# Configuration

The project supports environment variables and a local `.env` file.

Start from:

```bash
cp .env.example .env
```

## Required Variables

| Variable | Purpose |
| --- | --- |
| `SITE_URL` | Base URL used for verification/reset links |
| `DB_HOST` | Database host |
| `DB_NAME` | Database name |
| `DB_USER` | Database username |
| `DB_PASS` | Database password |
| `JWT_SECRET` | Secret used to sign API JWTs |
| `BREVO_API_KEY` | Brevo Transactional Email API key |
| `FROM_EMAIL` | Verified sender email |
| `FROM_NAME` | Sender display name |
| `SUPPORT_PAYMENT_LABEL` | Public label for optional support/donation method |
| `SUPPORT_PAYMENT_VALUE` | Public value shown for optional support/donation method |
| `SUPPORT_PAYMENT_NOTE` | Public note shown under support/donation details |

## Production Notes

- Set `API_ENV=production`.
- Use a long random `JWT_SECRET`.
- Set `CORS_ALLOWED_ORIGINS` to trusted origins only, separated by commas.
- Keep `.env` out of Git.
- Make sure `uploads/` is writable but cannot execute PHP.

## Example

```env
SITE_URL=https://example.com/papers
DB_HOST=localhost
DB_NAME=papers
DB_USER=papers_user
DB_PASS=replace-me
JWT_SECRET=replace-with-long-random-secret
BREVO_API_KEY=replace-with-brevo-api-key
FROM_EMAIL=papers@example.com
FROM_NAME=Bahria Papers Portal
API_ENV=production
CORS_ALLOWED_ORIGINS=https://example.com
SUPPORT_PAYMENT_LABEL=Support
SUPPORT_PAYMENT_VALUE=Configure payment details
SUPPORT_PAYMENT_NOTE=Set this in your deployment
```
