# API Documentation

The API router is `api/index.php`.

Base URL:

```text
{SITE_URL}/api
```

Example local URL:

```text
http://localhost:8000/api
```

## Response Format

Most endpoints use `ApiResponse` from `api/helpers/response.php`.

Success:

```json
{
  "success": true,
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "error": {
    "code": "SERVER_ERROR",
    "message": "Request failed"
  }
}
```

## Authentication

Protected API endpoints use JWT bearer tokens.

Header:

```text
Authorization: Bearer <token>
```

JWT behavior is implemented in:

- `api/helpers/jwt.php`
- `api/middleware/auth.php`

Set `JWT_SECRET` in the environment.

## Route Map

### Auth

| Method | Route | File | Purpose |
| --- | --- | --- | --- |
| `POST` | `/auth/login` | `api/endpoints/auth/login.php` | Login and issue JWT/refresh token |
| `POST` | `/auth/register` | `api/endpoints/auth/register.php` | Register user and send verification email |
| `POST` | `/auth/verify` | `api/endpoints/auth/verify.php` | Verify email and set password |
| `POST` | `/auth/forgot-password` | `api/endpoints/auth/forgot_password.php` | Request reset email |
| `POST` | `/auth/reset-password` | `api/endpoints/auth/reset_password.php` | Reset password with token |
| `POST` | `/auth/refresh` | `api/endpoints/auth/refresh.php` | Refresh JWT |
| `GET` | `/auth/me` | `api/endpoints/auth/me.php` | Current user |
| `POST` | `/auth/firebase-login` | `api/endpoints/auth/firebase_login.php` | Firebase token login |

### Papers

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/papers` | List/search papers |
| `POST` | `/papers` | Upload paper |
| `GET` | `/papers/trending` | Trending papers |
| `GET` | `/papers/recent` | Recent papers |
| `GET` | `/papers/{id}` | Paper detail |
| `GET` | `/papers/{id}/download` | Download paper |
| `POST` | `/papers/{id}/view` | Increment/register view |

### Favorites

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/favorites` | List favorites |
| `POST` | `/favorites/{paperId}` | Add favorite |
| `DELETE` | `/favorites/{paperId}` | Remove favorite |
| `GET` | `/favorites/{paperId}/check` | Check favorite status |

### Requests

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/requests` | List paper requests |
| `POST` | `/requests` | Create request |
| `GET` | `/requests/{id}` | Request detail |
| `POST` | `/requests/{id}/vote` | Add vote |
| `DELETE` | `/requests/{id}/vote` | Remove vote |
| `GET` | `/requests/{id}/comments` | List comments |
| `POST` | `/requests/{id}/comments` | Add comment |
| `PUT` | `/requests/{id}/close` | Close request |
| `PUT` | `/requests/{id}/fulfill` | Mark fulfilled |

### User

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/user/profile` | Get profile |
| `PUT` | `/user/profile` | Update profile |
| `POST` | `/user/avatar` | Upload avatar |
| `GET` | `/user/stats` | User stats |
| `GET` | `/user/uploads` | User uploads |

### Notifications

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/notifications` | List notifications |
| `GET` | `/notifications/unread` | Unread count |
| `PUT` | `/notifications/read-all` | Mark all read |
| `PUT` | `/notifications/{id}/read` | Mark one read |
| `DELETE` | `/notifications/{id}` | Delete notification |

### Activity

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/activity` | Activity list |
| `GET` | `/activity/stats` | Activity stats |

### Meta

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/meta/campuses` | Campuses |
| `GET` | `/meta/departments` | Departments |
| `GET` | `/meta/semesters` | Semesters |
| `GET` | `/meta/exam-types` | Exam types |

## Adding an Endpoint

1. Add a file under `api/endpoints/{resource}/`.
2. Register the route in `api/index.php`.
3. Validate input with `validateInput()`.
4. Use `ApiResponse::*` helpers.
5. Use `AuthMiddleware` for protected user-specific routes.
