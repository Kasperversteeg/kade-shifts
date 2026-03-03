<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { Shift, ScheduleEmployee } from '@/types';

interface Props {
    show: boolean;
    shift?: Shift | null;
    employees: ScheduleEmployee[];
    defaultDate?: string;
    defaultUserId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    shift: null,
    defaultDate: undefined,
    defaultUserId: null,
});

const emit = defineEmits<{
    close: [];
}>();

const { t } = useI18n();
const dialogRef = ref<HTMLDialogElement | null>(null);

const isEditing = computed(() => !!props.shift);

const form = useForm({
    date: '',
    start_time: '09:00',
    end_time: '17:00',
    user_id: null as number | null | string,
    position: '',
    notes: '',
});

watch(() => props.show, (value) => {
    if (value) {
        if (props.shift) {
            form.date = props.shift.date;
            form.start_time = props.shift.start_time;
            form.end_time = props.shift.end_time;
            form.user_id = props.shift.user_id;
            form.position = props.shift.position ?? '';
            form.notes = props.shift.notes ?? '';
        } else {
            form.reset();
            form.date = props.defaultDate ?? '';
            form.start_time = '09:00';
            form.end_time = '17:00';
            form.user_id = props.defaultUserId;
            form.position = '';
            form.notes = '';
        }
        dialogRef.value?.showModal();
    } else {
        dialogRef.value?.close();
    }
});

const submit = (): void => {
    const data = {
        ...form.data(),
        user_id: form.user_id === '' || form.user_id === null ? null : Number(form.user_id),
    };

    if (isEditing.value && props.shift) {
        form.transform(() => data).patch(route('admin.shifts.update', props.shift!.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.transform(() => data).post(route('admin.shifts.store'), {
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <dialog ref="dialogRef" class="modal" @close="emit('close')">
        <div class="modal-box">
            <h3 class="text-lg font-bold mb-4">
                {{ isEditing ? t('schedule.editShift') : t('schedule.createShift') }}
            </h3>
            <form @submit.prevent="submit" class="space-y-3">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ t('schedule.date') }}</legend>
                    <input
                        type="date"
                        v-model="form.date"
                        class="input w-full"
                        :class="{ 'input-error': form.errors.date }"
                        required
                    />
                    <p v-if="form.errors.date" class="label text-error">{{ form.errors.date }}</p>
                </fieldset>

                <div class="grid grid-cols-2 gap-3">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('schedule.startTime') }}</legend>
                        <input
                            type="time"
                            v-model="form.start_time"
                            class="input w-full"
                            :class="{ 'input-error': form.errors.start_time }"
                            required
                        />
                        <p v-if="form.errors.start_time" class="label text-error">{{ form.errors.start_time }}</p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('schedule.endTime') }}</legend>
                        <input
                            type="time"
                            v-model="form.end_time"
                            class="input w-full"
                            :class="{ 'input-error': form.errors.end_time }"
                            required
                        />
                        <p v-if="form.errors.end_time" class="label text-error">{{ form.errors.end_time }}</p>
                    </fieldset>
                </div>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ t('schedule.employee') }}</legend>
                    <select
                        v-model="form.user_id"
                        class="select w-full"
                        :class="{ 'select-error': form.errors.user_id }"
                    >
                        <option :value="null">{{ t('schedule.unassigned') }}</option>
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                            {{ emp.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.user_id" class="label text-error">{{ form.errors.user_id }}</p>
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ t('schedule.position') }}</legend>
                    <input
                        type="text"
                        v-model="form.position"
                        class="input w-full"
                        :class="{ 'input-error': form.errors.position }"
                        maxlength="100"
                    />
                    <p v-if="form.errors.position" class="label text-error">{{ form.errors.position }}</p>
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ t('schedule.notes') }}</legend>
                    <textarea
                        v-model="form.notes"
                        class="textarea w-full"
                        :class="{ 'textarea-error': form.errors.notes }"
                        rows="2"
                        maxlength="500"
                    ></textarea>
                    <p v-if="form.errors.notes" class="label text-error">{{ form.errors.notes }}</p>
                </fieldset>

                <div class="modal-action">
                    <button type="button" class="btn" @click="emit('close')">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        {{ form.processing ? t('schedule.saving') : (isEditing ? t('schedule.update') : t('schedule.save')) }}
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="emit('close')">close</button>
        </form>
    </dialog>
</template>
