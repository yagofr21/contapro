<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import type { Option } from '@/types/finance';
import type { Portfolio } from '@/types/investment';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';

const props = defineProps<{ portfolio?: Portfolio; currencies: Option[] }>();
const form = useForm({ name: props.portfolio?.name ?? '', currency: props.portfolio?.currency ?? 'BRL' });
const submit = () => props.portfolio ? form.put(route('portfolios.update', props.portfolio.id)) : form.post(route('portfolios.store'));
</script>

<template>
  <form class="mt-8 max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit"><div class="grid gap-6 sm:grid-cols-2"><label><span class="mb-2 block text-sm font-semibold">Nome</span><input v-model="form.name" autofocus class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Longo prazo" /><InputError class="mt-2" :message="form.errors.name" /></label><label><span class="mb-2 block text-sm font-semibold">Moeda base</span><SelectInput v-model="form.currency"><option v-for="currency in currencies" :key="currency.value" :value="currency.value">{{ currency.label }}</option></SelectInput><InputError class="mt-2" :message="form.errors.currency" /></label></div><div class="mt-8 flex justify-end gap-3 border-t border-stone-100 pt-6 dark:border-slate-800"><Link :href="portfolio ? route('portfolios.show', portfolio.id) : route('portfolios.index')" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-stone-500">Cancelar</Link><button :disabled="form.processing" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white"><Save :size="17" />Salvar</button></div></form>
</template>
