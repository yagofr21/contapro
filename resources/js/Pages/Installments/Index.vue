<script setup lang="ts">
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { CreditCard, Plus, Trash2 } from '@lucide/vue';

type Installment = {
    id: number;
    account_id: number;
    account_name: string;
    category_id: number | null;
    category_name: string | null;
    category_color: string | null;
    type: string;
    amount: string;
    total_count: number;
    remaining_count: number;
    next_due_date: string;
    description: string | null;
    is_finished: boolean;
};

defineProps<{ installments: Installment[] }>();

const remove = (installment: Installment) => {
    if (confirm(`Remover a serie de parcelas ${installment.description ?? ''}?`)) {
        router.delete(route('installments.destroy', installment.id));
    }
};
</script>

<template>
  <Head title="Parcelas" />
  <AuthenticatedLayout>
    <PageHeader kicker="Agenda" title="Parcelas" subtitle="Financiamentos e compras divididas em prestacoes.">
      <template #actions>
        <Link :href="route('installments.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Nova serie</Link>
      </template>
    </PageHeader>

    <div v-if="installments.length" class="mt-8 grid gap-4 lg:grid-cols-2">
      <article v-for="installment in installments" :key="installment.id" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" :class="installment.is_finished ? 'opacity-60' : ''">
        <div class="flex items-start justify-between">
          <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300"><CreditCard :size="18" /></span>
            <div>
              <h2 class="font-semibold">{{ installment.description ?? 'Serie de parcelas' }}</h2>
              <p class="mt-0.5 text-xs text-stone-400">{{ installment.category_name ?? 'Sem categoria' }} · {{ installment.account_name }} · proxima {{ formatDate(installment.next_due_date) }}</p>
            </div>
          </div>
          <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" @click="remove(installment)"><Trash2 :size="15" /></button>
        </div>
        <div class="mt-4 flex items-end justify-between">
          <p class="text-xl font-bold" :class="installment.type === 'expense' ? 'text-rose-600' : 'text-emerald-600'">{{ installment.type === 'expense' ? '-' : '+' }}{{ formatMoney(installment.amount) }} <span class="text-xs font-normal text-stone-400">/ parcela</span></p>
          <p class="text-sm font-semibold text-stone-500">{{ installment.total_count - installment.remaining_count }} de {{ installment.total_count }} pagas</p>
        </div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"><div class="h-full rounded-full bg-brand-500" :style="{ width: `${((installment.total_count - installment.remaining_count) / installment.total_count) * 100}%` }" /></div>
        <p v-if="installment.is_finished" class="mt-2 text-right text-xs font-semibold text-emerald-600">Concluida</p>
        <p v-else class="mt-2 text-right text-xs font-semibold text-stone-400">{{ installment.remaining_count }} restante{{ installment.remaining_count > 1 ? 's' : '' }}</p>
      </article>
    </div>
    <EmptyState v-else class="mt-8" :icon="CreditCard" title="Divida seus gastos" description="Crie series de parcelas e acompanhe o saldo." />
  </AuthenticatedLayout>
</template>
