<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, ShieldCheck, Trash2, XCircle } from '@lucide/vue';
import { computed } from 'vue';

type Summary = { total: number; invalid: number; matched: number; missing: number; extras: number };
type Reconciliation = {
    id: string;
    status: 'previewed' | 'reconciled' | 'divergent';
    account_id: number;
    account_name: string | null;
    currency: string;
    period_start: string;
    statement_date: string;
    declared_balance: string;
    detected_balance: string;
    delta: string;
    summary: Summary;
    confirmed_at: string | null;
};
type Row = { id: number; row_number: number; date: string | null; amount: string; description: string | null; status: 'matched' | 'missing' | 'invalid'; match_rule: 'exact' | 'window' | null; errors: string[] };
type Extra = { id: number; type: string; amount: string; date: string; description: string | null };

const props = defineProps<{ reconciliation: Reconciliation; rows: Row[]; extras: Extra[] }>();
const confirmation = useForm({});

const summaryCards = computed(() => [
    { label: 'Conciliadas', value: props.reconciliation.summary.matched, tone: 'text-emerald-600' },
    { label: 'Nao encontradas', value: props.reconciliation.summary.missing, tone: 'text-amber-600' },
    { label: 'Nao declaradas', value: props.reconciliation.summary.extras, tone: 'text-rose-600' },
    { label: 'Invalidas', value: props.reconciliation.summary.invalid, tone: 'text-stone-400' },
]);

const statusLabel = computed(() => ({
    previewed: 'Aguardando confirmacao',
    reconciled: 'Reconciliada',
    divergent: 'Divergente',
}[props.reconciliation.status] ?? props.reconciliation.status));
const statusClass = computed(() => ({
    previewed: 'bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300',
    reconciled: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    divergent: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
}[props.reconciliation.status] ?? 'bg-stone-100 text-stone-600 dark:bg-slate-800 dark:text-slate-300'));
const balanceDeltaTone = computed(() =>
    Number(props.reconciliation.delta) === 0 ? 'text-emerald-600' : Number(props.reconciliation.delta) > 0 ? 'text-amber-600' : 'text-rose-600',
);
const ruleLabel = (rule: 'exact' | 'window' | null) => {
    if (rule === null) return '—';
    return { exact: 'Data exata', window: 'Janela (3 dias)' }[rule];
};
const statusLabelFor = (row: Row) => ({ matched: 'Conciliada', missing: 'Nao encontrada', invalid: 'Invalida' }[row.status] ?? row.status);
const statusClassFor = (row: Row) => ({
    matched: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    missing: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    invalid: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
}[row.status] ?? 'bg-stone-100 text-stone-600 dark:bg-slate-800 dark:text-slate-300');
const typeLabel = (type: string) => ({ income: 'Receita', expense: 'Despesa', transfer_in: 'Transferencia (entrada)', transfer_out: 'Transferencia (saida)' }[type] ?? type);
const sign = (type: string) => (type === 'income' || type === 'transfer_in' ? '+' : '-');

const confirm = () => {
    if (props.reconciliation.status !== 'previewed') return;
    confirmation.post(route('reconciliations.confirm', props.reconciliation.id));
};
const discard = () => {
    if (window.confirm('Descartar esta previa?')) {
        router.delete(route('reconciliations.destroy', props.reconciliation.id));
    }
};
</script>

