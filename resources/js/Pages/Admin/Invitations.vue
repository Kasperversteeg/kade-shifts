<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';

defineProps({
    invitations: Array,
});

const form = useForm({
    email: '',
});

const sendInvitation = () => {
    form.post(route('admin.invitations.store'), {
        onSuccess: () => form.reset(),
    });
};

const getStatus = (invitation) => {
    if (invitation.accepted_at) return 'Accepted';
    if (dayjs(invitation.expires_at).isBefore(dayjs())) return 'Expired';
    return 'Pending';
};

const getStatusClass = (invitation) => {
    if (invitation.accepted_at) return 'badge-success';
    if (dayjs(invitation.expires_at).isBefore(dayjs())) return 'badge-error';
    return 'badge-warning';
};
</script>

<template>
    <Head title="Invitations" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">User Invitations</h1>
                <Link :href="route('admin.overview')" class="btn btn-outline btn-sm">
                    Back to Overview
                </Link>
            </div>

            <!-- Send Invitation Form -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Send New Invitation</h2>
                    <form @submit.prevent="sendInvitation" class="flex gap-2">
                        <div class="form-control flex-1">
                            <input
                                type="email"
                                v-model="form.email"
                                placeholder="user@example.com"
                                class="input input-bordered"
                                :class="{ 'input-error': form.errors.email }"
                                required
                            />
                            <label v-if="form.errors.email" class="label">
                                <span class="label-text-alt text-error">{{ form.errors.email }}</span>
                            </label>
                        </div>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Sending...' : 'Send Invitation' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Invitations List -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Sent Invitations</h2>

                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Invited By</th>
                                    <th>Status</th>
                                    <th>Expires</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invitation in invitations" :key="invitation.id">
                                    <td>{{ invitation.email }}</td>
                                    <td>{{ invitation.inviter.name }}</td>
                                    <td>
                                        <span class="badge" :class="getStatusClass(invitation)">
                                            {{ getStatus(invitation) }}
                                        </span>
                                    </td>
                                    <td>{{ dayjs(invitation.expires_at).format('MMM D, YYYY') }}</td>
                                </tr>
                                <tr v-if="invitations.length === 0">
                                    <td colspan="4" class="text-center opacity-60">
                                        No invitations sent yet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
