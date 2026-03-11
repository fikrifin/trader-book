<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    targets: Object,
});

const page = usePage();
const accountOptions = computed(() => (page.props.trading_accounts || []).map((item) => ({
    value: item.id,
    label: `${item.name} (${item.account_type})`,
})));

const form = useForm({
    trading_account_id: page.props.active_account?.id || '',
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
    target_profit: '',
    target_win_rate: '',
    target_max_drawdown: '',
});

const save = () => {
    form.post(route('settings.targets.store'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Targets" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Monthly Targets</h1>

        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Set Target</h2>
            <div class="grid gap-3 md:grid-cols-3">
                <AppSelect v-model="form.trading_account_id" label="Trading Account" :options="accountOptions" :error="form.errors.trading_account_id" />
                <AppInput v-model="form.year" type="number" label="Year" :error="form.errors.year" />
                <AppInput v-model="form.month" type="number" label="Month" :error="form.errors.month" />
                <AppInput v-model="form.target_profit" type="number" label="Target Profit" :error="form.errors.target_profit" />
                <AppInput v-model="form.target_win_rate" type="number" label="Target Win Rate (%)" :error="form.errors.target_win_rate" />
                <AppInput v-model="form.target_max_drawdown" type="number" label="Target Max DD (%)" :error="form.errors.target_max_drawdown" />
            </div>
            <div class="mt-3">
                <AppButton :loading="form.processing" @click="save">Simpan Target</AppButton>
            </div>
        </div>

        <template v-if="targets?.data?.length">
            <AppTable>
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Account</th>
                        <th class="px-3 py-2 text-left">Period</th>
                        <th class="px-3 py-2 text-left">Target Profit</th>
                        <th class="px-3 py-2 text-left">Win Rate</th>
                        <th class="px-3 py-2 text-left">Max DD</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in targets.data" :key="item.id">
                        <td class="px-3 py-2">{{ item.trading_account?.name || item.trading_account_id }}</td>
                        <td class="px-3 py-2">{{ item.month }}/{{ item.year }}</td>
                        <td class="px-3 py-2">{{ item.target_profit }}</td>
                        <td class="px-3 py-2">{{ item.target_win_rate }}</td>
                        <td class="px-3 py-2">{{ item.target_max_drawdown }}</td>
                    </tr>
                </tbody>
            </AppTable>
            <AppPagination :links="targets.links" />
        </template>

        <AppEmptyState v-else>
            <template #title>Belum ada target</template>
            Buat target bulanan pertama untuk akun trading Anda.
        </AppEmptyState>
    </AppLayout>
</template>
