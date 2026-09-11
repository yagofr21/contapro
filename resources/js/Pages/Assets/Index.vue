<script setup lang="ts">
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import type { MarketAsset, PortfolioOption } from '@/types/investment';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CandlestickChart, Plus, RefreshCw, Search, WalletCards } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{ assets: MarketAsset[]; portfolios: PortfolioOption[] }>();
const refreshing = ref<number | null>(null);
const toggling = ref<number | null>(null);
const query = ref('');
const filteredAssets = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (!term) {
        return props.assets;
    }

    return props.assets.filter((asset) => [
        asset.symbol, asset.name, asset.market, asset.type,
    ].some((field) => field.toLowerCase().includes(term)));
});
const selectedPortfolios = ref<Record<number, string>>(Object.fromEntries(
    props.assets.map((asset) => [
        asset.id,
        String(props.portfolios.find((portfolio) => portfolio.currency === asset.currency)?.id ?? ''),
    ]),
));

const compatiblePortfolios = (asset: MarketAsset) => props.portfolios.filter(
    (portfolio) => portfolio.currency === asset.currency,
);
const refresh = (asset: MarketAsset) => router.post(route('assets.refresh', asset.id), {}, {
    onStart: () => refreshing.value = asset.id,
    onFinish: () => refreshing.value = null,
});
const toggleAutoUpdate = (asset: MarketAsset) => {
    const previous = asset.auto_update;
    asset.auto_update = !previous;
    toggling.value = asset.id;
    router.post(route('assets.auto-update', asset.id), { auto_update: !previous }, {
        onError: () => {
            asset.auto_update = previous;
            toggling.value = null;
        },
        onFinish: () => toggling.value = null,
    });
};
</script>

<template>
  <Head title="Ativos" />
  <AuthenticatedLayout>
    <PageHeader kicker="Catalogo de mercado" title="Ativos" subtitle="Escolha uma carteira e registre sua compra com quantidade, preco e taxas.">
      <template #actions>
        <Link :href="route('assets.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white"><Plus :size="18" />Cadastrar no catalogo</Link>
      </template>
    </PageHeader>

    <div class="mt-6 flex gap-3 rounded-2xl border border-brand-100 bg-brand-50/60 p-4 text-sm text-brand-900 dark:border-brand-900 dark:bg-brand-950/30 dark:text-brand-100">
      <WalletCards :size="20" class="mt-0.5 shrink-0" />
      <p><strong>Catalogo nao e posicao.</strong> A quantidade nasce ao registrar uma compra em uma carteira. Use o atalho de cada ativo abaixo.</p>
    </div>

    <label class="mt-6 flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 shadow-sm focus-within:border-brand-400 dark:border-slate-800 dark:bg-slate-900">
      <Search :size="18" class="shrink-0 text-stone-400" />
      <input v-model="query" type="search" placeholder="Buscar por simbolo, nome, mercado ou tipo..." class="w-full bg-transparent text-sm outline-none placeholder:text-stone-400 dark:placeholder:text-slate-500">
    </label>

    <Card v-if="assets.length && filteredAssets.length" class="mt-6">
      <article v-for="asset in filteredAssets" :key="asset.id" class="grid gap-5 border-b border-stone-100 px-5 py-5 last:border-0 dark:border-slate-800 lg:grid-cols-[1fr_auto] lg:items-center">
        <div class="flex items-center gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/40"><CandlestickChart :size="19" /></span>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <p class="font-bold">{{ asset.symbol }}</p>
              <StatusBadge tone="slate" :label="`${asset.market} · ${asset.type}`" />
              <StatusBadge v-if="!asset.is_active" tone="rose" label="Inativo" />
            </div>
            <p class="mt-1 text-xs text-stone-400">{{ asset.name }}</p>
          </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
          <div class="sm:min-w-32 sm:text-right">
            <p class="font-bold">{{ asset.price ? formatMoney(asset.price, asset.currency) : 'Sem cotacao' }}</p>
            <p v-if="asset.price_date" class="text-xs text-stone-400">{{ formatDate(asset.price_date) }}</p>
          </div>
          <div v-if="asset.can_refresh" class="flex items-center gap-2" title="Se ativado, a cotacao desse ativo e atualizada automaticamente todos os dias">
            <ToggleSwitch :checked="asset.auto_update" :disabled="toggling === asset.id" @update:checked="toggleAutoUpdate(asset)" />
            <span class="text-[11px] font-medium text-stone-400">Atualizar automaticamente</span>
          </div>
          <button v-if="asset.can_refresh" type="button" :disabled="refreshing === asset.id" title="Atualizar cotacao" class="rounded-xl border border-stone-200 p-2.5 text-stone-400 transition hover:border-brand-300 hover:text-brand-600 disabled:opacity-50 dark:border-slate-700" @click="refresh(asset)"><RefreshCw :size="17" :class="{ 'animate-spin': refreshing === asset.id }" /></button>
          <template v-if="asset.is_active && compatiblePortfolios(asset).length">
            <select v-model="selectedPortfolios[asset.id]" aria-label="Carteira para a operacao" class="rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
              <option v-for="portfolio in compatiblePortfolios(asset)" :key="portfolio.id" :value="String(portfolio.id)">{{ portfolio.name }}</option>
            </select>
            <Link :href="route('investment-transactions.create', { portfolio: selectedPortfolios[asset.id], asset: asset.id })" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white">Registrar compra <ArrowRight :size="16" /></Link>
          </template>
          <Link v-else-if="asset.is_active" :href="route('portfolios.create')" class="inline-flex items-center justify-center gap-2 rounded-xl border border-brand-200 px-4 py-2.5 text-sm font-semibold text-brand-700 dark:border-brand-800 dark:text-brand-300">Criar carteira {{ asset.currency }}</Link>
        </div>
      </article>
    </Card>
    <EmptyState v-else-if="assets.length" class="mt-6" :icon="Search" title="Nenhum ativo encontrado" description="Ajuste o termo de busca ou cadastre o ativo no catalogo." />
    <EmptyState v-else class="mt-8" :icon="CandlestickChart" title="Nenhum ativo no catalogo" description="Cadastre o primeiro ativo para depois registrar uma compra." />
  </AuthenticatedLayout>
</template>
