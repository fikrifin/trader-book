<template>
    <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">
        <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-xl bg-white text-brand-500 shadow-sm ring-1 ring-gray-100">
            <svg viewBox="0 0 24 24" fill="none" class="size-6">
                <path d="M4 7h16M7 12h10M9 17h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </div>
        <div class="mb-2 text-base font-semibold text-gray-700">
            <slot name="title">Data kosong</slot>
        </div>
        <div class="mx-auto max-w-md text-gray-500">
            <slot>Belum ada data.</slot>
        </div>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
