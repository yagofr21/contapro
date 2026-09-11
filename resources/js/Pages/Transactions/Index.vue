<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import SelectInput from '@/Components/SelectInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import type { Category, Paginated, Transaction } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, Filter, Pencil, Plus, ReceiptText, Search, Trash2 } from '@lucide/vue';
import { onMounted, reactive, ref } from 'vue';
import TransactionForm from './Partials/TransactionForm.vue';

type AccountOption = { id: number; name: string; currency: string };
type Filters = { account_id?: number; category_id?: number; type?: string; from?: string; to?: string; search?: string };

const props = defineProps<{
    transactions: Paginated<Transaction>;
    accounts: AccountOption[];
    categories: Category[];
    filters: Filters;
}>();

const modalOpen = ref(false);
const editing = ref<Transaction | null>(null);

const openCreate = () => {
    editing.value = null;
    modalOpen.value = true;
};

const openEdit = (transaction: Transaction) => {
    editing.value = transaction;
    modalOpen.value = true;
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
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    search: props.filters.search ?? '',
});

const applyFilters = () => router.get(route('transactions.index'), form, { preserveState: true, replace: true });
const paginationLabel = (label: string) => label.replace('&laquo;', '‹').replace('&raquo;', '›');
const clearFilters = () => {
    Object.assign(form, { account_id: '', category_id: '', type: '', from: '', to: '', search: '' });
    applyFilters();
};
const remove = (transaction: Transaction) => {
    if (confirm('Remover este lancamento?')) {
        router.delete(route('transactions.destroy', transaction.id));
    }
};

const typePresentation = (transaction: Transaction) => {
    if (transaction.is_transfer) return { label: 'Transferencia', icon: ArrowLeftRight, tone: 'text-brand-600 bg-brand-50 dark:bg-brand-950/50' };
    if (transaction.type === 'income') return { label: 'Receita', icon: ArrowDownLeft, tone: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50' };
    return { label: 'Despesa', icon: ArrowUpRight, tone: 'text-rose-600 bg-rose-50 dark:bg-rose-950/50' };
};
</script>

<template>
  <Head title="Lancamentos" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Fluxo de caixa</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">Lancamentos</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-slate-400">Receitas, despesas e transferencias reunidas em uma linha do tempo.</p>
      </div>
      <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700" @click="openCreate"><Plus :size="18" />Novo lancamento</button>
    </section>

    <form class="mt-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900" @submit.prevent="applyFilters">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <label class="relative xl:col-span-2"><Search :size="16" class="absolute left-3 top-3 text-stone-400" /><input v-model="form.search" class="w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="Buscar descricao" /></label>
        <SelectInput v-model="form.account_id"><option value="">Todas as contas</option><option v-for="account in accounts" :key="account.id" :value="String(account.id)">{{ account.name }}</option></SelectInput>
        <SelectInput v-model="form.type"><option value="">Todos os tipos</option><option value="expense">Despesas</option><option value="income">Receitas</option><option value="transfer">Transferencias</option></SelectInput>
        <input v-model="form.from" type="date" class="rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" aria-label="Data inicial" />
        <input v-model="form.to" type="date" class="rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" aria-label="Data final" />
      </div>
      <div class="mt-3 flex items-center justify-end gap-2">
        <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800" @click="clearFilters">Limpar</button>
        <button class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white dark:bg-brand-600"><Filter :size="14" />Filtrar</button>
      </div>
    </form>

    <div v-if="transactions.data.length" class="mt-5 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="hidden grid-cols-[1fr_160px_130px_140px_80px] gap-4 border-b border-stone-100 px-5 py-3 text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:border-slate-800 md:grid">
        <span>Lancamento</span><span>Conta</span><span>Data</span><span class="text-right">Valor</span><span />
      </div>
      <article v-for="transaction in transactions.data" :key="transaction.id" class="grid gap-3 border-b border-stone-100 px-4 py-4 last:border-0 dark:border-slate-800 md:grid-cols-[1fr_160px_130px_140px_80px] md:items-center md:px-5">
        <div class="flex min-w-0 items-center gap-3">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl" :class="typePresentation(transaction).tone"><component :is="typePresentation(transaction).icon" :size="18" /></span>
          <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ transaction.description || typePresentation(transaction).label }}</p><p class="mt-0.5 text-xs text-stone-400"><span v-if="transaction.category_color" class="mr-1 inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: transaction.category_color }" />{{ transaction.category_name || typePresentation(transaction).label }}</p></div>
        </div>
        <p class="text-sm text-stone-500 dark:text-slate-400">{{ transaction.account_name }}</p>
        <p class="text-sm text-stone-500 dark:text-slate-400">{{ formatDate(transaction.transaction_date) }}</p>
        <p class="text-left text-sm font-bold md:text-right" :class="transaction.type === 'income' ? 'text-emerald-600' : transaction.is_transfer ? 'text-brand-600' : 'text-rose-600'">{{ transaction.type === 'income' ? '+' : transaction.is_transfer ? '' : '-' }}{{ formatMoney(transaction.amount, transaction.currency) }}</p>
        <div class="flex justify-end gap-1">
          <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800" @click="openEdit(transaction)"><Pencil :size="15" /></button>
          <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" @click="remove(transaction)"><Trash2 :size="15" /></button>
        </div>
      </article>
    </div>

    <div v-else class="mt-5 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900"><ReceiptText :size="36" class="mx-auto text-stone-300 dark:text-slate-600" /><h2 class="mt-4 text-lg font-semibold">Nenhum lancamento encontrado</h2><p class="mt-1 text-sm text-stone-500">Ajuste os filtros ou registre uma movimentacao.</p></div>

    <nav v-if="transactions.links.length > 3" class="mt-5 flex flex-wrap items-center justify-center gap-1">
      <Link v-for="link in transactions.links" :key="link.label" :href="link.url ?? '#'" class="min-w-9 rounded-lg px-3 py-2 text-center text-xs font-semibold" :class="link.active ? 'bg-brand-600 text-white' : link.url ? 'bg-white text-stone-600 hover:bg-stone-100 dark:bg-slate-900 dark:text-slate-300' : 'pointer-events-none text-stone-300'" preserve-state>{{ paginationLabel(link.label) }}</Link>
    </nav>

    <Modal :show="modalOpen" max-width="2xl" :title="editing ? 'Editar lancamento' : 'Novo lancamento'" @close="closeModal">
      <TransactionForm v-if="modalOpen" :transaction="editing ?? undefined" :accounts="accounts" :categories="categories" @cancel="closeModal" />
    </Modal>
  </AuthenticatedLayout>
</template>