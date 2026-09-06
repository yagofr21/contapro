<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Check, Download, FileSpreadsheet, History, ShieldCheck, UploadCloud } from '@lucide/vue';
import { computed } from 'vue';

type Summary = { total: number; valid: number; invalid: number; duplicate: number; imported: number; delimiter: string };
type Batch = { id: string; kind: 'financial' | 'investment'; status: 'previewed' | 'confirmed' | 'failed'; filename: string; portfolio_name: string | null; summary: Summary; confirmed_at: string | null };
type Row = { id: number; row_number: number; raw: Record<string, string>; normalized: Record<string, unknown> | null; status: string; errors: string[] };
type Portfolio = { id: number; name: string; currency: string };
type HistoryItem = Omit<Batch, 'confirmed_at'> & { created_at: string };

const props = defineProps<{ batch: Batch | null; rows: Row[]; portfolios: Portfolio[]; history: HistoryItem[] }>();
const upload = useForm<{ kind: 'financial' | 'investment'; portfolio_id: string; file: File | null }>({ kind: 'financial', portfolio_id: '', file: null });
const confirmation = useForm({ skip_invalid: false });
const headers = computed(() => props.rows.length ? Object.keys(props.rows[0].raw) : []);
const canConfirm = computed(() => props.batch?.status === 'previewed' && props.batch.summary.valid > 0);
const kindLabel = (kind: string) => kind === 'financial' ? 'Financeiro' : 'Investimentos';
const statusLabel = (status: string) => ({ previewed: 'Aguardando confirmacao', confirmed: 'Confirmada', valid: 'Valida', invalid: 'Invalida', duplicate: 'Duplicada', imported: 'Importada' }[status] ?? status);
const statusClass = (status: string) => ({
    confirmed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    valid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    imported: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    invalid: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    duplicate: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    previewed: 'bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300',
}[status] ?? 'bg-stone-100 text-stone-600 dark:bg-slate-800 dark:text-slate-300');

const submitUpload = () => upload.post(route('imports.store'), { forceFormData: true });
const confirmImport = () => {
    if (!props.batch) return;
    confirmation.post(route('imports.confirm', props.batch.id));
};
const discard = () => {
    if (props.batch && window.confirm('Descartar esta previa?')) router.delete(route('imports.destroy', props.batch.id));
};
</script>

