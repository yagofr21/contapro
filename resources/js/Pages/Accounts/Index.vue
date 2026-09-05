<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatMoney } from '@/lib/format';
import type { Account } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, ArrowUpRight, Landmark, Plus, WalletCards } from '@lucide/vue';

defineProps<{ accounts: Account[] }>();

const typeLabels: Record<string, string> = {
    cash: 'Dinheiro',
    checking: 'Conta corrente',
    credit_card: 'Cartao de credito',
    investment: 'Investimentos',
    savings: 'Poupanca',
};

const remove = (account: Account) => {
    if (confirm(`Remover a conta "${account.name}"? Os lancamentos serao preservados.`)) {
        router.delete(route('accounts.destroy', account.id));
    }
};
</script>

<template>
  <Head title="Contas" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Organizacao financeira</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950 dark:text-white">Suas contas</h1>
        <p class="mt-2 max-w-xl text-sm text-stone-500 dark:text-slate-400">Acompanhe onde o dinheiro esta e mantenha cada saldo sob controle.</p>
      </div>
      <Link :href="route('accounts.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
        <Plus :size="18" /> Nova conta
      </Link>
    </section>

    <div v-if="accounts.length" class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="account in accounts" :key="account.id" class="group relative overflow-hidden rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
        <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-brand-50 transition group-hover:scale-110 dark:bg-brand-950/40" />
        <div class="relative flex items-start justify-between gap-4">
          <div class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
            <Landmark v-if="account.type !== 'cash'" :size="21" />
            <WalletCards v-else :size="21" />
          </div>
          <span v-if="account.is_archived" class="rounded-full bg-stone-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-stone-500 dark:bg-slate-800">Arquivada</span>
        </div>
        <div class="relative mt-5">
          <p class="text-sm font-medium text-stone-500 dark:text-slate-400">{{ account.name }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight" :class="Number(account.balance) < 0 ? 'text-rose-600' : 'text-stone-950 dark:text-white'">{{ formatMoney(account.balance ?? account.initial_balance, account.currency) }}</p>
          <p class="mt-2 text-xs text-stone-400">{{ typeLabels[account.type] }}</p>
        </div>
        <div class="relative mt-5 flex items-center gap-2 border-t border-stone-100 pt-4 dark:border-slate-800">
          <Link :href="route('accounts.edit', account.id)" class="inline-flex flex-1 items-center gap-1.5 text-xs font-semibold text-brand-600 hover:text-brand-700"><ArrowUpRight :size="15" />Editar</Link>
          <button class="inline-flex items-center gap-1.5 text-xs font-semibold text-stone-400 hover:text-rose-600" @click="remove(account)"><Archive :size="15" />Remover</button>
        </div>
      </article>
    </div>

    <div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
      <WalletCards :size="36" class="mx-auto text-stone-300 dark:text-slate-600" />
      <h2 class="mt-4 text-lg font-semibold">Nenhuma conta cadastrada</h2>
      <p class="mt-1 text-sm text-stone-500">Comece pela conta que voce mais movimenta.</p>
    </div>
  </AuthenticatedLayout>
</template>
