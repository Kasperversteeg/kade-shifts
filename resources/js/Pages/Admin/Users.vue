<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';

interface UserRow {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    hourly_rate: string | null;
    contract_type: string | null;
    contract_start_date: string | null;
    contract_end_date: string | null;
    birth_date: string | null;
    start_date: string | null;
    city: string | null;
    profile_completeness: { percentage: number; missing: string[] };
}

interface Props {
    users: UserRow[];
}

const props = defineProps<Props>();
const { t } = useI18n();

const search = ref('');
const filterActive = ref<'all' | 'active' | 'inactive'>('all');

type SortField = 'name' | 'email' | 'contract_type' | 'hourly_rate' | 'city' | 'profile_completeness';
const sortField = ref<SortField>('name');
const sortAsc = ref(true);

const toggleSort = (field: SortField): void => {
    if (sortField.value === field) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortField.value = field;
        sortAsc.value = true;
    }
};

const filteredUsers = computed(() => {
    let result = props.users;

    if (filterActive.value === 'active') {
        result = result.filter(u => u.is_active);
    } else if (filterActive.value === 'inactive') {
        result = result.filter(u => !u.is_active);
    }

    if (search.value) {
        const q = search.value.toLowerCase();
        result = result.filter(u =>
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            (u.city && u.city.toLowerCase().includes(q)) ||
            (u.phone && u.phone.includes(q))
        );
    }

    const sorted = [...result].sort((a, b) => {
        let aVal: string | number;
        let bVal: string | number;

        if (sortField.value === 'profile_completeness') {
            aVal = a.profile_completeness.percentage;
            bVal = b.profile_completeness.percentage;
        } else if (sortField.value === 'hourly_rate') {
            aVal = parseFloat(a.hourly_rate ?? '0');
            bVal = parseFloat(b.hourly_rate ?? '0');
        } else {
            aVal = (a[sortField.value] ?? '').toString().toLowerCase();
            bVal = (b[sortField.value] ?? '').toString().toLowerCase();
        }

        if (aVal < bVal) return sortAsc.value ? -1 : 1;
        if (aVal > bVal) return sortAsc.value ? 1 : -1;
        return 0;
    });

    return sorted;
});

const formatContractType = (type: string | null): string => {
    if (!type) return '—';
    return t(`admin.contractTypes.${type}`, type);
};
</script>

<template>
    <Head :title="t('adminNav.users')" />

    <AdminLayout>
        <div class="space-y-4">
            <h1 class="text-2xl font-bold">{{ t('adminNav.users') }}</h1>

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row gap-2 items-start sm:items-center justify-between">
                <fieldset class="fieldset">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="t('admin.searchUsers')"
                        class="input input-sm w-full sm:w-64"
                    />
                </fieldset>
                <div class="join">
                    <button
                        class="btn btn-xs join-item"
                        :class="{ 'btn-active': filterActive === 'all' }"
                        @click="filterActive = 'all'"
                    >
                        {{ t('admin.filterAll') }} ({{ props.users.length }})
                    </button>
                    <button
                        class="btn btn-xs join-item"
                        :class="{ 'btn-active': filterActive === 'active' }"
                        @click="filterActive = 'active'"
                    >
                        {{ t('admin.filterActive') }} ({{ props.users.filter(u => u.is_active).length }})
                    </button>
                    <button
                        class="btn btn-xs join-item"
                        :class="{ 'btn-active': filterActive === 'inactive' }"
                        @click="filterActive = 'inactive'"
                    >
                        {{ t('admin.filterInactive') }} ({{ props.users.filter(u => !u.is_active).length }})
                    </button>
                </div>
            </div>

            <!-- Data Grid -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th class="cursor-pointer select-none" @click="toggleSort('name')">
                                        {{ t('admin.name') }}
                                        <span v-if="sortField === 'name'" class="ml-1">{{ sortAsc ? '▲' : '▼' }}</span>
                                    </th>
                                    <th class="cursor-pointer select-none" @click="toggleSort('email')">
                                        {{ t('admin.email') }}
                                        <span v-if="sortField === 'email'" class="ml-1">{{ sortAsc ? '▲' : '▼' }}</span>
                                    </th>
                                    <th class="cursor-pointer select-none" @click="toggleSort('contract_type')">
                                        {{ t('admin.contractType') }}
                                        <span v-if="sortField === 'contract_type'" class="ml-1">{{ sortAsc ? '▲' : '▼' }}</span>
                                    </th>
                                    <th class="cursor-pointer select-none text-right" @click="toggleSort('hourly_rate')">
                                        {{ t('admin.hourlyRate') }}
                                        <span v-if="sortField === 'hourly_rate'" class="ml-1">{{ sortAsc ? '▲' : '▼' }}</span>
                                    </th>
                                    <th class="cursor-pointer select-none" @click="toggleSort('city')">
                                        {{ t('admin.city') }}
                                        <span v-if="sortField === 'city'" class="ml-1">{{ sortAsc ? '▲' : '▼' }}</span>
                                    </th>
                                    <th class="cursor-pointer select-none text-center" @click="toggleSort('profile_completeness')">
                                        {{ t('admin.profileStatus') }}
                                        <span v-if="sortField === 'profile_completeness'" class="ml-1">{{ sortAsc ? '▲' : '▼' }}</span>
                                    </th>
                                    <th class="text-center">{{ t('invitations.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in filteredUsers" :key="user.id">
                                    <td>
                                        <Link :href="route('admin.user-detail', user.id)" class="link link-hover link-primary font-medium">
                                            {{ user.name }}
                                        </Link>
                                    </td>
                                    <td class="text-sm">{{ user.email }}</td>
                                    <td>
                                        <span v-if="user.contract_type" class="badge badge-sm badge-outline">
                                            {{ formatContractType(user.contract_type) }}
                                        </span>
                                        <span v-else class="text-sm opacity-40">—</span>
                                    </td>
                                    <td class="text-right">
                                        <template v-if="user.hourly_rate">
                                            &euro;{{ parseFloat(user.hourly_rate).toFixed(2) }}
                                        </template>
                                        <span v-else class="text-sm opacity-40">—</span>
                                    </td>
                                    <td class="text-sm">{{ user.city ?? '—' }}</td>
                                    <td class="text-center">
                                        <div class="flex items-center gap-2 justify-center">
                                            <progress
                                                class="progress w-16"
                                                :class="user.profile_completeness.percentage === 100 ? 'progress-success' : 'progress-warning'"
                                                :value="user.profile_completeness.percentage"
                                                max="100"
                                            ></progress>
                                            <span class="text-xs">{{ user.profile_completeness.percentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge badge-sm"
                                            :class="user.is_active ? 'badge-success' : 'badge-error'"
                                        >
                                            {{ user.is_active ? t('admin.filterActive') : t('admin.inactive') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex gap-1">
                                            <Link :href="route('admin.user-shifts', user.id)" class="btn btn-ghost btn-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                {{ t('admin.viewShifts') }}
                                            </Link>
                                            <Link :href="route('admin.user-edit', user.id)" class="btn btn-ghost btn-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                {{ t('admin.editUser') }}
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="8" class="text-center py-8 opacity-60">
                                        {{ t('admin.noUsersFound') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
