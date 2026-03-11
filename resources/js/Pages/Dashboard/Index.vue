<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppProgressBar from '@/Components/UI/AppProgressBar.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    today_summary: Object,
    month_summary: Object,
    equity_curve: Array,
    daily_pl_chart: Array,
    recent_trades: Array,
    target_progress: Object,
    risk_status: Object,
});
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
                <p class="mt-2 text-2xl font-bold" :class="Number(today_summary?.total_pl || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ today_summary?.total_pl || 0 }}
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Win Rate Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold">{{ month_summary?.win_rate || 0 }}%</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">P/L Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold" :class="Number(month_summary?.total_pl || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ month_summary?.total_pl || 0 }}
                </p>
            </div>
        </div>

        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm">
            <p class="mb-2 text-sm font-medium text-gray-700">Progress Target Bulanan</p>
            <AppProgressBar :value="target_progress?.progress_percent || 0" :max="100" />
            <div class="mt-2 text-xs text-gray-500">
                <span>Current: {{ target_progress?.current || 0 }}</span>
                <span class="mx-1">•</span>
                <span>Target: {{ target_progress?.target || 0 }}</span>
                <span class="mx-1">•</span>
                <span>Remaining: {{ target_progress?.remaining || 0 }}</span>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-medium text-gray-700">Risk Status</p>
                <p class="text-sm text-gray-600">Daily DD: {{ risk_status?.daily_drawdown || 0 }}%</p>
                <p class="text-sm text-gray-600">Weekly DD: {{ risk_status?.weekly_drawdown || 0 }}%</p>
                <div class="mt-2">
                    <AppBadge :variant="risk_status?.daily_ok && risk_status?.weekly_ok ? 'win' : 'loss'">
                        {{ risk_status?.daily_ok && risk_status?.weekly_ok ? 'Safe' : 'Warning' }}
                    </AppBadge>
                </div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-medium text-gray-700">Chart Data</p>
                <p class="text-sm text-gray-600">Equity points: {{ equity_curve?.length || 0 }}</p>
                <p class="text-sm text-gray-600">Daily P/L points: {{ daily_pl_chart?.length || 0 }}</p>
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
                        <td class="px-3 py-2">{{ trade.trade_date }}</td>
                        <td class="px-3 py-2">{{ trade.pair }}</td>
                        <td class="px-3 py-2">
                            <AppBadge :variant="trade.result === 'win' ? 'win' : (trade.result === 'loss' ? 'loss' : (trade.result === 'breakeven' ? 'breakeven' : 'partial'))">
                                {{ trade.result }}
                            </AppBadge>
                        </td>
                        <td class="px-3 py-2" :class="Number(trade.profit_loss) >= 0 ? 'text-green-600' : 'text-red-600'">{{ trade.profit_loss }}</td>
                    </tr>
                </tbody>
            </AppTable>
            <p v-else class="text-sm text-gray-500">Belum ada trade terbaru.</p>
        </div>
    </AppLayout>
</template>
