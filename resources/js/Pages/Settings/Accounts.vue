<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    accounts: Object,
});

const createForm = useForm({
    name: '',
    broker: '',
    account_type: 'demo',
    account_number: '',
    initial_balance: 0,
    currency: 'USD',
    max_daily_loss: '',
    max_daily_loss_pct: '',
    max_trades_per_day: '',
    max_drawdown_pct: '',
    notes: '',
    is_active: true,
});

const save = () => {
    createForm.post(route('settings.accounts.store'), { preserveScroll: true, onSuccess: () => createForm.reset() });
};
</script>

<template>
    <Head title="Accounts" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Trading Accounts</h1>

        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Tambah Account</h2>
            <div class="grid gap-3 md:grid-cols-3">
                <AppInput v-model="createForm.name" label="Name" :error="createForm.errors.name" />
                <AppInput v-model="createForm.broker" label="Broker" :error="createForm.errors.broker" />
                <AppSelect v-model="createForm.account_type" label="Type" :options="[
                    { value: 'live', label: 'Live' },
                    { value: 'demo', label: 'Demo' },
                    { value: 'prop', label: 'Prop' }
                ]" :error="createForm.errors.account_type" />
                <AppInput v-model="createForm.account_number" label="Account Number" :error="createForm.errors.account_number" />
                <AppInput v-model="createForm.initial_balance" type="number" label="Initial Balance" :error="createForm.errors.initial_balance" />
                <AppInput v-model="createForm.currency" label="Currency" :error="createForm.errors.currency" />
            </div>
            <div class="mt-3">
                <AppButton :loading="createForm.processing" @click="save">Simpan Account</AppButton>
            </div>
        </div>

        <template v-if="accounts?.data?.length">
            <AppTable>
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Balance</th>
                        <th class="px-3 py-2 text-left">Currency</th>
                        <th class="px-3 py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in accounts.data" :key="item.id">
                        <td class="px-3 py-2">{{ item.name }}</td>
                        <td class="px-3 py-2"><AppBadge variant="info">{{ item.account_type }}</AppBadge></td>
                        <td class="px-3 py-2">{{ item.current_balance }}</td>
                        <td class="px-3 py-2">{{ item.currency }}</td>
                        <td class="px-3 py-2">
                            <div class="flex gap-2">
                                <Link :href="route('accounts.switch')" method="post" as="button" :data="{ trading_account_id: item.id }" class="rounded border px-2 py-1 text-xs">Set Active</Link>
                                <Link :href="route('settings.accounts.destroy', item.id)" method="delete" as="button" class="rounded border border-red-200 px-2 py-1 text-xs text-red-600">Delete</Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </AppTable>
            <AppPagination :links="accounts.links" />
        </template>

        <AppEmptyState v-else>
            <template #title>Belum ada account</template>
            Tambahkan account trading pertama Anda.
        </AppEmptyState>
    </AppLayout>
</template>
