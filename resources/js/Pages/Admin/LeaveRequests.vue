<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RejectLeaveModal from '@/Components/RejectLeaveModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import type { LeaveRequest } from '@/types';

interface Props {
    leaveRequests: LeaveRequest[];
}

defineProps<Props>();
const { t } = useI18n();

const rejectingRequest = ref<LeaveRequest | null>(null);

const approve = (id: number): void => {
    router.post(route('admin.leave.approve', id));
};

const statusBadgeClass = (status: string): string => {
    switch (status) {
        case 'approved': return 'badge-success';
        case 'rejected': return 'badge-error';
        default: return 'badge-warning';
    }
};
</script>

<template>
    <Head :title="t('admin.leaveRequests')" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <Link :href="route('admin.overview')" class="btn btn-ghost btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    {{ t('admin.back') }}
                </Link>
                <h1 class="text-2xl font-bold">{{ t('admin.leaveRequests') }}</h1>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div v-if="leaveRequests.length === 0" class="text-center py-8 opacity-60">
                        {{ t('leave.noRequests') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ t('admin.name') }}</th>
                                    <th>{{ t('leave.type') }}</th>
                                    <th>{{ t('leave.dates') }}</th>
                                    <th class="hidden md:table-cell">{{ t('leave.reason') }}</th>
                                    <th>{{ t('status.draft') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="request in leaveRequests" :key="request.id">
                                    <td>
                                        <div class="font-medium">{{ request.user_name }}</div>
                                        <div class="text-xs opacity-60">{{ request.user_email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-sm">{{ t(`leave.types.${request.type}`) }}</span>
                                    </td>
                                    <td>
                                        <div>{{ request.start_date }} — {{ request.end_date }}</div>
                                        <div class="text-xs opacity-60">{{ request.days }} {{ request.days === 1 ? 'day' : 'days' }}</div>
                                    </td>
                                    <td class="hidden md:table-cell truncate max-w-48">{{ request.reason }}</td>
                                    <td>
                                        <span class="badge badge-sm" :class="statusBadgeClass(request.status)">
                                            {{ t(`leave.status.${request.status}`) }}
                                        </span>
                                        <div v-if="request.rejection_reason" class="text-xs text-error mt-1">
                                            {{ request.rejection_reason }}
                                        </div>
                                    </td>
                                    <td>
                                        <div v-if="request.status === 'pending'" class="flex gap-1">
                                            <button
                                                class="btn btn-success btn-xs"
                                                @click="approve(request.id)"
                                            >
                                                {{ t('admin.approveLeave') }}
                                            </button>
                                            <button
                                                class="btn btn-error btn-xs"
                                                @click="rejectingRequest = request"
                                            >
                                                {{ t('admin.rejectLeave') }}
                                            </button>
                                        </div>
                                        <div v-else class="text-xs opacity-60">
                                            {{ request.reviewer_name }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <RejectLeaveModal
            :show="!!rejectingRequest"
            :leave-request-id="rejectingRequest?.id ?? null"
            @close="rejectingRequest = null"
        />
    </AuthenticatedLayout>
</template>
