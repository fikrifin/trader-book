<script setup>
const props = defineProps({
    variant: { type: String, default: 'text' },
    lines: { type: Number, default: 3 },
});
</script>

<template>
    <div v-if="variant === 'card'" class="animate-pulse rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="mb-3 h-4 w-1/3 rounded bg-gray-200" />
        <div class="h-7 w-2/3 rounded bg-gray-200" />
    </div>

    <div v-else-if="variant === 'chart'" class="animate-pulse rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="mb-3 h-4 w-1/4 rounded bg-gray-200" />
        <div class="h-56 rounded bg-gray-200" />
    </div>

    <div v-else-if="variant === 'table-row'" class="animate-pulse space-y-2">
        <div
            v-for="item in lines"
            :key="item"
            class="grid grid-cols-6 gap-2 rounded-lg border border-gray-100 bg-white px-3 py-2"
        >
            <div class="col-span-2 h-4 rounded bg-gray-200" />
            <div class="h-4 rounded bg-gray-200" />
            <div class="h-4 rounded bg-gray-200" />
            <div class="h-4 rounded bg-gray-200" />
            <div class="h-4 rounded bg-gray-200" />
        </div>
    </div>

    <div v-else class="animate-pulse space-y-2">
        <div v-for="item in lines" :key="item" class="h-4 rounded bg-gray-200" />
    </div>
</template>
