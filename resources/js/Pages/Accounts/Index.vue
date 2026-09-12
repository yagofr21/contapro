<script setup lang="ts">
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { bankMetaFrom, type BankOption } from '@/lib/banks';
import { formatDate, formatMoney } from '@/lib/format';
import type { Account, Option } from '@/types/finance';
import { Head, router } from '@inertiajs/vue3';
import { Archive, ArrowUpRight, Landmark, Plus, WalletCards } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import AccountForm from './Partials/AccountForm.vue';

const props = defineProps<{ accounts: Account[]; types: Option[]; currencies: Option[]; banks: BankOption[] }>();

const typeLabels: Record<string, string> = {
    cash: 'Dinheiro',
    checking: 'Conta corrente',
    credit_card: 'Cartao de credito',
    investment: 'Investimentos',
    savings: 'Poupanca',
};

const accountTone = (account: Account) => {
    const meta = bankMetaFrom(props.banks, account.bank);
    const color = account.color ?? meta?.color ?? '#1b6ef5';
    return {
        color,
        initials: meta?.initials ?? (account.type === 'cash' ? '💰' : '🏦'),
        isBank: Boolean(meta),
    };
};

const presented = (accounts: Account[]) =>
    accounts.map((account) => ({ account, tone: accountTone(account) }));

const modalOpen = ref(false);
const editing = ref<Account | null>(null);

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

onMounted(() => {
    const url = new URL(window.location.href);
    if (url.searchParams.get('create') === '1') {
        url.searchParams.delete('create');
        window.history.replaceState({}, '', url.toString());
        openCreate();
    }
});

const remove = (account: Account) => {
    if (confirm(`Remover a conta "${account.name}"? Os lancamentos serao preservados.`)) {
        router.delete(route('accounts.destroy', account.id));
    }
};
</script>

<template>
  <Head title="Contas" />
  <AuthenticatedLayout>
    <PageHeader kicker="Organizacao financeira" title="Suas contas" subtitle="Acompanhe onde o dinheiro esta e mantenha cada saldo sob controle.">
      <template #actions>
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700" @click="openCreate"><Plus :size="18" /> Nova conta</button>
      </template>
    </PageHeader>

    <div v-if="accounts.length" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="{ account, tone } in presented(accounts)" :key="account.id" class="group relative overflow-hidden rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
        <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full transition group-hover:scale-110" :style="{ backgroundColor: tone.color + '1A' }" />
        <div class="relative flex items-start justify-between gap-4">
          <div class="grid h-11 w-11 place-items-center rounded-2xl text-white shadow-sm" :style="tone.isBank ? { backgroundColor: tone.color } : { backgroundColor: tone.color + '22', color: tone.color }">
            <span v-if="tone.isBank" class="text-sm font-extrabold">{{ tone.initials }}</span>
            <Landmark v-else-if="account.type !== 'cash'" :size="21" />
            <WalletCards v-else :size="21" />
          </div>
          <StatusBadge v-if="account.is_archived" tone="slate" label="Arquivada" />
        </div>
        <div class="relative mt-5">
          <p class="text-sm font-medium text-stone-500 dark:text-slate-400">{{ account.name }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight" :class="Number(account.balance) < 0 ? 'text-rose-600' : 'text-stone-950 dark:text-white'">{{ formatMoney(account.balance ?? account.initial_balance, account.currency) }}</p>
          <p class="mt-2 text-xs text-stone-400">{{ typeLabels[account.type] }}</p>
        </div>
        <div v-if="account.credit_card?.has_limit" class="relative mt-4 rounded-xl bg-stone-50 p-3 dark:bg-slate-950">
          <div class="flex items-center justify-between text-[11px] font-medium text-stone-500 dark:text-slate-400">
            <span>Limite</span>
            <span :class="account.credit_card.over_limit ? 'font-bold text-rose-600 dark:text-rose-400' : ''">{{ account.credit_card.utilization }}% usado</span>
          </div>
          <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-stone-200 dark:bg-slate-700">
            <div class="h-full rounded-full transition-all duration-300" :class="account.credit_card.over_limit ? 'bg-rose-500' : account.credit_card.utilization && account.credit_card.utilization > 75 ? 'bg-amber-400' : 'bg-emerald-500'" :style="{ width: Math.min(account.credit_card.utilization ?? 0, 100) + '%' }" />
          </div>
          <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-stone-500 dark:text-slate-400">
            <div><p class="text-stone-400 dark:text-slate-500">Fatura atual</p><p class="font-semibold text-stone-700 dark:text-slate-200">{{ formatMoney(account.credit_card.current_invoice, account.currency) }}</p></div>
            <div><p class="text-stone-400 dark:text-slate-500">Disponivel</p><p class="font-semibold text-stone-700 dark:text-slate-200">{{ formatMoney(account.credit_card.available ?? '0', account.currency) }}</p></div>
            <div><p class="text-stone-400 dark:text-slate-500">Proximas faturas</p><p class="font-semibold text-stone-700 dark:text-slate-200">{{ formatMoney(account.credit_card.future_invoices, account.currency) }}</p></div>
            <div><p class="text-stone-400 dark:text-slate-500">Vence em</p><p class="font-semibold text-stone-700 dark:text-slate-200">{{ formatDate(account.credit_card.next_due) }}</p></div>
          </div>
        </div>
        <div class="relative mt-5 flex items-center gap-2 border-t border-stone-100 pt-4 dark:border-slate-800">
          <button class="inline-flex flex-1 items-center gap-1.5 text-xs font-semibold text-brand-600 hover:text-brand-700" @click="openEdit(account)"><ArrowUpRight :size="15" />Editar</button>
          <button class="inline-flex items-center gap-1.5 text-xs font-semibold text-stone-400 hover:text-rose-600" @click="remove(account)"><Archive :size="15" />Remover</button>
        </div>
      </article>
    </div>

    <EmptyState v-else class="mt-6" :icon="WalletCards" title="Nenhuma conta cadastrada" description="Comece pela conta que voce mais movimenta." />

    <Modal :show="modalOpen" max-width="lg" :title="editing ? 'Editar conta' : 'Nova conta'" @close="closeModal">
      <AccountForm v-if="modalOpen" :account="editing ?? undefined" :types="types" :currencies="currencies" :banks="banks" embedded @cancel="closeModal" />
    </Modal>
  </AuthenticatedLayout>
</template>