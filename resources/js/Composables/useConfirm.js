import { ref } from 'vue';

const activeModal = ref(null); // { title, message, confirmText, cancelText, resolve, type: 'confirm'|'prompt', promptValue }

export function useConfirm() {
    const confirm = (message, title = 'Are you sure?', confirmText = 'Confirm', cancelText = 'Cancel') => {
        return new Promise((resolve) => {
            activeModal.value = {
                title,
                message,
                confirmText,
                cancelText,
                resolve,
                type: 'confirm'
            };
        });
    };

    const prompt = (message, title = 'Input Required', confirmText = 'Submit', cancelText = 'Cancel', defaultValue = '') => {
        return new Promise((resolve) => {
            activeModal.value = {
                title,
                message,
                confirmText,
                cancelText,
                resolve,
                type: 'prompt',
                promptValue: defaultValue
            };
        });
    };

    return {
        activeModal,
        confirm,
        prompt
    };
}
