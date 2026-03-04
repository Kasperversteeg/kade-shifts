<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import RejectEntryModal from '@/Components/RejectEntryModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import dayjs from 'dayjs';
import type { StatusCounts } from '@/types';

const { t } = useI18n();

interface ApprovalEntry {
    id: number;
    user_id: number;
    user_name: string;
    user_email: string;
    date: string;
    shift_start: string;
    shift_end: string;
    break_minutes: number;
    total_hours: number;
    notes: string | null;
    status: string;
    rejection_reason: string | null;
}

interface Props {
    entries: ApprovalEntry[];
    statusCounts: StatusCounts;
    currentMonth: string;
    activeStatus: string;
    submittedEntryIds: number[];
}

const props = defineProps<Props>();

const rejectingEntryId = ref<number | null>(null);
const selectedIds = ref<number[]>([]);

const allSubmittedSelected = computed(() => {
    return props.submittedEntryIds.length > 0 &&
        props.submittedEntryIds.every(id => selectedIds.value.includes(id));
});

const toggleSelectAll = (): void => {
    if (allSubmittedSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = [...props.submittedEntryIds];
    }
};

const toggleEntry = (id: number): void => {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const setStatusFilter = (status: string): void => {
    selectedIds.value = [];
    router.get(route('admin.approvals'), {
        month: props.currentMonth,
        status,
    }, { preserveState: true });
};

const approveEntry = (entryId: number): void => {
    router.post(route('admin.entries.approve', entryId));
};

const bulkApprove = (): void => {
    if (selectedIds.value.length === 0) return;
    router.post(route('admin.entries.bulk-approve'), {
        entry_ids: selectedIds.value,
    });
};

const statusBadgeClass = (status: string): string => {
    switch (status) {
        case 'draft': return 'badge-ghost';
        case 'submitted': return 'badge-warning';
        case 'approved': return 'badge-success';
        case 'rejected': return 'badge-error';
        default: return 'badge-ghost';
    }
};

const totalHours = computed(() => {
    return props.entries.reduce((sum, e) => sum + Number(e.total_hours), 0).toFixed(2);
});
</script>

<template>
    <Head :title="t('admin.approvals')" />

    <AdminLayout>
        <div class="space-y-4">
            <h1 class="text-2xl font-bold">{{ t('admin.approvals') }}</h1>

            <MonthNavigator :current-month="currentMonth" />

            <!-- Stats -->
            <div class="stats shadow w-full">
                <div class="stat">
                    <div class="stat-title">{{ t('admin.pendingApproval') }}</div>
                    <div class="stat-value" :class="statusCounts.submitted > 0 ? 'text-warning' : ''">{{ statusCounts.submitted }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('status.approved') }}</div>
                    <div class="stat-value text-success">{{ statusCounts.approved }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('status.rejected') }}</div>
                    <div class="stat-value" :class="statusCounts.rejected > 0 ? 'text-error' : ''">{{ statusCounts.rejected }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('status.draft') }}</div>
                    <div class="stat-value">{{ statusCounts.draft }}</div>
                </div>
            </div>

            <!-- Entries Table -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4">
                        <h2 class="card-title">{{ t('admin.timeEntries') }}</h2>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="join">
                                <button
                                    class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeStatus === 'submitted' }"
                                    @click="setStatusFilter('submitted')"
                                >
                                    {{ t('status.submitted') }} ({{ statusCounts.submitted }})
                                </button>
                                <button
                                    class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeStatus === 'approved' }"
                                    @click="setStatusFilter('approved')"
                                >
                                    {{ t('status.approved') }} ({{ statusCounts.approved }})
                                </button>
                                <button
                                    class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeStatus === 'rejected' }"
                                    @click="setStatusFilter('rejected')"
                                >
                                    {{ t('status.rejected') }} ({{ statusCounts.rejected }})
                                </button>
                                <button
                                    class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeStatus === 'all' }"
                                    @click="setStatusFilter('all')"
                                >
                                    {{ t('admin.filterAll') }}
                                </button>
                            </div>
                            <button
                                v-if="selectedIds.length > 0"
                                @click="bulkApprove"
                                class="btn btn-success btn-sm"
                            >
                                {{ t('admin.bulkApprove') }} ({{ selectedIds.length }})
                            </button>
                        </div>
                    </div>

                    <div v-if="entries.length === 0" class="text-center py-8 opacity-60">
                        {{ t('admin.allApproved') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th v-if="activeStatus === 'submitted' || activeStatus === 'all'">
                                        <input
                                            type="checkbox"
                                            class="checkbox checkbox-sm"
                                            :checked="allSubmittedSelected"
                                            :disabled="submittedEntryIds.length === 0"
                                            @change="toggleSelectAll"
                                        />
                                    </th>
                                    <th>{{ t('schedule.employee') }}</th>
                                    <th>{{ t('timeEntries.date') }}</th>
                                    <th>{{ t('timeEntries.shiftStart') }}</th>
                                    <th>{{ t('timeEntries.shiftEnd') }}</th>
                                    <th class="text-right">{{ t('timeEntries.breakMinutes') }}</th>
                                    <th class="text-right">{{ t('admin.totalHours') }}</th>
                                    <th class="text-center">{{ t('invitations.status') }}</th>
                                    <th>{{ t('timeEntries.notes') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in entries" :key="entry.id">
                                    <td v-if="activeStatus === 'submitted' || activeStatus === 'all'">
                                        <input
                                            v-if="entry.status === 'submitted'"
                                            type="checkbox"
                                            class="checkbox checkbox-sm"
                                            :checked="selectedIds.includes(entry.id)"
                                            @change="toggleEntry(entry.id)"
                                        />
                                    </td>
                                    <td>
                                        <Link :href="route('admin.user-shifts', { user: entry.user_id, month: currentMonth })" class="link link-hover link-primary font-medium">
                                            {{ entry.user_name }}
                                        </Link>
                                    </td>
                                    <td class="font-medium">{{ dayjs(entry.date).format('ddd, MMM D') }}</td>
                                    <td>{{ entry.shift_start }}</td>
                                    <td>{{ entry.shift_end }}</td>
                                    <td class="text-right">
                                        <span v-if="entry.break_minutes > 0">{{ entry.break_minutes }}min</span>
                                        <span v-else class="opacity-40">—</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="badge badge-primary badge-sm">{{ entry.total_hours }}{{ t('summary.hoursUnit') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-sm" :class="statusBadgeClass(entry.status)">
                                            {{ t(`status.${entry.status}`) }}
                                        </span>
                                    </td>
                                    <td class="max-w-48 truncate text-sm opacity-60">
                                        <template v-if="entry.status === 'rejected' && entry.rejection_reason">
                                            <span class="text-error">{{ entry.rejection_reason }}</span>
                                        </template>
                                        <template v-else>
                                            {{ entry.notes ?? '—' }}
                                        </template>
                                    </td>
                                    <td>
                                        <div v-if="entry.status === 'submitted'" class="flex gap-1">
                                            <button @click="approveEntry(entry.id)" class="btn btn-success btn-xs">
                                                {{ t('admin.approve') }}
                                            </button>
                                            <button @click="rejectingEntryId = entry.id" class="btn btn-error btn-xs">
                                                {{ t('admin.reject') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td :colspan="activeStatus === 'submitted' || activeStatus === 'all' ? 7 : 6" class="text-right">{{ t('admin.total') }}</td>
                                    <td class="text-right">{{ totalHours }}{{ t('summary.hoursUnit') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <RejectEntryModal
            :show="!!rejectingEntryId"
            :entry-id="rejectingEntryId"
            @close="rejectingEntryId = null"
        />
    </AdminLayout>
</template>
