export function useToast() {
    const toast = (message, type = 'success') => {
        window.dispatchEvent(new CustomEvent('toast-notify', { detail: { message, type } }));
    };

    return { toast };
}
