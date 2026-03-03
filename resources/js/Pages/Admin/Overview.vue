<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { UserWithHours } from '@/types';

const { t } = useI18n();

interface Props {
    users: UserWithHours[];
    grandTotal: number;
    currentMonth: string;
    activeFilter: string;
    pendingApprovals: number;
    pendingLeaveRequests: number;
    expiringContracts: number;
    estimatedMonthlyCost: number;
}

const props = defineProps<Props>();

const form = useForm({
    month: props.currentMonth,
});

const sendReport = (): void => {
    form.post(route('admin.send-report'));
};

const setActiveFilter = (filter: string): void => {
    router.get(window.location.pathname, {
        month: props.currentMonth,
        active: filter,
    }, { preserveState: true });
};
</script>

<template>

    <Head :title="t('admin.overview')" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">{{ t('admin.overview') }}</h1>
                <Link :href="route('admin.invitations')" class="btn btn-outline btn-sm">
                    {{ t('admin.manageInvitations') }}
                </Link>
            </div>

            <MonthNavigator :current-month="currentMonth" />

            <!-- Alert Cards -->
            <div v-if="pendingApprovals > 0 || pendingLeaveRequests > 0 || expiringContracts > 0" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <Link v-if="pendingApprovals > 0" :href="route('admin.overview')" class="alert alert-warning shadow-sm">
                    <span>{{ pendingApprovals }} {{ t('admin.pendingApprovalCount') }}</span>
                </Link>
                <Link v-if="pendingLeaveRequests > 0" :href="route('admin.leave.index')" class="alert alert-info shadow-sm">
                    <span>{{ pendingLeaveRequests }} {{ t('admin.pendingLeaveCount') }}</span>
                </Link>
                <div v-if="expiringContracts > 0" class="alert alert-error shadow-sm">
                    <span>{{ expiringContracts }} {{ t('admin.expiringContractsCount') }}</span>
                </div>
            </div>

            <div class="stats shadow w-full">
                <div class="stat">
                    <div class="stat-title">{{ t('admin.totalUsers') }}</div>
                    <div class="stat-value">{{ users.length }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('admin.totalHours') }}</div>
                    <div class="stat-value text-primary">{{ grandTotal }}{{ t('summary.hoursUnit') }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('admin.avgPerUser') }}</div>
                    <div class="stat-value text-secondary">
                        {{ users.length > 0 ? (grandTotal / users.length).toFixed(1) : 0 }}{{ t('summary.hoursUnit') }}
                    </div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('admin.estimatedCost') }}</div>
                    <div class="stat-value text-accent">&euro;{{ estimatedMonthlyCost.toFixed(0) }}</div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-4">
                        <div class="flex items-center gap-4 mb-2 md:mb-0">
                            <h2 class="card-title">{{ t('admin.userHours') }}</h2>
                            <div class="join">
                                <button
                                    class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeFilter === 'active' }"
                                    @click="setActiveFilter('active')"
                                >
                                    {{ t('admin.filterActive') }}
                                </button>
                                <button
                                    class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeFilter === 'inactive' }"
                                    @click="setActiveFilter('inactive')"
                                >
                                    {{ t('admin.filterInactive') }}
                                </button>
                                <button
                                    class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeFilter === 'all' }"
                                    @click="setActiveFilter('all')"
                                >
                                    {{ t('admin.filterAll') }}
                                </button>
                            </div>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <a :href="route('admin.export-csv', { month: currentMonth })"
                                class="btn btn-outline btn-sm">
                                {{ t('export.csv') }}
                            </a>
                            <a :href="route('admin.export-pdf', { month: currentMonth })"
                                class="btn btn-outline btn-sm">
                                {{ t('export.pdf') }}
                            </a>
                            <button @click="sendReport" class="btn btn-primary btn-sm" :disabled="form.processing">
                                {{ form.processing ? t('admin.sending') : t('admin.emailReport') }}
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>{{ t('admin.name') }}</th>
                                    <th>{{ t('admin.email') }}</th>
                                    <th class="text-center">{{ t('invitations.status') }}</th>
                                    <th class="text-right">{{ t('admin.entries') }}</th>
                                    <th class="text-right">{{ t('admin.totalHours') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id">
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <Link :href="route('admin.user-detail', user.id)"
                                                class="link link-hover link-primary">
                                                {{ user.name }}
                                            </Link>
                                            <span v-if="!user.is_active" class="badge badge-error badge-xs">{{ t('admin.inactive') }}</span>
                                        </div>
                                    </td>
                                    <td>{{ user.email }}</td>
                                    <td class="text-center">
                                        <div class="flex gap-1 justify-center flex-wrap">
                                            <span v-if="user.status_counts.submitted" class="badge badge-warning badge-sm">
                                                {{ user.status_counts.submitted }} {{ t('status.submitted') }}
                                            </span>
                                            <span v-if="user.status_counts.approved" class="badge badge-success badge-sm">
                                                {{ user.status_counts.approved }} {{ t('status.approved') }}
                                            </span>
                                            <span v-if="user.status_counts.draft" class="badge badge-ghost badge-sm">
                                                {{ user.status_counts.draft }} {{ t('status.draft') }}
                                            </span>
                                            <span v-if="user.status_counts.rejected" class="badge badge-error badge-sm">
                                                {{ user.status_counts.rejected }} {{ t('status.rejected') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-right">{{ user.entries_count }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-primary">{{ user.total_hours }}{{
                                            t('summary.hoursUnit') }}</span>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="5" class="text-center opacity-60">
                                        {{ t('admin.noUsersThisMonth') }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="users.length > 0">
                                <tr class="font-bold">
                                    <td colspan="4" class="text-right">{{ t('admin.total') }}</td>
                                    <td class="text-right">{{ grandTotal }}{{ t('summary.hoursUnit') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
