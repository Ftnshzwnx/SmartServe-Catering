<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    orders: {
        type: Array,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
    tab: {
        type: String,
        default: 'all',
    },
    notifications: {
        type: Object,
        default: () => ({ pending: 0, confirmed: 0, delivered: 0, total: 0 }),
    },
});

const { t } = useLocalization();

const activeTab = ref(props.tab);
const fileInputs = ref({});
const fileErrors = ref({});
const processingReupload = ref({});

function handleTabChange(tabName) {
    activeTab.value = tabName;
    router.get(route('orders.index', { tab: tabName }));
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

function canCancel(order) {
    if (['Cancelled', 'Completed'].includes(order.status)) return false;
    
    // Check if event date is at least 7 days away
    const deliveryDate = new Date(order.delivery_date);
    const today = new Date();
    const diffTime = deliveryDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays >= 7;
}

function handleCancelOrder(id) {
    if (confirm(t('confirm_cancel_order') || 'Are you sure you want to cancel this order? Please note that deposits are non-refundable.')) {
        router.post(route('orders.cancel', { id: id }), {}, {
            onSuccess: () => alert(t('toast_order_cancelled') || 'Order cancelled successfully.')
        });
    }
}

function triggerFileSelect(orderId) {
    document.getElementById(`reupload-file-${orderId}`).click();
}

const showSuccessToast = ref(false);
const toastMessage = ref('');

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
</script>

<template>
    <Head :title="t('my_bookings')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .order-card {
            background: #ffffff;
            border-radius: 0px;
            padding: 28px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.01);
            transition: all 0.2s ease;
        }
        .order-card:hover {
            border-color: #4A6B5D;
            box-shadow: 0 10px 20px -8px rgba(74, 107, 93, 0.08);
        }
        .tab-btn {
            padding: 8px 16px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0px;
            border: 1px solid transparent;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: all 0.2s ease;
            color: #8C8275;
        }
        .tab-btn.active {
            background: #4A6B5D;
            color: #ffffff;
            border-color: #4A6B5D;
        }
        .progress-dot {
            width: 10px;
            height: 10px;
            border-radius: 0px;
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

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between font-sans-modern">
                <h2 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                    {{ t('my_bookings') }}
                </h2>
            </div>
        </template>

        <div class="py-12 bg-[#FAF7F2] min-h-[calc(100vh-80px)] font-sans-modern">
            <div class="max-w-5xl mx-auto px-6 space-y-8">
                
                <!-- Filters tabs row -->
                <div class="flex flex-wrap items-center gap-1.5 border-b border-[#E6E1DA] pb-4">
                    <button 
                        @click="handleTabChange('all')" 
                        class="tab-btn" 
                        :class="{ 'active': activeTab === 'all' }"
                    >
                        {{ t('all') }}
                    </button>
                    <button 
                        @click="handleTabChange('pending')" 
                        class="tab-btn flex items-center gap-1.5" 
                        :class="{ 'active': activeTab === 'pending' }"
                    >
                        {{ t('pending_rejected') }}
                        <span v-if="notifications.pending > 0" class="bg-[#8C3A3A] text-white text-[9px] font-bold px-1.5 py-0.5">
                            {{ notifications.pending }}
                        </span>
                    </button>
                    <button 
                        @click="handleTabChange('confirmed')" 
                        class="tab-btn" 
                        :class="{ 'active': activeTab === 'confirmed' }"
                    >
                        {{ t('confirmed') }}
                    </button>
                    <button 
                        @click="handleTabChange('delivered')" 
                        class="tab-btn" 
                        :class="{ 'active': activeTab === 'delivered' }"
                    >
                        {{ t('delivered_tab') }}
                    </button>
                    <button 
                        @click="handleTabChange('payment_submitted')" 
                        class="tab-btn" 
                        :class="{ 'active': activeTab === 'payment_submitted' }"
                    >
                        {{ t('awaiting_verification') }}
                    </button>
                    <button 
                        @click="handleTabChange('completed')" 
                        class="tab-btn" 
                        :class="{ 'active': activeTab === 'completed' }"
                    >
                        {{ t('completed_tab') }}
                    </button>
                    <button 
                        @click="handleTabChange('cancelled')" 
                        class="tab-btn" 
                        :class="{ 'active': activeTab === 'cancelled' }"
                    >
                        {{ t('cancelled_tab') }}
                    </button>
                </div>

                <!-- Orders List -->
                <div v-if="orders.length > 0" class="space-y-8">
                    <div v-for="order in orders" :key="order.id" class="order-card space-y-6">
                        
                        <!-- Order Header Information -->
                        <div class="flex flex-wrap justify-between items-start gap-4 border-b border-[#EBEFEF] pb-4 text-xs tracking-wider text-[#8C8275] uppercase">
                            <div>
                                <span class="text-[9px] font-bold block mb-1">{{ t('order_id') }}</span>
                                <span class="text-base font-normal text-[#2D3330] font-serif-luxury tracking-wide">#{{ order.id }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold block mb-1">{{ t('delivery_event_date_label') }}</span>
                                <span class="text-xs font-semibold text-[#2D3330]">
                                    <i class="far fa-calendar mr-1 text-[#4A6B5D]"></i> {{ order.delivery_date }} ({{ order.delivery_time }})
                                </span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold block mb-1">{{ t('total_price') }}</span>
                                <span class="text-xs font-semibold text-[#4A6B5D] lowercase">RM <span class="text-sm font-semibold uppercase">{{ parseFloat(order.total_price).toFixed(2) }}</span></span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold block mb-1">{{ t('status_label') }}</span>
                                <span class="inline-flex items-center px-3 py-1 text-[10px] font-semibold border rounded-none uppercase tracking-widest" :class="getStatusBadge(order.status)">
                                    {{ getTranslatedStatus(order.status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar Tracker -->
                        <div v-if="order.status !== 'Cancelled'" class="py-4">
                            <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-6">{{ t('order_timeline_tracker') }}</span>
                            
                            <div class="relative flex items-center justify-between w-full">
                                <!-- Track bar line -->
                                <div class="absolute left-0 right-0 h-0.5 bg-[#E6E1DA] top-1/2 -translate-y-1/2"></div>
                                <div 
                                    class="absolute left-0 h-0.5 bg-[#4A6B5D] top-1/2 -translate-y-1/2 transition-all duration-300"
                                    :style="{
                                        width: 
                                            order.status === 'Pending' || order.status === 'Deposit Rejected' ? '0%' :
                                            order.status === 'Payment Submitted' ? '25%' :
                                            order.status === 'Confirmed' ? '50%' :
                                            order.status === 'Delivered' || order.status === 'Balance Rejected' ? '75%' :
                                            order.status === 'Completed' ? '100%' : '0%'
                                    }"
                                ></div>

                                <!-- Dot 1: Pending -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Pending', 'Deposit Rejected', 'Payment Submitted', 'Confirmed', 'Delivered', 'Balance Rejected', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[9px] font-semibold text-[#8C8275] uppercase tracking-wider">{{ t('timeline_submitted') }}</span>
                                </div>

                                <!-- Dot 2: Confirmed -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Confirmed', 'Delivered', 'Balance Rejected', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[9px] font-semibold text-[#8C8275] uppercase tracking-wider">{{ t('timeline_confirmed') }}</span>
                                </div>

                                <!-- Dot 3: Delivered -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Delivered', 'Balance Rejected', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[9px] font-semibold text-[#8C8275] uppercase tracking-wider">{{ t('timeline_delivered') }}</span>
                                </div>

                                <!-- Dot 4: Completed -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="order.status === 'Completed' ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[9px] font-semibold text-[#8C8275] uppercase tracking-wider">{{ t('timeline_completed') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action / Error Notes from Admin Section -->
                        <div v-if="order.admin_note" class="p-4 bg-[#FDF2F2] border border-[#FADCDD] text-xs">
                            <span class="font-bold text-[#8C3A3A] uppercase tracking-wider block mb-1">
                                <i class="fas fa-exclamation-triangle"></i> {{ t('rejection_reason_admin') }}
                            </span>
                            <p class="text-[#8C3A3A] leading-relaxed font-light">{{ order.admin_note }}</p>
                        </div>

                        <!-- Details & Control Panel -->
                        <div class="flex flex-wrap justify-between items-center gap-4 bg-[#FAF6F0] p-4 border border-[#E6E1DA]">
                            <div class="text-xs text-[#5C6460] leading-relaxed">
                                <span class="font-bold text-[#2D3330] uppercase tracking-wider block mb-0.5 text-[10px]">{{ t('package') }}:</span>
                                <span class="font-light">{{ order.package_name }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <Link 
                                    :href="route('orders.show', { id: order.id })" 
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold px-4 py-2.5 rounded-none text-xs uppercase tracking-widest transition-colors"
                                >
                                    <i class="fas fa-search-plus mr-1 text-[10px]"></i> {{ t('view_invoice') }}
                                </Link>

                                <!-- Re-upload receipt button (Only shown when rejected) -->
                                <div v-if="['Deposit Rejected', 'Balance Rejected'].includes(order.status)" class="relative inline-block">
                                    <input 
                                        type="file" 
                                        :id="`reupload-file-${order.id}`" 
                                        class="hidden" 
                                        @change="handleReceiptSelect($event, order.id, order.status === 'Deposit Rejected' ? 'deposit' : 'balance')"
                                        accept="image/jpeg,image/png,image/jpg,application/pdf"
                                    />
                                    <button 
                                        type="button"
                                        @click="triggerFileSelect(order.id)"
                                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-4 py-2.5 rounded-none text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5"
                                        :disabled="processingReupload[order.id]"
                                    >
                                        <i class="fas fa-cloud-upload-alt"></i> 
                                        {{ processingReupload[order.id] ? t('uploading') : t('reupload_receipt_btn') }}
                                    </button>
                                    <span v-if="fileErrors[order.id]" class="text-[10px] text-[#8C3A3A] font-semibold absolute top-full left-0 mt-1 whitespace-nowrap">{{ fileErrors[order.id] }}</span>
                                </div>

                                <!-- Cancellation Button -->
                                <button 
                                    v-if="canCancel(order)"
                                    @click="handleCancelOrder(order.id)"
                                    class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-semibold px-4 py-2.5 rounded-none text-xs uppercase tracking-widest transition-colors"
                                >
                                    <i class="fas fa-times-circle mr-1 text-[10px]"></i> {{ t('cancel_event_btn') }}
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-none border border-[#E6E1DA] p-20 text-center shadow-sm max-w-xl mx-auto">
                    <div class="w-16 h-16 rounded-none bg-[#FAF6F0] text-[#8C8275] flex items-center justify-center text-xl mx-auto mb-6 border border-[#E6E1DA]">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h4 class="text-[#2D3330] font-normal font-serif-luxury text-2xl uppercase tracking-wider mb-2">{{ t('no_bookings_found') }}</h4>
                    <p class="text-[#8C8275] text-xs mt-1 mb-8 font-light leading-relaxed">{{ t('no_bookings_found_desc') }}</p>
                    <Link 
                        :href="route('menu.index')"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-8 py-3.5 rounded-none text-xs uppercase tracking-widest transition-colors"
                    >
                        {{ t('browse_packages_btn') }}
                    </Link>
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
    </AuthenticatedLayout>
</template>
