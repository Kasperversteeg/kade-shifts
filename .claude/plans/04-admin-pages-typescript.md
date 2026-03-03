# Plan 04: Admin Pages TypeScript Migration

## Goal
Convert the 3 admin page components to TypeScript. These pages already use DaisyUI and i18n — only TypeScript is missing.

## Dependencies
- Best run after Plan 01 (types available), but can define interfaces inline.
- Independent of Plans 02, 03, 05, 06.

## Files to Modify

### `resources/js/Pages/Admin/Overview.vue`
Current state: `<script setup>`, DaisyUI, i18n — fully functional, just needs TS.

Props received from `AdminController@overview`:
- `users` — array of objects with `{ id, name, email, total_hours }`
- `grandTotal` — number (sum of all users' hours)
- `currentMonth` — string ('YYYY-MM')

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import types: `import type { UserWithHours } from '@/types';`
3. Define and use typed props:
   ```typescript
   interface Props {
       users: UserWithHours[];
       grandTotal: number;
       currentMonth: string;
   }
   const props = defineProps<Props>();
   ```
4. Type computed values and any local refs
5. Type the `sendReport` function and any Inertia form calls

### `resources/js/Pages/Admin/UserDetail.vue`
Current state: `<script setup>`, DaisyUI, i18n — fully functional, just needs TS.

Props received from `AdminController@userDetail`:
- `user` — User object (the user being viewed)
- `entries` — array of TimeEntry for that user/month
- `monthTotal` — number
- `currentMonth` — string ('YYYY-MM')

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import types: `import type { User, TimeEntry } from '@/types';`
3. Define and use typed props:
   ```typescript
   interface Props {
       user: User;
       entries: TimeEntry[];
       monthTotal: number;
       currentMonth: string;
   }
   const props = defineProps<Props>();
   ```

### `resources/js/Pages/Admin/Invitations.vue`
Current state: `<script setup>`, DaisyUI, i18n — fully functional, just needs TS.

Props received from `InvitationController@index`:
- `invitations` — array of Invitation objects

Changes:
1. Add `lang="ts"` to `<script setup>`
2. Import types: `import type { Invitation } from '@/types';`
3. Define and use typed props:
   ```typescript
   interface Props {
       invitations: Invitation[];
   }
   const props = defineProps<Props>();
   ```
4. Type the `useForm` for sending invitations:
   ```typescript
   const form = useForm({
       email: '' as string,
   });
   ```
5. Type the `sendInvitation` function
6. Type any computed properties for invitation status display

## Verification
- `ddev npm run build` should succeed
- Navigate to `/admin/overview` — should show user table, stats, export/email buttons
- Navigate to `/admin/users/{id}` — should show user detail with month navigation
- Navigate to `/admin/invitations` — should show invite form and invitation list
