<script setup lang="ts">
/**
 * Example: A fully typed, DaisyUI-styled component
 * demonstrating the project's standard patterns.
 *
 * This is a reference example — not a live component.
 */
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';

// ── Types ────────────────────────────────────────────
interface TimeEntry {
    id: number;
    date: string;
    shift_start: string;
    shift_end: string;
    break_minutes: number;
    total_hours: number;
    notes: string | null;
}

interface Props {
    entries: TimeEntry[];
    monthTotal: number;
    currentMonth: string;
}

// ── Props & Emits ────────────────────────────────────
const props = defineProps<Props>();

const emit = defineEmits<{
    edit: [entry: TimeEntry];
    delete: [id: number];
    'month-change': [month: string];
}>();

// ── Composables ──────────────────────────────────────
const { t } = useI18n();

// ── State ────────────────────────────────────────────
const showForm = ref<boolean>(false);
const editingEntry = ref<TimeEntry | null>(null);

// ── Computed ─────────────────────────────────────────
const formattedMonth = computed<string>(() =>
    dayjs(props.currentMonth).format('MMMM YYYY')
);

const hasEntries = computed<boolean>(() => props.entries.length > 0);

// ── Form ─────────────────────────────────────────────
const form = useForm({
    date: dayjs().format('YYYY-MM-DD'),
    shift_start: '09:00',
    shift_end: '17:00',
    break_minutes: 30,
    notes: '',
});

// ── Methods ──────────────────────────────────────────
const submit = (): void => {
    form.post(route('time-entries.store'), {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const handleEdit = (entry: TimeEntry): void => {
    editingEntry.value = entry;
    emit('edit', entry);
};

const handleDelete = (id: number): void => {
    if (confirm(t('common.confirmDelete'))) {
        emit('delete', id);
    }
};

const formatTime = (time: string): string => {
    return time.substring(0, 5);
};

const formatDate = (date: string): string => {
    return dayjs(date).format('ddd, MMM D, YYYY');
};
</script>

<template>
    <!-- Summary Stats -->
    <div class="stats shadow">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-title">{{ t('summary.totalHours') }}</div>
            <div class="stat-value text-primary">{{ monthTotal }}{{ t('summary.h') }}</div>
            <div class="stat-desc">{{ formattedMonth }}</div>
        </div>
    </div>

    <!-- Entry Form Card -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">{{ t('dashboard.addHours') }}</h2>

            <button
                v-if="!showForm"
                class="btn btn-primary"
                @click="showForm = true"
            >
                {{ t('dashboard.addNewEntry') }}
            </button>

            <form v-else @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">{{ t('timeEntries.date') }}</span>
                        </label>
                        <input
                            type="date"
                            v-model="form.date"
                            class="input input-bordered"
                            :class="{ 'input-error': form.errors.date }"
                        />
                        <label v-if="form.errors.date" class="label">
                            <span class="label-text-alt text-error">{{ form.errors.date }}</span>
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">{{ t('timeEntries.shiftStart') }}</span>
                        </label>
                        <input
                            type="time"
                            v-model="form.shift_start"
                            class="input input-bordered"
                            :class="{ 'input-error': form.errors.shift_start }"
                        />
                        <label v-if="form.errors.shift_start" class="label">
                            <span class="label-text-alt text-error">{{ form.errors.shift_start }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex gap-2 justify-end">
                    <button type="button" class="btn btn-ghost" @click="showForm = false">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                        {{ form.processing ? t('timeEntries.saving') : t('timeEntries.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Entry List Card -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title mb-4">{{ t('dashboard.recentEntries') }}</h2>

            <div v-if="!hasEntries" class="text-center py-8 opacity-60">
                {{ t('dashboard.noEntries') }}
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="entry in entries"
                    :key="entry.id"
                    class="card bg-base-200 p-4"
                >
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-semibold">{{ formatDate(entry.date) }}</div>
                            <div class="text-sm opacity-70">
                                {{ formatTime(entry.shift_start) }} - {{ formatTime(entry.shift_end) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-primary">{{ entry.total_hours }}h</span>
                            <button
                                class="btn btn-ghost btn-sm"
                                @click="handleEdit(entry)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button
                                class="btn btn-ghost btn-sm text-error"
                                @click="handleDelete(entry.id)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
