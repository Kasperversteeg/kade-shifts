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
        <input id="admin-drawer" type="checkbox" class="drawer-toggle" />

        <!-- Main content area -->
        <div class="drawer-content flex flex-col min-h-screen bg-base-200">

            <!-- Top navbar — visible only on mobile/tablet -->
            <div class="navbar bg-neutral text-neutral-content lg:hidden pt-[env(safe-area-inset-top)]">
                <div class="navbar-start">
                    <label for="admin-drawer" class="btn btn-ghost btn-square">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="navbar-center">
                    <Link :href="route('admin.overview')" class="text-xl font-bold">
                        Kade Shifts
                        <span class="badge badge-sm badge-outline">Admin</span>
                    </Link>
                </div>
                <div class="navbar-end gap-1">
                    <ThemeToggle />
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
                            <div class="bg-primary flex items-center justify-center text-primary-content w-10 rounded-full">
                                <span>{{ user.name.charAt(0).toUpperCase() }}</span>
                            </div>
                        </div>
                        <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-50 p-2 shadow bg-base-100 text-base-content rounded-box w-52">
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
            <label for="admin-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside class="bg-neutral text-neutral-content w-64 min-h-full flex flex-col">

                <!-- Brand -->
                <div class="p-4 pb-2">
                    <Link :href="route('admin.overview')" class="text-xl font-bold flex items-center gap-2">
                        Kade Shifts
                        <span class="badge badge-sm badge-outline">Admin</span>
                    </Link>
                </div>

                <!-- Nav links -->
                <ul class="menu flex-1 py-2 gap-0.5 text-neutral-content [&_li>a]:rounded-none">
                    <li>
                        <Link :href="route('admin.overview')"
                            :class="{ 'active bg-base-content/10': route().current('admin.overview') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            {{ $t('adminNav.overview') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.users')"
                            :class="{ 'active bg-base-content/10': route().current('admin.users') || route().current('admin.user-detail') || route().current('admin.user-edit') || route().current('admin.user-shifts') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            {{ $t('adminNav.users') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.teams')"
                            :class="{ 'active bg-base-content/10': route().current('admin.teams') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            {{ $t('adminNav.teams') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.schedule')"
                            :class="{ 'active bg-base-content/10': route().current('admin.schedule') || route().current('admin.shifts.*') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            {{ $t('adminNav.schedule') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.leave.index')"
                            :class="{ 'active bg-base-content/10': route().current('admin.leave.*') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $t('adminNav.leaveRequests') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.invitations')"
                            :class="{ 'active bg-base-content/10': route().current('admin.invitations') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ $t('adminNav.invitations') }}
                        </Link>
                    </li>
                </ul>

                <!-- Bottom section -->
                <div class="border-t border-neutral-content/20 p-4 space-y-3">
                    <!-- Employee view link -->
                    <Link :href="route('dashboard')" class="btn btn-ghost btn-sm w-full justify-start gap-2 text-neutral-content">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                        </svg>
                        {{ $t('adminNav.employeeView') }}
                    </Link>

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
                    <ul class="menu menu-sm p-0 gap-0.5 [&_li>a]:rounded-none [&_li>button]:rounded-none">
                        <li><Link :href="route('profile.edit')">{{ $t('nav.profile') }}</Link></li>
                        <li><Link :href="route('preferences.edit')">{{ $t('nav.preferences') }}</Link></li>
                        <li><Link :href="route('logout')" method="post" as="button">{{ $t('nav.logOut') }}</Link></li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</template>
