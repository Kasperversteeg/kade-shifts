<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { DocumentType } from '@/types';

const { t } = useI18n();

interface Props {
    uploadUrl: string;
    acceptedTypes?: DocumentType[];
    documentType?: DocumentType;
}

const props = withDefaults(defineProps<Props>(), {
    acceptedTypes: () => ['id_front', 'id_back', 'contract_signed', 'other'],
});

const emit = defineEmits<{
    uploaded: [];
}>();

const selectedType = ref<DocumentType>(props.documentType ?? props.acceptedTypes[0]);
const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const progress = ref(0);

const onFileSelected = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    uploading.value = true;
    progress.value = 0;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', selectedType.value);

    router.post(props.uploadUrl, formData, {
        forceFormData: true,
        onProgress: (p) => {
            if (p.percentage) {
                progress.value = p.percentage;
            }
        },
        onSuccess: () => {
            uploading.value = false;
            progress.value = 0;
            if (fileInput.value) fileInput.value.value = '';
            emit('uploaded');
        },
        onError: () => {
            uploading.value = false;
            progress.value = 0;
        },
    });
};
</script>

<template>
    <div class="space-y-3">
        <fieldset v-if="!documentType && acceptedTypes.length > 1" class="fieldset">
            <legend class="fieldset-legend">{{ t('documents.type') }}</legend>
            <select v-model="selectedType" class="select w-full">
                <option v-for="type in acceptedTypes" :key="type" :value="type">
                    {{ t(`documents.types.${type}`) }}
                </option>
            </select>
        </fieldset>

        <fieldset class="fieldset">
            <legend class="fieldset-legend">{{ t('documents.upload') }}</legend>
            <input
                ref="fileInput"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                capture="environment"
                class="file-input w-full"
                :disabled="uploading"
                @change="onFileSelected"
            />
            <p class="label text-xs opacity-60">{{ t('documents.maxSize') }}</p>
        </fieldset>

        <div v-if="uploading">
            <progress class="progress progress-primary w-full" :value="progress" max="100"></progress>
        </div>
    </div>
</template>
