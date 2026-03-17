<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    instruments: Array,
    history: Array,
});

const timeframes = [
    { value: 'M15', label: 'M15' },
    { value: 'M30', label: 'M30' },
    { value: 'H1', label: 'H1' },
    { value: 'H4', label: 'H4' },
    { value: 'D1', label: 'D1' },
];

const instrumentOptions = (props.instruments || []).map((item) => ({
    value: item.id,
    label: `${item.symbol} - ${item.name}`,
}));

const form = useForm({
    instrument_id: instrumentOptions[0]?.value || '',
    timeframe: 'H1',
    strategy_preference: '',
});

const generate = () => {
    form.post(route('ai.recommendations.store'), {
        preserveScroll: true,
    });
};

const latest = computed(() => (props.history || [])[0] || null);
</script>

<template>
    <Head title="AI Assistant" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">AI Assistant</h1>

        <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50/60 p-4 text-sm text-blue-900">
            AI bersifat decision support, bukan nasihat keuangan. Selalu validasi ulang dengan risk management Anda.
        </div>

        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Generate Recommendation</h2>
            <div class="grid gap-3 md:grid-cols-3">
                <AppSelect
                    v-model="form.instrument_id"
                    label="Instrument"
                    :options="instrumentOptions"
                    :error="form.errors.instrument_id"
                />
                <AppSelect
                    v-model="form.timeframe"
                    label="Timeframe"
                    :options="timeframes"
                    :error="form.errors.timeframe"
                />
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700">Strategy Preference (optional)</span>
                    <textarea
                        v-model="form.strategy_preference"
                        rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none transition-colors duration-150 hover:border-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                        placeholder="Contoh: fokus momentum pullback, hindari entry agresif"
                    />
                    <span v-if="form.errors.strategy_preference" class="mt-1 block text-xs text-red-600">{{ form.errors.strategy_preference }}</span>
                </label>
            </div>
            <div class="mt-3">
                <AppButton :loading="form.processing" :disabled="!form.instrument_id" @click="generate">
                    Generate AI Recommendation
                </AppButton>
            </div>
        </div>

        <div v-if="latest" class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Latest Recommendation</h2>

            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-md border border-gray-200 p-3">
                    <p class="text-[11px] uppercase text-gray-500">Bias</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ latest.recommendation?.market_bias || '-' }}</p>
                </div>
                <div class="rounded-md border border-gray-200 p-3">
                    <p class="text-[11px] uppercase text-gray-500">Action</p>
                    <p class="mt-1 text-sm font-semibold" :class="latest.recommendation?.recommended_action === 'buy' ? 'text-emerald-600' : (latest.recommendation?.recommended_action === 'sell' ? 'text-red-600' : 'text-gray-700')">
                        {{ latest.recommendation?.recommended_action || '-' }}
                    </p>
                </div>
                <div class="rounded-md border border-gray-200 p-3">
                    <p class="text-[11px] uppercase text-gray-500">Risk %</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ latest.recommendation?.risk_pct ?? '-' }}</p>
                </div>
                <div class="rounded-md border border-gray-200 p-3">
                    <p class="text-[11px] uppercase text-gray-500">Confidence</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ latest.recommendation?.confidence ?? '-' }}</p>
                </div>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div class="rounded-md border border-gray-200 p-3">
                    <p class="text-[11px] uppercase text-gray-500">Plan</p>
                    <p class="mt-1 text-sm text-gray-700">Entry: {{ latest.recommendation?.entry_zone || '-' }}</p>
                    <p class="mt-1 text-sm text-gray-700">Stop Loss: {{ latest.recommendation?.stop_loss || '-' }}</p>
                    <p class="mt-1 text-sm text-gray-700">Take Profit: {{ latest.recommendation?.take_profit || '-' }}</p>
                    <p class="mt-1 text-sm text-gray-700">Invalidation: {{ latest.recommendation?.invalidation || '-' }}</p>
                </div>
                <div class="rounded-md border border-gray-200 p-3">
                    <p class="text-[11px] uppercase text-gray-500">Checklist</p>
                    <ul class="mt-1 list-disc space-y-1 pl-4 text-sm text-gray-700">
                        <li v-for="(item, idx) in (latest.recommendation?.checklist || [])" :key="`check-${idx}`">{{ item }}</li>
                    </ul>
                </div>
            </div>

            <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                {{ latest.recommendation?.warning || 'Gunakan rekomendasi sebagai decision support, bukan sinyal pasti.' }}
            </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">History Recommendation</h2>

            <AppTable v-if="history?.length">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Time</th>
                        <th class="px-3 py-2 text-left">Instrument</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Action</th>
                        <th class="px-3 py-2 text-left">Confidence</th>
                        <th class="px-3 py-2 text-left">Warning</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in history" :key="item.id">
                        <td class="px-3 py-2 text-xs text-gray-500">{{ item.created_at }}</td>
                        <td class="px-3 py-2">{{ item.prompt_context?.instrument?.symbol || '-' }}</td>
                        <td class="px-3 py-2">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="item.status === 'success' ? 'bg-emerald-100 text-emerald-700' : (item.status === 'blocked' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                            >
                                {{ item.status }}
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ item.recommendation?.recommended_action || '-' }}</td>
                        <td class="px-3 py-2">{{ item.recommendation?.confidence ?? '-' }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ item.recommendation?.warning || item.error_message || '-' }}</td>
                    </tr>
                </tbody>
            </AppTable>

            <AppEmptyState v-else>
                <template #title>Belum ada recommendation</template>
                Generate rekomendasi pertama Anda dari form di atas.
            </AppEmptyState>
        </div>
    </AppLayout>
</template>
