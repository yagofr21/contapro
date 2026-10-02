<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
  <GuestLayout>
    <Head title="Confirmar senha" />

    <header class="mb-7">
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-600 dark:text-brand-400">Área protegida</p>
      <h2 class="mt-3 text-3xl font-bold tracking-tight">Confirme sua senha</h2>
      <p class="mt-2 text-sm leading-6 text-stone-500 dark:text-slate-400">Precisamos validar sua identidade antes de continuar.</p>
    </header>

    <form class="space-y-5" @submit.prevent="submit">
      <div>
        <InputLabel for="password" value="Senha" />
        <TextInput
          id="password"
          v-model="form.password"
          type="password"
          class="mt-2 block w-full"
          required
          autocomplete="current-password"
          autofocus
        />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="flex justify-end">
        <PrimaryButton
          class="w-full"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Confirmar
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>
