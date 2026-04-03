<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import type { LeaveRequest, LeaveBalance } from '@/types';

interface Props {
    leaveRequests: LeaveRequest[];
    leaveBalance: LeaveBalance;
}

const props = defineProps<Props>();
const { t } = useI18n();
const page = usePage();
const showLeaveBalance = computed(() => (page.props.auth as any).user?.contract_type === 'vast');

const showForm = ref<boolean>(false);
const cancellingRequest = ref<LeaveRequest | null>(null);

const form = useForm({
    type: 'vakantie',
    start_date: '',
    end_date: '',
    reason: '',
});

const submit = (): void => {
    form.post(route('leave.store'), {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const confirmCancel = (): void => {
    if (!cancellingRequest.value) return;
    router.delete(route('leave.destroy', cancellingRequest.value.id), {
        onSuccess: () => { cancellingRequest.value = null; },
    });
};

const statusBadgeClass = (status: string): string => {
    switch (status) {
        case 'approved': return 'badge-success';
        case 'rejected': return 'badge-error';
        default: return 'badge-warning';
    }
};

const leaveTypes = ['vakantie', 'bijzonder_verlof', 'onbetaald_verlof'] as const;
</script>

<template>
    <Head :title="t('leave.title')" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">{{ t('leave.title') }}</h1>
                <button
                    v-if="!showForm"
                    class="btn btn-primary btn-sm"
                    @click="showForm = true"
                >
                    {{ t('leave.request') }}
                </button>
            </div>

            <!-- Leave Balance Stats (only for "vast" contract type) -->
            <div v-if="showLeaveBalance" class="stats shadow w-full">
                <div class="stat">
                    <div class="stat-title">{{ t('leave.balance.total') }}</div>
                    <div class="stat-value text-primary">{{ leaveBalance.total }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('leave.balance.used') }}</div>
                    <div class="stat-value">{{ leaveBalance.used }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">{{ t('leave.balance.remaining') }}</div>
                    <div class="stat-value" :class="leaveBalance.remaining > 0 ? 'text-success' : 'text-error'">
                        {{ leaveBalance.remaining }}
                    </div>
                </div>
            </div>

            <!-- Request Form -->
            <div v-if="showForm" class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">{{ t('leave.request') }}</h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('leave.type') }}</legend>
                            <select
                                v-model="form.type"
                                class="select w-full"
                                :class="{ 'select-error': form.errors.type }"
                            >
                                <option v-for="lt in leaveTypes" :key="lt" :value="lt">
                                    {{ t(`leave.types.${lt}`) }}
                                </option>
                            </select>
                            <p v-if="form.errors.type" class="label text-error">{{ form.errors.type }}</p>
                        </fieldset>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('leave.startDate') }}</legend>
                                <input
                                    type="date"
                                    v-model="form.start_date"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.start_date }"
                                />
                                <p v-if="form.errors.start_date" class="label text-error">{{ form.errors.start_date }}</p>
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('leave.endDate') }}</legend>
                                <input
                                    type="date"
                                    v-model="form.end_date"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.end_date }"
                                />
                                <p v-if="form.errors.end_date" class="label text-error">{{ form.errors.end_date }}</p>
                            </fieldset>
                        </div>

                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('leave.reason') }}</legend>
                            <textarea
                                v-model="form.reason"
                                class="textarea w-full"
                                :class="{ 'textarea-error': form.errors.reason }"
                                rows="3"
                            ></textarea>
                            <p v-if="form.errors.reason" class="label text-error">{{ form.errors.reason }}</p>
                        </fieldset>

                        <div class="flex gap-2 justify-end">
                            <button type="button" class="btn" @click="showForm = false; form.reset()">
                                {{ t('common.cancel') }}
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ form.processing ? t('leave.submitting') : t('leave.submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Leave Requests List -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div v-if="leaveRequests.length === 0" class="text-center py-8 opacity-60">
                        {{ t('leave.noRequests') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
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
                                        <button
                                            v-if="request.status === 'pending'"
                                            class="btn btn-ghost btn-xs text-error"
                                            @click="cancellingRequest = request"
                                        >
                                            {{ t('common.cancel') }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="!!cancellingRequest"
            :title="t('leave.cancelConfirm')"
            :message="cancellingRequest ? `${t(`leave.types.${cancellingRequest.type}`)} — ${cancellingRequest.start_date} — ${cancellingRequest.end_date}` : ''"
            :confirm-label="t('common.cancel')"
            variant="warning"
            @confirm="confirmCancel"
            @cancel="cancellingRequest = null"
        />
    </AuthenticatedLayout>
</template>
