<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Bank } from '@/types/finance';
import { router } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import { Landmark, Pencil, Plus, Trash2 } from '@lucide/vue';

defineProps<{ banks: Bank[] }>();

const remove = (bank: Bank) => {
    if (confirm(`Remover o banco "${bank.label}"?`)) {
        router.delete(route('banks.destroy', bank.id));
    }
};

const usageCount = (bank: Bank) => bank.accounts_count ?? 0;
</script>

<template>
  <Head title="Bancos" />
  <AuthenticatedLayout>
    <PageHeader kicker="Financeiro" title="Bancos" subtitle="Catalogo de instituicoes usado na identificacao visual das contas.">
      <template #actions>
        <Link :href="route('banks.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white"><Plus :size="18" />Novo banco</Link>
      </template>
    </PageHeader>
    <div v-if="banks.length" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <article v-for="bank in banks" :key="bank.id" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start justify-between">
          <span class="grid h-11 w-11 place-items-center rounded-2xl text-sm font-extrabold text-white" :style="{ backgroundColor: bank.color }">{{ bank.initials }}</span>
          <div class="flex items-center gap-1">
            <button class="rounded-lg p-2 text-stone-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30" :disabled="usageCount(bank) > 0" :title="usageCount(bank) > 0 ? 'Banco vinculado a contas' : 'Remover banco'" @click="remove(bank)">
              <Trash2 :size="16" :class="{ 'opacity-30': usageCount(bank) > 0 }" />
            </button>
            <StatusBadge :tone="bank.is_active ? 'emerald' : 'slate'" :label="bank.is_active ? 'Ativa' : 'Inativa'" />
          </div>
        </div>
        <h2 class="mt-5 font-bold">{{ bank.label }}</h2>
        <p class="mt-1 font-mono text-xs text-stone-400">{{ bank.code }}</p>
        <p class="mt-2 text-xs text-stone-400">{{ usageCount(bank) }} conta{{ usageCount(bank) === 1 ? '' : 's' }} vinculada{{ usageCount(bank) === 1 ? '' : 's' }}</p>
        <Link :href="route('banks.edit', bank.id)" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-600"><Pencil :size="15" />Editar</Link>
      </article>
    </div>
    <EmptyState v-else class="mt-8" :icon="Landmark" title="Nenhum banco cadastrado" description="Cadastre as instituicoes para personalizar a identificacao das suas contas." />
  </AuthenticatedLayout>
</template>