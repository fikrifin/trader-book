<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppCurrencyDisplay from '@/Components/UI/AppCurrencyDisplay.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppSkeleton from '@/Components/UI/AppSkeleton.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    trades: Object,
    filters: Object,
    accounts: Array,
    pairs: Array,
});

const filtersForm = reactive({
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    pair: props.filters?.pair || '',
    result: props.filters?.result || '',
    session: props.filters?.session || '',
    direction: props.filters?.direction || '',
    account_id: props.filters?.account_id || '',
});

const accountOptions = (props.accounts || []).map((item) => ({ value: item.id, label: `${item.name} (${item.account_type})` }));
const pairOptions = (props.pairs || []).map((item) => ({ value: item, label: item }));
const filterOpen = ref(Boolean(
    filtersForm.date_from
    || filtersForm.date_to
    || filtersForm.pair
    || filtersForm.result
    || filtersForm.session
    || filtersForm.direction
    || filtersForm.account_id
));
const isFiltering = ref(false);

const activeFilterCount = computed(() => {
    const values = Object.values(filtersForm);
    return values.filter((item) => String(item || '').trim() !== '').length;
});

const dateRangeSummary = computed(() => {
    if (!filtersForm.date_from && !filtersForm.date_to) return '';
    if (filtersForm.date_from && filtersForm.date_to) return `Showing trades from ${filtersForm.date_from} to ${filtersForm.date_to}`;
    if (filtersForm.date_from) return `Showing trades from ${filtersForm.date_from}`;
    return `Showing trades until ${filtersForm.date_to}`;
});

const listSummary = computed(() => {
    const rows = props.trades?.data || [];
    const total = rows.length;
    const wins = rows.filter((item) => item.result === 'win').length;
    const netPl = rows.reduce((sum, item) => sum + Number(item.profit_loss || 0), 0);

    return {
        total,
        winRate: total > 0 ? ((wins / total) * 100).toFixed(2) : '0.00',
        netPl: netPl.toFixed(2),
    };
});

const applyFilters = () => {
    isFiltering.value = true;
    router.get(route('trades.index'), filtersForm, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isFiltering.value = false;
        },
    });
};

const clearFilters = () => {
    Object.assign(filtersForm, {
        date_from: '',
        date_to: '',
        pair: '',
        result: '',
        session: '',
        direction: '',
        account_id: '',
    });
    applyFilters();
};

const exportData = (format) => {
    const params = new URLSearchParams({ ...filtersForm, format });
    window.location.href = `${route('trades.export')}?${params.toString()}`;
};

const formatDisplayDate = (value) => {
    if (!value) return '-';

    const text = String(value);

    // Already in Y-m-d format.
    if (/^\d{4}-\d{2}-\d{2}$/.test(text)) {
        const [year, month, day] = text.split('-');
        return `${day}-${month}-${year}`;
    }

    const date = new Date(text);
    if (Number.isNaN(date.getTime())) return text;

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
};
</script>

