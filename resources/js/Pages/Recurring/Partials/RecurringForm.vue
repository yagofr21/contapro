<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDecimal, parseDecimalInput } from '@/lib/format';
import type { Category, Option } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';

type AccountOption = { id: number; name: string; currency: string };
type Schedule = {
    id: number;
    type: string;
    account_id: number;
    destination_account_id: number | null;
    category_id: number | null;
    amount: string;
    frequency: string;
    starts_on: string;
    ends_on: string | null;
    description: string | null;
    is_active: boolean;
};

const props = defineProps<{
    schedule?: Schedule;
    accounts: AccountOption[];
    categories: Category[];
    frequencies?: Option[];
    types?: Option[];
}>();

const form = useForm({
    type: props.schedule?.type ?? 'expense',
    account_id: props.schedule ? String(props.schedule.account_id) : '',
    destination_account_id: props.schedule?.destination_account_id ? String(props.schedule.destination_account_id) : '',
    category_id: props.schedule?.category_id ? String(props.schedule.category_id) : '',
    amount: formatDecimal(props.schedule?.amount ?? '', 2, 4),
    frequency: props.schedule?.frequency ?? 'monthly',
    starts_on: props.schedule?.starts_on ?? new Date().toISOString().slice(0, 8) + '01',
    ends_on: props.schedule?.ends_on ?? '',
    description: props.schedule?.description ?? '',
    is_active: props.schedule?.is_active ?? true,
});

const availableCategories = computed(() => props.categories.filter((category) => category.type === form.type));
const isTransfer = computed(() => form.type === 'transfer');

const submit = () => {
    form.transform((data) => ({
        ...data,
        amount: parseDecimalInput(data.amount),
        category_id: isTransfer.value || !data.category_id ? null : data.category_id,
        destination_account_id: isTransfer.value ? data.destination_account_id : null,
        ends_on: data.ends_on && !isTransfer.value ? data.ends_on : null,
        is_active: props.schedule ? data.is_active : true,
    }));
    if (props.schedule) {
        form.put(route('recurring.update', props.schedule.id));
    } else {
        form.post(route('recurring.store'));
    }
};
</script>

<template>
  <form class="mt-8 max-w-3xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
    <fieldset class="grid grid-cols-3 gap-2">
      <label v-for="option in [{ value: 'expense', label: 'Despesa' }, { value: 'income', label: 'Receita' }, { value: 'transfer', label: 'Transferencia' }]" :key="option.value" class="cursor-pointer">
        <input v-model="form.type" type="radio" :value="option.value" class="peer sr-only" />
        <span class="block rounded-xl border border-stone-200 px-2 py-2.5 text-center text-xs font-semibold text-stone-500 transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700 dark:border-slate-700 dark:peer-checked:bg-brand-950/50 dark:peer-checked:text-brand-200">{{ option.label }}</span>
      </label>
    </fieldset>
    <InputError class="mt-2" :message="form.errors.type" />

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
      <label>
        <span class="mb-2 block text-sm font-semibold">{{ isTransfer ? 'Conta de origem' : 'Conta' }}</span>
        <SelectInput v-model="form.account_id"><option value="" disabled>Selecione</option><option v-for="account in accounts" :key="account.id" :value="String(account.id)">{{ account.name }} · {{ account.currency }}</option></SelectInput>
        <InputError class="mt-2" :message="form.errors.account_id" />
      </label>
      <label v-if="isTransfer">
        <span class="mb-2 block text-sm font-semibold">Conta de destino</span>
        <SelectInput v-model="form.destination_account_id"><option value="" disabled>Selecione</option><option v-for="account in accounts" :key="account.id" :value="String(account.id)" :disabled="String(account.id) === form.account_id">{{ account.name }} · {{ account.currency }}</option></SelectInput>
        <InputError class="mt-2" :message="form.errors.destination_account_id" />
      </label>
      <label v-if="!isTransfer">
        <span class="mb-2 block text-sm font-semibold">Categoria <span class="font-normal text-stone-400">(opcional)</span></span>
        <SelectInput v-model="form.category_id"><option value="">Sem categoria</option><option v-for="category in availableCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></SelectInput>
        <InputError class="mt-2" :message="form.errors.category_id" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Valor por ocorrencia</span>
        <input v-model="form.amount" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" />
        <InputError class="mt-2" :message="form.errors.amount" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Frequencia</span>
        <SelectInput v-model="form.frequency"><option v-for="freq in frequencies" :key="freq.value" :value="freq.value">{{ freq.label }}</option></SelectInput>
        <InputError class="mt-2" :message="form.errors.frequency" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Inicio</span>
        <input v-model="form.starts_on" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" />
        <InputError class="mt-2" :message="form.errors.starts_on" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Fim <span class="font-normal text-stone-400">(opcional)</span></span>
        <input v-model="form.ends_on" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" />
        <InputError class="mt-2" :message="form.errors.ends_on" />
      </label>
      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Descricao <span class="font-normal text-stone-400">(opcional)</span></span>
        <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Assinatura mensal" />
        <InputError class="mt-2" :message="form.errors.description" />
      </label>
    </div>

    <label v-if="schedule" class="mt-6 flex items-center gap-3">
      <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500" />
      <span class="text-sm font-semibold">Recorrencia ativa</span>
    </label>

    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-100 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
      <Link :href="route('recurring.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link>
      <button :disabled="form.processing || accounts.length === 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50"><Save :size="17" />{{ schedule ? 'Salvar alteracoes' : 'Criar recorrencia' }}</button>
    </div>
  </form>
</template>
