<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Bars3Icon,
    XMarkIcon,
    ChevronDownIcon,
    HomeIcon,
    ChartBarIcon,
    BookOpenIcon,
    ClipboardDocumentListIcon,
    Cog6ToothIcon,
    UserIcon,
    WrenchScrewdriverIcon,
    FlagIcon,
    ArrowRightOnRectangleIcon,
    LifebuoyIcon,
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
const desktopSettingsOpen = ref(route().current('settings.*'));
const mobileSettingsOpen = ref(route().current('settings.*'));

const menu = [
    { label: 'Dashboard', href: route('dashboard'), current: 'dashboard', icon: HomeIcon },
    { label: 'Trades', href: route('trades.index'), current: 'trades.*', icon: ClipboardDocumentListIcon },
    { label: 'Journal', href: route('journals.index'), current: 'journals.*', icon: BookOpenIcon },
    { label: 'Statistics', href: route('statistics.index'), current: 'statistics.*', icon: ChartBarIcon },
];

const settingsMenu = [
    { label: 'Accounts', href: route('settings.accounts.index'), current: 'settings.accounts.*', icon: UserIcon },
    { label: 'Instruments', href: route('settings.instruments.index'), current: 'settings.instruments.*', icon: WrenchScrewdriverIcon },
    { label: 'Setups', href: route('settings.setups.index'), current: 'settings.setups.*', icon: ClipboardDocumentListIcon },
    { label: 'Targets', href: route('settings.targets.index'), current: 'settings.targets.*', icon: FlagIcon },
    { label: 'Profile', href: route('settings.profile'), current: 'settings.profile*', icon: UserIcon },
];

const desktopMenu = computed(() => menu);
const primaryMobileMenu = computed(() => [menu[0], menu[1], menu[2], menu[3]]);

const accountOptions = computed(() => accounts.value.map((item) => ({
    value: item.id,
    label: `● ${item.name} (${item.account_type})`,
})));

const userInitial = computed(() => (user.value?.name || 'U').trim().charAt(0).toUpperCase());

const switchAccount = () => {
    if (!switchForm.trading_account_id) return;
    switchForm.post(route('accounts.switch'), { preserveScroll: true });
};

const isActive = (pattern) => route().current(pattern);

const closeMobileMenu = () => {
    mobileOpen.value = false;
};

const toggleDesktopSettings = () => {
    desktopSettingsOpen.value = !desktopSettingsOpen.value;
};

