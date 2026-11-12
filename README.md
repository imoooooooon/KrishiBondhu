# Krishi Bondhu (কৃষি বন্ধু)

CSE470 group project, BRAC University. One Laravel (MVC) application with Blade templates and MySQL.

> **Status:** environment and workflow foundation only. There are no product pages, routes, or
> business tables yet. Visiting `/` returns 404 on purpose. `/up` is Laravel's health check.

- Team workflow (branches, PRs, reviews): [CONTRIBUTING.md](CONTRIBUTING.md)
- Step-by-step daily workflow (also for AI agents): [docs/workflow.md](docs/workflow.md)
- Course rules and scope for humans and AI tools: [AGENTS.md](AGENTS.md)
- Decisions, sprint dates, deferred items: [docs/project-context.md](docs/project-context.md)

## Versions

| Tool | Version | Notes |
| --- | --- | --- |
| PHP | 8.4.x (8.4.27 used for setup) | Laravel 13 supports PHP 8.3–8.5 |
| Laravel framework | 13.x (13.42.0 locked) | Exact versions are in `composer.lock` |
| Composer | 2.x (2.11.2 used for setup) | |
| MySQL | 8.4 LTS (8.4.9 used for setup) | InnoDB, utf8mb4 / utf8mb4_0900_ai_ci |
| PHPUnit / Pint | 12.x / 1.x | Installed by Composer (dev) |
| Git | any recent 2.x | |

No Node.js, npm, Vite, or CSS framework is required yet. They will be added only if the team decides to use them.

Required PHP extensions: `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `intl`, `zip`
(plus the ones enabled by default: ctype, dom, filter, hash, pcre, session, tokenizer, xml).

---

## First-time setup (once per computer)

### 1. Install the tools (Windows 11)

Run these in PowerShell (normal user):

```powershell
winget install --id PHP.PHP.NTS.8.4 --source winget
winget install --id Oracle.MySQL --version 8.4.9 --source winget   # click "Yes" on the admin prompt
winget install --id Git.Git --source winget                         # skip if Git is installed
winget install --id GitHub.cli --source winget                      # optional, for PRs from the terminal
```

Close and reopen the terminal, then create `php.ini` with the needed extensions:

```powershell
$php = Split-Path (Get-Command php).Source
$ini = Get-Content "$php\php.ini-development" -Raw
$ini = $ini -replace ';extension_dir = "ext"', "extension_dir = `"$php\ext`""
foreach ($e in 'curl','fileinfo','mbstring','openssl','pdo_mysql','zip','intl','sodium') {
  $ini = $ini -replace "(?m)^;extension=$e\s*$", "extension=$e" }
Set-Content "$php\php.ini" $ini -Encoding ascii
php -m   # should list pdo_mysql, mbstring, intl, zip ...
```

Install Composer with the official Windows installer: <https://getcomposer.org/Composer-Setup.exe>
(it finds the PHP you just installed). Check with `composer -V`.

Set up the MySQL server: open **MySQL Configurator** from the Start menu
(`C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql_configurator.exe`), keep port 3306,
choose a root password you will remember, and let it run MySQL as a Windows service that starts automatically.
Add `C:\Program Files\MySQL\MySQL Server 8.4\bin` to your user `PATH` so the `mysql` command works.

macOS (Homebrew) alternative: `brew install php@8.4 composer mysql@8.4 && brew services start mysql@8.4`.

### 2. Create YOUR local databases

Each teammate has their own databases on their own computer. Do not share one database.
Pick your own password and replace `your-local-password`:

```powershell
mysql -u root -p
```

```sql
CREATE DATABASE krishibondhu      CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE DATABASE krishibondhu_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE USER 'krishibondhu'@'localhost' IDENTIFIED BY 'your-local-password';
GRANT ALL PRIVILEGES ON krishibondhu.*      TO 'krishibondhu'@'localhost';
GRANT ALL PRIVILEGES ON krishibondhu_test.* TO 'krishibondhu'@'localhost';
EXIT;
```

- `krishibondhu` is your development database.
- `krishibondhu_test` is disposable. Tests always use it (`phpunit.xml` forces it), so tests never touch your dev data.

### 3. Clone and configure the project

```bash
git clone https://github.com/imoooooooon/KrishiBondhu.git
cd KrishiBondhu
git switch staging              # daily work starts from staging
composer install                # installs exact versions from composer.lock
composer setup                  # creates .env from .env.example and generates YOUR app key
```

Open `.env` and set `DB_PASSWORD` to the password you chose in step 2.
Never commit `.env`. It is ignored by Git, and each computer has its own key and password.

Set your Git identity once (use the email linked to your GitHub account so commits count as yours):

```bash
git config --global user.name  "Your Name"
git config --global user.email "you@example.com"
```

### 4. Verify

```bash
composer check
```

This validates Composer files, checks formatting, shows the environment, connects to MySQL, and runs the tests.
All steps should pass.

---

## Daily commands

| What | Command |
| --- | --- |
| Start the app | `composer dev` then open <http://127.0.0.1:8000/up> |
| Run all checks | `composer check` |
| Run tests only | `composer test` |
| Check formatting | `composer lint` |
| Fix formatting | `composer format` |
| Install after `composer.lock` changed | `composer install` |
| Clear cached config after editing `.env` | `php artisan config:clear` |

For branches, commits, pushing, and PRs, see [CONTRIBUTING.md](CONTRIBUTING.md).

## Configuration notes

- Sessions and cache use files (`storage/framework`), and queued jobs run synchronously (`QUEUE_CONNECTION=sync`).
  This means the foundation needs **no database tables**. The default Laravel users/cache/jobs migrations were removed
  on purpose. Tables will be added by real features later.
- The app timezone is UTC (Laravel default). How to display Asia/Dhaka time is a later decision.
- CI (GitHub Actions) runs the same checks against a throwaway MySQL 8.4 container on every PR into `staging` or `main`.
