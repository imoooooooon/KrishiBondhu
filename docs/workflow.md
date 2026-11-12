# Daily workflow runbook

Follow this every time you work on Krishi Bondhu after first-time setup.
For first-time setup, use [docs/first-time-setup.md](first-time-setup.md). For the reasons behind each rule, see
[CONTRIBUTING.md](../CONTRIBUTING.md).

**Using an AI agent?** Start the session with:

> Read `AGENTS.md` and `docs/workflow.md` and follow them. I am **<name>** (`<prefix>/`).
> My task is **<SRS feature ID and/or issue #, and a short description>**. Start with section 1.

The agent must follow this file step by step and **stop to ask the student** at every step marked ✋.

---

## Who is who

| Member | GitHub | Branch prefix |
| --- | --- | --- |
| Md. Mahfuz Ahmed Emon | imoooooooon | `emon/` |
| Md. Abdullah Al Jarif | jarifff | `jarif/` |
| Mehrab Hossain Nizami | mugdhoo44 | `mehrab/` |
| Mir Mohammad Sajedul Islam | sajedulislam5840 | `sajedul/` |

Feature IDs come from the SRS, [docs/srs.md](srs.md) (`F1`–`F26`, requirements `R1`–`R26`). Course rules are in
[docs/course/project-outline.md](course/project-outline.md). Original PDFs stay in the git-ignored `course-docs/`
folder on each computer and are never committed.

All docs are living documents. When the way we work changes, update the relevant `.md` file in the same PR.

---

## 1. Session start checks (every time)

```bash
git status                     # must be clean, or only contain your own unfinished work
git branch --show-current      # which branch am I on?
git config user.email          # must be YOUR GitHub email
git fetch origin --prune
```

- If `git status` shows changes you do not recognise: ✋ stop and ask. Do not discard them.
- If `composer.lock` changed since your last session, run `composer install`.
- Quick health check: `composer check` (all steps should pass before you start).

Then go to **section 2** (new task) or **section 3** (continuing a task).

## 2. Starting a new task

1. ✋ Confirm the task. It should be an issue on GitHub. If none exists, create one with the **Task** template
   (task, SRS feature ID, owner, acceptance criteria, milestone). Do not invent features that are not in the SRS.
   Check with the team first.
2. Make sure you have no other unfinished working branch. Normally **one active branch per person**.
3. Create the branch from the latest staging:

   ```bash
   git switch staging
   git pull origin staging
   git switch -c <prefix>/<short-task-name>     # e.g. jarif/crop-listing-form
   git push -u origin <prefix>/<short-task-name>
   ```

   Branch names: lowercase words with hyphens, describing the work, not the person.
4. On the project board, move the issue to **In progress** and assign yourself.
5. Optional but recommended: open a **draft PR** early so teammates can see progress:

   ```bash
   gh pr create --base staging --draft --fill
   ```

## 3. Continuing an existing task

```bash
git switch <prefix>/<short-task-name>
git pull                                # your own branch's latest commits (e.g. from another computer)
git merge origin/staging                # bring in teammates' merged work (see section 5)
composer install
```

## 4. While working

- Follow Laravel MVC as mapped in [docs/architecture.md](architecture.md): Models in `app/Models`, Controllers in
  `app/Http/Controllers`, Blade views in `resources/views`, schema changes as migrations in `database/migrations`.
  Keep business logic out of views. Do not put the whole feature in one controller.
- **Routes:** your feature's routes already exist in `routes/modules/<area>.php` (see [docs/IA.md](IA.md) §5).
  Replace `PlannedController` with your controller and **keep the URI and route name**. Link with `route('name')`.
  Adding or renaming a route? Update IA.md §5 in the same branch.
- **Pages:** use `<x-layouts.app>` and the `<x-ui.*>` components from [docs/design-system.md](design-system.md).
  No hex colours, inline styles or CSS frameworks. Check your page on `/design-system`-style quality: light and dark
  theme, English and Bangla, phone width.
- Only change files that belong to your task. If you need to change shared files (layout, header, `public/css/app.css`,
  shared components, shared models, `composer.json`), ✋ tell the team first, because these cause conflicts.
- New package? ✋ Ask the team first. Packages that implement an assessed feature need **faculty approval**.
  Record every significant package in [docs/third-party.md](third-party.md).
- When your work changes a feature's status or scope, update its row in [docs/srs.md](srs.md) in the same branch
  (sprint completed, status), and add a line to the SRS revision log for scope changes.
- Never put secrets or API keys in code. Use `.env` (and add a placeholder to `.env.example`).
- Write real tests for real behaviour (normal, invalid, and boundary cases). Tests use `krishibondhu_test` only.
- Commit small, meaningful steps:

  ```bash
  git status
  git add <specific files>              # check that .env, logs, and dumps are NOT listed
  git commit -m "Validate harvest date on crop listing form"
  git push
  ```

  Messages say *what changed*. Never "update", "final", "done", or "fix stuff".
- **Push at least at the end of every work session**, and always before each sprint deadline (Friday),
  even if the work is incomplete.
