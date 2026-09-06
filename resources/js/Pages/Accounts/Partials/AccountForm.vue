<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDecimal, parseDecimalInput } from '@/lib/format';
import type { Account, Option } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';

const props = defineProps<{
    account?: Account;
    types: Option[];
    currencies: Option[];
}>();

const form = useForm({
    name: props.account?.name ?? '',
    type: props.account?.type ?? 'checking',
    currency: props.account?.currency ?? 'BRL',
    initial_balance: formatDecimal(props.account?.initial_balance ?? '0', 2, 4),
    is_archived: props.account?.is_archived ?? false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        initial_balance: parseDecimalInput(data.initial_balance),
    }));

    if (props.account) {
        form.put(route('accounts.update', props.account.id));
    } else {
        form.post(route('accounts.store'));
    }
};
</script>

<template>
  <form class="mt-8 max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
    <div class="grid gap-6 sm:grid-cols-2">
      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Nome da conta</span>
        <input v-model="form.name" autofocus class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Conta principal" />
        <InputError class="mt-2" :message="form.errors.name" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Tipo</span>
        <SelectInput v-model="form.type">
          <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
        </SelectInput>
        <InputError class="mt-2" :message="form.errors.type" />
      </label>
      <label>
        <span class="mb-2 block text-sm font-semibold">Moeda</span>
        <SelectInput v-model="form.currency">
          <option v-for="currency in currencies" :key="currency.value" :value="currency.value">{{ currency.label }}</option>
        </SelectInput>
        <InputError class="mt-2" :message="form.errors.currency" />
      </label>
      <label class="sm:col-span-2">
        <span class="mb-2 block text-sm font-semibold">Saldo inicial</span>
        <input v-model="form.initial_balance" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" />
        <p class="mt-2 text-xs text-stone-400">Use virgula para centavos, por exemplo: 1.250,50. O saldo atual inclui todos os lancamentos.</p>
        <InputError class="mt-2" :message="form.errors.initial_balance" />
      </label>
      <label v-if="account" class="flex items-center gap-3 sm:col-span-2">
        <input v-model="form.is_archived" type="checkbox" class="rounded border-stone-300 text-brand-600 focus:ring-brand-500" />
        <span class="text-sm font-medium">Conta arquivada</span>
      </label>
    </div>
    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-100 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
      <Link :href="route('accounts.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link>
      <button :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50">
        <Save :size="17" /> {{ account ? 'Salvar alteracoes' : 'Criar conta' }}
      </button>
    </div>
  </form>
</template>
