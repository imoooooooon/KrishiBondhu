# AGENTS.md: rules for humans and AI coding assistants

Krishi Bondhu (কৃষি বন্ধু) is a **CSE470 group project at BRAC University**, built by four students.
It is assessed coursework. Read this before generating or changing anything.

**Before any task, follow [docs/workflow.md](docs/workflow.md) step by step** (session checks, branch, commits,
sync with staging, PR, review, cleanup). Stop and ask the student wherever it says ✋.

## Course constraints (must follow)

- One shared group project. Backend framework with clear **MVC** architecture is mandatory.
- Stack: **PHP + Laravel**, **Blade** views, **MySQL 8.4** (InnoDB, utf8mb4), plain JavaScript, Composer.
- Not allowed as the main stack: MERN combinations, or Python frameworks as the backend.
  Do not add Next.js, React, a separate frontend app, microservices, or a separate API backend.
- Supporting packages are fine for development, but **any package that implements a major assessed feature
  needs faculty approval first**. Ask the team before adding a dependency.
- At least **20 substantive features overall and at least 5 per member**. Authentication and plain CRUD do not
  count as independent features.
- Every student must make **meaningful commits throughout** development and **push current work at every sprint
  deadline**, including incomplete work.
- Faculty must have the required repository access.
- Commit messages must say what changed. No "update", "final", "done".
- One-week sprints: goals agreed at sprint start (Saturday), attainment reported at sprint end (Friday),
  including what is unfinished and why. Do not hide incomplete work, bugs, or scope changes.
- Record every significant third-party package, API, or service in `docs/third-party.md`.
- **AI assistance must be disclosed** (record it in `docs/ai-usage.md`). Never use AI to generate entire modules
  or feature sets, or to fabricate requirements, test results, Git history, or sprint evidence. Each student must be
  able to explain, modify, and debug their code in the viva, and understand the overall system.
- Feature IDs are `F1`–`F26` (requirements `R1`–`R26`) from the SRS, [docs/srs.md](docs/srs.md). Login,
  registration, and plain CRUD do not count as features. The full faculty rules are in
  [docs/course/project-outline.md](docs/course/project-outline.md). Original PDFs stay in the git-ignored
  `course-docs/` folder and must not be committed.

## Scope boundaries for AI assistants

- Do **not** generate the assessed project: features, business logic, product pages, or schema, unless the
  responsible student explicitly asks for help with their own specific task and will review and understand it.
- **Frontend:** follow [docs/design-system.md](docs/design-system.md). Build pages from `<x-layouts.app>` and the
  `<x-ui.*>` components; use semantic tokens from `public/css/tokens.css`. No hex colours or inline styles in views,
  no CSS/JS frameworks (Bootstrap, Tailwind, Alpine.js, Livewire, React …), and no npm/Vite build pipeline unless the
  team agrees and records it in `docs/project-context.md`. Check `/design-system` in light and dark, English and Bangla.
- **Routes:** follow [docs/IA.md](docs/IA.md). All planned routes already exist in `routes/modules/*.php`, pointing
  at `PlannedController`. To build one, swap in your controller and **keep the URI and route name**. Link with
  `route('name')`, never hard-coded paths. New routes are named, put in the right module file, and added to IA.md §5.
- **Structure:** MVC as mapped in [docs/architecture.md](docs/architecture.md). There are no separate frontend/backend folders.
  Views present, controllers coordinate, models and `app/Services` hold business rules.
- `docs/srs.md` is the team's living SRS and working scope. Update feature status rows as work lands. Change
  scope (features, requirements) only when the team has agreed, and log it in its revision log.
- All docs are living documents. Keep them in sync with how the team actually works, in the same PR as the change.
  Treat `docs/course/*` as faculty rules: do not change their meaning.
- Keep Laravel's conventional structure (`app/Http/Controllers`, `app/Models`, `resources/views`, `routes/web.php`,
  `database/migrations`). No speculative abstraction layers or empty placeholder classes.
- Never commit `.env`, secrets, database dumps, uploads, `vendor/`, logs, or raw course documents (`/course-docs/`).
- Never impersonate teammates or fabricate their commits, reviews, or sprint evidence.

## Collaboration rules

- Branches: `main` (stable), `staging` (integration), and short-lived working branches
  `emon/<task>`, `jarif/<task>`, `mehrab/<task>`, `sajedul/<task>`. Full workflow: `CONTRIBUTING.md`.
- All changes reach `staging` through a PR (CI green, conversations resolved). No approval is required: team members
  are equal contributors and merge their own PRs. Reviews are optional and encouraged. `main` only receives PRs
  from `staging`. Merge commits only. An agent must confirm with the student before merging.
- Never force-push or hard-reset shared branches. Integrate `origin/staging` with `git merge`.

## Commands

```bash
composer install     # install locked dependencies
composer setup       # create .env and generate this computer's app key (first time only)
composer dev         # start the dev server at http://127.0.0.1:8000 (design system: /design-system, health: /up)
php artisan route:list --except-vendor   # see every route and whether it is still PlannedController
composer check       # validate composer files, Pint formatting, boot, MySQL connectivity, tests
composer test        # tests only (always use the krishibondhu_test database)
composer format      # fix PHP formatting with Pint
```

Tests run against `krishibondhu_test` (forced in `phpunit.xml`). Do not write fake or trivial tests. Write tests
for real behaviour as it is built.
