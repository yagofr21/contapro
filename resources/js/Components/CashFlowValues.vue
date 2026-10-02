<script setup lang="ts">
import { formatMoney, formatMonth } from '@/lib/format';
defineProps<{ months: { month: string; income: string; expenses: string }[]; currency: string }>();
</script>

<template>
  <details class="mt-4 border-t border-stone-100 pt-3 dark:border-slate-800">
    <summary class="cursor-pointer py-2 text-sm font-semibold text-brand-700 dark:text-brand-300">Ver valores por mês</summary>
    <dl class="mt-2 divide-y divide-stone-100 dark:divide-slate-800">
      <div v-for="month in months" :key="month.month" class="py-3">
        <dt class="text-sm font-semibold">{{ formatMonth(month.month) }}</dt>
        <dd class="mt-2 grid gap-1 text-xs text-stone-600 dark:text-slate-300 sm:grid-cols-3">
          <span>Receitas: {{ formatMoney(month.income, currency) }}</span>
          <span>Despesas: {{ formatMoney(month.expenses, currency) }}</span>
          <span>Saldo: {{ formatMoney(String(Number(month.income) - Number(month.expenses)), currency) }}</span>
        </dd>
      </div>
    </dl>
  </details>
</template>
