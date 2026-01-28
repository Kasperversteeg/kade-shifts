<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    users: Array,
    grandTotal: Number,
    currentMonth: String,
});

const form = useForm({
    month: props.currentMonth,
});

const sendReport = () => {
    form.post(route('admin.send-report'));
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
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="card-title">{{ t('admin.userHours') }}</h2>
                        <div class="flex gap-2">
                            <a :href="route('admin.export-csv', { month: currentMonth })" class="btn btn-outline btn-sm">
                                {{ t('export.csv') }}
                            </a>
                            <a :href="route('admin.export-pdf', { month: currentMonth })" class="btn btn-outline btn-sm">
                                {{ t('export.pdf') }}
                            </a>
                            <button
                                @click="sendReport"
                                class="btn btn-primary btn-sm"
                                :disabled="form.processing"
                            >
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
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id">
                                    <td>
                                        <Link :href="route('admin.user-detail', user.id)" class="link link-hover link-primary">
                                            {{ user.name }}
                                        </Link>
                                    </td>
                                    <td>{{ user.email }}</td>
                                    <td class="text-right">{{ user.entries_count }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-primary">{{ user.total_hours }}{{ t('summary.hoursUnit') }}</span>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="4" class="text-center opacity-60">
                                        {{ t('admin.noUsersThisMonth') }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="users.length > 0">
                                <tr class="font-bold">
                                    <td colspan="3" class="text-right">{{ t('admin.total') }}</td>
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
