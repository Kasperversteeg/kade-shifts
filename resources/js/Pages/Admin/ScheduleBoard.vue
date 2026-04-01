<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { VueDraggable } from 'vue-draggable-plus';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import WeekNavigator from '@/Components/WeekNavigator.vue';
import ShiftCard from '@/Components/ShiftCard.vue';
import ShiftModal from '@/Components/ShiftModal.vue';
import ShiftPresetModal from '@/Components/ShiftPresetModal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import dayjs from 'dayjs';
import type { Shift, ScheduleEmployee, ShiftPreset } from '@/types';

interface TeamGroup {
    id: number;
    name: string;
    members: { id: number; name: string }[];
}

interface Props {
    shifts: Shift[];
    employees: ScheduleEmployee[];
    shiftPresets: ShiftPreset[];
    days: string[];
    currentWeek: string;
    hasUnpublished: boolean;
    teams?: TeamGroup[];
    ungroupedEmployees?: { id: number; name: string }[];
}

const props = defineProps<Props>();
const { t } = useI18n();

// Modal state
const showShiftModal = ref(false);
const editingShift = ref<Shift | null>(null);
const defaultDate = ref<string>('');
const defaultUserId = ref<number | null>(null);

// Delete confirm state
const showDeleteConfirm = ref(false);
const deletingShift = ref<Shift | null>(null);

// Preset modal state
const showPresetModal = ref(false);

// Publishing state
const publishing = ref(false);

// Collapsed team state
const STORAGE_KEY = 'schedule-collapsed-teams';
const collapsedTeams = ref<Set<number | string>>(new Set());

onMounted(() => {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored) as (number | string)[];
            collapsedTeams.value = new Set(parsed);
        }
    } catch {
        // ignore invalid localStorage data
    }
});

const persistCollapsed = (): void => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify([...collapsedTeams.value]));
};

const toggleTeam = (teamId: number | string): void => {
    if (collapsedTeams.value.has(teamId)) {
        collapsedTeams.value.delete(teamId);
    } else {
        collapsedTeams.value.add(teamId);
    }
    persistCollapsed();
};

const isCollapsed = (teamId: number | string): boolean => {
    return collapsedTeams.value.has(teamId);
};

const hasTeamGrouping = computed(() => {
    return props.teams && props.teams.length > 0;
});

// Build cell data for shift lookup — reactive object so VueDraggable v-model works
const cellShifts = reactive<Record<string, Shift[]>>({});

const buildCellShifts = (): void => {
    // Clear existing keys
    for (const key in cellShifts) {
        delete cellShifts[key];
    }

    for (const day of props.days) {
        cellShifts[`unassigned-${day}`] = props.shifts.filter((s) => s.user_id === null && s.date === day);
    }

    for (const emp of props.employees) {
        for (const day of props.days) {
            cellShifts[`${emp.id}-${day}`] = props.shifts.filter((s) => s.user_id == emp.id && s.date === day);
        }
    }

    // Cover any user_id in shifts not in the employees list
    for (const shift of props.shifts) {
        if (shift.user_id !== null) {
            for (const day of props.days) {
                const key = `${shift.user_id}-${day}`;
                if (!(key in cellShifts)) {
                    cellShifts[key] = props.shifts.filter((s) => s.user_id == shift.user_id && s.date === day);
                }
            }
        }
    }
};

// Rebuild when props change
watch(() => [props.shifts, props.days, props.employees], buildCellShifts, { immediate: true });

const getCellKey = (rowId: number | null, day: string): string => {
    return `${rowId ?? 'unassigned'}-${day}`;
};

const getShiftsForCell = (rowId: number | null, day: string): Shift[] => {
    return cellShifts[getCellKey(rowId, day)] ?? [];
};

const today = dayjs().format('YYYY-MM-DD');

const isToday = (day: string): boolean => day === today;

const formatDayShort = (day: string): string => {
    return dayjs(day).format('dd');
};

const formatDayNum = (day: string): string => {
    return dayjs(day).format('D MMM');
};

const openCreateModal = (day: string, userId: number | null): void => {
    editingShift.value = null;
    defaultDate.value = day;
    defaultUserId.value = userId;
    showShiftModal.value = true;
};

const onCellClick = (event: MouseEvent, day: string, userId: number | null): void => {
    // Only open create modal if clicking the empty area, not a shift card
    const target = event.target as HTMLElement;
    if (target.closest('[data-shift-id]')) return;
    openCreateModal(day, userId);
};

const openEditModal = (shift: Shift): void => {
    editingShift.value = shift;
    showShiftModal.value = true;
};

const confirmDelete = (shift: Shift): void => {
    deletingShift.value = shift;
    showDeleteConfirm.value = true;
};

const deleteShift = (): void => {
    if (!deletingShift.value) return;
    router.delete(route('admin.shifts.destroy', deletingShift.value.id), {
        onFinish: () => {
            showDeleteConfirm.value = false;
            deletingShift.value = null;
        },
    });
};

