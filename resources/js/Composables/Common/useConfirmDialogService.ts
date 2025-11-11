import { useDialogService } from '@/Composables/Common/useDialogService'
import ConfirmationDialog from '@/Components/App/ConfirmationDialog.vue'

export function useConfirmDialog() {
    const dialog = useDialogService()

    const confirm = async (options: {
        title?: string
        message?: string
        confirmText?: string
        cancelText?: string
        confirmColor?: string
    }): Promise<boolean> => {
        console.log('dialog')
        const { afterClosed } = dialog.open<boolean>({
            component: ConfirmationDialog,
            props: options,
            maxWidth: '400px',
        })

        const result = await afterClosed()
        return result ?? false
    }

    return { confirm }
}
