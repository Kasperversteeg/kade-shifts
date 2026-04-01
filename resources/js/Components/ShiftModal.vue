<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { Shift, ScheduleEmployee, ShiftPreset } from '@/types';

interface Props {
    show: boolean;
    shift?: Shift | null;
    employees: ScheduleEmployee[];
    shiftPresets?: ShiftPreset[];
    defaultDate?: string;
    defaultUserId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    shift: null,
    shiftPresets: () => [],
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
    shift_preset_id: null as number | null | string,
    position: '',
    notes: '',
});

const activePresets = computed(() => {
    return props.shiftPresets.filter(p => p.is_active).sort((a, b) => a.sort_order - b.sort_order);
});

const selectedPreset = computed(() => {
    if (!form.shift_preset_id) return null;
    return props.shiftPresets.find(p => p.id === Number(form.shift_preset_id)) ?? null;
});

const onPresetChange = (event: Event): void => {
    const value = (event.target as HTMLSelectElement).value;
    if (value === '' || value === 'custom') {
        form.shift_preset_id = null;
        return;
    }
    const presetId = Number(value);
    form.shift_preset_id = presetId;
    const preset = props.shiftPresets.find(p => p.id === presetId);
    if (preset) {
        form.start_time = preset.start_time;
        form.end_time = preset.end_time;
    }
};

watch(() => props.show, (value) => {
    if (value) {
        if (props.shift) {
            form.date = props.shift.date;
            form.start_time = props.shift.start_time;
            form.end_time = props.shift.end_time;
            form.user_id = props.shift.user_id;
            form.shift_preset_id = props.shift.shift_preset_id;
            form.position = props.shift.position ?? '';
            form.notes = props.shift.notes ?? '';
        } else {
            form.reset();
            form.date = props.defaultDate ?? '';
            form.start_time = '09:00';
            form.end_time = '17:00';
            form.user_id = props.defaultUserId;
            form.shift_preset_id = null;
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
        shift_preset_id: form.shift_preset_id === '' || form.shift_preset_id === null ? null : Number(form.shift_preset_id),
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

                <fieldset v-if="activePresets.length" class="fieldset">
                    <legend class="fieldset-legend">{{ t('shiftPresets.title') }}</legend>
                    <div class="flex items-center gap-2">
                        <div
                            v-if="selectedPreset"
                            class="w-4 h-4 rounded-full border border-base-300 shrink-0"
                            :style="{ backgroundColor: selectedPreset.color }"
                        ></div>
                        <select
                            :value="form.shift_preset_id ?? 'custom'"
                            class="select w-full"
                            @change="onPresetChange"
                        >
                            <option value="custom">{{ t('shiftPresets.custom') }}</option>
                            <option v-for="preset in activePresets" :key="preset.id" :value="preset.id">
                                {{ preset.name }} ({{ preset.start_time }} - {{ preset.end_time }})
                            </option>
                        </select>
                    </div>
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
