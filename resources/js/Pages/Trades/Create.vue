<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import TradeForm from '@/Components/Trade/TradeForm.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    accounts: Array,
    instruments: Array,
    setups: Array,
});

const form = useForm({
    trading_account_id: '',
    instrument_id: '',
    setup_id: '',
    date: new Date().toISOString().slice(0, 10),
    open_time: '',
    close_time: '',
    session: 'asia',
    pair: '',
    direction: 'buy',
    entry_price: '',
    stop_loss: '',
    take_profit_1: '',
    take_profit_2: '',
    take_profit_3: '',
    close_price: '',
    lot_size: '',
    risk_amount: '',
    commission: 0,
    swap: 0,
    result: 'breakeven',
    profit_loss: '',
    profit_loss_gross: '',
    pips: '',
    rr_ratio: '',
    rr_planned: '',
    setup: '',
    timeframe: '',
    followed_plan: true,
    mistake: '',
    notes: '',
    tags_text: '',
    tags: [],
    screenshot_before: null,
    screenshot_after: null,
});

const submit = () => {
    form.tags = String(form.tags_text || '')
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean);

    form.post(route('trades.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Create Trade" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Create Trade</h1>

        <TradeForm
            :form="form"
            :accounts="accounts || []"
            :instruments="instruments || []"
            :setups="setups || []"
            submit-label="Simpan Trade"
            @submit="submit"
        />
    </AppLayout>
</template>
