<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDecimal, formatMoney, parseDecimalInput } from '@/lib/format';
import type { AssetOption, BrokerOption, InvestmentTransaction, Portfolio } from '@/types/investment';
import { Link, useForm } from '@inertiajs/vue3';
import { Calculator, Save } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    portfolio: Portfolio;
    assets: AssetOption[];
    brokers: BrokerOption[];
    transaction?: InvestmentTransaction;
    selectedAssetId?: number | null;
}>();

const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
const form = useForm({
    asset_id: String(props.transaction?.asset_id ?? props.selectedAssetId ?? props.assets[0]?.id ?? ''),
    broker_id: String(props.transaction?.broker_id ?? ''),
    type: props.transaction?.type ?? 'buy',
    quantity: formatDecimal(props.transaction?.quantity ?? '', 0, 8),
    unit_price: formatDecimal(props.transaction?.unit_price ?? '', 2, 8),
    fees: formatDecimal(props.transaction?.fees ?? '0', 2, 4),
    split_from: formatDecimal(props.transaction?.split_from ?? '', 0, 8),
    split_to: formatDecimal(props.transaction?.split_to ?? '', 0, 8),
    gross_amount: formatDecimal(props.transaction?.gross_amount ?? '', 2, 4),
    net_amount: formatDecimal(props.transaction?.net_amount ?? '', 2, 4),
    transaction_date: props.transaction?.transaction_date ?? today,
    note: props.transaction?.note ?? '',
});

const isIncome = computed(() => form.type === 'dividend' || form.type === 'interest');
const isSplit = computed(() => form.type === 'split');
const isTrade = computed(() => form.type === 'buy' || form.type === 'sell');
const selectedAsset = computed(() => props.assets.find((asset) => String(asset.id) === form.asset_id));
const availableQuantity = computed(() => formatDecimal(selectedAsset.value?.available_quantity ?? '0', 0, 8));
const operationTotal = computed(() => {
    const quantity = Number(parseDecimalInput(form.quantity)) || 0;
    const unitPrice = Number(parseDecimalInput(form.unit_price)) || 0;
    const fees = Number(parseDecimalInput(form.fees)) || 0;
    const gross = quantity * unitPrice;

    return form.type === 'sell' ? gross - fees : gross + fees;
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        quantity: parseDecimalInput(data.quantity),
        unit_price: parseDecimalInput(data.unit_price),
        fees: parseDecimalInput(data.fees),
        split_from: parseDecimalInput(data.split_from),
        split_to: parseDecimalInput(data.split_to),
        gross_amount: parseDecimalInput(data.gross_amount),
        net_amount: parseDecimalInput(data.net_amount),
    }));

    if (props.transaction) form.put(route('investment-transactions.update', props.transaction.id));
    else form.post(route('investment-transactions.store', props.portfolio.id));
};
</script>

