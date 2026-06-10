<script setup>
import { useConfirm } from '@/Composables/useConfirm';

const { activeModal } = useConfirm();

function handleConfirm() {
    if (activeModal.value) {
        const resolve = activeModal.value.resolve;
        const type = activeModal.value.type;
        const val = activeModal.value.promptValue;
        activeModal.value = null;
        if (type === 'prompt') {
            resolve(val);
        } else {
            resolve(true);
        }
    }
}

function handleCancel() {
    if (activeModal.value) {
        const resolve = activeModal.value.resolve;
        activeModal.value = null;
        resolve(null);
    }
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="activeModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-6 bg-[#1B2A22]/50 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-sm w-full p-8 relative space-y-6 border border-[#E6E1DA] shadow-2xl">
                <!-- Close Button -->
                <button 
                    @click="handleCancel" 
                    class="absolute top-5 right-5 text-[#8C8275] hover:text-[#2D3330] w-8 h-8 rounded-full hover:bg-[#FAF7F2] border border-[#E6E1DA] flex items-center justify-center cursor-pointer"
                >
                    <i class="fas fa-times text-xs"></i>
                </button>

                <div class="text-center space-y-3">
                    <!-- Icon badge -->
                    <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto text-xl border border-[#E6E1DA]"
                         :class="activeModal.type === 'prompt' ? 'bg-[#FAF6F0] text-[#C5A880]' : 'bg-[#FAF6F0] text-[#4A6B5D]'"
                    >
                        <i :class="activeModal.type === 'prompt' ? 'fas fa-pen-nib' : 'fas fa-question-circle'"></i>
                    </div>
                    
                    <h3 class="text-base font-extrabold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                        {{ activeModal.title }}
                    </h3>
                    
                    <p class="text-xs text-[#5C6460] leading-relaxed">
                        {{ activeModal.message }}
                    </p>
                </div>

                <!-- Prompt Input Field -->
                <div v-if="activeModal.type === 'prompt'" class="space-y-1">
                    <input 
                        type="text" 
                        v-model="activeModal.promptValue"
                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                        placeholder="Type here..."
                        required
                        @keyup.enter="handleConfirm"
                    />
                </div>

                <!-- Footer buttons -->
                <div class="flex gap-2.5 pt-2">
                    <button 
                        @click="handleCancel" 
                        class="flex-1 bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold py-3 px-4 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer"
                    >
                        {{ activeModal.cancelText }}
                    </button>
                    <button 
                        @click="handleConfirm" 
                        class="flex-1 text-white font-bold py-3 px-4 rounded-xl text-xs uppercase tracking-widest shadow transition-colors cursor-pointer"
                        :class="activeModal.type === 'prompt' ? 'bg-[#C5A880] hover:bg-[#b89047]' : 'bg-[#4A6B5D] hover:bg-[#3D574B]'"
                    >
                        {{ activeModal.confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
