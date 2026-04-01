# Schedule Feature Plan

## Overview

Revamped schedule/planning module with two main views:
1. **Admin Schedule Maker** — team-grouped week grid with shift presets and click-to-assign
2. **Employee Month Overview** — desktop calendar grid + mobile agenda list aggregating shifts, hours, leave, and sick days

## Current State

### What exists and works
- `Shift` model (date, start_time, end_time, user_id, position, notes, published, created_by)
- `ShiftController` with full CRUD + drag-drop move + week publish + email notification
- `ScheduleController` (employee view) showing week-based list of published shifts
- `Admin/ScheduleBoard.vue` — flat week grid (employees as rows, days as columns) with drag-and-drop via `vue-draggable-plus`
- `Schedule/Index.vue` — employee week view, list of day cards with shifts
- Components: `ShiftCard.vue`, `ShiftModal.vue`, `WeekNavigator.vue`, `MonthNavigator.vue`
- `Team` model with many-to-many User pivot (name, description, created_by, soft deletes)
- `TimeEntry`, `LeaveRequest`, `SickLeave` models all functional

### What is missing
- No `ShiftPreset` model/table (shift types like "Dag", "Nacht")
- No team grouping in the schedule board
- No `shift_preset_id` on shifts table
- No month-based employee overview combining all event types
- No mobile-optimized view

---

## Database Changes

### New table: `shift_presets`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string(50) | e.g. "Dag", "Nacht", "Ochtend" |
| short_name | string(10) | Display abbreviation, e.g. "D", "N" |
| start_time | time | e.g. 09:00 |
| end_time | time | e.g. 17:00 |
| color | string(7) | Hex color for visual blocks, e.g. "#3B82F6" |
| sort_order | integer | For ordering in dropdown/UI |
| is_active | boolean | Soft toggle |
| created_by | FK users, nullable | |
| timestamps | | |

### Alter `shifts` table

Add `shift_preset_id` (nullable FK to shift_presets, `nullOnDelete`). Links a shift to its preset for color/name display. Existing `start_time`/`end_time` remain as actual times (admin can override preset defaults).

### No changes needed to `teams`, `team_user`, `time_entries`, `leave_requests`, or `sick_leaves`.

---

## Mobile View Decision

**Pattern: Agenda/list view with compact mini-calendar header**

Reasoning:
- Full month grid on 375px = ~45px per cell, too small for event details
- Week-at-a-time requires 4-5 swipes per month
- Agenda/list is the proven pattern (Google Calendar, Apple Calendar, Shiftbase)

Structure:
- **Top**: `MonthNavigator` (reuse existing)
- **Below navigator**: Mini-calendar strip — month as row of small day circles, colored dots for days with events, tapping a day scrolls the list
- **Main content**: Vertical list of day sections, each with stacked event cards, color-coded by type
- Auto-scroll to today on mount
- Empty days hidden to save space

Desktop stays as full month calendar grid. Responsive switch at `md:` breakpoint using same page component.

---

## Phase 1: Shift Presets (Backend Foundation)

### Step 1.1 — Migration + Model for ShiftPreset
**Agent: backend-coder**
- Create migration `create_shift_presets_table`
- Create `ShiftPreset` model with fillable, casts, relationships
- Create migration to add `shift_preset_id` to `shifts` table
- Update `Shift` model with `belongsTo('shiftPreset')` relationship
- Create `ShiftPresetSeeder` with defaults: "Dag" (09:00-17:00, blue), "Nacht" (23:00-07:00, purple)

### Step 1.2 — ShiftPreset CRUD API
**Agent: backend-coder**
- Create `AdminShiftPresetController` with index/store/update/destroy
- Create `StoreShiftPresetRequest` and `UpdateShiftPresetRequest`
- Add routes under admin prefix
- Pass presets to the ScheduleBoard as props

### Step 1.3 — Shift Preset Management UI
**Agent: designer** (markup), then **frontend-coder** (wiring)
- Modal accessible from the ScheduleBoard for managing shift types
- Color picker for hex color
- List of existing presets with inline edit/delete

### Step 1.4 — Update ShiftModal to support presets
**Agent: frontend-coder**
- Add preset dropdown above time fields
- When preset selected, auto-fill `start_time`, `end_time`, store `shift_preset_id`
- "Aangepast" (Custom) option clears the preset link
- Pass `shiftPresets` as prop from controller

---

## Phase 2: Team-Grouped Admin Schedule Board

### Step 2.1 — Backend: Group employees by team
**Agent: backend-coder**
- Modify `ShiftController::index()` to load teams with their members
- Build grouped structure: `teams: [{ id, name, members: [...] }]` plus "Overig" group for unassigned users
- Pass `teams` and `shiftPresets` as props

### Step 2.2 — Frontend: Refactor ScheduleBoard for team grouping
**Agent: designer** (mockup), then **frontend-coder** (implementation)
- Replace flat employee rows with collapsible team sections
- Each team is a `<tbody>` with styled header row, click to collapse
- Employee rows within each team section
- "Overig" section at bottom for users not in any team
- Unassigned-shifts row at top (outside teams)
- Persist collapse state in localStorage
- Shift blocks show preset color as left-border or background tint
- Display preset `short_name` on shift blocks

