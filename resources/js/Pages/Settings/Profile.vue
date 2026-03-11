<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

const form = useForm({
    name: user?.name || '',
    email: user?.email || '',
    timezone: user?.timezone || 'UTC',
    currency_preference: user?.currency_preference || 'USD',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.patch(route('settings.profile.update'), { preserveScroll: true, onSuccess: () => form.reset('password', 'password_confirmation') });
};
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Profile</h1>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-2">
                <AppInput v-model="form.name" label="Name" :error="form.errors.name" />
                <AppInput v-model="form.email" label="Email" :error="form.errors.email" />
                <AppInput v-model="form.timezone" label="Timezone" :error="form.errors.timezone" />
                <AppInput v-model="form.currency_preference" label="Currency" :error="form.errors.currency_preference" />
                <AppInput v-model="form.password" type="password" label="New Password" :error="form.errors.password" />
                <AppInput v-model="form.password_confirmation" type="password" label="Confirm Password" />
            </div>

            <div class="mt-4">
                <AppButton :loading="form.processing" @click="submit">Update Profile</AppButton>
            </div>
        </div>
    </AppLayout>
</template>