const toggleMobileSettings = () => {
    mobileSettingsOpen.value = !mobileSettingsOpen.value;
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <AppToast />

        <div class="flex min-h-screen">
            <aside class="hidden w-72 shrink-0 bg-gradient-to-b from-gray-950 via-gray-900 to-gray-950 p-4 text-white lg:block">
                <div class="mb-4 flex items-center gap-2 text-lg font-bold">
                    <div class="rounded-lg bg-brand-500/20 p-1.5 text-brand-300 ring-1 ring-brand-400/30">
                        <ChartBarIcon class="size-4" />
                    </div>
                    Trader Book
                </div>

                <div class="mb-6 rounded-xl border border-white/10 bg-white/5 p-3">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-full bg-brand-500/30 text-sm font-semibold text-brand-100">
                            {{ userInitial }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">{{ user?.name }}</p>
                            <p class="text-xs text-gray-300">Active account</p>
                        </div>
                    </div>
                </div>

                <nav class="space-y-1">
                    <Link
                        v-for="item in desktopMenu"
                        :key="item.label"
                        :href="item.href"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="isActive(item.current) ? 'bg-brand-600/90 text-white shadow-[0_0_12px_rgb(79_70_229/0.4)]' : 'text-gray-200 hover:bg-white/5'"
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.label }}
                    </Link>

                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition"
                        :class="isActive('settings.*') ? 'bg-brand-600/90 text-white shadow-[0_0_12px_rgb(79_70_229/0.4)]' : 'text-gray-200 hover:bg-white/5'"
                        @click="toggleDesktopSettings"
                    >
                        <span class="flex items-center gap-2">
                            <Cog6ToothIcon class="size-4" />
                            Settings
                        </span>
                        <ChevronDownIcon class="size-4 transition" :class="desktopSettingsOpen ? 'rotate-180' : ''" />
                    </button>

                    <div v-if="desktopSettingsOpen" class="ml-6 space-y-1 border-l border-white/10 pl-3">
                        <Link
                            v-for="item in settingsMenu"
                            :key="`desktop-settings-${item.label}`"
                            :href="item.href"
                            class="flex items-center gap-2 rounded-md px-3 py-2 text-sm transition"
                            :class="isActive(item.current) ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5'"
                        >
                            <component :is="item.icon" class="size-4" />
                            {{ item.label }}
                        </Link>
                    </div>
                </nav>

                <div class="mt-8 border-t border-white/10 pt-4 text-xs text-gray-400">
                    <div class="mb-2 flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-white/5">
                        <LifebuoyIcon class="size-4" />
                        Help
                    </div>
                    <p class="px-2">v1.0</p>
                </div>
            </aside>

            <div class="flex-1">
                <header class="sticky top-0 z-20 border-b border-gray-100 bg-white/80 px-4 py-3 backdrop-blur-md lg:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-md border border-gray-200 p-2 text-gray-700 lg:hidden"
                                @click="mobileOpen = true"
                            >
                                <Bars3Icon class="size-5" />
                            </button>
                            <div class="flex items-center gap-2">
                                <div class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">
                                    {{ userInitial }}
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-500">Logged as</p>
                                    <p class="text-sm font-semibold text-gray-800">{{ user?.name }}</p>
                                </div>
                            </div>
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
                                class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm text-gray-600 transition hover:bg-red-50 hover:text-red-600"
                            >
                                <ArrowRightOnRectangleIcon class="size-4" />
                                Logout
                            </Link>
                        </div>
                    </div>
                </header>

                <main class="p-4 pb-20 lg:p-6 lg:pb-6">
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
            <aside v-if="mobileOpen" class="fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-gray-950 via-gray-900 to-gray-950 p-4 text-white lg:hidden">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-lg font-bold">
                        <div class="rounded-lg bg-brand-500/20 p-1.5 text-brand-300 ring-1 ring-brand-400/30">
                            <ChartBarIcon class="size-4" />
                        </div>
                        Trader Book
                    </div>
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
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="isActive(item.current) ? 'bg-brand-600/90 text-white shadow-[0_0_12px_rgb(79_70_229/0.4)]' : 'text-gray-200 hover:bg-white/5'"
                        @click="closeMobileMenu"
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.label }}
                    </Link>

                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm"
                        :class="isActive('settings.*') ? 'bg-brand-600/90 text-white shadow-[0_0_12px_rgb(79_70_229/0.4)]' : 'text-gray-200 hover:bg-white/5'"
                        @click="toggleMobileSettings"
                    >
                        <span class="flex items-center gap-2">
                            <Cog6ToothIcon class="size-4" />
                            Settings
                        </span>
                        <ChevronDownIcon class="size-4 transition" :class="mobileSettingsOpen ? 'rotate-180' : ''" />
                    </button>

                    <div v-if="mobileSettingsOpen" class="ml-6 space-y-1 border-l border-white/10 pl-3">
                        <Link
                            v-for="item in settingsMenu"
                            :key="`mobile-settings-${item.label}`"
                            :href="item.href"
                            class="flex items-center gap-2 rounded-md px-3 py-2 text-sm"
                            :class="isActive(item.current) ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5'"
                            @click="closeMobileMenu"
                        >
                            <component :is="item.icon" class="size-4" />
                            {{ item.label }}
                        </Link>
                    </div>
                </nav>
            </aside>
        </Transition>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white/90 backdrop-blur-md lg:hidden">
            <div class="grid grid-cols-4">
                <Link
                    v-for="item in primaryMobileMenu"
                    :key="`bottom-${item.label}`"
                    :href="item.href"
                    class="relative flex flex-col items-center gap-1 py-2 text-xs"
                    :class="isActive(item.current) ? 'text-brand-600' : 'text-gray-600'"
                >
                    <span v-if="isActive(item.current)" class="absolute inset-x-2 top-0 h-0.5 rounded-full bg-brand-500" />
                    <component :is="item.icon" class="size-5" />
                    <span>{{ item.label }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
