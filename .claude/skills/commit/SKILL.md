---
version: 0.1.0
user_invocable: true
trigger: commit
description: Commit staged/unstaged changes with conventional commit prefixes, branch safety, and no co-author line.
---

# Commit Skill

You are a git commit assistant. Follow these rules exactly — no exceptions.

## Process

### Step 1: Check the current branch

Run `git branch --show-current` to get the current branch name.

**If the branch is `main` or `master`, STOP immediately.** Do NOT commit. Instead:
1. Tell the user they are on the main branch and cannot commit directly.
2. Ask them what branch name they'd like to create (suggest one based on the changes if possible).
3. Once they provide a name, create and switch to that branch with `git checkout -b <branch-name>`.
4. Then continue with the commit process.

### Step 2: Review changes

Run these commands in parallel to understand what will be committed:
- `git status` (never use `-uall`)
- `git diff` (unstaged changes)
- `git diff --cached` (staged changes)
- `git log --oneline -5` (recent commits for style reference)

If there are no changes to commit, inform the user and stop.

### Step 3: Stage files

- If there are unstaged changes, stage them by adding specific files by name (not `git add .` or `git add -A`).
- Do NOT stage files that look like secrets (`.env`, credentials, tokens, keys).
- If unsure which files to stage, ask the user.

### Step 4: Determine commit type and write the message

Analyze the staged changes and classify the commit using one of these prefixes:

| Prefix       | When to use                                      |
|-------------|--------------------------------------------------|
| `feat:`     | New feature or functionality                      |
| `fix:`      | Bug fix                                           |
| `refactor:` | Code restructuring without behavior change        |
| `style:`    | Formatting, whitespace, missing semicolons, etc.  |
| `docs:`     | Documentation only                                |
| `test:`     | Adding or updating tests                          |
| `chore:`    | Build scripts, configs, dependencies, tooling     |
| `perf:`     | Performance improvement                           |
| `ci:`       | CI/CD pipeline changes                            |

Write a commit message in this format:
```
<prefix> <short summary in lowercase, imperative mood>
```

Examples:
- `feat: add monthly report email sending`
- `fix: handle cross-midnight shift calculation`
- `refactor: extract time validation into form request`
- `chore: add commit skill for claude code`

Keep the subject line under 72 characters. If more context is needed, add a blank line and a body paragraph.

### Step 5: Commit

Create the commit using a HEREDOC for the message. **Do NOT add a `Co-Authored-By` line.**

```bash
git commit -m "$(cat <<'EOF'
<prefix> <message>
EOF
)"
```

### Step 6: Verify

Run `git status` after the commit to confirm it succeeded. Report the result to the user.

If the commit fails due to a pre-commit hook, fix the issue and create a NEW commit (never amend).

## Important rules

- **NEVER** commit to `main` or `master`. Always require a feature branch.
- **NEVER** add `Co-Authored-By` lines.
- **NEVER** use `--no-verify` or skip hooks.
- **NEVER** use `git add .` or `git add -A`.
- **NEVER** amend commits unless the user explicitly asks.
- **ALWAYS** use conventional commit prefixes.
- **ALWAYS** check the branch before doing anything else.
