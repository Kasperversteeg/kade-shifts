<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import HoursSummary from '@/Components/HoursSummary.vue';
import TimeEntryCard from '@/Components/TimeEntryCard.vue';
import RejectEntryModal from '@/Components/RejectEntryModal.vue';
import DocumentUpload from '@/Components/DocumentUpload.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import type { User, TimeEntry, Document, SickLeave } from '@/types';

const { t } = useI18n();

interface Props {
    user: User;
    entries: TimeEntry[];
    documents: Document[];
    sickLeaves: SickLeave[];
    isCurrentlySick: boolean;
    sickDaysThisYear: number;
    monthTotal: number;
    currentMonth: string;
    submittedEntryIds: number[];
}

const props = defineProps<Props>();

const rejectingEntry = ref<TimeEntry | null>(null);
const deletingDocument = ref<Document | null>(null);

const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const deleteDocument = (): void => {
    if (!deletingDocument.value) return;
    router.delete(route('admin.documents.destroy', deletingDocument.value.id), {
        onSuccess: () => { deletingDocument.value = null; },
    });
};

const toggleActive = (): void => {
    router.post(route('admin.user-toggle-active', props.user.id));
};

const bulkApprove = (): void => {
    if (props.submittedEntryIds.length === 0) return;
    router.post(route('admin.entries.bulk-approve'), {
        entry_ids: props.submittedEntryIds,
    });
};

const sickLeaveForm = useForm({
    start_date: '',
    notes: '',
});

const submitSickLeave = (): void => {
    sickLeaveForm.post(route('admin.sick-leave.store', props.user.id), {
        onSuccess: () => { sickLeaveForm.reset(); },
    });
};

const recoverForm = useForm({
    end_date: '',
});

const submitRecovery = (sickLeaveId: number): void => {
    recoverForm.patch(route('admin.sick-leave.recover', sickLeaveId), {
        onSuccess: () => { recoverForm.reset(); },
    });
};
</script>

