<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { bankMeta, type BankMeta, type BankOption } from '@/lib/banks';
import { formatDecimal, parseDecimalInput } from '@/lib/format';
import type { Account, Option } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        account?: Account;
        types: Option[];
        currencies: Option[];
        banks?: BankOption[];
        onCancel?: () => void;
        embedded?: boolean;
    }>(),
    {
        account: undefined,
        onCancel: undefined,
        banks: () => [],
        embedded: false,
    },
);

const emit = defineEmits(['cancel']);

const form = useForm({
    name: props.account?.name ?? '',
    bank: props.account?.bank ?? '',
    color: props.account?.color ?? '',
    type: props.account?.type ?? 'checking',
    currency: props.account?.currency ?? 'BRL',
    initial_balance: formatDecimal(props.account?.initial_balance ?? '0', 2, 4),
    credit_limit: formatDecimal(props.account?.credit_limit ?? '', 2, 4),
    credit_closing_day: props.account?.credit_closing_day ? String(props.account.credit_closing_day) : '',
    credit_due_day: props.account?.credit_due_day ? String(props.account.credit_due_day) : '',
    is_archived: props.account?.is_archived ?? false,
});

const isCreditCard = computed(() => form.type === 'credit_card');
const closingDayOptions = Array.from({ length: 28 }, (_, index) => index + 1);
const selectedBank = computed<BankMeta | null>(() => {
    const found = props.banks.find((bank) => bank.value === form.bank);
    return found ? bankMeta(found.value) ?? { ...found } : bankMeta(form.bank);
});
const colorOptions = ['#1b6ef5', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#14b8a6', '#f97316', '#64748b'];

const selectBank = (value: string) => {
    form.bank = value;
    const meta = props.banks.find((bank) => bank.value === value) ?? bankMeta(value);
    if (meta) {
        form.color = meta.color;
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        bank: data.bank || null,
        color: data.color || null,
        initial_balance: parseDecimalInput(data.initial_balance),
        ...(isCreditCard.value
            ? { credit_limit: data.credit_limit ? parseDecimalInput(data.credit_limit) : null }
            : { credit_limit: null, credit_closing_day: null, credit_due_day: null }),
    }));

    if (props.account) {
        form.put(route('accounts.update', props.account.id));
    } else {
        form.post(route('accounts.store'));
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
  <form :class="embedded ? '' : 'mt-8 max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8'" @submit.prevent="submit">
    <div class="px-6 py-5 sm:px-7">
      <div class="grid gap-5 sm:grid-cols-2">
        <label class="sm:col-span-2">
          <span class="mb-2 block text-sm font-semibold">Nome da conta</span>
          <input v-model="form.name" autofocus class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Conta principal" />
          <InputError class="mt-2" :message="form.errors.name" />
        </label>
        <div class="sm:col-span-2">
          <span class="mb-2 block text-sm font-semibold">Identificacao visual</span>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="bank in banks"
              :key="bank.value"
              type="button"
              class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition"
              :class="form.bank === bank.value ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-500/30 dark:bg-brand-950/50 dark:text-brand-200' : 'border-stone-200 bg-white text-stone-600 hover:border-stone-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-600'"
              @click="selectBank(bank.value)"
            >
              <span class="grid h-6 w-6 place-items-center rounded-lg text-[10px] font-extrabold text-white" :style="{ backgroundColor: bank.color }">
                {{ bank.initials }}
              </span>
              {{ bank.label }}
            </button>
            <button
              v-if="form.bank"
              type="button"
              class="inline-flex items-center rounded-xl border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-500 transition hover:border-stone-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400"
              @click="form.bank = ''"
            >
              Limpar
            </button>
          </div>
          <div class="mt-3 flex items-center gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-sm font-extrabold" :style="{ backgroundColor: form.color || selectedBank?.color || '#1b6ef5', color: selectedBank?.text ?? '#fff' }">
              {{ selectedBank?.initials ?? '#' }}
            </span>
            <input v-model="form.color" type="color" class="h-10 w-14 cursor-pointer rounded-lg border border-stone-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-950" aria-label="Cor da conta" />
            <input v-model="form.color" class="w-28 rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-xs font-mono uppercase shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" aria-label="Cor em hexadecimal" />
            <div class="ml-auto flex flex-wrap gap-1.5">
              <button v-for="c in colorOptions" :key="c" type="button" class="h-5 w-5 rounded-full transition hover:scale-110" :style="{ backgroundColor: c }" :aria-label="`Cor ${c}`" :title="c" @click="form.color = c" />
            </div>
          </div>
          <InputError class="mt-2" :message="form.errors.bank" />
          <InputError class="mt-2" :message="form.errors.color" />
        </div>
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
        <template v-if="isCreditCard">
          <label class="sm:col-span-2">
            <span class="mb-2 block text-sm font-semibold">Limite do cartao</span>
            <input v-model="form.credit_limit" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="0,00" />
            <p class="mt-2 text-xs text-stone-400">O limite disponivel sera calculado pela fatura atual mais as parcelas futuras.</p>
            <InputError class="mt-2" :message="form.errors.credit_limit" />
          </label>
          <label>
            <span class="mb-2 block text-sm font-semibold">Dia do fechamento</span>
            <SelectInput v-model="form.credit_closing_day"><option value="">Selecione</option><option v-for="day in closingDayOptions" :key="day" :value="String(day)">Dia {{ day }}</option></SelectInput>
            <InputError class="mt-2" :message="form.errors.credit_closing_day" />
          </label>
          <label>
            <span class="mb-2 block text-sm font-semibold">Dia do vencimento</span>
            <SelectInput v-model="form.credit_due_day"><option value="">Selecione</option><option v-for="day in closingDayOptions" :key="day" :value="String(day)">Dia {{ day }}</option></SelectInput>
            <InputError class="mt-2" :message="form.errors.credit_due_day" />
          </label>
        </template>
        <label v-if="account" class="flex items-center gap-3 sm:col-span-2">
          <input v-model="form.is_archived" type="checkbox" class="rounded border-stone-300 text-brand-600 focus:ring-brand-500" />
          <span class="text-sm font-medium">Conta arquivada</span>
        </label>
      </div>
    </div>
    <div class="px-6 pb-6 sm:px-7">
      <div class="flex flex-col-reverse gap-3 border-t border-stone-100 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
        <button v-if="embedded" type="button" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800" @click="cancel">Cancelar</button>
        <Link v-else :href="route('accounts.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link>
        <button :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50">
          <Save :size="17" /> {{ account ? 'Salvar alteracoes' : 'Criar conta' }}
        </button>
      </div>
    </div>
  </form>
</template>
