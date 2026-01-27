<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
    <Head title="Admin Overview" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">Admin Overview</h1>
                <Link :href="route('admin.invitations')" class="btn btn-outline btn-sm">
                    Manage Invitations
                </Link>
            </div>

            <MonthNavigator :current-month="currentMonth" />

            <!-- Summary Stats -->
            <div class="stats shadow w-full">
                <div class="stat">
                    <div class="stat-title">Total Users</div>
                    <div class="stat-value">{{ users.length }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Total Hours</div>
                    <div class="stat-value text-primary">{{ grandTotal }}h</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Average per User</div>
                    <div class="stat-value text-secondary">
                        {{ users.length > 0 ? (grandTotal / users.length).toFixed(1) : 0 }}h
                    </div>
                </div>
            </div>

            <!-- User Hours Table -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="card-title">User Hours</h2>
                        <button
                            @click="sendReport"
                            class="btn btn-primary btn-sm"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Sending...' : 'Email Report' }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th class="text-right">Entries</th>
                                    <th class="text-right">Total Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id">
                                    <td>{{ user.name }}</td>
                                    <td>{{ user.email }}</td>
                                    <td class="text-right">{{ user.entries_count }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-primary">{{ user.total_hours }}h</span>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="4" class="text-center opacity-60">
                                        No users with entries this month
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="users.length > 0">
                                <tr class="font-bold">
                                    <td colspan="3" class="text-right">Total:</td>
                                    <td class="text-right">{{ grandTotal }}h</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
