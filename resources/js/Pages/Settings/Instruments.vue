<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    instruments: Object,
    twelvedataConfigured: Boolean,
});

const createForm = useForm({
    symbol: '',
    name: '',
    category: 'forex',
    pip_value: '',
    is_active: true,
});

const save = () => {
    createForm.post(route('settings.instruments.store'), { preserveScroll: true, onSuccess: () => createForm.reset('symbol', 'name', 'pip_value') });
};

const syncForm = useForm({
    query: '',
    limit: 20,
    category: '',
});

const syncInstruments = () => {
    syncForm.post(route('settings.instruments.sync'), {
        preserveScroll: true,
    });
};

const prices = ref({});
const pricesLoading = ref(false);
let timer = null;

const symbols = computed(() => (props.instruments?.data || []).map((item) => item.symbol).filter(Boolean));

const displayPrice = (item) => {
    const live = prices.value?.[item.symbol]?.price;

    if (live !== null && live !== undefined && !Number.isNaN(Number(live))) {
        return Number(live).toLocaleString('en-US', { maximumFractionDigits: 6 });
    }

    if (item.last_price !== null && item.last_price !== undefined && !Number.isNaN(Number(item.last_price))) {
        return Number(item.last_price).toLocaleString('en-US', { maximumFractionDigits: 6 });
    }

    return '-';
};

const loadPrices = async () => {
    if (!props.twelvedataConfigured || symbols.value.length === 0) {
        prices.value = {};
        return;
    }

    pricesLoading.value = true;

    try {
        const response = await axios.get(route('settings.instruments.prices'), {
            params: {
                symbols: symbols.value.join(','),
            },
        });

        prices.value = response?.data?.prices || {};
    } catch (error) {
        prices.value = {};
    } finally {
        pricesLoading.value = false;
    }
};

onMounted(() => {
    loadPrices();
    timer = window.setInterval(loadPrices, 10000);
});

onBeforeUnmount(() => {
    if (timer) {
        window.clearInterval(timer);
    }
});
</script>

<template>
    <Head title="Instruments" />

    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Instruments</h1>

        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Tambah Instrument</h2>
            <div class="grid gap-3 md:grid-cols-4">
                <AppInput v-model="createForm.symbol" label="Symbol" :error="createForm.errors.symbol" />
                <AppInput v-model="createForm.name" label="Name" :error="createForm.errors.name" />
                <AppSelect v-model="createForm.category" label="Category" :options="[
                    { value: 'forex', label: 'Forex' },
                    { value: 'commodity', label: 'Commodity' },
                    { value: 'crypto', label: 'Crypto' },
                    { value: 'index', label: 'Index' },
                    { value: 'stock', label: 'Stock' }
                ]" :error="createForm.errors.category" />
                <AppInput v-model="createForm.pip_value" type="number" label="Pip Value" :error="createForm.errors.pip_value" />
            </div>
            <div class="mt-3">
                <AppButton :loading="createForm.processing" @click="save">Simpan Instrument</AppButton>
            </div>
        </div>

        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Sinkronisasi Instrument dari Twelve Data</h2>
            <p class="mb-3 text-xs text-gray-500">
                Cari simbol (contoh: <span class="font-medium">XAU</span>, <span class="font-medium">BTC</span>, <span class="font-medium">EUR</span>, <span class="font-medium">AAPL</span>) lalu tambahkan otomatis ke daftar instrument.
            </p>

            <div v-if="!twelvedataConfigured" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                TWELVEDATA_API_KEY belum dikonfigurasi di environment.
            </div>

            <div v-else class="grid gap-3 md:grid-cols-4">
                <AppInput v-model="syncForm.query" label="Keyword Symbol" :error="syncForm.errors.query" placeholder="Contoh: BTC" />
                <AppInput v-model="syncForm.limit" type="number" label="Limit" :error="syncForm.errors.limit" />
                <AppSelect
                    v-model="syncForm.category"
                    label="Filter Category"
                    :options="[
                        { value: '', label: 'Semua Category' },
                        { value: 'forex', label: 'Forex' },
                        { value: 'commodity', label: 'Commodity' },
                        { value: 'crypto', label: 'Crypto' },
                        { value: 'index', label: 'Index' },
                        { value: 'stock', label: 'Stock' }
                    ]"
                    :error="syncForm.errors.category"
                />
                <div class="flex items-end">
                    <AppButton :loading="syncForm.processing" :disabled="!syncForm.query" @click="syncInstruments">Sync dari API</AppButton>
                </div>
            </div>
        </div>

        <template v-if="instruments?.data?.length">
            <AppTable>
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Symbol</th>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Category</th>
                        <th class="px-3 py-2 text-left">Pip Value</th>
                        <th class="px-3 py-2 text-left">Harga Saat Ini</th>
                        <th class="px-3 py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in instruments.data" :key="item.id">
                        <td class="px-3 py-2">{{ item.symbol }}</td>
                        <td class="px-3 py-2">{{ item.name }}</td>
                        <td class="px-3 py-2">{{ item.category }}</td>
                        <td class="px-3 py-2">{{ item.pip_value }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900">{{ displayPrice(item) }}</div>
                            <p class="text-[11px] text-gray-500">
                                {{ pricesLoading ? 'Memuat...' : (prices?.[item.symbol]?.error || (prices?.[item.symbol]?.price !== undefined ? 'Live' : 'Cached')) }}
                            </p>
                        </td>
                        <td class="px-3 py-2">
                            <Link :href="route('settings.instruments.destroy', item.id)" method="delete" as="button" class="rounded border border-red-200 px-2 py-1 text-xs text-red-600">Delete</Link>
                        </td>
                    </tr>
                </tbody>
            </AppTable>
            <AppPagination :links="instruments.links" />
        </template>

        <AppEmptyState v-else>
            <template #title>Belum ada instrument</template>
            Tambahkan instrument trading terlebih dahulu.
        </AppEmptyState>
    </AppLayout>
</template>
