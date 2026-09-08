<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import type { Category, Option } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        category?: Category;
        types: Option[];
        parentOptions: Category[];
        onCancel?: () => void;
        embedded?: boolean;
    }>(),
    {
        category: undefined,
        onCancel: undefined,
        embedded: false,
    },
);

const emit = defineEmits(['cancel']);

const form = useForm({
    name: props.category?.name ?? '',
    type: props.category?.type ?? 'expense',
    parent_id: props.category?.parent_id ? String(props.category.parent_id) : '',
    color: props.category?.color ?? '#338dff',
});

const availableParents = computed(() => props.parentOptions.filter((cat) => cat.type === form.type));

const submit = () => {
    form.transform((data) => ({ ...data, parent_id: data.parent_id || null }));
    if (props.category) {
        form.put(route('categories.update', props.category.id));
    } else {
        form.post(route('categories.store'));
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
          <span class="mb-2 block text-sm font-semibold">Nome</span>
          <input v-model="form.name" autofocus class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Alimentacao" />
          <InputError class="mt-2" :message="form.errors.name" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Tipo</span>
          <SelectInput v-model="form.type">
            <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
          </SelectInput>
          <InputError class="mt-2" :message="form.errors.type" />
        </label>
        <label>
          <span class="mb-2 block text-sm font-semibold">Cor</span>
          <span class="flex h-[42px] items-center gap-3 rounded-xl border border-stone-200 px-3 dark:border-slate-700">
            <input v-model="form.color" type="color" class="h-7 w-8 cursor-pointer border-0 bg-transparent p-0" />
            <span class="text-sm text-stone-500">{{ form.color }}</span>
          </span>
          <InputError class="mt-2" :message="form.errors.color" />
        </label>
        <label class="sm:col-span-2">
          <span class="mb-2 block text-sm font-semibold">Categoria pai <span class="font-normal text-stone-400">(opcional)</span></span>
          <SelectInput v-model="form.parent_id">
            <option value="">Sem categoria pai</option>
            <option v-for="parent in availableParents" :key="parent.id" :value="String(parent.id)">{{ parent.name }}</option>
          </SelectInput>
          <InputError class="mt-2" :message="form.errors.parent_id" />
        </label>
      </div>
    </div>
    <div class="px-6 pb-6 sm:px-7">
      <div class="flex flex-col-reverse gap-3 border-t border-stone-100 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
        <button v-if="embedded" type="button" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800" @click="cancel">Cancelar</button>
        <Link v-else :href="route('categories.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:hover:bg-slate-800">Cancelar</Link>
        <button :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 disabled:opacity-50"><Save :size="17" />{{ category ? 'Salvar alteracoes' : 'Criar categoria' }}</button>
      </div>
    </div>
  </form>
</template>