const onDragEnd = (evt: any): void => {
    const item = evt.item as HTMLElement;
    const to = evt.to as HTMLElement;
    const shiftId = item.dataset.shiftId;
    const targetUserId = to.dataset.userId || null;
    const targetDate = to.dataset.date;

    if (!shiftId || !targetDate) return;

    router.patch(route('admin.shifts.move', shiftId), {
        user_id: targetUserId ? Number(targetUserId) : null,
        date: targetDate,
    }, {
        preserveState: true,
    });
};

const publishWeek = (): void => {
    publishing.value = true;
    router.post(route('admin.schedule.publish'), {
        week: props.currentWeek,
    }, {
        onFinish: () => {
            publishing.value = false;
        },
    });
};
</script>

<template>
    <Head :title="t('schedule.scheduleBoard')" />

    <AdminLayout>
        <div class="space-y-4">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <WeekNavigator :currentWeek="currentWeek" />
                        <div class="flex items-center gap-2">
                            <button
                                class="btn btn-ghost btn-sm"
                                :title="t('shiftPresets.title')"
                                @click="showPresetModal = true"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </button>
                            <button
                                v-if="hasUnpublished"
                                class="btn btn-primary btn-sm"
                                :disabled="publishing"
                                @click="publishWeek"
                            >
                                {{ publishing ? t('schedule.publishing') : t('schedule.publish') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Grid -->
            <div class="card bg-base-100 shadow-xl overflow-x-auto">
                <div class="card-body p-0">
                    <table class="table table-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="w-36 sticky left-0 bg-base-100 z-10 border-b border-base-300"></th>
                                <th
                                    v-for="day in days"
                                    :key="day"
                                    class="text-center min-w-[130px] border-b border-base-300 py-3"
                                    :class="{ 'bg-primary/5': isToday(day) }"
                                >
                                    <div class="text-xs uppercase tracking-wider opacity-60">{{ formatDayShort(day) }}</div>
                                    <div class="font-semibold" :class="{ 'text-primary': isToday(day) }">{{ formatDayNum(day) }}</div>
                                </th>
                            </tr>
                        </thead>

                        <!-- Unassigned row -->
                        <tbody>
                            <tr class="align-top">
                                <td class="sticky left-0 bg-base-100 z-10 text-sm whitespace-nowrap px-3 py-2 border-b border-base-200 opacity-60 italic">
                                    {{ t('schedule.unassigned') }}
                                </td>
                                <td
                                    v-for="day in days"
                                    :key="day"
                                    class="p-0 border-b border-base-200"
                                    :class="{ 'bg-primary/5': isToday(day) }"
                                >
                                    <VueDraggable
                                        v-model="cellShifts[getCellKey(null, day)]"
                                        group="shifts"
                                        :data-user-id="null"
                                        :data-date="day"
                                        class="min-h-[52px] space-y-1 p-1.5 cursor-pointer hover:bg-primary/5 transition-colors"
                                        @end="onDragEnd"
                                        @click="onCellClick($event, day, null)"
                                    >
                                        <div v-for="shift in getShiftsForCell(null, day)" :key="shift.id" :data-shift-id="shift.id">
                                            <ShiftCard
                                                :shift="shift"
                                                :editable="true"
                                                @edit="openEditModal"
                                                @delete="confirmDelete"
                                            />
                                        </div>
                                    </VueDraggable>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Team-grouped rows -->
                        <template v-if="hasTeamGrouping">
                            <tbody v-for="team in teams" :key="team.id">
                                <tr
                                    class="cursor-pointer select-none hover:bg-base-200/50 transition-colors"
                                    @click="toggleTeam(team.id)"
                                >
                                    <td
                                        :colspan="days.length + 1"
                                        class="bg-base-300/60 font-bold py-2 px-3 border-b border-base-300"
                                    >
                                        <div class="flex items-center gap-2">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="2"
                                                stroke="currentColor"
                                                class="w-4 h-4 transition-transform duration-200"
                                                :class="{ '-rotate-90': isCollapsed(team.id) }"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                            <span>{{ team.name }}</span>
                                            <span class="text-sm font-normal opacity-50">({{ team.members.length }})</span>
                                        </div>
                                    </td>
                                </tr>
                                <template v-if="!isCollapsed(team.id)">
                                    <tr v-for="member in team.members" :key="member.id" class="align-top">
                                        <td class="sticky left-0 bg-base-100 z-10 text-sm font-medium whitespace-nowrap px-3 py-2 border-b border-base-200">
                                            {{ member.name }}
                                        </td>
                                        <td
                                            v-for="day in days"
                                            :key="day"
                                            class="p-0 border-b border-base-200"
                                            :class="{ 'bg-primary/5': isToday(day) }"
                                        >
                                            <VueDraggable
                                                v-model="cellShifts[getCellKey(member.id, day)]"
                                                group="shifts"
                                                :data-user-id="member.id"
                                                :data-date="day"
                                                class="min-h-[52px] space-y-1 p-1.5 cursor-pointer hover:bg-primary/5 transition-colors"
                                                @end="onDragEnd"
                                                @click="onCellClick($event, day, member.id)"
                                            >
                                                <div v-for="shift in getShiftsForCell(member.id, day)" :key="shift.id" :data-shift-id="shift.id">
                                                    <ShiftCard
                                                        :shift="shift"
                                                        :editable="true"
                                                        @edit="openEditModal"
                                                        @delete="confirmDelete"
                                                    />
                                                </div>
                                            </VueDraggable>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>

                            <!-- "Overig" section -->
                            <tbody v-if="ungroupedEmployees && ungroupedEmployees.length > 0">
                                <tr
                                    class="cursor-pointer select-none hover:bg-base-200/50 transition-colors"
                                    @click="toggleTeam('other')"
                                >
                                    <td
                                        :colspan="days.length + 1"
                                        class="bg-base-300/60 font-bold py-2 px-3 border-b border-base-300"
                                    >
                                        <div class="flex items-center gap-2">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="2"
                                                stroke="currentColor"
                                                class="w-4 h-4 transition-transform duration-200"
                                                :class="{ '-rotate-90': isCollapsed('other') }"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                            <span>{{ t('teams.other') }}</span>
                                            <span class="text-sm font-normal opacity-50">({{ ungroupedEmployees.length }})</span>
                                        </div>
                                    </td>
                                </tr>
                                <template v-if="!isCollapsed('other')">
                                    <tr v-for="emp in ungroupedEmployees" :key="emp.id" class="align-top">
                                        <td class="sticky left-0 bg-base-100 z-10 text-sm font-medium whitespace-nowrap px-3 py-2 border-b border-base-200">
                                            {{ emp.name }}
                                        </td>
                                        <td
                                            v-for="day in days"
                                            :key="day"
                                            class="p-0 border-b border-base-200"
                                            :class="{ 'bg-primary/5': isToday(day) }"
                                        >
                                            <VueDraggable
                                                v-model="cellShifts[getCellKey(emp.id, day)]"
                                                group="shifts"
                                                :data-user-id="emp.id"
                                                :data-date="day"
                                                class="min-h-[52px] space-y-1 p-1.5 cursor-pointer hover:bg-primary/5 transition-colors"
                                                @end="onDragEnd"
                                                @click="onCellClick($event, day, emp.id)"
                                            >
                                                <div v-for="shift in getShiftsForCell(emp.id, day)" :key="shift.id" :data-shift-id="shift.id">
                                                    <ShiftCard
                                                        :shift="shift"
                                                        :editable="true"
                                                        @edit="openEditModal"
                                                        @delete="confirmDelete"
                                                    />
                                                </div>
                                            </VueDraggable>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </template>

                        <!-- Fallback: flat employee rows (when no team data) -->
                        <tbody v-else>
                            <tr v-for="emp in employees" :key="emp.id" class="align-top">
                                <td class="sticky left-0 bg-base-100 z-10 text-sm font-medium whitespace-nowrap px-3 py-2 border-b border-base-200">
                                    {{ emp.name }}
                                </td>
                                <td
                                    v-for="day in days"
                                    :key="day"
                                    class="p-0 border-b border-base-200"
                                    :class="{ 'bg-primary/5': isToday(day) }"
                                >
                                    <VueDraggable
                                        v-model="cellShifts[getCellKey(emp.id, day)]"
                                        group="shifts"
                                        :data-user-id="emp.id"
                                        :data-date="day"
                                        class="min-h-[52px] space-y-1 p-1.5 cursor-pointer hover:bg-primary/5 transition-colors"
                                        @end="onDragEnd"
                                        @click="onCellClick($event, day, emp.id)"
                                    >
                                        <div v-for="shift in getShiftsForCell(emp.id, day)" :key="shift.id" :data-shift-id="shift.id">
                                            <ShiftCard
                                                :shift="shift"
                                                :editable="true"
                                                @edit="openEditModal"
                                                @delete="confirmDelete"
                                            />
                                        </div>
                                    </VueDraggable>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ShiftModal
            :show="showShiftModal"
            :shift="editingShift"
            :employees="employees"
            :shiftPresets="shiftPresets"
            :defaultDate="defaultDate"
            :defaultUserId="defaultUserId"
            @close="showShiftModal = false; editingShift = null"
        />

        <ShiftPresetModal
            :show="showPresetModal"
            :presets="shiftPresets"
            @close="showPresetModal = false"
        />

        <ConfirmDialog
            :show="showDeleteConfirm"
            :title="t('schedule.deleteShift')"
            :message="t('schedule.deleteConfirm')"
            @confirm="deleteShift"
            @cancel="showDeleteConfirm = false; deletingShift = null"
        />
    </AdminLayout>
</template>
