<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
const page = usePage();
const user = computed(() => page.props.auth.user);

const activeTab = ref('profile'); // 'profile', 'security', 'delete'
</script>

<template>
    <Head :title="t('settings')" />

    <AuthenticatedLayout
        :header-title="t('settings')"
        :header-desc="t('edit_profile_desc')"
    >
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            
            <!-- Left Side: Tabs Navigation Selector -->
            <div class="md:col-span-1 bg-white rounded-3xl border border-[#E6E1DA] p-4 shadow-xs space-y-1 h-fit">
                <button 
                    type="button"
                    @click="activeTab = 'profile'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer text-left"
                    :class="activeTab === 'profile' ? 'bg-[#4A6B5D]/10 text-[#4A6B5D]' : 'text-[#8C8275] hover:bg-[#FAF7F2] hover:text-[#2D3330]'"
                >
                    <i class="fas fa-user text-sm w-5 text-center"></i>
                    {{ t('personal_account') }}
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'security'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer text-left"
                    :class="activeTab === 'security' ? 'bg-[#4A6B5D]/10 text-[#4A6B5D]' : 'text-[#8C8275] hover:bg-[#FAF7F2] hover:text-[#2D3330]'"
                >
                    <i class="fas fa-shield-alt text-sm w-5 text-center"></i>
                    {{ t('reset_password') }}
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'delete'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer text-left"
                    :class="activeTab === 'delete' ? 'bg-red-500/10 text-red-600' : 'text-[#8C8275] hover:bg-red-50 hover:text-red-600'"
                >
                    <i class="fas fa-trash-alt text-sm w-5 text-center"></i>
                    {{ t('delete_account') }}
                </button>
            </div>

            <!-- Right Side: Active Tab Content Panel -->
            <div class="md:col-span-3">
                
                <!-- 1. Personal Account Tab -->
                <div v-show="activeTab === 'profile'" class="space-y-6 animate-fade-in">
                    <div class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs space-y-6">
                        
                        <!-- User Profile Banner Card -->
                        <div class="flex items-center gap-4 bg-[#FAF7F2] p-6 rounded-2xl border border-[#E6E1DA]/60">
                            <div class="w-16 h-16 rounded-full bg-[#C5A880] text-white flex items-center justify-center font-bold text-2xl shadow-xs select-none animate-scale-up">
                                {{ (user?.name || 'C').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-[#2D3330] capitalize leading-none mb-1">{{ user?.name || 'Customer' }}</h4>
                                <p class="text-xs text-[#8C8275]">{{ user?.email || '' }}</p>
                                <span class="inline-block mt-2 px-2.5 py-1 text-[9px] uppercase tracking-wider font-extrabold bg-[#4A6B5D]/10 text-[#4A6B5D] rounded-full">Customer</span>
                            </div>
                        </div>

                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>
                </div>

                <!-- 2. Security Tab -->
                <div v-show="activeTab === 'security'" class="space-y-6 animate-fade-in">
                    <div class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs">
                        <UpdatePasswordForm />
                    </div>
                </div>

                <!-- 3. Delete Tab -->
                <div v-show="activeTab === 'delete'" class="space-y-6 animate-fade-in">
                    <div class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs">
                        <DeleteUserForm />
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
