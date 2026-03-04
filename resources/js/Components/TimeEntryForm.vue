<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import type { AtwWarning } from '@/types';

const { t } = useI18n();

const emit = defineEmits<{
    cancel: [];
    success: [];
}>();

const form = useForm({
    date: dayjs().format('YYYY-MM-DD'),
    shift_start: '09:00',
    shift_end: '17:00',
    break_minutes: 30,
    notes: '',
});

const shiftDurationHours = computed<number>(() => {
    if (!form.shift_start || !form.shift_end) return 0;
    const [sh, sm] = form.shift_start.split(':').map(Number);
    const [eh, em] = form.shift_end.split(':').map(Number);
    let startMin = sh * 60 + sm;
    let endMin = eh * 60 + em;
    if (endMin <= startMin) endMin += 24 * 60; // cross-midnight
    return (endMin - startMin) / 60;
});

const atwWarnings = computed<AtwWarning[]>(() => {
    const warnings: AtwWarning[] = [];
    const hours = shiftDurationHours.value;
    const breakMin = form.break_minutes || 0;

    if (hours > 5.5 && breakMin < 30) {
        warnings.push({ type: 'break_short', message: 'atw.breakShort' });
    }
    if (hours > 10 && breakMin < 45) {
        warnings.push({ type: 'break_very_short', message: 'atw.breakVeryShort' });
    }
    if (hours > 12) {
        warnings.push({ type: 'shift_too_long', message: 'atw.shiftTooLong' });
    }

    return warnings;
});

const submit = (): void => {
    form.post(route('time-entries.store'), {
        onSuccess: () => {
            emit('success');
            form.reset();
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('timeEntries.date') }}</legend>
                <input
                    type="date"
                    v-model="form.date"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.date }"
                />
                <p v-if="form.errors.date" class="label text-error">{{ form.errors.date }}</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('timeEntries.shiftStart') }}</legend>
                <input
                    type="time"
                    v-model="form.shift_start"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.shift_start }"
                />
                <p v-if="form.errors.shift_start" class="label text-error">{{ form.errors.shift_start }}</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('timeEntries.shiftEnd') }}</legend>
                <input
                    type="time"
                    v-model="form.shift_end"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.shift_end }"
                />
                <p v-if="form.errors.shift_end" class="label text-error">{{ form.errors.shift_end }}</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('timeEntries.breakMinutes') }}</legend>
                <input
                    type="number"
                    v-model.number="form.break_minutes"
                    min="0"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.break_minutes }"
                />
                <p v-if="form.errors.break_minutes" class="label text-error">{{ form.errors.break_minutes }}</p>
            </fieldset>
        </div>

        <!-- ATW Warnings -->
        <div v-if="atwWarnings.length > 0" class="alert alert-warning">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div>
                <p v-for="(warning, idx) in atwWarnings" :key="idx" class="text-sm">
                    {{ t(warning.message) }}
                </p>
            </div>
        </div>

        <fieldset class="fieldset">
            <legend class="fieldset-legend">{{ t('timeEntries.notes') }}</legend>
            <textarea
                v-model="form.notes"
                class="textarea w-full"
                :class="{ 'textarea-error': form.errors.notes }"
                rows="2"
            ></textarea>
        </fieldset>

        <div class="flex gap-2 justify-end">
            <button type="button" @click="emit('cancel')" class="btn btn-ghost">
                {{ t('common.cancel') }}
            </button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                {{ form.processing ? t('timeEntries.saving') : t('timeEntries.save') }}
            </button>
        </div>
    </form>
</template>
