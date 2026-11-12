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

## Open items

- The SRS names **Bootstrap** for the UI. UI dependencies are still a deferred team decision (below). Update
  `docs/srs.md` once decided.

## Sprint deadlines (Asia/Dhaka)

| Milestone | Date |
| --- | --- |
| Sprint 1 | 14 November 2026 |
| Sprint 2 | 5 December 2026 |
| Sprint 3 | 13 December 2026 |
| Sprint 4 | 20 December 2026 |

Submission cutoff times and sprint start dates have not been confirmed. Check the faculty announcement.
Push current work (even incomplete) before every deadline.

## Next phases (not started)

1. Information architecture and route planning
2. Design system
3. Frontend and backend implementation

## Deferred decisions

- UI dependencies (Bootstrap, Tailwind, Alpine.js, Livewire, or none) and whether an npm/Vite asset pipeline is needed.
- Information architecture, routes, navigation.
- Authentication approach and user/role schema. (`config/auth.php` still names `App\Models\User`, which was removed with the default users migration. Create it when authentication is designed.)
- Display timezone/locale handling (app currently stores UTC; Bangla/English UI).
- Feature assignment per member (≥ 5 each, ≥ 20 total).
- Any package implementing an assessed feature (requires faculty approval).

## Future environments (planned, not created)

We intend to add **staging** and **production** deployments later. Each will have its own database, `APP_KEY`,
secrets, session storage, and uploaded-file storage, separate from each other and from every local machine.
No hosting has been purchased and no public URL exists yet.
