<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    deliveryZones: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const { toast } = useToast();
const { confirm } = useConfirm();
const { t, currentLanguage } = useLocalization();

const activeTab = ref('company'); // 'company', 'zones', 'profile', 'security'
const qrPreviewUrl = ref('');
const showQrModal = ref(false);
function resolveQrPath(path) {
    if (!path) return '';
    if (path.startsWith('data:') || path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) {
        return path;
    }
    return '/' + path;
}

const activeQrUrl = computed(() => {
    if (qrPreviewUrl.value) return qrPreviewUrl.value;
    return resolveQrPath(props.settings.qr_code_path);
});
const fileError = ref('');

// Company Form state
const form = useForm({
    business_name: props.settings.business_name || 'SmartServe Catering',
    deposit_percentage: props.settings.deposit_percentage || 30,
    cancellation_policy_days: props.settings.cancellation_policy_days || 7,
    grace_period_days: props.settings.grace_period_days || 2,
    min_lead_time_days: props.settings.min_lead_time_days || 7,
    min_order_value: props.settings.min_order_value || 100,
    contact_phone: props.settings.contact_phone || '019-2094670',
    contact_email: props.settings.contact_email || 'admin@smartservecatering.com',
    business_address: props.settings.business_address || 'Gong Badak, Kuala Terengganu',
    bank_name: props.settings.bank_name || 'Maybank',
    bank_account_no: props.settings.bank_account_no || '164213456789',
    bank_account_name: props.settings.bank_account_name || 'SmartServe Catering',
    qr_code: null,
});

// Profile Form state
const profileForm = useForm({
    name: user.value?.name || '',
    full_name: user.value?.full_name || '',
    email: user.value?.email || '',
    phone: user.value?.phone || '',
    address: user.value?.address || '',
});

// Password Form state
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Zone form state
const showZoneModal = ref(false);
const editingZoneId = ref(null);
const zoneForm = useForm({
    name: '',
    fee: '',
});

function openAddZoneModal() {
    editingZoneId.value = null;
    zoneForm.name = '';
    zoneForm.fee = '';
    zoneForm.clearErrors();
    showZoneModal.value = true;
}

function openEditZoneModal(zone) {
    editingZoneId.value = zone.id;
    zoneForm.name = zone.name;
    zoneForm.fee = zone.fee;
    zoneForm.clearErrors();
    showZoneModal.value = true;
}

function submitZoneForm() {
    if (editingZoneId.value) {
        zoneForm.put(route('admin.delivery-zones.update', { id: editingZoneId.value }), {
            onSuccess: () => {
                toast(t('admin_settings_toast_zone_updated'));
                showZoneModal.value = false;
            }
        });
    } else {
        zoneForm.post(route('admin.delivery-zones.store'), {
            onSuccess: () => {
                toast(t('admin_settings_toast_zone_created'));
                showZoneModal.value = false;
            }
        });
    }
}

async function deleteZone(id) {
    if (await confirm(t('admin_settings_confirm_delete_zone'), t('admin_settings_confirm_delete_zone_title'))) {
        zoneForm.delete(route('admin.delivery-zones.delete', { id: id }), {
            onSuccess: () => {
                toast(t('admin_settings_toast_zone_deleted'));
            }
        });
    }
}

function handleFileChange(event) {
    const file = event.target.files[0];
    fileError.value = '';
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            fileError.value = t('admin_settings_file_size_error');
            form.qr_code = null;
            qrPreviewUrl.value = '';
            return;
        }
        form.qr_code = file;
        qrPreviewUrl.value = URL.createObjectURL(file);
    }
}

function submitSettings() {
    form.post(route('admin.settings.update'), {
        forceFormData: true,
        onSuccess: () => {
            toast(t('admin_settings_toast_settings_updated'));
            form.reset('qr_code');
            qrPreviewUrl.value = '';
        }
    });
}

function submitProfile() {
    profileForm.post(route('profile.update'), {
        onSuccess: () => {
            toast(t('admin_settings_toast_profile_updated'));
        }
    });
}

function submitPassword() {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast(t('admin_settings_toast_password_updated'));
            passwordForm.reset();
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        }
    });
}
</script>