<template>
  <Head title="Conciliacao" />
  <AuthenticatedLayout>
    <Link :href="route('reconciliations.index')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-stone-500 hover:text-brand-600 dark:text-slate-400"><ArrowLeft :size="14" />Conciliacoes</Link>

    <section class="mt-3 overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl">
      <div class="flex flex-col gap-5 border-b border-slate-800 p-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-bold">{{ reconciliation.account_name ?? 'Conta removida' }}</h2><span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="statusClass">{{ statusLabel }}</span></div>
          <p class="mt-1 text-xs text-slate-400">{{ formatDate(reconciliation.period_start) }} ate {{ formatDate(reconciliation.statement_date) }} · {{ reconciliation.currency }}</p>
        </div>
        <div v-if="reconciliation.status === 'previewed'" class="flex flex-wrap gap-2">
          <button type="button" class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300" @click="discard"><Trash2 :size="15" class="mr-1 inline" />Descartar</button>
          <button :disabled="confirmation.processing" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-bold text-slate-950 disabled:opacity-40" @click="confirm"><ShieldCheck :size="17" />Confirmar conciliacao</button>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-px bg-slate-800 sm:grid-cols-4">
        <article class="min-w-0 bg-slate-950 p-4 sm:p-5"><p class="text-xs text-slate-500">Saldo declarado</p><p class="mt-1 truncate text-lg font-bold sm:text-2xl">{{ formatMoney(reconciliation.declared_balance, reconciliation.currency) }}</p></article>
        <article class="min-w-0 bg-slate-950 p-4 sm:p-5"><p class="text-xs text-slate-500">Saldo detectado</p><p class="mt-1 truncate text-lg font-bold sm:text-2xl">{{ formatMoney(reconciliation.detected_balance, reconciliation.currency) }}</p></article>
        <article class="min-w-0 bg-slate-950 p-4 sm:p-5"><p class="text-xs text-slate-500">Diferenca</p><p class="mt-1 truncate text-lg font-bold sm:text-2xl" :class="balanceDeltaTone">{{ formatMoney(reconciliation.delta, reconciliation.currency) }}</p></article>
        <article class="min-w-0 bg-slate-950 p-4 sm:p-5"><p class="text-xs text-slate-500">Linhas</p><p class="mt-1 text-lg font-bold sm:text-2xl">{{ reconciliation.summary.total }}</p></article>
      </div>
    </section>

    <div v-if="reconciliation.status === 'reconciled'" class="mt-4 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"><CheckCircle2 :size="18" class="shrink-0" /><p><strong>Extrato conciliado.</strong> Nenhuma linha divergente e a diferenca de saldo esta dentro da tolerancia de 1 centavo.</p></div>
    <div v-else-if="reconciliation.status === 'divergent'" class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"><XCircle :size="18" class="shrink-0" /><p><strong>Extrato divergente.</strong> Revise as linhas nao encontradas, nao declaradas ou invalidas e a diferenca de saldo.</p></div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article v-for="item in summaryCards" :key="item.label" class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs text-stone-400">{{ item.label }}</p><p class="mt-2 text-xl font-bold" :class="item.tone">{{ item.value }}</p></article>
    </section>

    <section class="mt-6 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <header class="flex items-center justify-between border-b border-stone-100 p-5 dark:border-slate-800"><div><h2 class="font-semibold">Linhas do extrato</h2><p class="text-xs text-stone-500">Exibindo ate 200 das {{ reconciliation.summary.total }} linhas.</p></div></header>
      <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-stone-50 text-xs text-stone-500 dark:bg-slate-950"><tr><th class="px-4 py-3">Linha</th><th class="px-4 py-3">Data</th><th class="px-4 py-3 text-right">Valor</th><th class="px-4 py-3">Descricao</th><th class="hidden px-4 py-3 md:table-cell">Regra</th><th class="px-4 py-3">Situacao</th></tr></thead><tbody class="divide-y divide-stone-100 dark:divide-slate-800"><tr v-for="row in rows" :key="row.id"><td class="px-4 py-3 font-mono text-xs text-stone-400">{{ row.row_number }}</td><td class="whitespace-nowrap px-4 py-3">{{ row.date ? formatDate(row.date) : '—' }}</td><td class="whitespace-nowrap px-4 py-3 text-right font-semibold">{{ formatMoney(row.amount, reconciliation.currency) }}</td><td class="max-w-48 truncate px-4 py-3 text-stone-500 sm:max-w-64">{{ row.description || '—' }}</td><td class="hidden whitespace-nowrap px-4 py-3 text-xs text-stone-400 md:table-cell">{{ ruleLabel(row.match_rule) }}</td><td class="px-4 py-3"><span class="whitespace-nowrap rounded-full px-2 py-1 text-[11px] font-semibold" :class="statusClassFor(row)">{{ statusLabelFor(row) }}</span><p v-if="row.errors.length" class="mt-2 text-xs text-rose-600">{{ row.errors.join(' ') }}</p></td></tr></tbody></table></div>
    </section>

    <section v-if="extras.length" class="mt-6 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <header class="border-b border-stone-100 p-5 dark:border-slate-800"><h2 class="font-semibold">Lancamentos nao declarados no extrato</h2><p class="text-xs text-stone-500">Movimentacoes do periodo sem nenhuma linha correspondente. Exibindo ate 100.</p></header>
      <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-stone-50 text-xs text-stone-500 dark:bg-slate-950"><tr><th class="px-4 py-3">Data</th><th class="px-4 py-3 text-right">Valor</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Descricao</th></tr></thead><tbody class="divide-y divide-stone-100 dark:divide-slate-800"><tr v-for="extra in extras" :key="extra.id"><td class="px-4 py-3">{{ formatDate(extra.date) }}</td><td class="px-4 py-3 text-right font-semibold" :class="extra.type === 'expense' || extra.type === 'transfer_out' ? 'text-rose-600' : 'text-emerald-600'">{{ sign(extra.type) }}{{ formatMoney(extra.amount, reconciliation.currency) }}</td><td class="px-4 py-3 text-xs">{{ typeLabel(extra.type) }}</td><td class="max-w-72 truncate px-4 py-3 text-stone-500">{{ extra.description || '—' }}</td></tr></tbody></table></div>
    </section>
  </AuthenticatedLayout>
</template>