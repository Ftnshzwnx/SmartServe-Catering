<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useLocalization } from '@/Composables/useLocalization';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const { t } = useLocalization();
const user = usePage().props.auth.user;

const form = useForm({
    name: user.name || '',
    full_name: user.full_name || '',
    email: user.email || '',
    phone: user.phone || '',
    address: user.address || '',
});
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">
                {{ t('profile_information') }}
            </h2>

            <p class="text-xs text-[#8C8275] mt-1.5">
                {{ t('profile_info_desc') }}
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-6"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Short Name / Username -->
                <div class="space-y-1.5">
                    <InputLabel for="name" :value="t('your_name_label')" class="text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- Full Name -->
                <div class="space-y-1.5">
                    <InputLabel for="full_name" :value="t('full_name_label')" class="text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

                    <TextInput
                        id="full_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.full_name"
                        required
                        autocomplete="name"
                    />

                    <InputError class="mt-2" :message="form.errors.full_name" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Email Address -->
                <div class="space-y-1.5">
                    <InputLabel for="email" :value="t('email_address_label')" class="text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <!-- Phone Number -->
                <div class="space-y-1.5">
                    <InputLabel for="phone" :value="t('phone_number_label')" class="text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

                    <TextInput
                        id="phone"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.phone"
                        required
                        autocomplete="tel"
                    />

                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>
            </div>

            <!-- Delivery Address -->
            <div class="space-y-1.5">
                <InputLabel for="address" :value="t('delivery_address_label')" class="text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

                <textarea
                    id="address"
                    rows="4"
                    class="mt-1 block w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-sm focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] resize-none transition-all duration-150 shadow-xs"
                    v-model="form.address"
                    required
                ></textarea>

                <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-xs text-gray-800">
                    {{ t('email_unverified') }}
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-xs text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        {{ t('click_resend_verification') }}
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-xs font-medium text-green-600"
                >
                    {{ t('verification_link_sent') }}
                </div>
            </div>

            <div class="flex items-center gap-4 border-t border-[#E6E1DA] pt-6 justify-end">
                <PrimaryButton :disabled="form.processing">
                    <i class="fas fa-save mr-1.5"></i> {{ t('save_changes') }}
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
                        {{ t('saved_successfully') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
