import { ref } from 'vue'

const show = ref(false)
const message = ref('')
const color = ref<'success' | 'error' | 'warning'>('error')

export const useSnackbar = () => {
    const open = (msg: string, c: 'success' | 'error' | 'warning') => {
        message.value = msg
        color.value = c
        show.value = true
    }
    return {
        show,
        message,
        color,
        success: (msg: string) => open(msg, 'success'),
        error: (msg: string) => open(msg, 'error'),
        warning: (msg: string) => open(msg, 'warning'),
    }
}
