<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { formatDate, formatMoney } from '@/lib/format';
import { projectSpending, type SpendingBaseline } from '@/lib/projections';
import { TrendingUp, SlidersHorizontal } from '@lucide/vue';
const props = defineProps<{ baseline?: SpendingBaseline; balance: string; currency: string }>();
const income = ref<number | string>('');
const expenses = ref<number | string>('');
const reduction = ref(0);
const months = ref(6);
watch(() => props.baseline, (value) => {
    income.value = value?.monthly_income === null || !value ? '' : Number(value.monthly_income);
    expenses.value = value?.monthly_expenses === null || !value ? '' : Number(value.monthly_expenses);
    reduction.value = 0;
}, { immediate: true });
const ready = computed(() => income.value !== '' && expenses.value !== '' && Number.isFinite(Number(income.value)) && Number.isFinite(Number(expenses.value)) && Number(income.value) >= 0 && Number(expenses.value) >= 0);
const scenario = computed(() => projectSpending(Number(props.balance), Number(income.value), Number(expenses.value), reduction.value, months.value));
const points = computed(() => Array.from({ length: 7 }, (_, index) => projectSpending(Number(props.balance), Number(income.value), Number(expenses.value), reduction.value, months.value * index / 6)));
const bounds = computed(() => {
    const values = points.value.flatMap((point) => [point.balance, point.baselineBalance]);
    return { min: Math.min(...values), range: Math.max(1, Math.max(...values) - Math.min(...values)) };
});
const line = (scenarioLine: boolean) => points.value.map((point, index) => `${20 + index * 60},${140 - ((scenarioLine ? point.balance : point.baselineBalance) - bounds.value.min) / bounds.value.range * 110}`).join(' ');
</script>
<template>
  <section class="mt-6 scroll-mt-20 overflow-hidden rounded-2xl border border-brand-200 bg-white dark:border-brand-900 dark:bg-slate-900" aria-labelledby="projection-title">
    <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="min-w-0">
        <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-700 dark:text-brand-300"><TrendingUp :size="16" />Olhe para a frente</p>
        <h2 id="projection-title" class="mt-2 text-xl font-bold">Se continuar nesse ritmo…</h2>
        <p class="mt-2 text-sm text-stone-600 dark:text-slate-400">Simule sua evolução sem alterar seus lançamentos.</p>
        <template v-if="ready">
          <p class="mt-5 text-xs text-stone-600 dark:text-slate-400">Saldo estimado em {{ months }} meses · {{ currency }}</p>
          <p class="financial-value mt-1 text-3xl font-bold" :class="scenario.balance < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-brand-700 dark:text-brand-300'">{{ formatMoney(String(scenario.balance), currency) }}</p>
          <p class="mt-2 text-sm text-stone-600 dark:text-slate-400">{{ scenario.monthlySavings >= 0 ? 'Sobra mensal' : 'Déficit mensal' }}: <strong>{{ formatMoney(String(scenario.monthlySavings), currency) }}</strong></p>
          <svg class="mt-4 h-36 w-full" viewBox="0 0 400 160" role="img" :aria-label="`Comparação: ritmo atual ${formatMoney(String(scenario.baselineBalance), currency)}; cenário ${formatMoney(String(scenario.balance), currency)}`">
            <line x1="20" y1="145" x2="380" y2="145" stroke="currentColor" class="text-stone-200 dark:text-slate-700" />
            <polyline :points="line(false)" fill="none" stroke="#94a3b8" stroke-width="3" stroke-dasharray="6 5" />
            <polyline :points="line(true)" fill="none" stroke="currentColor" stroke-width="3" class="text-brand-600 dark:text-brand-400" />
          </svg>
          <div class="flex flex-wrap gap-4 text-xs text-stone-600 dark:text-slate-400"><span>··· Ritmo atual: {{ formatMoney(String(scenario.baselineBalance), currency) }}</span><span class="text-brand-700 dark:text-brand-300">— Cenário simulado</span></div>
        </template>
        <p v-else class="mt-5 rounded-xl bg-stone-50 p-4 text-sm text-stone-600 dark:bg-slate-950 dark:text-slate-400">Ainda não há meses completos para estimar seu ritmo. Informe receitas e despesas mensais ao lado para simular.</p>
      </div>
      <div class="rounded-xl bg-stone-50 p-4 dark:bg-slate-950">
        <h3 class="flex items-center gap-2 text-sm font-bold"><SlidersHorizontal :size="16" />Ajuste o cenário</h3>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
          <label class="text-xs font-semibold">Receita mensal ({{ currency }})<input v-model="income" type="number" min="0" step="0.01" class="mt-2 block min-h-11 w-full rounded-lg border-stone-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" /></label>
          <label class="text-xs font-semibold">Despesa mensal ({{ currency }})<input v-model="expenses" type="number" min="0" step="0.01" class="mt-2 block min-h-11 w-full rounded-lg border-stone-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" /></label>
        </div>
        <label class="mt-5 block text-xs font-semibold">Reduzir gastos em {{ formatMoney(String(Math.min(reduction, Number(expenses))), currency) }} por mês<input v-model.number="reduction" :disabled="!ready" type="range" min="0" :max="Math.max(0, Number(expenses))" step="10" class="mt-3 min-h-8 w-full accent-brand-600" /></label>
        <label class="mt-4 block text-xs font-semibold">Horizonte<select v-model.number="months" class="mt-2 block min-h-11 w-full rounded-lg border-stone-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"><option :value="3">3 meses</option><option :value="6">6 meses</option><option :value="12">12 meses</option><option :value="24">24 meses</option></select></label>
        <p class="mt-4 text-xs leading-relaxed text-stone-600 dark:text-slate-400">{{ baseline?.months_count ? `Base: ${baseline.months_count} meses completos, de ${formatDate(baseline.period_start!)} a ${formatDate(baseline.period_end)}.` : 'Base: valores informados por você.' }} Valores editáveis; sem rendimentos, inflação ou inclusão adicional dos agendamentos. Compras no cartão entram como despesa; pagamentos de fatura não entram novamente.</p>
      </div>
    </div>
  </section>
</template>
