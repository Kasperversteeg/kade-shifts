<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';

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
