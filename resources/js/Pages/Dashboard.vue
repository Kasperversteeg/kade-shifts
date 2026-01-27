<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TimeEntryForm from '@/Components/TimeEntryForm.vue';
import TimeEntryCard from '@/Components/TimeEntryCard.vue';
import HoursSummary from '@/Components/HoursSummary.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    entries: Array,
    monthTotal: Number,
    currentMonth: String,
});

const showForm = ref(false);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="grid gap-4">
            <!-- Summary Card -->
            <HoursSummary :total="monthTotal" :month="currentMonth" />

            <!-- Quick Add -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Add Hours</h2>
                    <button
                        v-if="!showForm"
                        @click="showForm = true"
                        class="btn btn-primary"
                    >
                        + Add New Entry
                    </button>
                    <TimeEntryForm
                        v-else
                        @cancel="showForm = false"
                        @success="showForm = false"
                    />
                </div>
            </div>

            <!-- Recent Entries -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">Recent Entries</h2>
                    <div v-if="entries.length === 0" class="text-center py-8 opacity-60">
                        No entries yet. Add your first time entry above!
                    </div>
                    <div v-else class="space-y-2">
                        <TimeEntryCard
                            v-for="entry in entries.slice(0, 5)"
                            :key="entry.id"
                            :entry="entry"
                        />
                    </div>
                    <Link v-if="entries.length > 0" :href="route('time-entries.index')" class="btn btn-outline btn-sm mt-4">
                        View All Entries
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
