<script setup>
import AppCurrencyDisplay from '@/Components/UI/AppCurrencyDisplay.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    month: { type: String, required: true },
    tradeSummary: { type: Array, default: () => [] },
    journals: { type: Array, default: () => [] },
});

const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const summaryMap = computed(() => {
    const map = new Map();
    for (const item of props.tradeSummary || []) {
        map.set(item.date, item);
    }

    return map;
});

const journalMap = computed(() => {
    const map = new Map();
    for (const item of props.journals || []) {
        map.set(item.date, item);
    }

    return map;
});

const cells = computed(() => {
    const [year, month] = String(props.month).split('-').map(Number);
    const firstDay = new Date(year, month - 1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();

    const mondayBasedOffset = (firstDay.getDay() + 6) % 7;
    const values = [];

    for (let index = 0; index < mondayBasedOffset; index++) {
        values.push(null);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month - 1, day);
        const iso = date.toISOString().slice(0, 10);
        values.push({
            day,
            iso,
            summary: summaryMap.value.get(iso) || null,
            journal: journalMap.value.get(iso) || null,
        });
    }

    while (values.length % 7 !== 0) {
        values.push(null);
    }

    return values;
});
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
        <div class="mb-2 grid grid-cols-7 gap-2">
            <div v-for="label in weekDays" :key="label" class="text-center text-xs font-semibold uppercase text-gray-500">
                {{ label }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-2">
            <div
                v-for="(cell, index) in cells"
                :key="`cell-${index}`"
                class="min-h-24 rounded-md border border-gray-100 p-2"
                :class="cell ? 'bg-gray-50' : 'bg-transparent border-transparent'"
            >
                <template v-if="cell">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-700">{{ cell.day }}</span>
                        <span v-if="cell.journal?.mood_before" class="text-[10px] text-gray-500">Mood {{ cell.journal.mood_before }}</span>
                    </div>

                    <template v-if="cell.summary">
                        <p class="text-[10px] text-gray-500">Trades: {{ cell.summary.trade_count }}</p>
                        <p class="text-[11px] font-semibold" :class="Number(cell.summary.total_pl) >= 0 ? 'text-green-600' : 'text-red-600'">
                            <AppCurrencyDisplay :value="cell.summary.total_pl" show-plus />
                        </p>
                    </template>
                    <p v-else class="text-[10px] text-gray-400">No trades</p>

                    <div class="mt-1">
                        <Link
                            v-if="cell.journal"
                            :href="route('journals.show', cell.journal.id)"
                            class="text-[10px] font-medium text-brand-600 hover:text-brand-700"
                        >
                            Open Journal
                        </Link>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>