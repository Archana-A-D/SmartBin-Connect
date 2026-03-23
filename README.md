# SmartBin (PHP + MySQL)

This project is a PHP app (XAMPP-style) that uses a MySQL database.

## Important: GitHub vs "Deploy"

GitHub can **store your code** (Git repository), but **GitHub Pages cannot run PHP or MySQL**. To "deploy" this app you need a server/hosting that supports **PHP + MySQL** (shared hosting, a VPS, or a Docker host) and then you can still keep the source on GitHub.

## 1) Put the project on GitHub

In this folder:

```bash
git init
git add .
git commit -m "Initial commit"
```

Create a new repo on GitHub, then:

```bash
git remote add origin <YOUR_REPO_URL>
git branch -M main
git push -u origin main
```

## 2) Deploy to a PHP host (recommended for this project)

1. Buy/use hosting that supports PHP + MySQL (cPanel/shared hosting works well).
2. Upload the project files to your web root (often `public_html/`).
3. Create a MySQL database + user on the host.
4. Import your database schema/data (see next section).
5. Set database credentials via environment variables (see below) or update `backend/db.php`.

### Database import (phpMyAdmin)

- On local XAMPP: export your `smartbin` database from phpMyAdmin (Export -> SQL).
- On the server: open phpMyAdmin -> select your new DB -> Import the `.sql`.

## 3) Configure DB connection (no secrets in git)

`backend/db.php` reads these environment variables (and falls back to local defaults):

- `SMARTBIN_DB_HOST`
- `SMARTBIN_DB_USER`
- `SMARTBIN_DB_PASSWORD`
- `SMARTBIN_DB_NAME`

On many hosts you can set environment variables in the hosting panel; otherwise you may need to configure them in Apache/Nginx/PHP-FPM config (hosting-dependent).

## 4) Split deploy (optional)

If you want, you can host only the static front-end on GitHub Pages, but the PHP API + database must be hosted elsewhere. In that case you'll update your JS to call the hosted API URL (not `localhost`).
