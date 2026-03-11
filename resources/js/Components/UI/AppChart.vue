<script setup>
import { computed } from 'vue';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    BarElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler,
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    BarElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler,
);

const props = defineProps({
    type: { type: String, default: 'line' },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({ responsive: true, maintainAspectRatio: false }) },
    heightClass: { type: String, default: 'h-72' },
});

const component = computed(() => {
    if (props.type === 'bar') return Bar;
    if (props.type === 'doughnut') return Doughnut;

    return Line;
});
</script>

<template>
    <div :class="heightClass">
        <component :is="component" :data="data" :options="options" />
    </div>
</template>