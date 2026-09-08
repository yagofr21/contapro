<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Repeat, Trash2 } from '@lucide/vue';

type Schedule = {
    id: number;
    account_id: number;
    account_name: string;
    category_id: number | null;
    category_name: string | null;
    category_color: string | null;
    type: string;
    amount: string;
    frequency: string;
    frequency_label: string;
    starts_on: string;
    ends_on: string | null;
    next_run_date: string;
    description: string | null;
    is_active: boolean;
};

defineProps<{ schedules: Schedule[] }>();

const remove = (schedule: Schedule) => {
    if (confirm(`Remover a recorrencia ${schedule.description ?? ''}?`)) {
        router.delete(route('recurring.destroy', schedule.id));
    }
};
</script>

<template>
  <Head title="Recorrencias" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Agenda</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Recorrencias</h1><p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Transacoes que se repetem sozinhas no dia marcado.</p></div>
      <Link :href="route('recurring.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Nova recorrencia</Link>
    </section>

    <div v-if="schedules.length" class="mt-8 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-stone-50 text-left text-xs uppercase tracking-wider text-stone-400 dark:bg-slate-950/60">
            <tr>
              <th class="px-5 py-3">Descricao</th>
              <th class="px-5 py-3">Tipo</th>
              <th class="px-5 py-3">Valor</th>
              <th class="px-5 py-3">Frequencia</th>
              <th class="px-5 py-3">Proxima data</th>
              <th class="px-5 py-3">Conta</th>
              <th class="px-5 py-3 text-right">Acoes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100 dark:divide-slate-800">
            <tr v-for="schedule in schedules" :key="schedule.id" class="opacity-60" :class="schedule.is_active ? '' : 'opacity-60'">
              <td class="px-5 py-4">
                <p class="font-semibold">{{ schedule.description ?? 'Sem descricao' }}</p>
                <p class="text-xs text-stone-400">{{ schedule.category_name ?? 'Sem categoria' }} · {{ schedule.account_name }}</p>
              </td>
              <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="schedule.type === 'income' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : schedule.type === 'expense' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300'">{{ schedule.type === 'income' ? 'Receita' : schedule.type === 'expense' ? 'Despesa' : 'Transferencia' }}</span></td>
              <td class="px-5 py-4 font-semibold" :class="schedule.type === 'expense' ? 'text-rose-600' : 'text-emerald-600'">{{ schedule.type === 'expense' ? '-' : '+' }}{{ formatMoney(schedule.amount) }}</td>
              <td class="px-5 py-4">{{ schedule.frequency_label }}</td>
              <td class="px-5 py-4">{{ formatDate(schedule.next_run_date) }}</td>
              <td class="px-5 py-4">{{ schedule.account_name }}</td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-1">
                  <Link :href="route('recurring.edit', schedule.id)" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800"><Pencil :size="15" /></Link>
                  <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" @click="remove(schedule)"><Trash2 :size="15" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900"><Repeat :size="36" class="mx-auto text-stone-300 dark:text-slate-600" /><h2 class="mt-4 text-lg font-semibold">Automatize seus lancamentos</h2><p class="mt-1 text-sm text-stone-500">Crie recorrencias para contas, salarios e assinaturas.</p></div>
  </AuthenticatedLayout>
</template>
