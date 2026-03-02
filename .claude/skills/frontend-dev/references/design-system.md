# Design System Reference

## DaisyUI Configuration

The project uses DaisyUI 5.x on Tailwind CSS 4.x. Theme config in `resources/css/app.css`:

```css
@import "tailwindcss";
@plugin "@tailwindcss/forms";
@plugin "daisyui" {
    themes: light --default, dark;
}
```

Two themes enabled: `light` (default) and `dark`. Theme is toggled via `data-theme` attribute on `<html>` and persisted in `localStorage`.

## DaisyUI Component Classes

### Buttons

```html
<!-- Variants -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-accent">Accent</button>
<button class="btn btn-ghost">Ghost</button>
<button class="btn btn-outline">Outline</button>
<button class="btn btn-link">Link</button>

<!-- Semantic -->
<button class="btn btn-success">Success</button>
<button class="btn btn-warning">Warning</button>
<button class="btn btn-error">Error</button>
<button class="btn btn-info">Info</button>

<!-- Sizes -->
<button class="btn btn-xs">Tiny</button>
<button class="btn btn-sm">Small</button>
<button class="btn">Normal</button>
<button class="btn btn-lg">Large</button>

<!-- States -->
<button class="btn btn-primary" :disabled="form.processing">
    {{ form.processing ? 'Saving...' : 'Save' }}
</button>

<!-- With icon -->
<button class="btn btn-primary gap-2">
    <svg>...</svg>
    Label
</button>
```

### Cards

```html
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">Title</h2>
        <p>Content</p>
        <div class="card-actions justify-end">
            <button class="btn btn-primary">Action</button>
        </div>
    </div>
</div>

<!-- Compact card -->
<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <!-- Reduced padding -->
    </div>
</div>
```

### Forms

```html
<!-- Text input -->
<div class="form-control">
    <label class="label">
        <span class="label-text">Label</span>
    </label>
    <input type="text" class="input input-bordered" />
    <label class="label">
        <span class="label-text-alt text-error">Error message</span>
    </label>
</div>

<!-- Select -->
<div class="form-control">
    <label class="label">
        <span class="label-text">Pick one</span>
    </label>
    <select class="select select-bordered">
        <option disabled selected>Choose</option>
        <option>Option A</option>
    </select>
</div>

<!-- Textarea -->
<div class="form-control">
    <label class="label">
        <span class="label-text">Notes</span>
    </label>
    <textarea class="textarea textarea-bordered" rows="3"></textarea>
</div>

<!-- Error state -->
<input class="input input-bordered input-error" />
<textarea class="textarea textarea-bordered textarea-error"></textarea>
<select class="select select-bordered select-error"></select>
```

### Tables

```html
<div class="overflow-x-auto">
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Hours</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="user in users" :key="user.id">
                <td>{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td>
                    <span class="badge badge-primary">{{ user.hours }}h</span>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total</th>
                <th>{{ total }}h</th>
            </tr>
        </tfoot>
    </table>
</div>
```

### Modals

```html
<!-- Using native dialog -->
<dialog ref="dialog" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Title</h3>
        <!-- Content -->
        <div class="modal-action">
            <button class="btn btn-ghost">Cancel</button>
            <button class="btn btn-primary">Confirm</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>
```

### Navbar

```html
<div class="navbar bg-base-100 shadow-lg">
    <div class="navbar-start">
        <!-- Logo / hamburger -->
    </div>
    <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal px-1">
            <li><a>Link</a></li>
            <li><a class="active">Active</a></li>
        </ul>
    </div>
    <div class="navbar-end">
        <!-- Actions -->
    </div>
</div>
```

### Alerts / Flash Messages

```html
<div class="alert alert-success">
    <span>Operation successful!</span>
</div>
<div class="alert alert-error">
    <span>Something went wrong.</span>
</div>
<div class="alert alert-warning">
    <span>Warning message.</span>
</div>
<div class="alert alert-info">
    <span>Info message.</span>
</div>
```

### Badges

```html
<span class="badge badge-primary">Primary</span>
<span class="badge badge-secondary">Secondary</span>
<span class="badge badge-accent">Accent</span>
<span class="badge badge-ghost">Ghost</span>
<span class="badge badge-outline">Outline</span>

<!-- Sizes -->
<span class="badge badge-sm">Small</span>
<span class="badge badge-lg">Large</span>
```

