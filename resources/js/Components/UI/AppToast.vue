<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
const warning = computed(() => page.props.flash?.warning);
</script>

<template>
    <div class="fixed right-4 top-4 z-[60] w-80 space-y-2">
        <div v-if="success" class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
            {{ success }}
        </div>
        <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ error }}
        </div>
        <div v-if="warning" class="rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-700">
            {{ warning }}
        </div>
    </div>
</template>