<template>
    <AdminLayout 
        :title="t('admin_system_settings')"
        :header-title="t('admin_system_settings')"
        :header-desc="currentLanguage === 'en' ? 'Configure company profile information, dynamic business policies, and payment credentials.' : 'Konfigurasikan maklumat profil syarikat, polisi perniagaan dinamik, dan butiran pembayaran.'"
    >
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            
            <!-- Left Side: Tabs Navigation Selector (Pill Segmented Control on Mobile) -->
            <div class="md:col-span-1 bg-[#FAF7F2] md:bg-white border border-[#E6E1DA] rounded-2xl md:rounded-3xl p-1 md:p-4 shadow-xs flex flex-row md:flex-col gap-1 md:gap-0 md:space-y-1 overflow-x-auto whitespace-nowrap scrollbar-none select-none md:select-text h-fit mb-6 md:mb-0">
                <button 
                    type="button"
                    @click="activeTab = 'company'"
                    class="flex-1 md:w-full flex items-center justify-center md:justify-start gap-1.5 md:gap-3 px-3 md:px-4 py-2.5 md:py-3 rounded-xl text-[10px] sm:text-xs font-extrabold md:font-bold transition-all duration-200 cursor-pointer text-center md:text-left shrink-0 uppercase md:normal-case tracking-wider md:tracking-normal focus:outline-none"
                    :class="activeTab === 'company' ? 'bg-[#4A6B5D] text-white shadow-xs md:bg-[#4A6B5D]/10 md:text-[#4A6B5D] md:shadow-none' : 'text-[#8C8275] hover:text-[#5C6460] md:hover:bg-[#FAF7F2] md:hover:text-[#2D3330]'"
                >
                    <i class="fas fa-building text-xs md:text-sm w-4 md:w-5 text-center shrink-0"></i>
                    {{ t('admin_settings_tab_company') }}
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'zones'"
                    class="flex-1 md:w-full flex items-center justify-center md:justify-start gap-1.5 md:gap-3 px-3 md:px-4 py-2.5 md:py-3 rounded-xl text-[10px] sm:text-xs font-extrabold md:font-bold transition-all duration-200 cursor-pointer text-center md:text-left shrink-0 uppercase md:normal-case tracking-wider md:tracking-normal focus:outline-none"
                    :class="activeTab === 'zones' ? 'bg-[#4A6B5D] text-white shadow-xs md:bg-[#4A6B5D]/10 md:text-[#4A6B5D] md:shadow-none' : 'text-[#8C8275] hover:text-[#5C6460] md:hover:bg-[#FAF7F2] md:hover:text-[#2D3330]'"
                >
                    <i class="fas fa-truck text-xs md:text-sm w-4 md:w-5 text-center shrink-0"></i>
                    {{ t('admin_settings_tab_zones') }}
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'profile'"
                    class="flex-1 md:w-full flex items-center justify-center md:justify-start gap-1.5 md:gap-3 px-3 md:px-4 py-2.5 md:py-3 rounded-xl text-[10px] sm:text-xs font-extrabold md:font-bold transition-all duration-200 cursor-pointer text-center md:text-left shrink-0 uppercase md:normal-case tracking-wider md:tracking-normal focus:outline-none"
                    :class="activeTab === 'profile' ? 'bg-[#4A6B5D] text-white shadow-xs md:bg-[#4A6B5D]/10 md:text-[#4A6B5D] md:shadow-none' : 'text-[#8C8275] hover:text-[#5C6460] md:hover:bg-[#FAF7F2] md:hover:text-[#2D3330]'"
                >
                    <i class="fas fa-user text-xs md:text-sm w-4 md:w-5 text-center shrink-0"></i>
                    {{ t('admin_settings_tab_profile') }}
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'security'"
                    class="flex-1 md:w-full flex items-center justify-center md:justify-start gap-1.5 md:gap-3 px-3 md:px-4 py-2.5 md:py-3 rounded-xl text-[10px] sm:text-xs font-extrabold md:font-bold transition-all duration-200 cursor-pointer text-center md:text-left shrink-0 uppercase md:normal-case tracking-wider md:tracking-normal focus:outline-none"
                    :class="activeTab === 'security' ? 'bg-[#4A6B5D] text-white shadow-xs md:bg-[#4A6B5D]/10 md:text-[#4A6B5D] md:shadow-none' : 'text-[#8C8275] hover:text-[#5C6460] md:hover:bg-[#FAF7F2] md:hover:text-[#2D3330]'"
                >
                    <i class="fas fa-shield-alt text-xs md:text-sm w-4 md:w-5 text-center shrink-0"></i>
                    {{ t('admin_settings_tab_security') }}
                </button>
            </div>

            <!-- Right Side: Active Tab Content Panel -->
            <div class="md:col-span-3">
                
                <!-- 1. Company Profile Tab -->
                <div v-show="activeTab === 'company'" class="space-y-6 animate-fade-in">
                    <form @submit.prevent="submitSettings" class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            <!-- Left: General & Payments -->
                            <div class="lg:col-span-2 space-y-4 md:space-y-6">
                                <!-- General & Policies -->
                                <div class="bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] p-3.5 md:p-8 shadow-xs space-y-4 md:space-y-6">
                                    <h3 class="text-xs sm:text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2 md:pb-2.5">{{ t('admin_settings_company_title') }}</h3>
                                    
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_business_name') }}</label>
                                        <input 
                                            type="text" 
                                            v-model="form.business_name" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            :placeholder="currentLanguage === 'en' ? 'e.g. SmartServe Catering Enterprise' : 'Contoh: SmartServe Catering Enterprise'"
                                            required
                                        />
                                        <span v-if="form.errors.business_name" class="text-xs text-red-500 font-semibold block">{{ form.errors.business_name }}</span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">
                                        <!-- Deposit Percentage -->
                                        <div class="space-y-1.5">
                                            <label class="text-[9.5px] sm:text-[10px] md:text-[11px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_deposit_percentage') }}</label>
                                            <input 
                                                type="number" 
                                                v-model="form.deposit_percentage" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="0" 
                                                max="100"
                                                required
                                            />
                                            <span v-if="form.errors.deposit_percentage" class="text-xs text-red-500 font-semibold block">{{ form.errors.deposit_percentage }}</span>
                                        </div>

                                        <!-- Cancellation Policy Days -->
                                        <div class="space-y-1.5">
                                            <label class="text-[9.5px] sm:text-[10px] md:text-[11px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_cancellation_policy') }}</label>
                                            <input 
                                                type="number" 
                                                v-model="form.cancellation_policy_days" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="0" 
                                                required
                                             />
                                            <span v-if="form.errors.cancellation_policy_days" class="text-xs text-red-500 font-semibold block">{{ form.errors.cancellation_policy_days }}</span>
                                        </div>

                                        <!-- Grace Period Days -->
                                        <div class="space-y-1.5">
                                            <label class="text-[9.5px] sm:text-[10px] md:text-[11px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_grace_period') }}</label>
                                            <input 
                                                type="number" 
                                                v-model="form.grace_period_days" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="0" 
                                                required
                                            />
                                            <span v-if="form.errors.grace_period_days" class="text-xs text-red-500 font-semibold block">{{ form.errors.grace_period_days }}</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                        <!-- Min Lead Time -->
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_min_lead_time') }}</label>
                                            <input 
                                                type="number" 
                                                v-model="form.min_lead_time_days" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="1" 
                                                required
                                            />
                                            <span v-if="form.errors.min_lead_time_days" class="text-xs text-red-500 font-semibold block">{{ form.errors.min_lead_time_days }}</span>
                                        </div>

                                        <!-- Min Order Value -->
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_min_order_value') }}</label>
                                            <input 
                                                type="number" 
                                                v-model="form.min_order_value" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="0" 
                                                required
                                            />
                                            <span v-if="form.errors.min_order_value" class="text-xs text-red-500 font-semibold block">{{ form.errors.min_order_value }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Credentials -->
                                <div class="bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] p-3.5 md:p-8 shadow-xs space-y-4 md:space-y-6">
                                    <h3 class="text-xs sm:text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2 md:pb-2.5">{{ t('admin_settings_payment_credentials') }}</h3>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 items-start">
                                        <!-- Bank Account Info -->
                                        <div class="space-y-3.5">
                                            <span class="text-xs font-bold text-[#C5A880] uppercase tracking-widest block border-b border-[#E6E1DA] pb-1.5">{{ t('admin_settings_bank_transfer_info') }}</span>
                                            
                                            <div class="space-y-2.5">
                                                <div class="space-y-1">
                                                    <label class="text-[9.5px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_bank_name') }}</label>
                                                    <input 
                                                        type="text" 
                                                        v-model="form.bank_name" 
                                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                        placeholder="e.g. Maybank"
                                                        required
                                                    />
                                                    <span v-if="form.errors.bank_name" class="text-xs text-red-500 font-semibold block">{{ form.errors.bank_name }}</span>
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-[9.5px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_account_no') }}</label>
                                                    <input 
                                                        type="text" 
                                                        v-model="form.bank_account_no" 
                                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                        placeholder="e.g. 164213456789"
                                                        required
                                                    />
                                                    <span v-if="form.errors.bank_account_no" class="text-xs text-red-500 font-semibold block">{{ form.errors.bank_account_no }}</span>
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-[9.5px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_account_name') }}</label>
                                                    <input 
                                                        type="text" 
                                                        v-model="form.bank_account_name" 
                                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                        placeholder="e.g. SmartServe Catering"
                                                        required
                                                    />
                                                    <span v-if="form.errors.bank_account_name" class="text-xs text-red-500 font-semibold block">{{ form.errors.bank_account_name }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- QR Code Info -->
                                        <div class="space-y-3.5">
                                            <span class="text-xs font-bold text-[#C5A880] uppercase tracking-widest block border-b border-[#E6E1DA] pb-1.5">{{ t('admin_settings_qr_title') }}</span>
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 items-center">
                                                <div 
                                                    class="border border-dashed border-[#C5A880]/30 hover:border-[#C5A880]/60 rounded-2xl bg-[#FAF7F2] p-2.5 text-center cursor-pointer group transition-all duration-300 relative hover:shadow-md"
                                                    @click="activeQrUrl && (showQrModal = true)"
                                                    title="Klik untuk besarkan"
                                                >
                                                    <div class="inline-block p-1 bg-white border border-[#E6E1DA] rounded-xl shadow-xs relative overflow-hidden">
                                                        <img 
                                                            v-if="qrPreviewUrl" 
                                                            :src="qrPreviewUrl" 
                                                            :alt="t('admin_settings_qr_preview_new')" 
                                                            class="w-20 h-20 object-contain mx-auto transition-transform duration-350 group-hover:scale-105"
                                                        />
                                                        <img 
                                                            v-else-if="settings.qr_code_path" 
                                                            :src="resolveQrPath(settings.qr_code_path)" 
                                                            :alt="t('admin_settings_qr_preview_current')" 
                                                            class="w-20 h-20 object-contain mx-auto transition-transform duration-350 group-hover:scale-105"
                                                        />
                                                        <div v-else class="w-20 h-20 bg-[#FAF7F2] rounded-xl flex flex-col items-center justify-center text-[#8C8275]">
                                                            <i class="fas fa-qrcode text-lg mb-0.5"></i>
                                                            <span class="text-[8px] font-bold">{{ t('admin_settings_qr_no_file') }}</span>
                                                        </div>
                                                        <!-- Hover Magnifier Icon Overlay -->
                                                        <div v-if="activeQrUrl" class="absolute inset-0 bg-[#4A6B5D]/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                            <i class="fas fa-search-plus text-sm"></i>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-y-1.5">
                                                    <label class="text-[9.5px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_qr_preview') }}</label>
                                                    <div class="border-2 border-dashed border-[#E6E1DA] hover:border-[#4A6B5D] rounded-xl p-2.5 text-center cursor-pointer transition-colors relative bg-[#FAF7F2]/30">
                                                        <input 
                                                            type="file" 
                                                            @change="handleFileChange"
                                                            accept="image/jpeg,image/png,image/jpg"
                                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                        />
                                                        <div class="space-y-1 pointer-events-none text-xs">
                                                            <div class="text-[#4A6B5D] text-xs mx-auto">
                                                                    <i class="fas fa-image"></i>
                                                            </div>
                                                            <span class="font-bold text-[#2D3330] text-[9.5px] block truncate">
                                                                {{ form.qr_code ? form.qr_code.name : t('admin_settings_qr_choose_file') }}
                                                            </span>
                                                            <span class="text-[8px] text-[#8C8275] block">
                                                                {{ t('admin_settings_qr_max_size') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <span v-if="fileError" class="text-[9.5px] text-red-500 font-semibold block">{{ fileError }}</span>
                                                    <span v-if="form.errors.qr_code" class="text-[9.5px] text-red-500 font-semibold block">{{ form.errors.qr_code }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Contact Details -->
                            <div class="lg:col-span-1 bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] p-3.5 md:p-8 shadow-xs space-y-4 md:space-y-6">
                                <h3 class="text-xs sm:text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2 md:pb-2.5">{{ t('admin_settings_contact_details') }}</h3>
                                
                                <div class="space-y-3 md:space-y-4">
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_contact_phone') }}</label>
                                        <input 
                                            type="text" 
                                            v-model="form.contact_phone" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            placeholder="e.g. +6012-3456789"
                                            required
                                        />
                                        <span v-if="form.errors.contact_phone" class="text-xs text-red-500 font-semibold block">{{ form.errors.contact_phone }}</span>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_support_email') }}</label>
                                        <input 
                                            type="email" 
                                            v-model="form.contact_email" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            placeholder="e.g. sales@smartserve.com"
                                            required
                                        />
                                        <span v-if="form.errors.contact_email" class="text-xs text-red-500 font-semibold block">{{ form.errors.contact_email }}</span>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_business_address') }}</label>
                                        <textarea 
                                            v-model="form.business_address" 
                                            rows="4"
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] resize-none" 
                                            placeholder="Enter physical location..."
                                            required
                                        ></textarea>
                                        <span v-if="form.errors.business_address" class="text-xs text-red-500 font-semibold block">{{ form.errors.business_address }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Save Actions -->
                         <div class="bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] p-3.5 md:px-8 shadow-xs flex justify-end">
                             <button 
                                 type="submit" 
                                 class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 md:px-6 py-2 md:py-2.5 shadow transition-colors cursor-pointer rounded-xl text-[10px] sm:text-xs uppercase tracking-widest w-full md:w-auto"
                                 :disabled="form.processing"
                             >
                                 <i class="fas fa-save mr-1.5"></i> {{ t('admin_settings_save_settings_btn') }}
                             </button>
                         </div>
                    </form>
                </div>

                <!-- 2. Personal Account Tab -->
                <div v-show="activeTab === 'profile'" class="space-y-4 md:space-y-6 animate-fade-in">
                    <div class="bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] p-4 md:p-8 shadow-xs space-y-4 md:space-y-6">
                        
                        <div>
                            <h3 class="text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2 md:pb-2.5">{{ t('admin_settings_profile_title') }}</h3>
                            <p class="text-[10px] md:text-xs text-[#8C8275] mt-1.5">{{ t('admin_settings_profile_desc') }}</p>
                        </div>

                        <!-- User Profile Banner Card -->
                        <div class="flex items-center gap-3 md:gap-4 bg-[#FAF7F2] p-4 md:p-6 rounded-2xl border border-[#E6E1DA]/60">
                            <div class="w-12 h-12 md:w-16 md:h-16 rounded-full bg-[#C5A880] text-white flex items-center justify-center font-bold text-lg md:text-2xl shadow-xs select-none shrink-0">
                                {{ (user?.name || 'A').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h4 class="text-sm md:text-base font-bold text-[#2D3330] capitalize leading-none mb-1">{{ user?.name || 'Admin' }}</h4>
                                <p class="text-[10px] md:text-xs text-[#8C8275] truncate">{{ user?.email || 'admin@smartservecatering.com' }}</p>
                                <span class="inline-block mt-2 px-2 py-0.5 text-[8px] md:text-[9px] uppercase tracking-wider font-extrabold bg-[#4A6B5D]/10 text-[#4A6B5D] rounded-full">{{ t('admin_settings_profile_role') }}</span>
                            </div>
                        </div>

                        <!-- Profile edit form -->
                        <form @submit.prevent="submitProfile" class="space-y-4 md:space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_profile_username') }}</label>
                                    <input 
                                        type="text" 
                                        v-model="profileForm.name" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        required
                                    />
                                    <span v-if="profileForm.errors.name" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.name }}</span>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_profile_fullname') }}</label>
                                    <input 
                                        type="text" 
                                        v-model="profileForm.full_name" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        required
                                    />
                                    <span v-if="profileForm.errors.full_name" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.full_name }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_profile_email') }}</label>
                                    <input 
                                        type="email" 
                                        v-model="profileForm.email" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        required
                                    />
                                    <span v-if="profileForm.errors.email" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.email }}</span>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_profile_phone') }}</label>
                                    <input 
                                        type="text" 
                                        v-model="profileForm.phone" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        required
                                    />
                                    <span v-if="profileForm.errors.phone" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.phone }}</span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_profile_address') }}</label>
                                <textarea 
                                    v-model="profileForm.address" 
                                    rows="3"
                                    class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] resize-none" 
                                    required
                                ></textarea>
                                <span v-if="profileForm.errors.address" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.address }}</span>
                            </div>

                            <div class="border-t border-[#E6E1DA] pt-4 md:pt-6 flex justify-end">
                                <button 
                                    type="submit" 
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 md:px-6 py-2 md:py-2.5 shadow transition-colors cursor-pointer rounded-xl text-[10px] sm:text-xs uppercase tracking-widest w-full md:w-auto"
                                    :disabled="profileForm.processing"
                                >
                                    <i class="fas fa-save mr-1.5"></i> {{ t('admin_settings_save_changes_btn') }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                <!-- 3. Security (Passwords) Tab -->
                <div v-show="activeTab === 'security'" class="space-y-4 md:space-y-6 animate-fade-in">
                    <div class="bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] p-4 md:p-8 shadow-xs space-y-4 md:space-y-6">
                        
                        <div>
                            <h3 class="text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2 md:pb-2.5">{{ t('admin_settings_security_title') }}</h3>
                            <p class="text-[10px] md:text-xs text-[#8C8275] mt-1.5">{{ t('admin_settings_security_desc') }}</p>
                        </div>

                        <!-- Password update form -->
                        <form @submit.prevent="submitPassword" class="space-y-4 md:space-y-6">
                            <div class="space-y-3 md:space-y-4 max-w-xl">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_security_current_pwd') }}</label>
                                    <div class="relative flex items-center">
                                        <input 
                                            :type="showCurrentPassword ? 'text' : 'password'" 
                                            v-model="passwordForm.current_password" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 pr-10 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            autocomplete="current-password"
                                            required
                                        />
                                        <button 
                                            type="button"
                                            @click="showCurrentPassword = !showCurrentPassword"
                                            class="absolute right-3 text-[#8C8275] hover:text-[#2D3330] transition-colors focus:outline-none cursor-pointer"
                                        >
                                            <i class="fas" :class="showCurrentPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <span v-if="passwordForm.errors.current_password" class="text-xs text-red-500 font-semibold block">{{ passwordForm.errors.current_password }}</span>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_security_new_pwd') }}</label>
                                    <div class="relative flex items-center">
                                        <input 
                                            :type="showNewPassword ? 'text' : 'password'" 
                                            v-model="passwordForm.password" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 pr-10 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            autocomplete="new-password"
                                            required
                                        />
                                        <button 
                                            type="button"
                                            @click="showNewPassword = !showNewPassword"
                                            class="absolute right-3 text-[#8C8275] hover:text-[#2D3330] transition-colors focus:outline-none cursor-pointer"
                                        >
                                            <i class="fas" :class="showNewPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <span v-if="passwordForm.errors.password" class="text-xs text-red-500 font-semibold block">{{ passwordForm.errors.password }}</span>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_settings_security_confirm_pwd') }}</label>
                                    <div class="relative flex items-center">
                                        <input 
                                            :type="showConfirmPassword ? 'text' : 'password'" 
                                            v-model="passwordForm.password_confirmation" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 pr-10 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            autocomplete="new-password"
                                            required
                                        />
                                        <button 
                                            type="button"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                            class="absolute right-3 text-[#8C8275] hover:text-[#2D3330] transition-colors focus:outline-none cursor-pointer"
                                        >
                                            <i class="fas" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <span v-if="passwordForm.errors.password_confirmation" class="text-xs text-red-500 font-semibold block">{{ passwordForm.errors.password_confirmation }}</span>
                                </div>
                            </div>

                            <div class="border-t border-[#E6E1DA] pt-4 md:pt-6 flex justify-end">
                                <button 
                                    type="submit" 
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 md:px-6 py-2 md:py-2.5 shadow transition-colors cursor-pointer rounded-xl text-[10px] sm:text-xs uppercase tracking-widest w-full md:w-auto"
                                    :disabled="passwordForm.processing"
                                >
                                    <i class="fas fa-key mr-1.5"></i> {{ t('admin_settings_security_update_pwd_btn') }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                <!-- 4. Delivery Zones CRUD Tab -->
                <div v-show="activeTab === 'zones'" class="space-y-4 md:space-y-6 animate-fade-in font-sans-modern">
                    <div class="bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] p-4 md:p-8 shadow-xs space-y-4 md:space-y-6">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 border-b border-[#E6E1DA] pb-4">
                            <div>
                                <h3 class="text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-0 pb-0">{{ t('admin_settings_zones_title') }}</h3>
                                <p class="text-[10px] md:text-xs text-[#8C8275] mt-1">{{ t('admin_settings_zones_desc') }}</p>
                            </div>
                            <button
                                type="button"
                                @click="openAddZoneModal"
                                class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-3 py-2 rounded-xl text-[10px] md:text-xs uppercase tracking-widest transition-colors flex items-center justify-center gap-1.5 cursor-pointer w-full sm:w-auto"
                            >
                                <i class="fas fa-plus-circle text-xs md:text-sm"></i> {{ t('admin_settings_add_zone_btn') }}
                            </button>
                        </div>

                        <!-- Zones Table -->
                        <div class="overflow-x-auto rounded-xl md:rounded-2xl border border-[#E6E1DA] bg-white">
                            <table class="w-full min-w-[500px] text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-[#FAF7F2] text-[#8C8275] border-b border-[#E6E1DA] font-bold uppercase tracking-wider">
                                        <th class="px-2.5 sm:px-6 py-2 sm:py-4">{{ t('admin_settings_zone_name_col') }}</th>
                                        <th class="px-2.5 sm:px-6 py-2 sm:py-4">{{ t('admin_settings_delivery_fee_col') }}</th>
                                        <th class="px-2.5 sm:px-6 py-2 sm:py-4 text-center w-32">{{ t('admin_settings_actions_col') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E6E1DA] font-semibold text-[#2D3330]">
                                    <tr v-for="zone in props.deliveryZones" :key="zone.id" class="hover:bg-[#FAF7F2]/40 transition-colors">
                                        <td class="px-2.5 sm:px-6 py-2 sm:py-4">{{ zone.name }}</td>
                                        <td class="px-2.5 sm:px-6 py-2 sm:py-4">RM {{ parseFloat(zone.fee).toFixed(2) }}</td>
                                        <td class="px-2.5 sm:px-6 py-2 sm:py-4 text-center">
                                            <div class="flex justify-center gap-3">
                                                <button
                                                    type="button"
                                                    @click="openEditZoneModal(zone)"
                                                    class="text-amber-600 hover:text-amber-800 transition-colors p-1 cursor-pointer"
                                                    :title="t('admin_settings_edit_zone')"
                                                >
                                                    <i class="fas fa-edit text-sm"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteZone(zone.id)"
                                                    class="text-rose-600 hover:text-rose-800 transition-colors p-1 cursor-pointer"
                                                    :title="t('admin_settings_delete_zone')"
                                                >
                                                    <i class="fas fa-trash-alt text-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="props.deliveryZones.length === 0">
                                        <td colspan="3" class="p-8 text-center text-[#8C8275] italic">{{ t('admin_settings_no_zones') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Add/Edit Zone Modal -->
        <div v-if="showZoneModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-[60] animate-fade-in font-sans-modern">
            <div class="bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] shadow-2xl p-4 md:p-8 max-w-md w-full space-y-4 md:space-y-6">
                <div>
                    <h3 class="text-base md:text-lg font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                        {{ editingZoneId ? t('admin_settings_zone_modal_edit_title') : t('admin_settings_zone_modal_add_title') }}
                    </h3>
                    <p class="text-[10px] md:text-xs text-[#8C8275] mt-1">{{ t('admin_settings_zone_modal_desc') }}</p>
                </div>

                <form @submit.prevent="submitZoneForm" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-[9.5px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_settings_zone_name_label') }}</label>
                        <input 
                            type="text" 
                            v-model="zoneForm.name" 
                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                            :placeholder="currentLanguage === 'en' ? 'e.g. Dungun' : 'Contoh: Dungun'"
                            required
                        />
                        <span v-if="zoneForm.errors.name" class="text-xs text-red-500 font-semibold block">{{ zoneForm.errors.name }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[9.5px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_settings_zone_fee_label') }}</label>
                        <input 
                            type="number" 
                            v-model="zoneForm.fee" 
                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] py-1.5 px-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                            :placeholder="currentLanguage === 'en' ? 'e.g. 120.00' : 'Contoh: 120.00'"
                            min="0"
                            step="0.01"
                            required
                        />
                        <span v-if="zoneForm.errors.fee" class="text-xs text-red-500 font-semibold block">{{ zoneForm.errors.fee }}</span>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-2">
                        <button 
                            type="button" 
                            @click="showZoneModal = false"
                            class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-3 py-1.5 rounded-xl text-[10px] md:text-xs uppercase tracking-widest transition-colors cursor-pointer"
                        >
                            {{ t('admin_settings_zone_cancel') }}
                        </button>
                        <button 
                            type="submit" 
                            class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-3 py-1.5 rounded-xl text-[10px] md:text-xs uppercase tracking-widest transition-colors cursor-pointer"
                            :disabled="zoneForm.processing"
                        >
                            {{ zoneForm.processing ? t('admin_settings_zone_submitting') : t('admin_settings_zone_save_btn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- QR Code Preview Popup Modal -->
        <div v-if="showQrModal && activeQrUrl" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-[60] animate-fade-in font-sans-modern" @click.self="showQrModal = false">
            <div class="bg-white rounded-3xl border border-[#E6E1DA] shadow-2xl p-6 md:p-8 max-w-sm w-full space-y-6 relative">
                <button 
                    type="button" 
                    @click="showQrModal = false"
                    class="absolute top-4 right-4 text-[#8C8275] hover:text-[#2D3330] transition-colors p-1 rounded-full hover:bg-[#FAF7F2] w-8 h-8 flex items-center justify-center cursor-pointer"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>

                <div class="text-center space-y-4 pt-2">
                    <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                        {{ t('admin_settings_qr_title') }}
                    </h3>
                    <p class="text-xs text-[#8C8275]">
                        {{ currentLanguage === 'en' ? 'Scan to pay or verify credentials' : 'Imbas untuk bayar atau sahkan butiran' }}
                    </p>
                    <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-[#C5A880]/30 inline-block shadow-inner">
                        <img 
                            :src="activeQrUrl" 
                            alt="QR Code" 
                            class="w-64 h-64 object-contain mx-auto rounded-lg bg-white p-2 border border-[#E6E1DA]"
                        />
                    </div>
                </div>

                <div class="bg-[#FAF7F2]/50 p-4 rounded-2xl border border-[#E6E1DA] text-xs text-[#2D3330] space-y-2">
                    <div class="flex justify-between">
                        <span class="text-[#8C8275]">{{ t('admin_settings_bank_name') }}:</span>
                        <span class="font-bold">{{ form.bank_name || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#8C8275]">{{ t('admin_settings_account_no') }}:</span>
                        <span class="font-bold font-mono">{{ form.bank_account_no || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#8C8275]">{{ t('admin_settings_account_name') }}:</span>
                        <span class="font-bold text-right">{{ form.bank_account_name || '-' }}</span>
                    </div>
                </div>

                <div class="flex justify-center pt-2">
                    <button 
                        type="button" 
                        @click="showQrModal = false"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-6 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer w-full text-center"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
