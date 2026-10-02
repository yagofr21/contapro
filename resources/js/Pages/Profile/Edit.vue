<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Perfil" />

  <AuthenticatedLayout>
    <PageHeader kicker="Conta e segurança" title="Perfil" subtitle="Gerencie seus dados de acesso e mantenha a conta protegida." />

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_0.9fr]">
      <div class="space-y-6">
        <div class="cp-card p-5 sm:p-7">
          <UpdateProfileInformationForm class="max-w-xl" />
        </div>

        <div class="cp-card p-5 sm:p-7">
          <UpdatePasswordForm class="max-w-xl" />
        </div>
      </div>

      <div class="cp-card h-fit p-5 sm:p-7">
        <DeleteUserForm />
      </div>
    </div>
  </AuthenticatedLayout>
</template>
