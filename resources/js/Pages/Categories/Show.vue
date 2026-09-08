<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatMoney } from '@/lib/format';
import type { Category } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/vue3';
import { Calendar, ChevronLeft, ChevronRight, Flame } from '@lucide/vue';
import { computed } from 'vue';

type CalendarDay = { date: string; day: number; totals: Record<string, string> };
type Summary = { total: string; avg: string; days: number; top: { date: string; amount: string }; low: { date: string; amount: string } };

const props = defineProps<{
    category: Category;
    month: string;
    monthLabel: string;
    calendar: CalendarDay[];
    summaries: Record<string, Summary>;
    heatCurrency: string | null;
    prevMonth: string;
    nextMonth: string;
}>();

const firstDayOffset = computed(() => new Date(`${props.month}-01T00:00:00`).getDay());
const heatSummary = computed<Summary | null>(() => (props.heatCurrency ? (props.summaries[props.heatCurrency] ?? null) : null));
const heatAvg = computed(() => Number(heatSummary.value?.avg ?? 0));
const allCurrencies = computed(() => Object.keys(props.summaries));
const hasMultipleCurrencies = computed(() => allCurrencies.value.length > 1);

const navigate = (target: string) => {
    router.get(route('categories.show', props.category.id), { month: target }, { preserveState: false, replace: true });
};

const totalFor = (cell: CalendarDay | null, currency: string | null): string | undefined => {
    if (!cell || !currency) return undefined;
    return cell.totals[currency];
};

const heatClass = (amount?: string | null, isExpense?: boolean) => {
    if (!amount || heatAvg.value === 0) return '';
    const value = Number(amount);
    if (value === 0) return '';
    const ratio = value / heatAvg.value;
    if (isExpense) {
        if (ratio >= 1.6) return 'bg-rose-500/90 text-white';
        if (ratio >= 1.2) return 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-200';
        return 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200';
    }
    if (ratio >= 1.6) return 'bg-emerald-500/90 text-white';
    if (ratio >= 1.2) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200';
    return 'bg-stone-100 text-stone-700 dark:bg-slate-800 dark:text-slate-300';
};

const padCells = computed(() => [...Array(firstDayOffset.value).fill(null), ...props.calendar]);
</script>

<template>
  <Head :title="`${category.name} · ${monthLabel}`" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="flex items-center gap-3">
          <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: category.color ?? '#94a3b8' }" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em]" :class="category.type === 'expense' ? 'text-rose-600' : 'text-emerald-600'">{{ category.type === 'expense' ? 'Despesa' : 'Receita' }}</p>
            <h1 class="text-2xl font-bold tracking-tight">{{ category.name }}</h1>
            <p v-if="category.parent_name" class="text-xs text-stone-400">Em {{ category.parent_name }}</p>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="inline-flex items-center justify-center rounded-xl border border-stone-200 p-2 text-stone-500 transition hover:bg-stone-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Mes anterior" @click="navigate(prevMonth)"><ChevronLeft :size="18" /></button>
        <span class="min-w-[120px] text-center text-sm font-semibold">{{ monthLabel }}</span>
        <button class="inline-flex items-center justify-center rounded-xl border border-stone-200 p-2 text-stone-500 transition hover:bg-stone-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Proximo mes" @click="navigate(nextMonth)"><ChevronRight :size="18" /></button>
      </div>
    </section>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <template v-for="currency in allCurrencies" :key="currency">
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400">Total em {{ currency }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight" :class="category.type === 'expense' ? 'text-rose-600' : 'text-emerald-600'">{{ formatMoney(summaries[currency].total, currency) }}</p>
          <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-stone-500 dark:text-slate-400">
            <div><p class="text-[10px] uppercase tracking-wider text-stone-400">Media/dia</p><p class="mt-0.5 font-semibold">{{ formatMoney(summaries[currency].avg, currency) }}</p></div>
            <div><p class="text-[10px] uppercase tracking-wider text-stone-400">Melhor dia</p><p class="mt-0.5 font-semibold">{{ summaries[currency].top.date.slice(8) }}/{{ summaries[currency].top.date.slice(5, 7) }}</p></div>
            <div><p class="text-[10px] uppercase tracking-wider text-stone-400">Alerta</p><p class="mt-0.5 font-semibold text-rose-600">{{ summaries[currency].low.date.slice(8) }}/{{ summaries[currency].low.date.slice(5, 7) }}</p></div>
            <div><p class="text-[10px] uppercase tracking-wider text-stone-400">Dias com movimento</p><p class="mt-0.5 font-semibold">{{ summaries[currency].days }}</p></div>
          </div>
        </div>
      </template>
    </div>

    <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm font-semibold"><Calendar :size="16" class="text-stone-400" />Calendario</div>
        <p v-if="heatCurrency && heatSummary" class="flex items-center gap-1.5 text-xs text-stone-400"><Flame :size="14" class="text-amber-500" />Escala baseada em {{ heatCurrency }} (media {{ formatMoney(heatSummary.avg, heatCurrency) }})</p>
      </div>

      <div class="grid grid-cols-7 gap-1">
        <span v-for="day in ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab']" :key="day" class="text-center text-[10px] font-bold uppercase tracking-wider text-stone-400">{{ day }}</span>

        <div
          v-for="(cell, index) in padCells"
          :key="index"
          class="flex h-[4.5rem] flex-col justify-between rounded-xl border border-stone-100 p-1.5 text-xs dark:border-slate-800"
          :class="cell && heatCurrency ? heatClass(totalFor(cell, heatCurrency), category.type === 'expense') : cell ? 'bg-stone-50 dark:bg-slate-800/50' : ''"
        >
          <template v-if="cell">
            <span class="text-[11px] font-semibold" :class="heatClass(totalFor(cell, heatCurrency), category.type === 'expense')?.includes('text-white') ? 'text-white' : 'text-stone-500 dark:text-slate-400'">{{ cell.day }}</span>
            <span v-if="totalFor(cell, heatCurrency)" class="truncate text-[11px] font-semibold leading-tight" :class="heatClass(totalFor(cell, heatCurrency), category.type === 'expense')?.includes('text-white') ? 'text-white' : 'text-stone-700 dark:text-slate-200'">{{ formatMoney(totalFor(cell, heatCurrency) ?? '', heatCurrency ?? '') }}</span>
          </template>
        </div>
      </div>

      <div v-if="hasMultipleCurrencies && heatCurrency" class="mt-4 rounded-xl border border-stone-100 p-4 dark:border-slate-800">
        <p class="mb-2 text-xs font-semibold text-stone-500 dark:text-slate-400">Valores adicionais em {{ allCurrencies.filter((c) => c !== heatCurrency).join(', ') }}</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
          <div v-for="currency in allCurrencies.filter((c) => c !== heatCurrency)" :key="currency" class="rounded-lg bg-stone-50 p-2.5 dark:bg-slate-800/60">
            <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400">{{ currency }}</p>
            <p class="mt-0.5 text-sm font-bold">{{ formatMoney(summaries[currency].total, currency) }}</p>
          </div>
        </div>
      </div>
    </div>

    <nav class="mt-6">
      <Link :href="route('categories.index')" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Voltar para categorias</Link>
    </nav>
  </AuthenticatedLayout>
</template>