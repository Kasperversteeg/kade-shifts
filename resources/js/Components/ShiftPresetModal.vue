<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface ShiftPreset {
    id: number;
    name: string;
    short_name: string;
    start_time: string;
    end_time: string;
    color: string;
    sort_order: number;
    is_active: boolean;
}

interface Props {
    presets: ShiftPreset[];
    show: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useI18n();
const dialogRef = ref<HTMLDialogElement | null>(null);
const editingPreset = ref<ShiftPreset | null>(null);
const showForm = ref(false);

const isEditing = computed(() => !!editingPreset.value);

const form = useForm({
    name: '',
    short_name: '',
    start_time: '09:00',
    end_time: '17:00',
    color: '#4d5930',
    sort_order: 0,
    is_active: true,
});

const sortedPresets = computed(() => {
    return [...props.presets].sort((a, b) => a.sort_order - b.sort_order);
});

watch(() => props.show, (value) => {
    if (value) {
        resetForm();
        dialogRef.value?.showModal();
    } else {
        dialogRef.value?.close();
    }
});

const resetForm = () => {
    editingPreset.value = null;
    showForm.value = false;
    form.reset();
    form.clearErrors();
};

const openCreate = () => {
    editingPreset.value = null;
    form.reset();
    form.clearErrors();
    form.sort_order = props.presets.length;
    form.color = '#4d5930';
    form.start_time = '09:00';
    form.end_time = '17:00';
    form.is_active = true;
    showForm.value = true;
};

const openEdit = (preset: ShiftPreset) => {
    editingPreset.value = preset;
    form.name = preset.name;
    form.short_name = preset.short_name;
    form.start_time = preset.start_time;
    form.end_time = preset.end_time;
    form.color = preset.color;
    form.sort_order = preset.sort_order;
    form.is_active = preset.is_active;
    form.clearErrors();
    showForm.value = true;
};

const cancelForm = () => {
    resetForm();
};

const submit = () => {
    if (isEditing.value && editingPreset.value) {
        form.patch(route('admin.shift-presets.update', editingPreset.value.id), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('admin.shift-presets.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const deletePreset = (preset: ShiftPreset) => {
    if (!confirm(t('shiftPresets.deleteConfirm'))) return;
    router.delete(route('admin.shift-presets.destroy', preset.id));
};

const close = () => {
    resetForm();
    emit('close');
};
</script>

<template>
    <dialog ref="dialogRef" class="modal" @close="close">
        <div class="modal-box max-w-2xl">
            <h3 class="text-lg font-bold mb-4">{{ t('shiftPresets.title') }}</h3>

            <!-- Preset list -->
            <div v-if="sortedPresets.length" class="overflow-x-auto mb-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ t('shiftPresets.color') }}</th>
                            <th>{{ t('shiftPresets.name') }}</th>
                            <th>{{ t('shiftPresets.shortName') }}</th>
                            <th>{{ t('shiftPresets.times') }}</th>
                            <th class="text-right">{{ t('shiftPresets.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="preset in sortedPresets" :key="preset.id" class="hover">
                            <td>
                                <div
                                    class="w-6 h-6 rounded-full border border-base-300"
                                    :style="{ backgroundColor: preset.color }"
                                ></div>
                            </td>
                            <td>
                                <span>{{ preset.name }}</span>
                                <span v-if="!preset.is_active" class="badge badge-sm badge-ghost ml-2">
                                    {{ t('shiftPresets.inactive') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-sm" :style="{ backgroundColor: preset.color, color: '#fff' }">
                                    {{ preset.short_name }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">{{ preset.start_time }} - {{ preset.end_time }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <button class="btn btn-ghost btn-xs" @click="openEdit(preset)">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button class="btn btn-ghost btn-xs text-error" @click="deletePreset(preset)">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-8 opacity-60 mb-4">
                {{ t('shiftPresets.noPresets') }}
            </div>

            <!-- Add button (when form is hidden) -->
            <button v-if="!showForm" class="btn btn-primary btn-sm mb-4" @click="openCreate">
                + {{ t('shiftPresets.addPreset') }}
            </button>

            <!-- Add/Edit form -->
            <div v-if="showForm" class="border border-base-300 rounded-lg p-4 mb-4">
                <h4 class="font-semibold mb-3">
                    {{ isEditing ? t('shiftPresets.editPreset') : t('shiftPresets.addPreset') }}
                </h4>
                <form @submit.prevent="submit" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('shiftPresets.name') }}</legend>
                            <input
                                v-model="form.name"
                                type="text"
                                class="input w-full"
                                :class="{ 'input-error': form.errors.name }"
                                :placeholder="t('shiftPresets.namePlaceholder')"
                                required
                            />
                            <p v-if="form.errors.name" class="label text-error">{{ form.errors.name }}</p>
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('shiftPresets.shortName') }}</legend>
                            <input
                                v-model="form.short_name"
                                type="text"
                                class="input w-full"
                                :class="{ 'input-error': form.errors.short_name }"
                                :placeholder="t('shiftPresets.shortNamePlaceholder')"
                                maxlength="5"
                                required
                            />
                            <p v-if="form.errors.short_name" class="label text-error">{{ form.errors.short_name }}</p>
                        </fieldset>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('shiftPresets.startTime') }}</legend>
                            <input
                                v-model="form.start_time"
                                type="time"
                                class="input w-full"
                                :class="{ 'input-error': form.errors.start_time }"
                                required
                            />
                            <p v-if="form.errors.start_time" class="label text-error">{{ form.errors.start_time }}</p>
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('shiftPresets.endTime') }}</legend>
                            <input
                                v-model="form.end_time"
                                type="time"
                                class="input w-full"
                                :class="{ 'input-error': form.errors.end_time }"
                                required
                            />
                            <p v-if="form.errors.end_time" class="label text-error">{{ form.errors.end_time }}</p>
                        </fieldset>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('shiftPresets.color') }}</legend>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.color"
                                    type="color"
                                    class="w-10 h-10 rounded cursor-pointer border border-base-300"
                                />
                                <div
                                    class="w-8 h-8 rounded-full border border-base-300"
                                    :style="{ backgroundColor: form.color }"
                                ></div>
                            </div>
                            <p v-if="form.errors.color" class="label text-error">{{ form.errors.color }}</p>
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('shiftPresets.sortOrder') }}</legend>
                            <input
                                v-model.number="form.sort_order"
                                type="number"
                                class="input w-full"
                                :class="{ 'input-error': form.errors.sort_order }"
                                min="0"
                            />
                            <p v-if="form.errors.sort_order" class="label text-error">{{ form.errors.sort_order }}</p>
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('shiftPresets.active') }}</legend>
                            <label class="flex items-center gap-2 cursor-pointer mt-2">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="toggle toggle-primary"
                                />
                                <span class="text-sm">{{ form.is_active ? t('shiftPresets.active') : t('shiftPresets.inactive') }}</span>
                            </label>
                        </fieldset>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-sm" @click="cancelForm">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" :disabled="form.processing">
                            {{ form.processing ? t('common.saving') : (isEditing ? t('common.save') : t('shiftPresets.addPreset')) }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Modal close -->
            <div class="modal-action">
                <button type="button" class="btn" @click="close">
                    {{ t('common.close') }}
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="close">close</button>
        </form>
    </dialog>
</template>
