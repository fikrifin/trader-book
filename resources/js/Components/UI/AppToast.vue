<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, ExclamationTriangleIcon, XCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const page = usePage();

const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
const warning = computed(() => page.props.flash?.warning);

const successVisible = ref(false);
const warningVisible = ref(false);
const errorVisible = ref(false);

watch(success, (value) => {
    if (!value) return;
    successVisible.value = true;
    setTimeout(() => {
        successVisible.value = false;
    }, 4000);
}, { immediate: true });

watch(warning, (value) => {
    if (!value) return;
    warningVisible.value = true;
    setTimeout(() => {
        warningVisible.value = false;
    }, 4000);
}, { immediate: true });

watch(error, (value) => {
    if (!value) return;
    errorVisible.value = true;
    setTimeout(() => {
        errorVisible.value = false;
    }, 4000);
}, { immediate: true });
</script>

<template>
    <div class="fixed right-4 top-4 z-[60] w-80 space-y-2">
        <Transition
            enter-active-class="transform transition duration-200 ease-out"
            enter-from-class="translate-x-4 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="success && successVisible" class="overflow-hidden rounded-xl border border-emerald-100 border-l-4 border-l-emerald-500 bg-white shadow-lg">
                <div class="flex items-start gap-2 px-3 py-2 text-sm text-emerald-700">
                    <CheckCircleIcon class="mt-0.5 size-5 shrink-0" />
                    <p class="flex-1">{{ success }}</p>
                    <button type="button" class="rounded p-0.5 hover:bg-emerald-50" @click="successVisible = false"><XMarkIcon class="size-4" /></button>
                </div>
                <div class="toast-progress bg-emerald-500" />
            </div>
        </Transition>

        <Transition
            enter-active-class="transform transition duration-200 ease-out"
            enter-from-class="translate-x-4 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="warning && warningVisible" class="overflow-hidden rounded-xl border border-amber-100 border-l-4 border-l-amber-500 bg-white shadow-lg">
                <div class="flex items-start gap-2 px-3 py-2 text-sm text-amber-700">
                    <ExclamationTriangleIcon class="mt-0.5 size-5 shrink-0" />
                    <p class="flex-1">{{ warning }}</p>
                    <button type="button" class="rounded p-0.5 hover:bg-amber-50" @click="warningVisible = false"><XMarkIcon class="size-4" /></button>
                </div>
                <div class="toast-progress bg-amber-500" />
            </div>
        </Transition>

        <Transition
            enter-active-class="transform transition duration-200 ease-out"
            enter-from-class="translate-x-4 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="error && errorVisible" class="overflow-hidden rounded-xl border border-red-100 border-l-4 border-l-red-500 bg-white shadow-lg">
                <div class="flex items-start gap-2 px-3 py-2 text-sm text-red-700">
                    <XCircleIcon class="mt-0.5 size-5 shrink-0" />
                    <p class="flex-1">{{ error }}</p>
                    <button type="button" class="rounded p-0.5 hover:bg-red-50" @click="errorVisible = false"><XMarkIcon class="size-4" /></button>
                </div>
                <div class="toast-progress bg-red-500" />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.toast-progress {
    height: 2px;
    animation: toastProgress 4s linear forwards;
}

@keyframes toastProgress {
    from {
        width: 100%;
    }
    to {
        width: 0;
    }
}
</style>
