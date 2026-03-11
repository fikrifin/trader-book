<script setup>
defineProps({
    variant: { type: String, default: 'info' },
});
</script>

<template>
    <span
        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="{
            'bg-green-100 text-green-700': variant === 'win',
            'bg-red-100 text-red-700': variant === 'loss',
            'bg-yellow-100 text-yellow-700': variant === 'breakeven',
            'bg-blue-100 text-blue-700': variant === 'info' || variant === 'partial',
            'bg-emerald-100 text-emerald-700': variant === 'buy',
            'bg-orange-100 text-orange-700': variant === 'sell',
            'bg-amber-100 text-amber-700': variant === 'warning',
        }"
    >
        <slot />
    </span>
</template>
