<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

const { t } = useLocalization();

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-4 sm:space-y-6">
        <header>
            <h2 class="text-sm sm:text-base font-bold text-red-600 font-serif-luxury uppercase tracking-wide border-b border-red-200 pb-2 sm:pb-2.5">
                {{ t('delete_account') }}
            </h2>

            <p class="text-[10px] sm:text-xs text-[#8C8275] mt-1.5">
                {{ t('delete_account_desc') }}
            </p>
        </header>

        <div class="flex justify-end border-t border-[#E6E1DA] pt-4 sm:pt-6">
            <DangerButton @click="confirmUserDeletion" class="rounded-lg sm:rounded-xl px-3.5 py-2 sm:px-5 sm:py-2.5 text-[10px] sm:text-xs font-bold uppercase tracking-widest cursor-pointer">
                <i class="fas fa-trash-alt mr-1.5"></i> {{ t('delete_account') }}
            </DangerButton>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-4 sm:p-6 md:p-8 space-y-4 sm:space-y-6">
                <header>
                    <h2 class="text-sm sm:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2 sm:pb-2.5">
                        {{ t('delete_confirm_title') }}
                    </h2>

                    <p class="text-[10px] sm:text-xs text-[#8C8275] mt-1.5">
                        {{ t('delete_confirm_desc') }}
                    </p>
                </header>

                <div class="space-y-1 sm:space-y-1.5">
                    <InputLabel
                        for="password"
                        :value="t('password_label')"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="flex justify-end gap-2.5 sm:gap-3 border-t border-[#E6E1DA] pt-4 sm:pt-6">
                    <SecondaryButton @click="closeModal" class="rounded-lg sm:rounded-xl px-3.5 py-2 sm:px-5 sm:py-2.5 text-[10px] sm:text-xs font-bold uppercase tracking-widest cursor-pointer">
                        {{ t('cancel') }}
                    </SecondaryButton>

                    <DangerButton
                        class="ms-2.5 sm:ms-3 rounded-lg sm:rounded-xl px-3.5 py-2 sm:px-5 sm:py-2.5 text-[10px] sm:text-xs font-bold uppercase tracking-widest cursor-pointer"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ t('delete_account') }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
