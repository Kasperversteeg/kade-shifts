<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { VueDraggable } from 'vue-draggable-plus';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WeekNavigator from '@/Components/WeekNavigator.vue';
import ShiftCard from '@/Components/ShiftCard.vue';
import ShiftModal from '@/Components/ShiftModal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import dayjs from 'dayjs';
import type { Shift, ScheduleEmployee } from '@/types';

interface Props {
    shifts: Shift[];
    employees: ScheduleEmployee[];
    days: string[];
    currentWeek: string;
    hasUnpublished: boolean;
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

// Publishing state
const publishing = ref(false);

// Build cell data: "unassigned" row + one row per employee
const rowKeys = computed(() => {
    return [
        { id: null, name: t('schedule.unassigned') },
        ...props.employees,
    ];
});

const cellShifts = computed(() => {
    const map: Record<string, Shift[]> = {};
    for (const row of rowKeys.value) {
        for (const day of props.days) {
            const key = `${row.id ?? 'unassigned'}-${day}`;
            map[key] = props.shifts.filter(
                (s) => (s.user_id === row.id) && s.date === day
            );
        }
    }
    return map;
});

const getCellKey = (rowId: number | null, day: string): string => {
    return `${rowId ?? 'unassigned'}-${day}`;
};

const formatDay = (day: string): string => {
    return dayjs(day).format('ddd D');
};

const openCreateModal = (day: string, userId: number | null): void => {
    editingShift.value = null;
    defaultDate.value = day;
    defaultUserId.value = userId;
    showShiftModal.value = true;
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

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <WeekNavigator :currentWeek="currentWeek" />
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

            <!-- Schedule Grid -->
            <div class="card bg-base-100 shadow-xl overflow-x-auto">
                <div class="card-body p-2 sm:p-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="w-32 sticky left-0 bg-base-100 z-10">{{ t('schedule.employee') }}</th>
                                <th v-for="day in days" :key="day" class="text-center min-w-[120px]">
                                    {{ formatDay(day) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in rowKeys" :key="row.id ?? 'unassigned'" class="align-top">
                                <td class="sticky left-0 bg-base-100 z-10 font-medium text-sm whitespace-nowrap">
                                    {{ row.name }}
                                </td>
                                <td v-for="day in days" :key="day" class="p-1">
                                    <VueDraggable
                                        :modelValue="cellShifts[getCellKey(row.id, day)]"
                                        group="shifts"
                                        :data-user-id="row.id"
                                        :data-date="day"
                                        item-key="id"
                                        class="min-h-[60px] space-y-1 rounded-lg bg-base-200/50 p-1"
                                        @end="onDragEnd"
                                    >
                                        <template #item="{ element }">
                                            <div :data-shift-id="element.id">
                                                <ShiftCard
                                                    :shift="element"
                                                    :editable="true"
                                                    @edit="openEditModal"
                                                    @delete="confirmDelete"
                                                />
                                            </div>
                                        </template>
                                    </VueDraggable>
                                    <button
                                        class="btn btn-ghost btn-xs w-full mt-1 opacity-40 hover:opacity-100"
                                        @click="openCreateModal(day, row.id)"
                                    >
                                        +
                                    </button>
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
            :defaultDate="defaultDate"
            :defaultUserId="defaultUserId"
            @close="showShiftModal = false; editingShift = null"
        />

        <ConfirmDialog
            :show="showDeleteConfirm"
            :title="t('schedule.deleteShift')"
            :message="t('schedule.deleteConfirm')"
            @confirm="deleteShift"
            @cancel="showDeleteConfirm = false; deletingShift = null"
        />
    </AuthenticatedLayout>
</template>
