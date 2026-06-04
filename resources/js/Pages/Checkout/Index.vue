<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    cartItems: {
        type: Array,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
    qrCodeFile: {
        type: String,
        default: 'admin/uploads/qr_default.png',
    },
    userData: {
        type: Object,
        required: true,
    },
});

const { t } = useLocalization();

// Calculate grand total of checkout items
const grandTotal = computed(() => {
    return props.cartItems.reduce((sum, item) => sum + parseFloat(item.price) * parseInt(item.quantity), 0.00);
});

// 30% deposit calculation
const depositAmount = computed(() => {
    return grandTotal.value * 0.3;
});

// 70% balance calculation
const balanceAmount = computed(() => {
    return grandTotal.value * 0.7;
});

// Form state
const form = useForm({
    name: props.userData.full_name || '',
    phone: props.userData.phone || '',
    address: props.userData.address || '',
    delivery_date: '',
    delivery_time: '',
    receipt: null,
});

// Error handling helper
const fileError = ref('');

// Date limit helper (min 7 days from today)
const minDateString = computed(() => {
    const date = new Date();
    date.setDate(date.getDate() + 7);
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
});

function handleFileChange(event) {
    const file = event.target.files[0];
    fileError.value = '';
    if (file) {
        if (file.size > 4 * 1024 * 1024) {
            fileError.value = t('file_size_error') || 'File size must be less than 4MB.';
            form.receipt = null;
            return;
        }
        form.receipt = file;
    }
}

function submitCheckout() {
    form.post(route('checkout.place'), {
        forceFormData: true,
        onSuccess: () => {
            alert(t('toast_order_submitted') || 'Your order request has been submitted successfully.');
        },
        onError: (errors) => {
            if (errors.receipt) {
                fileError.value = errors.receipt;
            }
        }
    });
}
</script>

