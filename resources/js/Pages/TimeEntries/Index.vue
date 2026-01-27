<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TimeEntryCard from '@/Components/TimeEntryCard.vue';
import TimeEntryForm from '@/Components/TimeEntryForm.vue';
import MonthNavigator from '@/Components/MonthNavigator.vue';
import HoursSummary from '@/Components/HoursSummary.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    entries: Array,
    monthTotal: Number,
    currentMonth: String,
});

const showForm = ref(false);
</script>

<template>
    <Head title="My Hours" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <MonthNavigator :current-month="currentMonth" />

            <HoursSummary :total="monthTotal" :month="currentMonth" />

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="card-title">All Entries</h2>
                        <button
                            @click="showForm = !showForm"
                            class="btn btn-primary btn-sm"
                        >
                            {{ showForm ? 'Cancel' : '+ Add Entry' }}
                        </button>
                    </div>

                    <div v-if="showForm" class="mb-4 p-4 bg-base-200 rounded-lg">
                        <TimeEntryForm
                            @cancel="showForm = false"
                            @success="showForm = false"
                        />
                    </div>

                    <div v-if="entries.length === 0" class="text-center py-8 opacity-60">
                        No entries for this month.
                    </div>
                    <div v-else class="space-y-2">
                        <TimeEntryCard
                            v-for="entry in entries"
                            :key="entry.id"
                            :entry="entry"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
