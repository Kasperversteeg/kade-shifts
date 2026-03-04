<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
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

    <AdminLayout>
        <div class="space-y-4">
            <h1 class="text-2xl font-bold">{{ t('admin.overview') }}</h1>

            <MonthNavigator :current-month="currentMonth" />

            <!-- Alert Cards -->
            <div v-if="pendingApprovals > 0 || pendingLeaveRequests > 0 || expiringContracts > 0"
                class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <Link v-if="pendingApprovals > 0" :href="route('admin.approvals')" class="alert alert-warning shadow-sm">
                    <span>{{ pendingApprovals }} {{ t('admin.pendingApprovalCount') }}</span>
                </Link>
                <Link v-if="pendingLeaveRequests > 0" :href="route('admin.leave.index')"
                    class="alert alert-info shadow-sm">
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
                                <button class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeFilter === 'active' }"
                                    @click="setActiveFilter('active')">
                                    {{ t('admin.filterActive') }}
                                </button>
                                <button class="btn btn-xs join-item"
                                    :class="{ 'btn-active': activeFilter === 'inactive' }"
                                    @click="setActiveFilter('inactive')">
                                    {{ t('admin.filterInactive') }}
                                </button>
                                <button class="btn btn-xs join-item" :class="{ 'btn-active': activeFilter === 'all' }"
                                    @click="setActiveFilter('all')">
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
                                    <th class="text-right">{{ t('admin.entries') }}</th>
                                    <th class="text-right">{{ t('admin.totalHours') }}</th>
                                    <th></th>
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
                                            <span v-if="!user.is_active" class="badge badge-error badge-xs">{{
                                                t('admin.inactive') }}</span>
                                        </div>
                                    </td>
                                    <td>{{ user.email }}</td>
                                    <td class="text-right">{{ user.entries_count }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-primary">{{ user.total_hours }}{{
                                            t('summary.hoursUnit') }}</span>
                                    </td>
                                    <td>
                                        <Link :href="route('admin.user-shifts', { user: user.id, month: currentMonth })" class="btn btn-ghost btn-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            {{ t('admin.viewShifts') }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="6" class="text-center opacity-60">
                                        {{ t('admin.noUsersThisMonth') }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="users.length > 0">
                                <tr class="font-bold">
                                    <td colspan="4" class="text-right">{{ t('admin.total') }}</td>
                                    <td class="text-right">{{ grandTotal }}{{ t('summary.hoursUnit') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
