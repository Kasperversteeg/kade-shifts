<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed, onMounted, nextTick } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import dayjs from 'dayjs';
import type { CalendarEvent, MonthTotals } from '@/types';

interface Props {
    events: CalendarEvent[];
    currentMonth: string;
    totals: MonthTotals;
}

const props = defineProps<Props>();
const { t } = useI18n();

// ── Calendar grid helpers ──────────────────────────────────────────

const monthStart = computed(() => dayjs(props.currentMonth + '-01'));
const monthEnd = computed(() => monthStart.value.endOf('month'));

const dayHeaders = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];

interface CalendarDay {
    date: string;
    dayNumber: number;
    isCurrentMonth: boolean;
    isToday: boolean;
    isPast: boolean;
    isWeekend: boolean;
    events: CalendarEvent[];
}

const calendarDays = computed<CalendarDay[]>(() => {
    const days: CalendarDay[] = [];
    const today = dayjs().format('YYYY-MM-DD');

    // Monday = 1 in ISO, dayjs().day() returns 0=Sun..6=Sat
    const firstDayOfMonth = monthStart.value;
    const jsDay = firstDayOfMonth.day(); // 0=Sun
    const isoDay = jsDay === 0 ? 7 : jsDay; // 1=Mon..7=Sun
    const paddingBefore = isoDay - 1;

    // Fill padding days from previous month
    for (let i = paddingBefore; i > 0; i--) {
        const d = firstDayOfMonth.subtract(i, 'day');
        const dateStr = d.format('YYYY-MM-DD');
        const dow = d.day();
        days.push({
            date: dateStr,
            dayNumber: d.date(),
            isCurrentMonth: false,
            isToday: dateStr === today,
            isPast: dateStr < today,
            isWeekend: dow === 0 || dow === 6,
            events: eventsForDate(dateStr),
        });
    }

    // Current month days
    const daysInMonth = monthStart.value.daysInMonth();
    for (let d = 1; d <= daysInMonth; d++) {
        const date = monthStart.value.date(d);
        const dateStr = date.format('YYYY-MM-DD');
        const dow = date.day();
        days.push({
            date: dateStr,
            dayNumber: d,
            isCurrentMonth: true,
            isToday: dateStr === today,
            isPast: dateStr < today,
            isWeekend: dow === 0 || dow === 6,
            events: eventsForDate(dateStr),
        });
    }

    // Padding after to complete the last week row
    const remainder = days.length % 7;
    if (remainder > 0) {
        const padAfter = 7 - remainder;
        for (let i = 1; i <= padAfter; i++) {
            const date = monthEnd.value.add(i, 'day');
            const dateStr = date.format('YYYY-MM-DD');
            const dow = date.day();
            days.push({
                date: dateStr,
                dayNumber: date.date(),
                isCurrentMonth: false,
                isToday: dateStr === today,
                isPast: dateStr < today,
                isWeekend: dow === 0 || dow === 6,
                events: eventsForDate(dateStr),
            });
        }
    }

    return days;
});

// ── Event helpers ──────────────────────────────────────────────────

function eventsForDate(date: string): CalendarEvent[] {
    return props.events.filter((e) => e.date === date);
}

/** Days that have events, sorted, for the mobile agenda list */
const agendaDays = computed(() => {
    const dayMap = new Map<string, CalendarEvent[]>();
    for (const ev of props.events) {
        if (!dayMap.has(ev.date)) dayMap.set(ev.date, []);
        dayMap.get(ev.date)!.push(ev);
    }
    return Array.from(dayMap.entries())
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([date, events]) => ({ date, events }));
});

/** All days in month for mini-calendar strip */
const miniCalendarDays = computed(() => {
    const days: { date: string; dayNumber: number; isToday: boolean; hasEvents: boolean; eventColors: string[] }[] = [];
    const today = dayjs().format('YYYY-MM-DD');
    const daysInMonth = monthStart.value.daysInMonth();
    for (let d = 1; d <= daysInMonth; d++) {
        const date = monthStart.value.date(d);
        const dateStr = date.format('YYYY-MM-DD');
        const dayEvents = eventsForDate(dateStr);
        const colors = [...new Set(dayEvents.map((e) => e.color))];
        days.push({
            date: dateStr,
            dayNumber: d,
            isToday: dateStr === today,
            hasEvents: dayEvents.length > 0,
            eventColors: colors,
        });
    }
    return days;
});

function eventPillLabel(event: CalendarEvent): string {
    switch (event.type) {
        case 'shift':
            return event.start_time && event.end_time
                ? `${event.start_time}-${event.end_time}`
                : event.label;
        case 'time_entry':
            return event.hours != null ? `${event.hours}${t('summary.hoursUnit')}` : event.label;
        case 'leave':
        case 'sick':
            return event.label;
        default:
            return event.label;
    }
}