<template>
    <Head :title="t('secure_checkout')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .checkout-card {
            background: #ffffff;
            border-radius: 0px;
            padding: 30px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.01);
        }
        .form-input {
            width: 100%;
            border-radius: 0px;
            border: 1px solid #E6E1DA;
            padding: 12px 16px;
            font-size: 0.9rem;
            color: #2D3330;
            transition: all 0.2s ease;
            background: white;
        }
        .form-input:focus {
            border-color: #4A6B5D;
            outline: none;
            box-shadow: none;
        }
        .qr-placeholder {
            border: 1px solid #E6E1DA;
            border-radius: 0px;
            background: #FAF6F0;
        }
    </component>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between font-sans-modern">
                <div class="flex flex-col">
                    <span class="text-[10px] text-[#8C8275] font-bold uppercase tracking-widest mb-1">
                        <Link :href="route('cart.index')" class="hover:text-[#4A6B5D] transition-colors">{{ t('shopping_cart') }}</Link>
                        <span class="mx-2 text-[#E6E1DA]">/</span>
                        <span class="text-[#4A6B5D]">{{ t('secure_checkout') }}</span>
                    </span>
                    <h2 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                        {{ t('secure_checkout') }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[#FAF7F2] min-h-[calc(100vh-80px)] font-sans-modern">
            <div class="max-w-6xl mx-auto px-6">
                
                <div class="grid lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left: Details & Payment (8 cols) -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- Event Details Form -->
                        <form @submit.prevent="submitCheckout" class="space-y-6">
                            
                            <div class="checkout-card space-y-6">
                                <h3 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#EBEFEF] pb-3 flex items-center gap-2">
                                    <i class="fas fa-calendar-check text-[#4A6B5D] text-sm"></i> {{ t('event_delivery_details') }}
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Full Name -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('customer_name') }}</label>
                                        <input 
                                            type="text" 
                                            v-model="form.name" 
                                            class="form-input"
                                            placeholder="Your full name"
                                            required
                                        />
                                        <span v-if="form.errors.name" class="text-xs text-red-500 font-semibold">{{ form.errors.name }}</span>
                                    </div>

                                    <!-- Phone Number -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('phone_number') }}</label>
                                        <input 
                                            type="text" 
                                            v-model="form.phone" 
                                            class="form-input"
                                            placeholder="e.g. +60123456789"
                                            required
                                        />
                                        <span v-if="form.errors.phone" class="text-xs text-red-500 font-semibold">{{ form.errors.phone }}</span>
                                    </div>

                                    <!-- Delivery Date -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('delivery_event_date') }}</label>
                                        <input 
                                            type="date" 
                                            v-model="form.delivery_date" 
                                            :min="minDateString"
                                            class="form-input"
                                            required
                                        />
                                        <span class="text-[9px] text-[#8C8275] font-semibold uppercase tracking-wider block mt-1">
                                            <i class="fas fa-info-circle"></i> {{ t('policy_header_2') }} (Min 7 days).
                                        </span>
                                        <span v-if="form.errors.delivery_date" class="text-xs text-red-500 font-semibold">{{ form.errors.delivery_date }}</span>
                                    </div>

                                    <!-- Delivery Time -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('preferred_delivery_time') }}</label>
                                        <input 
                                            type="time" 
                                            v-model="form.delivery_time" 
                                            class="form-input"
                                            required
                                        />
                                        <span v-if="form.errors.delivery_time" class="text-xs text-red-500 font-semibold">{{ form.errors.delivery_time }}</span>
                                    </div>
                                </div>

                                <!-- Delivery Address -->
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('event_venue_address') }}</label>
                                    <textarea 
                                        v-model="form.address" 
                                        rows="3" 
                                        class="form-input"
                                        placeholder="Enter the complete address for catering delivery"
                                        required
                                    ></textarea>
                                    <span v-if="form.errors.address" class="text-xs text-red-500 font-semibold">{{ form.errors.address }}</span>
                                </div>
                            </div>

                            <!-- Payment Instructions & Receipt Upload -->
                            <div class="checkout-card space-y-6">
                                <h3 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#EBEFEF] pb-3 flex items-center gap-2">
                                    <i class="fas fa-receipt text-[#4A6B5D] text-sm"></i> {{ t('payment_slip_deposit') }}
                                </h3>

                                <div class="p-5 bg-[#FAF6F0] border border-[#E6E1DA] space-y-3">
                                    <span class="font-bold text-[#8C3A3A] text-xs uppercase tracking-wider block">Deposit Commitment:</span>
                                    <p class="text-xs text-[#5C6460] leading-relaxed font-light">
                                        {{ t('deposit_commitment_desc') }}
                                        <br>
                                        <strong class="text-[#8C3A3A] text-sm font-semibold block mt-2">RM {{ depositAmount.toFixed(2) }}</strong>
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                                    <!-- QR Display -->
                                    <div class="qr-placeholder p-6 text-center space-y-4">
                                        <span class="text-[10px] font-bold text-[#4A6B5D] uppercase tracking-widest block">{{ t('scan_to_pay') }}</span>
                                        <div class="inline-block p-3 bg-white border border-[#E6E1DA]">
                                            <!-- Dynamically load the QR file if it exists -->
                                            <img 
                                                v-if="qrCodeFile" 
                                                :src="'/' + qrCodeFile" 
                                                alt="QR Code" 
                                                class="w-48 h-48 object-contain mx-auto"
                                            />
                                            <div v-else class="w-48 h-48 bg-[#FAF7F2] flex flex-col items-center justify-center text-[#8C8275]">
                                                <i class="fas fa-qrcode text-4xl mb-2"></i>
                                                <span class="text-[10px] font-bold uppercase tracking-widest">{{ t('qr_not_configured') }}</span>
                                            </div>
                                        </div>
                                        <div class="text-[10px] text-[#8C8275] uppercase tracking-wider leading-relaxed font-semibold">
                                            <span class="text-[#2D3330] block">SmartServe Catering Enterprise</span>
                                            Maybank Account: 563064123456
                                        </div>
                                    </div>

                                    <!-- File Upload Form -->
                                    <div class="space-y-4">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('upload_payment_slip') }}</label>
                                        
                                        <div class="border border-dashed border-[#E6E1DA] hover:border-[#4A6B5D] p-6 text-center cursor-pointer transition-colors relative bg-white">
                                            <input 
                                                type="file" 
                                                @change="handleFileChange"
                                                accept="image/jpeg,image/png,image/jpg,application/pdf"
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                required
                                            />
                                            <div class="space-y-2 pointer-events-none">
                                                <div class="w-10 h-10 bg-[#FAF7F2] text-[#4A6B5D] flex items-center justify-center mx-auto text-base border border-[#E6E1DA]">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <span class="text-xs font-semibold text-[#2D3330] block uppercase tracking-wide">
                                                    {{ form.receipt ? form.receipt.name : t('select_receipt_file') }}
                                                </span>
                                                <span class="text-[9px] text-[#8C8275] uppercase tracking-wider block">
                                                    {{ t('accepted_formats_desc') }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <span v-if="fileError" class="text-xs text-red-500 font-semibold block">{{ fileError }}</span>
                                    </div>
                                </div>

                                <div class="border-t border-[#E6E1DA] pt-6">
                                    <button 
                                        type="submit" 
                                        class="w-full inline-flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-4 px-6 rounded-none text-xs uppercase tracking-widest transition-colors shadow-sm"
                                        :disabled="form.processing"
                                    >
                                        <i class="fas fa-shield-alt text-[10px]"></i> {{ t('confirm_booking_submit') }}
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>

                    <!-- Right: Checkout Items Summary (4 cols) -->
                    <div class="lg:col-span-4 sticky top-24 space-y-6 font-sans-modern">
                        <div class="checkout-card space-y-6">
                            <h3 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider border-b border-[#EBEFEF] pb-3">{{ t('selected_packages') }}</h3>

                            <div class="divide-y divide-[#EBEFEF] max-h-80 overflow-y-auto pr-1">
                                <div v-for="item in cartItems" :key="item.id" class="py-4 space-y-2 first:pt-0">
                                    <div class="flex justify-between items-start gap-2">
                                        <div>
                                            <span class="font-normal text-[#2D3330] font-serif-luxury text-sm uppercase tracking-wide block">{{ item.package_name }}</span>
                                            <span class="text-xs text-[#8C8275]">{{ item.quantity }} {{ t('pax') }}</span>
                                        </div>
                                        <span class="font-semibold text-xs text-[#2D3330] whitespace-nowrap">
                                            RM {{ (parseFloat(item.price) * parseInt(item.quantity)).toFixed(2) }}
                                        </span>
                                    </div>
                                    <!-- Selected Add-ons -->
                                    <div v-if="item.selected_addons && item.selected_addons.length > 0" class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="addon in item.selected_addons" 
                                            :key="addon"
                                            class="bg-[#FAF6F0] text-[9px] text-[#5C6460] font-medium px-2 py-0.5 border border-[#E6E1DA]"
                                        >
                                            + {{ addon }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-[#E6E1DA] pt-4 space-y-3 text-xs uppercase tracking-wider text-[#8C8275]">
                                <div class="flex justify-between">
                                    <span>{{ t('grand_total') }}</span>
                                    <span class="font-bold text-[#2D3330]">RM {{ grandTotal.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-[#8C3A3A] font-semibold">
                                    <span>{{ t('deposit_required') }}</span>
                                    <span>RM {{ depositAmount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{ t('balance_due') }}</span>
                                    <span>RM {{ balanceAmount.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
