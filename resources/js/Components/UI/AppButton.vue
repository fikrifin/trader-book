<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const variantClass = computed(() => {
    if (props.variant === 'secondary') return 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50';
    if (props.variant === 'danger') return 'bg-gradient-to-r from-red-600 to-red-500 text-white hover:from-red-700 hover:to-red-600';
    if (props.variant === 'ghost') return 'bg-transparent text-gray-700 hover:bg-gray-100';
    return 'bg-gradient-to-r from-brand-600 to-brand-500 text-white hover:from-brand-700 hover:to-brand-600';
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition-all duration-150 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
        :class="variantClass"
    >
        <svg v-if="loading" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-20" />
            <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="3" class="opacity-90" />
        </svg>
        <slot />
    </button>
</template>
