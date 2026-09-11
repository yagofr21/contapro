<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatDecimal, formatMoney } from '@/lib/format';
import type { Holding, InvestmentTransaction, Portfolio } from '@/types/investment';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, DollarSign, Download, Pencil, Percent, Plus, RefreshCcw, Trash2 } from '@lucide/vue';

type PortfolioSummary = {
    cost: string;
    current_value: string;
    market_return: string;
    realized_profit_loss: string;
    net_income: string;
    total_return: string;
};

defineProps<{
    portfolio: Portfolio;
    summary: PortfolioSummary;
    holdings: Holding[];
    transactions: InvestmentTransaction[];
}>();

const operationMeta = {
    buy: { label: 'Compra', icon: ArrowDownLeft, tone: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40' },
    sell: { label: 'Venda', icon: ArrowUpRight, tone: 'bg-rose-50 text-rose-600 dark:bg-rose-950/40' },
    dividend: { label: 'Dividendo', icon: DollarSign, tone: 'bg-brand-50 text-brand-600 dark:bg-brand-950/40' },
    interest: { label: 'Juros', icon: Percent, tone: 'bg-amber-50 text-amber-600 dark:bg-amber-950/40' },
    split: { label: 'Desdobramento / grupamento', icon: RefreshCcw, tone: 'bg-violet-50 text-violet-600 dark:bg-violet-950/40' },
};
const removeOperation = (transaction: InvestmentTransaction) => {
    if (confirm('Remover esta operacao e recalcular todos os resultados posteriores?')) {
        router.delete(route('investment-transactions.destroy', transaction.id));
    }
};
</script>

<template>
  <Head :title="portfolio.name" />
  <AuthenticatedLayout>
    <PageHeader :kicker="`Carteira · ${portfolio.currency}`" :title="portfolio.name" subtitle="Posicao e resultados reconstruidos a partir do historico completo.">
      <template #title-suffix>
        <Link :href="route('portfolios.edit', portfolio.id)" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800"><Pencil :size="17" /></Link>
      </template>
      <template #actions>
        <a :href="route('portfolios.operations.export', portfolio.id)" class="inline-flex items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"><Download :size="17" />Exportar CSV</a>
        <Link :href="route('investment-transactions.create', portfolio.id)" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20"><Plus :size="18" />Nova operacao</Link>
      </template>
    </PageHeader>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <article v-for="item in [
        { label: 'Custo em aberto', value: summary.cost, result: false },
        { label: 'Valor atual', value: summary.current_value, result: false },
        { label: 'Resultado nao realizado', value: summary.market_return, result: true },
        { label: 'Resultado realizado', value: summary.realized_profit_loss, result: true },
        { label: 'Proventos liquidos', value: summary.net_income, result: true },
        { label: 'Retorno total', value: summary.total_return, result: true },
      ]" :key="item.label" class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
      >
        <p class="text-xs text-stone-400">{{ item.label }}</p>
        <p class="mt-2 text-xl font-bold" :class="item.result ? (Number(item.value) >= 0 ? 'text-emerald-600' : 'text-rose-600') : ''">{{ formatMoney(item.value, portfolio.currency) }}</p>
      </article>
    </section>

    <Card title="Posicoes" subtitle="Quantidade e custo medio atuais" class="mt-6">
      <div v-if="holdings.length">
        <div class="md:hidden divide-y divide-stone-100 dark:divide-slate-800">
          <article v-for="holding in holdings" :key="holding.id" class="flex items-center gap-3 px-5 py-4">
            <div class="min-w-0 flex-1">
              <p class="font-bold">{{ holding.symbol }}</p>
              <p class="mt-0.5 text-xs text-stone-400">{{ formatDecimal(holding.quantity) }} un. · Custo medio {{ formatMoney(holding.average_cost, holding.currency) }}</p>
              <p class="mt-0.5 text-xs text-stone-400">Preco atual {{ formatMoney(holding.current_price, holding.currency) }}</p>
            </div>
            <p class="whitespace-nowrap text-sm font-bold" :class="Number(holding.return) >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ formatMoney(holding.return, holding.currency) }}</p>
          </article>
        </div>
        <div class="hidden overflow-x-auto md:block"><table class="w-full min-w-[640px] text-left"><thead class="text-[10px] uppercase tracking-wider text-stone-400"><tr><th class="px-5 py-3">Ativo</th><th class="px-4 py-3 text-right">Quantidade</th><th class="px-4 py-3 text-right">Custo medio</th><th class="px-4 py-3 text-right">Preco atual</th><th class="px-5 py-3 text-right">Resultado</th></tr></thead><tbody><tr v-for="holding in holdings" :key="holding.id" class="border-t border-stone-100 dark:border-slate-800"><td class="px-5 py-4"><p class="font-bold">{{ holding.symbol }}</p><p class="truncate text-xs text-stone-400">{{ holding.name }}</p></td><td class="whitespace-nowrap px-4 py-4 text-right text-sm">{{ formatDecimal(holding.quantity) }}</td><td class="whitespace-nowrap px-4 py-4 text-right text-sm">{{ formatMoney(holding.average_cost, holding.currency) }}</td><td class="whitespace-nowrap px-4 py-4 text-right text-sm">{{ formatMoney(holding.current_price, holding.currency) }}</td><td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold" :class="Number(holding.return) >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ formatMoney(holding.return, holding.currency) }}</td></tr></tbody></table></div>
      </div>
      <EmptyState v-else title="Nenhuma posicao aberta" description="Registre uma compra." />
    </Card>

    <Card title="Historico de operacoes" class="mt-6">
      <div v-if="transactions.length">
        <article v-for="transaction in transactions" :key="transaction.id" class="flex flex-col gap-3 border-b border-stone-100 px-5 py-4 last:border-0 dark:border-slate-800 sm:flex-row sm:items-center">
          <div class="flex min-w-0 flex-1 items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="operationMeta[transaction.type].tone"><component :is="operationMeta[transaction.type].icon" :size="17" /></span><div class="min-w-0"><p class="truncate text-sm font-bold">{{ transaction.asset_symbol }} · {{ operationMeta[transaction.type].label }}</p><p class="mt-0.5 text-xs text-stone-400">{{ formatDate(transaction.date ?? '') }}<template v-if="transaction.broker_name"> · {{ transaction.broker_name }}</template></p><p v-if="transaction.type === 'buy' || transaction.type === 'sell'" class="mt-1 text-xs text-stone-500">{{ formatDecimal(transaction.quantity) }} unidades · {{ formatMoney(transaction.unit_price, portfolio.currency) }} por unidade<template v-if="Number(transaction.fees) > 0"> · taxas {{ formatMoney(transaction.fees, portfolio.currency) }}</template></p><p v-else-if="transaction.type === 'split'" class="mt-1 text-xs text-stone-500">Proporcao {{ formatDecimal(transaction.split_from ?? '0') }} para {{ formatDecimal(transaction.split_to ?? '0') }} · custo total preservado</p><p v-else class="mt-1 text-xs text-stone-500">Bruto {{ formatMoney(transaction.gross_amount ?? '0', portfolio.currency) }}</p></div></div>
          <div v-if="transaction.type !== 'split'" class="sm:min-w-40 sm:text-right"><p class="text-xs text-stone-400">{{ transaction.type === 'sell' ? 'Liquido da venda' : transaction.type === 'buy' ? 'Total investido' : 'Liquido recebido' }}</p><p class="text-sm font-semibold">{{ formatMoney(transaction.total_amount ?? '0', portfolio.currency) }}</p><p v-if="transaction.type === 'sell'" class="mt-1 text-xs font-semibold" :class="Number(transaction.realized_profit_loss) >= 0 ? 'text-emerald-600' : 'text-rose-600'">Realizado: {{ formatMoney(transaction.realized_profit_loss ?? '0', portfolio.currency) }}</p></div>
          <div v-else class="text-xs font-semibold text-violet-600 sm:min-w-40 sm:text-right">Evento de quantidade</div>
          <div class="flex justify-end gap-1"><Link :href="route('investment-transactions.edit', transaction.id)" class="rounded-lg p-2 text-stone-400 hover:text-brand-600"><Pencil :size="15" /></Link><button class="rounded-lg p-2 text-stone-400 hover:text-rose-600" @click="removeOperation(transaction)"><Trash2 :size="15" /></button></div>
        </article>
      </div>
      <EmptyState v-else title="Nenhuma operacao registrada" />
    </Card>
  </AuthenticatedLayout>
</template>
