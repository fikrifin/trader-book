<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCalendar from '@/Components/UI/AppCalendar.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppCurrencyDisplay from '@/Components/UI/AppCurrencyDisplay.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const todayLocalDate = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const props = defineProps({
    journals: Object,
    month: String,
    trade_summary: Array,
    accounts: Array,
    active_account_id: Number,
});

const filters = reactive({
    month: props.month,
    account_id: props.active_account_id || '',
});

const accountOptions = computed(() => (props.accounts || []).map((item) => ({
    value: item.id,
    label: `${item.name} (${item.account_type})`,
})));

const viewMode = ref('calendar');

const applyFilters = () => {
    router.get(route('journals.index'), filters, { preserveState: true, preserveScroll: true });
};

const form = useForm({
    trading_account_id: props.active_account_id || '',
    date: todayLocalDate(),
    mood_before: '',
    plan: '',
    review: '',
    followed_risk_rules: false,
});

const createJournal = () => {
    form.post(route('journals.store'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Journal" />

    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Daily Journal</h1>
                <p class="text-xs text-gray-500">Catat rencana, emosi, dan evaluasi trading harian.</p>
            </div>
            <div class="flex gap-2">
                <AppButton :variant="viewMode === 'calendar' ? 'primary' : 'secondary'" @click="viewMode = 'calendar'">Calendar</AppButton>
                <AppButton :variant="viewMode === 'list' ? 'primary' : 'secondary'" @click="viewMode = 'list'">List</AppButton>
            </div>
        </div>

        <AppCard class="mb-4" hoverable>
            <div class="grid gap-3 md:grid-cols-4">
                <AppInput v-model="filters.month" type="month" label="Month" />
                <AppSelect v-model="filters.account_id" label="Account" :options="accountOptions" />
                <div class="flex items-end">
                    <AppButton @click="applyFilters">Apply</AppButton>
                </div>
            </div>
        </AppCard>

        <AppCard class="mb-4" hoverable>
            <h2 class="mb-3 text-sm font-semibold">Create / Upsert Journal</h2>
            <div class="grid gap-3 md:grid-cols-3">
                <AppSelect v-model="form.trading_account_id" label="Account" :options="accountOptions" :error="form.errors.trading_account_id" />
                <AppInput v-model="form.date" type="date" label="Date" :error="form.errors.date" />
                <AppInput v-model="form.mood_before" type="number" label="Mood (1-5)" :error="form.errors.mood_before" />
            </div>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700">Plan</span>
                    <textarea v-model="form.plan" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none transition-colors duration-150 hover:border-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700">Review</span>
                    <textarea v-model="form.review" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none transition-colors duration-150 hover:border-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" />
                </label>
            </div>
            <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700">
                <input v-model="form.followed_risk_rules" type="checkbox" class="rounded border-gray-300" />
                Followed risk rules
            </label>
            <div class="mt-3">
                <AppButton :loading="form.processing" @click="createJournal">Save Journal</AppButton>
            </div>
        </AppCard>

        <AppCard class="mb-4" hoverable>
            <h2 class="mb-3 text-sm font-semibold">Trade Summary by Date ({{ month }})</h2>
            <AppTable v-if="trade_summary?.length && viewMode === 'list'">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Trades</th>
                        <th class="px-3 py-2 text-left">Total P/L</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in trade_summary" :key="item.date">
                        <td class="px-3 py-2">{{ item.date }}</td>
                        <td class="px-3 py-2">{{ item.trade_count }}</td>
                        <td class="px-3 py-2" :class="Number(item.total_pl) >= 0 ? 'text-green-600' : 'text-red-600'"><AppCurrencyDisplay :value="item.total_pl" show-plus /></td>
                    </tr>
                </tbody>
            </AppTable>
            <AppCalendar
                v-else-if="viewMode === 'calendar'"
                :month="month"
                :trade-summary="trade_summary"
                :journals="journals?.data || []"
            />
            <AppEmptyState v-else>
                <template #title>Belum ada trade di bulan ini</template>
                Summary akan tampil setelah ada trade.
            </AppEmptyState>
        </AppCard>

        <template v-if="journals?.data?.length && viewMode === 'list'">
            <AppCard class="mb-4" hoverable>
                <AppTable>
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Account</th>
                            <th class="px-3 py-2 text-left">Mood</th>
                            <th class="px-3 py-2 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in journals.data" :key="item.id" class="transition-colors hover:bg-gray-50/80">
                            <td class="px-3 py-2">{{ item.date }}</td>
                            <td class="px-3 py-2">{{ item.trading_account?.name || item.trading_account_id }}</td>
                            <td class="px-3 py-2">{{ item.mood_before || '-' }}</td>
                            <td class="px-3 py-2">
                                <Link :href="route('journals.show', item.id)" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 transition hover:bg-gray-50">Detail</Link>
                            </td>
                        </tr>
                    </tbody>
                </AppTable>
            </AppCard>
            <AppPagination :links="journals.links" />
        </template>

        <AppEmptyState v-else>
            <template #title>Belum ada journal</template>
            Buat journal harian pertama Anda.
        </AppEmptyState>
    </AppLayout>
</template>
