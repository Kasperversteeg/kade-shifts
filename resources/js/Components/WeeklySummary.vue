<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import type { WeeklyTotal } from '@/types';

interface Props {
    weeklyTotals: WeeklyTotal[];
}

const props = defineProps<Props>();
const { t } = useI18n();

const getBadgeClass = (hours: number): string => {
    if (hours > 60) return 'badge-error';
    if (hours > 48) return 'badge-warning';
    return 'badge-success';
};

const formatWeekRange = (weekStart: string, weekEnd: string): string => {
    return `${dayjs(weekStart).format('D MMM')} - ${dayjs(weekEnd).format('D MMM')}`;
};

const hasAnyWarnings = computed<boolean>(() => {
    return props.weeklyTotals.some(w => w.warnings.length > 0);
});
</script>

<template>
    <div v-if="weeklyTotals.length > 0" class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h3 class="font-semibold text-sm mb-2">{{ t('atw.weeklyTotals') }}</h3>
            <div class="flex flex-wrap gap-3">
                <div
                    v-for="week in weeklyTotals"
                    :key="week.week"
                    class="flex items-center gap-2 text-sm"
                >
                    <span class="opacity-60">{{ t('atw.week') }} {{ week.week }}</span>
                    <span class="text-xs opacity-40">{{ formatWeekRange(week.weekStart, week.weekEnd) }}</span>
                    <span class="badge badge-sm" :class="getBadgeClass(week.totalHours)">
                        {{ week.totalHours }}{{ t('summary.hoursUnit') }}
                    </span>
                    <span v-if="week.warnings.length > 0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-warning">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </span>
                </div>
            </div>
            <div v-if="hasAnyWarnings" class="mt-2">
                <div v-for="week in weeklyTotals.filter(w => w.warnings.length > 0)" :key="`warn-${week.week}`">
                    <p v-for="(warning, idx) in week.warnings" :key="idx" class="text-xs text-warning">
                        {{ t('atw.week') }} {{ week.week }}: {{ t(warning.message) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
