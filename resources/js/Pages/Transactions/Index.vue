<script setup lang="ts">
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import type { Category, Paginated, Transaction } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, Filter, Pencil, Plus, ReceiptText, Search, Trash2 } from '@lucide/vue';
import { onMounted, reactive, ref } from 'vue';
import TransactionForm from './Partials/TransactionForm.vue';

type AccountOption = { id: number; name: string; currency: string };
type Filters = { account_id?: number; category_id?: number; type?: string; status?: string; from?: string; to?: string; search?: string };
type Summary = { currency: string; income: string; expenses: string; transfers: string; net: string; transaction_count: number };

const props = defineProps<{
    transactions: Paginated<Transaction>;
    accounts: AccountOption[];
    categories: Category[];
    filters: Filters;
    summaries: Summary[];
}>();

const modalOpen = ref(false);
const editing = ref<Transaction | null>(null);
const modalTone = ref<false | 'brand' | 'green' | 'rose'>(false);

const openCreate = () => {
    editing.value = null;
    modalTone.value = 'rose';
    modalOpen.value = true;
};

const openEdit = (transaction: Transaction) => {
    editing.value = transaction;
    modalTone.value = transaction.is_transfer ? 'brand' : transaction.type === 'income' ? 'green' : 'rose';
    modalOpen.value = true;
};

const onTypeChange = (type: string) => {
    if (!editing.value) {
        modalTone.value = type === 'income' ? 'green' : type === 'transfer' ? 'brand' : 'rose';
    }
};

const closeModal = () => {
    modalOpen.value = false;
    editing.value = null;
};

onMounted(() => {
    const url = new URL(window.location.href);
    if (url.searchParams.get('create') === '1') {
        url.searchParams.delete('create');
        window.history.replaceState({}, '', url.toString());
        openCreate();
    }
});

const form = reactive({
    account_id: props.filters.account_id ? String(props.filters.account_id) : '',
    category_id: props.filters.category_id ? String(props.filters.category_id) : '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ? formatDate(props.filters.from) : '',
    to: props.filters.to ? formatDate(props.filters.to) : '',
    search: props.filters.search ?? '',
});

const parseBrazilianDate = (value: string) => {
    const match = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);

    return match ? `${match[3]}-${match[2]}-${match[1]}` : value;
};

const applyFilters = () => router.get(route('transactions.index'), {
    ...form,
    from: form.from ? parseBrazilianDate(form.from) : '',
    to: form.to ? parseBrazilianDate(form.to) : '',
}, { preserveState: true, replace: true });
const paginationLabel = (label: string) => label
    .replace('&laquo; Previous', 'Anterior')
    .replace('Next &raquo;', 'Próxima')
    .replace('&laquo;', '‹')
    .replace('&raquo;', '›');
const clearFilters = () => {
    Object.assign(form, { account_id: '', category_id: '', type: '', status: '', from: '', to: '', search: '' });
    applyFilters();
};
const remove = (transaction: Transaction) => {
    if (confirm('Remover este lançamento?')) {
        router.delete(route('transactions.destroy', transaction.id));
    }
};

