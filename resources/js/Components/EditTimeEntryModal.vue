<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, watch } from 'vue';
import type { TimeEntry } from '@/types';

const { t } = useI18n();

interface Props {
    entry: TimeEntry | null;
    show: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const dialog = ref<HTMLDialogElement | null>(null);

const form = useForm({
    date: '',
    shift_start: '',
    shift_end: '',
    break_minutes: 0,
    notes: '',
});

watch(() => props.show, (val: boolean) => {
    if (val && props.entry) {
        form.date = props.entry.date?.substring(0, 10);
        form.shift_start = props.entry.shift_start?.substring(0, 5);
        form.shift_end = props.entry.shift_end?.substring(0, 5);
        form.break_minutes = props.entry.break_minutes;
        form.notes = props.entry.notes || '';
        dialog.value?.showModal();
    } else {
        dialog.value?.close();
    }
});

const submit = (): void => {
    if (!props.entry) return;
    form.patch(route('time-entries.update', props.entry.id), {
        onSuccess: () => {
            emit('close');
        },
    });
};

const cancel = (): void => {
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <dialog ref="dialog" class="modal" @close="cancel">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">{{ t('timeEntries.edit') }}</h3>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('timeEntries.date') }}</legend>
                        <input type="date" v-model="form.date" class="input w-full"
                            :class="{ 'input-error': form.errors.date }" />
                        <p v-if="form.errors.date" class="label text-error">{{ form.errors.date }}</p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('timeEntries.shiftStart') }}</legend>
                        <input type="time" step="900" v-model="form.shift_start" class="input w-full"
                            :class="{ 'input-error': form.errors.shift_start }" />
                        <p v-if="form.errors.shift_start" class="label text-error">{{ form.errors.shift_start }}</p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('timeEntries.shiftEnd') }}</legend>
                        <input type="time" v-model="form.shift_end" class="input w-full"
                            :class="{ 'input-error': form.errors.shift_end }" />
                        <p v-if="form.errors.shift_end" class="label text-error">{{ form.errors.shift_end }}</p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('timeEntries.breakMinutes') }}</legend>
                        <input type="number" v-model.number="form.break_minutes" min="0" class="input w-full"
                            :class="{ 'input-error': form.errors.break_minutes }" />
                        <p v-if="form.errors.break_minutes" class="label text-error">{{ form.errors.break_minutes }}</p>
                    </fieldset>

                    <fieldset class="fieldset md:col-span-2">
                        <legend class="fieldset-legend">{{ t('timeEntries.notes') }}</legend>
                        <textarea v-model="form.notes" class="textarea w-full"
                            :class="{ 'textarea-error': form.errors.notes }" rows="2"></textarea>
                    </fieldset>
                </div>
                <div class="modal-action">
                    <button type="button" @click="cancel" class="btn btn-ghost">{{ t('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        {{ form.processing ? t('timeEntries.saving') : t('timeEntries.update') }}
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="cancel">close</button>
        </form>
    </dialog>
</template>
