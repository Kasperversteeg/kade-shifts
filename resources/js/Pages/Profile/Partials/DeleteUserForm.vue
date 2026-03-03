<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { nextTick, ref, watch } from 'vue';

const { t } = useI18n();

const confirmingUserDeletion = ref<boolean>(false);
const passwordInput = ref<HTMLInputElement | null>(null);
const dialog = ref<HTMLDialogElement | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = (): void => {
    confirmingUserDeletion.value = true;
};

watch(confirmingUserDeletion, (val) => {
    if (val) {
        dialog.value?.showModal();
        nextTick(() => passwordInput.value?.focus());
    } else {
        dialog.value?.close();
    }
});

const deleteUser = (): void => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = (): void => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium">
                {{ t('profile.deleteAccount') }}
            </h2>
            <p class="mt-1 text-sm opacity-60">
                {{ t('profile.deleteAccountDescription') }}
            </p>
        </header>

        <button class="btn btn-error" @click="confirmUserDeletion">
            {{ t('profile.deleteAccountButton') }}
        </button>

        <dialog ref="dialog" class="modal" @close="closeModal">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-2">
                    {{ t('profile.deleteAccountConfirm') }}
                </h3>

                <p class="text-sm opacity-60">
                    {{ t('profile.deleteAccountConfirmDescription') }}
                </p>

                <fieldset class="fieldset mt-4">
                    <legend class="fieldset-legend">{{ t('auth.password') }}</legend>
                    <input
                        id="password"
                        ref="passwordInput"
                        type="password"
                        v-model="form.password"
                        class="input w-full"
                        :class="{ 'input-error': form.errors.password }"
                        :placeholder="t('auth.password')"
                        @keyup.enter="deleteUser"
                    />
                    <p v-if="form.errors.password" class="label text-error">{{ form.errors.password }}</p>
                </fieldset>

                <div class="modal-action">
                    <button class="btn btn-ghost" @click="closeModal">
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        class="btn btn-error"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ t('profile.deleteAccountButton') }}
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button @click="closeModal">close</button>
            </form>
        </dialog>
    </section>
</template>
