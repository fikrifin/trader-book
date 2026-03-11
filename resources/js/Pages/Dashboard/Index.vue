<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppChart from '@/Components/UI/AppChart.vue';
import AppCurrencyDisplay from '@/Components/UI/AppCurrencyDisplay.vue';
import AppProgressBar from '@/Components/UI/AppProgressBar.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    today_summary: Object,
    month_summary: Object,
    equity_curve: Array,
    daily_pl_chart: Array,
    recent_trades: Array,
    target_progress: Object,
    risk_status: Object,
});

const chartBaseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
};

const equityChartData = computed(() => ({
    labels: (props.equity_curve || []).map((item) => item.date),
    datasets: [
        {
            label: 'Equity Curve',
            data: (props.equity_curve || []).map((item) => Number(item.cumulative_pl || 0)),
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.16)',
            fill: true,
            tension: 0.35,
            pointRadius: 0,
            borderWidth: 2,
        },
    ],
}));

const dailyPlChartData = computed(() => ({
    labels: (props.daily_pl_chart || []).map((item) => item.date),
    datasets: [
        {
            label: 'Daily P/L',
            data: (props.daily_pl_chart || []).map((item) => Number(item.pl || 0)),
            backgroundColor: (props.daily_pl_chart || []).map((item) => Number(item.pl || 0) >= 0 ? '#10b981' : '#ef4444'),
            borderRadius: 4,
            maxBarThickness: 22,
        },
    ],
}));
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
            <Link :href="route('trades.create')" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">+ Add Trade</Link>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Total Trade Hari Ini</p>
                <p class="mt-2 text-2xl font-bold">{{ today_summary?.trade_count || 0 }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">P/L Hari Ini</p>
                <p class="mt-2 text-2xl font-bold" :class="Number(today_summary?.pl || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                    <AppCurrencyDisplay :value="today_summary?.pl || 0" show-plus />
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Win Rate Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold">{{ month_summary?.win_rate || 0 }}%</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">P/L Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold" :class="Number(month_summary?.pl || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                    <AppCurrencyDisplay :value="month_summary?.pl || 0" show-plus />
                </p>
            </div>
        </div>

        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm">
            <p class="mb-2 text-sm font-medium text-gray-700">Progress Target Bulanan</p>
            <AppProgressBar :value="target_progress?.progress_pct || 0" :max="100" />
            <div class="mt-2 text-xs text-gray-500">
                <span>Current: <AppCurrencyDisplay :value="target_progress?.actual_profit || 0" show-plus /></span>
                <span class="mx-1">•</span>
                <span>Target: <AppCurrencyDisplay :value="target_progress?.target_profit || 0" /></span>
                <span class="mx-1">•</span>
                <span>Progress: {{ target_progress?.progress_pct || 0 }}%</span>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-medium text-gray-700">Risk Status</p>
                <p class="text-sm text-gray-600">Trades Today: {{ risk_status?.today_trade_count || 0 }}</p>
                <p class="text-sm text-gray-600">Today Loss: {{ risk_status?.today_loss || 0 }}</p>
                <div class="mt-2">
                    <AppBadge :variant="risk_status?.is_blocked ? 'loss' : 'win'">
                        {{ risk_status?.is_blocked ? 'Blocked' : 'Safe' }}
                    </AppBadge>
                </div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-medium text-gray-700">Month Stats</p>
                <p class="text-sm text-gray-600">Trades: {{ month_summary?.trade_count || 0 }}</p>
                <p class="text-sm text-gray-600">Profit Factor: {{ month_summary?.profit_factor || 0 }}</p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-medium text-gray-700">Equity Curve (90 hari)</p>
                <AppChart type="line" :data="equityChartData" :options="chartBaseOptions" height-class="h-64" />
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-medium text-gray-700">Daily P/L (30 hari)</p>
                <AppChart type="bar" :data="dailyPlChartData" :options="chartBaseOptions" height-class="h-64" />
            </div>
        </div>

        <div class="mt-4 rounded-lg bg-white p-4 shadow-sm">
            <p class="mb-2 text-sm font-medium text-gray-700">Recent Trades</p>
            <AppTable v-if="recent_trades?.length">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Pair</th>
                        <th class="px-3 py-2 text-left">Result</th>
                        <th class="px-3 py-2 text-left">P/L</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="trade in recent_trades" :key="trade.id">
                        <td class="px-3 py-2">{{ trade.date }}</td>
                        <td class="px-3 py-2">{{ trade.pair }}</td>
                        <td class="px-3 py-2">
                            <AppBadge :variant="trade.result === 'win' ? 'win' : (trade.result === 'loss' ? 'loss' : (trade.result === 'breakeven' ? 'breakeven' : 'partial'))">
                                {{ trade.result }}
                            </AppBadge>
                        </td>
                        <td class="px-3 py-2" :class="Number(trade.profit_loss) >= 0 ? 'text-green-600' : 'text-red-600'"><AppCurrencyDisplay :value="trade.profit_loss" show-plus /></td>
                    </tr>
                </tbody>
            </AppTable>
            <p v-else class="text-sm text-gray-500">Belum ada trade terbaru.</p>
        </div>
    </AppLayout>
</template>
