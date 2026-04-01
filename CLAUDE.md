# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Hour registration web app for small teams (15-20 people). Users log daily work hours; admins view team summaries and send monthly reports via email. Currently in pre-implementation phase — see `mvp.md` for requirements and `.claude/claude-app-plan.md` for the full implementation plan.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.1+)
- **Frontend**: Vue 3 (Composition API) + Inertia.js
- **Styling**: Tailwind CSS + DaisyUI
- **Database**: MySQL (via DDEV)
- **Local dev environment**: DDEV
- **Auth**: Laravel Breeze with Inertia
- **Email**: Laravel Mail with Queue support
- **PWA**: Vite PWA Plugin
- **Date handling**: Day.js

## Common Commands

```bash
# DDEV environment
ddev start                     # Start containers (web + db)
ddev stop                      # Stop containers
ddev restart                   # Restart containers
ddev ssh                       # Shell into the web container
ddev describe                  # Show project URLs and info
ddev launch                    # Open site in browser

# Development
ddev npm run dev               # Vite dev server with HMR
ddev artisan queue:work        # Queue worker for emails

# Database
ddev artisan migrate           # Run migrations
ddev artisan migrate:fresh --seed  # Reset DB and seed
ddev artisan db:seed --class=AdminUserSeeder

# Testing
ddev artisan test              # Run all tests
ddev artisan test --filter=TimeEntry  # Run specific test

# Production build
ddev npm run build
ddev composer install --optimize-autoloader --no-dev
ddev artisan config:cache && ddev artisan route:cache && ddev artisan view:cache

# Code generation
ddev artisan make:controller NameController
ddev artisan make:model Name
ddev artisan make:migration create_name_table
ddev artisan make:request StoreNameRequest
ddev artisan make:mail NameMail
```

## Architecture

### Inertia.js data flow
Controllers return `Inertia::render('PageName', $props)` — props are automatically passed as JSON to Vue page components. No separate API layer needed. Forms use `useForm()` from `@inertiajs/vue3` for submission with automatic error handling.

### Authorization model
Two roles: `user` and `admin` (enum on users table). Custom `AdminMiddleware` protects admin routes. Users can only access their own time entries (enforced in controllers via `user_id` check).

### Time calculation
`TimeEntry::calculateTotalHours($start, $end, $breakMinutes)` handles the core business logic including cross-midnight shifts. Result stored as `decimal(5,2)` in `total_hours` column.

### Registration flow
Invitation-based only — admin sends invite email with token (7-day expiry), user completes registration via token link. No open registration.

### Route structure
- `/dashboard` — user dashboard with quick-add form
- `/time-entries` — monthly time entry listing with month navigation
- `/admin/overview` — admin view of all users' hours per month
- `/admin/invitations` — invite management
- `/invitation/{token}` — public invitation acceptance (no auth)

### Key conventions
- Vue pages in `resources/js/Pages/` (Inertia page components)
- Reusable Vue components in `resources/js/Components/`
- Layouts in `resources/js/Layouts/` (AppLayout for authenticated, GuestLayout for auth pages)
- Form validation via Laravel Form Request classes in `app/Http/Requests/`
- No Vuex/Pinia — Inertia's server-driven props handle state

## Agents

Specialized agents are defined in `.claude/agents/` for delegating tasks:

- **architect** — Architecture advisor. Use when planning new features, evaluating design decisions, or reviewing structural changes. Advises only, does not write code.
- **backend-coder** — Laravel/PHP developer. Use for ALL backend tasks: controllers, models, migrations, routes, form requests, middleware, services, mail, tests, and anything in `app/` or `database/`.
- **frontend-coder** — Vue 3/TypeScript developer. Use for ALL frontend tasks: pages, components, layouts, types, translations, and anything in `resources/js/`.
- **designer** — UI/UX specialist. Use when creating new pages/components or updating visual designs. Outputs structural mockups with DaisyUI + Tailwind that the frontend-coder wires up.
- **code-reviewer** — Automated code reviewer. Triggered after frontend-coder or backend-coder finish. Reviews for quality, security, and convention adherence.

### Agent workflow
1. **Planning**: Use `architect` to plan the feature and identify affected layers
2. **Implementation**: Delegate to `backend-coder` and/or `frontend-coder` (can run in parallel for independent work)
3. **Design**: Use `designer` when new UI is needed — designer creates the markup, frontend-coder adds logic
4. **Review**: `code-reviewer` runs automatically after coder agents finish

### Important constraints for agents
- Agents cannot run `ddev` commands — they don't have container access
- All agents should read `CLAUDE.md` before starting work
- This is an Inertia.js monolith — no separate API layer, no Pinia stores, no client-side routing

## Deployment

Target: Plesk hosting. Document root must point to `/public`. Cron job needed for scheduled tasks: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
