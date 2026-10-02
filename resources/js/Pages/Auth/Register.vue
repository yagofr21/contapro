<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
  <GuestLayout>
    <Head title="Criar cadastro" />

    <header class="mb-7">
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-600 dark:text-brand-400">Comece com segurança</p>
      <h2 class="mt-3 text-3xl font-bold tracking-tight">Crie sua conta</h2>
      <p class="mt-2 text-sm leading-6 text-stone-500 dark:text-slate-400">Organize contas, cartões e investimentos em um só lugar.</p>
    </header>

    <form class="space-y-5" @submit.prevent="submit">
      <div>
        <InputLabel for="name" value="Nome" />

        <TextInput
          id="name"
          v-model="form.name"
          type="text"
          class="mt-2 block w-full"
          required
          autofocus
          autocomplete="name"
        />

        <InputError class="mt-2" :message="form.errors.name" />
      </div>

      <div>
        <InputLabel for="email" value="E-mail" />

        <TextInput
          id="email"
          v-model="form.email"
          type="email"
          class="mt-2 block w-full"
          required
          autocomplete="username"
        />

        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div>
        <InputLabel for="password" value="Senha" />

        <TextInput
          id="password"
          v-model="form.password"
          type="password"
          class="mt-2 block w-full"
          required
          autocomplete="new-password"
        />

        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div>
        <InputLabel
          for="password_confirmation"
          value="Confirmar senha"
        />

        <TextInput
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="mt-2 block w-full"
          required
          autocomplete="new-password"
        />

        <InputError
          class="mt-2"
          :message="form.errors.password_confirmation"
        />
      </div>

      <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
        <Link
          :href="route('login')"
          class="text-sm font-semibold text-brand-600 hover:text-brand-700"
        >
          Já tem cadastro?
        </Link>

        <PrimaryButton
          class="w-full sm:w-auto"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Criar cadastro
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>
