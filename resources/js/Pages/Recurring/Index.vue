<script setup lang="ts">
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
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
    <PageHeader kicker="Agenda" title="Recorrencias" subtitle="Transacoes que se repetem sozinhas no dia marcado.">
      <template #actions>
        <Link :href="route('recurring.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Nova recorrencia</Link>
      </template>
    </PageHeader>

    <Card v-if="schedules.length" class="mt-8">
      <ul class="divide-y divide-stone-100 dark:divide-slate-800 md:hidden">
        <li v-for="schedule in schedules" :key="schedule.id" class="px-5 py-4" :class="schedule.is_active ? '' : 'opacity-60'">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate font-semibold">{{ schedule.description ?? 'Sem descricao' }}</p>
              <p class="mt-0.5 truncate text-xs text-stone-400">{{ schedule.category_name ?? 'Sem categoria' }} · {{ schedule.account_name }}</p>
            </div>
            <StatusBadge class="shrink-0" :tone="schedule.type === 'income' ? 'emerald' : schedule.type === 'expense' ? 'rose' : 'sky'" :label="schedule.type === 'income' ? 'Receita' : schedule.type === 'expense' ? 'Despesa' : 'Transferencia'" />
          </div>
          <div class="mt-3 flex items-end justify-between gap-3">
            <div class="min-w-0">
              <p class="font-semibold" :class="schedule.type === 'expense' ? 'text-rose-600' : 'text-emerald-600'">{{ schedule.type === 'expense' ? '-' : '+' }}{{ formatMoney(schedule.amount) }}</p>
              <p class="mt-0.5 text-xs text-stone-400">{{ schedule.frequency_label }} · proxima {{ formatDate(schedule.next_run_date) }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
              <Link :href="route('recurring.edit', schedule.id)" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800"><Pencil :size="15" /></Link>
              <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" aria-label="Remover recorrencia" @click="remove(schedule)"><Trash2 :size="15" /></button>
            </div>
          </div>
        </li>
      </ul>
      <div class="hidden overflow-x-auto md:block">
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
            <tr v-for="schedule in schedules" :key="schedule.id" :class="schedule.is_active ? '' : 'opacity-60'">
              <td class="px-5 py-4">
                <p class="truncate font-semibold">{{ schedule.description ?? 'Sem descricao' }}</p>
                <p class="truncate text-xs text-stone-400">{{ schedule.category_name ?? 'Sem categoria' }} · {{ schedule.account_name }}</p>
              </td>
              <td class="px-5 py-4"><StatusBadge :tone="schedule.type === 'income' ? 'emerald' : schedule.type === 'expense' ? 'rose' : 'sky'" :label="schedule.type === 'income' ? 'Receita' : schedule.type === 'expense' ? 'Despesa' : 'Transferencia'" /></td>
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
    </Card>
    <EmptyState v-else class="mt-8" :icon="Repeat" title="Automatize seus lancamentos" description="Crie recorrencias para contas, salarios e assinaturas." />
  </AuthenticatedLayout>
</template>
