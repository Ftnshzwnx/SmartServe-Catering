<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

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

const activeTab = ref('company'); // 'company', 'zones', 'profile', 'security'
const qrPreviewUrl = ref('');
const fileError = ref('');

// Company Form state
const form = useForm({
    business_name: props.settings.business_name || 'SmartServe Catering',
    deposit_percentage: props.settings.deposit_percentage || 30,
    cancellation_policy_days: props.settings.cancellation_policy_days || 7,
    grace_period_days: props.settings.grace_period_days || 2,
    min_lead_time_days: props.settings.min_lead_time_days || 7,
    min_order_value: props.settings.min_order_value || 100,
    contact_phone: props.settings.contact_phone || '0123456789',
    contact_email: props.settings.contact_email || 'support@smartservecatering.com',
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
                toast('Delivery zone updated successfully.');
                showZoneModal.value = false;
            }
        });
    } else {
        zoneForm.post(route('admin.delivery-zones.store'), {
            onSuccess: () => {
                toast('Delivery zone created successfully.');
                showZoneModal.value = false;
            }
        });
    }
}

async function deleteZone(id) {
    if (await confirm('Are you sure you want to delete this delivery zone?', 'Delete Delivery Zone')) {
        zoneForm.delete(route('admin.delivery-zones.delete', { id: id }), {
            onSuccess: () => {
                toast('Delivery zone deleted successfully.');
            }
        });
    }
}

function handleFileChange(event) {
    const file = event.target.files[0];
    fileError.value = '';
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            fileError.value = 'File size must be less than 2MB.';
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
            toast('Settings updated successfully.');
            form.reset('qr_code');
            qrPreviewUrl.value = '';
        }
    });
}

function submitProfile() {
    profileForm.patch(route('profile.update'), {
        onSuccess: () => {
            toast('Profile updated successfully.');
        }
    });
}