function eventTypeIcon(type: string): string {
    switch (type) {
        case 'shift': return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'time_entry': return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'leave': return 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z';
        case 'sick': return 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z';
        default: return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
}

function eventTypeLabel(type: string): string {
    return t(`schedule.eventTypes.${type}`);
}

function formatAgendaDayHeader(date: string): string {
    return dayjs(date).format('dd D MMMM');
}

function statusBadgeClass(status: string | null): string {
    switch (status) {
        case 'approved': return 'badge-success';
        case 'submitted':
        case 'pending': return 'badge-info';
        case 'rejected': return 'badge-error';
        case 'draft': return 'badge-ghost';
        default: return '';
    }
}

// ── Actions ────────────────────────────────────────────────────────

function prefillTimeEntry(event: CalendarEvent): void {
    router.post(route('time-entries.store'), {
        date: event.date,
        shift_start: event.start_time ?? '',
        shift_end: event.end_time ?? '',
        break_minutes: 0,
        notes: event.position ? `${event.position}` : '',
    });
}

function scrollToMiniDay(date: string): void {
    const el = document.getElementById(`agenda-day-${date}`);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── Auto-scroll to today on mount (mobile) ─────────────────────────

onMounted(() => {
    nextTick(() => {
        const today = dayjs().format('YYYY-MM-DD');
        const el = document.getElementById(`agenda-day-${today}`);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Scroll mini-calendar to today
        const miniEl = document.getElementById(`mini-day-${today}`);
        if (miniEl) miniEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    });
});
</script>

<template>
    <Head :title="t('schedule.monthOverview')" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <!-- Month Navigator -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <MonthNavigator :currentMonth="currentMonth" />
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- DESKTOP: Stats + Month Calendar Grid (md: and up)     -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="hidden md:block space-y-4">
                <!-- Stats bar -->
                <div class="stats stats-horizontal shadow w-full">
                    <div class="stat">
                        <div class="stat-figure text-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-title">{{ t('schedule.plannedHours') }}</div>
                        <div class="stat-value text-info text-2xl">{{ totals.planned_hours }}{{ t('summary.hoursUnit') }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-figure text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-title">{{ t('schedule.workedHours') }}</div>
                        <div class="stat-value text-success text-2xl">{{ totals.worked_hours }}{{ t('summary.hoursUnit') }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-figure text-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="stat-title">{{ t('schedule.leaveDays') }}</div>
                        <div class="stat-value text-warning text-2xl">{{ totals.leave_days }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-figure text-error">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <div class="stat-title">{{ t('schedule.sickDays') }}</div>
                        <div class="stat-value text-error text-2xl">{{ totals.sick_days }}</div>
                    </div>
                </div>

                <!-- Month calendar grid -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body p-3">
                        <!-- Day headers -->
                        <div class="grid grid-cols-7 gap-px mb-1">
                            <div
                                v-for="header in dayHeaders"
                                :key="header"
                                class="text-center text-xs font-semibold uppercase opacity-60 py-1"
                            >
                                {{ header }}
                            </div>
                        </div>

                        <!-- Day cells -->
                        <div class="grid grid-cols-7 gap-px">
                            <div
                                v-for="day in calendarDays"
                                :key="day.date"
                                class="min-h-[5.5rem] p-1 rounded-lg border border-base-300 flex flex-col"
                                :class="{
                                    'ring-2 ring-primary': day.isToday,
                                    'bg-base-200': day.isWeekend && day.isCurrentMonth,
                                    'bg-base-100': !day.isWeekend && day.isCurrentMonth,
                                    'opacity-40 bg-base-200/50': !day.isCurrentMonth,
                                    'opacity-60': day.isPast && day.isCurrentMonth && !day.isToday,
                                }"
                            >
                                <!-- Day number -->
                                <div class="flex items-center justify-between mb-0.5">
                                    <span
                                        class="text-xs font-semibold leading-none"
                                        :class="{ 'text-primary font-bold': day.isToday }"
                                    >
                                        {{ day.dayNumber }}
                                    </span>
                                </div>

                                <!-- Event pills -->
                                <div class="flex flex-col gap-0.5 overflow-hidden flex-1">
                                    <button
                                        v-for="event in day.events.slice(0, 3)"
                                        :key="`${event.source_type}-${event.source_id}`"
                                        class="text-left text-[10px] leading-tight truncate rounded px-1 py-0.5 cursor-pointer hover:opacity-80 transition-opacity"
                                        :style="{ backgroundColor: event.color + '22', borderLeft: `3px solid ${event.color}` }"
                                        @click="event.type === 'shift' ? prefillTimeEntry(event) : undefined"
                                    >
                                        {{ eventPillLabel(event) }}
                                    </button>
                                    <span
                                        v-if="day.events.length > 3"
                                        class="text-[9px] opacity-50 pl-1"
                                    >
                                        +{{ day.events.length - 3 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="events.length === 0" class="card bg-base-100 shadow-xl">
                    <div class="card-body text-center opacity-60 py-12">
                        {{ t('schedule.noEventsThisMonth') }}
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- MOBILE: Mini-calendar + Stats + Agenda list (below md) -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="md:hidden space-y-3">
                <!-- Mini-calendar strip -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body p-3">
                        <div class="flex overflow-x-auto gap-1 pb-1 -mx-1 px-1 scrollbar-none">
                            <button
                                v-for="day in miniCalendarDays"
                                :key="day.date"
                                :id="`mini-day-${day.date}`"
                                class="flex flex-col items-center min-w-[2rem] py-1 px-0.5 rounded-lg transition-colors"
                                :class="{
                                    'bg-primary text-primary-content': day.isToday,
                                    'hover:bg-base-200': !day.isToday,
                                }"
                                @click="scrollToMiniDay(day.date)"
                            >
                                <span class="text-[10px] font-medium">{{ day.dayNumber }}</span>
                                <div class="flex gap-0.5 mt-0.5 h-1.5">
                                    <span
                                        v-for="(color, idx) in day.eventColors.slice(0, 3)"
                                        :key="idx"
                                        class="w-1.5 h-1.5 rounded-full"
                                        :style="{ backgroundColor: day.isToday ? 'currentColor' : color }"
                                    ></span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Compact stats row -->
                <div class="grid grid-cols-4 gap-2">
                    <div class="bg-base-100 rounded-lg p-2 text-center shadow-sm">
                        <div class="text-lg font-bold text-info">{{ totals.planned_hours }}{{ t('summary.hoursUnit') }}</div>
                        <div class="text-[10px] opacity-60">{{ t('schedule.plannedHours') }}</div>
                    </div>
                    <div class="bg-base-100 rounded-lg p-2 text-center shadow-sm">
                        <div class="text-lg font-bold text-success">{{ totals.worked_hours }}{{ t('summary.hoursUnit') }}</div>
                        <div class="text-[10px] opacity-60">{{ t('schedule.workedHours') }}</div>
                    </div>
                    <div class="bg-base-100 rounded-lg p-2 text-center shadow-sm">
                        <div class="text-lg font-bold text-warning">{{ totals.leave_days }}</div>
                        <div class="text-[10px] opacity-60">{{ t('schedule.leaveDays') }}</div>
                    </div>
                    <div class="bg-base-100 rounded-lg p-2 text-center shadow-sm">
                        <div class="text-lg font-bold text-error">{{ totals.sick_days }}</div>
                        <div class="text-[10px] opacity-60">{{ t('schedule.sickDays') }}</div>
                    </div>
                </div>

                <!-- Agenda list -->
                <div v-if="agendaDays.length > 0" class="space-y-1">
                    <div v-for="agendaDay in agendaDays" :key="agendaDay.date" :id="`agenda-day-${agendaDay.date}`">
                        <!-- Day header (sticky) -->
                        <div
                            class="sticky top-0 z-10 bg-base-200/90 backdrop-blur-sm px-3 py-1.5 -mx-1 rounded-lg"
                        >
                            <span class="text-sm font-semibold capitalize">{{ formatAgendaDayHeader(agendaDay.date) }}</span>
                            <span
                                v-if="agendaDay.date === dayjs().format('YYYY-MM-DD')"
                                class="badge badge-primary badge-xs ml-2"
                            >
                                {{ t('month.today') }}
                            </span>
                        </div>

                        <!-- Event cards for day -->
                        <div class="space-y-2 mt-2 mb-4">
                            <div
                                v-for="event in agendaDay.events"
                                :key="`${event.source_type}-${event.source_id}`"
                                class="card bg-base-100 shadow-sm"
                                :style="{ borderLeft: `4px solid ${event.color}` }"
                            >
                                <div class="card-body p-3 gap-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <!-- Type icon -->
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-60">
                                                <path stroke-linecap="round" stroke-linejoin="round" :d="eventTypeIcon(event.type)" />
                                            </svg>
                                            <span class="text-xs font-medium opacity-60">{{ eventTypeLabel(event.type) }}</span>
                                        </div>
                                        <!-- Status badge -->
                                        <span
                                            v-if="event.status"
                                            class="badge badge-xs"
                                            :class="statusBadgeClass(event.status)"
                                        >
                                            {{ t(`status.${event.status}`, event.status) }}
                                        </span>
                                    </div>

                                    <!-- Event details -->
                                    <div class="font-semibold text-sm">
                                        {{ event.label }}
                                    </div>
                                    <div v-if="event.type === 'shift' && event.start_time && event.end_time" class="text-sm opacity-70">
                                        {{ event.start_time }} - {{ event.end_time }}
                                        <span v-if="event.hours"> ({{ event.hours }}{{ t('summary.hoursUnit') }})</span>
                                    </div>
                                    <div v-else-if="event.type === 'time_entry' && event.hours != null" class="text-sm opacity-70">
                                        {{ event.hours }}{{ t('summary.hoursUnit') }} {{ t('schedule.workedHours').toLowerCase() }}
                                    </div>
                                    <div v-if="event.detail" class="text-xs opacity-50">
                                        {{ event.detail }}
                                    </div>

                                    <!-- Log hours button for shifts -->
                                    <button
                                        v-if="event.type === 'shift'"
                                        class="btn btn-primary btn-sm btn-outline mt-1 self-start"
                                        @click="prefillTimeEntry(event)"
                                    >
                                        {{ t('schedule.logHours') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state (mobile) -->
                <div v-else class="card bg-base-100 shadow-xl">
                    <div class="card-body text-center opacity-60 py-12">
                        {{ t('schedule.noEventsThisMonth') }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
