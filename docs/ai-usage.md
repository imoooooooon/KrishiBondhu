# AI usage record

Course policy requires disclosing AI assistance. Add an entry whenever an AI tool meaningfully contributes.

| Date | Who | Tool | What it did | What the student reviewed/changed |
| --- | --- | --- | --- | --- |
| 2026-11-12 | Md. Mahfuz Ahmed Emon (imoooooooon) | Claude Code (Claude Opus 5.5) | Environment and repository setup only: installed PHP 8.4, Composer, and MySQL 8.4 on Emon's Windows PC; generated the minimal Laravel 13 scaffold and removed sample/welcome content; configured MySQL dev/test databases, `.env.example`, `phpunit.xml`, Pint, `.gitignore`; wrote two infrastructure tests (app boots, test DB connectivity); wrote CI workflows, issue/PR templates, and these docs; configured GitHub branch protection, milestones, and labels. | Emon requested and supervised the setup. Team to review the docs and workflow. |
| 2026-11-12 | Md. Mahfuz Ahmed Emon (imoooooooon) | Claude Code (Claude Opus 5.5) | Wrote the daily workflow runbook (`docs/workflow.md`) and third-party register; added course-outline rules to `AGENTS.md`; noted SRS items to reconcile. | Emon to review; teammate review via PR. |
| 2026-11-12 | Md. Mahfuz Ahmed Emon (imoooooooon) | Claude Code (Claude Opus 5.5) | Transcribed the SRS and faculty course PDFs to Markdown (`docs/srs.md`, `docs/course/`); removed the required-approval rule from branch protection at the team's request and updated the workflow docs. | Emon requested; team to keep the SRS updated. |
| 2026-11-12 | Md. Mahfuz Ahmed Emon (imoooooooon) | Claude Code (Claude Opus 5.5) | Wrote `docs/first-time-setup.md` (teammate onboarding: manual vs. Claude-assisted steps, copy-paste prompt). | Emon requested; teammates to follow and report gaps. |
| 2026-11-12 | Md. Mahfuz Ahmed Emon (imoooooooon) | Claude Code (Claude Opus 5.5) | Information architecture and design system at Emon's request: `docs/IA.md` (roles, navigation, 193 named routes in `routes/modules/*.php` mapped to F1–F26, served by a `PlannedController` placeholder), `docs/design-system.md` adapted from Emon's Al Wasil system (tokens, CSS, Blade UI components, layout, `/design-system` page, logo), `docs/architecture.md`, route-map tests. No feature logic, schema or real pages for any F-ID were built. | Emon directed the design (Al Wasil reference, agriculture theme); each feature owner implements their own controllers, models and pages. |
| 2026-11-12 | Md. Mahfuz Ahmed Emon (imoooooooon) | Claude Code (Claude Opus 5.5) | Wired the Li Ador Noirrit Bangla font (committed after Emon confirmed permission from Lipighor; Noto fallback; footer credit) and a basic landing page at `/`. | Emon requested; supplied the font locally. |

No product features, business logic, UI, routes, or database schema were generated.
