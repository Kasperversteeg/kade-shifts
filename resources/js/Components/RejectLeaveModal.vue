<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface Props {
    show: boolean;
    leaveRequestId: number | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useI18n();

const dialogRef = ref<HTMLDialogElement | null>(null);

const form = useForm({
    reason: '',
});

watch(() => props.show, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
        dialogRef.value?.showModal();
    } else {
        dialogRef.value?.close();
    }
});

const submit = (): void => {
    if (!props.leaveRequestId) return;

    form.post(route('admin.leave.reject', props.leaveRequestId), {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <dialog ref="dialogRef" class="modal" @close="emit('close')">
        <div class="modal-box">
            <h3 class="text-lg font-bold">{{ t('admin.rejectLeave') }}</h3>
            <form @submit.prevent="submit" class="mt-4">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ t('admin.rejectLeaveReason') }}</legend>
                    <textarea
                        v-model="form.reason"
                        class="textarea w-full"
                        :class="{ 'textarea-error': form.errors.reason }"
                        :placeholder="t('admin.rejectionReasonPlaceholder')"
                        rows="3"
                    ></textarea>
                    <p v-if="form.errors.reason" class="label text-error">
                        {{ form.errors.reason }}
                    </p>
                </fieldset>
                <div class="modal-action">
                    <button type="button" class="btn" @click="emit('close')">{{ t('common.cancel') }}</button>
                    <button type="submit" class="btn btn-error" :disabled="form.processing">
                        {{ t('admin.rejectLeave') }}
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="emit('close')">close</button>
        </form>
    </dialog>
</template>
