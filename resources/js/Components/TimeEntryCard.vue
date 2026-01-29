<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';

const { t } = useI18n();

const props = defineProps({
    entry: Object,
    readonly: { type: Boolean, default: false },
});

const emit = defineEmits(['edit']);

const formattedDate = computed(() => {
    return dayjs(props.entry.date).format('ddd, MMM D, YYYY');
});

const deleteForm = useForm({});

const deleteEntry = () => {
    if (confirm(t('timeEntries.deleteConfirm'))) {
        deleteForm.delete(route('time-entries.destroy', props.entry.id));
    }
};
</script>

<template>
    <div class="card bg-base-100 border border-base-300">
        <div class="card-body p-4">
            <div class="flex gap-2 md:gap-0 flex-col md:flex-row justify-between items-start">
                <div>
                    <h3 class="font-semibold">{{ formattedDate }}</h3>
                    <p class="text-sm opacity-60">
                        {{ entry.shift_start }} - {{ entry.shift_end }}
                        <span v-if="entry.break_minutes > 0">
                            ({{ t('timeEntries.break', { minutes: entry.break_minutes }) }})
                        </span>
                    </p>
                    <p v-if="entry.notes" class="text-sm mt-1">{{ entry.notes }}</p>
                </div>
                <div class="flex justify-self-center items-center gap-2">
                    <div class="badge badge-primary badge-lg px-4">{{ entry.total_hours }}{{ t('summary.hoursUnit') }}</div>
                    <template v-if="!readonly">
                        <button @click="emit('edit', entry)" class="btn btn-ghost btn-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </button>
                        <button @click="deleteEntry" class="btn btn-ghost btn-xs text-error">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
