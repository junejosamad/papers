# User Flows

## Signup and Verification

Files:

- `login/index.php`
- `verify.php`
- `config.php`

Flow:

1. User enters enrollment number on signup.
2. The app derives or accepts the student email depending on the flow.
3. A verification token is generated.
4. User record is created or updated.
5. `sendVerificationEmail()` sends the verification email through Brevo.
6. User opens the verification link.
7. `verify.php` validates token and expiry.
8. User sets password.
9. Account is marked verified.

## Login

Files:

- `login/index.php`
- `dashboard.php`

Flow:

1. User submits enrollment number and password.
2. Password is verified with `password_verify()`.
3. Session values are set.
4. User is redirected to `dashboard.php`.

## Forgot Password

Files:

- `login/index.php`
- `reset-password.php`
- `config.php`

Flow:

1. User requests a password reset.
2. Reset token and expiry are stored.
3. `sendPasswordResetEmail()` sends a reset link through Brevo.
4. User opens `reset-password.php?token=...`.
5. Token is validated.
6. User sets a new password.
7. Reset token is cleared.

## Browse and Search Papers

Files:

- `dashboard.php`
- `view.php`
- `download.php`

Flow:

1. Dashboard builds filters from query parameters.
2. Papers are fetched from `papers`.
3. User previews a paper in `view.php`.
4. `view.php` increments `views`.
5. User downloads through `download.php`.
6. `download.php` increments `downloads`.

## Upload Paper

Files:

- `upload.php`

Flow:

1. Authenticated user submits paper metadata and file.
2. File extension/type/size are validated.
3. File is saved under `uploads/`.
4. Metadata is inserted into `papers`.
5. Activity is logged.

## Favorites

Files:

- `backpack.php`
- `api/endpoints/favorites/*`

Flow:

1. User toggles favorite state.
2. `favorites` table stores `user_id` and `paper_id`.
3. Backpack/favorites views fetch the user's saved paper IDs.

## Request Board

Files:

- `requests.php`
- `api/endpoints/requests/*`

Flow:

1. User creates a request for a missing paper.
2. Other users can vote and comment.
3. Notifications are generated for votes/comments.
4. Request owner can close the request.
5. API clients can mark requests fulfilled through the API route.

## Notifications

Files:

- `includes/header.php`
- `ajax/get_notifications.php`
- `api/endpoints/notifications/*`

Flow:

1. Feature actions insert rows into `notifications`.
2. Header JavaScript fetches unread notifications.
3. The dropdown renders the latest notifications.
4. API endpoints support list/read/delete behavior for API clients.
