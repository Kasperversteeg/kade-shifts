<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import type { Shift } from '@/types';

interface Props {
    shift: Shift;
    editable?: boolean;
}

withDefaults(defineProps<Props>(), {
    editable: false,
});

const emit = defineEmits<{
    edit: [shift: Shift];
    delete: [shift: Shift];
}>();

const { t } = useI18n();
</script>

<template>
    <div
        class="card card-compact bg-base-100 shadow-sm border border-base-300 cursor-grab active:cursor-grabbing"
        :class="{ 'opacity-60': !shift.published && !editable }"
    >
        <div class="card-body p-2 gap-1">
            <div class="flex items-center justify-between">
                <span class="font-semibold text-sm">{{ shift.start_time }} — {{ shift.end_time }}</span>
                <span
                    v-if="editable"
                    class="badge badge-xs"
                    :class="shift.published ? 'badge-success' : 'badge-warning'"
                >
                    {{ shift.published ? t('schedule.published') : t('schedule.unpublished') }}
                </span>
            </div>
            <p v-if="shift.position" class="text-xs opacity-70">{{ shift.position }}</p>
            <p v-if="shift.user_name && !editable" class="text-xs font-medium">{{ shift.user_name }}</p>
            <p v-if="!shift.user_id" class="text-xs italic opacity-50">{{ t('schedule.openShift') }}</p>
            <div v-if="editable" class="flex gap-1 mt-1">
                <button class="btn btn-ghost btn-xs" @click.stop="emit('edit', shift)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    </svg>
                </button>
                <button class="btn btn-ghost btn-xs text-error" @click.stop="emit('delete', shift)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
