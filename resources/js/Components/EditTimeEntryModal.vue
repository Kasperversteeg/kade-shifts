<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, watch } from 'vue';

const { t } = useI18n();

const props = defineProps({
    entry: Object,
    show: Boolean,
});

const emit = defineEmits(['close']);

const dialog = ref(null);

const form = useForm({
    date: '',
    shift_start: '',
    shift_end: '',
    break_minutes: 0,
    notes: '',
});

watch(() => props.show, (val) => {
    if (val && props.entry) {
        form.date = props.entry.date;
        form.shift_start = props.entry.shift_start?.substring(0, 5);
        form.shift_end = props.entry.shift_end?.substring(0, 5);
        form.break_minutes = props.entry.break_minutes;
        form.notes = props.entry.notes || '';
        dialog.value?.showModal();
    } else {
        dialog.value?.close();
    }
});

const submit = () => {
    form.patch(route('time-entries.update', props.entry.id), {
        onSuccess: () => {
            emit('close');
        },
    });
};

const cancel = () => {
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
