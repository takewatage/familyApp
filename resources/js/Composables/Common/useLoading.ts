import { ref, computed } from 'vue'

const count = ref(0)

export function useLoading() {
    const isLoading = computed(() => count.value > 0)
    const start = () => count.value++
    const end = () => {
        if (count.value > 0) count.value--
    }
    return { isLoading, start, end }
}
