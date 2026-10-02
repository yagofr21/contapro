<script setup lang="ts">
import CreditCardLimit from '@/Components/CreditCardLimit.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { bankMetaFrom, type BankOption } from '@/lib/banks';
import { formatDate, formatMoney, parseDecimalInput } from '@/lib/format';
import type { Account, Option } from '@/types/finance';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Archive, ArrowUpRight, Landmark, Plus, WalletCards } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import AccountForm from './Partials/AccountForm.vue';

const props = defineProps<{ accounts: Account[]; types: Option[]; currencies: Option[]; banks: BankOption[] }>();

const accountView = ref('all');
const visibleAccounts = computed(() => props.accounts.filter((account) => accountView.value === 'archived' ? account.is_archived : !account.is_archived && (accountView.value === 'all' || (accountView.value === 'cards' ? account.type === 'credit_card' : account.type !== 'credit_card'))));
const balances = computed(() => {
    const totals = new Map<string, number>();
    for (const account of props.accounts.filter((item) => !item.is_archived && ['cash', 'checking', 'savings'].includes(item.type))) {
        totals.set(account.currency, (totals.get(account.currency) ?? 0) + Number(account.balance ?? account.initial_balance));
    }
    return [...totals].map(([currency, balance]) => ({ currency, balance: String(balance) }));
});
const typeLabels: Record<string, string> = {
    cash: 'Dinheiro',
    checking: 'Conta corrente',
    credit_card: 'Cartão de crédito',
    investment: 'Investimentos',
    savings: 'Poupança',
};

const accountTone = (account: Account) => {
    const meta = bankMetaFrom(props.banks, account.bank);
    const color = account.color ?? meta?.color ?? '#1b6ef5';
    return {
        color,
        initials: meta?.initials ?? account.name.slice(0, 2).toUpperCase(),
        isBank: Boolean(meta),
    };
};

const presented = (accounts: Account[]) =>
    accounts.map((account) => ({ account, tone: accountTone(account) }));

const modalOpen = ref(false);
const editing = ref<Account | null>(null);
const paying = ref<Account | null>(null);
const paymentForm = useForm({ account_id: '', amount: '', transaction_date: new Date().toISOString().slice(0, 10), description: '' });

const openCreate = () => {
    editing.value = null;
    modalOpen.value = true;
};

const openEdit = (account: Account) => {
    editing.value = account;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editing.value = null;
};

const openPayment = (account: Account) => {
    paying.value = account;
    paymentForm.defaults({
        account_id: '',
        amount: account.credit_card?.current_invoice ?? '',
        transaction_date: new Date().toISOString().slice(0, 10),
        description: `Pagamento de fatura ${account.name}`,
    });
    paymentForm.reset();
};

const closePayment = () => {
    paying.value = null;
    paymentForm.clearErrors();
};

const paymentAccounts = (card: Account) => props.accounts.filter((account) => !account.is_archived && account.id !== card.id && account.type !== 'credit_card' && account.currency === card.currency);

const submitPayment = () => {
    if (!paying.value) return;
    paymentForm.transform((data) => ({ ...data, amount: parseDecimalInput(data.amount) }))
        .post(route('accounts.pay-card', paying.value.id), { onSuccess: closePayment });
};

onMounted(() => {
    const url = new URL(window.location.href);
    if (url.searchParams.get('create') === '1') {
        url.searchParams.delete('create');
        window.history.replaceState({}, '', url.toString());
        openCreate();
    }
});

const remove = (account: Account) => {
    if (confirm(`Remover a conta "${account.name}"? Os lançamentos serão preservados.`)) {
        router.delete(route('accounts.destroy', account.id));
    }
};
</script>

