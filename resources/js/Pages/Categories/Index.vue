<script setup lang="ts">
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Category, Option } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryForm from './Partials/CategoryForm.vue';

const props = defineProps<{ categories: Category[]; types: Option[]; parentOptions: Category[] }>();

const expenses = computed(() => props.categories.filter((category) => category.type === 'expense'));
const incomes = computed(() => props.categories.filter((category) => category.type === 'income'));

const modalOpen = ref(false);
const editing = ref<Category | null>(null);

const openCreate = () => {
    editing.value = null;
    modalOpen.value = true;
};

const openEdit = (category: Category) => {
    editing.value = category;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editing.value = null;
};

const remove = (category: Category) => {
    if (confirm(`Remover a categoria "${category.name}"?`)) {
        router.delete(route('categories.destroy', category.id));
    }
};
</script>

<template>
  <Head title="Categorias" />
  <AuthenticatedLayout>
    <PageHeader kicker="Classificacao" title="Categorias" subtitle="Crie uma linguagem consistente para entender para onde seu dinheiro vai.">
      <template #actions>
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700" @click="openCreate"><Plus :size="18" />Nova categoria</button>
      </template>
    </PageHeader>

    <div v-if="categories.length" class="mt-6 grid gap-6 lg:grid-cols-2">
      <Card v-for="group in [{ title: 'Despesas', data: expenses }, { title: 'Receitas', data: incomes }]" :key="group.title" :title="group.title" :subtitle="`${group.data.length} categorias`">
        <div class="space-y-1 p-5">
          <article v-for="category in group.data" :key="category.id" class="group flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-stone-50 dark:hover:bg-slate-800/70">
            <span class="h-3 w-3 shrink-0 rounded-full ring-4 ring-stone-100 dark:ring-slate-800" :style="{ backgroundColor: category.color ?? '#94a3b8' }" />
            <div class="min-w-0 flex-1">
              <Link :href="route('categories.show', category.id)" class="truncate text-sm font-semibold hover:text-brand-600">{{ category.name }}</Link>
              <p v-if="category.parent_name" class="text-xs text-stone-400">Em {{ category.parent_name }}</p>
            </div>
            <div class="flex opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
              <button class="rounded-lg p-2 text-stone-400 hover:bg-white hover:text-brand-600 dark:hover:bg-slate-700" @click="openEdit(category)"><Pencil :size="15" /></button>
              <button class="rounded-lg p-2 text-stone-400 hover:bg-white hover:text-rose-600 dark:hover:bg-slate-700" @click="remove(category)"><Trash2 :size="15" /></button>
            </div>
          </article>
          <p v-if="!group.data.length" class="py-6 text-center text-sm text-stone-400">Nenhuma categoria neste grupo.</p>
        </div>
      </Card>
    </div>

    <EmptyState v-else class="mt-6" :icon="Tags" title="Organize seus lancamentos" description="Cadastre categorias de receita e despesa." />

    <Modal :show="modalOpen" max-width="md" :title="editing ? 'Editar categoria' : 'Nova categoria'" @close="closeModal">
      <CategoryForm
        v-if="modalOpen"
        :category="editing ?? undefined"
        :types="types"
        :parent-options="editing ? parentOptions.filter((p) => p.id !== editing!.id) : parentOptions"
        embedded
        @cancel="closeModal"
      />
    </Modal>
  </AuthenticatedLayout>
</template>