<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    instruments: Object,
});

const createForm = useForm({
    symbol: '',
    name: '',
    category: 'forex',
    pip_value: '',
    is_active: true,
});

const save = () => {
    createForm.post(route('settings.instruments.store'), { preserveScroll: true, onSuccess: () => createForm.reset('symbol', 'name', 'pip_value') });
};
</script>

<template>
    <Head title="Instruments" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Instruments</h1>

        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Tambah Instrument</h2>
            <div class="grid gap-3 md:grid-cols-4">
                <AppInput v-model="createForm.symbol" label="Symbol" :error="createForm.errors.symbol" />
                <AppInput v-model="createForm.name" label="Name" :error="createForm.errors.name" />
                <AppSelect v-model="createForm.category" label="Category" :options="[
                    { value: 'forex', label: 'Forex' },
                    { value: 'commodity', label: 'Commodity' },
                    { value: 'crypto', label: 'Crypto' },
                    { value: 'index', label: 'Index' },
                    { value: 'stock', label: 'Stock' }
                ]" :error="createForm.errors.category" />
                <AppInput v-model="createForm.pip_value" type="number" label="Pip Value" :error="createForm.errors.pip_value" />
            </div>
            <div class="mt-3">
                <AppButton :loading="createForm.processing" @click="save">Simpan Instrument</AppButton>
            </div>
        </div>

        <template v-if="instruments?.data?.length">
            <AppTable>
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Symbol</th>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Category</th>
                        <th class="px-3 py-2 text-left">Pip Value</th>
                        <th class="px-3 py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in instruments.data" :key="item.id">
                        <td class="px-3 py-2">{{ item.symbol }}</td>
                        <td class="px-3 py-2">{{ item.name }}</td>
                        <td class="px-3 py-2">{{ item.category }}</td>
                        <td class="px-3 py-2">{{ item.pip_value }}</td>
                        <td class="px-3 py-2">
                            <Link :href="route('settings.instruments.destroy', item.id)" method="delete" as="button" class="rounded border border-red-200 px-2 py-1 text-xs text-red-600">Delete</Link>
                        </td>
                    </tr>
                </tbody>
            </AppTable>
            <AppPagination :links="instruments.links" />
        </template>

        <AppEmptyState v-else>
            <template #title>Belum ada instrument</template>
            Tambahkan instrument trading terlebih dahulu.
        </AppEmptyState>
    </AppLayout>
</template>
