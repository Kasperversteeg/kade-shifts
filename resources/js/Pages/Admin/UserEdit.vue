<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface ProfileCompleteness {
    percentage: number;
    missing: string[];
}

interface UserProfile {
    id: number;
    name: string;
    email: string;
    hourly_rate: number | null;
    contract_type: string | null;
    contract_start_date: string | null;
    contract_end_date: string | null;
    birth_date: string | null;
    start_date: string | null;
    bsn: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    postal_code: string | null;
    profile_completeness: ProfileCompleteness;
}

interface Props {
    user: UserProfile;
}

const props = defineProps<Props>();
const { t } = useI18n();

const form = useForm({
    hourly_rate: props.user.hourly_rate ?? '',
    contract_type: props.user.contract_type ?? '',
    contract_start_date: props.user.contract_start_date ?? '',
    contract_end_date: props.user.contract_end_date ?? '',
    birth_date: props.user.birth_date ?? '',
    start_date: props.user.start_date ?? '',
    bsn: props.user.bsn ?? '',
    phone: props.user.phone ?? '',
    address: props.user.address ?? '',
    city: props.user.city ?? '',
    postal_code: props.user.postal_code ?? '',
});

const submit = (): void => {
    form.patch(route('admin.user-update', props.user.id));
};
</script>

<template>
    <Head :title="`${t('admin.editUser')} - ${user.name}`" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <Link :href="route('admin.user-detail', user.id)" class="btn btn-ghost btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    {{ t('admin.back') }}
                </Link>
                <div>
                    <h1 class="text-2xl font-bold">{{ t('admin.editUser') }}</h1>
                    <p class="text-sm opacity-60">{{ user.name }} — {{ user.email }}</p>
                </div>
            </div>

            <!-- Profile Completeness -->
            <div class="card bg-base-100 shadow">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium">
                            {{ user.profile_completeness.percentage === 100
                                ? t('admin.profileComplete')
                                : t('admin.profileIncomplete') }}
                        </span>
                        <span class="badge" :class="user.profile_completeness.percentage === 100 ? 'badge-success' : 'badge-warning'">
                            {{ user.profile_completeness.percentage }}%
                        </span>
                    </div>
                    <progress
                        class="progress w-full"
                        :class="user.profile_completeness.percentage === 100 ? 'progress-success' : 'progress-warning'"
                        :value="user.profile_completeness.percentage"
                        max="100"
                    ></progress>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">{{ t('admin.userProfile') }}</h2>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Contract & Financial -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.hourlyRate') }}</legend>
                                <input
                                    type="number"
                                    v-model="form.hourly_rate"
                                    step="0.01"
                                    min="0"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.hourly_rate }"
                                    placeholder="0.00"
                                />
                                <p v-if="form.errors.hourly_rate" class="label text-error">
                                    {{ form.errors.hourly_rate }}
                                </p>
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.contractType') }}</legend>
                                <select
                                    v-model="form.contract_type"
                                    class="select w-full"
                                    :class="{ 'select-error': form.errors.contract_type }"
                                >
                                    <option value="">—</option>
                                    <option value="vast">{{ t('admin.contractTypes.vast') }}</option>
                                    <option value="flex">{{ t('admin.contractTypes.flex') }}</option>
                                    <option value="oproep">{{ t('admin.contractTypes.oproep') }}</option>
                                </select>
                                <p v-if="form.errors.contract_type" class="label text-error">
                                    {{ form.errors.contract_type }}
                                </p>
                            </fieldset>
                        </div>

                        <!-- Contract Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.contractStart') }}</legend>
                                <input
                                    type="date"
                                    v-model="form.contract_start_date"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.contract_start_date }"
                                />
                                <p v-if="form.errors.contract_start_date" class="label text-error">
                                    {{ form.errors.contract_start_date }}
                                </p>
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.contractEnd') }}</legend>
                                <input
                                    type="date"
                                    v-model="form.contract_end_date"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.contract_end_date }"
                                />
                                <p v-if="form.errors.contract_end_date" class="label text-error">
                                    {{ form.errors.contract_end_date }}
                                </p>
                            </fieldset>
                        </div>

                        <!-- Personal Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.birthDate') }}</legend>
                                <input
                                    type="date"
                                    v-model="form.birth_date"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.birth_date }"
                                />
                                <p v-if="form.errors.birth_date" class="label text-error">
                                    {{ form.errors.birth_date }}
                                </p>
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.startDate') }}</legend>
                                <input
                                    type="date"
                                    v-model="form.start_date"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.start_date }"
                                />
                                <p v-if="form.errors.start_date" class="label text-error">
                                    {{ form.errors.start_date }}
                                </p>
                            </fieldset>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.bsn') }}</legend>
                                <input
                                    type="password"
                                    v-model="form.bsn"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.bsn }"
                                    maxlength="9"
                                    placeholder="*********"
                                />
                                <p v-if="form.errors.bsn" class="label text-error">
                                    {{ form.errors.bsn }}
                                </p>
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.phone') }}</legend>
                                <input
                                    type="tel"
                                    v-model="form.phone"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.phone }"
                                />
                                <p v-if="form.errors.phone" class="label text-error">
                                    {{ form.errors.phone }}
                                </p>
                            </fieldset>
                        </div>

                        <!-- Address -->
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('admin.address') }}</legend>
                            <input
                                type="text"
                                v-model="form.address"
                                class="input w-full"
                                :class="{ 'input-error': form.errors.address }"
                            />
                            <p v-if="form.errors.address" class="label text-error">
                                {{ form.errors.address }}
                            </p>
                        </fieldset>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.city') }}</legend>
                                <input
                                    type="text"
                                    v-model="form.city"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.city }"
                                />
                                <p v-if="form.errors.city" class="label text-error">
                                    {{ form.errors.city }}
                                </p>
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">{{ t('admin.postalCode') }}</legend>
                                <input
                                    type="text"
                                    v-model="form.postal_code"
                                    class="input w-full"
                                    :class="{ 'input-error': form.errors.postal_code }"
                                    maxlength="10"
                                />
                                <p v-if="form.errors.postal_code" class="label text-error">
                                    {{ form.errors.postal_code }}
                                </p>
                            </fieldset>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                                {{ form.processing ? t('common.saving') : t('common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
