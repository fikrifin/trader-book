<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import { Head, router } from '@inertiajs/vue3';

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
        from_date: formData.get('from_date') || '',
        to_date: formData.get('to_date') || '',
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Statistics" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Statistics</h1>

        <form class="mb-4 grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4" @submit.prevent="applyFilter">
            <AppInput name="from_date" type="date" label="From" :model-value="filters?.from_date || ''" />
            <AppInput name="to_date" type="date" label="To" :model-value="filters?.to_date || ''" />
            <div class="md:col-span-2 flex items-end gap-2">
                <AppButton type="submit">Apply</AppButton>
            </div>
        </form>

        <div class="mb-4 grid gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Trades</p>
                <p class="mt-2 text-xl font-semibold">{{ summary?.trade_count || 0 }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Win Rate</p>
                <p class="mt-2 text-xl font-semibold">{{ summary?.win_rate || 0 }}%</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Profit Factor</p>
                <p class="mt-2 text-xl font-semibold">{{ summary?.profit_factor || 0 }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Net P/L</p>
                <p class="mt-2 text-xl font-semibold" :class="Number(summary?.total_pl || 0) >= 0 ? 'text-green-600' : 'text-red-600'">{{ summary?.total_pl || 0 }}</p>
            </div>
        </div>

        <div class="mb-4 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Expectancy</p>
                <p class="mt-2 text-xl font-semibold">{{ summary?.expectancy || 0 }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Avg Win / Loss</p>
                <p class="mt-2 text-sm font-medium">{{ summary?.avg_win || 0 }} / {{ summary?.avg_loss || 0 }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Max Drawdown</p>
                <p class="mt-2 text-xl font-semibold text-red-600">{{ summary?.max_drawdown || 0 }}</p>
            </div>
        </div>

        <div class="mb-4 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Net vs Gross</h2>
            <div class="grid gap-3 md:grid-cols-3">
                <p class="text-sm">Gross: <span class="font-medium">{{ net_vs_gross?.gross || 0 }}</span></p>
                <p class="text-sm">Commission: <span class="font-medium">{{ net_vs_gross?.commission || 0 }}</span></p>
                <p class="text-sm">Swap: <span class="font-medium">{{ net_vs_gross?.swap || 0 }}</span></p>
            </div>
        </div>

        <div class="mb-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Performance by Pair</h2>
                <AppTable v-if="performance_by_pair?.length">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Pair</th>
                            <th class="px-3 py-2 text-left">Trades</th>
                            <th class="px-3 py-2 text-left">Win Rate</th>
                            <th class="px-3 py-2 text-left">P/L</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in performance_by_pair" :key="item.label">
                            <td class="px-3 py-2">{{ item.label }}</td>
                            <td class="px-3 py-2">{{ item.trades }}</td>
                            <td class="px-3 py-2">{{ item.win_rate }}%</td>
                            <td class="px-3 py-2" :class="Number(item.total_pl) >= 0 ? 'text-green-600' : 'text-red-600'">{{ item.total_pl }}</td>
                        </tr>
                    </tbody>
                </AppTable>
                <p v-else class="text-sm text-gray-500">Data pair belum tersedia.</p>
            </div>

            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Performance by Setup</h2>
                <AppTable v-if="performance_by_setup?.length">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Setup</th>
                            <th class="px-3 py-2 text-left">Trades</th>
                            <th class="px-3 py-2 text-left">Win Rate</th>
                            <th class="px-3 py-2 text-left">P/L</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in performance_by_setup" :key="item.label">
                            <td class="px-3 py-2">{{ item.label }}</td>
                            <td class="px-3 py-2">{{ item.trades }}</td>
                            <td class="px-3 py-2">{{ item.win_rate }}%</td>
                            <td class="px-3 py-2" :class="Number(item.total_pl) >= 0 ? 'text-green-600' : 'text-red-600'">{{ item.total_pl }}</td>
                        </tr>
                    </tbody>
                </AppTable>
                <p v-else class="text-sm text-gray-500">Data setup belum tersedia.</p>
            </div>
        </div>

        <div class="mb-4 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Session</h2>
                <div class="space-y-2 text-sm">
                    <div v-for="item in performance_by_session" :key="item.label" class="flex items-center justify-between">
                        <span>{{ item.label }}</span>
                        <AppBadge :variant="Number(item.total_pl) >= 0 ? 'win' : 'loss'">{{ item.total_pl }}</AppBadge>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Timeframe</h2>
                <div class="space-y-2 text-sm">
                    <div v-for="item in performance_by_timeframe" :key="item.label" class="flex items-center justify-between">
                        <span>{{ item.label }}</span>
                        <AppBadge :variant="Number(item.total_pl) >= 0 ? 'win' : 'loss'">{{ item.total_pl }}</AppBadge>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Day of Week</h2>
                <div class="space-y-2 text-sm">
                    <div v-for="item in performance_by_day_of_week" :key="item.label" class="flex items-center justify-between">
                        <span>{{ item.label }}</span>
                        <AppBadge :variant="Number(item.total_pl) >= 0 ? 'win' : 'loss'">{{ item.total_pl }}</AppBadge>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Mistake Distribution</h2>
            <div v-if="mistake_distribution?.length" class="space-y-2 text-sm">
                <div v-for="item in mistake_distribution" :key="item.label" class="flex items-center justify-between">
                    <span>{{ item.label }}</span>
                    <AppBadge variant="warning">{{ item.value }}</AppBadge>
                </div>
            </div>
            <p v-else class="text-sm text-gray-500">Belum ada data kesalahan trading.</p>
        </div>
    </AppLayout>
</template>
