<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import DocumentUpload from '@/Components/DocumentUpload.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { Document } from '@/types';

const { t } = useI18n();

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    documents: Document[];
}

defineProps<Props>();

const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};
</script>

<template>
    <Head :title="t('profile.title')" />

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>
            </div>

            <!-- My Documents -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">{{ t('documents.myDocuments') }}</h2>

                    <div v-if="documents.length > 0" class="space-y-2 mt-2">
                        <div
                            v-for="doc in documents"
                            :key="doc.id"
                            class="flex items-center justify-between p-2 bg-base-200 rounded-lg"
                        >
                            <div>
                                <span class="badge badge-sm mr-2">{{ t(`documents.types.${doc.type}`) }}</span>
                                <span class="text-sm">{{ doc.original_filename }}</span>
                                <span class="text-xs opacity-60 ml-2">{{ formatFileSize(doc.file_size) }}</span>
                            </div>
                            <a :href="route('documents.download', doc.id)" class="btn btn-ghost btn-xs">
                                {{ t('documents.download') }}
                            </a>
                        </div>
                    </div>
                    <p v-else class="text-sm opacity-60 mt-2">{{ t('documents.noDocuments') }}</p>

                    <div class="divider"></div>
                    <DocumentUpload
                        :upload-url="route('documents.store-own')"
                        :accepted-types="['id_front', 'id_back']"
                    />
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <UpdatePasswordForm />
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
