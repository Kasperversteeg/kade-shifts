<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    email: string;
    token: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.resetPassword')" />

        <form @submit.prevent="submit" class="space-y-4">
            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ t('auth.email') }}</span>
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    class="input input-bordered"
                    :class="{ 'input-error': form.errors.email }"
                    required
                    autofocus
                    autocomplete="username"
                />
                <label v-if="form.errors.email" class="label">
                    <span class="label-text-alt text-error">{{ form.errors.email }}</span>
                </label>
            </div>

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
                    autocomplete="new-password"
                />
                <label v-if="form.errors.password" class="label">
                    <span class="label-text-alt text-error">{{ form.errors.password }}</span>
                </label>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ t('auth.confirmPassword') }}</span>
                </label>
                <input
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    class="input input-bordered"
                    :class="{ 'input-error': form.errors.password_confirmation }"
                    required
                    autocomplete="new-password"
                />
                <label v-if="form.errors.password_confirmation" class="label">
                    <span class="label-text-alt text-error">{{ form.errors.password_confirmation }}</span>
                </label>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="form.processing"
                >
                    {{ t('auth.resetPasswordButton') }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