<template>
    <Head title="Trades" />

    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Trades</h1>
                <p class="text-xs text-gray-500">Kelola, filter, dan analisa histori transaksi Anda.</p>
            </div>
            <Link :href="route('trades.create')" class="inline-flex items-center rounded-lg bg-gradient-to-r from-brand-600 to-brand-500 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:from-brand-700 hover:to-brand-600">+ Add Trade</Link>
        </div>

        <AppCard class="mb-4" hoverable>
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700">Filter Trades</p>
                <button type="button" class="text-xs font-medium text-brand-600" @click="filterOpen = !filterOpen">
                    {{ filterOpen ? 'Hide Filters' : 'Show Filters' }}
                    <span v-if="activeFilterCount > 0" class="ml-1 rounded-full bg-brand-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ activeFilterCount }}</span>
                </button>
            </div>

            <div v-if="filterOpen" class="grid gap-3 md:grid-cols-4">
            <AppInput v-model="filtersForm.date_from" type="date" label="Date From" />
            <AppInput v-model="filtersForm.date_to" type="date" label="Date To" />
            <AppSelect v-model="filtersForm.account_id" label="Account" :options="accountOptions" />
            <AppSelect v-model="filtersForm.pair" label="Pair" :options="pairOptions" />
            <AppSelect v-model="filtersForm.result" label="Result" :options="[
                { value: 'win', label: 'Win' },
                { value: 'loss', label: 'Loss' },
                { value: 'breakeven', label: 'Breakeven' },
                { value: 'partial', label: 'Partial' },
            ]" />
            <AppSelect v-model="filtersForm.session" label="Session" :options="[
                { value: 'asia', label: 'Asia' },
                { value: 'london', label: 'London' },
                { value: 'new_york', label: 'New York' },
                { value: 'overlap', label: 'Overlap' },
            ]" />
            <AppSelect v-model="filtersForm.direction" label="Direction" :options="[
                { value: 'buy', label: 'Buy' },
                { value: 'sell', label: 'Sell' },
            ]" />

            <div class="flex items-end gap-2">
                <AppButton @click="applyFilters">Apply</AppButton>
                <AppButton variant="secondary" @click="clearFilters">Clear</AppButton>
            </div>

            <div class="flex items-end gap-2">
                <AppButton variant="ghost" @click="exportData('csv')">Export CSV</AppButton>
                <AppButton variant="ghost" @click="exportData('xlsx')">Export Excel</AppButton>
            </div>
            </div>
        </AppCard>

        <p v-if="dateRangeSummary" class="mb-3 text-xs font-medium text-gray-500">{{ dateRangeSummary }}</p>

        <div class="mb-4 grid gap-3 md:grid-cols-3">
            <AppCard padding="sm" hoverable>
                <p class="text-xs uppercase text-gray-500">Visible Trades</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ listSummary.total }}</p>
            </AppCard>
            <AppCard padding="sm" hoverable>
                <p class="text-xs uppercase text-gray-500">Win Rate</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ listSummary.winRate }}%</p>
            </AppCard>
            <AppCard padding="sm" hoverable>
                <p class="text-xs uppercase text-gray-500">Net P/L</p>
                <p class="mt-1 text-lg font-semibold" :class="Number(listSummary.netPl) >= 0 ? 'text-green-600' : 'text-red-600'">
                    <AppCurrencyDisplay :value="listSummary.netPl" show-plus />
                </p>
            </AppCard>
        </div>

        <div v-if="isFiltering" class="mb-4">
            <AppSkeleton variant="table-row" :lines="5" />
        </div>

        <template v-else-if="trades?.data?.length">
            <div class="space-y-3 md:hidden">
                <AppCard
                    v-for="item in trades.data"
                    :key="`card-${item.id}`"
                    hoverable
                    :class="item.result === 'win' ? 'bg-emerald-50/20' : (item.result === 'loss' ? 'bg-red-50/20' : '')"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-900">{{ item.pair }}</p>
                        <AppBadge :variant="item.result === 'win' ? 'win' : (item.result === 'loss' ? 'loss' : (item.result === 'breakeven' ? 'breakeven' : 'partial'))">{{ item.result }}</AppBadge>
                    </div>
                    <div class="mb-2 text-xs text-gray-500">{{ formatDisplayDate(item.date) }} • {{ item.session }}</div>
                    <div class="mb-3 flex items-center justify-between">
                        <AppBadge :variant="item.direction === 'buy' ? 'buy' : 'sell'">{{ item.direction }}</AppBadge>
                        <p class="text-sm font-semibold" :class="Number(item.profit_loss) >= 0 ? 'text-green-600' : 'text-red-600'"><AppCurrencyDisplay :value="item.profit_loss" show-plus /></p>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('trades.show', item.id)" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 transition hover:bg-gray-50">Detail</Link>
                        <Link :href="route('trades.edit', item.id)" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 transition hover:bg-gray-50">Edit</Link>
                        <Link :href="route('trades.destroy', item.id)" method="delete" as="button" class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs text-red-600 transition hover:bg-red-100">Delete</Link>
                    </div>
                </AppCard>
            </div>

            <AppTable class="hidden md:block">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Pair</th>
                        <th class="px-3 py-2 text-left">Direction</th>
                        <th class="px-3 py-2 text-left">Session</th>
                        <th class="px-3 py-2 text-left">Result</th>
                        <th class="px-3 py-2 text-left">P/L</th>
                        <th class="px-3 py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in trades.data" :key="item.id" class="transition-colors hover:bg-gray-50/80" :class="item.result === 'win' ? 'bg-emerald-50/20' : (item.result === 'loss' ? 'bg-red-50/20' : '')">
                        <td class="px-3 py-2">{{ formatDisplayDate(item.date) }}</td>
                        <td class="px-3 py-2">{{ item.pair }}</td>
                        <td class="px-3 py-2">
                            <AppBadge :variant="item.direction === 'buy' ? 'buy' : 'sell'">{{ item.direction }}</AppBadge>
                        </td>
                        <td class="px-3 py-2">{{ item.session }}</td>
                        <td class="px-3 py-2">
                            <AppBadge :variant="item.result === 'win' ? 'win' : (item.result === 'loss' ? 'loss' : (item.result === 'breakeven' ? 'breakeven' : 'partial'))">{{ item.result }}</AppBadge>
                        </td>
                        <td class="px-3 py-2" :class="Number(item.profit_loss) >= 0 ? 'text-green-600' : 'text-red-600'"><AppCurrencyDisplay :value="item.profit_loss" show-plus /></td>
                        <td class="px-3 py-2">
                            <div class="flex gap-2">
                                <Link :href="route('trades.show', item.id)" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 transition hover:bg-gray-50">Detail</Link>
                                <Link :href="route('trades.edit', item.id)" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 transition hover:bg-gray-50">Edit</Link>
                                <Link :href="route('trades.destroy', item.id)" method="delete" as="button" class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs text-red-600 transition hover:bg-red-100">Delete</Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </AppTable>
            <AppPagination :links="trades.links" />
        </template>

        <AppEmptyState v-else>
            <template #title>Belum ada trade</template>
            Tambahkan trade pertama Anda untuk memulai jurnal trading.
        </AppEmptyState>
    </AppLayout>
</template>
