<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppChart from '@/Components/UI/AppChart.vue';
import AppCurrencyDisplay from '@/Components/UI/AppCurrencyDisplay.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import { ChartBarSquareIcon } from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    filters: Object,
    summary: Object,
    performance_by_pair: Array,
    performance_by_setup: Array,
    performance_by_session: Array,
    performance_by_timeframe: Array,
    performance_by_day_of_week: Array,
    net_vs_gross: Object,
    mistake_distribution: Array,
});

const applyFilter = (event) => {
    const formData = new FormData(event.target);
    router.get(route('statistics.index'), {
        date_from: formData.get('date_from') || '',
        date_to: formData.get('date_to') || '',
    }, { preserveState: true, replace: true });
};

const chartBaseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: {
            ticks: {
                maxRotation: 45,
                minRotation: 0,
            },
            grid: { display: false },
        },
        y: {
            beginAtZero: true,
            grid: { color: '#f1f5f9' },
        },
    },
};

const winLossDonutData = computed(() => {
    const totalTrades = Number(props.summary?.total_trades || 0);
    const winRate = Number(props.summary?.win_rate || 0);
    const wins = Math.round((winRate / 100) * totalTrades);
    const lossesAndOthers = Math.max(totalTrades - wins, 0);

    return {
        labels: ['Wins', 'Non-win'],
        datasets: [
            {
                data: [wins, lossesAndOthers],
                backgroundColor: ['#10b981', '#ef4444'],
                borderWidth: 0,
            },
        ],
    };
});

const pairBarChartData = computed(() => {
    const topPairs = (props.performance_by_pair || []).slice(0, 8);

    return {
        labels: topPairs.map((item) => item.pair),
        datasets: [
            {
                label: 'Net P/L',
                data: topPairs.map((item) => Number(item.net_pl || 0)),
                backgroundColor: topPairs.map((item) => Number(item.net_pl || 0) >= 0 ? '#10b981' : '#ef4444'),
                borderRadius: 4,
                maxBarThickness: 26,
            },
        ],
    };
});

const pairSortBy = ref('net_pl');
const pairSortDirection = ref('desc');
const setupSortBy = ref('net_pl');
const setupSortDirection = ref('desc');

const getSortedRows = (rows, sortBy, sortDirection) => {
    const values = [...(rows || [])];
    return values.sort((left, right) => {
        const leftValue = Number(left?.[sortBy]);
        const rightValue = Number(right?.[sortBy]);
        const leftComparable = Number.isNaN(leftValue) ? String(left?.[sortBy] || '') : leftValue;
        const rightComparable = Number.isNaN(rightValue) ? String(right?.[sortBy] || '') : rightValue;

        if (leftComparable < rightComparable) return sortDirection === 'asc' ? -1 : 1;
        if (leftComparable > rightComparable) return sortDirection === 'asc' ? 1 : -1;
        return 0;
    });
};

const sortedPerformanceByPair = computed(() => {
    return getSortedRows(props.performance_by_pair, pairSortBy.value, pairSortDirection.value);
});

const sortedPerformanceBySetup = computed(() => {
    return getSortedRows(props.performance_by_setup, setupSortBy.value, setupSortDirection.value);
});

