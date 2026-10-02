<script setup lang="ts">
import type { CreditCardInfo } from '@/types/finance';
import { formatMoney } from '@/lib/format';
defineProps<{ card: CreditCardInfo; currency: string }>();
</script>

<template>
  <section aria-label="Limite do cartão">
    <dl v-if="card.has_limit" class="grid gap-3 text-xs sm:grid-cols-3">
      <div><dt class="text-stone-600 dark:text-slate-400">Limite total</dt><dd class="financial-value mt-1 font-semibold">{{ formatMoney(card.credit_limit ?? '0', currency) }}</dd></div>
      <div><dt class="text-stone-600 dark:text-slate-400">Limite utilizado</dt><dd class="financial-value mt-1 font-semibold">{{ formatMoney(card.utilized ?? '0', currency) }}</dd></div>
      <div><dt class="text-stone-600 dark:text-slate-400">Limite disponível</dt><dd class="financial-value mt-1 font-semibold" :class="card.over_limit ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300'">{{ formatMoney(card.available ?? '0', currency) }}</dd></div>
    </dl>
    <template v-if="card.has_limit">
      <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-stone-200 dark:bg-slate-700" aria-hidden="true"><div class="h-full rounded-full" :class="card.over_limit ? 'bg-rose-600' : 'bg-brand-600'" :style="{ width: Math.max(0, Math.min(card.utilization ?? 0, 100)) + '%' }" /></div>
      <p class="mt-2 text-xs text-stone-600 dark:text-slate-300">{{ card.utilization }}% do limite utilizado<span v-if="card.over_limit" class="font-semibold text-rose-700 dark:text-rose-300"> · Limite excedido</span></p>
    </template>
    <p v-else class="text-xs leading-5 text-stone-600 dark:text-slate-300">Limite não informado. Cadastre o limite na edição do cartão para acompanhar o valor disponível.</p>
  </section>
</template>
