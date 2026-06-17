<script setup>
import { ref } from 'vue';
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
    profile_image: null,
});

const imagePreview = ref(null);
const fileInput = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.profile_image = file;
        const reader = new FileReader();
        reader.onload = (event) => {
            imagePreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};
</script>

<template>
    <section class="space-y-4 sm:space-y-6">
        <header>
            <h2 class="text-sm sm:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2 sm:pb-2.5">
                {{ t('profile_information') }}
            </h2>

            <p class="text-[10px] sm:text-xs text-[#8C8275] mt-1.5">
                {{ t('profile_info_desc') }}
            </p>
        </header>

        <form
            @submit.prevent="form.post(route('profile.update'))"
            class="space-y-4 sm:space-y-6"
        >
            <!-- Profile Image Upload Section -->
            <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 pb-4 sm:pb-6 border-b border-[#E6E1DA]/60">
                <div class="relative group">
                    <img 
                        v-if="imagePreview || user.profile_image" 
                        :src="imagePreview || '/storage/' + user.profile_image" 
                        class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-2 border-[#4A6B5D]/20 shadow-sm" 
                    />
                    <div v-else class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-[#FAF7F2] border-2 border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center font-bold text-xl sm:text-2xl shadow-xs">
                        {{ (form.name || 'C').charAt(0).toUpperCase() }}
                    </div>
                    
                    <button 
                        type="button" 
                        @click="fileInput.click()" 
                        class="absolute inset-0 bg-[#1C201E]/40 rounded-full opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-semibold transition-opacity duration-200 cursor-pointer"
                    >
                        <i class="fas fa-camera text-base"></i>
                    </button>
                </div>

                <div class="space-y-1 text-center sm:text-left">
                    <h3 class="text-xs sm:text-sm font-bold text-[#2D3330]">{{ t('profile_picture') || 'Profile Picture' }}</h3>
                    <p class="text-[10px] sm:text-[11px] text-[#8C8275]">{{ t('profile_picture_desc') || 'Upload a JPG, PNG or WEBP image (Max 2MB)' }}</p>
                    <button 
                        type="button" 
                        @click="fileInput.click()" 
                        class="mt-1 sm:mt-2 text-xs font-bold text-[#4A6B5D] hover:text-[#3D574B] transition-colors"
                    >
                        {{ t('choose_file') || 'Choose Photo' }}
                    </button>
                    <input 
                        ref="fileInput" 
                        type="file" 
                        class="hidden" 
                        accept="image/*" 
                        @change="handleFileChange" 
                    />
                    <InputError class="mt-2" :message="form.errors.profile_image" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <!-- Short Name / Username -->
                <div class="space-y-1 sm:space-y-1.5">
                    <InputLabel for="name" :value="t('your_name_label')" class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

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
                <div class="space-y-1 sm:space-y-1.5">
                    <InputLabel for="full_name" :value="t('full_name_label')" class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <!-- Email Address -->
                <div class="space-y-1 sm:space-y-1.5">
                    <InputLabel for="email" :value="t('email_address_label')" class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

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
                <div class="space-y-1 sm:space-y-1.5">
                    <InputLabel for="phone" :value="t('phone_number_label')" class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

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
            <div class="space-y-1 sm:space-y-1.5">
                <InputLabel for="address" :value="t('delivery_address_label')" class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider" />

                <textarea
                    id="address"
                    rows="3"
                    class="mt-1 block w-full rounded-lg sm:rounded-xl border-[#E6E1DA] text-[#2D3330] p-2 sm:p-3 text-xs sm:text-sm focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] resize-none transition-all duration-150 shadow-xs"
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

            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 border-t border-[#E6E1DA] pt-4 sm:pt-6">
                <PrimaryButton :disabled="form.processing" class="w-full sm:w-auto justify-center">
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
                        class="text-xs text-green-600 font-semibold text-center sm:text-left"
                    >
                        {{ t('saved_successfully') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
