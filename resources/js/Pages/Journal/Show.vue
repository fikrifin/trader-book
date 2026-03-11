<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    journal: Object,
    trades: Array,
    day_summary: Object,
});

const form = useForm({
    trading_account_id: props.journal?.trading_account_id || '',
    date: props.journal?.date || '',
    mood_before: props.journal?.mood_before || '',
    plan: props.journal?.plan || '',
    review: props.journal?.review || '',
    followed_risk_rules: Boolean(props.journal?.followed_risk_rules),
});

const submit = () => {
    form.patch(route('journals.update', props.journal.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Journal Detail" />

    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Journal Detail</h1>
            <Link :href="route('journals.index')" class="rounded border px-3 py-2 text-sm">Back</Link>
        </div>

        <div class="mb-4 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Date</p>
                <p class="mt-2 text-lg font-semibold">{{ journal.date }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Trades</p>
                <p class="mt-2 text-lg font-semibold">{{ day_summary?.trade_count || 0 }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-gray-500">Total P/L</p>
                <p class="mt-2 text-lg font-semibold" :class="Number(day_summary?.total_pl || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ day_summary?.total_pl || 0 }}
                </p>
            </div>
        </div>

        <div class="mb-4 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Update Journal</h2>
            <div class="grid gap-3 md:grid-cols-3">
                <AppInput v-model="form.date" type="date" label="Date" :error="form.errors.date" />
                <AppInput v-model="form.mood_before" type="number" label="Mood (1-5)" :error="form.errors.mood_before" />
                <label class="flex items-end gap-2 pb-2 text-sm text-gray-700">
                    <input v-model="form.followed_risk_rules" type="checkbox" class="rounded border-gray-300" />
                    Followed risk rules
                </label>
            </div>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700">Plan</span>
                    <textarea v-model="form.plan" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700">Review</span>
                    <textarea v-model="form.review" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </label>
            </div>
            <div class="mt-3">
                <AppButton :loading="form.processing" @click="submit">Update Journal</AppButton>
            </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Trades of the Day</h2>
            <AppTable v-if="trades?.length">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Pair</th>
                        <th class="px-3 py-2 text-left">Direction</th>
                        <th class="px-3 py-2 text-left">Result</th>
                        <th class="px-3 py-2 text-left">P/L</th>
                        <th class="px-3 py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in trades" :key="item.id">
                        <td class="px-3 py-2">{{ item.pair }}</td>
                        <td class="px-3 py-2">
                            <AppBadge :variant="item.direction === 'buy' ? 'buy' : 'sell'">{{ item.direction }}</AppBadge>
                        </td>
                        <td class="px-3 py-2">
                            <AppBadge :variant="item.result === 'win' ? 'win' : (item.result === 'loss' ? 'loss' : (item.result === 'breakeven' ? 'breakeven' : 'partial'))">{{ item.result }}</AppBadge>
                        </td>
                        <td class="px-3 py-2" :class="Number(item.profit_loss) >= 0 ? 'text-green-600' : 'text-red-600'">{{ item.profit_loss }}</td>
                        <td class="px-3 py-2">
                            <Link :href="route('trades.show', item.id)" class="rounded border px-2 py-1 text-xs">View Trade</Link>
                        </td>
                    </tr>
                </tbody>
            </AppTable>
            <p v-else class="text-sm text-gray-500">Belum ada trade di tanggal ini.</p>
        </div>
    </AppLayout>
</template>
