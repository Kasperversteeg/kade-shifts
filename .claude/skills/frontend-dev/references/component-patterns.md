# Component Patterns Reference

## TypeScript Interfaces

### Core Data Types

Define shared interfaces in `resources/js/types/` or inline in components:

```typescript
// resources/js/types/index.ts

export interface User {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'admin';
    email_verified_at: string | null;
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
    status: 'pending' | 'accepted' | 'expired';
    invited_by: User;
    expires_at: string;
    created_at: string;
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

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
```

### Typed Props

```vue
<script setup lang="ts">
import type { TimeEntry } from '@/types';

interface Props {
    entries: TimeEntry[];
    monthTotal: number;
    currentMonth: string;
}

const props = defineProps<Props>();
```

### Props with Defaults

```vue
<script setup lang="ts">
interface Props {
    title: string;
    readonly?: boolean;
    variant?: 'primary' | 'secondary' | 'ghost';
}

const props = withDefaults(defineProps<Props>(), {
    readonly: false,
    variant: 'primary',
});
```

### Typed Emits

```vue
<script setup lang="ts">
import type { TimeEntry } from '@/types';

const emit = defineEmits<{
    close: [];
    save: [entry: TimeEntry];
    delete: [id: number];
    'update:modelValue': [value: string];
}>();
```

### Typed Template Refs

```vue
<script setup lang="ts">
import { ref } from 'vue';

const dialog = ref<HTMLDialogElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);
```

### Typed Computed

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value.role === 'admin');
```

## Form Handling Patterns

All forms use DaisyUI v5 syntax: `fieldset`/`fieldset-legend` for labeled fields. **Never** use deprecated v4 classes (`form-control`, `label-text`, `input-bordered`, etc.).

### Basic Form (POST)

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    date: '',
    shift_start: '09:00',
    shift_end: '17:00',
    break_minutes: 30,
    notes: '',
});

const submit = (): void => {
    form.post(route('time-entries.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <fieldset class="fieldset">
            <legend class="fieldset-legend">{{ t('timeEntries.date') }}</legend>
            <input type="date" v-model="form.date" class="input w-full"
                :class="{ 'input-error': form.errors.date }" />
            <p v-if="form.errors.date" class="label text-error">{{ form.errors.date }}</p>
        </fieldset>

        <button type="submit" class="btn btn-primary" :disabled="form.processing">
            {{ form.processing ? t('timeEntries.saving') : t('timeEntries.save') }}
        </button>
    </form>
</template>
```

### Edit Form (PATCH)

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import type { TimeEntry } from '@/types';

interface Props {
    entry: TimeEntry;
}

const props = defineProps<Props>();

const form = useForm({
    date: props.entry.date,
    shift_start: props.entry.shift_start.substring(0, 5),
    shift_end: props.entry.shift_end.substring(0, 5),
    break_minutes: props.entry.break_minutes,
    notes: props.entry.notes ?? '',
});

const submit = (): void => {
    form.patch(route('time-entries.update', props.entry.id), {
        onSuccess: () => {
            // handle success
        },
    });
};
```

### Delete Action

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const deleteForm = useForm({});

const handleDelete = (id: number): void => {
    if (confirm('Are you sure?')) {
        deleteForm.delete(route('time-entries.destroy', id));
    }
};
```

## Modal Pattern

The project uses native `<dialog>` with DaisyUI modal classes:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    show: boolean;
    title: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{ close: [] }>();
const { t } = useI18n();

const dialog = ref<HTMLDialogElement | null>(null);

watch(() => props.show, (val) => {
    if (val) {
        dialog.value?.showModal();
    } else {
        dialog.value?.close();
    }
});

const close = (): void => {
    emit('close');
};
</script>

<template>
    <dialog ref="dialog" class="modal" @close="close">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">{{ title }}</h3>
            <slot />
            <div class="modal-action">
                <slot name="actions">
                    <button class="btn btn-ghost" @click="close">
                        {{ t('common.cancel') }}
                    </button>
                </slot>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="close">close</button>
        </form>
    </dialog>
</template>
```

## Page Component Pattern

### Authenticated Page

```vue
<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { TimeEntry } from '@/types';

interface Props {
    entries: TimeEntry[];
    monthTotal: number;
    currentMonth: string;
}

defineProps<Props>();
const { t } = useI18n();
</script>

<template>
    <Head :title="t('pageSection.title')" />

    <AuthenticatedLayout>
        <div class="grid gap-4">
            <!-- Page content using DaisyUI cards, tables, etc. -->
        </div>
    </AuthenticatedLayout>
</template>
```

### Guest Page (Auth)

```vue
<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    email: '',
    password: '',
});
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.login')" />
        <!-- Auth form content -->
    </GuestLayout>
</template>
```

## State Management Patterns

### Local UI State

```typescript
const showForm = ref<boolean>(false);
const editingEntry = ref<TimeEntry | null>(null);
const selectedMonth = ref<string>(dayjs().format('YYYY-MM'));
```

### Derived State from Inertia Props

```typescript
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);
```

### Month Navigation

```typescript
import dayjs from 'dayjs';
import { router } from '@inertiajs/vue3';

const navigateToMonth = (month: string): void => {
    router.get(route('time-entries.index'), { month }, {
        preserveState: true,
    });
};

const previousMonth = (current: string): string => {
    return dayjs(current).subtract(1, 'month').format('YYYY-MM');
};

const nextMonth = (current: string): string => {
    return dayjs(current).add(1, 'month').format('YYYY-MM');
};
```

## Event Handling Patterns

### Parent-Child Communication

```vue
<!-- Parent -->
<TimeEntryCard
    v-for="entry in entries"
    :key="entry.id"
    :entry="entry"
    @edit="editingEntry = $event"
    @delete="handleDelete($event)"
/>

<!-- Child (TimeEntryCard) -->
<script setup lang="ts">
import type { TimeEntry } from '@/types';

interface Props {
    entry: TimeEntry;
    readonly?: boolean;
}

defineProps<Props>();
const emit = defineEmits<{
    edit: [entry: TimeEntry];
    delete: [id: number];
}>();
</script>
```

## Date Formatting

Always use dayjs:

```typescript
import dayjs from 'dayjs';

// Display date
dayjs(entry.date).format('ddd, MMM D, YYYY')  // "Mon, Jan 6, 2025"

// Display month
dayjs(currentMonth).format('MMMM YYYY')  // "January 2025"

// Time range
`${entry.shift_start.substring(0, 5)} - ${entry.shift_end.substring(0, 5)}`

// Default form date
dayjs().format('YYYY-MM-DD')
```
