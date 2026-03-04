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
    <div class="min-h-screen bg-base-200">
        <!-- Admin Navbar -->
        <div class="navbar bg-neutral text-neutral-content pt-[env(safe-area-inset-top)]">
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
                        class="menu menu-sm dropdown-content mt-3 z-1 p-2 shadow bg-neutral rounded-box w-52">
                        <li>
                            <Link :href="route('admin.overview')">{{ $t('adminNav.overview') }}</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.users')">{{ $t('adminNav.users') }}</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.approvals')">{{ $t('adminNav.approvals') }}</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.schedule')">{{ $t('adminNav.schedule') }}</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.leave.index')">{{ $t('adminNav.leaveRequests') }}</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.invitations')">{{ $t('adminNav.invitations') }}</Link>
                        </li>
                        <li class="border-t border-neutral-content/20 mt-2 pt-2">
                            <Link :href="route('dashboard')">{{ $t('adminNav.employeeView') }}</Link>
                        </li>
                    </ul>
                </div>
                <Link :href="route('admin.overview')" class="btn btn-ghost text-xl">
                    Kade Shifts
                    <span class="badge badge-sm badge-outline">Admin</span>
                </Link>
            </div>
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1">
                    <li>
                        <Link :href="route('admin.overview')"
                            :class="{ 'active bg-neutral-focus': route().current('admin.overview') }">
                            {{ $t('adminNav.overview') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.users')"
                            :class="{ 'active bg-neutral-focus': route().current('admin.users') || route().current('admin.user-detail') || route().current('admin.user-edit') || route().current('admin.user-shifts') }">
                            {{ $t('adminNav.users') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.approvals')"
                            :class="{ 'active bg-neutral-focus': route().current('admin.approvals') }">
                            {{ $t('adminNav.approvals') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.schedule')"
                            :class="{ 'active bg-neutral-focus': route().current('admin.schedule') || route().current('admin.shifts.*') }">
                            {{ $t('adminNav.schedule') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.leave.index')"
                            :class="{ 'active bg-neutral-focus': route().current('admin.leave.*') }">
                            {{ $t('adminNav.leaveRequests') }}
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('admin.invitations')"
                            :class="{ 'active bg-neutral-focus': route().current('admin.invitations') }">
                            {{ $t('adminNav.invitations') }}
                        </Link>
                    </li>
                </ul>
            </div>
            <div class="navbar-end gap-1">
                <Link :href="route('dashboard')" class="btn btn-ghost btn-sm gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                    </svg>
                    <span class="hidden sm:inline">{{ $t('adminNav.employeeView') }}</span>
                </Link>
                <ThemeToggle />
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
                        <div class="bg-primary flex items-center justify-center text-primary-content w-10 rounded-full">
                            <span>{{ user.name.charAt(0).toUpperCase() }}</span>
                        </div>
                    </div>
                    <ul tabindex="0"
                        class="menu menu-sm dropdown-content mt-3 z-1 p-2 shadow bg-base-100 text-base-content rounded-box w-52">
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
