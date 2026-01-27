<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const props = defineProps({
    entry: Object,
});

const formattedDate = computed(() => {
    return dayjs(props.entry.date).format('ddd, MMM D, YYYY');
});

const deleteForm = useForm({});

const deleteEntry = () => {
    if (confirm('Are you sure you want to delete this entry?')) {
        deleteForm.delete(route('time-entries.destroy', props.entry.id));
    }
};
</script>

<template>
    <div class="card bg-base-100 border border-base-300">
        <div class="card-body p-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-semibold">{{ formattedDate }}</h3>
                    <p class="text-sm opacity-60">
                        {{ entry.shift_start }} - {{ entry.shift_end }}
                        <span v-if="entry.break_minutes > 0">
                            ({{ entry.break_minutes }}min break)
                        </span>
                    </p>
                    <p v-if="entry.notes" class="text-sm mt-1">{{ entry.notes }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="badge badge-primary badge-lg">{{ entry.total_hours }}h</div>
                    <button @click="deleteEntry" class="btn btn-ghost btn-xs text-error">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
