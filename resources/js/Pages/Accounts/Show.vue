<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { bankMetaFrom, type BankOption } from '@/lib/banks';
import { formatDate, formatMoney } from '@/lib/format';
import type { Account } from '@/types/finance';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard } from '@lucide/vue';

const props = defineProps<{ account: Account; paymentAccounts: { id: number; name: string; currency: string }[]; banks: BankOption[] }>();

const meta = bankMetaFrom(props.banks, props.account.bank);
const statusLabels: Record<string, string> = {
    open: 'Em aberto',
    closing_soon: 'Fecha em breve',
    due_soon: 'Vence em breve',
    overdue: 'Atrasada',
};
</script>

<template>
  <Head :title="account.name" />
  <AuthenticatedLayout>
    <PageHeader kicker="Cartão de crédito" :title="account.name" :subtitle="meta?.label ?? 'Detalhamento da fatura e compromissos do cartão.'">
      <template #actions>
        <Link :href="route('accounts.index')" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-stone-500 ring-1 ring-stone-200 hover:bg-stone-50 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800"><ArrowLeft :size="16" />Contas</Link>
      </template>
    </PageHeader>

    <section v-if="account.credit_card" class="mt-6 grid gap-4 lg:grid-cols-4">
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-500">Fatura atual</p><p class="mt-2 text-3xl font-bold text-rose-600 dark:text-rose-400">{{ formatMoney(account.credit_card.current_invoice, account.currency) }}</p></div><span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-bold text-stone-600 dark:bg-slate-800 dark:text-slate-300">{{ statusLabels[account.credit_card.status] }}</span></div>
        <p class="mt-3 text-sm text-stone-500 dark:text-slate-400">Ciclo {{ formatDate(account.credit_card.current_invoice_start) }} a {{ formatDate(account.credit_card.current_invoice_end) }} · vence em {{ formatDate(account.credit_card.next_due) }}</p>
      </article>
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-400">Saldo vencido</p><p class="mt-2 text-2xl font-bold" :class="Number(account.credit_card.overdue_balance) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-stone-900 dark:text-white'">{{ formatMoney(account.credit_card.overdue_balance, account.currency) }}</p></article>
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-400">Dívida total</p><p class="mt-2 text-2xl font-bold text-stone-900 dark:text-white">{{ formatMoney(account.credit_card.total_debt, account.currency) }}</p></article>
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-400">Limite total</p><p class="mt-2 text-xl font-bold">{{ formatMoney(account.credit_card.credit_limit ?? '0', account.currency) }}</p></article>
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-400">Limite utilizado</p><p class="mt-2 text-xl font-bold">{{ formatMoney(account.credit_card.utilized ?? '0', account.currency) }}</p></article>
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-400">Disponível</p><p class="mt-2 text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatMoney(account.credit_card.available ?? '0', account.currency) }}</p></article>
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-400">Saldo credor</p><p class="mt-2 text-xl font-bold text-brand-600 dark:text-brand-400">{{ formatMoney(account.credit_card.credit_balance, account.currency) }}</p></article>
    </section>

    <section v-if="account.credit_card" class="mt-6 grid gap-6 xl:grid-cols-2">
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Fatura atual</h2>
        <ul class="mt-4 divide-y divide-stone-100 dark:divide-slate-800">
          <li v-for="item in account.credit_card.current_purchases" :key="`${item.date}-${item.description}`" class="flex items-center gap-3 py-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/60"><CreditCard :size="16" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ item.description }}</p><p class="text-xs text-stone-400">{{ formatDate(item.date) }}</p></div><p class="font-bold text-rose-600">{{ formatMoney(item.remaining, account.currency) }}</p></li>
        </ul>
        <p v-if="!account.credit_card.current_purchases?.length" class="mt-6 text-center text-sm text-stone-400">Sem compras pendentes neste ciclo.</p>
      </article>

      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Próximas faturas</h2>
        <ul class="mt-4 divide-y divide-stone-100 dark:divide-slate-800">
          <li v-for="item in account.credit_card.future_installments" :key="`${item.cycle_end}-${item.description}`" class="flex items-center gap-3 py-3"><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ item.description }}</p><p class="text-xs text-stone-400">Fatura de {{ formatDate(item.cycle_end) }} · vence {{ formatDate(item.due_date) }}</p></div><p class="font-bold">{{ formatMoney(item.remaining, account.currency) }}</p></li>
        </ul>
        <p v-if="!account.credit_card.future_installments?.length" class="mt-6 text-center text-sm text-stone-400">Sem parcelas futuras.</p>
      </article>

      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Pagamentos</h2>
        <ul class="mt-4 divide-y divide-stone-100 dark:divide-slate-800"><li v-for="payment in account.credit_card.payments" :key="`${payment.date}-${payment.amount}`" class="flex justify-between gap-3 py-3"><div><p class="text-sm font-semibold">{{ payment.description ?? 'Pagamento de fatura' }}</p><p class="text-xs text-stone-400">{{ formatDate(payment.date) }}</p></div><p class="font-bold text-emerald-600">{{ formatMoney(payment.amount, account.currency) }}</p></li></ul>
        <p v-if="!account.credit_card.payments?.length" class="mt-6 text-center text-sm text-stone-400">Nenhum pagamento registrado.</p>
      </article>

      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Histórico de faturas</h2>
        <ul class="mt-4 divide-y divide-stone-100 dark:divide-slate-800"><li v-for="invoice in account.credit_card.history" :key="invoice.cycle" class="flex justify-between gap-3 py-3"><div><p class="text-sm font-semibold">Fatura fechada em {{ formatDate(invoice.cycle) }}</p><p class="text-xs text-stone-400">Vencimento {{ formatDate(invoice.due_date) }}</p></div><p class="font-bold">{{ formatMoney(invoice.amount, account.currency) }}</p></li></ul>
        <p class="mt-4 rounded-2xl bg-stone-50 p-3 text-xs text-stone-500 dark:bg-slate-950 dark:text-slate-400">Saldo contábil técnico: {{ formatMoney(account.balance ?? '0', account.currency) }}. Para cartões, a decisão financeira usa dívida vencida, fatura atual e compromissos futuros.</p>
      </article>
    </section>
  </AuthenticatedLayout>
</template>
