<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-base-200 px-4 sm:px-6"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden rounded-lg bg-base-100 px-6 py-4 shadow-md sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
