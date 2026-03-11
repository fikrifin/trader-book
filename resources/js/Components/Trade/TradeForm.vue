<script setup>
import { computed, watch } from 'vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';

const props = defineProps({
    form: { type: Object, required: true },
    accounts: { type: Array, default: () => [] },
    instruments: { type: Array, default: () => [] },
    setups: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Simpan Trade' },
});

const emit = defineEmits(['submit']);

const accountOptions = computed(() => props.accounts.map((item) => ({
    value: item.id,
    label: `${item.name} (${item.account_type})`,
})));

const instrumentOptions = computed(() => props.instruments.map((item) => ({
    value: item.id,
    label: item.symbol,
})));

const setupOptions = computed(() => props.setups.map((item) => ({
    value: item.id,
    label: item.name,
})));

const pipSize = computed(() => {
    const pair = String(props.form.pair || '').toUpperCase();

    if (!pair) return 0.0001;
    if (pair.includes('JPY')) return 0.01;
    if (pair.includes('XAU')) return 0.01;
    if (pair.includes('NAS') || pair.includes('BTC')) return 1;

    return 0.0001;
});

watch(
    () => [props.form.entry_price, props.form.stop_loss, props.form.take_profit_1],
    () => {
        const entry = Number(props.form.entry_price || 0);
        const sl = Number(props.form.stop_loss || 0);
        const tp1 = Number(props.form.take_profit_1 || 0);

        if (!entry || !sl || !tp1 || Math.abs(entry - sl) === 0) return;

        props.form.rr_planned = (Math.abs(tp1 - entry) / Math.abs(entry - sl)).toFixed(2);
    }
);

watch(
    () => [props.form.entry_price, props.form.stop_loss, props.form.close_price],
    () => {
        const entry = Number(props.form.entry_price || 0);
        const sl = Number(props.form.stop_loss || 0);
        const close = Number(props.form.close_price || 0);

        if (!entry || !sl || !close || Math.abs(entry - sl) === 0) return;

        props.form.rr_ratio = (Math.abs(close - entry) / Math.abs(entry - sl)).toFixed(2);
    }
);

watch(
    () => [props.form.entry_price, props.form.close_price, props.form.direction, props.form.lot_size, props.form.commission, props.form.swap],
    () => {
        const entry = Number(props.form.entry_price || 0);
        const close = Number(props.form.close_price || 0);
        const lot = Number(props.form.lot_size || 0);
        const commission = Number(props.form.commission || 0);
        const swap = Number(props.form.swap || 0);

        if (!entry || !close || !lot) return;

        const signedPips = String(props.form.direction) === 'sell'
            ? (entry - close) / pipSize.value
            : (close - entry) / pipSize.value;

        const gross = signedPips * 10 * lot;
        props.form.pips = signedPips.toFixed(1);
        props.form.profit_loss_gross = gross.toFixed(2);
        props.form.profit_loss = (gross - commission - Math.abs(swap)).toFixed(2);
    }
);
</script>

