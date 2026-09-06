<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Option } from '@/types/finance';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Info, Save } from '@lucide/vue';

defineProps<{ types: Option[]; markets: Option[]; currencies: Option[] }>();
const form = useForm({ symbol: '', name: '', type: 'stock', market: 'B3', currency: 'BRL' });
</script>

<template>
  <Head title="Cadastrar ativo" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Catalogo</p>
    <h1 class="mt-2 text-3xl font-bold">Cadastrar ativo</h1>
    <div class="mt-5 flex max-w-2xl gap-3 rounded-2xl border border-brand-100 bg-brand-50/60 p-4 text-sm text-brand-900 dark:border-brand-900 dark:bg-brand-950/30 dark:text-brand-100"><Info :size="20" class="mt-0.5 shrink-0" /><p>Este cadastro apenas disponibiliza o ativo. Depois, use <strong>Registrar compra</strong> no catalogo para informar carteira, quantidade, preco e taxas.</p></div>
    <form class="mt-5 max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900" @submit.prevent="form.post(route('assets.store'))">
      <div class="grid gap-5 sm:grid-cols-2">
        <label><span class="mb-2 block text-sm font-semibold">Codigo</span><input v-model="form.symbol" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 uppercase dark:border-slate-700 dark:bg-slate-950" placeholder="PETR4" /><InputError class="mt-2" :message="form.errors.symbol" /></label>
        <label><span class="mb-2 block text-sm font-semibold">Nome</span><input v-model="form.name" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950" placeholder="Petrobras PN" /><InputError class="mt-2" :message="form.errors.name" /></label>
        <label><span class="mb-2 block text-sm font-semibold">Tipo</span><SelectInput v-model="form.type"><option v-for="item in types" :key="item.value" :value="item.value">{{ item.label }}</option></SelectInput><InputError class="mt-2" :message="form.errors.type" /></label>
        <label><span class="mb-2 block text-sm font-semibold">Mercado</span><SelectInput v-model="form.market"><option v-for="item in markets" :key="item.value" :value="item.value">{{ item.label }}</option></SelectInput><InputError class="mt-2" :message="form.errors.market" /></label>
        <label><span class="mb-2 block text-sm font-semibold">Moeda</span><SelectInput v-model="form.currency"><option v-for="item in currencies" :key="item.value" :value="item.value">{{ item.label }}</option></SelectInput><InputError class="mt-2" :message="form.errors.currency" /></label>
      </div>
      <div class="mt-7 flex justify-end gap-3 border-t border-stone-100 pt-5 dark:border-slate-800"><Link :href="route('assets.index')" class="px-4 py-2.5 text-sm font-semibold text-stone-500">Cancelar</Link><button :disabled="form.processing" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"><Save :size="17" />Cadastrar ativo</button></div>
    </form>
  </AuthenticatedLayout>
</template>
