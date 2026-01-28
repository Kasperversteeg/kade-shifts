<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import HoursSummary from '@/Components/HoursSummary.vue';
import TimeEntryCard from '@/Components/TimeEntryCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    user: Object,
    entries: Array,
    monthTotal: Number,
    currentMonth: String,
});
</script>

<template>
    <Head :title="`${user.name}`" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <Link :href="route('admin.overview')" class="btn btn-ghost btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    {{ t('admin.back') }}
                </Link>
                <div>
                    <h1 class="text-2xl font-bold">{{ user.name }}</h1>
                    <p class="text-sm opacity-60">{{ user.email }}</p>
                </div>
            </div>

            <MonthNavigator :current-month="currentMonth" />

            <HoursSummary :total="monthTotal" :month="currentMonth" />

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">{{ t('admin.entries') }}</h2>

                    <div v-if="entries.length === 0" class="text-center py-8 opacity-60">
                        {{ t('timeEntries.noEntries') }}
                    </div>
                    <div v-else class="space-y-2">
                        <TimeEntryCard
                            v-for="entry in entries"
                            :key="entry.id"
                            :entry="entry"
                            readonly
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