function submitPassword() {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast('Password updated successfully.');
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
        title="Catering Business Settings"
        header-title="Business Settings"
        header-desc="Configure company profile information, dynamic business policies, and payment credentials."
    >
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            
            <!-- Left Side: Tabs Navigation Selector -->
            <div class="md:col-span-1 bg-white rounded-3xl border border-[#E6E1DA] p-4 shadow-xs space-y-1 h-fit">
                <button 
                    type="button"
                    @click="activeTab = 'company'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer text-left"
                    :class="activeTab === 'company' ? 'bg-[#4A6B5D]/10 text-[#4A6B5D]' : 'text-[#8C8275] hover:bg-[#FAF7F2] hover:text-[#2D3330]'"
                >
                    <i class="fas fa-building text-sm w-5 text-center"></i>
                    Company Profile
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'zones'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer text-left"
                    :class="activeTab === 'zones' ? 'bg-[#4A6B5D]/10 text-[#4A6B5D]' : 'text-[#8C8275] hover:bg-[#FAF7F2] hover:text-[#2D3330]'"
                >
                    <i class="fas fa-truck text-sm w-5 text-center"></i>
                    Delivery Zones
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'profile'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer text-left"
                    :class="activeTab === 'profile' ? 'bg-[#4A6B5D]/10 text-[#4A6B5D]' : 'text-[#8C8275] hover:bg-[#FAF7F2] hover:text-[#2D3330]'"
                >
                    <i class="fas fa-user text-sm w-5 text-center"></i>
                    Personal Account
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'security'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer text-left"
                    :class="activeTab === 'security' ? 'bg-[#4A6B5D]/10 text-[#4A6B5D]' : 'text-[#8C8275] hover:bg-[#FAF7F2] hover:text-[#2D3330]'"
                >
                    <i class="fas fa-shield-alt text-sm w-5 text-center"></i>
                    Security
                </button>
            </div>

            <!-- Right Side: Active Tab Content Panel -->
            <div class="md:col-span-3">
                
                <!-- 1. Company Profile Tab -->
                <div v-show="activeTab === 'company'" class="space-y-6 animate-fade-in">
                    <form @submit.prevent="submitSettings" class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            <!-- Left: General & Payments -->
                            <div class="lg:col-span-2 space-y-6">
                                <!-- General & Policies -->
                                <div class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs space-y-6">
                                    <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">General & Policies</h3>
                                    
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Business / Company Name</label>
                                        <input 
                                            type="text" 
                                            v-model="form.business_name" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            placeholder="e.g. SmartServe Catering Enterprise"
                                            required
                                        />
                                        <span v-if="form.errors.business_name" class="text-xs text-red-500 font-semibold block">{{ form.errors.business_name }}</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Deposit Percentage -->
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-bold text-[#8C8275] uppercase tracking-wider block">Deposit Required (%)</label>
                                            <input 
                                                type="number" 
                                                v-model="form.deposit_percentage" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="0" 
                                                max="100"
                                                required
                                            />
                                            <span v-if="form.errors.deposit_percentage" class="text-xs text-red-500 font-semibold block">{{ form.errors.deposit_percentage }}</span>
                                        </div>

                                        <!-- Cancellation Policy Days -->
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-bold text-[#8C8275] uppercase tracking-wider block">Cancellation Notice (Days)</label>
                                            <input 
                                                type="number" 
                                                v-model="form.cancellation_policy_days" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="0" 
                                                required
                                            />
                                            <span v-if="form.errors.cancellation_policy_days" class="text-xs text-red-500 font-semibold block">{{ form.errors.cancellation_policy_days }}</span>
                                        </div>

                                        <!-- Grace Period Days -->
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-bold text-[#8C8275] uppercase tracking-wider block">Grace Period (Days)</label>
                                            <input 
                                                type="number" 
                                                v-model="form.grace_period_days" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="0" 
                                                required
                                            />
                                            <span v-if="form.errors.grace_period_days" class="text-xs text-red-500 font-semibold block">{{ form.errors.grace_period_days }}</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                                        <!-- Min Lead Time -->
                                        <div class="space-y-1.5">
                                            <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Minimum Lead Time (Days)</label>
                                            <input 
                                                type="number" 
                                                v-model="form.min_lead_time_days" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="1" 
                                                required
                                            />
                                            <span v-if="form.errors.min_lead_time_days" class="text-xs text-red-500 font-semibold block">{{ form.errors.min_lead_time_days }}</span>
                                        </div>

                                        <!-- Min Order Value -->
                                        <div class="space-y-1.5">
                                            <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Minimum Order Value (RM)</label>
                                            <input 
                                                type="number" 
                                                v-model="form.min_order_value" 
                                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                min="0" 
                                                required
                                            />
                                            <span v-if="form.errors.min_order_value" class="text-xs text-red-500 font-semibold block">{{ form.errors.min_order_value }}</span>
                                        </div>
                                    </div>
                                </div>



                                <!-- Payment Credentials -->
                                <div class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs space-y-6">
                                    <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">Payment Credentials</h3>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                                        <!-- Bank Account Info -->
                                        <div class="space-y-4">
                                            <span class="text-xs font-bold text-[#C5A880] uppercase tracking-widest block border-b border-[#E6E1DA] pb-1.5">Manual Bank Transfer Info</span>
                                            
                                            <div class="space-y-3">
                                                <div class="space-y-1">
                                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-wider block">Bank Name</label>
                                                    <input 
                                                        type="text" 
                                                        v-model="form.bank_name" 
                                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-2.5 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                        placeholder="e.g. Maybank"
                                                        required
                                                    />
                                                    <span v-if="form.errors.bank_name" class="text-xs text-red-500 font-semibold block">{{ form.errors.bank_name }}</span>
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-wider block">Account Number</label>
                                                    <input 
                                                        type="text" 
                                                        v-model="form.bank_account_no" 
                                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-2.5 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                        placeholder="e.g. 164213456789"
                                                        required
                                                    />
                                                    <span v-if="form.errors.bank_account_no" class="text-xs text-red-500 font-semibold block">{{ form.errors.bank_account_no }}</span>
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-wider block">Account Holder Name</label>
                                                    <input 
                                                        type="text" 
                                                        v-model="form.bank_account_name" 
                                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-2.5 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                                        placeholder="e.g. SmartServe Catering"
                                                        required
                                                    />
                                                    <span v-if="form.errors.bank_account_name" class="text-xs text-red-500 font-semibold block">{{ form.errors.bank_account_name }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- QR Code Info -->
                                        <div class="space-y-4">
                                            <span class="text-xs font-bold text-[#C5A880] uppercase tracking-widest block border-b border-[#E6E1DA] pb-1.5">Scan to Pay QR Code</span>
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                                                <div class="border border-dashed border-[#C5A880]/30 rounded-2xl bg-[#FAF7F2] p-3 text-center">
                                                    <div class="inline-block p-1 bg-white border border-[#E6E1DA] rounded-xl shadow-xs">
                                                        <img 
                                                            v-if="qrPreviewUrl" 
                                                            :src="qrPreviewUrl" 
                                                            alt="New QR" 
                                                            class="w-24 h-24 object-contain mx-auto"
                                                        />
                                                        <img 
                                                            v-else-if="settings.qr_code_path" 
                                                            :src="'/' + settings.qr_code_path" 
                                                            alt="Current QR" 
                                                            class="w-24 h-24 object-contain mx-auto"
                                                        />
                                                        <div v-else class="w-24 h-24 bg-[#FAF7F2] rounded-xl flex flex-col items-center justify-center text-[#8C8275]">
                                                            <i class="fas fa-qrcode text-xl mb-1"></i>
                                                            <span class="text-[8px] font-bold">NO FILE</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-wider block">Upload QR Image</label>
                                                    <div class="border-2 border-dashed border-[#E6E1DA] hover:border-[#4A6B5D] rounded-xl p-3 text-center cursor-pointer transition-colors relative bg-[#FAF7F2]/30">
                                                        <input 
                                                            type="file" 
                                                            @change="handleFileChange"
                                                            accept="image/jpeg,image/png,image/jpg"
                                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                        />
                                                        <div class="space-y-1 pointer-events-none text-xs">
                                                            <div class="text-[#4A6B5D] text-sm mx-auto">
                                                                    <i class="fas fa-image"></i>
                                                            </div>
                                                            <span class="font-bold text-[#2D3330] text-[10px] block truncate">
                                                                {{ form.qr_code ? form.qr_code.name : 'Choose file' }}
                                                            </span>
                                                            <span class="text-[8px] text-[#8C8275] block">
                                                                Max 2MB
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <span v-if="fileError" class="text-[10px] text-red-500 font-semibold block">{{ fileError }}</span>
                                                    <span v-if="form.errors.qr_code" class="text-[10px] text-red-500 font-semibold block">{{ form.errors.qr_code }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Contact Details -->
                            <div class="lg:col-span-1 bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs space-y-6">
                                <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">Contact Details</h3>
                                
                                <div class="space-y-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Contact Phone Number</label>
                                        <input 
                                            type="text" 
                                            v-model="form.contact_phone" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            placeholder="e.g. +6012-3456789"
                                            required
                                        />
                                        <span v-if="form.errors.contact_phone" class="text-xs text-red-500 font-semibold block">{{ form.errors.contact_phone }}</span>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Support Email Address</label>
                                        <input 
                                            type="email" 
                                            v-model="form.contact_email" 
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                            placeholder="e.g. sales@smartserve.com"
                                            required
                                        />
                                        <span v-if="form.errors.contact_email" class="text-xs text-red-500 font-semibold block">{{ form.errors.contact_email }}</span>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Physical Business Address</label>
                                        <textarea 
                                            v-model="form.business_address" 
                                            rows="5"
                                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] resize-none" 
                                            placeholder="Enter physical location..."
                                            required
                                        ></textarea>
                                        <span v-if="form.errors.business_address" class="text-xs text-red-500 font-semibold block">{{ form.errors.business_address }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Actions -->
                        <div class="bg-white rounded-3xl border border-[#E6E1DA] p-4 px-6 md:px-8 shadow-xs flex justify-end">
                            <button 
                                type="submit" 
                                class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-6 py-3 shadow transition-colors cursor-pointer rounded-xl text-xs uppercase tracking-widest"
                                :disabled="form.processing"
                            >
                                <i class="fas fa-save mr-1.5"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2. Personal Account Tab -->
                <div v-show="activeTab === 'profile'" class="space-y-6 animate-fade-in">
                    <div class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs space-y-6">
                        
                        <div>
                            <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">Personal Account</h3>
                            <p class="text-xs text-[#8C8275] mt-1.5">Update your personal account display name and login email address.</p>
                        </div>

                        <!-- User Profile Banner Card -->
                        <div class="flex items-center gap-4 bg-[#FAF7F2] p-6 rounded-2xl border border-[#E6E1DA]/60">
                            <div class="w-16 h-16 rounded-full bg-[#C5A880] text-white flex items-center justify-center font-bold text-2xl shadow-xs select-none">
                                {{ (user?.name || 'A').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-[#2D3330] capitalize leading-none mb-1">{{ user?.name || 'Admin' }}</h4>
                                <p class="text-xs text-[#8C8275]">{{ user?.email || 'admin@smartservecatering.com' }}</p>
                                <span class="inline-block mt-2 px-2.5 py-1 text-[9px] uppercase tracking-wider font-extrabold bg-[#4A6B5D]/10 text-[#4A6B5D] rounded-full">Administrator</span>
                            </div>
                        </div>

                        <!-- Profile edit form -->
                        <form @submit.prevent="submitProfile" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Short Name / Username</label>
                                    <input 
                                        type="text" 
                                        v-model="profileForm.name" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        required
                                    />
                                    <span v-if="profileForm.errors.name" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.name }}</span>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Full Name</label>
                                    <input 
                                        type="text" 
                                        v-model="profileForm.full_name" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        required
                                    />
                                    <span v-if="profileForm.errors.full_name" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.full_name }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Email Address</label>
                                    <input 
                                        type="email" 
                                        v-model="profileForm.email" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        required
                                    />
                                    <span v-if="profileForm.errors.email" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.email }}</span>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Phone Number</label>
                                    <input 
                                        type="text" 
                                        v-model="profileForm.phone" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        required
                                    />
                                    <span v-if="profileForm.errors.phone" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.phone }}</span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Physical Business Address</label>
                                <textarea 
                                    v-model="profileForm.address" 
                                    rows="4"
                                    class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] resize-none" 
                                    required
                                ></textarea>
                                <span v-if="profileForm.errors.address" class="text-xs text-red-500 font-semibold block">{{ profileForm.errors.address }}</span>
                            </div>

                            <div class="border-t border-[#E6E1DA] pt-6 flex justify-end">
                                <button 
                                    type="submit" 
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-6 py-3 shadow transition-colors cursor-pointer rounded-xl text-xs uppercase tracking-widest"
                                    :disabled="profileForm.processing"
                                >
                                    <i class="fas fa-save mr-1.5"></i> Save Changes
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                <!-- 3. Security (Passwords) Tab -->
                <div v-show="activeTab === 'security'" class="space-y-6 animate-fade-in">
                    <div class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs space-y-6">
                        
                        <div>
                            <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">Security & Passwords</h3>
                            <p class="text-xs text-[#8C8275] mt-1.5">Ensure your account is using a long, random password to stay secure.</p>
                        </div>

                        <!-- Password update form -->
                        <form @submit.prevent="submitPassword" class="space-y-6">
                            <div class="space-y-4 max-w-xl">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Current Password</label>
                                    <input 
                                        type="password" 
                                        v-model="passwordForm.current_password" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        autocomplete="current-password"
                                        required
                                    />
                                    <span v-if="passwordForm.errors.current_password" class="text-xs text-red-500 font-semibold block">{{ passwordForm.errors.current_password }}</span>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">New Password</label>
                                    <input 
                                        type="password" 
                                        v-model="passwordForm.password" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        autocomplete="new-password"
                                        required
                                    />
                                    <span v-if="passwordForm.errors.password" class="text-xs text-red-500 font-semibold block">{{ passwordForm.errors.password }}</span>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider block">Confirm New Password</label>
                                    <input 
                                        type="password" 
                                        v-model="passwordForm.password_confirmation" 
                                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                        autocomplete="new-password"
                                        required
                                    />
                                    <span v-if="passwordForm.errors.password_confirmation" class="text-xs text-red-500 font-semibold block">{{ passwordForm.errors.password_confirmation }}</span>
                                </div>
                            </div>

                            <div class="border-t border-[#E6E1DA] pt-6 flex justify-end">
                                <button 
                                    type="submit" 
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-6 py-3 shadow transition-colors cursor-pointer rounded-xl text-xs uppercase tracking-widest"
                                    :disabled="passwordForm.processing"
                                >
                                    <i class="fas fa-key mr-1.5"></i> Update Password
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                <!-- 4. Delivery Zones CRUD Tab -->
                <div v-show="activeTab === 'zones'" class="space-y-6 animate-fade-in font-sans-modern">
                    <div class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs space-y-6">
                        <div class="flex justify-between items-center border-b border-[#E6E1DA] pb-4">
                            <div>
                                <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-0 pb-0">Delivery Zones & Fees</h3>
                                <p class="text-xs text-[#8C8275] mt-1">Configure available delivery regions and their flat fees for customer orders.</p>
                            </div>
                            <button
                                type="button"
                                @click="openAddZoneModal"
                                class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5 cursor-pointer"
                            >
                                <i class="fas fa-plus-circle text-sm"></i> Add Zone
                            </button>
                        </div>

                        <!-- Zones Table -->
                        <div class="overflow-x-auto rounded-2xl border border-[#E6E1DA] bg-white">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-[#FAF7F2] text-[#8C8275] border-b border-[#E6E1DA] font-bold uppercase tracking-wider">
                                        <th class="p-4">Zone Name</th>
                                        <th class="p-4">Delivery Fee (RM)</th>
                                        <th class="p-4 text-center w-32">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E6E1DA] font-semibold text-[#2D3330]">
                                    <tr v-for="zone in props.deliveryZones" :key="zone.id" class="hover:bg-[#FAF7F2]/40 transition-colors">
                                        <td class="p-4">{{ zone.name }}</td>
                                        <td class="p-4">RM {{ parseFloat(zone.fee).toFixed(2) }}</td>
                                        <td class="p-4 text-center">
                                            <div class="flex justify-center gap-3">
                                                <button
                                                    type="button"
                                                    @click="openEditZoneModal(zone)"
                                                    class="text-amber-600 hover:text-amber-800 transition-colors p-1 cursor-pointer"
                                                    title="Edit Zone"
                                                >
                                                    <i class="fas fa-edit text-sm"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteZone(zone.id)"
                                                    class="text-rose-600 hover:text-rose-800 transition-colors p-1 cursor-pointer"
                                                    title="Delete Zone"
                                                >
                                                    <i class="fas fa-trash-alt text-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="props.deliveryZones.length === 0">
                                        <td colspan="3" class="p-8 text-center text-[#8C8275] italic">No delivery zones configured yet.</td>
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
            <div class="bg-white rounded-3xl border border-[#E6E1DA] shadow-2xl p-6 md:p-8 max-w-md w-full space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                        {{ editingZoneId ? 'Edit Delivery Zone' : 'Add New Delivery Zone' }}
                    </h3>
                    <p class="text-xs text-[#8C8275] mt-1">Specify name and flat shipping fee rate for this area.</p>
                </div>

                <form @submit.prevent="submitZoneForm" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">Zone Name</label>
                        <input 
                            type="text" 
                            v-model="zoneForm.name" 
                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                            placeholder="e.g. Dungun"
                            required
                        />
                        <span v-if="zoneForm.errors.name" class="text-xs text-red-500 font-semibold block">{{ zoneForm.errors.name }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">Delivery Fee (RM)</label>
                        <input 
                            type="number" 
                            v-model="zoneForm.fee" 
                            class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                            placeholder="e.g. 120.00"
                            min="0"
                            step="0.01"
                            required
                        />
                        <span v-if="zoneForm.errors.fee" class="text-xs text-red-500 font-semibold block">{{ zoneForm.errors.fee }}</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button 
                            type="button" 
                            @click="showZoneModal = false"
                            class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer"
                            :disabled="zoneForm.processing"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
