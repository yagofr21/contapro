<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
  <div v-show="message" role="alert">
    <p class="text-sm font-medium text-danger-600 dark:text-danger-500">
      {{ message }}
    </p>
  </div>
</template>
