# Security Policy

## Supported Versions

The `main` branch receives security fixes.

## Reporting a Vulnerability

Do not open a public issue for sensitive vulnerabilities.

Please report privately to the project maintainer through GitHub security advisories or a private contact channel configured by the repository owner.

## Secret Handling

Never commit:

- Database credentials
- Brevo API keys
- SMTP credentials
- JWT secrets
- cPanel mailbox credentials
- Uploaded user files
- Admin logs or queue secrets

Rotate any secret that was previously committed or shared publicly.

## Deployment Checklist

- Set a strong `JWT_SECRET`.
- Set `API_ENV=production`.
- Configure `CORS_ALLOWED_ORIGINS`.
- Configure `BREVO_API_KEY`.
- Ensure `uploads/` denies PHP execution.
- Disable PHP error display in production.
- Use HTTPS.
