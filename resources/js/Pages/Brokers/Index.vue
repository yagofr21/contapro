<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Broker } from '@/types/investment';
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Pencil, Plus } from '@lucide/vue';
defineProps<{ brokers: Broker[] }>();
</script>

<template>
  <Head title="Corretoras" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Investimentos</p><h1 class="mt-2 text-3xl font-bold">Corretoras</h1><p class="mt-2 text-sm text-stone-500">Instituicoes vinculadas ao historico das suas operacoes.</p></div><Link :href="route('brokers.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white"><Plus :size="18" />Nova corretora</Link></section>
    <div v-if="brokers.length" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><article v-for="broker in brokers" :key="broker.id" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950/40"><Building2 :size="20" /></span><span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="broker.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40' : 'bg-stone-100 text-stone-500 dark:bg-slate-800'">{{ broker.is_active ? 'Ativa' : 'Inativa' }}</span></div><h2 class="mt-5 font-bold">{{ broker.name }}</h2><p class="mt-1 text-xs text-stone-400">{{ broker.transactions_count }} operacoes vinculadas</p><Link :href="route('brokers.edit', broker.id)" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-600"><Pencil :size="15" />Editar</Link></article></div>
    <div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900"><Building2 :size="36" class="mx-auto text-stone-300" /><h2 class="mt-4 text-lg font-semibold">Nenhuma corretora cadastrada</h2><p class="mt-1 text-sm text-stone-500">O cadastro e opcional e ajuda a organizar o historico.</p></div>
  </AuthenticatedLayout>
</template>
