<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    show: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    variant?: 'error' | 'warning';
}

const props = withDefaults(defineProps<Props>(), {
    confirmLabel: undefined,
    variant: 'error',
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

const { t } = useI18n();

const dialogRef = ref<HTMLDialogElement | null>(null);

watch(() => props.show, (value) => {
    if (value) {
        dialogRef.value?.showModal();
    } else {
        dialogRef.value?.close();
    }
});

const resolvedConfirmLabel = () => props.confirmLabel ?? t('common.delete');
</script>

<template>
    <dialog ref="dialogRef" class="modal" @close="emit('cancel')">
        <div class="modal-box">
            <h3 class="text-lg font-bold">{{ title }}</h3>
            <p class="py-4">{{ message }}</p>
            <div class="modal-action">
                <button class="btn" @click="emit('cancel')">{{ t('common.cancel') }}</button>
                <button
                    class="btn"
                    :class="variant === 'error' ? 'btn-error' : 'btn-warning'"
                    @click="emit('confirm')"
                >
                    {{ resolvedConfirmLabel() }}
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="emit('cancel')">close</button>
        </form>
    </dialog>
</template>
