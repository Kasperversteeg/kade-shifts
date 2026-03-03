<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    status?: string;
}

defineProps<Props>();

const form = useForm({
    email: '',
});

const submit = (): void => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.forgotPasswordTitle')" />

        <div class="mb-4 text-sm opacity-60">
            {{ t('auth.forgotPasswordDescription') }}
        </div>

        <div v-if="status" class="alert alert-success mb-4">
            <span class="text-sm">{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('auth.email') }}</legend>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.email }"
                    required
                    autofocus
                    autocomplete="username"
                />
                <p v-if="form.errors.email" class="label text-error">{{ form.errors.email }}</p>
            </fieldset>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="form.processing"
                >
                    {{ t('auth.emailResetLink') }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
