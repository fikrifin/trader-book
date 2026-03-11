<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    showLabel: { type: Boolean, default: false },
    color: { type: String, default: 'brand' },
});

const percent = computed(() => {
    if (!props.max) return 0;
    return Math.min(100, Math.max(0, (props.value / props.max) * 100));
});

const fillClass = computed(() => {
    if (props.color === 'success') return 'from-emerald-500 to-emerald-400';
    if (props.color === 'warning') return 'from-amber-500 to-amber-400';
    if (props.color === 'danger') return 'from-red-500 to-red-400';

    if (props.value > props.max) return 'from-red-500 to-red-400';
    if (percent.value >= 80) return 'from-amber-500 to-amber-400';
    return 'from-brand-500 to-brand-400';
});
</script>

<template>
    <div>
        <div class="relative h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
            <div
                class="relative h-2.5 rounded-full bg-gradient-to-r transition-[width] duration-300"
                :class="[fillClass, percent < 100 ? 'shimmer overflow-hidden' : '']"
                :style="{ width: `${percent}%` }"
            />
        </div>
        <p v-if="showLabel" class="mt-1 text-right text-xs font-medium text-gray-500">{{ percent.toFixed(0) }}%</p>
    </div>
</template>
