<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import type { Category, Transaction } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';

type AccountOption = { id: number; name: string; currency: string };

const props = defineProps<{
    transaction?: Transaction;
    accounts: AccountOption[];
    categories: Category[];
}>();

const form = useForm({
    type: props.transaction?.type ?? 'expense',
    account_id: props.transaction ? String(props.transaction.account_id) : '',
    destination_account_id: props.transaction?.destination_account_id ? String(props.transaction.destination_account_id) : '',
    category_id: props.transaction?.category_id ? String(props.transaction.category_id) : '',
    amount: props.transaction?.amount ?? '',
    transaction_date: props.transaction?.transaction_date ?? new Date().toISOString().slice(0, 10),
    description: props.transaction?.description ?? '',
});

const availableCategories = computed(() => props.categories.filter((category) => category.type === form.type));
const isTransfer = computed(() => form.type === 'transfer');

const submit = () => {
    form.transform((data) => ({
        ...data,
        category_id: isTransfer.value || !data.category_id ? null : data.category_id,
        destination_account_id: isTransfer.value ? data.destination_account_id : null,
    }));
    if (props.transaction) {
        form.put(route('transactions.update', props.transaction.id));
    } else {
        form.post(route('transactions.store'));
    }
};
</script>

<template>
  <form class="mt-8 max-w-3xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
    <fieldset class="grid grid-cols-3 gap-2" :disabled="Boolean(transaction)">
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
      <label v-else>
        <span class="mb-2 block text-sm font-semibold">Categoria <span class="font-normal text-stone-400">(opcional)</span></span>
        <SelectInput v-model="form.category_id"><option value="">Sem categoria</option><option v-for="category in availableCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></SelectInput>
        <InputError class="mt-2" :message="form.errors.category_id" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Valor</span>
        <input v-model="form.amount" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="0.00" />
        <InputError class="mt-2" :message="form.errors.amount" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Data</span>
        <input v-model="form.transaction_date" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" />
        <InputError class="mt-2" :message="form.errors.transaction_date" />
      </label>
      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Descricao <span class="font-normal text-stone-400">(opcional)</span></span>
        <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Detalhes deste lancamento" />
        <InputError class="mt-2" :message="form.errors.description" />
      </label>
    </div>
    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-100 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
      <Link :href="route('transactions.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link>
      <button :disabled="form.processing || accounts.length === 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50"><Save :size="17" />{{ transaction ? 'Salvar alteracoes' : 'Criar lancamento' }}</button>
    </div>
  </form>
</template>
