import { ref } from 'vue'

const tab = ref('dok')

export const useTab = () => {
    return {
        tab,
    }
}
