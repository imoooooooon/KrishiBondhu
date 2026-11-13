# First-time setup (do this once per computer)

Welcome to Krishi Bondhu. This guide gets your computer ready to work on the project. It takes about 30–45 minutes.
After this, you only ever follow [docs/workflow.md](workflow.md) for daily work.

You can do the whole setup with **Claude Code** helping you. Some steps need **you**, because they involve a
browser login, a Windows admin prompt, or a password only you should know. Each step below says who does it:

- 🧑 **You:** must be done by you.
- 🤖 **Claude Code:** Claude can run it for you. You watch and approve.

---

## 1. How we work (2-minute overview)

- One Laravel application (PHP 8.4, Laravel 13, Blade, MySQL 8.4). No React/Node app, no Docker.
- Everyone has their **own** local database and `.env` on their own computer. Nothing is shared except the code on GitHub.
- GitHub branches:
  - `main`: stable release. Only updated from `staging` at release time.
  - `staging`: where everyone's finished work is combined and tested.
  - `<yourname>/<task>`: a short-lived branch for **one** piece of work (e.g. `jarif/crop-listing-form`).
- The cycle for every task:
  1. Create a branch from the latest `staging`.
  2. Commit and push often (at least every work session and before each Friday sprint deadline).
  3. Merge the latest `staging` into your branch, run `composer check`.
  4. Open a PR into `staging`. When CI is green, **you merge it yourself** (no approval needed).
  5. Delete the branch. Your commits stay in `staging`/`main` history forever with your name on them.
- **Do not create branches in advance.** Create one when you actually start a task.
- AI tools are allowed as assistants, but you must understand and be able to explain every line in the viva.
  Record AI help in `docs/ai-usage.md`.

Branch prefixes: Emon → `emon/`, Jarif → `jarif/`, Mehrab → `mehrab/`, Sajedul → `sajedul/`.

---

## 2. Before you start 🧑

1. **Accept the GitHub invite** to `imoooooooon/KrishiBondhu`: <https://github.com/imoooooooon/KrishiBondhu/invitations>
2. Know which **email is linked to your GitHub account** (GitHub → Settings → Emails). Your commits must use it, or
   they will not count as yours.
3. Have **Claude Code** installed (VS Code extension or terminal) if you want Claude to help.
4. Get the course PDFs from Emon (project outline, course description, SRS). They are **not** in the repo.

---

## 3. Install the tools (Windows 11)

