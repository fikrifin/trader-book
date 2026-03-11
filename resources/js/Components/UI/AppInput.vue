<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    name: { type: String, default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block">
        <span v-if="label" class="mb-1 block text-sm font-medium text-gray-700">{{ label }}</span>
        <input
            :name="name"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            @input="emit('update:modelValue', $event.target.value)"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none transition-colors duration-150 hover:border-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"
            :class="error ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : ''"
        />
        <span v-if="error" class="mt-1 block text-xs text-red-600">{{ error }}</span>
    </label>
</template>
