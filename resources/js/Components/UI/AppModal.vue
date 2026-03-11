<script setup>
defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="emit('close')">
        <div class="w-full max-w-lg rounded-xl bg-white p-4 shadow-xl">
            <slot />
        </div>
    </div>
</template>
