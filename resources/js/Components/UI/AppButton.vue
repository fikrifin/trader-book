<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const variantClass = computed(() => {
    if (props.variant === 'secondary') return 'bg-gray-100 text-gray-800 hover:bg-gray-200';
    if (props.variant === 'danger') return 'bg-red-600 text-white hover:bg-red-700';
    if (props.variant === 'ghost') return 'bg-transparent text-gray-700 hover:bg-gray-100';
    return 'bg-indigo-600 text-white hover:bg-indigo-700';
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-60"
        :class="variantClass"
    >
        <svg v-if="loading" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" class="opacity-80" />
        </svg>
        <slot />
    </button>
</template>
