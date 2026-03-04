<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed } from 'vue';

interface Props {
    currentWeek: string;
}

const props = defineProps<Props>();

const weekStart = computed(() => dayjs(props.currentWeek));
const weekEnd = computed(() => weekStart.value.add(6, 'day'));

const weekNumber = computed<number>(() => {
    // ISO week number calculation
    const date = weekStart.value.toDate();
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil(((d.getTime() - yearStart.getTime()) / 86400000 + 1) / 7);
});

const displayWeek = computed<string>(() => {
    return `Wk ${weekNumber.value}: ${weekStart.value.format('D MMM')} — ${weekEnd.value.format('D MMM YYYY')}`;
});

const isCurrentWeek = computed<boolean>(() => {
    const today = dayjs();
    return today.isAfter(weekStart.value.subtract(1, 'day')) && today.isBefore(weekEnd.value.add(1, 'day'));
});

const goToPreviousWeek = (): void => {
    const prev = weekStart.value.subtract(7, 'day').format('YYYY-MM-DD');
    router.get(window.location.pathname, { week: prev }, { preserveState: true });
};

const goToNextWeek = (): void => {
    const next = weekStart.value.add(7, 'day').format('YYYY-MM-DD');
    router.get(window.location.pathname, { week: next }, { preserveState: true });
};

const goToCurrentWeek = (): void => {
    // Find Monday of current week
    const today = dayjs();
    const day = today.day();
    const monday = today.subtract(day === 0 ? 6 : day - 1, 'day');
    router.get(window.location.pathname, { week: monday.format('YYYY-MM-DD') }, { preserveState: true });
};
</script>

<template>
    <div class="flex items-center justify-between">
        <button @click="goToPreviousWeek" class="btn btn-circle btn-sm btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </button>
        <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold">{{ displayWeek }}</h2>
            <button
                v-if="!isCurrentWeek"
                @click="goToCurrentWeek"
                class="btn btn-xs btn-outline"
            >
                {{ $t('month.today') }}
            </button>
        </div>
        <button @click="goToNextWeek" class="btn btn-circle btn-sm btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    </div>
</template>
