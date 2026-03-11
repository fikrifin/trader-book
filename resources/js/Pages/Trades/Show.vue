<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    trade: Object,
});

const imageUrl = (path) => path ? `/storage/${path}` : null;
</script>

<template>
    <Head title="Trade Detail" />

    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Trade Detail</h1>
            <div class="flex gap-2">
                <Link :href="route('trades.edit', trade.id)" class="rounded border px-3 py-2 text-sm">Edit</Link>
                <Link :href="route('trades.destroy', trade.id)" method="delete" as="button" class="rounded border border-red-200 px-3 py-2 text-sm text-red-600">Delete</Link>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <div class="mb-3 flex gap-2">
                    <AppBadge :variant="trade.direction === 'buy' ? 'buy' : 'sell'">{{ trade.direction }}</AppBadge>
                    <AppBadge :variant="trade.result === 'win' ? 'win' : (trade.result === 'loss' ? 'loss' : (trade.result === 'breakeven' ? 'breakeven' : 'partial'))">{{ trade.result }}</AppBadge>
                </div>

                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-gray-500">Date</dt><dd class="font-medium">{{ trade.date }}</dd>
                    <dt class="text-gray-500">Pair</dt><dd class="font-medium">{{ trade.pair }}</dd>
                    <dt class="text-gray-500">Session</dt><dd class="font-medium">{{ trade.session }}</dd>
                    <dt class="text-gray-500">Timeframe</dt><dd class="font-medium">{{ trade.timeframe || '-' }}</dd>
                    <dt class="text-gray-500">Entry</dt><dd class="font-medium">{{ trade.entry_price }}</dd>
                    <dt class="text-gray-500">Stop Loss</dt><dd class="font-medium">{{ trade.stop_loss }}</dd>
                    <dt class="text-gray-500">TP1</dt><dd class="font-medium">{{ trade.take_profit_1 }}</dd>
                    <dt class="text-gray-500">Close</dt><dd class="font-medium">{{ trade.close_price || '-' }}</dd>
                    <dt class="text-gray-500">Lot</dt><dd class="font-medium">{{ trade.lot_size }}</dd>
                    <dt class="text-gray-500">P/L Net</dt><dd class="font-medium" :class="Number(trade.profit_loss) >= 0 ? 'text-green-600' : 'text-red-600'">{{ trade.profit_loss }}</dd>
                    <dt class="text-gray-500">P/L Gross</dt><dd class="font-medium">{{ trade.profit_loss_gross }}</dd>
                    <dt class="text-gray-500">RR Planned</dt><dd class="font-medium">{{ trade.rr_planned || '-' }}</dd>
                    <dt class="text-gray-500">RR Actual</dt><dd class="font-medium">{{ trade.rr_ratio || '-' }}</dd>
                    <dt class="text-gray-500">Commission</dt><dd class="font-medium">{{ trade.commission }}</dd>
                    <dt class="text-gray-500">Swap</dt><dd class="font-medium">{{ trade.swap }}</dd>
                </dl>

                <div class="mt-3 border-t pt-3">
                    <p class="text-sm text-gray-500">Notes</p>
                    <p class="text-sm text-gray-800">{{ trade.notes || '-' }}</p>
                </div>

                <div class="mt-2">
                    <p class="text-sm text-gray-500">Mistake</p>
                    <p class="text-sm text-gray-800">{{ trade.mistake || '-' }}</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <p class="mb-2 text-sm font-medium text-gray-700">Screenshot Before</p>
                    <img v-if="imageUrl(trade.screenshot_before)" :src="imageUrl(trade.screenshot_before)" alt="before" class="w-full rounded-lg border" />
                    <p v-else class="text-sm text-gray-500">Tidak ada screenshot.</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <p class="mb-2 text-sm font-medium text-gray-700">Screenshot After</p>
                    <img v-if="imageUrl(trade.screenshot_after)" :src="imageUrl(trade.screenshot_after)" alt="after" class="w-full rounded-lg border" />
                    <p v-else class="text-sm text-gray-500">Tidak ada screenshot.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