<template>
    <Head :title="`${user.name}`" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.overview')" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                        {{ t('admin.back') }}
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ user.name }}
                            <span v-if="!user.is_active" class="badge badge-error">{{ t('admin.inactive') }}</span>
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
                    <button
                        @click="toggleActive"
                        class="btn btn-sm"
                        :class="user.is_active ? 'btn-warning' : 'btn-success'"
                    >
                        {{ user.is_active ? t('admin.deactivate') : t('admin.activate') }}
                    </button>
                    <Link :href="route('admin.user-edit', user.id)" class="btn btn-outline btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        {{ t('admin.editUser') }}
                    </Link>
                </div>
            </div>

            <!-- Profile Summary -->
            <div v-if="user.hourly_rate || user.contract_type" class="card bg-base-100 shadow">
                <div class="card-body p-4">
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div v-if="user.hourly_rate">
                            <span class="opacity-60">{{ t('admin.hourlyRate') }}:</span>
                            <span class="font-medium ml-1">&euro;{{ user.hourly_rate }}</span>
                        </div>
                        <div v-if="user.contract_type">
                            <span class="opacity-60">{{ t('admin.contractType') }}:</span>
                            <span class="badge badge-sm ml-1">{{ t(`admin.contractTypes.${user.contract_type}`) }}</span>
                        </div>
                        <div v-if="user.contract_start_date">
                            <span class="opacity-60">{{ t('admin.contractStart') }}:</span>
                            <span class="font-medium ml-1">{{ user.contract_start_date }}</span>
                        </div>
                        <div v-if="user.contract_end_date">
                            <span class="opacity-60">{{ t('admin.contractEnd') }}:</span>
                            <span class="font-medium ml-1">{{ user.contract_end_date }}</span>
                        </div>
                    </div>
                    <div v-if="user.profile_completeness && user.profile_completeness.percentage < 100" class="mt-2">
                        <progress class="progress progress-warning w-full" :value="user.profile_completeness.percentage" max="100"></progress>
                        <p class="text-xs opacity-60 mt-1">{{ t('admin.profileIncomplete') }} ({{ user.profile_completeness.percentage }}%)</p>
                    </div>
                </div>
            </div>

            <MonthNavigator :current-month="currentMonth" />

            <HoursSummary :total="monthTotal" :month="currentMonth" />

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">{{ t('admin.entries') }}</h2>

                    <div v-if="entries.length === 0" class="text-center py-8 opacity-60">
                        {{ t('timeEntries.noEntries') }}
                    </div>
                    <div v-else class="space-y-2">
                        <TimeEntryCard
                            v-for="entry in entries"
                            :key="entry.id"
                            :entry="entry"
                            readonly
                            show-approval-actions
                            @reject="rejectingEntry = $event"
                        />
                    </div>
                </div>
            </div>

            <!-- Sick Leave -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="card-title">{{ t('sickLeave.title') }}</h2>
                        <div class="flex gap-2 items-center">
                            <span v-if="isCurrentlySick" class="badge badge-error">{{ t('sickLeave.currentlySick') }}</span>
                            <span class="badge badge-ghost">{{ t('sickLeave.daysThisYear') }}: {{ sickDaysThisYear }}</span>
                        </div>
                    </div>

                    <!-- Register sick leave form (only if not currently sick) -->
                    <div v-if="!isCurrentlySick" class="mb-4">
                        <form @submit.prevent="submitSickLeave" class="flex flex-col md:flex-row gap-2 items-end">
                            <fieldset class="fieldset flex-1">
                                <legend class="fieldset-legend">{{ t('sickLeave.startDate') }}</legend>
                                <input
                                    type="date"
                                    v-model="sickLeaveForm.start_date"
                                    class="input w-full"
                                    :class="{ 'input-error': sickLeaveForm.errors.start_date }"
                                />
                                <p v-if="sickLeaveForm.errors.start_date" class="label text-error">{{ sickLeaveForm.errors.start_date }}</p>
                            </fieldset>
                            <fieldset class="fieldset flex-1">
                                <legend class="fieldset-legend">{{ t('sickLeave.notes') }}</legend>
                                <input
                                    type="text"
                                    v-model="sickLeaveForm.notes"
                                    class="input w-full"
                                />
                            </fieldset>
                            <button type="submit" class="btn btn-error btn-sm" :disabled="sickLeaveForm.processing">
                                {{ t('sickLeave.register') }}
                            </button>
                        </form>
                    </div>

                    <div v-if="sickLeaves.length === 0" class="text-center py-4 opacity-60">
                        {{ t('sickLeave.noRecords') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ t('sickLeave.startDate') }}</th>
                                    <th>{{ t('sickLeave.endDate') }}</th>
                                    <th>{{ t('sickLeave.notes') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="sl in sickLeaves" :key="sl.id">
                                    <td>{{ sl.start_date }}</td>
                                    <td>
                                        <span v-if="sl.end_date">{{ sl.end_date }}</span>
                                        <span v-else class="badge badge-error badge-sm">{{ t('sickLeave.active') }}</span>
                                    </td>
                                    <td class="text-sm opacity-60">{{ sl.notes }}</td>
                                    <td>
                                        <div v-if="sl.is_active" class="flex gap-1 items-end">
                                            <input
                                                type="date"
                                                v-model="recoverForm.end_date"
                                                class="input input-sm"
                                            />
                                            <button
                                                class="btn btn-success btn-xs"
                                                :disabled="recoverForm.processing"
                                                @click="submitRecovery(sl.id)"
                                            >
                                                {{ t('sickLeave.recover') }}
                                            </button>
                                        </div>
                                        <span v-else class="text-xs opacity-60">{{ sl.days }} {{ sl.days === 1 ? 'day' : 'days' }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">{{ t('documents.title') }}</h2>

                    <div v-if="documents.length === 0" class="text-center py-4 opacity-60">
                        {{ t('documents.noDocuments') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ t('documents.type') }}</th>
                                    <th>{{ t('documents.filename') }}</th>
                                    <th>{{ t('documents.size') }}</th>
                                    <th>{{ t('documents.uploadedAt') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="doc in documents" :key="doc.id">
                                    <td>
                                        <span class="badge badge-sm">{{ t(`documents.types.${doc.type}`) }}</span>
                                    </td>
                                    <td class="truncate max-w-48">{{ doc.original_filename }}</td>
                                    <td class="text-xs opacity-60">{{ formatFileSize(doc.file_size) }}</td>
                                    <td class="text-xs opacity-60">{{ doc.created_at }}</td>
                                    <td class="flex gap-1 justify-end">
                                        <a :href="route('documents.download', doc.id)" class="btn btn-ghost btn-xs">
                                            {{ t('documents.download') }}
                                        </a>
                                        <button @click="deletingDocument = doc" class="btn btn-ghost btn-xs text-error">
                                            {{ t('common.delete') }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="divider"></div>
                    <DocumentUpload :upload-url="route('admin.documents.store', user.id)" />
                </div>
            </div>
        </div>

        <RejectEntryModal
            :show="!!rejectingEntry"
            :entry-id="rejectingEntry?.id ?? null"
            @close="rejectingEntry = null"
        />

        <ConfirmDialog
            :show="!!deletingDocument"
            :title="t('documents.deleteConfirm')"
            :message="deletingDocument?.original_filename ?? ''"
            @confirm="deleteDocument"
            @cancel="deletingDocument = null"
        />
    </AuthenticatedLayout>
</template>
