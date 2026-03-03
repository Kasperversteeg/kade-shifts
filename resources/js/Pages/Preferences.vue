<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { UserPreferences } from '@/types';

const { t, locale } = useI18n();

interface Props {
    preferences: UserPreferences;
}

const props = defineProps<Props>();

const form = useForm({
    language: props.preferences?.language || 'en',
});

const submit = (): void => {
    form.patch(route('preferences.update'), {
        onSuccess: () => {
            locale.value = form.language;
        },
    });
};
</script>

<template>
    <Head :title="t('preferences.title')" />

    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">{{ t('preferences.title') }}</h2>

                    <form @submit.prevent="submit" class="space-y-4">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ t('preferences.language') }}</legend>
                            <select v-model="form.language" class="select w-full">
                                <option value="en">English</option>
                                <option value="nl">Nederlands</option>
                            </select>
                        </fieldset>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ t('preferences.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