| Step | Who | What happens |
| --- | --- | --- |
| Install Git and GitHub CLI | 🤖 | `winget install --id Git.Git` and `winget install --id GitHub.cli` (skip if installed) |
| Log in to GitHub CLI | 🧑 | Run `gh auth login`, choose GitHub.com → HTTPS → "Login with a web browser", enter the code in your browser. Claude cannot do this login for you. |
| Install PHP 8.4 | 🤖 | `winget install --id PHP.PHP.NTS.8.4 --source winget` |
| Create `php.ini` with extensions | 🤖 | Uses the PowerShell snippet in [README → Install the tools](../README.md#1-install-the-tools-windows-11) |
| Install Composer | 🤖 / 🧑 | Claude can install it from <https://getcomposer.org/download/> with the signature check, or you run `Composer-Setup.exe` yourself |
| Install MySQL 8.4 LTS | 🤖 + 🧑 | Claude runs `winget install --id Oracle.MySQL --version 8.4.9 --source winget`. **You click "Yes"** on the Windows admin (UAC) prompt. |
| Configure the MySQL server | 🧑 | Open **MySQL Configurator** (Start menu, or `C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql_configurator.exe`). Keep port **3306**, **choose your own root password and remember it**, and run MySQL as a Windows service that starts automatically. |
| Add MySQL to PATH | 🤖 | Adds `C:\Program Files\MySQL\MySQL Server 8.4\bin` to your user PATH |

Close and reopen the terminal (or VS Code) after installing so the new PATH works.

macOS (Homebrew): `brew install php@8.4 composer mysql@8.4 gh && brew services start mysql@8.4`, then run
`mysql_secure_installation` yourself to set the root password.

---

## 4. Get the project and configure it

| Step | Who | What happens |
| --- | --- | --- |
| Set your Git identity | 🧑 (or 🤖 with your details) | `git config --global user.name "Your Name"` and `git config --global user.email "your-github-email"` |
| Clone the repo | 🤖 | `git clone https://github.com/imoooooooon/KrishiBondhu.git` then `cd KrishiBondhu` and `git switch staging` |
| Put the course PDFs in place | 🧑 | Create a `course-docs` folder inside the project and put the PDFs there. Git ignores this folder, so they are never uploaded. |
| Install dependencies | 🤖 | `composer install` |
| Create `.env` and your app key | 🤖 | `composer setup` (copies `.env.example` to `.env` and generates **your own** key) |
| Create your databases | 🤖 + 🧑 | Claude generates a random password for the `krishibondhu` user, writes the SQL below into a temporary file, and puts the same password into your `.env` as `DB_PASSWORD`. **You** run `mysql -u root -p < <that file>` and type your root password. Claude never needs your root password. Claude deletes the temp file afterwards. |
| Create the tables | 🤖 | `php artisan migrate` (creates the users, roles and other tables in your `krishibondhu` database; tests build their own tables in `krishibondhu_test`). |
| Verify everything | 🤖 | `composer check` (validates Composer files, formatting, app boot, MySQL connection, and tests). All steps must pass. |
| Try the app | 🤖 / 🧑 | `composer dev`, then open <http://127.0.0.1:8000/design-system>. You should see the Krishi Bondhu design system. Other pages show a "planned page" until their feature is built; `/up` is the health check. |

The database SQL (your own databases on your own computer):

```sql
CREATE DATABASE krishibondhu      CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE DATABASE krishibondhu_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE USER 'krishibondhu'@'localhost' IDENTIFIED BY 'your-local-password';
GRANT ALL PRIVILEGES ON krishibondhu.*      TO 'krishibondhu'@'localhost';
GRANT ALL PRIVILEGES ON krishibondhu_test.* TO 'krishibondhu'@'localhost';
```

- `krishibondhu` = your development database. `krishibondhu_test` = disposable, used only by tests.
- Never commit `.env`. Never share your `.env` or passwords in the group chat.

---

## 5. Let Claude Code do it: copy-paste prompt

Open Claude Code in an **empty folder** where you want the project (e.g. `D:\Projects`), then paste this, filling in
your details:

> I am **<Full Name>** (GitHub: **<username>**, GitHub email: **<email>**, branch prefix: **<prefix>/**), a member
> of the Krishi Bondhu CSE470 team. Set up my computer for the project at
> https://github.com/imoooooooon/KrishiBondhu by following its `docs/first-time-setup.md` exactly.
> First inspect what is already installed (OS, Git, gh, PHP and extensions, Composer, MySQL) and reuse anything compatible.
> Do the 🤖 steps yourself. For every 🧑 step, stop and tell me exactly what to do, then wait for me.
> Never ask for my MySQL root password and never commit `.env`. Do not create any branches, features, or pages, and
> do not write new migrations. At the end, run `composer check`, show me the result, and summarise what was done and what
> (if anything) is still left.

When it finishes, `composer check` must pass.

---

## 6. Checklist: you are done when

- [ ] You accepted the GitHub invite and `gh auth status` shows your account.
- [ ] `git config user.email` shows your GitHub email.
- [ ] `php -v` shows 8.4.x, `composer -V` works, and MySQL 8.4 runs as a service.
- [ ] The project is cloned, you are on `staging`, and `course-docs/` holds the PDFs.
- [ ] `.env` exists with your own `APP_KEY` and `DB_PASSWORD`.
- [ ] `composer check` passes.
- [ ] You read [docs/workflow.md](workflow.md) and [AGENTS.md](../AGENTS.md).

---

## 7. What next

Do **not** create a branch yet. When you get your first task (an issue, with an SRS feature ID like `F5`), start
Claude Code inside the project folder and say:

> Read `AGENTS.md` and `docs/workflow.md` and follow them. I am **<name>** (`<prefix>/`).
> My task is **<feature ID / issue # and short description>**. Start with section 1.

## Troubleshooting

| Problem | Fix |
| --- | --- |
| `php` / `composer` / `mysql` not found | Close and reopen the terminal or VS Code (PATH changes need a restart). |
| `could not find driver` | `pdo_mysql` is not enabled in `php.ini`. Rerun the php.ini step and check `php -m`. |
| Crop photo says it "did not finish uploading" (F1) | PHP's upload limit is lower than the app's 5 MB per photo. In `php.ini` (`php --ini` shows where) set `upload_max_filesize = 8M` and `post_max_size = 40M`, then restart `composer dev`. F1 also needs the `gd` extension (`extension=gd`, check with `php -m`). |
| `Access denied for user 'krishibondhu'` | `DB_PASSWORD` in `.env` does not match the SQL password. Fix `.env`, then `php artisan config:clear`. |
| `Unknown database 'krishibondhu_test'` | The database SQL was not run. Run it again. |
| `composer check` fails on formatting | `composer format`. (Only matters for your own changes.) |
| Port 3306 already in use | Another MySQL/MariaDB (e.g. XAMPP) is running. Stop it, or ask the team before changing ports. |
| Commits not showing on your GitHub profile | Your `git config user.email` is not linked to your GitHub account. Add it in GitHub → Settings → Emails. |

## Optional: local demo data

After `php artisan migrate`, you can fill your **own local** database with demo records (needs internet once, for the photos):

```bash
php artisan db:seed --class=CropListingDemoSeeder   # F1: demo farmers + 11 crop listings
php artisan kb:demo-cattle seed                      # F6: 12 demo cattle for the same demo farmers
php artisan kb:demo-cattle credentials               # prints the demo logins and links
php artisan kb:demo-cattle cleanup                   # dry run; add --apply to remove the demo cattle
```

Demo logins use the password `password` and work only on your computer. Both refuse to run in production. Photo credits: [demo-assets.md](demo-assets.md).
