<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    status?: string;
}

const props = defineProps<Props>();

const form = useForm({});

const submit = (): void => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed<boolean>(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.verifyEmailTitle')" />

        <div class="mb-4 text-sm opacity-60">
            {{ t('auth.verifyEmailDescription') }}
        </div>

        <div v-if="verificationLinkSent" class="alert alert-success mb-4">
            <span class="text-sm">{{ t('auth.verificationSent') }}</span>
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="form.processing"
                >
                    {{ t('auth.resendVerification') }}
                </button>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="btn btn-ghost btn-sm"
                >
                    {{ t('auth.logout') }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
