<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Broker } from '@/types/investment';
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Pencil, Plus } from '@lucide/vue';
defineProps<{ brokers: Broker[] }>();
</script>

<template>
  <Head title="Corretoras" />
  <AuthenticatedLayout>
    <PageHeader kicker="Investimentos" title="Corretoras" subtitle="Instituicoes vinculadas ao historico das suas operacoes.">
      <template #actions>
        <Link :href="route('brokers.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white"><Plus :size="18" />Nova corretora</Link>
      </template>
    </PageHeader>
    <div v-if="brokers.length" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><article v-for="broker in brokers" :key="broker.id" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950/40"><Building2 :size="20" /></span><StatusBadge :tone="broker.is_active ? 'emerald' : 'slate'" :label="broker.is_active ? 'Ativa' : 'Inativa'" /></div><h2 class="mt-5 font-bold">{{ broker.name }}</h2><p class="mt-1 text-xs text-stone-400">{{ broker.transactions_count }} operacoes vinculadas</p><Link :href="route('brokers.edit', broker.id)" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-600"><Pencil :size="15" />Editar</Link></article></div>
    <EmptyState v-else class="mt-8" :icon="Building2" title="Nenhuma corretora cadastrada" description="O cadastro e opcional e ajuda a organizar o historico." />
  </AuthenticatedLayout>
</template>
