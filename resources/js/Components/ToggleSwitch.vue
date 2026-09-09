<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    checked: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits(['update:checked']);

const proxy = computed({
    get: () => props.checked,
    set: (value: boolean) => emit('update:checked', value),
});
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="checked"
    :disabled="disabled"
    :class="[
      proxy ? 'bg-brand-600' : 'bg-stone-300 dark:bg-slate-700',
      disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
    ]"
    class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
    @click="proxy = !proxy"
  >
    <span :class="proxy ? 'translate-x-5' : 'translate-x-0'" class="inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
  </button>
</template>