<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    cartCount: {
        type: Number,
        default: 0,
    },
    ordersCount: {
        type: Number,
        default: 0,
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
    featuredPackages: {
        type: Array,
        default: () => [],
    },
    pendingReviewOrders: {
        type: Array,
        default: () => [],
    },
});

const { t, currentLanguage } = useLocalization();
const page = usePage();
const qrCodePath = computed(() => page.props.settings?.qr_code_path || null);

const showQrModal = ref(false);
function resolveQrPath(path) {
    if (!path) return '';
    if (path.startsWith('data:') || path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) {
        return path;
    }
    return '/' + path;
}


const greeting = computed(() => {
    if (page.props.flash?.just_registered) {
        return currentLanguage.value === 'en' ? 'Welcome' : 'Selamat datang';
    }
    return t('welcome_back');
});


const depositPercent = computed(() => {
    return parseFloat(page.props.settings?.deposit_percentage || 30);
});

function getDepositAmount(totalPrice) {
    return parseFloat(totalPrice) * (depositPercent.value / 100);
}

function getBalanceAmount(totalPrice) {
    return parseFloat(totalPrice) * ((100 - depositPercent.value) / 100);
}

const shouldShowActiveOrderUpload = computed(() => {
    if (!activeOrder.value) return false;
    const status = activeOrder.value.status;
    if (['Deposit Rejected', 'Balance Rejected', 'Delivered'].includes(status)) return true;
    if (status === 'Pending' && activeOrder.value.is_custom_proposal && !activeOrder.value.payment_proof) return true;
    return false;
});

function shouldShowQr(order) {
    if (!order || !qrCodePath.value) return false;
    const status = order.status;
    if (status === 'Delivered' || status === 'Balance Rejected') return true;
    if (status === 'Pending' && order.is_custom_proposal && !order.payment_proof) return true;
    if (status === 'Deposit Rejected') return true;
    return false;
}

function isDepositPayment(order) {
    if (!order) return false;
    return order.status === 'Pending' || order.status === 'Deposit Rejected';
}

// Review form
const reviewOrder = computed(() => props.pendingReviewOrders?.[0] || null);
const hoverRating = ref(0);
const reviewForm = useForm({
    rating: 0,
    review_text: '',
});
function submitReview() {
    if (!reviewOrder.value) return;
    reviewForm.post(route('orders.review', reviewOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset();
            router.reload({ only: ['pendingReviewOrders'] });
        }
    });
}

function downloadQr() {
    if (!qrCodePath.value) return;
    const link = document.createElement('a');
    link.href = resolveQrPath(qrCodePath.value);
    link.download = 'SmartServe-Payment-QR.png';
    link.click();
}

// Active Order Tracker computeds
const activeOrder = computed(() => {
    return props.recentOrders.find(order => !['Completed', 'Cancelled'].includes(order.status));
});

const activeOrderStep = computed(() => {
    if (!activeOrder.value) return 0;
    const status = activeOrder.value.status;
    if (['Pending', 'Deposit Rejected'].includes(status)) return 1;
    if (['Confirmed'].includes(status)) return 2;
    if (['Delivered', 'Balance Rejected', 'Payment Submitted'].includes(status)) return 3;
    if (['Completed'].includes(status)) return 4;
    return 1;
});

const activeOrderCountdown = computed(() => {
    if (!activeOrder.value || !activeOrder.value.delivery_date) return null;
    
    const targetDate = new Date(activeOrder.value.delivery_date);
    targetDate.setHours(0, 0, 0, 0);
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const diffTime = targetDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
        return {
            days: Math.abs(diffDays),
            label: t('event_completed_days') || 'Event occurred',
            class: 'text-[#8C8275] bg-[#FAF6F0] border-[#E6E1DA]'
        };
    } else if (diffDays === 0) {
        return {
            days: 0,
            label: t('event_today') || 'Event is Today!',
            class: 'text-[#8C3A3A] bg-[#FDF2F2] border-[#FADCDD] animate-pulse'
        };
    } else {
        return {
            days: diffDays,
            label: diffDays === 1 ? (t('day_remaining') || 'Day Remaining') : (t('days_remaining') || 'Days Remaining'),
            class: 'text-[#4A6B5D] bg-[#EBEFEF] border-[#D1DEDB]'
        };
    }
});

// Re-upload receipt handling
const fileErrors = ref({});
const processingReupload = ref({});
const showSuccessToast = ref(false);
const toastMessage = ref('');

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('verified') === '1') {
        toastMessage.value = t('email_verified_success') || 'Email verified successfully! Welcome to your dashboard.';
        showSuccessToast.value = true;
        setTimeout(() => {
            showSuccessToast.value = false;
        }, 4000);

        // Remove verified parameter from URL query string
        const url = new URL(window.location.href);
        url.searchParams.delete('verified');
        window.history.replaceState({}, '', url);
    }
});

