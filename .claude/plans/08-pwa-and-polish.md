# Plan 08: PWA Assets + Backend Polish

## Goal
Complete PWA setup with required icon assets, and fix the email queueing issue.

## Dependencies
- Independent of all other plans.

## PWA Icon Assets

### Current State
`vite.config.js` already has full VitePWA configuration with manifest, but references icon files that may not exist:
- `/favicon.ico`
- `/apple-touch-icon.png`
- `/pwa-192x192.png`
- `/pwa-512x512.png`

### Action: Check and Create PWA Icons
1. Check if these files exist in `public/`:
   ```bash
   ls -la public/favicon.ico public/apple-touch-icon.png public/pwa-192x192.png public/pwa-512x512.png
   ```

2. If missing, generate them. The theme color is `#4d5930` (olive green from the kade theme). Options:
   - Use `@vite-pwa/assets-generator` (already in devDependencies) to generate from a source SVG/PNG
   - Create a simple SVG logo and convert to required sizes
   - Use the existing `ApplicationLogo.vue` SVG as the source

3. Required files in `public/`:
   - `favicon.ico` — 32x32 or multi-size .ico
   - `apple-touch-icon.png` — 180x180
   - `pwa-192x192.png` — 192x192
   - `pwa-512x512.png` — 512x512

4. Add to `<head>` in `resources/views/app.blade.php` (if not already present):
   ```html
   <link rel="apple-touch-icon" href="/apple-touch-icon.png">
   <meta name="theme-color" content="#4d5930">
   ```

### Service Worker Verification
The VitePWA config sets:
- `registerType: 'autoUpdate'` — SW updates automatically
- `navigateFallback: '/'` — offline fallback to root
- `globPatterns: ['**/*.{js,css,ico,png,svg}']` — cache static assets

Verify after build that `sw.js` is generated in `public/`.

## Backend: Queue Email Sending

### Current State
In `AdminController@sendMonthlyReport`, the email is sent synchronously:
```php
Mail::to($admin->email)->send(new MonthlyHoursReport(...));
```

### Fix
Change to queued sending:
```php
Mail::to($admin->email)->queue(new MonthlyHoursReport(...));
```

The `MonthlyHoursReport` mailable already extends `Mailable` and uses `Queueable`, so it's ready for queuing. The `jobs` migration already exists.

### Queue Worker Note
For local development, the queue worker runs via:
```bash
ddev artisan queue:work
```

For production on Plesk, a cron job or supervisor config will be needed (document this in CLAUDE.md if not already present).

## Verification
- `ddev npm run build` — should succeed, check for `sw.js` in build output
- Open site on mobile browser → should show "Add to Home Screen" prompt
- Send monthly report as admin → should return immediately (not wait for email to send)
- Run `ddev artisan queue:work` and verify email is processed from queue