### Step 2.3 — Frontend: Update ShiftCard for preset colors
**Agent: frontend-coder**
- Accept optional `color` and `preset_name` props
- Colored left border or background based on `color`
- Show preset short name if present

---

## Phase 3: Employee Month Overview (User View)

### Step 3.1 — Backend: Month endpoint for ScheduleController
**Agent: backend-coder**
- Refactor `ScheduleController::index()` to accept `?month=YYYY-MM` (switch from week to month)
- Query four event types for authenticated user within month:
  - `shifts` (published, user_id = auth) — include preset color/name
  - `time_entries` (all statuses)
  - `leave_requests` (approved + pending)
  - `sick_leaves` (overlapping with month range)
- Build unified `events` array: each item has `date`, `type`, `label`, `detail`, `color`, `status`
- Return `calendarDays` array for month grid (including padding days)
- Return month totals: planned hours, worked hours, leave days, sick days

### Step 3.2 — Frontend: Desktop month calendar grid
**Agent: designer** (mockup), then **frontend-coder** (build)
- Refactor `Schedule/Index.vue` or new page component
- `MonthNavigator` at top (reuse existing)
- Summary stats bar: planned hours, worked hours, leave days, sick days
- 7-column calendar grid (Ma-Zo headers)
- Each day cell shows stacked event pills, color-coded:
  - Shift: blue pill with times ("09:00-17:00")
  - TimeEntry: green pill with hours ("7.5u gewerkt")
  - Leave: orange pill with type ("Vakantie")
  - Sick: red pill ("Ziek")
- Today cell highlighted with ring
- Past days slightly muted

### Step 3.3 — Frontend: Mobile agenda/list view
**Agent: designer** (mockup), then **frontend-coder** (build)
- Same page component, responsive switch at `md:` breakpoint
- Mini-calendar strip component (month as row of small day circles with event dots)
- Agenda list: day-grouped sections with full-width event cards
- Each event card: icon + type label, time range, status badge, details
- Auto-scroll to today on mount
- "Prefill uren" button on shift cards (reuse existing logic)

---

## Phase 4: Polish and Edge Cases

### Step 4.1 — TypeScript types
**Agent: frontend-coder**
- Add `ShiftPreset` interface to `types/index.ts`
- Add `CalendarEvent` union type
- Update `Shift` interface with optional `shift_preset_id`, `preset_color`, `preset_name`

### Step 4.2 — Translations
**Agent: frontend-coder**
- Add NL/EN translations for all new keys (preset names, event types, section headers)

### Step 4.3 — Tests
**Agent: backend-coder**
- Feature tests for ShiftPreset CRUD
- Feature test for month-based ScheduleController data aggregation
- Test that non-published shifts are excluded from employee view
- Test team grouping logic

---

## Reuse vs. New Code

| Artifact | Status |
|----------|--------|
| `Shift` model | **Reuse** — add relationship to preset |
| `ShiftController` | **Modify** — add team grouping + presets to index |
| `ScheduleController` | **Major refactor** — week to month, aggregate 4 event types |
| `ShiftModal.vue` | **Modify** — add preset dropdown |
| `ShiftCard.vue` | **Modify** — add color support |
| `WeekNavigator.vue` | **Reuse as-is** in admin board |
| `MonthNavigator.vue` | **Reuse as-is** in employee view |
| `Admin/ScheduleBoard.vue` | **Major refactor** — team sections, preset colors |
| `Schedule/Index.vue` | **Rewrite** — new month grid + mobile agenda |
| `ShiftPreset` model | **New** |
| `AdminShiftPresetController` | **New** |
| `ShiftPresetModal.vue` | **New** component |
| `MiniCalendar.vue` | **New** component (mobile month strip) |
| `AgendaList.vue` | **New** component (mobile event list) |
| `MonthCalendarGrid.vue` | **New** component (desktop calendar) |
| `EventPill.vue` | **New** component (color-coded event display) |

---

## Risks and Decisions

1. **Cross-midnight shifts**: "Nacht" (23:00-07:00) spans two days. **Decision**: show on start date only — consistent with existing `date` column.

2. **Drag-and-drop with team sections**: Current `vue-draggable-plus` uses flat structure. Team grouping means multiple `<tbody>` elements. Same `group="shifts"` attribute should work across sections but needs testing.

3. **Shift preset deletion**: Use `nullOnDelete` on FK + `is_active` toggle on presets. Deactivated presets stop showing in dropdown but existing shifts keep their color/name.

4. **Employee week view breaking change**: Current `Schedule/Index.vue` is week-based. Changing to month changes the URL contract (`?week=` to `?month=`). Low risk given pre-production state.

5. **Performance**: Month view queries 4 tables for single user — no concern at 15-20 person team size.

---

## Implementation Order

1. **Phase 1** — foundational, unblocks everything. Can ship independently.
2. **Phase 2** — builds on Phase 1, admin-facing only.
3. **Phase 3** — biggest piece, depends on Phase 1 for preset colors. Step 3.1 (backend) and Step 2.2 (frontend) can run in parallel.
4. **Phase 4** — woven throughout each phase, not deferred.
