<script setup lang="ts">
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import type { Installment } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarRange, Plus, Trash2 } from '@lucide/vue';

defineProps<{ installments: Installment[] }>();

const remove = (installment: Installment) => {
    if (confirm(`Remover a serie de parcelas ${installment.description ?? ''}?`)) {
        router.delete(route('installments.destroy', installment.id));
    }
};

const progress = (installment: Installment) => Math.round((installment.paid_count / installment.total_count) * 100);
const shortDate = (value: string) => value.slice(0, 10);
</script>

<template>
  <Head title="Parcelas" />
  <AuthenticatedLayout>
    <PageHeader kicker="Agenda" title="Parcelas" subtitle="Compras parceladas no cartao e prestacoes em andamento.">
      <template #actions>
        <Link :href="route('installments.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Nova serie</Link>
      </template>
    </PageHeader>

    <div v-if="installments.length" class="mt-8 grid gap-5 lg:grid-cols-2">
      <article v-for="installment in installments" :key="installment.id" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" :class="installment.is_finished ? 'opacity-70' : ''">
        <div class="flex items-start justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300"><CalendarRange :size="18" /></span>
            <div class="min-w-0">
              <h2 class="truncate font-semibold">{{ installment.description ?? 'Serie de parcelas' }}</h2>
              <p class="mt-0.5 text-xs text-stone-400">{{ installment.category_name ?? 'Sem categoria' }} · {{ installment.account_name }}</p>
            </div>
          </div>
          <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" :title="`Remover ${installment.description ?? 'serie'}`" @click="remove(installment)"><Trash2 :size="15" /></button>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs text-stone-400">Total da compra</p>
            <p class="text-lg font-bold" :class="installment.type === 'expense' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">{{ formatMoney(installment.total_amount) }}</p>
          </div>
          <div class="text-right">
            <span v-if="installment.is_finished" class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">Concluida</span>
            <span v-else class="inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300">Parcela {{ installment.current_parcela }}/{{ installment.total_count }}</span>
            <p v-if="!installment.is_finished" class="mt-1.5 text-xs text-stone-400">{{ installment.remaining_count }} restante{{ installment.remaining_count > 1 ? 's' : '' }} de {{ formatMoney(installment.amount) }}</p>
          </div>
        </div>

        <div class="mt-3">
          <div class="mb-1 flex justify-between text-[11px] text-stone-400"><span>{{ progress(installment) }}% pago</span><span>{{ installment.paid_count }}/{{ installment.total_count }} parcelas</span></div>
          <div class="h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"><div class="h-full rounded-full bg-brand-500" :style="{ width: progress(installment) + '%' }" /></div>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
          <div class="rounded-xl bg-stone-50 p-2.5 dark:bg-slate-950"><p class="text-[11px] text-stone-400">Pago</p><p class="font-bold text-emerald-600 dark:text-emerald-400">{{ formatMoney(installment.total_paid) }}</p></div>
          <div class="rounded-xl bg-stone-50 p-2.5 dark:bg-slate-950"><p class="text-[11px] text-stone-400">Em aberto</p><p class="font-bold text-rose-600 dark:text-rose-400">{{ formatMoney(installment.total_remaining) }}</p></div>
        </div>

        <div v-if="installment.schedule.some((row) => row.status === 'pendente')" class="mt-3">
          <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-stone-400">Proximas parcelas</p>
          <ul class="space-y-1 overflow-x-auto">
            <li v-for="row in installment.schedule.filter((item) => item.status === 'pendente')" :key="row.number" class="flex items-center gap-2 rounded-lg px-1.5 py-1 text-xs">
              <span class="w-7 shrink-0 text-right font-bold text-stone-500">{{ row.number }}</span>
              <span class="h-1 w-1 shrink-0 rounded-full bg-brand-300" />
              <span class="shrink-0 text-stone-500">{{ formatDate(shortDate(row.due_date)) }}</span>
              <span class="ml-auto shrink-0 font-semibold text-stone-700 dark:text-slate-200">{{ formatMoney(row.amount) }}</span>
            </li>
          </ul>
        </div>
        <p v-else-if="installment.is_finished" class="mt-3 text-xs text-stone-400">Todas as {{ installment.total_count }} parcelas foram pagas.</p>
      </article>
    </div>
    <EmptyState v-else class="mt-8" :icon="CalendarRange" title="Divida seus gastos" description="Lance uma compra parcelada no cartao ou crie uma serie de parcelas." />
  </AuthenticatedLayout>
</template>