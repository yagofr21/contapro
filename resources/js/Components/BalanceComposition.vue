<script setup lang="ts">
import type { Account } from '@/types/finance';
import { formatDate, formatMoney } from '@/lib/format';
import { Link } from '@inertiajs/vue3';
defineProps<{ accounts: Account[]; asOf: string; currency: string }>();
</script>
<template>
  <div class="p-5 sm:p-6">
    <p class="text-sm text-stone-600 dark:text-slate-400">Saldo inicial + entradas − saídas até {{ formatDate(asOf) }}. Entradas e saídas incluem transferências. Cartões e investimentos ficam separados.</p>
    <p v-if="accounts.some(account => account.is_archived)" class="mt-3 text-sm text-amber-800 dark:text-amber-200">Inclui contas arquivadas, que continuam compondo este indicador.</p>
    <ul class="mt-4 divide-y divide-stone-200 dark:divide-slate-700">
      <li v-for="account in accounts" :key="account.id" class="py-4">
        <div class="flex flex-wrap justify-between gap-2"><p class="font-semibold">{{ account.name }} <span v-if="account.is_archived" class="text-xs font-normal text-stone-600 dark:text-slate-400">(arquivada)</span></p><p class="financial-value font-bold">{{ formatMoney(account.balance ?? account.initial_balance, currency) }}</p></div>
        <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
          <div><dt class="text-stone-600 dark:text-slate-400">Saldo inicial</dt><dd>{{ formatMoney(account.initial_balance, currency) }}</dd></div>
          <div v-if="account.credits !== undefined"><dt class="text-stone-600 dark:text-slate-400">Entradas até a referência</dt><dd>+ {{ formatMoney(account.credits, currency) }}</dd></div>
          <div v-if="account.debits !== undefined"><dt class="text-stone-600 dark:text-slate-400">Saídas até a referência</dt><dd>− {{ formatMoney(account.debits, currency) }}</dd></div>
        </dl>
        <Link :href="route('transactions.index', { account_id: account.id, currency, to: asOf, status: 'realized' })" class="mt-2 inline-flex min-h-11 items-center text-sm font-semibold text-brand-700 underline dark:text-brand-300">Ver lançamentos de {{ account.name }}</Link>
      </li>
    </ul>
    <p v-if="!accounts.length" class="py-6 text-sm text-stone-600 dark:text-slate-400">Nenhuma conta de dinheiro, corrente ou poupança nesta moeda.</p>
    <p class="mt-3 text-xs text-stone-600 dark:text-slate-400">A lista de lançamentos exibe uma linha por transferência, na conta de origem. A composição acima inclui também as entradas na conta de destino.</p>
  </div>
</template>
