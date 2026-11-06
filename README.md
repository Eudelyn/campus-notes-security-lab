# Campus Notes: security review project (repaired)

Educational lab material. Run it only on an isolated machine you control, with synthetic data and a disposable local database. Never expose it to the internet or connect it to real accounts.

## Local setup

1. Install a supported PHP 8 release with `pdo_mysql` (and preferably `mbstring`) and a local MySQL/MariaDB server. Confirm `pdo_mysql` appears in `php -m`.
2. As a local database administrator, import `schema.sql` into a fresh server/database. If an older `notes_lab` exists, drop it first (`DROP DATABASE notes_lab;`). The repaired schema seeds the two synthetic accounts with bcrypt hashes instead of MD5.
3. Create a dedicated local account with only SELECT, INSERT, UPDATE, DELETE on `notes_lab`, then set the environment variables in the same terminal that runs PHP:
   - PowerShell: `$env:LAB_DB_USER = 'your_local_user'` and `$env:LAB_DB_PASSWORD = 'your_local_password'`
   - Optional: `LAB_DB_HOST` (default `localhost`), `LAB_APP_ORIGIN` (default `http://127.0.0.1:8000`)
   The app refuses to start without credentials; there is no root fallback. Never commit credentials.
4. Run `php -S 127.0.0.1:8000` from this folder and open `http://127.0.0.1:8000` (use 127.0.0.1, not localhost, so the API origin check matches).
5. Synthetic accounts (public, throwaway, lab only): see the original lab handout. Use separate browser profiles for separate sessions.

## Structure

```
index.php                 Web controller: POST actions (CSRF-checked) and page view
api.php                   JSON API: same-origin, session auth, per-owner reads/updates, input validation
includes/bootstrap.php    Error settings, loads the other includes, sanitized exception logging
includes/db.php           PDO connection from environment variables only
includes/security.php     Escaping, secure session start, CSRF, headers, input helpers
includes/auth.php         Register, login (password_hash/verify), logout
includes/notes.php        Owner-scoped note queries and validation
templates/home.php        HTML view; every value escaped with e()
assets/style.css          Styles (moved out of inline CSS so the CSP can be strict)
tests/cross-origin-test.html  Untrusted-origin test page (serve on port 8001)
schema.sql                Database schema with bcrypt seed hashes
```

## Untrusted-origin test

Sign in at `http://127.0.0.1:8000`, then in a second terminal run `php -S 127.0.0.1:8001 -t tests` and open `http://127.0.0.1:8001/cross-origin-test.html` in the same browser profile.

## Production notes (not covered by this local setup)

Use HTTPS (the session cookie becomes `Secure` automatically), a real web server with a document root that exposes only the entry points and assets, secrets from a secret manager, central logging, and login rate limiting.
