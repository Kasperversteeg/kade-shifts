<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="Invitation Expired" />

        <div class="text-center space-y-4">
            <h2 class="text-xl font-bold">Invitation Expired</h2>
            <p class="opacity-60">
                This invitation link has expired. Please contact your administrator to receive a new invitation.
            </p>
            <Link :href="route('login')" class="btn btn-primary">
                Go to Login
            </Link>
        </div>
    </GuestLayout>
</template>
