# Project context

## Team

| Member | GitHub | Branch prefix |
| --- | --- | --- |
| Md. Mahfuz Ahmed Emon | imoooooooon | `emon/` |
| Md. Abdullah Al Jarif | jarifff | `jarif/` |
| Mehrab Hossain Nizami | mugdhoo44 | `mehrab/` |
| Mir Mohammad Sajedul Islam | sajedulislam5840 | `sajedul/` |

Repository: <https://github.com/imoooooooon/KrishiBondhu>

## Confirmed decisions

- One Laravel 13 application (PHP 8.4), conventional MVC, Blade templates for pages, plain JavaScript for browser
  behaviour, MySQL 8.4 LTS (InnoDB, utf8mb4).
- Native local setup (no Docker/WSL required). Each teammate has their own local dev database, test database,
  and application key.
- Foundation has no business tables. Sessions/cache use files, and queues run synchronously, so no infrastructure
  tables are needed yet.
- Git workflow: `main` (stable) + `staging` (integration) + short-lived `<name>/<task>` branches. PRs required (no approval needed),
  merge commits only, protected branches. See `CONTRIBUTING.md`.
- CI: GitHub Actions with a throwaway MySQL 8.4 container. No secrets.
- The SRS is the working scope (26 planned features, `F1`–`F26`) and is maintained as a living Markdown document:
  [docs/srs.md](srs.md). Faculty documents are transcribed in [docs/course/](course/) for reference.
  The original PDFs stay in each member's local, git-ignored `course-docs/` folder (never committed). Student IDs
  and the faculty email are intentionally kept out of the public Markdown copies.
- All documentation is living: update it in the same PR whenever the workflow or scope changes.
- **Information architecture (2026-11-12):** [docs/IA.md](IA.md). 193 named routes for F1–F26, one route file per
  area in `routes/modules/`, unbuilt routes served by `PlannedController`.
- **Design system (2026-11-12):** [docs/design-system.md](design-system.md), adapted from the Al Wasil system for
  agriculture. **No UI framework** (replaces Bootstrap from the SRS draft) and **no npm/Vite pipeline**: plain CSS
  tokens and Blade components, self-hosted OFL fonts (Inter Tight, Noto Sans Bengali), Lucide icons (ISC).
- **Structure:** Laravel's MVC folders are the frontend/backend/database separation ([docs/architecture.md](architecture.md)).

## Sprint deadlines (Asia/Dhaka)

| Milestone | Date |
| --- | --- |
| Sprint 1 | 14 November 2026 |
| Sprint 2 | 5 December 2026 |
| Sprint 3 | 13 December 2026 |
| Sprint 4 | 20 December 2026 |

Submission cutoff times and sprint start dates have not been confirmed. Check the faculty announcement.
Push current work (even incomplete) before every deadline.

## Phases

1. ~~Information architecture and route planning~~ (done: IA.md)
2. ~~Design system~~ (done: design-system.md, `/design-system`)
3. Frontend and backend implementation (next: each member builds their features on the planned routes)

## Deferred decisions

- A JS interaction library or build pipeline, only if a feature genuinely needs one (team decision, record here).
- Home page and role dashboard content (IA.md §7).
- Authentication approach and user/role schema. (`config/auth.php` still names `App\Models\User`, which was removed with the default users migration. Create it when authentication is designed.)
- Display timezone/locale handling (app currently stores UTC; Bangla/English UI).
- Feature assignment per member (≥ 5 each, ≥ 20 total).
- Any package implementing an assessed feature (requires faculty approval).

## Future environments (planned, not created)

We intend to add **staging** and **production** deployments later. Each will have its own database, `APP_KEY`,
secrets, session storage, and uploaded-file storage, separate from each other and from every local machine.
No hosting has been purchased and no public URL exists yet.
