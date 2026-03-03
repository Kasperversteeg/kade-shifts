# Plan 02: Custom Components TypeScript Migration

## Goal
Convert all 6 custom (non-Breeze) components to TypeScript with proper typed props and emits.

## Dependencies
- Best run after Plan 01 (types/index.ts exists), but can create inline interfaces if running in parallel.

## Files to Modify

### `resources/js/Components/TimeEntryForm.vue`
Current state: `<script setup>`, DaisyUI, i18n — all good except no TS.

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import `useForm` type from Inertia (it's already typed)
3. Type the `emit` calls:
   ```typescript
   const emit = defineEmits<{
       cancel: [];
       success: [];
   }>();
   ```
4. Type the `form` — `useForm()` is already generic, just ensure the initial values have correct types
5. Type the `submit` function return as `void`

### `resources/js/Components/TimeEntryCard.vue`
Current state: `<script setup>`, DaisyUI, i18n — all good except no TS.

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import `TimeEntry` from `@/types` (or define inline)
3. Replace `defineProps` with typed version:
   ```typescript
   interface Props {
       entry: TimeEntry;
       readonly?: boolean;
   }
   const props = withDefaults(defineProps<Props>(), {
       readonly: false,
   });
   ```
4. Type emits:
   ```typescript
   const emit = defineEmits<{
       edit: [entry: TimeEntry];
   }>();
   ```
5. Type the `useForm` for delete and `handleDelete` function

### `resources/js/Components/EditTimeEntryModal.vue`
Current state: `<script setup>`, DaisyUI, i18n — all good except no TS.

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import `TimeEntry` from `@/types`
3. Type props:
   ```typescript
   interface Props {
       entry: TimeEntry | null;
       show: boolean;
   }
   const props = defineProps<Props>();
   ```
4. Type emits:
   ```typescript
   const emit = defineEmits<{
       close: [];
   }>();
   ```
5. Type the dialog ref: `ref<HTMLDialogElement | null>(null)`
6. Type the `useForm` for editing
7. Type the watch callback and submit function

### `resources/js/Components/HoursSummary.vue`
Current state: `<script setup>`, DaisyUI, i18n — all good except no TS.

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Type props:
   ```typescript
   interface Props {
       total: number;
       month: string;
   }
   defineProps<Props>();
   ```
3. Import dayjs type if used for formatting

### `resources/js/Components/MonthNavigator.vue`
Current state: `<script setup>`, DaisyUI, i18n — all good except no TS.

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Type props:
   ```typescript
   interface Props {
       currentMonth: string;
   }
   defineProps<Props>();
   ```
3. Type the computed values and navigation functions

### `resources/js/Components/ThemeToggle.vue`
Current state: `<script setup>`, DaisyUI — all good except no TS.

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Type the `isDark` ref as `ref<boolean>`
3. Type the `toggleTheme` function return as `void`

## Do NOT modify
- Any Breeze scaffold components (Checkbox, DangerButton, Dropdown, DropdownLink, InputError, InputLabel, Modal, NavLink, PrimaryButton, ResponsiveNavLink, SecondaryButton, TextInput) — these are handled in Plan 06 (dead code cleanup) or Plan 05 (auth/profile rewrite)
- ApplicationLogo.vue — template-only, no script needed

## Verification
- `ddev npm run build` should succeed
- Dashboard should render correctly (uses TimeEntryForm, TimeEntryCard, HoursSummary)
- TimeEntries/Index should render correctly (uses all components)
- Edit modal should open/close and save correctly
- Month navigation should work
- Theme toggle should work
