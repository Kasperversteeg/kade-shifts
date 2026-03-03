# Plan 06: Dead Code Cleanup

## Goal
Remove unused Breeze scaffold components and dead pages that are no longer needed after the auth/profile pages have been rewritten to use DaisyUI directly.

## Dependencies
- **MUST run after Plan 05** (auth/profile rewrite) — these components are still imported by the old Breeze-style auth pages. Only delete once no file imports them.

## Files to Delete

### Unused Breeze Components
After Plan 05 rewrites all auth/profile pages to use DaisyUI directly, these components will have zero imports:

1. `resources/js/Components/Checkbox.vue` — was only used in Login.vue
2. `resources/js/Components/DangerButton.vue` — was only used in DeleteUserForm.vue
3. `resources/js/Components/Dropdown.vue` — not used anywhere (AuthenticatedLayout uses DaisyUI dropdown directly)
4. `resources/js/Components/DropdownLink.vue` — not used anywhere
5. `resources/js/Components/InputError.vue` — was used in all auth/profile pages
6. `resources/js/Components/InputLabel.vue` — was used in all auth/profile pages
7. `resources/js/Components/Modal.vue` — was only used in DeleteUserForm.vue
8. `resources/js/Components/NavLink.vue` — not used anywhere (AuthenticatedLayout uses Inertia Link directly)
9. `resources/js/Components/PrimaryButton.vue` — was used in all auth pages
10. `resources/js/Components/ResponsiveNavLink.vue` — not used anywhere
11. `resources/js/Components/SecondaryButton.vue` — was only used in DeleteUserForm.vue
12. `resources/js/Components/TextInput.vue` — was used in all auth/profile pages

### Dead Pages
1. `resources/js/Pages/Welcome.vue` — stock Laravel welcome page, never customized. Route `/` redirects to `/dashboard`.
2. `resources/js/Pages/Auth/Register.vue` — registration is invitation-only via `AcceptInvitation.vue`. Open registration is disabled.

## Verification Steps
Before deleting each file, confirm zero imports:
```bash
# For each component/page, search for imports
ddev exec grep -r "ComponentName" resources/js/ --include="*.vue" --include="*.js" --include="*.ts"
```

After deletion:
- `ddev npm run build` should succeed with no missing import errors
- All app routes should still work
- No console errors in browser

## Route Cleanup (Optional)
If `Register.vue` is deleted, consider also:
- Removing the registration routes from `routes/auth.php` (the `RegisteredUserController` routes)
- This prevents anyone from navigating to `/register` directly

## Notes
- Keep `ApplicationLogo.vue` — it's used by `GuestLayout.vue`
- Keep `GuestLayout.vue` — it's used by auth pages (Login, ForgotPassword, etc.)
