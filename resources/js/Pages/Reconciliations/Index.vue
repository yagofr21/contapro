<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatMoney } from '@/lib/format';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Download, FileSpreadsheet, History, Scale } from '@lucide/vue';

type Account = { id: number; name: string; currency: string };
type Summary = { total: number; invalid: number; matched: number; missing: number; extras: number };
type HistoryItem = {
    id: string;
    status: 'previewed' | 'reconciled' | 'divergent';
    account_id: number;
    account_name: string | null;
    currency: string;
    period_start: string;
    statement_date: string;
    summary: Summary;
    delta: string;
    created_at: string;
};

defineProps<{ accounts: Account[]; history: HistoryItem[] }>();
const form = useForm<{ account_id: string; period_start: string; statement_date: string; declared_balance: string; file: File | null }>({
    account_id: '',
    period_start: '',
    statement_date: '',
    declared_balance: '',
    file: null,
});

const submit = () => form.post(route('reconciliations.store'), { forceFormData: true });
const statusLabel = (status: string) => ({ previewed: 'Aguardando', reconciled: 'Reconciliada', divergent: 'Divergente' }[status] ?? status);
</script>

<template>
  <Head title="Conciliacoes" />
  <AuthenticatedLayout>
    <PageHeader kicker="Conferencia de extrato" title="Conciliacoes" subtitle="Compare o saldo declarado pelo banco com o saldo detectado nos seus lancamentos, linha a linha.">
      <template #actions>
        <a :href="route('reconciliations.template')" class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"><Download :size="16" />Modelo de extrato</a>
      </template>
    </PageHeader>

    <section class="mt-7 grid gap-4 lg:grid-cols-3">
      <article v-for="(step, index) in [{ title: 'Enviar', text: 'Extrato CSV com Data, Descricao e Valor' }, { title: 'Conferir', text: 'Regras de casamento com seus lancamentos' }, { title: 'Confirmar', text: 'Situacao Reconciliada ou Divergente' }]" :key="step.title" class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full bg-slate-950 text-xs font-bold text-white">{{ index + 1 }}</span><div><h2 class="text-sm font-semibold">{{ step.title }}</h2><p class="text-xs text-stone-500">{{ step.text }}</p></div></div>
      </article>
    </section>

    <section class="mt-6 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
      <div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300"><Scale :size="22" /></span><div><h2 class="font-semibold">Nova conciliacao</h2><p class="text-xs text-stone-500">Valores negativos indicam saidas; positivos, entradas. Saldo final = declarado no extrato.</p></div></div>
      <form class="mt-5 grid gap-4 lg:grid-cols-[1fr_1fr_1fr_1fr_1.6fr_auto] lg:items-end" @submit.prevent="submit">
        <label><span class="mb-2 block text-xs font-semibold text-stone-500">Conta</span><SelectInput v-model="form.account_id" class="w-full"><option value="">Selecione</option><option v-for="account in accounts" :key="account.id" :value="String(account.id)">{{ account.name }} ({{ account.currency }})</option></SelectInput><InputError :message="form.errors.account_id" /></label>
        <label><span class="mb-2 block text-xs font-semibold text-stone-500">Inicio do periodo</span><input v-model="form.period_start" type="date" class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError :message="form.errors.period_start" /></label>
        <label><span class="mb-2 block text-xs font-semibold text-stone-500">Data do extrato</span><input v-model="form.statement_date" type="date" class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError :message="form.errors.statement_date" /></label>
        <label><span class="mb-2 block text-xs font-semibold text-stone-500">Saldo declarado</span><input v-model="form.declared_balance" type="number" step="0.01" lang="pt-BR" placeholder="0,00" class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError :message="form.errors.declared_balance" /></label>
        <label><span class="mb-2 block text-xs font-semibold text-stone-500">Arquivo CSV</span><input type="file" accept=".csv,.txt,text/csv" class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white dark:border-slate-700 dark:bg-slate-950" @input="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null" /><InputError :message="form.errors.file" /></label>
        <button :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 disabled:opacity-50"><FileSpreadsheet :size="17" />Gerar previa</button>
      </form>
    </section>

    <section v-if="history.length" class="mt-8">
      <div class="flex items-center gap-2"><History :size="18" class="text-stone-400" /><h2 class="font-semibold">Historico recente</h2></div>
      <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3"><Link v-for="item in history" :key="item.id" :href="route('reconciliations.show', item.id)" class="rounded-2xl border border-stone-200 bg-white p-4 transition hover:border-brand-300 dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-semibold">{{ item.account_name ?? 'Conta removida' }}</p><p class="mt-1 text-xs text-stone-500">{{ new Intl.DateTimeFormat('pt-BR').format(new Date(item.created_at)) }}</p></div><StatusBadge :tone="item.status === 'reconciled' ? 'emerald' : item.status === 'divergent' ? 'amber' : 'brand'" :label="statusLabel(item.status)" /></div><p class="mt-3 text-xs text-stone-400">{{ item.summary.total }} linhas · diferenca {{ formatMoney(item.delta, item.currency) }}</p></Link></div>
    </section>

    <div class="mt-6 flex gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-4 text-xs text-stone-500 dark:border-slate-800 dark:bg-slate-900"><AlertTriangle :size="17" class="shrink-0 text-amber-500" /><p>As regras casam valor absoluto e data (exata ou em ate 3 dias) preservando o sentido do lancamento. Conciliacao nao altera seus dados.</p></div>
  </AuthenticatedLayout>
</template>