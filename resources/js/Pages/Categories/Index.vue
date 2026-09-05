<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Category } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ categories: Category[] }>();
const expenses = computed(() => props.categories.filter((category) => category.type === 'expense'));
const incomes = computed(() => props.categories.filter((category) => category.type === 'income'));

const remove = (category: Category) => {
    if (confirm(`Remover a categoria "${category.name}"?`)) {
        router.delete(route('categories.destroy', category.id));
    }
};
</script>

<template>
  <Head title="Categorias" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Classificacao</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Categorias</h1>
        <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Crie uma linguagem consistente para entender para onde seu dinheiro vai.</p>
      </div>
      <Link :href="route('categories.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Nova categoria</Link>
    </section>

    <div v-if="categories.length" class="mt-8 grid gap-6 lg:grid-cols-2">
      <section v-for="group in [{ title: 'Despesas', data: expenses, icon: ArrowUpRight, tone: 'rose' }, { title: 'Receitas', data: incomes, icon: ArrowDownLeft, tone: 'emerald' }]" :key="group.title" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="mb-4 flex items-center justify-between border-b border-stone-100 pb-4 dark:border-slate-800">
          <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl" :class="group.tone === 'rose' ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/50' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50'"><component :is="group.icon" :size="18" /></span>
            <div><h2 class="font-semibold">{{ group.title }}</h2><p class="text-xs text-stone-400">{{ group.data.length }} categorias</p></div>
          </div>
        </header>
        <div class="space-y-1">
          <article v-for="category in group.data" :key="category.id" class="group flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-stone-50 dark:hover:bg-slate-800/70">
            <span class="h-3 w-3 shrink-0 rounded-full ring-4 ring-stone-100 dark:ring-slate-800" :style="{ backgroundColor: category.color ?? '#94a3b8' }" />
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold">{{ category.name }}</p>
              <p v-if="category.parent_name" class="text-xs text-stone-400">Em {{ category.parent_name }}</p>
            </div>
            <div class="flex opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
              <Link :href="route('categories.edit', category.id)" class="rounded-lg p-2 text-stone-400 hover:bg-white hover:text-brand-600 dark:hover:bg-slate-700"><Pencil :size="15" /></Link>
              <button class="rounded-lg p-2 text-stone-400 hover:bg-white hover:text-rose-600 dark:hover:bg-slate-700" @click="remove(category)"><Trash2 :size="15" /></button>
            </div>
          </article>
          <p v-if="!group.data.length" class="py-6 text-center text-sm text-stone-400">Nenhuma categoria neste grupo.</p>
        </div>
      </section>
    </div>

    <div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
      <Tags :size="36" class="mx-auto text-stone-300 dark:text-slate-600" />
      <h2 class="mt-4 text-lg font-semibold">Organize seus lancamentos</h2>
      <p class="mt-1 text-sm text-stone-500">Cadastre categorias de receita e despesa.</p>
    </div>
  </AuthenticatedLayout>
</template>
