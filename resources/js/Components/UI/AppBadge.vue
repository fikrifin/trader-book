<script setup>
defineProps({
    variant: { type: String, default: 'info' },
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1"
        :class="{
            'bg-emerald-50 text-emerald-700 ring-emerald-200': variant === 'win',
            'bg-red-50 text-red-700 ring-red-200': variant === 'loss',
            'bg-amber-50 text-amber-700 ring-amber-200': variant === 'breakeven',
            'bg-blue-50 text-blue-700 ring-blue-200': variant === 'info' || variant === 'partial',
            'bg-sky-50 text-sky-700 ring-sky-200': variant === 'buy',
            'bg-orange-50 text-orange-700 ring-orange-200': variant === 'sell',
            'bg-amber-50 text-amber-700 ring-amber-200': variant === 'warning',
        }"
    >
        <span class="mr-1 inline-block size-1.5 rounded-full bg-current" />
        <slot />
    </span>
</template>
