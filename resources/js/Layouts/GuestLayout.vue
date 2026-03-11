<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-brand-50 via-white to-gray-100">
        <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-brand-200/40 blur-3xl" />
        <div class="pointer-events-none absolute -bottom-24 -right-24 h-80 w-80 rounded-full bg-indigo-200/30 blur-3xl" />

        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="hidden w-full max-w-xl flex-1 lg:block">
                <p class="mb-4 inline-flex rounded-full border border-brand-100 bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-brand-700">
                    Trading Journal
                </p>
                <h1 class="text-4xl font-bold leading-tight text-gray-900">
                    Build better trading habits with a cleaner workflow.
                </h1>
                <p class="mt-4 max-w-lg text-sm text-gray-600">
                    Track trades, review psychology, and monitor performance in one elegant workspace designed for focus.
                </p>
                <div class="mt-8 grid max-w-md grid-cols-2 gap-3">
                    <div class="rounded-xl border border-white/70 bg-white/80 p-3 shadow-sm">
                        <p class="text-xs text-gray-500">Smart Metrics</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">Win rate, PF, drawdown</p>
                    </div>
                    <div class="rounded-xl border border-white/70 bg-white/80 p-3 shadow-sm">
                        <p class="text-xs text-gray-500">Daily Journal</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">Plan, mood, review</p>
                    </div>
                </div>
            </div>

            <div class="w-full lg:max-w-md">
                <div class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-xl backdrop-blur-md sm:p-6">
                    <div class="mb-5 flex items-center gap-3">
                        <Link href="/" class="flex items-center gap-2">
                            <div class="rounded-xl bg-brand-50 p-2 text-brand-600 ring-1 ring-brand-100">
                                <ApplicationLogo class="h-7 w-7 fill-current" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Trader Book</p>
                                <p class="text-xs text-gray-500">Discipline over impulse</p>
                            </div>
                        </Link>
                    </div>

                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
