<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import type { Team, TeamMember } from '@/types';

interface Props {
    teams: Team[];
    employees: TeamMember[];
}

const props = defineProps<Props>();
const { t } = useI18n();

const search = ref('');
const showCreateModal = ref(false);
const editingTeam = ref<Team | null>(null);

const form = useForm({
    name: '',
    description: '',
    member_ids: [] as number[],
});

const filteredTeams = computed(() => {
    if (!search.value) return props.teams;
    const q = search.value.toLowerCase();
    return props.teams.filter(team =>
        team.name.toLowerCase().includes(q) ||
        (team.description && team.description.toLowerCase().includes(q)) ||
        team.members.some(m => m.name.toLowerCase().includes(q))
    );
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    editingTeam.value = null;
    showCreateModal.value = true;
};

const openEdit = (team: Team) => {
    editingTeam.value = team;
    form.name = team.name;
    form.description = team.description ?? '';
    form.member_ids = team.members.map(m => m.id);
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingTeam.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingTeam.value) {
        form.patch(route('admin.teams.update', editingTeam.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.teams.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteTeam = (team: Team) => {
    if (!confirm(t('teams.deleteConfirm'))) return;
    router.delete(route('admin.teams.destroy', team.id));
};

const toggleMember = (id: number) => {
    const idx = form.member_ids.indexOf(id);
    if (idx === -1) {
        form.member_ids.push(id);
    } else {
        form.member_ids.splice(idx, 1);
    }
};
</script>

<template>
    <Head :title="t('teams.title')" />

    <AdminLayout>
        <div class="space-y-4">
            <h1 class="text-2xl font-bold">{{ t('teams.title') }}</h1>

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row gap-2 items-start sm:items-center justify-between">
                <fieldset class="fieldset">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="t('teams.searchTeams')"
                        class="input input-sm w-full sm:w-64"
                    />
                </fieldset>
                <button class="btn btn-primary btn-sm" @click="openCreate">
                    + {{ t('teams.createTeam') }}
                </button>
            </div>

            <!-- Teams Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="team in filteredTeams"
                    :key="team.id"
                    class="card bg-base-100 shadow-xl"
                >
                    <div class="card-body">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="card-title text-lg">{{ team.name }}</h2>
                                <p v-if="team.description" class="text-sm opacity-60 mt-1">{{ team.description }}</p>
                            </div>
                            <div class="flex gap-1">
                                <button class="btn btn-ghost btn-xs" @click="openEdit(team)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                                <button class="btn btn-ghost btn-xs text-error" @click="deleteTeam(team)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="divider my-1"></div>

                        <div>
                            <div class="text-sm font-medium mb-2">
                                {{ t('teams.members') }}
                                <span class="badge badge-sm badge-ghost ml-1">{{ team.member_count }}</span>
                            </div>
                            <div v-if="team.members.length" class="flex flex-wrap gap-1">
                                <div
                                    v-for="member in team.members"
                                    :key="member.id"
                                    class="badge badge-outline badge-sm"
                                >
                                    {{ member.name }}
                                </div>
                            </div>
                            <p v-else class="text-sm opacity-40">{{ t('teams.noMembers') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="filteredTeams.length === 0" class="card bg-base-100 shadow-xl">
                <div class="card-body text-center py-12 opacity-60">
                    {{ t('teams.noTeams') }}
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <dialog class="modal" :class="{ 'modal-open': showCreateModal }">
            <div class="modal-box">
                <h3 class="text-lg font-bold">
                    {{ editingTeam ? t('teams.editTeam') : t('teams.createTeam') }}
                </h3>
                <form @submit.prevent="submit" class="space-y-4 mt-4">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('teams.name') }}</legend>
                        <input
                            v-model="form.name"
                            type="text"
                            class="input w-full"
                            :class="{ 'input-error': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-error text-sm mt-1">{{ form.errors.name }}</p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('teams.description') }}</legend>
                        <input
                            v-model="form.description"
                            type="text"
                            class="input w-full"
                            :class="{ 'input-error': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-error text-sm mt-1">{{ form.errors.description }}</p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">{{ t('teams.members') }}</legend>
                        <div class="max-h-48 overflow-y-auto space-y-1 border border-base-300 rounded-lg p-2">
                            <label
                                v-for="emp in props.employees"
                                :key="emp.id"
                                class="flex items-center gap-2 cursor-pointer hover:bg-base-200 rounded px-2 py-1"
                            >
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm"
                                    :checked="form.member_ids.includes(emp.id)"
                                    @change="toggleMember(emp.id)"
                                />
                                <span class="text-sm">{{ emp.name }}</span>
                            </label>
                            <p v-if="props.employees.length === 0" class="text-sm opacity-40 px-2">
                                {{ t('admin.noUsersFound') }}
                            </p>
                        </div>
                    </fieldset>

                    <div class="modal-action">
                        <button type="button" class="btn" @click="closeModal">{{ t('common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ form.processing ? t('common.saving') : t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop" @click="closeModal">
                <button>close</button>
            </form>
        </dialog>
    </AdminLayout>
</template>
