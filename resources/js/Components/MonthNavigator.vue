<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed } from 'vue';

interface Props {
    currentMonth: string;
}

const props = defineProps<Props>();

const currentDate = computed(() => dayjs(props.currentMonth + '-01'));
const displayMonth = computed<string>(() => currentDate.value.format('MMMM YYYY'));

const isCurrentMonth = computed<boolean>(() => {
    return currentDate.value.format('YYYY-MM') === dayjs().format('YYYY-MM');
});

const goToPreviousMonth = (): void => {
    const prevMonth = currentDate.value.subtract(1, 'month').format('YYYY-MM');
    router.get(window.location.pathname, { month: prevMonth }, { preserveState: true });
};

const goToNextMonth = (): void => {
    const nextMonth = currentDate.value.add(1, 'month').format('YYYY-MM');
    router.get(window.location.pathname, { month: nextMonth }, { preserveState: true });
};

const goToCurrentMonth = (): void => {
    const month = dayjs().format('YYYY-MM');
    router.get(window.location.pathname, { month }, { preserveState: true });
};
</script>

<template>
    <div class="flex items-center justify-between">
        <button @click="goToPreviousMonth" class="btn btn-circle btn-sm btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </button>
        <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold">{{ displayMonth }}</h2>
            <button
                v-if="!isCurrentMonth"
                @click="goToCurrentMonth"
                class="btn btn-xs btn-outline"
            >
                {{ $t('month.today') }}
            </button>
        </div>
        <button @click="goToNextMonth" class="btn btn-circle btn-sm btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    </div>
</template>
