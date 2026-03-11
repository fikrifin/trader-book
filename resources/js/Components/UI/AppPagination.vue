<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, default: () => [] },
});
</script>

<template>
    <div v-if="links.length" class="mt-4 flex flex-wrap gap-2">
        <Link
            v-for="link in links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded border px-3 py-1 text-sm"
            :class="link.active ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600'"
            v-html="link.label"
        />
    </div>
</template>
