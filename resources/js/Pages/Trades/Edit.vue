<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import TradeForm from '@/Components/Trade/TradeForm.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    trade: Object,
    accounts: Array,
    instruments: Array,
    setups: Array,
});

const normalizeDateForInput = (value) => {
    if (!value) return '';

    const text = String(value);
    if (text.length >= 10) {
        return text.slice(0, 10);
    }

    return text;
};

const normalizeTimeForInput = (value) => {
    if (!value) return '';

    const text = String(value);
    const parts = text.split(':');
    if (parts.length >= 2) {
        return `${parts[0]}:${parts[1]}`;
    }

    return text;
};

const form = useForm({
    trading_account_id: props.trade?.trading_account_id || '',
    instrument_id: props.trade?.instrument_id || '',
    setup_id: props.trade?.setup_id || '',
    date: normalizeDateForInput(props.trade?.date),
    open_time: normalizeTimeForInput(props.trade?.open_time),
    close_time: normalizeTimeForInput(props.trade?.close_time),
    session: props.trade?.session || 'asia',
    pair: props.trade?.pair || '',
    direction: props.trade?.direction || 'buy',
    entry_price: props.trade?.entry_price || '',
    stop_loss: props.trade?.stop_loss || '',
    take_profit_1: props.trade?.take_profit_1 || '',
    take_profit_2: props.trade?.take_profit_2 || '',
    take_profit_3: props.trade?.take_profit_3 || '',
    close_price: props.trade?.close_price || '',
    lot_size: props.trade?.lot_size || '',
    risk_amount: props.trade?.risk_amount || '',
    commission: props.trade?.commission || 0,
    swap: props.trade?.swap || 0,
    result: props.trade?.result || 'breakeven',
    profit_loss: props.trade?.profit_loss || '',
    profit_loss_gross: props.trade?.profit_loss_gross || '',
    pips: props.trade?.pips || '',
    rr_ratio: props.trade?.rr_ratio || '',
    rr_planned: props.trade?.rr_planned || '',
    setup: props.trade?.setup || '',
    timeframe: props.trade?.timeframe || '',
    followed_plan: Boolean(props.trade?.followed_plan ?? true),
    mistake: props.trade?.mistake || '',
    notes: props.trade?.notes || '',
    tags_text: Array.isArray(props.trade?.tags) ? props.trade.tags.join(', ') : '',
    tags: Array.isArray(props.trade?.tags) ? props.trade.tags : [],
    screenshot_before: null,
    screenshot_after: null,
});

const submit = () => {
    form.tags = String(form.tags_text || '')
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean);

    form.transform((data) => ({
        ...data,
        _method: 'patch',
    }));

    form.post(route('trades.update', props.trade.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Edit Trade" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Edit Trade</h1>

        <TradeForm
            :form="form"
            :accounts="accounts || []"
            :instruments="instruments || []"
            :setups="setups || []"
            submit-label="Update Trade"
            @submit="submit"
        />
    </AppLayout>
</template>