const togglePairSort = (column) => {
    if (pairSortBy.value === column) {
        pairSortDirection.value = pairSortDirection.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    pairSortBy.value = column;
    pairSortDirection.value = 'desc';
};

const toggleSetupSort = (column) => {
    if (setupSortBy.value === column) {
        setupSortDirection.value = setupSortDirection.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    setupSortBy.value = column;
    setupSortDirection.value = 'desc';
};

const sortIndicator = (activeColumn, column, direction) => {
    if (activeColumn !== column) return '↕';
    return direction === 'asc' ? '↑' : '↓';
};
</script>

<template>
    <Head title="Statistics" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Statistics</h1>

        <AppCard class="mb-4" hoverable>
        <form class="grid gap-3 md:grid-cols-4" @submit.prevent="applyFilter">
            <AppInput name="date_from" type="date" label="From" :model-value="filters?.date_from || ''" />
            <AppInput name="date_to" type="date" label="To" :model-value="filters?.date_to || ''" />
            <div class="md:col-span-2 flex items-end gap-2">
                <AppButton type="submit">Apply</AppButton>
            </div>
        </form>
        </AppCard>

        <div class="mb-4 grid gap-4 md:grid-cols-4">
            <AppCard hoverable>
                <p class="text-xs uppercase text-gray-500">Trades</p>
                <p class="mt-2 text-xl font-semibold">{{ summary?.total_trades || 0 }}</p>
            </AppCard>
            <AppCard hoverable>
                <p class="text-xs uppercase text-gray-500">Win Rate</p>
                <p class="mt-2 text-xl font-semibold">{{ summary?.win_rate || 0 }}%</p>
            </AppCard>
            <AppCard hoverable>
                <p class="text-xs uppercase text-gray-500">Profit Factor</p>
                <p class="mt-2 text-xl font-semibold">{{ summary?.profit_factor || 0 }}</p>
            </AppCard>
            <AppCard hoverable>
                <p class="text-xs uppercase text-gray-500">Net P/L</p>
                <p class="mt-2 text-xl font-semibold" :class="Number(net_vs_gross?.total_net || 0) >= 0 ? 'text-green-600' : 'text-red-600'"><AppCurrencyDisplay :value="net_vs_gross?.total_net || 0" show-plus /></p>
            </AppCard>
        </div>

        <div class="mb-4 grid gap-4 md:grid-cols-3">
            <AppCard hoverable>
                <p class="text-xs uppercase text-gray-500">Expectancy</p>
                <p class="mt-2 text-xl font-semibold">{{ summary?.expectancy || 0 }}</p>
            </AppCard>
            <AppCard hoverable>
                <p class="text-xs uppercase text-gray-500">Avg Win / Loss</p>
                <p class="mt-2 text-sm font-medium">{{ summary?.average_win || 0 }} / {{ summary?.average_loss || 0 }}</p>
            </AppCard>
            <AppCard hoverable>
                <p class="text-xs uppercase text-gray-500">Max Drawdown</p>
                <p class="mt-2 text-xl font-semibold text-red-600">{{ summary?.max_drawdown || 0 }}</p>
            </AppCard>
        </div>

        <AppCard class="mb-4" hoverable>
            <h2 class="mb-3 text-sm font-semibold">Net vs Gross</h2>
            <div class="grid gap-3 md:grid-cols-3">
                <p class="text-sm">Gross: <span class="font-medium"><AppCurrencyDisplay :value="net_vs_gross?.total_gross || 0" /></span></p>
                <p class="text-sm">Fees: <span class="font-medium"><AppCurrencyDisplay :value="net_vs_gross?.total_fees || 0" /></span></p>
                <p class="text-sm">Net: <span class="font-medium"><AppCurrencyDisplay :value="net_vs_gross?.total_net || 0" show-plus /></span></p>
            </div>
        </AppCard>

        <div class="mb-4 grid gap-4 md:grid-cols-2">
            <AppCard hoverable>
                <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold"><ChartBarSquareIcon class="size-4 text-brand-600" /> Win Distribution</h2>
                <AppChart type="doughnut" :data="winLossDonutData" :options="chartBaseOptions" height-class="h-64" />
            </AppCard>
            <AppCard hoverable>
                <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold"><ChartBarSquareIcon class="size-4 text-brand-600" /> Top Pair Performance</h2>
                <AppChart type="bar" :data="pairBarChartData" :options="chartBaseOptions" height-class="h-64" />
            </AppCard>
        </div>

        <div class="mb-4 grid gap-4 md:grid-cols-2">
            <AppCard hoverable>
                <h2 class="mb-3 text-sm font-semibold">Performance by Pair</h2>
                <AppTable v-if="performance_by_pair?.length">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Pair</th>
                            <th class="px-3 py-2 text-left">
                                <button type="button" class="inline-flex items-center gap-1" @click="togglePairSort('total_trades')">Trades {{ sortIndicator(pairSortBy, 'total_trades', pairSortDirection) }}</button>
                            </th>
                            <th class="px-3 py-2 text-left">
                                <button type="button" class="inline-flex items-center gap-1" @click="togglePairSort('win_rate')">Win Rate {{ sortIndicator(pairSortBy, 'win_rate', pairSortDirection) }}</button>
                            </th>
                            <th class="px-3 py-2 text-left">
                                <button type="button" class="inline-flex items-center gap-1" @click="togglePairSort('net_pl')">P/L {{ sortIndicator(pairSortBy, 'net_pl', pairSortDirection) }}</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in sortedPerformanceByPair" :key="item.label" class="transition-colors hover:bg-gray-50/80">
                            <td class="px-3 py-2">{{ item.pair }}</td>
                            <td class="px-3 py-2">{{ item.total_trades }}</td>
                            <td class="px-3 py-2">{{ item.win_rate }}%</td>
                            <td class="px-3 py-2" :class="Number(item.net_pl) >= 0 ? 'text-green-600' : 'text-red-600'"><AppCurrencyDisplay :value="item.net_pl" show-plus /></td>
                        </tr>
                    </tbody>
                </AppTable>
                <p v-else class="text-sm text-gray-500">Data pair belum tersedia.</p>
            </AppCard>

            <AppCard hoverable>
                <h2 class="mb-3 text-sm font-semibold">Performance by Setup</h2>
                <AppTable v-if="performance_by_setup?.length">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Setup</th>
                            <th class="px-3 py-2 text-left">
                                <button type="button" class="inline-flex items-center gap-1" @click="toggleSetupSort('total_trades')">Trades {{ sortIndicator(setupSortBy, 'total_trades', setupSortDirection) }}</button>
                            </th>
                            <th class="px-3 py-2 text-left">
                                <button type="button" class="inline-flex items-center gap-1" @click="toggleSetupSort('win_rate')">Win Rate {{ sortIndicator(setupSortBy, 'win_rate', setupSortDirection) }}</button>
                            </th>
                            <th class="px-3 py-2 text-left">
                                <button type="button" class="inline-flex items-center gap-1" @click="toggleSetupSort('net_pl')">P/L {{ sortIndicator(setupSortBy, 'net_pl', setupSortDirection) }}</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in sortedPerformanceBySetup" :key="item.label" class="transition-colors hover:bg-gray-50/80">
                            <td class="px-3 py-2">{{ item.setup }}</td>
                            <td class="px-3 py-2">{{ item.total_trades }}</td>
                            <td class="px-3 py-2">{{ item.win_rate }}%</td>
                            <td class="px-3 py-2" :class="Number(item.net_pl) >= 0 ? 'text-green-600' : 'text-red-600'"><AppCurrencyDisplay :value="item.net_pl" show-plus /></td>
                        </tr>
                    </tbody>
                </AppTable>
                <p v-else class="text-sm text-gray-500">Data setup belum tersedia.</p>
            </AppCard>
        </div>

        <div class="mb-4 grid gap-4 md:grid-cols-3">
            <AppCard hoverable>
                <h2 class="mb-3 text-sm font-semibold">Session</h2>
                <div class="space-y-2 text-sm">
                    <div v-for="item in performance_by_session" :key="item.session" class="flex items-center justify-between">
                        <span>{{ item.session }}</span>
                        <AppBadge :variant="Number(item.net_pl) >= 0 ? 'win' : 'loss'"><AppCurrencyDisplay :value="item.net_pl" show-plus /></AppBadge>
                    </div>
                </div>
            </AppCard>
            <AppCard hoverable>
                <h2 class="mb-3 text-sm font-semibold">Timeframe</h2>
                <div class="space-y-2 text-sm">
                    <div v-for="item in performance_by_timeframe" :key="item.timeframe" class="flex items-center justify-between">
                        <span>{{ item.timeframe }}</span>
                        <AppBadge :variant="Number(item.net_pl) >= 0 ? 'win' : 'loss'"><AppCurrencyDisplay :value="item.net_pl" show-plus /></AppBadge>
                    </div>
                </div>
            </AppCard>
            <AppCard hoverable>
                <h2 class="mb-3 text-sm font-semibold">Day of Week</h2>
                <div class="space-y-2 text-sm">
                    <div v-for="item in performance_by_day_of_week" :key="item.label" class="flex items-center justify-between">
                        <span>{{ item.label }}</span>
                        <AppBadge :variant="Number(item.net_pl) >= 0 ? 'win' : 'loss'"><AppCurrencyDisplay :value="item.net_pl" show-plus /></AppBadge>
                    </div>
                </div>
            </AppCard>
        </div>

        <AppCard hoverable>
            <h2 class="mb-3 text-sm font-semibold">Mistake Distribution</h2>
            <div v-if="mistake_distribution?.length" class="space-y-2 text-sm">
                <div v-for="item in mistake_distribution" :key="item.label" class="flex items-center justify-between">
                    <span>{{ item.mistake }}</span>
                    <AppBadge variant="warning">{{ item.count }}</AppBadge>
                </div>
            </div>
            <p v-else class="text-sm text-gray-500">Belum ada data kesalahan trading.</p>
        </AppCard>
    </AppLayout>
</template>
