<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import type { PageProps, User } from '@/types';

interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error';
}

const page = usePage<PageProps>();
const user = computed<User>(() => page.props.auth.user);
const isAdmin = computed<boolean>(() => user.value?.role === 'admin');

const toasts = ref<Toast[]>([]);

function addToast(message: string, type: 'success' | 'error'): void {
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
    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />

        <!-- Main content area -->
        <div class="drawer-content flex flex-col min-h-screen bg-base-200">

            <!-- Top navbar — visible only on mobile/tablet -->
            <div class="navbar bg-base-100 shadow-lg lg:hidden pt-[env(safe-area-inset-top)]">
                <div class="navbar-start">
                    <label for="app-drawer" class="btn btn-ghost btn-square">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="navbar-center">
                    <Link :href="route('dashboard')" class="text-xl font-bold">Kade Shifts</Link>
                </div>
                <div class="navbar-end gap-1">
                    <ThemeToggle />
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
                            <div class="bg-primary flex items-center justify-center text-primary-content w-10 rounded-full">
                                <span>{{ user.name.charAt(0).toUpperCase() }}</span>
                            </div>
                        </div>
                        <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-50 p-2 shadow bg-base-100 rounded-box w-52">
                            <li class="menu-title">{{ user.name }}</li>
                            <li><Link :href="route('profile.edit')">{{ $t('nav.profile') }}</Link></li>
                            <li><Link :href="route('preferences.edit')">{{ $t('nav.preferences') }}</Link></li>
                            <li><Link :href="route('logout')" method="post" as="button">{{ $t('nav.logOut') }}</Link></li>
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

            <!-- Page content -->
            <main class="flex-1 p-4 lg:p-6">
                <slot />
            </main>
        </div>

        <!-- Sidebar drawer -->
        <div class="drawer-side z-40">
            <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside class="bg-base-100 text-base-content w-64 min-h-full flex flex-col border-r border-base-300">

                <!-- Brand -->
                <div class="p-4 pb-2">
                    <Link :href="route('dashboard')" class="text-xl font-bold">Kade Shifts</Link>
                </div>

                <!-- Nav links -->
                <ul class="menu flex-1 px-2 py-2 gap-0.5">
                    <li>
                        <Link :href="route('dashboard')"
                            :class="{ 'active bg-primary/10 text-primary font-semibold': route().current('dashboard') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            {{ $t('nav.dashboard') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('time-entries.index')"
                            :class="{ 'active bg-primary/10 text-primary font-semibold': route().current('time-entries.*') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $t('nav.myHours') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('leave.index')"
                            :class="{ 'active bg-primary/10 text-primary font-semibold': route().current('leave.*') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $t('nav.leave') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('schedule.index')"
                            :class="{ 'active bg-primary/10 text-primary font-semibold': route().current('schedule.*') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            {{ $t('nav.schedule') }}
                        </Link>
                    </li>
                    <li v-if="isAdmin">
                        <Link :href="route('admin.overview')"
                            :class="{ 'active bg-primary/10 text-primary font-semibold': route().current('admin.*') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            {{ $t('nav.admin') }}
                        </Link>
                    </li>
                </ul>

                <!-- Bottom section: theme toggle + user -->
                <div class="border-t border-base-300 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm opacity-70">{{ $t('nav.theme') }}</span>
                        <ThemeToggle />
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content w-8 rounded-full flex items-center justify-center">
                                <span class="text-sm">{{ user.name.charAt(0).toUpperCase() }}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate">{{ user.name }}</div>
                        </div>
                    </div>
                    <ul class="menu menu-sm p-0 gap-0.5">
                        <li><Link :href="route('profile.edit')">{{ $t('nav.profile') }}</Link></li>
                        <li><Link :href="route('preferences.edit')">{{ $t('nav.preferences') }}</Link></li>
                        <li><Link :href="route('logout')" method="post" as="button">{{ $t('nav.logOut') }}</Link></li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</template>
