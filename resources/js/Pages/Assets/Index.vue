<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import type { MarketAsset } from '@/types/investment';
import { Head, Link, router } from '@inertiajs/vue3';
import { CandlestickChart, Plus, RefreshCw } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{ assets: MarketAsset[] }>();
const refreshing = ref<number | null>(null);
const refresh = (asset: MarketAsset) => router.post(route('assets.refresh', asset.id), {}, {
    onStart: () => refreshing.value = asset.id,
    onFinish: () => refreshing.value = null,
});
</script>

<template><Head title="Ativos" /><AuthenticatedLayout><section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Catalogo de mercado</p><h1 class="mt-2 text-3xl font-bold">Ativos</h1><p class="mt-2 text-sm text-stone-500">Instrumentos disponiveis para suas carteiras.</p></div><Link :href="route('assets.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white"><Plus :size="18" />Adicionar ativo</Link></section><div v-if="assets.length" class="mt-8 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"><article v-for="asset in assets" :key="asset.id" class="grid gap-3 border-b border-stone-100 px-5 py-4 last:border-0 dark:border-slate-800 sm:grid-cols-[1fr_120px_220px] sm:items-center"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/40"><CandlestickChart :size="19" /></span><div><div class="flex items-center gap-2"><p class="font-bold">{{ asset.symbol }}</p><span v-if="!asset.is_active" class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-600">Inativo</span></div><p class="text-xs text-stone-400">{{ asset.name }}</p></div></div><span class="w-fit rounded-full bg-stone-100 px-2.5 py-1 text-[10px] font-bold text-stone-500 dark:bg-slate-800">{{ asset.market }} · {{ asset.type }}</span><div class="flex items-center gap-3 sm:justify-end"><div class="sm:text-right"><p class="font-bold">{{ asset.price ? formatMoney(asset.price, asset.currency) : 'Sem cotacao' }}</p><p v-if="asset.price_date" class="text-xs text-stone-400">{{ formatDate(asset.price_date) }}</p></div><button v-if="asset.can_refresh" type="button" :disabled="refreshing === asset.id" class="rounded-xl border border-stone-200 p-2.5 text-stone-400 transition hover:border-brand-300 hover:text-brand-600 disabled:opacity-40 dark:border-slate-700" aria-label="Atualizar cotacao" @click="refresh(asset)"><RefreshCw :size="17" :class="refreshing === asset.id ? 'animate-spin' : ''" /></button></div></article></div><div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 py-16 text-center"><CandlestickChart :size="36" class="mx-auto text-stone-300" /><p class="mt-4 font-semibold">Nenhum ativo cadastrado</p></div></AuthenticatedLayout></template>
