<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RejectEntryModal from '@/Components/RejectEntryModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import dayjs from 'dayjs';
import type { TimeEntry, User } from '@/types';

const { t } = useI18n();

interface UserGroup {
    user: Pick<User, 'id' | 'name' | 'email'>;
    entries: TimeEntry[];
    total_hours: number;
}

interface Props {
    groups: UserGroup[];
    totalPending: number;
    allEntryIds: number[];
}

const props = defineProps<Props>();

const selectedIds = ref<Set<number>>(new Set());
const collapsedUsers = ref<Set<number>>(new Set());
const rejectingEntryId = ref<number | null>(null);

const selectedCount = computed(() => selectedIds.value.size);

const allSelected = computed(() =>
    props.allEntryIds.length > 0 && props.allEntryIds.every(id => selectedIds.value.has(id))
);

const isUserAllSelected = (group: UserGroup): boolean => {
    return group.entries.length > 0 && group.entries.every(e => selectedIds.value.has(e.id));
};

const isUserPartialSelected = (group: UserGroup): boolean => {
    const some = group.entries.some(e => selectedIds.value.has(e.id));
    const all = isUserAllSelected(group);
    return some && !all;
};

const toggleAll = (): void => {
    if (allSelected.value) {
        selectedIds.value = new Set();
    } else {
        selectedIds.value = new Set(props.allEntryIds);
    }
};

const toggleUser = (group: UserGroup): void => {
    const ids = group.entries.map(e => e.id);
    if (isUserAllSelected(group)) {
        ids.forEach(id => selectedIds.value.delete(id));
    } else {
        ids.forEach(id => selectedIds.value.add(id));
    }
    selectedIds.value = new Set(selectedIds.value);
};

const toggleEntry = (id: number): void => {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id);
    } else {
        selectedIds.value.add(id);
    }
    selectedIds.value = new Set(selectedIds.value);
};

const toggleCollapse = (userId: number): void => {
    if (collapsedUsers.value.has(userId)) {
        collapsedUsers.value.delete(userId);
    } else {
        collapsedUsers.value.add(userId);
    }
    collapsedUsers.value = new Set(collapsedUsers.value);
};

const approveSelected = (): void => {
    if (selectedCount.value === 0) return;
    router.post(route('admin.entries.bulk-approve'), {
        entry_ids: Array.from(selectedIds.value),
    }, {
        onSuccess: () => { selectedIds.value = new Set(); },
    });
};

const approveSingle = (id: number): void => {
    router.post(route('admin.entries.approve', id));
};

const formatDate = (date: string): string => dayjs(date).format('dd D MMM');
const formatTime = (time: string): string => time?.substring(0, 5);
</script>

<template>
    <Head :title="t('admin.approvals')" />

    <AdminLayout>
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-bold">{{ t('admin.approvals') }}</h1>
                    <p class="text-sm opacity-60">{{ totalPending }} {{ t('admin.pendingApprovalCount') }}</p>
                </div>
                <div v-if="totalPending > 0" class="flex gap-2">
                    <button
                        v-if="selectedCount > 0"
                        @click="approveSelected"
                        class="btn btn-success btn-sm"
                    >
                        {{ t('admin.approveSelected') }} ({{ selectedCount }})
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="groups.length === 0" class="card bg-base-100 shadow-xl">
                <div class="card-body text-center py-16">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto opacity-30 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="opacity-60">{{ t('admin.noPendingApprovals') }}</p>
                </div>
            </div>

            <!-- Select all bar -->
            <div v-if="totalPending > 0" class="flex items-center gap-3 px-1">
                <input
                    type="checkbox"
                    class="checkbox checkbox-sm"
                    :checked="allSelected"
                    :indeterminate="selectedCount > 0 && !allSelected"
                    @change="toggleAll"
                />
                <span class="text-sm opacity-70">{{ t('admin.selectAll') }}</span>
            </div>

            <!-- User groups -->
            <div v-for="group in groups" :key="group.user.id" class="card bg-base-100 shadow">
                <!-- User header row -->
                <div
                    class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-base-200/50 transition-colors border-b border-base-200"
                    @click="toggleCollapse(group.user.id)"
                >
                    <input
                        type="checkbox"
                        class="checkbox checkbox-sm"
                        :checked="isUserAllSelected(group)"
                        :indeterminate="isUserPartialSelected(group)"
                        @click.stop
                        @change="toggleUser(group)"
                    />
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 opacity-50 transition-transform"
                        :class="{ '-rotate-90': collapsedUsers.has(group.user.id) }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <Link
                            :href="route('admin.user-detail', group.user.id)"
                            class="font-semibold link link-hover link-primary"
                            @click.stop
                        >
                            {{ group.user.name }}
                        </Link>
                        <span class="text-sm opacity-50 ml-2 hidden sm:inline">{{ group.user.email }}</span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="badge badge-ghost">{{ group.entries.length }} {{ group.entries.length === 1 ? t('admin.entry') : t('admin.entries') }}</span>
                        <span class="badge badge-primary font-semibold">{{ group.total_hours }}{{ t('summary.hoursUnit') }}</span>
                    </div>
                </div>

                <!-- Entries table -->
                <div v-show="!collapsedUsers.has(group.user.id)">
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr class="text-xs uppercase opacity-50">
                                    <th class="w-10"></th>
                                    <th>{{ t('timeEntries.date') }}</th>
                                    <th>{{ t('admin.shift') }}</th>
                                    <th class="text-right">{{ t('timeEntries.breakMinutes') }}</th>
                                    <th class="text-right">{{ t('admin.hours') }}</th>
                                    <th>{{ t('admin.notesColumn') }}</th>
                                    <th class="text-right w-32"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="entry in group.entries"
                                    :key="entry.id"
                                    class="hover"
                                    :class="{ 'bg-primary/5': selectedIds.has(entry.id) }"
                                >
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="checkbox checkbox-sm"
                                            :checked="selectedIds.has(entry.id)"
                                            @change="toggleEntry(entry.id)"
                                        />
                                    </td>
                                    <td class="font-medium whitespace-nowrap">{{ formatDate(entry.date) }}</td>
                                    <td class="whitespace-nowrap">
                                        <span class="font-mono text-sm">{{ formatTime(entry.shift_start) }}</span>
                                        <span class="opacity-40 mx-1">&ndash;</span>
                                        <span class="font-mono text-sm">{{ formatTime(entry.shift_end) }}</span>
                                    </td>
                                    <td class="text-right">
                                        <span v-if="entry.break_minutes > 0" class="text-sm opacity-70">{{ entry.break_minutes }}min</span>
                                        <span v-else class="text-sm opacity-30">&mdash;</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="font-semibold">{{ entry.total_hours }}{{ t('summary.hoursUnit') }}</span>
                                    </td>
                                    <td class="max-w-48 truncate text-sm opacity-60">{{ entry.notes || '' }}</td>
                                    <td class="text-right">
                                        <div class="flex gap-1 justify-end">
                                            <button
                                                @click="approveSingle(entry.id)"
                                                class="btn btn-success btn-xs"
                                            >
                                                {{ t('admin.approve') }}
                                            </button>
                                            <button
                                                @click="rejectingEntryId = entry.id"
                                                class="btn btn-ghost btn-xs text-error"
                                            >
                                                {{ t('admin.reject') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <RejectEntryModal
            :show="rejectingEntryId !== null"
            :entry-id="rejectingEntryId"
            @close="rejectingEntryId = null"
        />
    </AdminLayout>
</template>
