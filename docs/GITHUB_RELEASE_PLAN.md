# GitHub Release Plan

## Before First Push

1. Confirm no production secrets exist in the tree:

```bash
rg "xkeysib|xsmtpsib|DB_PASS|JWT_SECRET|bcshub|bytecraftsoft|03705006085|3705006085|MySuperSecretKey123"
```

2. Confirm generated/private paths are ignored:

```bash
git status --ignored --short
```

3. Run syntax checks:

```bash
find . -name "*.php" -not -path "./lib/*" -print0 | xargs -0 -n1 php -l
```

4. Review the files to be committed:

```bash
git status --short
git diff --cached
```

## First Commit

```bash
git add .
git commit -m "Prepare open-source release"
git branch -M main
git push -u origin main
```

## GitHub Repository Setup

Recommended repository settings:

- Add a project description and topics: `php`, `past-papers`, `student-portal`, `mysql`, `brevo`.
- Enable GitHub secret scanning.
- Enable Dependabot alerts.
- Protect the `main` branch after the first push.
- Add issue templates later if contributions grow.

## Do Not Publish

- Private admin portal source
- Uploaded papers/profile images
- Logs/cache files
- Production `.env`
- Database dumps with real users
- cPanel credentials or mailbox passwords
