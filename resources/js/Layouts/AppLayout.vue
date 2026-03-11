<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppToast from '@/Components/UI/AppToast.vue';

const page = usePage();

const user = computed(() => page.props.auth?.user || null);
const accounts = computed(() => page.props.trading_accounts || []);

const switchForm = useForm({
    trading_account_id: user.value?.active_account_id || page.props.active_account?.id || '',
});

const menu = [
    { label: 'Dashboard', href: route('dashboard') },
    { label: 'Trades', href: route('trades.index') },
    { label: 'Journal', href: route('journals.index') },
    { label: 'Statistics', href: route('statistics.index') },
    { label: 'Accounts', href: route('settings.accounts.index') },
    { label: 'Instruments', href: route('settings.instruments.index') },
    { label: 'Setups', href: route('settings.setups.index') },
    { label: 'Targets', href: route('settings.targets.index') },
    { label: 'Profile', href: route('settings.profile') },
];

const accountOptions = computed(() => accounts.value.map((item) => ({
    value: item.id,
    label: `${item.name} (${item.account_type})`,
})));

const switchAccount = () => {
    if (!switchForm.trading_account_id) return;
    switchForm.post(route('accounts.switch'), { preserveScroll: true });
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
                        v-for="item in menu"
                        :key="item.label"
                        :href="item.href"
                        class="block rounded-md px-3 py-2 text-sm hover:bg-gray-800"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>

            <div class="flex-1">
                <header class="border-b border-gray-200 bg-white px-4 py-3 lg:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
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
    </div>
</template>