<template>
  <Head title="Importacoes" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Entrada assistida</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Importacoes CSV</h1>
        <p class="mt-2 max-w-2xl text-sm text-stone-500">Confira cada linha antes de alterar seus dados. Arquivos originais nao ficam armazenados.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a :href="route('imports.template', 'financial')" class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"><Download :size="16" />Modelo financeiro</a>
        <a :href="route('imports.template', 'investment')" class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"><Download :size="16" />Modelo investimentos</a>
      </div>
    </section>

    <section class="mt-7 grid gap-4 lg:grid-cols-3">
      <article v-for="(step, index) in [{ title: 'Enviar', text: 'CSV de ate 2 MB e 1.000 linhas' }, { title: 'Conferir', text: 'Validacao e duplicidades por linha' }, { title: 'Confirmar', text: 'Gravacao atomica e auditavel' }]" :key="step.title" class="rounded-2xl border p-4" :class="(batch ? index <= 1 : index === 0) ? 'border-brand-200 bg-brand-50 dark:border-brand-900 dark:bg-brand-950/30' : 'border-stone-200 bg-white dark:border-slate-800 dark:bg-slate-900'">
        <div class="flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full bg-slate-950 text-xs font-bold text-white">{{ index + 1 }}</span><div><h2 class="text-sm font-semibold">{{ step.title }}</h2><p class="text-xs text-stone-500">{{ step.text }}</p></div></div>
      </article>
    </section>

    <section class="mt-6 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
      <div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300"><UploadCloud :size="22" /></span><div><h2 class="font-semibold">Nova importacao</h2><p class="text-xs text-stone-500">Separadores ponto e virgula, virgula ou tabulacao; UTF-8 ou Windows-1252.</p></div></div>
      <form class="mt-5 grid gap-4 lg:grid-cols-[200px_1fr_1.4fr_auto] lg:items-end" @submit.prevent="submitUpload">
        <label><span class="mb-2 block text-xs font-semibold text-stone-500">Conteudo</span><SelectInput v-model="upload.kind" class="w-full"><option value="financial">Financeiro</option><option value="investment">Investimentos</option></SelectInput><InputError :message="upload.errors.kind" /></label>
        <label v-if="upload.kind === 'investment'"><span class="mb-2 block text-xs font-semibold text-stone-500">Carteira</span><SelectInput v-model="upload.portfolio_id" class="w-full"><option value="">Selecione</option><option v-for="portfolio in portfolios" :key="portfolio.id" :value="String(portfolio.id)">{{ portfolio.name }} ({{ portfolio.currency }})</option></SelectInput><InputError :message="upload.errors.portfolio_id" /></label>
        <div v-else class="hidden lg:block" />
        <label><span class="mb-2 block text-xs font-semibold text-stone-500">Arquivo CSV</span><input type="file" accept=".csv,.txt,text/csv" class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white dark:border-slate-700 dark:bg-slate-950" @input="upload.file = ($event.target as HTMLInputElement).files?.[0] ?? null" /><InputError :message="upload.errors.file" /></label>
        <button :disabled="upload.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 disabled:opacity-50"><FileSpreadsheet :size="17" />Gerar previa</button>
      </form>
    </section>

    <template v-if="batch">
      <section class="mt-6 overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl">
        <div class="flex flex-col gap-5 border-b border-slate-800 p-6 lg:flex-row lg:items-center lg:justify-between">
          <div><div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-bold">{{ batch.filename }}</h2><span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="statusClass(batch.status)">{{ statusLabel(batch.status) }}</span></div><p class="mt-1 text-xs text-slate-400">{{ kindLabel(batch.kind) }}<template v-if="batch.portfolio_name"> · {{ batch.portfolio_name }}</template></p></div>
          <div v-if="batch.status === 'previewed'" class="flex flex-wrap gap-2"><button type="button" class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300" @click="discard">Descartar</button><button :disabled="!canConfirm || confirmation.processing || (batch.summary.invalid > 0 && !confirmation.skip_invalid)" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-bold text-slate-950 disabled:opacity-40" @click="confirmImport"><ShieldCheck :size="17" />Confirmar importacao</button></div>
        </div>
        <div class="grid grid-cols-2 gap-px bg-slate-800 sm:grid-cols-4"><div v-for="item in [{ label: 'Validas', value: batch.summary.valid, tone: 'text-emerald-400' }, { label: 'Invalidas', value: batch.summary.invalid, tone: 'text-rose-400' }, { label: 'Duplicadas', value: batch.summary.duplicate, tone: 'text-amber-400' }, { label: 'Importadas', value: batch.summary.imported, tone: 'text-brand-400' }]" :key="item.label" class="bg-slate-950 p-5"><p class="text-xs text-slate-500">{{ item.label }}</p><p class="mt-1 text-2xl font-bold" :class="item.tone">{{ item.value }}</p></div></div>
      </section>

      <div v-if="batch.status === 'previewed' && batch.summary.invalid > 0" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"><label class="flex cursor-pointer items-start gap-3"><input v-model="confirmation.skip_invalid" type="checkbox" class="mt-0.5 rounded border-amber-300 text-brand-600" /><span><strong>Ignorar {{ batch.summary.invalid }} linha(s) invalida(s)</strong><span class="mt-0.5 block text-xs opacity-80">Somente linhas validas serao gravadas. Duplicidades sempre sao ignoradas.</span></span></label><InputError :message="confirmation.errors.skip_invalid" /></div>

      <section class="mt-4 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="flex items-center justify-between border-b border-stone-100 p-5 dark:border-slate-800"><div><h2 class="font-semibold">Conferencia por linha</h2><p class="text-xs text-stone-500">Exibindo ate 200 das {{ batch.summary.total }} linhas.</p></div><Check v-if="batch.status === 'confirmed'" class="text-emerald-500" /></header>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-stone-50 text-xs text-stone-500 dark:bg-slate-950"><tr><th class="px-4 py-3">Linha</th><th v-for="header in headers" :key="header" class="whitespace-nowrap px-4 py-3">{{ header }}</th><th class="px-4 py-3">Situacao</th></tr></thead><tbody class="divide-y divide-stone-100 dark:divide-slate-800"><tr v-for="row in rows" :key="row.id"><td class="px-4 py-3 font-mono text-xs text-stone-400">{{ row.row_number }}</td><td v-for="header in headers" :key="header" class="max-w-56 truncate px-4 py-3">{{ row.raw[header] || '—' }}</td><td class="min-w-64 px-4 py-3"><span class="rounded-full px-2 py-1 text-[11px] font-semibold" :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span><p v-if="row.errors.length" class="mt-2 text-xs text-rose-600">{{ row.errors.join(' ') }}</p></td></tr></tbody></table></div>
      </section>
    </template>

    <section v-if="history.length" class="mt-8">
      <div class="flex items-center gap-2"><History :size="18" class="text-stone-400" /><h2 class="font-semibold">Historico recente</h2></div>
      <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3"><Link v-for="item in history" :key="item.id" :href="route('imports.show', item.id)" class="rounded-2xl border border-stone-200 bg-white p-4 transition hover:border-brand-300 dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-semibold">{{ item.filename }}</p><p class="mt-1 text-xs text-stone-500">{{ kindLabel(item.kind) }} · {{ new Intl.DateTimeFormat('pt-BR').format(new Date(item.created_at)) }}</p></div><span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span></div><p class="mt-3 text-xs text-stone-400">{{ item.summary.total }} linhas · {{ item.summary.imported }} importadas</p></Link></div>
    </section>

    <div class="mt-6 flex gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-4 text-xs text-stone-500 dark:border-slate-800 dark:bg-slate-900"><AlertTriangle :size="17" class="shrink-0 text-amber-500" /><p>CSV nao executa formulas, mas celulas iniciadas por caracteres de formula sao neutralizadas nas exportacoes do Conta Pro. Revise arquivos recebidos de terceiros.</p></div>
  </AuthenticatedLayout>
</template>
