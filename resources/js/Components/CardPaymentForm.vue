<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { formatDate, formatDecimal, formatMoney, parseDecimalInput } from '@/lib/format';
import type { Account } from '@/types/finance';
import { useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed } from 'vue';

const props = defineProps<{ account: Account; paymentAccounts: { id: number; name: string; currency: string }[]; initialCycle?: string }>();
const emit = defineEmits<{ close: [] }>();
const invoices = computed(() => (props.account.credit_card?.invoices ?? []).filter((invoice) => invoice.status !== 'paid' && Number(invoice.amount) > 0));
const initial = invoices.value.find((invoice) => invoice.cycle === (props.initialCycle ?? props.account.credit_card?.suggested_invoice_cycle)) ?? invoices.value[0];
const form = useForm({ account_id: '', invoice_cycle: initial?.cycle ?? '', amount: formatDecimal(initial?.amount ?? '', 2, 2), transaction_date: dayjs().format('YYYY-MM-DD'), description: '' });
const selected = computed(() => invoices.value.find((invoice) => invoice.cycle === form.invoice_cycle));
const labels = { open: 'Em aberto', closed: 'Fechada a vencer', overdue: 'Atrasada', paid: 'Paga' };
const updateAmount = () => {
    form.amount = formatDecimal(selected.value?.amount ?? '', 2, 2);
    form.clearErrors();
};
const submit = () => {
    form.transform((data) => ({ ...data, amount: parseDecimalInput(data.amount), description: data.description || `Pagamento ${props.account.name} · fatura fechada em ${formatDate(data.invoice_cycle)}` }))
        .post(route('accounts.pay-card', props.account.id), { onSuccess: () => emit('close') });
};
</script>
<template>
  <form class="p-6 sm:p-7" @submit.prevent="submit">
    <div v-if="!invoices.length" class="rounded-2xl bg-emerald-50 p-5 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">Este cartão está em dia. Não há faturas com saldo para pagar.</div>
    <div v-else class="space-y-5">
      <label class="block"><span class="mb-2 block text-sm font-semibold">Qual fatura você quer pagar?</span>
        <select v-model="form.invoice_cycle" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" @change="updateAmount">
          <option v-for="invoice in invoices" :key="invoice.cycle" :value="invoice.cycle">{{ formatDate(invoice.cycle) }} · {{ labels[invoice.status] }} · {{ formatMoney(invoice.amount, account.currency) }}</option>
        </select><InputError class="mt-2" :message="form.errors.invoice_cycle" />
      </label>
      <div v-if="selected" class="rounded-2xl border border-teal-200 bg-teal-50 p-4 dark:border-teal-900 dark:bg-teal-950/40">
        <div class="flex items-center justify-between gap-3"><p class="text-xs font-semibold text-teal-900 dark:text-teal-200">{{ labels[selected.status] }}</p><p class="text-xl font-bold text-teal-950 dark:text-teal-100">{{ formatMoney(selected.amount, account.currency) }}</p></div>
        <p class="mt-2 text-xs text-teal-900 dark:text-teal-200">Período {{ formatDate(selected.start) }} a {{ formatDate(selected.cycle) }}</p>
        <p class="mt-1 text-xs font-semibold text-teal-900 dark:text-teal-200">Vencimento {{ formatDate(selected.due_date) }}</p>
      </div>
      <label class="block"><span class="mb-2 block text-sm font-semibold">Pagar com</span><select v-model="form.account_id" required class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="" disabled>Selecione a conta de origem</option><option v-for="source in paymentAccounts" :key="source.id" :value="String(source.id)">{{ source.name }} · {{ source.currency }}</option></select><InputError class="mt-2" :message="form.errors.account_id" /></label>
      <p v-if="!paymentAccounts.length" class="text-sm text-amber-800 dark:text-amber-200">Cadastre uma conta de origem na mesma moeda para registrar o pagamento.</p>
      <div class="grid gap-4 sm:grid-cols-2">
        <label><span class="mb-2 block text-sm font-semibold">Valor pago</span><input v-model="form.amount" required inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError class="mt-2" :message="form.errors.amount" /></label>
        <label><span class="mb-2 block text-sm font-semibold">Data do pagamento</span><input v-model="form.transaction_date" required type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError class="mt-2" :message="form.errors.transaction_date" /></label>
      </div>
      <p class="text-xs text-stone-600 dark:text-slate-400">Você pode pagar o total ou uma parte. O valor será aplicado à fatura selecionada.</p>
      <label class="block"><span class="mb-2 block text-sm font-semibold">Observação <span class="font-normal text-stone-500">(opcional)</span></span><textarea v-model="form.description" rows="2" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError class="mt-2" :message="form.errors.description" /></label>
    </div>
    <div class="mt-6 flex justify-end gap-3 border-t border-stone-100 pt-5 dark:border-slate-800"><button type="button" class="rounded-xl px-4 py-2.5 text-sm font-semibold" @click="emit('close')">Cancelar</button><button v-if="invoices.length" :disabled="form.processing || !paymentAccounts.length" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{{ form.processing ? 'Registrando…' : 'Confirmar pagamento' }}</button></div>
  </form>
</template>
