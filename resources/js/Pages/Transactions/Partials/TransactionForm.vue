<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { formatDecimal, formatMoney, parseDecimalInput } from '@/lib/format';
import type { Category, Transaction } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, Save } from '@lucide/vue';
import { computed, watch } from 'vue';

type AccountOption = { id: number; name: string; currency: string; type?: string };

const props = withDefaults(
    defineProps<{
        transaction?: Transaction;
        accounts: AccountOption[];
        categories: Category[];
        onCancel?: () => void;
        embedded?: boolean;
        fromDashboard?: boolean;
    }>(),
    {
        transaction: undefined,
        onCancel: undefined,
        embedded: false,
        fromDashboard: false,
    },
);

const emit = defineEmits(['cancel', 'saved', 'type-change']);

const form = useForm({
    type: props.transaction?.type ?? 'expense',
    account_id: props.transaction ? String(props.transaction.account_id) : '',
    destination_account_id: props.transaction?.destination_account_id ? String(props.transaction.destination_account_id) : '',
    category_id: props.transaction?.category_id ? String(props.transaction.category_id) : '',
    amount: formatDecimal(props.transaction?.amount ?? '', 2, 4),
    transaction_date: props.transaction?.transaction_date ?? new Date().toISOString().slice(0, 10),
    description: props.transaction?.description ?? '',
    install_in: false,
    total_count: '',
    first_installment_date: new Date().toISOString().slice(0, 10),
});

watch(
    () => form.type,
    (type) => emit('type-change', type),
    { immediate: true },
);

const isTransfer = computed(() => form.type === 'transfer');
const isExpense = computed(() => form.type === 'expense');
const disableTypeToggle = computed(() => Boolean(props.transaction));
const totalCountOptions = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 15, 18, 24, 30, 36, 48, 60, 72, 84, 96, 108, 120];

const selectedAccount = computed(() => props.accounts.find((account) => String(account.id) === form.account_id));
const isCreditCard = computed(() => selectedAccount.value?.type === 'credit_card');
const canInstall = computed(() => isExpense.value && isCreditCard.value && !props.transaction);
const installCount = computed(() => Math.max(1, Number(form.total_count) || 1));

const selectedInstallmentAmount = computed(() => {
    if (!form.install_in || !form.amount) return null;
    const totalCents = Math.round(Number(parseDecimalInput(form.amount)) * 100);
    const base = Math.floor(totalCents / installCount.value);
    return Math.max(0, base) / 100;
});

const selectedCurrency = computed(() => selectedAccount.value?.currency ?? 'BRL');

const availableCategories = computed(() => props.categories.filter((category) => category.type === form.type));

const typePalette = (type: string, selected: boolean, disabled: boolean) => {
    if (disabled) return 'cursor-not-allowed opacity-40';
    if (type === 'expense') return selected ? 'bg-rose-50 text-rose-700 ring-2 ring-rose-500 dark:bg-rose-950/50 dark:text-rose-200' : 'hover:border-rose-200 hover:text-rose-500';
    if (type === 'income') return selected ? 'bg-emerald-50 text-emerald-700 ring-2 ring-emerald-500 dark:bg-emerald-950/50 dark:text-emerald-200' : 'hover:border-emerald-200 hover:text-emerald-500';
    return selected ? 'bg-blue-50 text-blue-700 ring-2 ring-blue-500 dark:bg-blue-950/50 dark:text-blue-200' : 'hover:border-blue-200 hover:text-blue-500';
};

const typeIcon = (type: string) => (type === 'income' ? ArrowDownLeft : type === 'expense' ? ArrowUpRight : ArrowLeftRight);

const submit = () => {
    form.transform((data) => ({
        ...data,
        amount: parseDecimalInput(data.amount),
        category_id: isTransfer.value || !data.category_id ? null : data.category_id,
        destination_account_id: isTransfer.value ? data.destination_account_id : null,
        ...(data.install_in ? { install_in: 1, first_installment_date: data.transaction_date, total_count: data.total_count } : { install_in: 0 }),
        ...(props.fromDashboard ? { from_dashboard: true } : {}),
    }));
    if (props.transaction) {
        form.put(route('transactions.update', props.transaction.id));
    } else {
        form.post(route('transactions.store'), { onSuccess: () => emit('saved') });
    }
};

const cancel = () => {
    if (props.embedded) {
        props.onCancel?.();
        emit('cancel');
    }
};
</script>

