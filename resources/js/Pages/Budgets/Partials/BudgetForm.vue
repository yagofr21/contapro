<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import type { Option } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';

type CategoryOption = { id: number; name: string; color: string | null };
type Budget = { id: number; category_id: number; limit_amount: string; period: string; starts_on: string; ends_on: string | null };

const props = defineProps<{ budget?: Budget; categories: CategoryOption[]; periods: Option[] }>();
const today = new Date().toISOString().slice(0, 10);
const form = useForm({
    category_id: props.budget ? String(props.budget.category_id) : '',
    limit_amount: props.budget?.limit_amount ?? '',
    period: props.budget?.period ?? 'monthly',
    starts_on: props.budget?.starts_on ?? today.slice(0, 8) + '01',
    ends_on: props.budget?.ends_on ?? '',
});
const isCustom = computed(() => form.period === 'custom');
const submit = () => {
    form.transform((data) => ({ ...data, ends_on: isCustom.value ? data.ends_on : null }));
    if (props.budget) form.put(route('budgets.update', props.budget.id));
    else form.post(route('budgets.store'));
};
</script>

<template>
  <form class="mt-8 max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
    <div class="grid gap-6 sm:grid-cols-2">
      <label class="sm:col-span-2"><span class="mb-2 block text-sm font-semibold">Categoria de despesa</span><SelectInput v-model="form.category_id"><option value="" disabled>Selecione</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></SelectInput><InputError class="mt-2" :message="form.errors.category_id" /></label>
      <label><span class="mb-2 block text-sm font-semibold">Limite</span><input v-model="form.limit_amount" inputmode="decimal" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="1000.00" /><InputError class="mt-2" :message="form.errors.limit_amount" /></label>
      <label><span class="mb-2 block text-sm font-semibold">Periodo</span><SelectInput v-model="form.period"><option v-for="period in periods" :key="period.value" :value="period.value">{{ period.label }}</option></SelectInput><InputError class="mt-2" :message="form.errors.period" /></label>
      <label><span class="mb-2 block text-sm font-semibold">Inicio</span><input v-model="form.starts_on" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError class="mt-2" :message="form.errors.starts_on" /></label>
      <label v-if="isCustom"><span class="mb-2 block text-sm font-semibold">Fim</span><input v-model="form.ends_on" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError class="mt-2" :message="form.errors.ends_on" /></label>
    </div>
    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-100 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end"><Link :href="route('budgets.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link><button :disabled="form.processing || categories.length === 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50"><Save :size="17" />{{ budget ? 'Salvar alteracoes' : 'Criar orcamento' }}</button></div>
  </form>
</template>
