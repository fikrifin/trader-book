<script setup>
import { computed } from 'vue';

const props = defineProps({
    hoverable: { type: Boolean, default: false },
    gradient: { type: Boolean, default: false },
    padding: { type: String, default: 'md' },
});

const paddingClass = computed(() => {
    if (props.padding === 'sm') return 'p-3';
    if (props.padding === 'lg') return 'p-6';
    return 'p-4';
});

const wrapperClass = computed(() => [
    'relative rounded-xl border border-gray-100 bg-white shadow-[var(--shadow-card)]',
    props.hoverable ? 'transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[var(--shadow-card-hover)]' : '',
]);
</script>

<template>
    <div class="relative rounded-[14px]" :class="gradient ? 'bg-gradient-to-br from-brand-100/60 via-white to-brand-50/60 p-[1px]' : ''">
        <div :class="[wrapperClass, paddingClass]">
            <slot />
        </div>
    </div>
</template>