const typePresentation = (transaction: Transaction) => {
    if (transaction.is_transfer) return { label: 'Transferência', icon: ArrowLeftRight, tone: 'text-brand-600 bg-brand-50 dark:bg-brand-950/50' };
    if (transaction.type === 'income') return { label: 'Receita', icon: ArrowDownLeft, tone: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50' };
    return { label: 'Despesa', icon: ArrowUpRight, tone: 'text-rose-600 bg-rose-50 dark:bg-rose-950/50' };
};
</script>

<template>
  <Head title="Lançamentos" />
  <AuthenticatedLayout>
    <PageHeader kicker="Fluxo de caixa" title="Lançamentos" subtitle="Receitas, despesas e transferências reunidas em uma linha do tempo.">
      <template #actions>
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700" @click="openCreate"><Plus :size="18" />Novo lançamento</button>
      </template>
    </PageHeader>

    <form class="mt-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900" @submit.prevent="applyFilters">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-8">
        <label class="relative xl:col-span-2"><Search :size="16" class="absolute left-3 top-3 text-stone-400" /><input v-model="form.search" class="w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="Buscar descrição" /></label>
        <SelectInput v-model="form.account_id"><option value="">Todas as contas</option><option v-for="account in accounts" :key="account.id" :value="String(account.id)">{{ account.name }}</option></SelectInput>
        <SelectInput v-model="form.category_id"><option value="">Todas as categorias</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></SelectInput>
        <SelectInput v-model="form.type"><option value="">Todos os tipos</option><option value="expense">Despesas</option><option value="income">Receitas</option><option value="transfer">Transferências</option></SelectInput>
        <SelectInput v-model="form.status"><option value="">Realizados e futuros</option><option value="realized">Realizados</option><option value="future">Futuros</option></SelectInput>
        <input v-model="form.from" type="text" inputmode="numeric" pattern="\d{2}/\d{2}/\d{4}" class="rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="dd/mm/aaaa" aria-label="Data inicial no formato dia mês ano" />
        <input v-model="form.to" type="text" inputmode="numeric" pattern="\d{2}/\d{2}/\d{4}" class="rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="dd/mm/aaaa" aria-label="Data final no formato dia mês ano" />
      </div>
      <div class="mt-3 flex items-center justify-end gap-2">
        <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800" @click="clearFilters">Limpar</button>
        <button class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white dark:bg-brand-600"><Filter :size="14" />Filtrar</button>
      </div>
    </form>

    <section v-if="summaries.length" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="summary in summaries" :key="summary.currency" class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">{{ summary.currency }}</p><h2 class="mt-1 text-sm font-bold">Totais filtrados</h2></div><span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-bold text-stone-500 dark:bg-slate-800 dark:text-slate-300">{{ summary.transaction_count }}</span></div>
        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
          <div><p class="text-xs text-stone-400">Receitas</p><p class="font-bold text-emerald-600">{{ formatMoney(summary.income, summary.currency) }}</p></div>
          <div><p class="text-xs text-stone-400">Despesas</p><p class="font-bold text-rose-600">{{ formatMoney(summary.expenses, summary.currency) }}</p></div>
          <div><p class="text-xs text-stone-400">Transferências</p><p class="font-bold text-brand-600">{{ formatMoney(summary.transfers, summary.currency) }}</p></div>
          <div><p class="text-xs text-stone-400">Resultado</p><p class="font-bold" :class="Number(summary.net) >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ formatMoney(summary.net, summary.currency) }}</p></div>
        </div>
      </article>
    </section>

    <div v-if="transactions.data.length" class="mt-5 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="hidden grid-cols-[1fr_160px_130px_140px_80px] gap-4 border-b border-stone-100 px-5 py-3 text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:border-slate-800 md:grid">
        <span>Lançamento</span><span>Conta</span><span>Data</span><span class="text-right">Valor</span><span />
      </div>
      <article v-for="transaction in transactions.data" :key="transaction.id" class="flex items-center gap-3 border-b border-stone-100 px-4 py-3.5 transition last:border-0 hover:bg-stone-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40 md:grid md:grid-cols-[1fr_160px_130px_140px_80px] md:items-center md:gap-4 md:px-5 md:py-4">
        <div class="flex min-w-0 flex-1 items-center gap-3 md:min-w-0">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl" :class="typePresentation(transaction).tone"><component :is="typePresentation(transaction).icon" :size="18" /></span>
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold">{{ transaction.description || typePresentation(transaction).label }} <span v-if="transaction.is_future" class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">Futuro</span></p>
            <details v-if="transaction.description && transaction.description.length > 48" class="mt-1 text-xs text-stone-500 dark:text-slate-400">
              <summary class="cursor-pointer font-semibold text-brand-600 hover:text-brand-700">Ver descrição completa</summary>
              <p class="mt-1 max-w-xl whitespace-normal break-words">{{ transaction.description }}</p>
            </details>
            <p class="mt-0.5 text-xs text-stone-400 md:hidden">{{ transaction.account_name }} · {{ formatDate(transaction.transaction_date) }}</p>
            <p class="mt-0.5 hidden text-xs text-stone-400 md:block"><span v-if="transaction.category_color" class="mr-1 inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: transaction.category_color }" />{{ transaction.category_name || typePresentation(transaction).label }}</p>
          </div>
        </div>
        <p class="hidden text-sm text-stone-500 dark:text-slate-400 md:block">{{ transaction.account_name }}</p>
        <p class="hidden text-sm text-stone-500 dark:text-slate-400 md:block">{{ formatDate(transaction.transaction_date) }}</p>
        <p class="whitespace-nowrap text-sm font-bold md:text-right" :class="transaction.type === 'income' ? 'text-emerald-600' : transaction.is_transfer ? 'text-brand-600' : 'text-rose-600'">{{ transaction.type === 'income' ? '+' : transaction.is_transfer ? '' : '-' }}{{ formatMoney(transaction.amount, transaction.currency) }}</p>
        <div class="flex shrink-0 items-center gap-1 md:justify-end">
          <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800" :aria-label="`Editar lançamento ${transaction.description || typePresentation(transaction).label}`" @click="openEdit(transaction)"><Pencil :size="15" /></button>
          <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" :aria-label="`Remover lançamento ${transaction.description || typePresentation(transaction).label}`" @click="remove(transaction)"><Trash2 :size="15" /></button>
        </div>
      </article>
    </div>

    <EmptyState v-else class="mt-5" :icon="ReceiptText" title="Nenhum lançamento encontrado" description="Ajuste os filtros ou registre uma movimentação." />

    <p v-if="transactions.total" class="mt-5 text-center text-xs text-stone-500 dark:text-slate-400">Mostrando {{ transactions.from }} a {{ transactions.to }} de {{ transactions.total }} lançamentos</p>

    <nav v-if="transactions.links.length > 3" class="mt-3 flex flex-wrap items-center justify-center gap-1">
      <Link v-for="link in transactions.links" :key="link.label" :href="link.url ?? '#'" class="min-w-9 rounded-lg px-3 py-2 text-center text-xs font-semibold" :class="link.active ? 'bg-brand-600 text-white' : link.url ? 'bg-white text-stone-600 hover:bg-stone-100 dark:bg-slate-900 dark:text-slate-300' : 'pointer-events-none text-stone-300'" preserve-state>{{ paginationLabel(link.label) }}</Link>
    </nav>

    <Modal :show="modalOpen" max-width="2xl" :gradient="modalTone" :title="editing ? 'Editar lançamento' : 'Novo lançamento'" @close="closeModal">
      <TransactionForm v-if="modalOpen" :transaction="editing ?? undefined" :accounts="accounts" :categories="categories" @cancel="closeModal" @type-change="onTypeChange" />
    </Modal>
  </AuthenticatedLayout>
</template>
