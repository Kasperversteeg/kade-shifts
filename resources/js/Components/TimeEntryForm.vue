<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';

const { t } = useI18n();
const emit = defineEmits(['cancel', 'success']);

const form = useForm({
    date: dayjs().format('YYYY-MM-DD'),
    shift_start: '09:00',
    shift_end: '17:00',
    break_minutes: 30,
    notes: '',
});

const submit = () => {
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

            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ t('timeEntries.shiftEnd') }}</span>
                </label>
                <input
                    type="time"
                    v-model="form.shift_end"
                    class="input input-bordered"
                    :class="{ 'input-error': form.errors.shift_end }"
                />
                <label v-if="form.errors.shift_end" class="label">
                    <span class="label-text-alt text-error">{{ form.errors.shift_end }}</span>
                </label>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ t('timeEntries.breakMinutes') }}</span>
                </label>
                <input
                    type="number"
                    v-model.number="form.break_minutes"
                    min="0"
                    class="input input-bordered"
                    :class="{ 'input-error': form.errors.break_minutes }"
                />
                <label v-if="form.errors.break_minutes" class="label">
                    <span class="label-text-alt text-error">{{ form.errors.break_minutes }}</span>
                </label>
            </div>
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ t('timeEntries.notes') }}</span>
            </label>
            <textarea
                v-model="form.notes"
                class="textarea textarea-bordered"
                :class="{ 'textarea-error': form.errors.notes }"
                rows="2"
            ></textarea>
        </div>

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