### Stats

```html
<div class="stats shadow">
    <div class="stat">
        <div class="stat-figure text-primary">
            <svg><!-- icon --></svg>
        </div>
        <div class="stat-title">Total Hours</div>
        <div class="stat-value text-primary">152.5</div>
        <div class="stat-desc">January 2025</div>
    </div>
</div>
```

### Dropdown

```html
<div class="dropdown dropdown-end">
    <div tabindex="0" role="button" class="btn btn-ghost">
        Menu
    </div>
    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
        <li><a>Item 1</a></li>
        <li><a>Item 2</a></li>
    </ul>
</div>
```

### Loading States

```html
<span class="loading loading-spinner loading-md"></span>
<span class="loading loading-dots loading-md"></span>
<button class="btn btn-primary" :disabled="loading">
    <span v-if="loading" class="loading loading-spinner loading-sm"></span>
    Save
</button>
```

### Toggle / Swap (Theme)

```html
<!-- Theme toggle (sun/moon) -->
<label class="swap swap-rotate btn btn-ghost btn-circle">
    <input type="checkbox" :checked="isDark" @change="toggleTheme" />
    <svg class="swap-on h-5 w-5" fill="currentColor"><!-- moon --></svg>
    <svg class="swap-off h-5 w-5" fill="currentColor"><!-- sun --></svg>
</label>
```

## Theme-Aware Color Tokens

### Background

| Token | Light | Dark | Use |
|-------|-------|------|-----|
| `bg-base-100` | white | dark gray | Primary surface (cards, modals) |
| `bg-base-200` | light gray | darker gray | Page background |
| `bg-base-300` | medium gray | darkest gray | Nested surfaces |

### Text

| Token | Use |
|-------|-----|
| `text-base-content` | Primary text |
| `text-primary` | Primary accent text |
| `text-secondary` | Secondary accent text |
| `text-error` | Error text |
| `opacity-60` | Muted / placeholder text |

### Borders

| Token | Use |
|-------|-----|
| `border-base-300` | Default borders |
| `border-primary` | Accent borders |
| `border-error` | Error state borders |

## Responsive Layout Patterns

### Page Grid

```html
<!-- Cards stack on mobile, 2-col on tablet, 3-col on desktop -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="card bg-base-100 shadow-xl">...</div>
</div>
```

### Form Grid

```html
<!-- Fields stack on mobile, 2-col on tablet+ -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="form-control">...</div>
    <div class="form-control">...</div>
</div>
```

### Container

```html
<main class="container mx-auto p-4">
    <!-- Page content -->
</main>
```

### Mobile Navigation

```html
<!-- Hidden on desktop, shown on mobile -->
<div class="lg:hidden">
    <!-- Mobile menu content -->
</div>

<!-- Hidden on mobile, shown on desktop -->
<div class="hidden lg:flex">
    <!-- Desktop menu content -->
</div>
```

## Icon Usage

The project uses inline SVGs (no icon library). Common icons:

```html
<!-- Hamburger menu -->
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
</svg>

<!-- Clock -->
<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>

<!-- Chevron left/right (for navigation) -->
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
</svg>

<!-- Edit (pencil) -->
<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
</svg>

<!-- Trash -->
<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
</svg>

<!-- Plus -->
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
</svg>

<!-- Download / Export -->
<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
</svg>
```

Use `class="h-5 w-5"` for standard size, `h-4 w-4` for small (in buttons), `h-8 w-8` for stat figures. Always use `stroke="currentColor"` so icons inherit text color from DaisyUI theme.

## Spacing Conventions

- Card body: default padding (via `card-body`) or `p-4` for compact
- Between cards/sections: `gap-4` in grid or `space-y-4` in flex
- Between form fields: `gap-4` in grid
- Page padding: `p-4` on container
- Button groups: `gap-2` or `flex gap-2`
- Section headings: `mb-4` below heading

## Empty States

```html
<div class="text-center py-8 opacity-60">
    {{ t('section.noData') }}
</div>
```

## Transition Patterns

For showing/hiding content, use `v-if` / `v-show` — no Vue transition wrappers needed for simple toggles. The native `<dialog>` element handles modal animations via DaisyUI.
