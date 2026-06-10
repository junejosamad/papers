# Installation

This guide sets up the open-source version locally.

## 1. Requirements

- PHP 8.0+
- MySQL or MariaDB
- PHP extensions: `pdo_mysql`, `curl`, `json`, `mbstring`
- A web server capable of serving PHP
- Brevo Transactional Email API key for real email sending

## 2. Clone

```bash
git clone https://github.com/junejosamad/papers.git
cd papers
```

## 3. Configure Environment

Copy the example file:

```bash
cp .env.example .env
```

Edit `.env`:

```env
SITE_URL=http://localhost:8000
DB_HOST=localhost
DB_NAME=papers
DB_USER=papers_user
DB_PASS=replace-me
JWT_SECRET=replace-with-long-random-secret
BREVO_API_KEY=replace-with-brevo-api-key
FROM_EMAIL=papers@example.com
FROM_NAME=Bahria Papers Portal
```

## 4. Database

Create the database and import the base schema:

```sql
CREATE DATABASE papers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then import:

```bash
mysql -u papers_user -p papers < database/schema.php
```

Run the safe migration once after the base schema exists:

```text
http://localhost:8000/api/safe_migrate.php
```

The migration creates or updates newer feature tables such as favorites, requests, notifications, and API tokens.

## 5. File Permissions

Ensure `uploads/` is writable by PHP:

```bash
chmod 755 uploads
```

On shared hosting, use cPanel File Manager permissions if shell access is unavailable.

## 6. Run Locally

For simple local testing:

```bash
php -S localhost:8000
```

Open:

```text
http://localhost:8000
```

## 7. Verify Setup

- Open the landing page.
- Go to `login/`.
- Create a test account.
- Confirm the verification email is sent through Brevo.
- Upload a small test PDF or image.
- Browse/download it from the dashboard.

## 8. Syntax Checks

Run before committing:

```bash
find . -name "*.php" -not -path "./lib/*" -print0 | xargs -0 -n1 php -l
```
