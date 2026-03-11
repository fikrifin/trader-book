<script setup>
defineProps({
    type: { type: String, default: 'info' },
});
</script>

<template>
    <div
        class="rounded-lg border px-3 py-2 text-sm"
        :class="{
            'border-green-200 bg-green-50 text-green-700': type === 'success',
            'border-red-200 bg-red-50 text-red-700': type === 'error',
            'border-yellow-200 bg-yellow-50 text-yellow-700': type === 'warning',
            'border-blue-200 bg-blue-50 text-blue-700': type === 'info',
        }"
    >
        <slot />
    </div>
</template>
