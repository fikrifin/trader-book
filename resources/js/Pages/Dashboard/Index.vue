<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppChart from '@/Components/UI/AppChart.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppCounter from '@/Components/UI/AppCounter.vue';
import AppCurrencyDisplay from '@/Components/UI/AppCurrencyDisplay.vue';
import AppProgressBar from '@/Components/UI/AppProgressBar.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import {
    ArrowsRightLeftIcon,
    ArrowTrendingUpIcon,
    ChartBarSquareIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();

const props = defineProps({
    today_summary: Object,
    month_summary: Object,
    equity_curve: Array,
    daily_pl_chart: Array,
    recent_trades: Array,
    target_progress: Object,
    risk_status: Object,
    top_movers: Array,
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

const topMoversLive = ref([]);
let topMoverTimer = null;

const activeAccount = computed(() => page.props.active_account || null);

const topMoverSymbols = computed(() => (topMoversLive.value || [])
    .map((item) => item.symbol)
    .filter(Boolean));

const syncTopMoversPrices = async () => {
    if (!topMoverSymbols.value.length) return;

    try {
        const response = await axios.get(route('settings.instruments.prices'), {
            params: {
                symbols: topMoverSymbols.value.join(','),
            },
        });

        const prices = response?.data?.prices || {};

        topMoversLive.value = topMoversLive.value.map((item) => {
            const live = prices[item.symbol];
            if (!live) return item;

            return {
                ...item,
                last_price: live.price ?? item.last_price,
                price_updated_at: live.updated_at ?? item.price_updated_at,
            };
        });
    } catch (error) {
        // Keep existing cached dashboard values when sync fails.
    }
};

watch(() => props.top_movers, (value) => {
    topMoversLive.value = Array.isArray(value) ? [...value] : [];
}, { immediate: true });

onMounted(() => {
    syncTopMoversPrices();
    topMoverTimer = window.setInterval(syncTopMoversPrices, 60000);
});

onBeforeUnmount(() => {
    if (topMoverTimer) {
        window.clearInterval(topMoverTimer);
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>

            <div class="flex flex-wrap items-center gap-2">
                <div v-if="activeAccount" class="rounded-lg border border-brand-100 bg-brand-50 px-3 py-2 text-right">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-brand-700">Saldo Saat Ini</p>
                    <p class="text-sm font-bold text-brand-800">
                        <AppCurrencyDisplay :value="activeAccount.current_balance || 0" />
                    </p>
                    <p class="text-[10px] text-brand-700/80">{{ activeAccount.name }}</p>
                </div>

                <Link :href="route('trades.create')" class="inline-flex items-center rounded-lg bg-gradient-to-r from-brand-600 to-brand-500 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:from-brand-700 hover:to-brand-600">+ Add Trade</Link>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <AppCard hoverable class="animate-fade-in-up">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Total Trade Hari Ini</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900"><AppCounter :value="Number(today_summary?.trade_count || 0)" /></p>
                    </div>
                    <div class="rounded-xl bg-brand-50 p-2 text-brand-600">
                        <ArrowsRightLeftIcon class="size-5" />
                    </div>
                </div>
            </AppCard>

            <AppCard hoverable class="animate-fade-in-up border-l-4" :class="Number(today_summary?.pl || 0) >= 0 ? 'border-l-emerald-400' : 'border-l-red-400'">
                <div class="flex items-start justify-between">
                    <div>
                <p class="text-xs uppercase text-gray-500">P/L Hari Ini</p>
                <p class="mt-2 text-2xl font-bold" :class="Number(today_summary?.pl || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                    <AppCurrencyDisplay :value="today_summary?.pl || 0" show-plus />
                </p>
                    </div>
                    <div class="rounded-xl p-2" :class="Number(today_summary?.pl || 0) >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                        <ArrowTrendingUpIcon class="size-5" />
                    </div>
                </div>
            </AppCard>

            <AppCard hoverable class="animate-fade-in-up">
                <p class="text-xs uppercase text-gray-500">Win Rate Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold"><AppCounter :value="Number(month_summary?.win_rate || 0)" :decimals="2" suffix="%" /></p>
            </AppCard>

            <AppCard hoverable class="animate-fade-in-up border-l-4" :class="Number(month_summary?.pl || 0) >= 0 ? 'border-l-emerald-400' : 'border-l-red-400'">
                <p class="text-xs uppercase text-gray-500">P/L Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold" :class="Number(month_summary?.pl || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                    <AppCurrencyDisplay :value="month_summary?.pl || 0" show-plus />
                </p>
            </AppCard>
        </div>

        <AppCard class="mt-6" hoverable>
            <p class="mb-2 text-sm font-medium text-gray-700">Progress Target Bulanan</p>
            <AppProgressBar :value="target_progress?.progress_pct || 0" :max="100" show-label />
            <div class="mt-2 text-xs text-gray-500">
                <span>Current: <AppCurrencyDisplay :value="target_progress?.actual_profit || 0" show-plus /></span>
                <span class="mx-1">•</span>
                <span>Target: <AppCurrencyDisplay :value="target_progress?.target_profit || 0" /></span>
                <span class="mx-1">•</span>
                <span>Progress: {{ target_progress?.progress_pct || 0 }}%</span>
            </div>
        </AppCard>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <AppCard hoverable :class="risk_status?.is_blocked ? 'border border-red-200 bg-red-50/40' : 'border border-emerald-200 bg-emerald-50/40'">
                <p class="mb-2 text-sm font-medium text-gray-700">Risk Status</p>
                <p class="text-sm text-gray-600">Trades Today: {{ risk_status?.today_trade_count || 0 }}</p>
                <p class="text-sm text-gray-600">Today Loss: {{ risk_status?.today_loss || 0 }}</p>
                <div class="mt-2 flex items-center gap-2">
                    <component :is="risk_status?.is_blocked ? ShieldExclamationIcon : ShieldCheckIcon" class="size-4" :class="risk_status?.is_blocked ? 'text-red-600' : 'text-emerald-600'" />
                    <AppBadge :variant="risk_status?.is_blocked ? 'loss' : 'win'">
                        {{ risk_status?.is_blocked ? 'Blocked' : 'Safe' }}
                    </AppBadge>
                </div>
            </AppCard>
            <AppCard hoverable>
                <p class="mb-2 text-sm font-medium text-gray-700">Month Stats</p>
                <p class="text-sm text-gray-600">Trades: <AppCounter :value="Number(month_summary?.trade_count || 0)" /></p>
                <p class="text-sm text-gray-600">Profit Factor: {{ month_summary?.profit_factor || 0 }}</p>
            </AppCard>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <AppCard hoverable>
                <p class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700"><ChartBarSquareIcon class="size-4 text-brand-600" /> Equity Curve (90 hari)</p>
                <AppChart type="line" :data="equityChartData" :options="chartBaseOptions" height-class="h-64" />
            </AppCard>
            <AppCard hoverable>
                <p class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700"><ChartBarSquareIcon class="size-4 text-brand-600" /> Daily P/L (30 hari)</p>
                <AppChart type="bar" :data="dailyPlChartData" :options="chartBaseOptions" height-class="h-64" />
            </AppCard>
        </div>

        <AppCard class="mt-4" hoverable>
            <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-medium text-gray-700">Top Movers (Cached Price)</p>
                <span class="text-xs text-gray-500">Berdasarkan perubahan % terbesar</span>
            </div>

            <AppTable v-if="topMoversLive?.length">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Symbol</th>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Last Price</th>
                        <th class="px-3 py-2 text-left">Change %</th>
                        <th class="px-3 py-2 text-left">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in topMoversLive" :key="item.id">
                        <td class="px-3 py-2 font-medium text-gray-900">{{ item.symbol }}</td>
                        <td class="px-3 py-2">{{ item.name }}</td>
                        <td class="px-3 py-2">{{ Number(item.last_price || 0).toLocaleString('en-US', { maximumFractionDigits: 6 }) }}</td>
                        <td class="px-3 py-2" :class="Number(item.price_change_pct || 0) >= 0 ? 'text-emerald-600' : 'text-red-600'">
                            {{ Number(item.price_change_pct || 0).toFixed(4) }}%
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ item.price_updated_at || '-' }}</td>
                    </tr>
                </tbody>
            </AppTable>
            <p v-else class="text-sm text-gray-500">Belum ada data perubahan harga. Buka halaman Instruments untuk memicu update harga.</p>
        </AppCard>

        <AppCard class="mt-4" hoverable>
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
        </AppCard>
    </AppLayout>
</template>
