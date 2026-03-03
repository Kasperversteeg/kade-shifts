<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface InvitationData {
    email: string;
    token: string;
}

interface Props {
    invitation: InvitationData;
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post(route('invitation.complete', props.invitation.token), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.acceptInvitation')" />

        <h2 class="text-xl font-bold text-center mb-4">{{ t('auth.welcome') }}</h2>
        <p class="text-sm text-center opacity-60 mb-6">
            {{ t('auth.completeRegistration') }} <strong>{{ invitation.email }}</strong>
        </p>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ t('auth.name') }}</span>
                </label>
                <input
                    type="text"
                    v-model="form.name"
                    class="input input-bordered"
                    :class="{ 'input-error': form.errors.name }"
                    required
                    autofocus
                />
                <label v-if="form.errors.name" class="label">
                    <span class="label-text-alt text-error">{{ form.errors.name }}</span>
                </label>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ t('auth.password') }}</span>
                </label>
                <input
                    type="password"
                    v-model="form.password"
                    class="input input-bordered"
                    :class="{ 'input-error': form.errors.password }"
                    required
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
                    type="password"
                    v-model="form.password_confirmation"
                    class="input input-bordered"
                    required
                />
            </div>

            <button
                type="submit"
                class="btn btn-primary w-full"
                :disabled="form.processing"
            >
                {{ form.processing ? t('auth.creatingAccount') : t('auth.createAccount') }}
            </button>
        </form>
    </GuestLayout>
</template>
