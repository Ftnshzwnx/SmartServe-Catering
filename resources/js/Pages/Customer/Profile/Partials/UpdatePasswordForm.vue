<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

const { t } = useLocalization();

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">
                {{ t('update_password') }}
            </h2>

            <p class="text-xs text-[#8C8275] mt-1.5">
                {{ t('update_password_desc') }}
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-6">
            <div class="space-y-1.5">
                <InputLabel for="current_password" :value="t('current_password')" class="text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                />

                <InputError
                    :message="form.errors.current_password"
                    class="mt-2"
                />
            </div>

            <div class="space-y-1.5">
                <InputLabel for="password" :value="t('new_password_label')" class="text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div class="space-y-1.5">
                <InputLabel
                    for="password_confirmation"
                    :value="t('confirm_password_label')"
                    class="text-xs font-bold text-[#8C8275] uppercase tracking-wider"
                />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />

                <InputError
                    :message="form.errors.password_confirmation"
                    class="mt-2"
                />
            </div>

            <div class="flex items-center gap-4 border-t border-[#E6E1DA] pt-6 justify-end">
                <PrimaryButton :disabled="form.processing">
                    <i class="fas fa-key mr-1.5"></i> {{ t('update_password') }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs text-green-600 font-semibold"
                    >
                        Password updated.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
