<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { PageProps } from '@/types';

const { t } = useI18n();

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage<PageProps>();
const user = page.props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone ?? '',
    birth_date: user.birth_date ?? '',
    address: user.address ?? '',
    city: user.city ?? '',
    postal_code: user.postal_code ?? '',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium">
                {{ t('profile.information') }}
            </h2>
            <p class="mt-1 text-sm opacity-60">
                {{ t('profile.informationDescription') }}
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-4"
        >
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('profile.name') }}</legend>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.name }"
                    required
                    autofocus
                    autocomplete="name"
                />
                <p v-if="form.errors.name" class="label text-error">{{ form.errors.name }}</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('auth.email') }}</legend>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.email }"
                    required
                    autocomplete="username"
                />
                <p v-if="form.errors.email" class="label text-error">{{ form.errors.email }}</p>
            </fieldset>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm">
                    {{ t('profile.emailUnverified') }}
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="link link-hover link-primary text-sm"
                    >
                        {{ t('profile.resendVerification') }}
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="alert alert-success mt-2"
                >
                    <span class="text-sm">{{ t('profile.verificationSent') }}</span>
                </div>
            </div>

            <div class="divider">{{ t('profile.personalDetails') }}</div>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('profile.phone') }}</legend>
                <input
                    type="tel"
                    v-model="form.phone"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.phone }"
                    autocomplete="tel"
                />
                <p v-if="form.errors.phone" class="label text-error">{{ form.errors.phone }}</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('profile.birthDate') }}</legend>
                <input
                    type="date"
                    v-model="form.birth_date"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.birth_date }"
                />
                <p v-if="form.errors.birth_date" class="label text-error">{{ form.errors.birth_date }}</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ t('profile.address') }}</legend>
                <input
                    type="text"
                    v-model="form.address"
                    class="input w-full"
                    :class="{ 'input-error': form.errors.address }"
                    autocomplete="street-address"
                />
                <p v-if="form.errors.address" class="label text-error">{{ form.errors.address }}</p>
            </fieldset>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ t('profile.postalCode') }}</legend>
                    <input
                        type="text"
                        v-model="form.postal_code"
                        class="input w-full"
                        :class="{ 'input-error': form.errors.postal_code }"
                        maxlength="10"
                        autocomplete="postal-code"
                    />
                    <p v-if="form.errors.postal_code" class="label text-error">{{ form.errors.postal_code }}</p>
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ t('profile.city') }}</legend>
                    <input
                        type="text"
                        v-model="form.city"
                        class="input w-full"
                        :class="{ 'input-error': form.errors.city }"
                        autocomplete="address-level2"
                    />
                    <p v-if="form.errors.city" class="label text-error">{{ form.errors.city }}</p>
                </fieldset>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    {{ t('common.save') }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm opacity-60">
                        {{ t('profile.saved') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