function triggerFileSelect(orderId) {
    document.getElementById(`reupload-file-${orderId}`).click();
}

function triggerBalanceFileSelect(orderId) {
    const el = document.getElementById(`balance-file-${orderId}`);
    if (el) el.click();
}

function handleReceiptSelect(event, orderId, type) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (file.size > 4 * 1024 * 1024) {
        fileErrors.value[orderId] = t('file_size_error') || 'File size must be less than 4MB.';
        return;
    }
    
    fileErrors.value[orderId] = '';
    processingReupload.value[orderId] = true;
    
    const formData = new FormData();
    formData.append('receipt', file);
    formData.append('type', type);
    
    router.post(route('orders.reupload', { id: orderId }), formData, {
        forceFormData: true,
        onSuccess: () => {
            toastMessage.value = t('toast_receipt_uploaded') || 'New receipt uploaded successfully!';
            showSuccessToast.value = true;
            setTimeout(() => {
                showSuccessToast.value = false;
            }, 3000);
            processingReupload.value[orderId] = false;
        },
        onError: (err) => {
            fileErrors.value[orderId] = err.receipt || 'Failed to upload receipt.';
            processingReupload.value[orderId] = false;
        }
    });
}

function getStatusBadge(status) {
    switch (status) {
        case 'Pending':
            return 'bg-[#FAF6F0] text-[#8C8275] border-[#E6E1DA]';
        case 'Confirmed':
            return 'bg-[#EBEFEF] text-[#4A6B5D] border-[#D1DEDB]';
        case 'Payment Submitted':
            return 'bg-[#FAF7F2] text-[#4A6B5D] border-[#E6E1DA]';
        case 'Delivered':
        case 'Completed':
            return 'bg-[#EBEFEF] text-[#4A6B5D] border-[#D1DEDB]';
        case 'Deposit Rejected':
        case 'Balance Rejected':
            return 'bg-[#FDF2F2] text-[#8C3A3A] border-[#FADCDD]';
        case 'Cancelled':
            return 'bg-zinc-100 text-zinc-500 border-zinc-200';
        default:
            return 'bg-zinc-100 text-zinc-500 border-zinc-200';
    }
}

function getTranslatedStatus(status) {
    switch (status) {
        case 'Pending':
            return t('pending') || 'Pending';
        case 'Confirmed':
            return t('confirmed') || 'Confirmed';
        case 'Payment Submitted':
            return t('awaiting_verification') || 'Awaiting Verification';
        case 'Delivered':
            return t('delivered_tab') || 'Delivered';
        case 'Completed':
            return t('completed_tab') || 'Completed';
        case 'Deposit Rejected':
            return t('deposit_rejected') || 'Deposit Rejected';
        case 'Balance Rejected':
            return t('balance_rejected') || 'Balance Rejected';
        case 'Cancelled':
            return t('cancelled_tab') || 'Cancelled';
        default:
            return status;
    }
}
</script>