<template>
  <form :class="embedded ? '' : 'mt-8 max-w-3xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8'" @submit.prevent="submit">
    <div class="px-6 py-5 sm:px-8">
      <fieldset class="grid gap-2 sm:grid-cols-3" :disabled="disableTypeToggle">
        <label v-for="option in [{ value: 'expense', label: 'Despesa' }, { value: 'income', label: 'Receita' }, { value: 'transfer', label: 'Transferencia' }]" :key="option.value" class="cursor-pointer">
          <input v-model="form.type" type="radio" :value="option.value" class="peer sr-only" />
          <span class="flex items-center justify-center gap-1.5 rounded-xl border border-stone-200 bg-white px-2 py-2.5 text-center text-xs font-semibold text-stone-500 transition dark:border-slate-700 dark:bg-slate-900" :class="typePalette(option.value, form.type === option.value, disableTypeToggle)">
            <component :is="typeIcon(option.value)" :size="14" />
            {{ option.label }}
          </span>
        </label>
      </fieldset>
      <InputError class="mt-2" :message="form.errors.type" />

      <div class="mt-5 grid gap-5 sm:grid-cols-2">
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
          <input v-model="form.amount" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" />
          <p class="mt-2 text-xs text-stone-400">Use virgula para os centavos, por exemplo: 89,90.</p>
          <InputError class="mt-2" :message="form.errors.amount" />
        </label>
        <label v-if="!form.install_in">
          <span class="mb-2 block text-sm font-semibold">Data</span>
          <input v-model="form.transaction_date" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" />
          <InputError class="mt-2" :message="form.errors.transaction_date" />
        </label>
        <label v-else>
          <span class="mb-2 block text-sm font-semibold">Vencimento da 1a parcela</span>
          <input v-model="form.transaction_date" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-950" />
          <p class="mt-2 text-xs text-stone-400">Cada parcela vence na mesma data nos meses seguintes.</p>
          <InputError class="mt-2" :message="form.errors.transaction_date" />
        </label>
        <label class="sm:col-span-2">
          <span class="mb-2 block text-sm font-semibold">Descricao <span class="font-normal text-stone-400">(opcional)</span></span>
          <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Detalhes deste lancamento" />
          <InputError class="mt-2" :message="form.errors.description" />
        </label>
      </div>

      <div v-if="canInstall" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-900/60 dark:bg-rose-950/30">
        <label class="flex cursor-pointer items-center gap-2.5">
          <input v-model="form.install_in" type="checkbox" class="h-4 w-4 rounded border-stone-300 text-rose-600 focus:ring-rose-500" />
          <span class="text-sm font-semibold text-rose-800 dark:text-rose-200">Parcelar esta despesa no cartao</span>
        </label>
        <div v-if="form.install_in" class="mt-3 grid gap-4 sm:grid-cols-2">
          <label>
            <span class="mb-2 block text-sm font-semibold">Quantidade de parcelas</span>
            <SelectInput v-model="form.total_count"><option v-for="count in totalCountOptions" :key="count" :value="String(count)">{{ count }}x</option></SelectInput>
            <InputError class="mt-2" :message="form.errors.total_count" />
          </label>
          <div class="flex items-end">
            <p v-if="selectedInstallmentAmount !== null" class="w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 text-sm font-semibold text-rose-700 dark:border-rose-900 dark:bg-slate-900">
              {{ installCount }}x de {{ formatMoney(String(selectedInstallmentAmount.toFixed(2)), selectedCurrency) }}
            </p>
            <p v-else class="w-full rounded-xl px-3 py-2.5 text-xs text-stone-400">Informe o valor total para calcular as parcelas.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="px-6 pb-6 sm:px-8">
      <div class="flex flex-col-reverse gap-3 border-t border-stone-100 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
        <button v-if="embedded" type="button" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800" @click="cancel">Cancelar</button>
        <Link v-else :href="route('transactions.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link>
        <button
          :disabled="form.processing || accounts.length === 0"
          class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg disabled:opacity-50"
          :class="form.type === 'expense' ? 'bg-rose-600 shadow-rose-600/20 hover:bg-rose-700' : form.type === 'income' ? 'bg-emerald-600 shadow-emerald-600/20 hover:bg-emerald-700' : 'bg-blue-600 shadow-blue-600/20 hover:bg-blue-700'"
        >
          <Save :size="17" />{{ transaction ? 'Salvar alteracoes' : form.install_in ? 'Criar compra parcelada' : 'Criar lancamento' }}
        </button>
      </div>
    </div>
  </form>
</template>