- If an AI tool helped, add a row to [docs/ai-usage.md](ai-usage.md) in the same branch. You must be able to explain
  every line in the viva.

## 5. Bringing in the latest staging

Do this before opening or updating a PR, and whenever teammates merge something you depend on:

```bash
git fetch origin
git merge origin/staging
composer install
composer check
git push
```

**If there is a conflict:**

1. `git status` lists the conflicted files ("both modified").
2. In each file, keep **both** people's intentions between `<<<<<<<`, `=======`, `>>>>>>>`, then delete the markers.
3. ✋ If you are not sure what the other person's code is for, stop and ask them. Never delete a teammate's work
   to make a conflict go away.
4. `composer.lock` conflict: take staging's version, then re-add your own package:
   `git checkout --theirs composer.lock && composer require <vendor/package>`, using the package you added.
5. Finish: `git add <files>`, `git commit`, `composer check`, `git push`.
6. Want to start over? `git merge --abort` cancels the merge in progress (only before you commit it).

A clean merge is not proof the app works. Always run `composer check` and try the feature again.

## 6. Finishing a task (PR and merge)

1. Run section 5 one last time so the branch includes the latest staging.
2. `composer check` passes locally, and you manually tested the acceptance criteria.
3. Open or update the PR into **staging**:

   ```bash
   gh pr create --base staging --fill     # or, if a draft exists:  gh pr ready
   ```

   Fill in the template: related issue, SRS feature ID, owner, changes, checks performed, known limitations.
4. Move the issue to **In review** on the board.
5. Wait for **Laravel checks** to pass: `gh pr checks --watch`. If it fails, fix, `git push`, and wait again.
6. **Approval is not required.** For larger or shared changes, give teammates a chance to comment first
   (`gh pr edit --add-reviewer <github-username>`). Address comments with new commits on the same branch, then
   resolve each conversation (unresolved conversations block the merge).
7. If GitHub says the branch is out of date with staging, run section 5 again and push.
8. Merge it yourself with a merge commit: ✋ the agent confirms with the student first.

   ```bash
   gh pr merge --merge
   ```

   Then continue with section 8.

## 7. Reviewing a teammate's PR (optional, encouraged)

1. Read **Files changed**. Check MVC placement, validation, no secrets, and tests for the acceptance criteria.
2. Optionally run it locally:

   ```bash
   git fetch origin
   git switch <their-branch>
   composer install && composer check
   git switch -                       # back to your own branch
   ```

3. Leave specific comments, or **Approve** / **Request changes**. Never approve without reading.
   Reviewing is not required to merge, but it helps catch bugs and shows collaboration in the Git history.

## 8. After your PR is merged

1. The merge must use **Create a merge commit** (the only option enabled).
2. Delete the remote branch (**Delete branch** button on the PR page, or
   `git push origin --delete <prefix>/<short-task-name>`), then locally:

   ```bash
   git switch staging
   git pull origin staging
   git branch -d <prefix>/<short-task-name>
   git fetch --prune
   ```

3. Close the issue if the PR did not close it, and move it to **Done** on the board.
4. Quickly check that the combined staging still works: `composer check`.
5. Start your next task at **section 2**.

## 9. End of sprint (Friday) and release to main

Every member, before the deadline:

- Push all current work, including incomplete work, to your working branch. Mark unfinished PRs as **draft** and
  write what is left in the PR or issue.
- Write your attainment for the sprint report: what was completed, partially completed, not completed, and why.

Release (one person, agreed by the team, after staging has been tested together):

```bash
gh pr create --base main --head staging --title "Release: Sprint N" --body "Features and fixes in this batch"
```

- Tell the team, wait for CI, then merge with a merge commit (`gh pr merge --merge`). **Never delete `staging`.**
- This PR releases **everything** currently in staging.

Sprint start (Saturday): agree on specific, measurable goals. Create or assign the issues and put them in the
sprint milestone.

## 10. Fixing common mistakes

| Problem | Fix |
| --- | --- |
| Committed on `staging` or `main` by mistake (not pushed) | `git switch -c <prefix>/<task>` (your commits move with you), then `git branch -f staging origin/staging` |
| `git push` to `staging`/`main` rejected | Expected: they are protected. Push your working branch and open a PR. |
| `.env` or a secret was committed | ✋ Stop, tell the team immediately, and change that password or key. Do not just delete the file in a new commit. |
| CI failed on the PR | Open the failed check on GitHub, fix it locally, run `composer check`, and push again. |
| Formatting check failed | `composer format`, then commit the changes. |
| "Release source branch" check failed | You opened a PR into `main`. Change the base to `staging`. |
| Need to undo a pushed commit | `git revert <commit>` (creates a new commit). Never force-push. |

## Never

- Force-push (`git push --force`), `git reset --hard` on shared work, or rewrite pushed history.
- Push directly to `main` or `staging`, merge with failing CI, or bypass protections.
- Commit `.env`, keys, passwords, database dumps, uploads, `vendor/`, logs, or anything in `course-docs/`.
- Commit or review as another teammate, or fabricate commits, tests, reviews, or sprint evidence.
- Let an AI generate whole features or modules that you cannot explain.