<template>
  <Head title="Contas" />
  <AuthenticatedLayout>
    <PageHeader kicker="Organização financeira" title="Contas e cartões" subtitle="Acompanhe onde o dinheiro está e mantenha cada saldo sob controle.">
      <template #actions>
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700" @click="openCreate"><Plus :size="18" /> Nova conta</button>
      </template>
    </PageHeader>

    <section v-if="balances.length" aria-label="Saldo disponível por moeda" class="cp-card mt-6 flex flex-wrap gap-x-10 gap-y-4 p-5">
      <div v-for="balance in balances" :key="balance.currency" class="min-w-0"><p class="text-xs text-stone-600 dark:text-slate-400">Saldo disponível · {{ balance.currency }}</p><p class="financial-value mt-1 text-2xl font-bold">{{ formatMoney(balance.balance, balance.currency) }}</p></div>
      <p class="w-full text-xs text-stone-600 dark:text-slate-400">Dinheiro, contas correntes e poupanças ativas. Cartões e investimentos apresentados separadamente.</p>
    </section>
    <div class="mt-6 flex flex-wrap gap-2" role="group" aria-label="Exibir contas">
      <button v-for="view in [{ key: 'all', label: 'Todas as ativas' }, { key: 'accounts', label: 'Contas' }, { key: 'cards', label: 'Cartões' }, { key: 'archived', label: 'Arquivadas' }]" :key="view.key" :aria-pressed="accountView === view.key" class="min-h-11 rounded-lg border px-4 text-sm font-semibold" :class="accountView === view.key ? 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' : 'border-stone-200 bg-white text-stone-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400'" @click="accountView = view.key">{{ view.label }}</button>
    </div>

    <div v-if="visibleAccounts.length" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="{ account, tone } in presented(visibleAccounts)" :key="account.id" class="group relative overflow-hidden rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-brand-200 dark:border-slate-800 dark:bg-slate-900">
        <div class="relative flex items-start justify-between gap-4">
          <div class="grid h-11 w-11 place-items-center rounded-2xl text-white shadow-sm" :style="tone.isBank ? { backgroundColor: tone.color } : { backgroundColor: tone.color + '22', color: tone.color }">
            <span v-if="tone.isBank" class="text-sm font-extrabold">{{ tone.initials }}</span>
            <Landmark v-else-if="account.type !== 'cash'" :size="21" />
            <WalletCards v-else :size="21" />
          </div>
          <StatusBadge v-if="account.is_archived" tone="slate" label="Arquivada" />
        </div>
        <div class="relative mt-5">
          <p class="text-sm font-medium text-stone-600 dark:text-slate-400">{{ account.name }}</p>
          <p v-if="account.credit_card" class="mt-2 text-xs font-semibold uppercase tracking-[0.18em] text-stone-600 dark:text-slate-400 ">Fatura atual</p>
          <p class="financial-value mt-1 text-2xl font-bold" :class="account.credit_card ? 'text-rose-700 dark:text-rose-300 ' : Number(account.balance) < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-stone-950 dark:text-white'">
            {{ formatMoney(account.credit_card?.current_invoice ?? account.balance ?? account.initial_balance, account.currency) }}
          </p>
          <p class="mt-2 text-xs text-stone-600 dark:text-slate-400">{{ typeLabels[account.type] }}</p>
        </div>
        <div v-if="account.credit_card" class="relative mt-4 rounded-xl bg-stone-50 p-3 dark:bg-slate-950">
          <div class="rounded-lg bg-white p-2 dark:bg-slate-900"><p class="text-stone-600 dark:text-slate-400 ">Situação da fatura</p><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400 ">Fecha em {{ formatDate(account.credit_card.next_closing) }} · vence em {{ formatDate(account.credit_card.next_due) }}</p></div>
          <div class="mt-3 grid grid-cols-2 gap-2 text-[11px] text-stone-600 dark:text-slate-400">
            <div v-if="Number(account.credit_card.overdue_balance) > 0"><p class="text-stone-600 dark:text-slate-400 ">Saldo vencido</p><p class="font-semibold text-rose-700 dark:text-rose-300 ">{{ formatMoney(account.credit_card.overdue_balance, account.currency) }}</p></div>
            <div><p class="text-stone-600 dark:text-slate-400 ">Próximas faturas</p><p class="font-semibold text-stone-700 dark:text-slate-200">{{ formatMoney(account.credit_card.future_invoices, account.currency) }}</p></div>
            <div><p class="text-stone-600 dark:text-slate-400 ">Dívida total</p><p class="font-semibold text-stone-700 dark:text-slate-200">{{ formatMoney(account.credit_card.total_debt, account.currency) }}</p></div>
            <div><p class="text-stone-600 dark:text-slate-400 ">Disponível</p><p class="font-semibold text-stone-700 dark:text-slate-200">{{ account.credit_card.has_limit ? formatMoney(account.credit_card.available ?? '0', account.currency) : 'Não informado' }}</p></div>
          </div>
          <CreditCardLimit class="mt-4 border-t border-stone-200 pt-3 dark:border-slate-800" :card="account.credit_card" :currency="account.currency" />
        </div>
        <div class="relative mt-5 flex flex-wrap items-center gap-3 border-t border-stone-100 pt-4 dark:border-slate-800">
          <Link v-if="account.credit_card" :href="route('accounts.show', account.id)" class="inline-flex min-h-11 items-center gap-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 hover:text-brand-700">Ver detalhes</Link>
          <button v-if="account.credit_card" class="inline-flex min-h-11 items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 hover:text-emerald-700" @click="openPayment(account)">Pagar fatura</button>
          <button class="inline-flex min-h-11 items-center gap-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 hover:text-brand-700" @click="openEdit(account)"><ArrowUpRight :size="15" />Editar</button>
          <button class="inline-flex min-h-11 items-center gap-1.5 text-xs font-semibold text-stone-600 dark:text-slate-400 hover:text-rose-600" @click="remove(account)"><Archive :size="15" />Remover</button>
        </div>
      </article>
    </div>

    <EmptyState v-else class="mt-6" :icon="WalletCards" title="Nenhuma conta nesta seleção" description="Cadastre uma conta ou cartão para começar a acompanhar suas finanças."><template #action><button class="cp-button" @click="openCreate"><Plus :size="17" />Nova conta</button></template></EmptyState>

    <Modal :show="modalOpen" max-width="lg" :title="editing ? 'Editar conta' : 'Nova conta'" @close="closeModal">
      <AccountForm v-if="modalOpen" :account="editing ?? undefined" :types="types" :currencies="currencies" :banks="banks" embedded @cancel="closeModal" />
    </Modal>
    <Modal :show="Boolean(paying)" max-width="lg" title="Pagar fatura" @close="closePayment">
      <form v-if="paying" class="px-6 py-5 sm:px-7" @submit.prevent="submitPayment">
        <div class="space-y-4">
          <label><span class="mb-2 block text-sm font-semibold">Conta de origem</span><select v-model="paymentForm.account_id" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="" disabled>Selecione</option><option v-for="account in paymentAccounts(paying)" :key="account.id" :value="String(account.id)">{{ account.name }} · {{ account.currency }}</option></select><p v-if="paymentForm.errors.account_id" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ paymentForm.errors.account_id }}</p></label>
          <label><span class="mb-2 block text-sm font-semibold">Valor pago</span><input v-model="paymentForm.amount" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" /><p v-if="paymentForm.errors.amount" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ paymentForm.errors.amount }}</p></label>
          <label><span class="mb-2 block text-sm font-semibold">Data</span><input v-model="paymentForm.transaction_date" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /><p v-if="paymentForm.errors.transaction_date" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ paymentForm.errors.transaction_date }}</p></label>
          <label><span class="mb-2 block text-sm font-semibold">Observação <span class="font-normal text-stone-600 dark:text-slate-400">(opcional)</span></span><textarea v-model="paymentForm.description" rows="3" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /></label>
        </div>
        <div class="mt-5 flex justify-end gap-2 border-t border-stone-100 pt-5 dark:border-slate-800"><button type="button" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-stone-600 dark:text-slate-400 hover:bg-stone-100 dark:hover:bg-slate-800" @click="closePayment">Cancelar</button><button :disabled="paymentForm.processing" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Registrar pagamento</button></div>
      </form>
    </Modal>
  </AuthenticatedLayout>
</template>
