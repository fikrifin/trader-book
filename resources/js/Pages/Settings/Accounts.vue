<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppCurrencyDisplay from '@/Components/UI/AppCurrencyDisplay.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const editingId = ref(null);
const deletingId = ref(null);

const editForm = useForm({
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

const startEdit = (item) => {
    editingId.value = item.id;
    editForm.reset();
    editForm.name = item.name || '';
    editForm.broker = item.broker || '';
    editForm.account_type = item.account_type || 'demo';
    editForm.account_number = item.account_number || '';
    editForm.initial_balance = item.initial_balance || 0;
    editForm.currency = item.currency || 'USD';
    editForm.max_daily_loss = item.max_daily_loss || '';
    editForm.max_daily_loss_pct = item.max_daily_loss_pct || '';
    editForm.max_trades_per_day = item.max_trades_per_day || '';
    editForm.max_drawdown_pct = item.max_drawdown_pct || '';
    editForm.notes = item.notes || '';
    editForm.is_active = Boolean(item.is_active);
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!editingId.value) return;
    editForm.put(route('settings.accounts.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            cancelEdit();
        },
    });
};

const confirmDelete = (id) => {
    deletingId.value = id;
};

const runDelete = () => {
    if (!deletingId.value) return;
    const targetId = deletingId.value;
    useForm({}).delete(route('settings.accounts.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            deletingId.value = null;
            if (editingId.value && editingId.value === targetId) {
                cancelEdit();
            }
        },
    });
};
</script>

<template>
    <Head title="Accounts" />

    <AppLayout>
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-900">Trading Accounts</h1>
            <p class="text-xs text-gray-500">Kelola akun live, demo, dan prop dalam satu tempat.</p>
        </div>

        <AppCard class="mb-6" hoverable>
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
        </AppCard>

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
                    <tr v-for="item in accounts.data" :key="item.id" class="transition-colors hover:bg-gray-50/80">
                        <td class="px-3 py-2">
                            <template v-if="editingId === item.id">
                                <AppInput v-model="editForm.name" :error="editForm.errors.name" />
                            </template>
                            <template v-else>{{ item.name }}</template>
                        </td>
                        <td class="px-3 py-2">
                            <template v-if="editingId === item.id">
                                <AppSelect v-model="editForm.account_type" :options="[
                                    { value: 'live', label: 'Live' },
                                    { value: 'demo', label: 'Demo' },
                                    { value: 'prop', label: 'Prop' }
                                ]" :error="editForm.errors.account_type" />
                            </template>
                            <template v-else><AppBadge variant="info">{{ item.account_type }}</AppBadge></template>
                        </td>
                        <td class="px-3 py-2">
                            <template v-if="editingId === item.id">
                                <AppInput v-model="editForm.initial_balance" type="number" :error="editForm.errors.initial_balance" />
                            </template>
                            <template v-else><AppCurrencyDisplay :value="item.current_balance" /></template>
                        </td>
                        <td class="px-3 py-2">
                            <template v-if="editingId === item.id">
                                <AppInput v-model="editForm.currency" :error="editForm.errors.currency" />
                            </template>
                            <template v-else>{{ item.currency }}</template>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex gap-2">
                                <Link :href="route('accounts.switch')" method="post" as="button" :data="{ trading_account_id: item.id }" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 transition hover:bg-gray-50">Set Active</Link>
                                <template v-if="editingId === item.id">
                                    <button type="button" class="rounded-md border border-green-200 bg-green-50 px-2 py-1 text-xs text-green-700 transition hover:bg-green-100" @click="submitEdit">Save</button>
                                    <button type="button" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 transition hover:bg-gray-50" @click="cancelEdit">Cancel</button>
                                </template>
                                <template v-else>
                                    <button type="button" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 transition hover:bg-gray-50" @click="startEdit(item)">Edit</button>
                                    <button type="button" class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs text-red-600 transition hover:bg-red-100" @click="confirmDelete(item.id)">Delete</button>
                                </template>
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

        <AppModal :show="Boolean(deletingId)" title="Hapus Account?" @close="deletingId = null">
            <p class="mt-2 text-sm text-gray-600">Tindakan ini tidak bisa dibatalkan.</p>
            <div class="mt-4 flex justify-end gap-2">
                <AppButton variant="secondary" @click="deletingId = null">Batal</AppButton>
                <AppButton variant="danger" @click="runDelete">Hapus</AppButton>
            </div>
        </AppModal>
    </AppLayout>
</template>
