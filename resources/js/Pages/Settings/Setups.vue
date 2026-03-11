<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    setups: Object,
});

const createForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const save = () => {
    createForm.post(route('settings.setups.store'), { preserveScroll: true, onSuccess: () => createForm.reset('name', 'description') });
};
</script>

<template>
    <Head title="Setups" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Setups</h1>

        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Tambah Setup</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <AppInput v-model="createForm.name" label="Name" :error="createForm.errors.name" />
                <AppInput v-model="createForm.description" label="Description" :error="createForm.errors.description" />
            </div>
            <div class="mt-3">
                <AppButton :loading="createForm.processing" @click="save">Simpan Setup</AppButton>
            </div>
        </div>

        <template v-if="setups?.data?.length">
            <AppTable>
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Description</th>
                        <th class="px-3 py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in setups.data" :key="item.id">
                        <td class="px-3 py-2">{{ item.name }}</td>
                        <td class="px-3 py-2">{{ item.description }}</td>
                        <td class="px-3 py-2">
                            <Link :href="route('settings.setups.destroy', item.id)" method="delete" as="button" class="rounded border border-red-200 px-2 py-1 text-xs text-red-600">Delete</Link>
                        </td>
                    </tr>
                </tbody>
            </AppTable>
            <AppPagination :links="setups.links" />
        </template>

        <AppEmptyState v-else>
            <template #title>Belum ada setup</template>
            Tambahkan setup trading untuk jurnal Anda.
        </AppEmptyState>
    </AppLayout>
</template>
