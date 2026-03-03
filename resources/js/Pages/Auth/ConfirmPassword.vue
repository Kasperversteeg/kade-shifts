<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    password: '',
});

const submit = (): void => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.confirmPasswordTitle')" />

        <div class="mb-4 text-sm opacity-60">
            {{ t('auth.confirmPasswordDescription') }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ t('auth.password') }}</span>
                </label>
                <input
                    id="password"
                    type="password"
                    v-model="form.password"
                    class="input input-bordered"
                    :class="{ 'input-error': form.errors.password }"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <label v-if="form.errors.password" class="label">
                    <span class="label-text-alt text-error">{{ form.errors.password }}</span>
                </label>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="form.processing"
                >
                    {{ t('auth.confirm') }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
