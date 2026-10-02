import { computed, onMounted, onUnmounted, ref } from 'vue';

/** Keep chart labels and tooltips aligned with the existing application theme. */
export function useChartPalette() {
    const dark = ref(document.documentElement.classList.contains('dark'));
    const observer = new MutationObserver(() => {
        dark.value = document.documentElement.classList.contains('dark');
    });
    onMounted(() => {
        dark.value = document.documentElement.classList.contains('dark');
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    });
    onUnmounted(() => observer.disconnect());
    return computed(() => ({
        text: dark.value ? '#cbd5e1' : '#57534e',
        grid: dark.value ? '#334155' : '#e5e7eb',
        surface: dark.value ? '#0f172a' : '#ffffff',
        income: dark.value ? '#6ee7b7' : '#047857',
        expense: dark.value ? '#fda4af' : '#be123c',
    }));
}
