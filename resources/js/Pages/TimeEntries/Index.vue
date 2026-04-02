<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TimeEntryCard from '@/Components/TimeEntryCard.vue';
import TimeEntryForm from '@/Components/TimeEntryForm.vue';
import EditTimeEntryModal from '@/Components/EditTimeEntryModal.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import HoursSummary from '@/Components/HoursSummary.vue';
import WeeklySummary from '@/Components/WeeklySummary.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import type { TimeEntry, StatusCounts, WeeklyTotal } from '@/types';

const { t } = useI18n();

interface Props {
    entries: TimeEntry[];
    monthTotal: number;
    currentMonth: string;
    statusCounts: StatusCounts;
    weeklyTotals: WeeklyTotal[];
}

const props = defineProps<Props>();

const showForm = ref<boolean>(false);
const editingEntry = ref<TimeEntry | null>(null);
</script>

<template>

    <Head :title="t('timeEntries.title')" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <MonthNavigator :current-month="currentMonth" />

            <HoursSummary :total="monthTotal" :month="currentMonth" />

            <WeeklySummary :weekly-totals="weeklyTotals" />

            <!-- Status Summary -->
            <div v-if="entries.length > 0" class="flex flex-wrap gap-2">
                <span v-if="statusCounts.submitted" class="badge badge-warning gap-1">
                    {{ statusCounts.submitted }} {{ t('status.submitted') }}
                </span>
                <span v-if="statusCounts.approved" class="badge badge-success gap-1">
                    {{ statusCounts.approved }} {{ t('status.approved') }}
                </span>
                <span v-if="statusCounts.rejected" class="badge badge-error gap-1">
                    {{ statusCounts.rejected }} {{ t('status.rejected') }}
                </span>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-4">
                        <h2 class="card-title mb-2 md:mb-0">{{ t('timeEntries.allEntries') }}</h2>
                        <div class="flex gap-2 flex-wrap">
                            <a :href="route('time-entries.export', { month: currentMonth })"
                                class="btn btn-outline btn-sm">
                                {{ t('export.csv') }}
                            </a>
                            <button @click="showForm = !showForm" class="btn btn-primary btn-sm">
                                {{ showForm ? t('common.cancel') : t('timeEntries.addEntry') }}
                            </button>
                        </div>
                    </div>

                    <div v-if="showForm" class="mb-4 p-4 bg-base-200 rounded-lg">
                        <TimeEntryForm @cancel="showForm = false" @success="showForm = false" />
                    </div>

                    <div v-if="entries.length === 0" class="text-center py-8 opacity-60">
                        {{ t('timeEntries.noEntries') }}
                    </div>
                    <div v-else class="space-y-2">
                        <TimeEntryCard v-for="entry in entries" :key="entry.id" :entry="entry"
                            @edit="editingEntry = $event" />
                    </div>
                </div>
            </div>
        </div>
        <EditTimeEntryModal :entry="editingEntry" :show="!!editingEntry" @close="editingEntry = null" />
    </AuthenticatedLayout>
</template>