<template>
  <form class="mt-8 max-w-4xl overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900" @submit.prevent="submit">
    <div class="border-b border-stone-100 bg-stone-50/70 px-6 py-5 dark:border-slate-800 dark:bg-slate-950/40 sm:px-8">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Lancamento na carteira</p>
      <h2 class="mt-1 text-lg font-bold">Dados da operacao</h2>
      <p class="mt-1 text-sm text-stone-500">Informe os valores como no Brasil: <strong>1.234,56</strong>.</p>
    </div>

    <div class="grid gap-6 p-6 sm:grid-cols-2 sm:p-8">
      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Ativo</span>
        <SelectInput v-model="form.asset_id">
          <option disabled value="">Selecione um ativo</option>
          <option v-for="asset in assets" :key="asset.id" :value="String(asset.id)">{{ asset.symbol }} · {{ asset.name }} ({{ asset.market }})</option>
        </SelectInput>
        <InputError class="mt-2" :message="form.errors.asset_id" />
        <p v-if="!assets.length" class="mt-2 text-xs text-amber-600">Cadastre um ativo no catalogo antes de registrar a operacao.</p>
      </label>

      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Corretora <span class="font-normal text-stone-400">(opcional)</span></span>
        <SelectInput v-model="form.broker_id">
          <option value="">Sem corretora informada</option>
          <option v-for="broker in brokers" :key="broker.id" :value="String(broker.id)">{{ broker.name }}{{ broker.is_active ? '' : ' (inativa)' }}</option>
        </SelectInput>
        <p v-if="!brokers.length" class="mt-2 text-xs text-stone-400">Cadastre corretoras pelo menu para identifica-las nas operacoes.</p>
        <InputError class="mt-2" :message="form.errors.broker_id" />
      </label>

      <label>
        <span class="mb-2 block text-sm font-semibold">Tipo de operacao</span>
        <SelectInput v-model="form.type">
          <option value="buy">Compra</option>
          <option value="sell">Venda</option>
          <option value="dividend">Dividendo</option>
          <option value="interest">Juros</option>
          <option value="split">Desdobramento / grupamento</option>
        </SelectInput>
        <InputError class="mt-2" :message="form.errors.type" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Data da operacao</span>
        <input v-model="form.transaction_date" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" />
        <InputError class="mt-2" :message="form.errors.transaction_date" />
      </label>

      <template v-if="isTrade">
        <label>
          <span class="mb-2 flex items-center justify-between gap-2 text-sm font-semibold">
            Quantidade
            <span v-if="form.type === 'sell'" class="text-xs font-normal text-stone-400">Disponivel: {{ availableQuantity }}</span>
          </span>
          <input v-model="form.quantity" type="text" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="100 ou 0,12345678" />
          <InputError class="mt-2" :message="form.errors.quantity" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Preco unitario</span>
          <input v-model="form.unit_price" type="text" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="35,90" />
          <InputError class="mt-2" :message="form.errors.unit_price" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Taxas e corretagem</span>
          <input v-model="form.fees" type="text" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" />
          <InputError class="mt-2" :message="form.errors.fees" />
        </label>
        <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4 dark:border-brand-900 dark:bg-brand-950/30">
          <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-600"><Calculator :size="15" />{{ form.type === 'sell' ? 'Valor liquido estimado' : 'Total da compra' }}</div>
          <p class="mt-2 text-2xl font-bold">{{ formatMoney(String(operationTotal), portfolio.currency) }}</p>
          <p class="mt-1 text-xs text-stone-500">Quantidade x preco {{ form.type === 'sell' ? '-' : '+' }} taxas</p>
        </div>
      </template>

      <template v-else-if="isSplit">
        <div class="sm:col-span-2 rounded-2xl border border-violet-100 bg-violet-50/70 px-4 py-3 text-sm text-violet-800 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-200">Informe a proporcao do evento. Exemplo: em um desdobramento de 1 para 5, cada unidade antiga passa a representar 5 novas. O custo total e preservado.</div>
        <label>
          <span class="mb-2 block text-sm font-semibold">Quantidade antiga na proporcao</span>
          <input v-model="form.split_from" type="text" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="1" />
          <InputError class="mt-2" :message="form.errors.split_from" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Nova quantidade na proporcao</span>
          <input v-model="form.split_to" type="text" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="5" />
          <InputError class="mt-2" :message="form.errors.split_to" />
        </label>
        <p class="sm:col-span-2 text-xs text-stone-500">Posicao atual do ativo: <strong>{{ availableQuantity }}</strong> unidades.</p>
      </template>

      <template v-else-if="isIncome">
        <div class="sm:col-span-2 rounded-2xl border border-brand-100 bg-brand-50/60 px-4 py-3 text-sm text-brand-800 dark:border-brand-900 dark:bg-brand-950/30 dark:text-brand-200">Proventos nao alteram a quantidade nem o custo medio. Informe os valores totais recebidos.</div>
        <label>
          <span class="mb-2 block text-sm font-semibold">Valor bruto</span>
          <input v-model="form.gross_amount" type="text" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="100,00" />
          <InputError class="mt-2" :message="form.errors.gross_amount" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Valor liquido</span>
          <input v-model="form.net_amount" type="text" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="85,00" />
          <InputError class="mt-2" :message="form.errors.net_amount" />
        </label>
      </template>

      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Observacao <span class="font-normal text-stone-400">(opcional)</span></span>
        <textarea v-model="form.note" rows="3" class="w-full resize-none rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="Estrategia ou detalhes da operacao" />
        <InputError class="mt-2" :message="form.errors.note" />
      </label>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-stone-100 px-6 py-5 dark:border-slate-800 sm:flex-row sm:justify-end sm:px-8">
      <Link :href="route('portfolios.show', portfolio.id)" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500">Cancelar</Link>
      <button :disabled="form.processing || !assets.length" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-40"><Save :size="17" />Salvar operacao</button>
    </div>
  </form>
</template>
