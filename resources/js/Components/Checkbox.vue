<script setup lang="ts">
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps<{
    checked: boolean;
    value?: string;
}>();

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
  <input
    v-model="proxyChecked"
    type="checkbox"
    :value="value"
    class="rounded border-stone-300 text-brand-600 shadow-sm transition focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950"
  />
</template>
