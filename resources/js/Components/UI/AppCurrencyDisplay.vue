<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    value: { type: [Number, String], default: 0 },
    currency: { type: String, default: '' },
    minimumFractionDigits: { type: Number, default: 2 },
    maximumFractionDigits: { type: Number, default: 2 },
    showPlus: { type: Boolean, default: false },
});

const page = usePage();

const activeAccountCurrency = computed(() => page.props.active_account?.currency || '');
const userCurrencyPreference = computed(() => page.props.auth?.user?.currency_preference || '');

const resolvedCurrency = computed(() => {
    return props.currency || activeAccountCurrency.value || userCurrencyPreference.value || 'USD';
});

const formatted = computed(() => {
    const numericValue = Number(props.value || 0);

    const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: resolvedCurrency.value,
        minimumFractionDigits: props.minimumFractionDigits,
        maximumFractionDigits: props.maximumFractionDigits,
    });

    const result = formatter.format(numericValue);
    if (props.showPlus && numericValue > 0) {
        return `+${result}`;
    }

    return result;
});
</script>

<template>
    <span>{{ formatted }}</span>
</template>