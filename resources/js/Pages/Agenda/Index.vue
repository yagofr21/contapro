<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, CalendarDays, History, Plus, Repeat } from '@lucide/vue';
import { computed } from 'vue';

type AgendaEvent = {
    date: string;
    kind: 'schedule' | 'installment';
    type: string;
    amount: string;
    account_id: number;
    account_name: string;
    category_name: string | null;
    description: string | null;
    frequency: string;
};

type Projection = {
    id: number;
    name: string;
    balance: string;
    projected_balance: string;
};

const props = defineProps<{ events: AgendaEvent[]; projection: Projection[]; horizon_days: number }>();

const groups = computed(() => {
    const map = new Map<string, AgendaEvent[]>();
    for (const event of props.events) {
        if (!map.has(event.date)) map.set(event.date, []);
        map.get(event.date)!.push(event);
    }
    return Array.from(map.entries());
});
</script>

<template>
  <Head title="Agenda e projecao" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Agenda</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Agenda e projecao</h1><p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Proximos {{ horizon_days }} dias de lancamentos previsiveis.</p></div>
      <div class="flex gap-2">
        <Link :href="route('recurring.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Repeat :size="18" />Recorrencia</Link>
        <Link :href="route('installments.create')" class="inline-flex items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-600 hover:bg-stone-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"><CalendarDays :size="18" />Parcelas</Link>
      </div>
    </section>

    <section v-if="projection.length" class="mt-8 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
      <article v-for="account in projection" :key="account.id" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm font-semibold text-stone-500">{{ account.name }}</p>
        <p class="mt-3 text-2xl font-bold">{{ formatMoney(account.projected_balance) }}</p>
        <p class="mt-1 text-xs text-stone-400">saldo atual {{ formatMoney(account.balance) }} ao fim dos {{ horizon_days }} dias</p>
      </article>
    </section>

    <section v-if="groups.length" class="mt-8">
      <h2 class="text-lg font-bold">Lancamentos agendados</h2>
      <div v-for="[date, dayEvents] in groups" :key="date" class="mt-4 rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center gap-3 border-b border-stone-100 px-5 py-3 dark:border-slate-800"><CalendarClock :size="16" class="text-stone-400" /><p class="text-sm font-semibold">{{ formatDate(date) }}</p></div>
        <ul class="divide-y divide-stone-100 dark:divide-slate-800">
          <li v-for="(event, index) in dayEvents" :key="index" class="flex items-center justify-between gap-3 px-5 py-3">
            <div class="flex items-center gap-3">
              <span class="grid h-8 w-8 place-items-center rounded-xl" :class="event.kind === 'schedule' ? 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300' : 'bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-300'"><component :is="event.kind === 'schedule' ? Repeat : CalendarDays" :size="15" /></span>
              <div>
                <p class="text-sm font-semibold">{{ event.description ?? 'Sem descricao' }}</p>
                <p class="text-xs text-stone-400">{{ event.kind === 'schedule' ? 'Recorrencia' : 'Parcela' }} · {{ event.account_name }}{{ event.category_name ? ' · ' + event.category_name : '' }}</p>
              </div>
            </div>
            <p class="text-sm font-bold" :class="event.type === 'expense' ? 'text-rose-600' : 'text-emerald-600'">{{ event.type === 'expense' ? '-' : '+' }}{{ formatMoney(event.amount) }}</p>
          </li>
        </ul>
      </div>
    </section>
    <div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900"><History :size="36" class="mx-auto text-stone-300 dark:text-slate-600" /><h2 class="mt-4 text-lg font-semibold">Nada agendado</h2><p class="mt-1 text-sm text-stone-500">Crie recorrencias ou parcelas para prever seus lancamentos.</p><Link :href="route('recurring.create')" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Criar recorrencia</Link></div>
  </AuthenticatedLayout>
</template>