<template>
    <form class="space-y-5" @submit.prevent="emit('submit')">
        <div class="grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4">
            <AppInput v-model="form.date" type="date" label="Date" :error="form.errors.date" />
            <AppInput v-model="form.open_time" type="time" label="Open Time" :error="form.errors.open_time" />
            <AppInput v-model="form.close_time" type="time" label="Close Time" :error="form.errors.close_time" />
            <AppSelect v-model="form.trading_account_id" label="Account" :options="accountOptions" :error="form.errors.trading_account_id" />
            <AppInput v-model="form.pair" label="Pair" placeholder="XAUUSD" :error="form.errors.pair" />
            <AppSelect v-model="form.instrument_id" label="Instrument (optional)" :options="instrumentOptions" :error="form.errors.instrument_id" />
            <AppSelect v-model="form.direction" label="Direction" :options="[{ value: 'buy', label: 'BUY' }, { value: 'sell', label: 'SELL' }]" :error="form.errors.direction" />
            <AppSelect v-model="form.session" label="Session" :options="[
                { value: 'asia', label: 'Asia' },
                { value: 'london', label: 'London' },
                { value: 'new_york', label: 'New York' },
                { value: 'overlap', label: 'Overlap' },
            ]" :error="form.errors.session" />
            <AppInput v-model="form.timeframe" label="Timeframe" placeholder="M15 / H1" :error="form.errors.timeframe" />
        </div>

        <div class="grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4">
            <AppInput v-model="form.entry_price" type="number" step="0.00001" label="Entry" :error="form.errors.entry_price" />
            <AppInput v-model="form.stop_loss" type="number" step="0.00001" label="Stop Loss" :error="form.errors.stop_loss" />
            <AppInput v-model="form.take_profit_1" type="number" step="0.00001" label="TP1" :error="form.errors.take_profit_1" />
            <AppInput v-model="form.close_price" type="number" step="0.00001" label="Close Price" :error="form.errors.close_price" />
            <AppInput v-model="form.take_profit_2" type="number" step="0.00001" label="TP2 (optional)" :error="form.errors.take_profit_2" />
            <AppInput v-model="form.take_profit_3" type="number" step="0.00001" label="TP3 (optional)" :error="form.errors.take_profit_3" />
            <AppInput v-model="form.lot_size" type="number" step="0.01" label="Lot Size" :error="form.errors.lot_size" />
            <AppInput v-model="form.risk_amount" type="number" step="0.01" label="Risk Amount" :error="form.errors.risk_amount" />
        </div>

        <div class="grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4">
            <AppInput v-model="form.commission" type="number" step="0.01" label="Commission" :error="form.errors.commission" />
            <AppInput v-model="form.swap" type="number" step="0.01" label="Swap" :error="form.errors.swap" />
            <AppSelect v-model="form.result" label="Result" :options="[
                { value: 'win', label: 'Win' },
                { value: 'loss', label: 'Loss' },
                { value: 'breakeven', label: 'Breakeven' },
                { value: 'partial', label: 'Partial' },
            ]" :error="form.errors.result" />
            <AppInput v-model="form.pips" type="number" step="0.1" label="Pips" :error="form.errors.pips" />
            <AppInput v-model="form.profit_loss_gross" type="number" step="0.01" label="P/L Gross" :error="form.errors.profit_loss_gross" />
            <AppInput v-model="form.profit_loss" type="number" step="0.01" label="P/L Net" :error="form.errors.profit_loss" />
            <AppInput v-model="form.rr_planned" type="number" step="0.01" label="RR Planned" :error="form.errors.rr_planned" />
            <AppInput v-model="form.rr_ratio" type="number" step="0.01" label="RR Actual" :error="form.errors.rr_ratio" />
        </div>

        <div class="grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-2">
            <AppSelect v-model="form.setup_id" label="Setup (optional)" :options="setupOptions" :error="form.errors.setup_id" />
            <AppInput v-model="form.setup" label="Setup (custom text)" :error="form.errors.setup" />
            <AppInput v-model="form.tags_text" label="Tags (comma separated)" placeholder="FOMO, Revenge" />

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input v-model="form.followed_plan" type="checkbox" class="rounded border-gray-300" />
                Followed Plan
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Mistake</span>
                <textarea v-model="form.mistake" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" rows="3" />
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Notes</span>
                <textarea v-model="form.notes" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" rows="3" />
            </label>
        </div>

        <div class="grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-2">
            <label class="block text-sm">
                <span class="mb-1 block font-medium text-gray-700">Screenshot Before</span>
                <input type="file" accept="image/*" @change="form.screenshot_before = $event.target.files[0]" />
                <span v-if="form.errors.screenshot_before" class="mt-1 block text-xs text-red-600">{{ form.errors.screenshot_before }}</span>
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-medium text-gray-700">Screenshot After</span>
                <input type="file" accept="image/*" @change="form.screenshot_after = $event.target.files[0]" />
                <span v-if="form.errors.screenshot_after" class="mt-1 block text-xs text-red-600">{{ form.errors.screenshot_after }}</span>
            </label>
        </div>

        <div>
            <AppButton type="submit" :loading="form.processing">{{ submitLabel }}</AppButton>
        </div>
    </form>
</template>
