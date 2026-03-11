<template>
    <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
        <div class="mb-2 font-semibold text-gray-700">
            <slot name="title">Data kosong</slot>
        </div>
        <div>
            <slot>Belum ada data.</slot>
        </div>
    </div>
</template>