<template>
    <Head :title="t('dashboard')" />

    <!-- Style injection for Outfit / Cormorant / Plus Jakarta fonts and premium elements -->
    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .banner-gradient {
            background: linear-gradient(135deg, #4A6B5D 0%, #364F44 100%);
        }
        .action-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .action-card:hover {
            transform: translateY(-4px);
            border-color: #4A6B5D;
            box-shadow: 0 12px 20px -8px rgba(74, 107, 93, 0.15);
        }
        .progress-dot {
            width: 10px;
            height: 10px;
            border-radius: 16px;
            z-index: 10;
        }
        .toast-notification {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #2D3330;
            color: #FAF7F2;
            border-left: 4px solid #4A6B5D;
            padding: 16px 24px;
            z-index: 100;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
        }
    </component>

    <AuthenticatedLayout
        :header-title="t('customer_dashboard_title')"
        :header-desc="t('welcome_desc')"
    >

        <div class="font-sans-modern">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-10">
                
                <!-- Welcome Banner -->
                <div class="banner-gradient rounded-lg sm:rounded-2xl p-4 sm:p-6 lg:p-8 text-white relative overflow-hidden shadow-md">
                    <!-- Subtle oatmeal circle background element -->
                    <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-white/5 blur-2xl"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <h3 class="text-base sm:text-2xl md:text-3xl lg:text-4xl font-light font-serif-luxury mb-2 tracking-wide">
                                {{ greeting }}, {{ $page.props.auth.user.name }}!
                            </h3>
                            <p class="text-[#E2ECE8] text-[10px] sm:text-xs lg:text-sm tracking-wide uppercase font-light max-w-xl">
                                {{ t('welcome_desc') }}
                            </p>
                        </div>
                        <div>
                            <Link 
                                :href="route('menu.index')"
                                class="inline-flex items-center gap-1.5 bg-[#FAF7F2] hover:bg-[#FAF7F2]/90 text-[#4A6B5D] text-[9px] sm:text-xs font-semibold uppercase tracking-widest px-3 py-1.5 sm:px-5 sm:py-2.5 rounded-lg shadow-sm transition-all duration-200"
                            >
                                <i class="fas fa-utensils"></i> {{ t('order_now') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Active Order Tracker -->
                <div v-if="activeOrder" class="bg-white rounded-lg sm:rounded-2xl p-3 sm:p-5 lg:p-6 border border-[#E6E1DA] shadow-sm space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#FAF6F0] pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-lg">
                                <i class="fas fa-truck-loading"></i>
                            </div>
                            <div>
                                <h4 class="font-normal text-[#2D3330] text-sm sm:text-base md:text-lg font-serif-luxury tracking-wide">{{ t('active_order_tracker') }}</h4>
                                <p class="text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest mt-0.5">
                                    {{ t('order_num') }} #{{ activeOrder.id }} — {{ activeOrder.package_name }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 text-[10px] font-semibold border rounded-full uppercase tracking-widest whitespace-nowrap" :class="getStatusBadge(activeOrder.status)">
                                {{ getTranslatedStatus(activeOrder.status) }}
                            </span>
                            <Link 
                                :href="route('orders.show', { id: activeOrder.id })"
                                class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#4A6B5D] hover:text-[#3D574B] flex items-center gap-0.5 font-sans-modern"
                            >
                                {{ t('view_details') }} <i class="fas fa-chevron-right text-[9px]"></i>
                            </Link>
                        </div>
                    </div>

                    <!-- Progress Bar Timeline -->
                    <div class="relative py-4">
                        <div class="relative flex items-center justify-between w-full">
                            <!-- Track bar line background -->
                            <div class="absolute left-0 right-0 h-0.5 bg-[#E6E1DA] top-1/2 -translate-y-1/2"></div>
                            <!-- Track bar line active progress -->
                            <div 
                                class="absolute left-0 h-0.5 bg-[#4A6B5D] top-1/2 -translate-y-1/2 transition-all duration-300"
                                :style="{
                                    width: 
                                        activeOrderStep === 1 ? '0%' :
                                        activeOrderStep === 2 ? '33.33%' :
                                        activeOrderStep === 3 ? '66.66%' :
                                        activeOrderStep === 4 ? '100%' : '0%'
                                }"
                            ></div>

                            <!-- Step 1: Placed -->
                            <div class="flex flex-col items-center gap-2 relative">
                                <div class="progress-dot border border-slate-300 animate-pulse" :class="activeOrderStep >= 1 ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                <span class="text-[8px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-tight sm:tracking-wider mt-1 text-center">{{ t('timeline_step_1') }}</span>
                            </div>

                            <!-- Step 2: Confirmed -->
                            <div class="flex flex-col items-center gap-2 relative">
                                <div class="progress-dot border border-slate-300" :class="activeOrderStep >= 2 ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                <span class="text-[8px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-tight sm:tracking-wider mt-1 text-center">{{ t('timeline_step_2') }}</span>
                            </div>

                            <!-- Step 3: Delivered -->
                            <div class="flex flex-col items-center gap-2 relative">
                                <div class="progress-dot border border-slate-300" :class="activeOrderStep >= 3 ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                <span class="text-[8px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-tight sm:tracking-wider mt-1 text-center">{{ t('timeline_step_3') }}</span>
                            </div>

                            <!-- Step 4: Completed -->
                            <div class="flex flex-col items-center gap-2 relative">
                                <div class="progress-dot border border-slate-300" :class="activeOrderStep >= 4 ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                <span class="text-[8px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-tight sm:tracking-wider mt-1 text-center">{{ t('timeline_step_4') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rejected, Delivered or Pending custom proposal deposit Upload Panel (deposit/balance) -->
                    <div v-if="shouldShowActiveOrderUpload" 
                        class="rounded-2xl border overflow-hidden"
                        :class="activeOrder.status.includes('Rejected') ? 'border-[#FADCDD]' : 'border-[#E6E1DA]'"
                    >
                        <!-- Header row -->
                        <div class="p-4 flex flex-col sm:flex-row sm:items-start justify-between gap-4"
                            :class="activeOrder.status.includes('Rejected') ? 'bg-[#FDF2F2]' : 'bg-[#FAF7F2]'"
                        >
                            <div class="space-y-1">
                                <span class="font-bold uppercase tracking-wider text-[10px] flex items-center gap-1.5"
                                    :class="activeOrder.status.includes('Rejected') ? 'text-[#8C3A3A]' : 'text-[#4A6B5D]'"
                                >
                                    <i class="fas fa-exclamation-triangle" v-if="activeOrder.status.includes('Rejected')"></i>
                                    <i class="fas fa-file-invoice-dollar" v-else></i>
                                    {{ activeOrder.status.includes('Rejected') ? t('action_required') : (isDepositPayment(activeOrder) ? t('pay_30_deposit') : t('pay_remaining_balance')) }} ({{ getTranslatedStatus(activeOrder.status) }})
                                </span>
                                <p class="text-xs font-light leading-relaxed"
                                    :class="activeOrder.status.includes('Rejected') ? 'text-[#8C3A3A]' : 'text-[#5C6460]'"
                                >
                                    <span v-if="activeOrder.status === 'Delivered' || activeOrder.status === 'Balance Rejected'">
                                        {{ t('scan_qr_balance_desc').replace('{balance}', getBalanceAmount(activeOrder.total_price).toFixed(2)) }}
                                    </span>
                                    <span v-else-if="activeOrder.status === 'Pending' && activeOrder.is_custom_proposal && !activeOrder.payment_proof">
                                        {{ t('custom_proposal_approved_deposit_desc').replace('{depositPercent}', depositPercent).replace('{depositAmount}', getDepositAmount(activeOrder.total_price).toFixed(2)) }}
                                    </span>
                                    <span v-else-if="activeOrder.admin_note" class="font-medium">"{{ activeOrder.admin_note }}"</span>
                                    <span v-else>{{ t('reupload_rejected_slip') }}</span>
                                </p>
                            </div>
                            <div class="relative shrink-0">
                                <input 
                                    type="file" 
                                    :id="`reupload-file-${activeOrder.id}`" 
                                    class="hidden" 
                                    @change="handleReceiptSelect($event, activeOrder.id, isDepositPayment(activeOrder) ? 'deposit' : 'balance')"
                                    accept="image/jpeg,image/png,image/jpg,application/pdf"
                                />
                                <button 
                                    type="button"
                                    @click="triggerFileSelect(activeOrder.id)"
                                    class="text-white font-semibold px-3 py-1.5 rounded-lg text-[9px] sm:text-[10px] uppercase tracking-widest transition-colors flex items-center gap-1 cursor-pointer shadow-xs"
                                    :class="activeOrder.status.includes('Rejected') ? 'bg-[#8C3A3A] hover:bg-[#732F2F]' : 'bg-[#4A6B5D] hover:bg-[#3D574B]'"
                                    :disabled="processingReupload[activeOrder.id]"
                                >
                                    <i class="fas fa-cloud-upload-alt"></i> 
                                    {{ processingReupload[activeOrder.id] ? t('uploading') : (activeOrder.status === 'Delivered' ? t('upload_balance_proof') : (activeOrder.status === 'Pending' ? t('upload_deposit_proof') : t('reupload_receipt_btn'))) }}
                                </button>
                                <span v-if="fileErrors[activeOrder.id]" class="text-[9px] text-[#8C3A3A] font-semibold absolute top-full right-0 mt-1 whitespace-nowrap">{{ fileErrors[activeOrder.id] }}</span>
                            </div>
                        </div>

                        <!-- QR Code Panel for Payments -->
                        <div v-if="shouldShowQr(activeOrder) && qrCodePath" class="border-t flex flex-col sm:flex-row items-center gap-5 p-5"
                            :class="activeOrder.status.includes('Rejected') ? 'border-[#FADCDD] bg-[#FDF2F2]' : 'border-[#E6E1DA] bg-white'"
                        >
                            <!-- QR Image (clickable to zoom) -->
                            <div 
                                class="flex-shrink-0 w-32 h-32 rounded-xl border border-[#E6E1DA] overflow-hidden shadow-sm bg-white p-1.5 cursor-zoom-in relative group"
                                @click="showQrModal = true"
                                title="Click to zoom"
                            >
                                <img :src="resolveQrPath(qrCodePath)" alt="Payment QR Code" class="w-full h-full object-contain transition-transform duration-200 group-hover:scale-105" />
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-200 flex items-center justify-center">
                                    <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 drop-shadow-md text-lg"></i>
                                </div>
                            </div>
                            <!-- Label + Download -->
                            <div class="flex flex-col gap-3 flex-1">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#4A6B5D] block mb-0.5">
                                        <i class="fas fa-qrcode mr-1"></i>
                                        {{ t('payment_qr_code') }}
                                    </span>
                                    <p class="text-[11px] text-[#5C6460] font-light leading-relaxed">
                                        <template v-if="isDepositPayment(activeOrder)">
                                            {{ t('scan_qr_deposit_info').replace('{depositPercent}', depositPercent).replace('{depositAmount}', getDepositAmount(activeOrder.total_price).toFixed(2)) }}
                                        </template>
                                        <template v-else>
                                            {{ t('scan_qr_balance_info').replace('{balance}', getBalanceAmount(activeOrder.total_price).toFixed(2)) }}
                                        </template>
                                    </p>
                                </div>
                                <button 
                                    type="button"
                                    @click="downloadQr()"
                                    class="self-start bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-3 py-1.5 rounded-md text-[9px] sm:text-[10px] uppercase tracking-widest transition-colors flex items-center gap-1 cursor-pointer"
                                >
                                    <i class="fas fa-download"></i>
                                    {{ t('save_qr_code') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✨ Review Prompt Card (shown when Completed order has no review) -->
                <div v-if="reviewOrder" class="bg-gradient-to-br from-[#FAF7F2] to-[#EBEFEF] border border-[#D4C9B8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#4A6B5D] text-white flex items-center justify-center text-base flex-shrink-0">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-[#4A6B5D] block">
                                    {{ t('share_your_experience') }}
                                </span>
                                <p class="text-xs text-[#5C6460] font-light mt-0.5">
                                    {{ t('share_experience_desc') }} (Order #${reviewOrder.id})
                                </p>
                            </div>
                        </div>
                        <!-- Skip button -->
                        <Link 
                            :href="route('orders.show', { id: reviewOrder.id })" 
                            class="text-[10px] text-[#8C8275] hover:text-[#4A6B5D] font-semibold uppercase tracking-wider transition-colors flex-shrink-0"
                        >
                            {{ t('view_invoice') }} →
                        </Link>
                    </div>

                    <!-- Star Rating -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                            {{ t('rating_stars_label') }}
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                @click="reviewForm.rating = star"
                                @mouseover="hoverRating = star"
                                @mouseleave="hoverRating = 0"
                                class="text-2xl transition-all duration-150 focus:outline-none hover:scale-110"
                            >
                                <i class="fa-star" :class="[(hoverRating || reviewForm.rating) >= star ? 'fas text-[#c5a880]' : 'far text-zinc-300']"></i>
                            </button>
                            <span v-if="reviewForm.rating" class="text-xs font-bold text-[#2D3330] ml-1">{{ reviewForm.rating }} / 5</span>
                        </div>
                        <span v-if="reviewForm.errors.rating" class="text-[10px] text-red-500 font-medium block">{{ reviewForm.errors.rating }}</span>
                    </div>

                    <!-- Review Textarea -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                            {{ t('your_review_optional') }}
                        </label>
                        <textarea
                            v-model="reviewForm.review_text"
                            rows="3"
                            class="w-full text-xs border border-[#E6E1DA] rounded-xl p-3 bg-white focus:outline-none focus:ring-1 focus:ring-[#4A6B5D] focus:border-[#4A6B5D] transition-shadow placeholder-[#C6C1B9]/80 resize-none"
                            :placeholder="t('review_placeholder')"
                        ></textarea>
                        <span v-if="reviewForm.errors.review_text" class="text-[10px] text-red-500 font-medium block">{{ reviewForm.errors.review_text }}</span>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="button"
                        @click="submitReview()"
                        :disabled="reviewForm.processing || !reviewForm.rating"
                        class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] disabled:bg-zinc-300 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl text-[11px] uppercase tracking-widest transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span v-if="reviewForm.processing">
                            {{ t('submitting_status') }}
                        </span>
                        <span v-else>
                            {{ t('submit_review_btn') }}
                        </span>
                    </button>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-3 sm:gap-6">
                    <!-- Stat Card 1 -->
                    <div class="bg-white p-2.5 sm:p-5 rounded-lg sm:rounded-xl border border-[#E6E1DA] shadow-sm flex items-center justify-between gap-1">
                        <div>
                            <span class="text-[8px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('shopping_cart') }}</span>
                            <span class="text-xs sm:text-lg md:text-2xl font-normal text-[#2D3330] font-serif-luxury whitespace-nowrap">{{ cartCount }} {{ t('packages') }}</span>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-lg shrink-0">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                    </div>
 
                    <!-- Stat Card 2 -->
                    <div class="bg-white p-2.5 sm:p-5 rounded-lg sm:rounded-xl border border-[#E6E1DA] shadow-sm flex items-center justify-between gap-1">
                        <div>
                            <span class="text-[8px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('total_bookings') }}</span>
                            <span class="text-xs sm:text-lg md:text-2xl font-normal text-[#2D3330] font-serif-luxury whitespace-nowrap">{{ ordersCount }} {{ t('orders') }}</span>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-lg shrink-0">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Sections -->
                <div v-if="activeOrder" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- 1. Upcoming Event Countdown Card -->
                    <div class="bg-white p-3.5 sm:p-5 rounded-lg sm:rounded-xl border border-[#E6E1DA] shadow-sm flex flex-col justify-between h-auto lg:h-80 lg:col-span-1">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('upcoming_event') }}</span>
                                <span v-if="activeOrderCountdown" class="px-2.5 py-1 text-[9px] font-bold rounded-full uppercase tracking-wider" :class="activeOrderCountdown.class">
                                    <i class="far fa-clock mr-0.5"></i> 
                                    <span v-if="activeOrderCountdown.days > 0" class="mr-0.5">{{ activeOrderCountdown.days }}</span>
                                    {{ activeOrderCountdown.label }}
                                </span>
                            </div>
                            
                            <h4 class="font-normal text-[#2D3330] text-sm sm:text-base md:text-xl font-serif-luxury tracking-wide mb-2 leading-snug">
                                {{ activeOrder.package_name }}
                            </h4>
                            
                            <ul class="space-y-2.5 text-xs text-[#5C6460]">
                                <li class="flex items-start gap-2">
                                    <i class="far fa-calendar-alt text-[#4A6B5D] w-4 mt-0.5 text-center"></i>
                                    <span>{{ activeOrder.delivery_date }}</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="far fa-clock text-[#4A6B5D] w-4 mt-0.5 text-center"></i>
                                    <span>{{ activeOrder.delivery_time }}</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-map-marker-alt text-[#4A6B5D] w-4 mt-0.5 text-center"></i>
                                    <span class="line-clamp-2 leading-relaxed" :title="activeOrder.delivery_address">{{ activeOrder.delivery_address }}</span>
                                </li>
                            </ul>
                        </div>
                        <Link 
                            :href="route('orders.show', { id: activeOrder.id })" 
                            class="inline-flex items-center justify-center gap-1 border border-[#E6E1DA] hover:border-[#4A6B5D] hover:bg-[#EBEFEF]/30 text-[#5C6460] hover:text-[#4A6B5D] font-bold px-3 py-1.5 rounded-lg text-[9px] sm:text-[10px] uppercase tracking-widest transition-all w-full mt-3 cursor-pointer"
                        >
                            <i class="fas fa-search-plus"></i> {{ t('view_invoice') }}
                        </Link>
                    </div>

                    <!-- 2. Financial Summary & Balance Slip Upload Widget -->
                    <div class="bg-white p-3.5 sm:p-5 rounded-lg sm:rounded-xl border border-[#E6E1DA] shadow-sm flex flex-col justify-between h-auto lg:h-80 lg:col-span-2">
                        <div>
                            <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-4">{{ t('financial_summary') }}</span>
                            
                            <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-4">
                                <div class="bg-[#FAF7F2] p-3 sm:p-4 border border-[#E6E1DA] rounded-xl">
                                    <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1">{{ t('deposit_paid') }}</span>
                                    <span class="text-xs sm:text-base md:text-lg font-bold text-[#4A6B5D] font-serif-luxury">
                                        RM {{ (parseFloat(activeOrder.total_price) * 0.3).toFixed(2) }}
                                    </span>
                                </div>
                                <div class="bg-[#FAF7F2] p-3 sm:p-4 border border-[#E6E1DA] rounded-xl">
                                    <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1">{{ t('balance_due_payment') }}</span>
                                    <span class="text-xs sm:text-base md:text-lg font-bold text-[#2D3330] font-serif-luxury">
                                        RM {{ (parseFloat(activeOrder.total_price) * 0.7).toFixed(2) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Balance payment slip re-upload logic or info -->
                        <div class="mt-2 border-t border-[#FAF6F0] pt-4">
                            <!-- Case 1: Status is Payment Submitted, meaning awaiting verification -->
                            <div v-if="activeOrder.status === 'Payment Submitted'" class="bg-[#FAF7F2] p-4 border border-[#E6E1DA] rounded-2xl flex items-center gap-3 text-xs text-[#5C6460]">
                                <i class="fas fa-info-circle text-[#4A6B5D] text-lg"></i>
                                <span>{{ t('balance_submitted_verification') }}</span>
                            </div>

                            <!-- Case 2: Status is Confirmed or Delivered, meaning balance receipt hasn't been uploaded yet -->
                            <div v-else-if="['Confirmed', 'Delivered'].includes(activeOrder.status)" class="space-y-3">
                                <p class="text-xs text-[#8C8275] font-light">{{ t('upload_balance_receipt') }}</p>
                                <div class="relative flex items-center gap-3">
                                    <input 
                                        type="file" 
                                        :id="`balance-file-${activeOrder.id}`" 
                                        class="hidden" 
                                        @change="handleReceiptSelect($event, activeOrder.id, 'balance')"
                                        accept="image/jpeg,image/png,image/jpg,application/pdf"
                                    />
                                    <button 
                                        type="button"
                                        @click="triggerBalanceFileSelect(activeOrder.id)"
                                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-3 py-1.5 rounded-lg text-[9px] sm:text-[10px] uppercase tracking-widest transition-colors flex items-center gap-1 cursor-pointer"
                                        :disabled="processingReupload[activeOrder.id]"
                                    >
                                        <i class="fas fa-cloud-upload-alt"></i> 
                                        {{ processingReupload[activeOrder.id] ? t('uploading') : t('upload_payment_slip') }}
                                    </button>
                                    <span v-if="fileErrors[activeOrder.id]" class="text-[9px] text-[#8C3A3A] font-semibold absolute top-full left-0 mt-1 whitespace-nowrap">{{ fileErrors[activeOrder.id] }}</span>
                                </div>
                            </div>

                            <!-- Case 3: Rejected / Completed / Other (already handled in activeOrderTimeline or order index, fallback generic state) -->
                            <div v-else class="text-xs text-[#8C8275] font-light flex items-center gap-2">
                                <i class="fas fa-check-circle text-[#4A6B5D]"></i>
                                <span>{{ t('payment_trans_status') }}: <span class="font-semibold uppercase">{{ getTranslatedStatus(activeOrder.status) }}</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
                    <!-- Left: Recommended Packages (Takes 2 columns on desktop) -->
                    <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                        <div>
                            <h4 class="text-xs font-bold text-[#8C8275] uppercase tracking-widest mb-1">{{ t('featured_packages_title') }}</h4>
                            <p class="text-xs text-[#8C8275] font-light">{{ t('featured_packages_subtitle') }}</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 sm:gap-6">
                            <div 
                                v-for="pkg in featuredPackages" 
                                :key="pkg.id" 
                                class="bg-white p-2.5 sm:p-5 rounded-lg sm:rounded-xl border border-[#E6E1DA] shadow-sm flex flex-col justify-between h-auto lg:h-80 action-card"
                            >
                                <div>
                                    <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-lg mb-2.5 sm:mb-4">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <h5 class="font-normal text-[#2D3330] text-[11px] sm:text-lg font-serif-luxury tracking-wide mb-1.5 sm:mb-2 truncate" :title="pkg.package_name">{{ pkg.package_name }}</h5>
                                    <p class="text-[9px] sm:text-xs text-[#8C8275] leading-relaxed font-light line-clamp-3 mb-2.5 sm:mb-3">{{ pkg.description }}</p>
                                    
                                    <div class="text-[8px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest space-y-0.5 sm:space-y-1">
                                        <div>{{ t('price') }}: <span class="text-[9px] sm:text-xs font-bold text-[#4A6B5D]">RM {{ parseFloat(pkg.price).toFixed(2) }}</span> / pax</div>
                                        <div>{{ t('min_requirement') }}: <span class="text-[9px] sm:text-xs font-bold text-[#2D3330]">{{ pkg.min_order }} pax</span></div>
                                    </div>
                                </div>
                                <Link 
                                    :href="route('menu.index')" 
                                    class="text-[9px] sm:text-xs font-bold uppercase tracking-wider text-[#4A6B5D] hover:text-[#3D574B] flex items-center gap-0.5 mt-2 sm:mt-3"
                                >
                                    {{ t('view_packages') }} <i class="fas fa-chevron-right text-[8px] sm:text-[10px]"></i>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Catering Guidelines FAQ (Takes 1 column on desktop) -->
                    <div class="lg:col-span-1 bg-white rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-[#E6E1DA] shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-3 border-b border-[#FAF6F0] pb-4 mb-4">
                                <div class="w-10 h-10 rounded-xl bg-[#FAF6F0] text-[#8C8275] flex items-center justify-center text-lg border border-[#E6E1DA] shrink-0">
                                    <i class="fas fa-lightbulb text-[#4A6B5D]"></i>
                                </div>
                                <div>
                                    <h4 class="font-normal text-[#2D3330] text-sm sm:text-base font-serif-luxury tracking-wide">{{ t('catering_guidelines') }}</h4>
                                    <p class="text-[9px] text-[#8C8275] uppercase tracking-widest font-semibold">SmartServe Catering</p>
                                </div>
                            </div>

                            <div class="space-y-4 text-xs text-[#2D3330] font-light leading-relaxed">
                                <div class="space-y-1">
                                    <h5 class="font-bold text-[#2D3330] text-xs uppercase tracking-wider">{{ t('guide_pax_title') }}</h5>
                                    <p class="text-[#5C6460]">{{ t('guide_pax_desc') }}</p>
                                </div>
                                <div class="space-y-1 border-t border-[#FAF6F0] pt-4">
                                    <h5 class="font-bold text-[#2D3330] text-xs uppercase tracking-wider">{{ t('guide_halal_title') }}</h5>
                                    <p class="text-[#5C6460]">{{ t('guide_halal_desc') }}</p>
                                </div>
                                <div class="space-y-1 border-t border-[#FAF6F0] pt-4">
                                    <h5 class="font-bold text-[#2D3330] text-xs uppercase tracking-wider">{{ t('guide_changes_title') }}</h5>
                                    <p class="text-[#5C6460]">{{ t('guide_changes_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings Widget -->
                <div class="bg-white rounded-lg sm:rounded-2xl p-3 sm:p-5 lg:p-6 border border-[#E6E1DA] shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-[#FAF6F0] pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-lg">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <h4 class="font-normal text-[#2D3330] text-lg font-serif-luxury tracking-wide">{{ t('recent_bookings') }}</h4>
                                <p class="text-[9px] text-[#8C8275] uppercase tracking-widest font-semibold">{{ t('my_orders_desc') }}</p>
                            </div>
                        </div>
                        <Link 
                            :href="route('orders.index')"
                            class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#4A6B5D] hover:text-[#3D574B] flex items-center gap-0.5 font-sans-modern"
                        >
                            {{ t('track_orders') }} <i class="fas fa-chevron-right text-[10px]"></i>
                        </Link>
                    </div>

                    <div v-if="recentOrders.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-[#2D3330] font-sans-modern">
                            <thead>
                                <tr class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest border-b border-[#FAF6F0]">
                                    <th class="py-3 px-2">{{ t('order_id') }}</th>
                                    <th class="py-3 px-2">{{ t('package_name') }}</th>
                                    <th class="py-3 px-2">{{ t('event_date') }}</th>
                                    <th class="py-3 px-2">{{ t('grand_total_label') }}</th>
                                    <th class="py-3 px-2">{{ t('status_label') }}</th>
                                    <th class="py-3 px-2 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FAF6F0]">
                                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-[#FAF7F2]/50 transition-colors">
                                    <td class="py-4 px-2 font-semibold font-serif-luxury text-xs sm:text-sm">#{{ order.id }}</td>
                                    <td class="py-4 px-2 font-medium">{{ order.package_name }}</td>
                                    <td class="py-4 px-2 text-[#5C6460]">
                                        <i class="far fa-calendar mr-1 text-[#4A6B5D]"></i> {{ order.delivery_date }}
                                    </td>
                                    <td class="py-4 px-2 font-semibold text-[#4A6B5D]">RM {{ parseFloat(order.total_price).toFixed(2) }}</td>
                                    <td class="py-4 px-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-[9px] font-semibold border rounded-full uppercase tracking-wider whitespace-nowrap" :class="getStatusBadge(order.status)">
                                            {{ getTranslatedStatus(order.status) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-2 text-right">
                                        <Link 
                                            :href="route('orders.show', { id: order.id })"
                                            class="inline-flex items-center gap-1 border border-[#E6E1DA] hover:border-[#4A6B5D] hover:bg-[#EBEFEF]/30 px-2.5 py-1 rounded-md text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-[#5C6460] hover:text-[#4A6B5D] transition-all cursor-pointer"
                                        >
                                            {{ t('view_details') }}
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center py-8 border border-dashed border-[#E6E1DA] rounded-xl">
                        <p class="text-[#8C8275] text-xs font-light">{{ t('no_recent_bookings') }}</p>
                        <Link 
                            :href="route('menu.index')"
                            class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-[10px] font-semibold uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-xs mt-4 transition-all cursor-pointer"
                        >
                            <i class="fas fa-utensils"></i> {{ t('order_now') }}
                        </Link>
                    </div>
                </div>

            </div>
        </div>

        <!-- Success Toast -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="showSuccessToast" class="toast-notification font-sans-modern text-xs uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-check-circle text-[#EBEFEF]"></i>
                <span>{{ toastMessage }}</span>
            </div>
        </Transition>

        <!-- QR Code Lightbox Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="showQrModal && qrCodePath"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="showQrModal = false"
                @keydown.escape="showQrModal = false"
                tabindex="-1"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showQrModal = false"></div>

                <!-- Modal Card -->
                <div class="relative z-10 bg-white rounded-2xl shadow-2xl p-6 flex flex-col items-center gap-5 max-w-sm w-full">
                    <!-- Close Button -->
                    <button 
                        @click="showQrModal = false" 
                        class="absolute top-4 right-4 w-9 h-9 rounded-full bg-[#FAF6F0] hover:bg-[#EBEFEF] text-[#5C6460] flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>

                    <!-- Title -->
                    <div class="text-center">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#4A6B5D] flex items-center justify-center gap-1.5 mb-1">
                            <i class="fas fa-qrcode"></i>
                            {{ t('payment_qr_code') }}
                        </span>
                        <p class="text-[11px] text-[#8C8275] font-light">
                            {{ t('scan_using_bank_app') }}
                        </p>
                    </div>

                    <!-- Large QR Image -->
                    <div class="w-64 h-64 rounded-2xl border-2 border-[#E6E1DA] overflow-hidden bg-white p-3 shadow-inner">
                        <img :src="resolveQrPath(qrCodePath)" alt="Payment QR Code" class="w-full h-full object-contain" />
                    </div>

                    <!-- Download Button -->
                    <button 
                        type="button"
                        @click="downloadQr()"
                        class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3 rounded-xl text-[11px] uppercase tracking-widest transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <i class="fas fa-download"></i>
                        {{ t('save_qr_code') }}
                    </button>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
