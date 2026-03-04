<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import RejectEntryModal from '@/Components/RejectEntryModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import dayjs from 'dayjs';
import type { TimeEntry, StatusCounts } from '@/types';

const { t } = useI18n();

interface UserSummary {
    id: number;
    name: string;
    email: string;
    hourly_rate: string | null;
    contract_type: string | null;
    is_active: boolean;
}

interface Props {
    user: UserSummary;
    entries: TimeEntry[];
    monthTotal: number;
    approvedTotal: number;
    statusCounts: StatusCounts;
    currentMonth: string;
    submittedEntryIds: number[];
}

const props = defineProps<Props>();

const rejectingEntry = ref<TimeEntry | null>(null);
const statusFilter = ref<string>('all');

const filteredEntries = computed(() => {
    if (statusFilter.value === 'all') return props.entries;
    return props.entries.filter(e => e.status === statusFilter.value);
});

const estimatedCost = computed(() => {
    if (!props.user.hourly_rate) return null;
    return (props.monthTotal * parseFloat(props.user.hourly_rate)).toFixed(2);
});

const bulkApprove = (): void => {
    if (props.submittedEntryIds.length === 0) return;
    router.post(route('admin.entries.bulk-approve'), {
        entry_ids: props.submittedEntryIds,
    });
};

const approveEntry = (entryId: number): void => {
    router.post(route('admin.entries.approve', entryId));
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
</script>

<template>
    <Head :title="`${user.name} — ${t('admin.shifts')}`" />

    <AdminLayout>
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.users')" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                        {{ t('admin.back') }}
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ user.name }}
                            <span v-if="!user.is_active" class="badge badge-error badge-sm">{{ t('admin.inactive') }}</span>
                        </h1>
                        <p class="text-sm opacity-60">{{ user.email }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="submittedEntryIds.length > 0"
                        @click="bulkApprove"
                        class="btn btn-success btn-sm"
                    >
                        {{ t('admin.bulkApprove') }} ({{ submittedEntryIds.length }})
                    </button>
                    <Link :href="route('admin.user-detail', user.id)" class="btn btn-outline btn-sm">
                        {{ t('admin.userProfile') }}
                    </Link>
                </div>
            </div>

            <MonthNavigator :current-month="currentMonth" />

            <!-- Stats -->
            <div class="stats shadow w-full">
                <div class="stat">
                    <div class="stat-title">{{ t('admin.totalHours') }}</div>
                    <div class="stat-value text-primary">{{ monthTotal }}{{ t('summary.hoursUnit') }}</div>
                    <div class="stat-desc">{{ entries.length }} {{ t('admin.entries').toLowerCase() }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('admin.approvedHours') }}</div>
                    <div class="stat-value text-success">{{ approvedTotal }}{{ t('summary.hoursUnit') }}</div>
                    <div class="stat-desc">{{ statusCounts.approved }} {{ t('status.approved').toLowerCase() }}</div>
                </div>
                <div v-if="estimatedCost" class="stat">
                    <div class="stat-title">{{ t('admin.estimatedCost') }}</div>
                    <div class="stat-value text-accent">&euro;{{ estimatedCost }}</div>
                    <div class="stat-desc">&euro;{{ user.hourly_rate }}/{{ t('summary.hoursUnit') }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('admin.pendingApproval') }}</div>
                    <div class="stat-value" :class="statusCounts.submitted > 0 ? 'text-warning' : ''">{{ statusCounts.submitted }}</div>
                    <div v-if="statusCounts.rejected > 0" class="stat-desc text-error">{{ statusCounts.rejected }} {{ t('status.rejected').toLowerCase() }}</div>
                </div>
            </div>

            <!-- Entries Table -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4">
                        <h2 class="card-title">{{ t('admin.shifts') }}</h2>
                        <div class="join">
                            <button
                                class="btn btn-xs join-item"
                                :class="{ 'btn-active': statusFilter === 'all' }"
                                @click="statusFilter = 'all'"
                            >
                                {{ t('admin.filterAll') }} ({{ entries.length }})
                            </button>
                            <button
                                v-if="statusCounts.draft > 0"
                                class="btn btn-xs join-item"
                                :class="{ 'btn-active': statusFilter === 'draft' }"
                                @click="statusFilter = 'draft'"
                            >
                                {{ t('status.draft') }} ({{ statusCounts.draft }})
                            </button>
                            <button
                                v-if="statusCounts.submitted > 0"
                                class="btn btn-xs join-item"
                                :class="{ 'btn-active': statusFilter === 'submitted' }"
                                @click="statusFilter = 'submitted'"
                            >
                                {{ t('status.submitted') }} ({{ statusCounts.submitted }})
                            </button>
                            <button
                                v-if="statusCounts.approved > 0"
                                class="btn btn-xs join-item"
                                :class="{ 'btn-active': statusFilter === 'approved' }"
                                @click="statusFilter = 'approved'"
                            >
                                {{ t('status.approved') }} ({{ statusCounts.approved }})
                            </button>
                            <button
                                v-if="statusCounts.rejected > 0"
                                class="btn btn-xs join-item"
                                :class="{ 'btn-active': statusFilter === 'rejected' }"
                                @click="statusFilter = 'rejected'"
                            >
                                {{ t('status.rejected') }} ({{ statusCounts.rejected }})
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredEntries.length === 0" class="text-center py-8 opacity-60">
                        {{ t('timeEntries.noEntries') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
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
                                <tr v-for="entry in filteredEntries" :key="entry.id">
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
                                            <button @click="rejectingEntry = entry" class="btn btn-error btn-xs">
                                                {{ t('admin.reject') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td colspan="4" class="text-right">{{ t('admin.total') }}</td>
                                    <td class="text-right">
                                        {{ statusFilter === 'all'
                                            ? monthTotal
                                            : filteredEntries.reduce((sum, e) => sum + Number(e.total_hours), 0).toFixed(2)
                                        }}{{ t('summary.hoursUnit') }}
                                    </td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <RejectEntryModal
            :show="!!rejectingEntry"
            :entry-id="rejectingEntry?.id ?? null"
            @close="rejectingEntry = null"
        />
    </AdminLayout>
</template>
