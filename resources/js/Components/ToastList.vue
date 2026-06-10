<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const toasts = ref([]);
let idCounter = 0;

function addToast(message, type = 'success') {
    if (!message) return;
    
    const id = idCounter++;
    toasts.value.push({ id, message, type });
    
    // Auto remove after 4.5 seconds
    setTimeout(() => {
        removeToast(id);
    }, 4500);
}

function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

// Global window event listener for client-side toasts
const handleToastEvent = (e) => {
    if (e.detail) {
        addToast(e.detail.message, e.detail.type);
    }
};

const page = usePage();

// Watch for Inertia flash messages from Laravel redirect
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            addToast(flash.success, 'success');
            page.props.flash.success = null;
        }
        if (flash?.error) {
            addToast(flash.error, 'error');
            page.props.flash.error = null;
        }
        if (flash?.status) {
            addToast(flash.status, 'info');
            page.props.flash.status = null;
        }
    },
    { deep: true, immediate: true }
);

onMounted(() => {
    window.addEventListener('toast-notify', handleToastEvent);
});

onUnmounted(() => {
    window.removeEventListener('toast-notify', handleToastEvent);
});
</script>

<template>
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-3 w-full max-w-sm pointer-events-none">
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-4"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex w-full items-center gap-3 rounded-2xl bg-white p-4 shadow-xl border-l-4 transition-all duration-300 relative overflow-hidden"
                :class="[
                    toast.type === 'success' ? 'border-[#4A6B5D]' : '',
                    toast.type === 'error' ? 'border-rose-600' : '',
                    toast.type === 'info' ? 'border-[#C5A880]' : '',
                ]"
            >
                <!-- Icon badge -->
                <div class="flex-shrink-0 flex items-center justify-center">
                    <span v-if="toast.type === 'success'" class="text-[#4A6B5D] text-lg flex items-center">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <span v-else-if="toast.type === 'error'" class="text-rose-600 text-lg flex items-center">
                        <i class="fas fa-exclamation-circle"></i>
                    </span>
                    <span v-else class="text-[#C5A880] text-lg flex items-center">
                        <i class="fas fa-info-circle"></i>
                    </span>
                </div>
                
                <!-- Content text -->
                <div class="flex-1 pr-6 flex items-center">
                    <p class="text-xs font-bold text-[#2D3330] leading-relaxed">{{ toast.message }}</p>
                </div>
                
                <!-- Close Button -->
                <button
                    @click="removeToast(toast.id)"
                    class="text-[#8C8275] hover:text-[#2D3330] p-1 rounded-lg hover:bg-[#FAF7F2] transition-colors cursor-pointer absolute top-1/2 -translate-y-1/2 right-3"
                >
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
