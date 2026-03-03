# Plan 01: TypeScript Foundation + Layouts + App Bootstrap

## Goal
Create shared TypeScript type definitions, convert `app.js` to `app.ts`, and convert both layouts to TypeScript. This is the foundation that all other TypeScript migration plans build on.

## Dependencies
- None (this plan should run first or in parallel with plans that don't need types)

## Files to Create

### `resources/js/types/index.ts`
Define all shared interfaces used across the app:

```typescript
export interface User {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'admin';
    email_verified_at: string | null;
    google_id: string | null;
    preferences: UserPreferences | null;
}

export interface UserPreferences {
    language?: 'en' | 'nl';
}

export interface TimeEntry {
    id: number;
    user_id: number;
    date: string;           // 'YYYY-MM-DD'
    shift_start: string;    // 'HH:mm:ss'
    shift_end: string;      // 'HH:mm:ss'
    break_minutes: number;
    total_hours: number;    // decimal(5,2)
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface Invitation {
    id: number;
    email: string;
    token: string;
    invited_by: number;
    inviter?: User;
    expires_at: string;
    accepted_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PageProps {
    auth: {
        user: User;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    locale: string;
}

// Used in Admin/Overview
export interface UserWithHours {
    id: number;
    name: string;
    email: string;
    total_hours: number;
}
```

### `resources/js/types/global.d.ts`
Ziggy route helper type declaration:

```typescript
import { route as ziggyRoute } from 'ziggy-js';

declare global {
    function route: typeof ziggyRoute;
}
```

## Files to Modify

### `resources/js/app.js` → `resources/js/app.ts`
1. Rename file from `.js` to `.ts`
2. Add type imports where needed
3. Update `vite.config.js` input from `resources/js/app.js` to `resources/js/app.ts`
4. Update `resources/views/app.blade.php` if it references `app.js` directly

### `resources/js/Layouts/AuthenticatedLayout.vue`
Current state: `<script setup>` (no TS), uses DaisyUI, has i18n. All good except no TypeScript.

Changes:
1. Add `lang="ts"` to script tag
2. Import `PageProps` from `@/types`
3. Type the `usePage()` call: `usePage<PageProps>()`
4. Type all `ref()` declarations (e.g., `showingNavigationDropdown: ref<boolean>(false)`)
5. Type the flash watch callback
6. Type the `showToast` ref and timeout

### `resources/js/Layouts/GuestLayout.vue`
Current state: `<script setup>` (no TS), uses DaisyUI. No props, very simple.

Changes:
1. Add `lang="ts"` to script tag
2. No other changes needed (no props, no complex state)

## Verification
After changes:
- `ddev npm run build` should succeed without TypeScript errors
- Both layouts should render correctly
- Flash toast notifications should still work in AuthenticatedLayout
