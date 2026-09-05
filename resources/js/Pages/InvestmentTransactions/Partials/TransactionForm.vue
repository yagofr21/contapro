<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import type { AssetOption, InvestmentTransaction, Portfolio } from '@/types/investment';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ portfolio: Portfolio; assets: AssetOption[]; transaction?: InvestmentTransaction }>();
const today = new Date().toISOString().slice(0, 10);
const form = useForm({
    asset_id: String(props.transaction?.asset_id ?? props.assets[0]?.id ?? ''),
    type: props.transaction?.type ?? 'buy',
    quantity: props.transaction?.quantity ?? '',
    unit_price: props.transaction?.unit_price ?? '',
    fees: props.transaction?.fees ?? '0',
    gross_amount: props.transaction?.gross_amount ?? '',
    net_amount: props.transaction?.net_amount ?? '',
    transaction_date: props.transaction?.transaction_date ?? today,
    note: props.transaction?.note ?? '',
});
const isIncome = computed(() => form.type === 'dividend' || form.type === 'interest');
const submit = () => props.transaction
    ? form.put(route('investment-transactions.update', props.transaction.id))
    : form.post(route('investment-transactions.store', props.portfolio.id));
</script>

<template>
  <form class="mt-8 max-w-3xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
    <div class="grid gap-6 sm:grid-cols-2">
      <label class="sm:col-span-2"><span class="mb-2 block text-sm font-semibold">Ativo</span><SelectInput v-model="form.asset_id"><option disabled value="">Selecione um ativo</option><option v-for="asset in assets" :key="asset.id" :value="String(asset.id)">{{ asset.symbol }} · {{ asset.name }} ({{ asset.market }})</option></SelectInput><InputError class="mt-2" :message="form.errors.asset_id" /><p v-if="!assets.length" class="mt-2 text-xs text-amber-600">Cadastre um ativo no catalogo antes de registrar a operacao.</p></label>
      <label><span class="mb-2 block text-sm font-semibold">Operacao</span><SelectInput v-model="form.type"><option value="buy">Compra</option><option value="sell">Venda</option><option value="dividend">Dividendo</option><option value="interest">Juros</option></SelectInput><InputError class="mt-2" :message="form.errors.type" /></label>
      <label><span class="mb-2 block text-sm font-semibold">Data</span><input v-model="form.transaction_date" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError class="mt-2" :message="form.errors.transaction_date" /></label>
      <template v-if="!isIncome">
        <label><span class="mb-2 block text-sm font-semibold">Quantidade</span><input v-model="form.quantity" type="number" min="0" step="0.00000001" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="0,00000000" /><InputError class="mt-2" :message="form.errors.quantity" /></label>
        <label><span class="mb-2 block text-sm font-semibold">Preco unitario</span><input v-model="form.unit_price" type="number" min="0" step="0.00000001" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" /><InputError class="mt-2" :message="form.errors.unit_price" /></label>
        <label><span class="mb-2 block text-sm font-semibold">Taxas</span><input v-model="form.fees" type="number" min="0" step="0.0001" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError class="mt-2" :message="form.errors.fees" /></label>
      </template>
      <template v-else>
        <label><span class="mb-2 block text-sm font-semibold">Valor bruto</span><input v-model="form.gross_amount" type="number" min="0" step="0.0001" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" /><InputError class="mt-2" :message="form.errors.gross_amount" /></label>
        <label><span class="mb-2 block text-sm font-semibold">Valor liquido</span><input v-model="form.net_amount" type="number" min="0" step="0.0001" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" /><InputError class="mt-2" :message="form.errors.net_amount" /></label>
      </template>
      <label class="sm:col-span-2"><span class="mb-2 block text-sm font-semibold">Observacao</span><textarea v-model="form.note" rows="3" class="w-full resize-none rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="Opcional" /><InputError class="mt-2" :message="form.errors.note" /></label>
    </div>
    <div class="mt-8 flex justify-end gap-3 border-t border-stone-100 pt-6 dark:border-slate-800"><Link :href="route('portfolios.show', portfolio.id)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-stone-500">Cancelar</Link><button :disabled="form.processing || !assets.length" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-40"><Save :size="17" />Salvar operacao</button></div>
  </form>
</template>
