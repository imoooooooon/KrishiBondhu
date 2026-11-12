# AGENTS.md: rules for humans and AI coding assistants

Krishi Bondhu (কৃষি বন্ধু) is a **CSE470 group project at BRAC University**, built by four students.
It is assessed coursework. Read this before generating or changing anything.

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
- **AI assistance must be disclosed** (record it in `docs/ai-usage.md`), and each student must understand and be able
  to defend their own work.

## Scope boundaries for AI assistants

- Do **not** generate the assessed project: features, business logic, product pages, or schema, unless the
  responsible student explicitly asks for help with their own specific task and will review and understand it.
- Do not pick UI dependencies (Bootstrap, Tailwind, Alpine.js, Livewire, etc.), a design system, or the route and
  information architecture. These are team decisions still pending (see `docs/project-context.md`).
- Do not add an asset build pipeline (npm/Vite) until the team decides it is needed.
- Do not rewrite the SRS. The current SRS is the working scope and may change.
- Keep Laravel's conventional structure (`app/Http/Controllers`, `app/Models`, `resources/views`, `routes/web.php`,
  `database/migrations`). No speculative abstraction layers or empty placeholder classes.
- Never commit `.env`, secrets, database dumps, uploads, `vendor/`, logs, or raw course documents (`/course-docs/`).
- Never impersonate teammates, fabricate their commits or reviews, or self-approve PRs.

## Collaboration rules

- Branches: `main` (stable), `staging` (integration), and short-lived working branches
  `emon/<task>`, `jarif/<task>`, `mehrab/<task>`, `sajedul/<task>`. Full workflow: `CONTRIBUTING.md`.
- All changes reach `staging` through a reviewed PR (1 approval from another teammate, CI green, conversations
  resolved). `main` only receives PRs from `staging`. Merge commits only.
- Never force-push or hard-reset shared branches. Integrate `origin/staging` with `git merge`.

## Commands

```bash
composer install     # install locked dependencies
composer setup       # create .env and generate this computer's app key (first time only)
composer dev         # start the dev server at http://127.0.0.1:8000 (health check: /up)
composer check       # validate composer files, Pint formatting, boot, MySQL connectivity, tests
composer test        # tests only (always use the krishibondhu_test database)
composer format      # fix PHP formatting with Pint
```

Tests run against `krishibondhu_test` (forced in `phpunit.xml`). Do not write fake or trivial tests. Write tests
for real behaviour as it is built.
