<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    preferences: Object,
});

const form = useForm({
    language: props.preferences?.language || 'en',
});

const submit = () => {
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
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">{{ t('preferences.language') }}</span>
                            </label>
                            <select v-model="form.language" class="select select-bordered w-full">
                                <option value="en">English</option>
                                <option value="nl">Nederlands</option>
                            </select>
                        </div>

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
