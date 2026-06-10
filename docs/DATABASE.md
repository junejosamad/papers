# Database Documentation

The project uses MySQL/MariaDB through PDO.

## Setup Files

- `database/schema.php` - base schema
- `api/safe_migrate.php` - idempotent migration for newer columns/tables
- `api/migrate.php` - older migration helper retained for compatibility

For a fresh install, import `database/schema.php`, then run `api/safe_migrate.php` once.

## Main Tables

### `users`

Stores student accounts.

Important columns:

- `id`
- `enrollment_number`
- `email`
- `password_hash`
- `is_verified`
- `verification_token`
- `token_expiry`
- `password_reset_token`
- `password_reset_expiry`
- `profile_image`
- `status`
- `firebase_uid`
- `last_login`

### `papers`

Stores uploaded paper metadata.

Important columns:

- `id`
- `user_id`
- `title`
- `file_name`
- `file_path`
- `file_type`
- `file_size`
- `campus`
- `department`
- `semester`
- `course_code`
- `course_title`
- `teacher_name`
- `year`
- `exam_type`
- `downloads`
- `views`
- `upload_date`

Uploaded files live in `uploads/`; the database stores their path and metadata.

### `activity_logs`

Stores user events such as login, upload, download, and password reset.

### `favorites`

Many-to-many relation between users and papers.

Unique key:

- `user_id`
- `paper_id`

### `paper_requests`

Community request board entries.

Important columns:

- `user_id`
- `campus`
- `course_code`
- `message`
- `status`
- `vote_count`

### `request_votes`

Stores which users voted on which requests.

### `request_comments`

Stores comments on paper requests.

### `notifications`

Stores in-app notifications shown in the header/dropdown.

### `api_tokens`

Stores hashed refresh tokens for API clients.

The raw refresh token is never stored. It is hashed using `JWT::hashRefreshToken()`.

## Migrations

`api/safe_migrate.php` checks for table/column existence before applying changes. This makes it safer for shared-hosting deployments where migration tooling is limited.

Do not run destructive schema changes without a backup.

## Data Safety

Do not commit:

- SQL dumps with real users
- Uploaded papers
- Profile images
- API token data
- Activity logs

## Common Queries

Find a user:

```sql
SELECT id, enrollment_number, email, is_verified, status
FROM users
WHERE enrollment_number = 'XX-XXXXXX-XXX';
```

Find recent uploads:

```sql
SELECT id, title, course_code, upload_date
FROM papers
ORDER BY upload_date DESC
LIMIT 20;
```

Check pending requests:

```sql
SELECT id, course_code, campus, status, vote_count
FROM paper_requests
WHERE status = 'Open'
ORDER BY vote_count DESC, created_at DESC;
```
