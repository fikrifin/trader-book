<script setup>
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    duration: { type: Number, default: 700 },
    decimals: { type: Number, default: 0 },
    suffix: { type: String, default: '' },
});

const displayValue = ref(0);
let animationFrame = null;

const easeOutCubic = (t) => 1 - (1 - t) ** 3;

const runAnimation = (target) => {
    if (animationFrame) {
        cancelAnimationFrame(animationFrame);
        animationFrame = null;
    }

    const startValue = Number(displayValue.value || 0);
    const change = target - startValue;
    const startTime = performance.now();

    const tick = (now) => {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / props.duration, 1);
        displayValue.value = startValue + (change * easeOutCubic(progress));

        if (progress < 1) {
            animationFrame = requestAnimationFrame(tick);
        }
    };

    animationFrame = requestAnimationFrame(tick);
};

watch(() => props.value, (nextValue) => {
    runAnimation(Number(nextValue || 0));
}, { immediate: true });

onMounted(() => {
    runAnimation(Number(props.value || 0));
});

const formattedValue = computed(() => `${displayValue.value.toFixed(props.decimals)}${props.suffix}`);
</script>

<template>
    <span>{{ formattedValue }}</span>
</template>
