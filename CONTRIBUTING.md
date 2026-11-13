# Contributing: team Git and GitHub workflow

> Short step-by-step version for daily use (and for AI agents): [docs/workflow.md](docs/workflow.md).

## Branches

| Branch | Purpose | Who writes to it |
| --- | --- | --- |
| `main` | Stable release | Only via a PR **from `staging`** |
| `staging` | Shared integration and testing | Only via PRs from working branches |
| `<name>/<task>` | One person's current piece of work | That person |

Working branch prefixes:

| Member | GitHub | Prefix |
| --- | --- | --- |
| Md. Mahfuz Ahmed Emon | imoooooooon | `emon/` |
| Md. Abdullah Al Jarif | jarifff | `jarif/` |
| Mehrab Hossain Nizami | mugdhoo44 | `mehrab/` |
| Mir Mohammad Sajedul Islam | sajedulislam5840 | `sajedul/` |

Branch names use lowercase words joined by hyphens, for example `emon/crop-listings` or `jarif/fix-login-redirect`.
These are naming examples, not assignments.

- A working branch is one coherent contribution: a feature, a manageable part of one, a bug fix, or documentation.
- Normally have **one active working branch at a time**. Create it when you start real work and delete it after it is merged.
- There are no permanent personal branches.

## The workflow

1. Update your local `staging` from `origin/staging`.
2. Create a working branch from `staging`.
3. Make meaningful commits and push the working branch, at least at every sprint deadline.
4. Before integration, merge the latest `origin/staging` into your working branch.
5. Resolve any conflicts, run `composer check`, and open (or update) a PR into `staging`.
6. Wait for CI to pass and resolve any comment conversations. Teammates are welcome to review, but **approval is
   not required**: we are equal contributors, and the author merges their own PR.
7. Merge it with **"Create a merge commit"**, then delete the working branch.
8. Test the combined `staging` version.
9. When the whole staging batch is ready, open a PR from `staging` into `main`.
10. When CI passes, merge the release (tell the team first).

GitHub enforces: PRs required on `main` and `staging` (no direct pushes), passing CI, resolved conversations,
no force pushes, no branch deletion, and the same rules for admins. No approval count is required.
Only merge commits are allowed (squash and rebase merging are off), so everyone's original commits stay in history.

## Things to understand

- **Pushing your working branch does not change `staging`.** Only a merged PR changes `staging`.
- **`git pull` on your working branch** updates it from *its own* upstream (`origin/<name>/<task>`), not from `staging`.
  You get teammates' merged work only when you explicitly merge `origin/staging` (step 4).
- Git merges independent changes automatically. When two people edit the same lines, Git stops and asks you to
  resolve the **conflict**.
- A clean text merge does **not** prove the combined app works. Run `composer check` and try the app after merging.
- A `staging` → `main` PR releases **everything** currently in `staging`, not just one person's work.
- Unfinished work stays **pushed** on its working branch. Open a **draft PR** or comment on the issue to show progress.
  Never wait until it is finished to push.
- Never use `git push --force`, `git reset --hard` on shared work, or rewrite pushed history as a way to "sync".

---

## Commands

### Start a new task

```bash
git switch staging
git pull origin staging                 # update local staging
git switch -c emon/short-task-name      # use YOUR prefix
git push -u origin emon/short-task-name # publish the branch and set its upstream
```

### Continue an existing task (new day, or another computer)

```bash
git fetch origin
git switch emon/short-task-name
git pull                                # gets your own branch's latest pushes
```

### Stage, commit, and push

```bash
git status                              # see what changed
git add path/to/file another/file       # stage specific files (check nothing secret like .env is listed)
git commit -m "Add crop listing form validation"
git push
```

Write commit messages that say what the change does. Commit small, meaningful steps, not one giant commit.

### Bring the latest staging into your working branch

```bash
git fetch origin
git switch emon/short-task-name
git merge origin/staging
composer install                        # in case composer.lock changed
php artisan migrate                     # in case database/migrations changed
composer check
git push
```

### Resolve a merge conflict without losing anyone's work

When `git merge origin/staging` reports a conflict:

```bash
git status                              # lists files marked "both modified"
```

Open each file and find the markers:

```text
<<<<<<< HEAD
your version
=======
the version from staging (a teammate's work)
>>>>>>> origin/staging
```

Edit the section so it keeps **both** intentions, then delete the marker lines. If you are unsure what the teammate
meant, ask them before choosing. Then:

```bash
git add path/to/resolved-file
git commit                              # completes the merge (default message is fine)
composer check
git push
```

To back out of a merge you have not committed yet: `git merge --abort` (this only cancels the merge in progress).
Do not use `git checkout --theirs/--ours` on a whole file unless you have confirmed the other side's changes are not needed.

### Open a PR

On GitHub: **Compare & pull request** → base `staging` ← compare `emon/short-task-name`, then fill in the template.
Or with GitHub CLI:

```bash
gh pr create --base staging --fill            # ready for review
gh pr create --base staging --fill --draft    # still in progress
gh pr ready                                   # mark a draft as ready
```

Reviews are optional. For larger or shared changes, ask a teammate to look (`gh pr edit --add-reviewer <username>`).
Comments and reviews on PRs are also useful evidence of collaboration for the course.

### Review a teammate's PR (optional, encouraged)

1. Read the **Files changed** tab and leave comments on specific lines.
2. Optionally test it locally:

   ```bash
   git fetch origin
   git switch jarif/their-task
   composer install && composer check
   git switch staging                    # go back afterwards
   ```

3. Leave comments, or submit **Approve** / **Request changes**. The author resolves each conversation after addressing it
   (unresolved conversations block the merge).

### Merge and delete the working branch

When CI is green and conversations are resolved, click **Merge pull request** (merge commit) and then **Delete branch** on GitHub.
Then locally:

```bash
git switch staging
git pull origin staging
git branch -d emon/short-task-name      # -d refuses to delete unmerged work, which is what we want
git fetch --prune                       # removes your stale remote-tracking refs
```

Automatic branch deletion is turned off. Delete completed working branches yourself. Never delete `staging` or `main`
(GitHub blocks that anyway).

### Promote staging to main (release)

After the combined `staging` has been tested:

```bash
gh pr create --base main --head staging --title "Release: Sprint N" --body "Summary of what this batch contains"
```

When CI passes, merge with a merge commit (agree with the team first, since it releases everyone's work). **Do not delete `staging`** after this merge.
The "Release source branch" check fails any PR into `main` whose head is not this repository's `staging`.

---

## Issues and the project board

- Track work as GitHub issues (templates: **Task** and **Bug**). Link the SRS feature ID where it applies.
- Put the issue in a sprint milestone and assign its owner.
- Labels: `feature`, `bug`, `documentation`, `infrastructure`, `blocked`, `question`.
- Board columns: Todo → In progress → In review → Done.
- Use `Closes #12` in the PR description to close the issue when the PR merges into the default branch (`main`).
  PRs into `staging` do not auto-close issues, so close them manually after merging, or let the release PR close them.
