<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm" @click.self="emit('close')">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                <div v-if="title || $slots.header" class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <slot name="header">
                        <h3 class="text-sm font-semibold text-gray-900">{{ title }}</h3>
                    </slot>
                    <button type="button" class="rounded-md p-1 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700" @click="emit('close')">
                        <XMarkIcon class="size-5" />
                    </button>
                </div>
                <div class="p-4">
                    <slot />
                </div>
            </div>
        </div>
    </Transition>
</template>
