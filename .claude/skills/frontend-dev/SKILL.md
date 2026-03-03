---
name: frontend-dev
description: This skill should be used when the user asks to "build a component", "create a page", "style the UI", "add a modal", "design the frontend", "fix the layout", "add a form", "update the design", or any frontend development task. Enforces TypeScript and DaisyUI for all Vue 3 components.
version: 0.1.0
user-invocable: true
argument-hint: [description of the UI to build]
---

# Frontend Development — Vue 3 + TypeScript + DaisyUI

Build and modify frontend components for the Kade Shifts app. All work MUST use TypeScript (`<script setup lang="ts">`) and DaisyUI component classes.

## Before Writing Code

1. Read the relevant existing components to understand current patterns
2. Check `references/component-patterns.md` for TypeScript interfaces and DaisyUI patterns
3. Check `references/design-system.md` for the project's DaisyUI theme configuration and component class reference

## Component Scaffold

Every new Vue component follows this exact structure:

```vue
<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

// 1. TypeScript interfaces
interface Props {
    // typed props
}

// 2. Props and emits
const props = defineProps<Props>();
const emit = defineEmits<{
    // typed events
}>();

// 3. Composables
const { t } = useI18n();

// 4. Reactive state
// 5. Computed properties
// 6. Methods
</script>

<template>
    <!-- DaisyUI components with Tailwind utilities -->
</template>
```

## Rules

- **TypeScript required**: `<script setup lang="ts">` on every component. Define interfaces for props, emits, and data.
- **DaisyUI v5 syntax**: Use `btn`, `card`, `fieldset`/`fieldset-legend`, `input`, `modal`, `table`, `alert`, `badge`, `navbar`, `menu`, `dropdown`, `stats`, `swap`, `toggle` classes. **Never** use deprecated v4 classes like `form-control`, `label-text`, `label-text-alt`, `input-bordered`, `textarea-bordered`, or `select-bordered`. Only use raw Tailwind for spacing, grid, and flex layout.
- **Theme-aware colors**: Use `bg-base-100/200/300`, `text-base-content`, `text-primary`, `btn-primary/secondary/accent/error`. Never hardcode hex or named colors.
- **Translations**: All user-facing strings through `t('section.key')`. Add to both `resources/js/lang/en.json` and `nl.json`.
- **Inertia patterns**: Use `useForm()` for forms, `Link` for navigation, `usePage()` for shared props. Never use `fetch()` or `axios` directly for page data.
- **Route helpers**: Use `route('name', params)` via Ziggy. Never hardcode URLs.
- **Responsive**: Mobile-first with `md:` and `lg:` breakpoints.

## Additional Resources

### References
- **`references/component-patterns.md`** — TypeScript interface patterns, form handling, modal patterns, and complete typed component examples
- **`references/design-system.md`** — DaisyUI class reference, theme configuration, responsive patterns, and icon usage for this project
