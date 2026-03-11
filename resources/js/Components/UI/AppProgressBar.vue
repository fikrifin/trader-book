<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
});

const percent = computed(() => {
    if (!props.max) return 0;
    return Math.min(100, Math.max(0, (props.value / props.max) * 100));
});
</script>

<template>
    <div class="h-2 w-full rounded-full bg-gray-200">
        <div class="h-2 rounded-full bg-indigo-600" :style="{ width: `${percent}%` }" />
    </div>
</template>
