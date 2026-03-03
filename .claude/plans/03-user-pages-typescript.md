# Plan 03: User Pages TypeScript Migration

## Goal
Convert the 3 user-facing page components to TypeScript. These pages already use DaisyUI and i18n — only TypeScript is missing.

## Dependencies
- Best run after Plan 01 (types available), but can define interfaces inline.
- Independent of Plans 02, 04, 05, 06.

## Files to Modify

### `resources/js/Pages/Dashboard.vue`
Current state: `<script setup>`, DaisyUI, i18n — fully functional, just needs TS.

Props received from `DashboardController@index`:
- `entries` — array of TimeEntry (last 5 for current month)
- `monthTotal` — number (total hours this month)
- `currentMonth` — string ('YYYY-MM' format)

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import types: `import type { TimeEntry } from '@/types';`
3. Define and use typed props:
   ```typescript
   interface Props {
       entries: TimeEntry[];
       monthTotal: number;
       currentMonth: string;
   }
   const props = defineProps<Props>();
   ```
4. Type local state refs (e.g., `showForm: ref<boolean>(false)`)
5. Ensure component imports are correct (TimeEntryForm, TimeEntryCard, HoursSummary, MonthNavigator)

### `resources/js/Pages/TimeEntries/Index.vue`
Current state: `<script setup>`, DaisyUI, i18n — fully functional, just needs TS.

Props received from `TimeEntryController@index`:
- `entries` — array of TimeEntry (all entries for selected month)
- `monthTotal` — number
- `currentMonth` — string ('YYYY-MM')

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import types: `import type { TimeEntry } from '@/types';`
3. Define and use typed props:
   ```typescript
   interface Props {
       entries: TimeEntry[];
       monthTotal: number;
       currentMonth: string;
   }
   const props = defineProps<Props>();
   ```
4. Type the `editingEntry` ref: `ref<TimeEntry | null>(null)`
5. Type the `showForm` ref: `ref<boolean>(false)`

### `resources/js/Pages/Preferences.vue`
Current state: `<script setup>`, DaisyUI, i18n — fully functional, just needs TS.

Props received from `PreferencesController@edit`:
- `preferences` — object with user preferences (language setting)

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import types: `import type { UserPreferences } from '@/types';`
3. Define and use typed props:
   ```typescript
   interface Props {
       preferences: UserPreferences;
   }
   const props = defineProps<Props>();
   ```
4. Type the `useForm` call

## Verification
- `ddev npm run build` should succeed
- Navigate to `/dashboard` — should render with entries, month total, quick-add form
- Navigate to `/time-entries` — should show monthly list, month navigation, add/edit/delete
- Navigate to `/preferences` — should show language selector and save
