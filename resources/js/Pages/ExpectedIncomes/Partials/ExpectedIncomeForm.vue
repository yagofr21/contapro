<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { parseDecimalInput } from '@/lib/format';
import type { Account, Category } from '@/types/finance';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Save } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        accounts?: Account[];
        categories: Category[];
        onCancel?: () => void;
        embedded?: boolean;
    }>(),
    {
        accounts: () => [],
        onCancel: undefined,
        embedded: false,
    },
);

const emit = defineEmits(['cancel']);

const incomeCategories = computed((): Category[] =>
    props.categories.filter((category) => category.type === 'income'),
);
const compatibleAccounts = computed((): Account[] =>
    props.accounts.filter((account) => !account.is_archived),
);

const form = useForm({
    description: '',
    amount: '',
    account_id: String(compatibleAccounts.value[0]?.id ?? ''),
    category_id: '',
    expected_date: new Date().toISOString().slice(0, 10),
    currency: compatibleAccounts.value[0]?.currency ?? 'BRL',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        amount: parseDecimalInput(data.amount),
        currency: compatibleAccounts.value.find((account) => String(account.id) === data.account_id)?.currency ?? 'BRL',
        category_id: data.category_id ? data.category_id : null,
    }));
    form.post(route('expected-incomes.store'));
};

const cancel = () => {
    if (props.embedded) {
        props.onCancel?.();
        emit('cancel');
    }
};
</script>

<template>
  <form @submit.prevent="submit">
    <div class="px-6 py-5 sm:px-8">
      <div class="grid gap-5 sm:grid-cols-2">
        <label class="sm:col-span-2">
          <span class="mb-2 block text-sm font-semibold">Descricao</span>
          <input v-model="form.description" type="text" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Salario, adiantamento salarial, emprestimo a amigo..." />
          <InputError class="mt-2" :message="form.errors.description" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Valor previsto</span>
          <input v-model="form.amount" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" />
          <InputError class="mt-2" :message="form.errors.amount" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Previsao de entrada</span>
          <input v-model="form.expected_date" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" />
          <InputError class="mt-2" :message="form.errors.expected_date" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Conta para receber</span>
          <SelectInput v-model="form.account_id"><option value="" disabled>Selecione</option><option v-for="account in compatibleAccounts" :key="account.id" :value="String(account.id)">{{ account.name }} · {{ account.currency }}</option></SelectInput>
          <InputError class="mt-2" :message="form.errors.account_id" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Categoria <span class="font-normal text-stone-400">(opcional)</span></span>
          <SelectInput v-model="form.category_id"><option value="">Sem categoria</option><option v-for="category in incomeCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></SelectInput>
          <InputError class="mt-2" :message="form.errors.category_id" />
        </label>
        <InputError class="sm:col-span-2" :message="form.errors.currency" />
      </div>
    </div>

    <div class="px-6 pb-6 sm:px-8">
      <div class="flex flex-col-reverse gap-3 border-t border-stone-100 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
        <button v-if="embedded" type="button" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800" @click="cancel">Cancelar</button>
        <button :disabled="form.processing || compatibleAccounts.length === 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50"><Save :size="17" />Cadastrar receita</button>
      </div>
    </div>
  </form>
</template>