<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Bars3Icon,
    XMarkIcon,
    HomeIcon,
    ChartBarIcon,
    BookOpenIcon,
    ClipboardDocumentListIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/outline';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppToast from '@/Components/UI/AppToast.vue';

const page = usePage();

const user = computed(() => page.props.auth?.user || null);
const accounts = computed(() => page.props.trading_accounts || []);

const switchForm = useForm({
    trading_account_id: user.value?.active_account_id || page.props.active_account?.id || '',
});

const mobileOpen = ref(false);

const menu = [
    { label: 'Dashboard', href: route('dashboard'), current: 'dashboard', icon: HomeIcon },
    { label: 'Trades', href: route('trades.index'), current: 'trades.*', icon: ClipboardDocumentListIcon },
    { label: 'Journal', href: route('journals.index'), current: 'journals.*', icon: BookOpenIcon },
    { label: 'Statistics', href: route('statistics.index'), current: 'statistics.*', icon: ChartBarIcon },
    { label: 'Settings', href: route('settings.accounts.index'), current: 'settings.*', icon: Cog6ToothIcon },
];

const desktopMenu = computed(() => menu.value || menu);
const primaryMobileMenu = computed(() => [menu[0], menu[1], menu[2], menu[3]]);

const accountOptions = computed(() => accounts.value.map((item) => ({
    value: item.id,
    label: `${item.name} (${item.account_type})`,
})));

const switchAccount = () => {
    if (!switchForm.trading_account_id) return;
    switchForm.post(route('accounts.switch'), { preserveScroll: true });
};

const isActive = (pattern) => route().current(pattern);

const closeMobileMenu = () => {
    mobileOpen.value = false;
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <AppToast />

        <div class="flex min-h-screen">
            <aside class="hidden w-64 shrink-0 bg-gray-900 p-4 text-white lg:block">
                <div class="mb-6 text-lg font-bold">Trader Book</div>
                <nav class="space-y-1">
                    <Link
                        v-for="item in desktopMenu"
                        :key="item.label"
                        :href="item.href"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-sm"
                        :class="isActive(item.current) ? 'bg-brand-700 text-white' : 'text-gray-200 hover:bg-gray-800'"
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>

            <div class="flex-1">
                <header class="border-b border-gray-200 bg-white px-4 py-3 lg:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-md border border-gray-200 p-2 text-gray-700 lg:hidden"
                                @click="mobileOpen = true"
                            >
                                <Bars3Icon class="size-5" />
                            </button>
                            <p class="text-xs uppercase text-gray-500">Logged as</p>
                            <p class="text-sm font-semibold text-gray-800">{{ user?.name }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="w-64">
                                <AppSelect
                                    v-model="switchForm.trading_account_id"
                                    :options="accountOptions"
                                    @change="switchAccount"
                                />
                            </div>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="rounded-lg bg-gray-900 px-3 py-2 text-sm text-white"
                            >
                                Logout
                            </Link>
                        </div>
                    </div>
                </header>

                <main class="p-4 lg:p-6">
                    <slot />
                </main>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="closeMobileMenu" />
        </Transition>

        <Transition
            enter-active-class="transform transition duration-200"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition duration-150"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <aside v-if="mobileOpen" class="fixed inset-y-0 left-0 z-50 w-72 bg-gray-900 p-4 text-white lg:hidden">
                <div class="mb-6 flex items-center justify-between">
                    <div class="text-lg font-bold">Trader Book</div>
                    <button type="button" class="rounded-md p-1 text-gray-200 hover:bg-gray-800" @click="closeMobileMenu">
                        <XMarkIcon class="size-5" />
                    </button>
                </div>

                <div class="mb-4">
                    <AppSelect
                        v-model="switchForm.trading_account_id"
                        :options="accountOptions"
                        @change="switchAccount"
                    />
                </div>

                <nav class="space-y-1">
                    <Link
                        v-for="item in desktopMenu"
                        :key="`mobile-${item.label}`"
                        :href="item.href"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-sm"
                        :class="isActive(item.current) ? 'bg-brand-700 text-white' : 'text-gray-200 hover:bg-gray-800'"
                        @click="closeMobileMenu"
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>
        </Transition>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white lg:hidden">
            <div class="grid grid-cols-4">
                <Link
                    v-for="item in primaryMobileMenu"
                    :key="`bottom-${item.label}`"
                    :href="item.href"
                    class="flex flex-col items-center gap-1 py-2 text-xs"
                    :class="isActive(item.current) ? 'text-brand-600' : 'text-gray-600'"
                >
                    <component :is="item.icon" class="size-5" />
                    <span>{{ item.label }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
