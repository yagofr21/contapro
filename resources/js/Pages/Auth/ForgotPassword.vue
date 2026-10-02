<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
  <GuestLayout>
    <Head title="Esqueceu a senha" />

    <header class="mb-7">
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300 ">Recuperação de acesso</p>
      <h2 class="mt-3 text-3xl font-bold tracking-tight">Redefinir senha</h2>
      <p class="mt-2 text-sm leading-6 text-stone-600 dark:text-slate-400">Informe seu e-mail e enviaremos um link seguro para criar uma nova senha.</p>
    </header>

    <div
      v-if="status"
      class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300"
    >
      {{ status }}
    </div>

    <form class="space-y-5" @submit.prevent="submit">
      <div>
        <InputLabel for="email" value="E-mail" />

        <TextInput
          id="email"
          v-model="form.email"
          type="email"
          class="mt-2 block w-full"
          required
          autofocus
          autocomplete="username"
        />

        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div class="flex items-center justify-end">
        <PrimaryButton
          class="w-full"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Enviar link de redefinição
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>
