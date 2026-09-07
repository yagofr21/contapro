<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDecimal, parseDecimalInput } from '@/lib/format';
import type { Category, Option } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';

type AccountOption = { id: number; name: string; currency: string };

const props = defineProps<{
    accounts: AccountOption[];
    categories: Category[];
    types?: Option[];
}>();

const form = useForm({
    type: 'expense',
    account_id: '',
    category_id: '',
    amount: '',
    total_count: '10',
    starts_on: new Date().toISOString().slice(0, 8) + '01',
    description: '',
});

const availableCategories = computed(() => props.categories.filter((category) => category.type === form.type));

const submit = () => {
    form.transform((data) => ({
        ...data,
        amount: parseDecimalInput(data.amount),
        category_id: !data.category_id ? null : data.category_id,
    }));
    form.post(route('installments.store'));
};
</script>

<template>
  <form class="mt-8 max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
    <fieldset class="grid grid-cols-2 gap-2">
      <label v-for="option in [{ value: 'expense', label: 'Despesa' }, { value: 'income', label: 'Receita' }]" :key="option.value" class="cursor-pointer">
        <input v-model="form.type" type="radio" :value="option.value" class="peer sr-only" />
        <span class="block rounded-xl border border-stone-200 px-2 py-2.5 text-center text-xs font-semibold text-stone-500 transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700 dark:border-slate-700 dark:peer-checked:bg-brand-950/50 dark:peer-checked:text-brand-200">{{ option.label }}</span>
      </label>
    </fieldset>
    <InputError class="mt-2" :message="form.errors.type" />

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
      <label>
        <span class="mb-2 block text-sm font-semibold">Conta</span>
        <SelectInput v-model="form.account_id"><option value="" disabled>Selecione</option><option v-for="account in accounts" :key="account.id" :value="String(account.id)">{{ account.name }} · {{ account.currency }}</option></SelectInput>
        <InputError class="mt-2" :message="form.errors.account_id" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Categoria <span class="font-normal text-stone-400">(opcional)</span></span>
        <SelectInput v-model="form.category_id"><option value="">Sem categoria</option><option v-for="category in availableCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></SelectInput>
        <InputError class="mt-2" :message="form.errors.category_id" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Valor por parcela</span>
        <input v-model="form.amount" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" />
        <InputError class="mt-2" :message="form.errors.amount" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Quantidade de parcelas</span>
        <input v-model="form.total_count" type="number" min="2" max="120" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" />
        <InputError class="mt-2" :message="form.errors.total_count" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Primeira parcela</span>
        <input v-model="form.starts_on" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" />
        <InputError class="mt-2" :message="form.errors.starts_on" />
      </label>
      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Descricao <span class="font-normal text-stone-400">(opcional)</span></span>
        <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Celular em 10x" />
        <InputError class="mt-2" :message="form.errors.description" />
      </label>
    </div>
    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-100 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
      <Link :href="route('installments.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link>
      <button :disabled="form.processing || accounts.length === 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50"><Save :size="17" />Criar serie de parcelas</button>
    </div>
  </form>
</template>
