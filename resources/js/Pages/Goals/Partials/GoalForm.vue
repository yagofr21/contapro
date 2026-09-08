<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDecimal, parseDecimalInput } from '@/lib/format';
import type { Option } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';

type AccountOption = { id: number; name: string; currency: string };
type Goal = { id: number; name: string; description: string | null; target_amount: string; currency: string; account_id: number | null; target_date: string };

const props = defineProps<{ goal?: Goal; accounts: AccountOption[]; currencies: Option[] }>();
const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    name: props.goal?.name ?? '',
    description: props.goal?.description ?? '',
    target_amount: formatDecimal(props.goal?.target_amount ?? '', 2, 4),
    currency: props.goal?.currency ?? 'BRL',
    account_id: props.goal?.account_id ? String(props.goal.account_id) : '',
    target_date: props.goal?.target_date ?? today,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        target_amount: parseDecimalInput(data.target_amount),
        account_id: data.account_id ? Number(data.account_id) : null,
    }));

    if (props.goal) form.put(route('goals.update', props.goal.id));
    else form.post(route('goals.store'));
};
</script>

<template>
  <form class="mt-8 max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
    <div class="grid gap-6 sm:grid-cols-2">
      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Nome da meta</span>
        <input v-model="form.name" autofocus class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Reserva de emergencia" />
        <InputError class="mt-2" :message="form.errors.name" />
      </label>
      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Descricao</span>
        <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Motivo e contexto (opcional)"></textarea>
        <InputError class="mt-2" :message="form.errors.description" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Valor da meta</span>
        <input v-model="form.target_amount" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="10.000,00" />
        <p class="mt-2 text-xs text-stone-400">Use virgula para os centavos.</p>
        <InputError class="mt-2" :message="form.errors.target_amount" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Moeda</span>
        <SelectInput v-model="form.currency">
          <option v-for="currency in currencies" :key="currency.value" :value="currency.value">{{ currency.label }}</option>
        </SelectInput>
        <InputError class="mt-2" :message="form.errors.currency" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Conta vinculada</span>
        <SelectInput v-model="form.account_id">
          <option value="">Todas as contas na moeda</option>
          <option v-for="account in accounts" :key="account.id" :value="String(account.id)">{{ account.name }} ({{ account.currency }})</option>
        </SelectInput>
        <p class="mt-2 text-xs text-stone-400">Sem vínculo, o progresso soma o saldo de todas as contas na moeda da meta.</p>
        <InputError class="mt-2" :message="form.errors.account_id" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Prazo</span>
        <input v-model="form.target_date" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" />
        <InputError class="mt-2" :message="form.errors.target_date" />
      </label>
    </div>
    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-100 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
      <Link :href="route('goals.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link>
      <button :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50">
        <Save :size="17" /> {{ goal ? 'Salvar alteracoes' : 'Criar meta' }}
      </button>
    </div>
  </form>
</template>