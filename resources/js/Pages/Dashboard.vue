<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TimeEntryForm from '@/Components/TimeEntryForm.vue';
import TimeEntryCard from '@/Components/TimeEntryCard.vue';
import EditTimeEntryModal from '@/Components/EditTimeEntryModal.vue';
import HoursSummary from '@/Components/HoursSummary.vue';
import { Head, Link } from '@inertiajs/vue3';
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
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <div class="grid gap-4">
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
