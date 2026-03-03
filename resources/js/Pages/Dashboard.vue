<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TimeEntryForm from '@/Components/TimeEntryForm.vue';
import TimeEntryCard from '@/Components/TimeEntryCard.vue';
import EditTimeEntryModal from '@/Components/EditTimeEntryModal.vue';
import HoursSummary from '@/Components/HoursSummary.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import type { TimeEntry, Shift } from '@/types';
import dayjs from 'dayjs';

const { t } = useI18n();

interface Props {
    entries: TimeEntry[];
    monthTotal: number;
    currentMonth: string;
    nextShift: Pick<Shift, 'id' | 'date' | 'start_time' | 'end_time' | 'position' | 'planned_hours'> | null;
}

defineProps<Props>();

const showForm = ref<boolean>(false);
const editingEntry = ref<TimeEntry | null>(null);
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <div class="grid gap-4">
            <!-- Next Shift Card -->
            <div v-if="nextShift" class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">{{ t('schedule.nextShift') }}</h2>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold">{{ dayjs(nextShift.date).format('ddd, MMM D') }}</p>
                            <p>{{ nextShift.start_time }} — {{ nextShift.end_time }}
                                <span class="text-sm opacity-70">({{ nextShift.planned_hours }}{{ t('summary.hoursUnit') }})</span>
                            </p>
                            <p v-if="nextShift.position" class="text-sm opacity-70">{{ nextShift.position }}</p>
                        </div>
                        <Link :href="route('schedule.index')" class="btn btn-outline btn-sm">
                            {{ t('schedule.mySchedule') }}
                        </Link>
                    </div>
                </div>
            </div>

            <HoursSummary :total="monthTotal" :month="currentMonth" />

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">{{ t('dashboard.addHours') }}</h2>
                    <button
                        v-if="!showForm"
                        @click="showForm = true"
                        class="btn btn-primary"
                    >
                        {{ t('dashboard.addNewEntry') }}
                    </button>
                    <TimeEntryForm
                        v-else
                        @cancel="showForm = false"
                        @success="showForm = false"
                    />
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">{{ t('dashboard.recentEntries') }}</h2>
                    <div v-if="entries.length === 0" class="text-center py-8 opacity-60">
                        {{ t('dashboard.noEntries') }}
                    </div>
                    <div v-else class="space-y-2">
                        <TimeEntryCard
                            v-for="entry in entries.slice(0, 5)"
                            :key="entry.id"
                            :entry="entry"
                            @edit="editingEntry = $event"
                        />
                    </div>
                    <Link v-if="entries.length > 0" :href="route('time-entries.index')" class="btn btn-outline btn-sm mt-4">
                        {{ t('dashboard.viewAll') }}
                    </Link>
                </div>
            </div>
        </div>
        <EditTimeEntryModal
            :entry="editingEntry"
            :show="!!editingEntry"
            @close="editingEntry = null"
        />
    </AuthenticatedLayout>
</template>
