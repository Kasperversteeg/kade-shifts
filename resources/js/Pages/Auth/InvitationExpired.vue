<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.invitationExpired')" />

        <div class="text-center space-y-4">
            <h2 class="text-xl font-bold">{{ t('auth.invitationExpired') }}</h2>
            <p class="opacity-60">
                {{ t('auth.invitationExpiredDescription') }}
            </p>
            <Link :href="route('login')" class="btn btn-primary">
                {{ t('auth.goToLogin') }}
            </Link>
        </div>
    </GuestLayout>
</template>
