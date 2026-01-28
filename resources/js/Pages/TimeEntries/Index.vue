<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TimeEntryCard from '@/Components/TimeEntryCard.vue';
import TimeEntryForm from '@/Components/TimeEntryForm.vue';
import EditTimeEntryModal from '@/Components/EditTimeEntryModal.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import HoursSummary from '@/Components/HoursSummary.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';

const { t } = useI18n();

defineProps({
    entries: Array,
    monthTotal: Number,
    currentMonth: String,
});

const showForm = ref(false);
const editingEntry = ref(null);
</script>

<template>
    <Head :title="t('timeEntries.title')" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <MonthNavigator :current-month="currentMonth" />

            <HoursSummary :total="monthTotal" :month="currentMonth" />

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="card-title">{{ t('timeEntries.allEntries') }}</h2>
                        <div class="flex gap-2">
                            <a :href="route('time-entries.export', { month: currentMonth })" class="btn btn-outline btn-sm">
                                {{ t('export.csv') }}
                            </a>
                            <button
                                @click="showForm = !showForm"
                                class="btn btn-primary btn-sm"
                            >
                                {{ showForm ? t('common.cancel') : t('timeEntries.addEntry') }}
                            </button>
                        </div>
                    </div>

                    <div v-if="showForm" class="mb-4 p-4 bg-base-200 rounded-lg">
                        <TimeEntryForm
                            @cancel="showForm = false"
                            @success="showForm = false"
                        />
                    </div>

                    <div v-if="entries.length === 0" class="text-center py-8 opacity-60">
                        {{ t('timeEntries.noEntries') }}
                    </div>
                    <div v-else class="space-y-2">
                        <TimeEntryCard
                            v-for="entry in entries"
                            :key="entry.id"
                            :entry="entry"
                            @edit="editingEntry = $event"
                        />
                    </div>
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
