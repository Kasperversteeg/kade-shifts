<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role === 'admin');

const toasts = ref([]);

function addToast(message, type) {
    const id = Date.now();
    toasts.value.push({ id, message, type });
    setTimeout(() => {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }, 4000);
}

watch(() => page.props.flash, (flash) => {
    if (flash?.success) addToast(flash.success, 'success');
    if (flash?.error) addToast(flash.error, 'error');
}, { immediate: true });
</script>

<template>
    <div class="min-h-screen bg-base-200">
        <!-- Navbar -->
        <div class="navbar bg-base-100 shadow-lg pt-[env(safe-area-inset-top)]">
            <div class="navbar-start">
                <div class="dropdown">
                    <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </div>
                    <ul tabindex="0"
                        class="menu menu-sm dropdown-content mt-3 z-1 p-2 shadow bg-base-100 rounded-box w-52">
                        <li>
                            <Link :href="route('dashboard')">{{ $t('nav.dashboard') }}</Link>
                        </li>
                        <li>
                            <Link :href="route('time-entries.index')">{{ $t('nav.myHours') }}</Link>
                        </li>
                        <li v-if="isAdmin">
                            <Link :href="route('admin.overview')">{{ $t('nav.admin') }}</Link>
                        </li>
                    </ul>
                </div>
                <Link :href="route('dashboard')" class="btn btn-ghost text-xl">
                    Kade Shifts
                </Link>
            </div>
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1">
                    <li>
                        <Link :href="route('dashboard')" :class="{ 'active': route().current('dashboard') }">
                            {{ $t('nav.dashboard') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('time-entries.index')"
                            :class="{ 'active': route().current('time-entries.*') }">
                            {{ $t('nav.myHours') }}
                        </Link>
                    </li>
                    <li v-if="isAdmin">
                        <Link :href="route('admin.overview')" :class="{ 'active': route().current('admin.*') }">
                            {{ $t('nav.admin') }}
                        </Link>
                    </li>
                </ul>
            </div>
            <div class="navbar-end gap-1">
                <ThemeToggle />
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
                        <div class="bg-primary flex items-center justify-center text-primary-content w-10 rounded-full">
                            <span>{{ user.name.charAt(0).toUpperCase() }}</span>
                        </div>
                    </div>
                    <ul tabindex="0"
                        class="menu menu-sm dropdown-content mt-3 z-1 p-2 shadow bg-base-100 rounded-box w-52">
                        <li class="menu-title">{{ user.name }}</li>
                        <li>
                            <Link :href="route('profile.edit')">{{ $t('nav.profile') }}</Link>
                        </li>
                        <li>
                            <Link :href="route('preferences.edit')">{{ $t('nav.preferences') }}</Link>
                        </li>
                        <li>
                            <Link :href="route('logout')" method="post" as="button">{{ $t('nav.logOut') }}</Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Toast Messages -->
        <div class="toast toast-end toast-top z-50">
            <TransitionGroup name="toast">
                <div v-for="toast in toasts" :key="toast.id" class="alert shadow-lg cursor-pointer"
                    :class="toast.type === 'success' ? 'alert-success' : 'alert-error'"
                    @click="toasts = toasts.filter(t => t.id !== toast.id)">
                    <span>{{ toast.message }}</span>
                </div>
            </TransitionGroup>
        </div>

        <!-- Page Content -->
        <main class="container mx-auto p-4">
            <slot />
        </main>
    </div>
</template>
