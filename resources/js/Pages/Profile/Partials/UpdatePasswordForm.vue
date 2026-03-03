<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';

const { t } = useI18n();

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = (): void => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium">
                {{ t('profile.updatePassword') }}
            </h2>
            <p class="mt-1 text-sm opacity-60">
                {{ t('profile.updatePasswordDescription') }}
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-4">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('profile.currentPassword') }}</legend>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    type="password"
                    v-model="form.current_password"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.current_password }"
                    autocomplete="current-password"
                />
                <p v-if="form.errors.current_password" class="label text-error">{{ form.errors.current_password }}</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('profile.newPassword') }}</legend>
                <input
                    id="password"
                    ref="passwordInput"
                    type="password"
                    v-model="form.password"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.password }"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password" class="label text-error">{{ form.errors.password }}</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('auth.confirmPassword') }}</legend>
                <input
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.password_confirmation }"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password_confirmation" class="label text-error">{{ form.errors.password_confirmation }}</p>
            </fieldset>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    {{ t('common.save') }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm opacity-60">
                        {{ t('profile.saved') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
