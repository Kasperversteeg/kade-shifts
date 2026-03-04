<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WeekNavigator from '@/Components/WeekNavigator.vue';
import dayjs from 'dayjs';
import type { Shift } from '@/types';

interface Props {
    shifts: Shift[];
    days: string[];
    currentWeek: string;
    weekTotal: number;
}

const props = defineProps<Props>();
const { t } = useI18n();

const shiftsForDay = (day: string): Shift[] => {
    return props.shifts.filter((s) => s.date === day);
};

const formatDay = (day: string): string => {
    return dayjs(day).format('dddd, D MMMM');
};

const isToday = (day: string): boolean => {
    return dayjs(day).format('YYYY-MM-DD') === dayjs().format('YYYY-MM-DD');
};

const prefillTimeEntry = (shift: Shift): void => {
    router.post(route('time-entries.store'), {
        date: shift.date,
        shift_start: shift.start_time,
        shift_end: shift.end_time,
        break_minutes: 0,
        notes: shift.position ? `${shift.position}` : '',
    });
};
</script>

<template>
    <Head :title="t('schedule.mySchedule')" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <WeekNavigator :currentWeek="currentWeek" />
                </div>
            </div>

            <!-- Week total -->
            <div class="stats shadow w-full">
                <div class="stat">
                    <div class="stat-title">{{ t('schedule.weekTotal') }}</div>
                    <div class="stat-value text-primary">{{ weekTotal }}{{ t('summary.hoursUnit') }}</div>
                    <div class="stat-desc">{{ t('schedule.plannedHours') }}</div>
                </div>
            </div>

            <!-- Day cards -->
            <div v-for="day in days" :key="day">
                <div
                    v-if="shiftsForDay(day).length > 0"
                    class="card bg-base-100 shadow-xl"
                    :class="{ 'ring-2 ring-primary': isToday(day) }"
                >
                    <div class="card-body">
                        <h3 class="card-title text-base">
                            {{ formatDay(day) }}
                            <span v-if="isToday(day)" class="badge badge-primary badge-sm">{{ t('month.today') }}</span>
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="shift in shiftsForDay(day)"
                                :key="shift.id"
                                class="flex items-center justify-between p-3 bg-base-200 rounded-lg"
                            >
                                <div>
                                    <span class="font-semibold">{{ shift.start_time }} — {{ shift.end_time }}</span>
                                    <span class="ml-2 text-sm opacity-70">({{ shift.planned_hours }}{{ t('summary.hoursUnit') }})</span>
                                    <p v-if="shift.position" class="text-sm opacity-70">{{ shift.position }}</p>
                                </div>
                                <button
                                    class="btn btn-primary btn-sm"
                                    @click="prefillTimeEntry(shift)"
                                >
                                    {{ t('schedule.prefillTimeEntry') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="shifts.length === 0" class="card bg-base-100 shadow-xl">
                <div class="card-body text-center opacity-60 py-12">
                    {{ t('schedule.noShiftsForYou') }}
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
