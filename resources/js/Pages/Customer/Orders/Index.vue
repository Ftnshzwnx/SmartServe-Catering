<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const { toast } = useToast();
const { confirm } = useConfirm();

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
        default: () => ({ all: 0, pending: 0, confirmed: 0, delivered: 0, proposals: 0, payment_submitted: 0, completed: 0, cancelled: 0, total: 0 }),
    },
});

const { t, currentLanguage } = useLocalization();

const page = usePage();
const qrCodePath = page.props.settings?.qr_code_path || null;

const depositPercent = computed(() => {
    return parseFloat(page.props.settings?.deposit_percentage || 30);
});

function getDepositAmount(totalPrice) {
    return parseFloat(totalPrice) * (depositPercent.value / 100);
}

function getBalanceAmount(totalPrice) {
    return parseFloat(totalPrice) * ((100 - depositPercent.value) / 100);
}

function shouldShowQr(order) {
    if (!qrCodePath) return false;
    if (order.status === 'Delivered' || order.status === 'Balance Rejected') return true;
    if (order.status === 'Pending' && order.is_custom_proposal && !order.payment_proof) return true;
    if (order.status === 'Deposit Rejected') return true;
    return false;
}

function isDepositPayment(order) {
    return order.status === 'Pending' || order.status === 'Deposit Rejected';
}

const showQrModal = ref(false);
function resolveQrPath(path) {
    if (!path) return '';
    if (path.startsWith('data:') || path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) {
        return path;
    }
    return '/' + path;
}


function downloadQr() {
    if (!qrCodePath) return;
    const link = document.createElement('a');
    link.href = resolveQrPath(qrCodePath);
    link.download = 'SmartServe-Payment-QR.png';
    link.click();
}

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
        case 'Pending Proposal':
            return 'bg-[#FAF6F0] text-[#8C8275] border-[#E6E1DA]';
        case 'Proposal Sent':
            return 'bg-[#FAF7F2] text-[#4A6B5D] border-[#C5A880]';
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
        case 'Pending Proposal':
            return t('awaiting_proposal_status');
        case 'Proposal Sent':
            return t('proposal_sent_status');
        case 'Pending':
            return t('pending');
        case 'Confirmed':
            return t('confirmed');
        case 'Payment Submitted':
            return t('awaiting_verification');
        case 'Delivered':
            return t('delivered_tab');
        case 'Completed':
            return t('completed_tab');
        case 'Deposit Rejected':
            return t('deposit_rejected');
        case 'Balance Rejected':
            return t('balance_rejected');
        case 'Cancelled':
            return t('cancelled_tab');
        default:
            return status;
    }
}

function canCancel(order) {
    // Cannot cancel once already cancelled or completed
    if (['Cancelled', 'Completed'].includes(order.status)) return false;

    // Cannot cancel once admin has approved the deposit (Confirmed and beyond)
    if (['Confirmed', 'Delivered', 'Balance Rejected', 'Payment Submitted'].includes(order.status)) return false;
    
    // Check if event date is at least 7 days away
    const deliveryDate = new Date(order.delivery_date);
    const today = new Date();
    const diffTime = deliveryDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays >= 7;
}

async function handleCancelOrder(id) {
    if (await confirm(
        t('confirm_cancel_order') || 'Are you sure you want to cancel this order? Please note that deposits are non-refundable.',
        t('confirm_action') || 'Cancel Order',
        t('yes_confirm') || 'Yes, Cancel',
        t('no_cancel') || 'Keep Order'
    )) {
        router.post(route('orders.cancel', { id: id }), {}, {
            onSuccess: () => toast(t('toast_order_cancelled') || 'Order cancelled successfully.')
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
        .font-serif-luxury { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .order-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.01);
            transition: all 0.2s ease;
        }
        @media (min-width: 640px) {
            .order-card {
                border-radius: 12px;
                padding: 20px;
            }
        }
        .order-card:hover {
            border-color: #4A6B5D;
            box-shadow: 0 10px 20px -8px rgba(74, 107, 93, 0.08);
        }
        .tab-btn {
            padding: 6px 12px;
            font-size: 0.65rem;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid transparent;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
            color: #8C8275;
            cursor: pointer;
            flex-shrink: 0;
            white-space: nowrap;
        }
        @media (min-width: 640px) {
            .tab-btn {
                padding: 8px 16px;
                font-size: 0.75rem;
                border-radius: 10px;
                letter-spacing: 0.1em;
            }
        }
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-none {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        .tab-btn.active {
            background: #4A6B5D;
            color: #ffffff;
            border-color: #4A6B5D;
        }
        .progress-dot {
            width: 8px;
            height: 8px;
            border-radius: 16px;
            z-index: 10;
        }
        @media (min-width: 640px) {
            .progress-dot {
                width: 10px;
                height: 10px;
            }
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
        :header-title="t('my_bookings')"
        :header-desc="t('my_orders_desc')"
    >

        <div class="font-sans-modern">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-6 sm:space-y-8">
                
                <!-- Filters tabs row -->
                <div class="flex overflow-x-auto whitespace-nowrap scrollbar-none items-center gap-1.5 border-b border-[#E6E1DA] pb-3 sm:pb-4">
                    <button 
                        @click="handleTabChange('all')" 
                        class="tab-btn flex items-center gap-2" 
                        :class="{ 'active': activeTab === 'all' }"
                    >
                        <span>{{ t('all') }}</span>
                        <span 
                            v-if="notifications.all > 0"
                            class="px-2 py-0.5 text-[9px] font-bold rounded-full transition-colors"
                            :class="activeTab === 'all' ? 'bg-white text-[#4A6B5D]' : 'bg-[#E6E1DA]/60 text-[#8C8275]'"
                        >
                            {{ notifications.all }}
                        </span>
                    </button>
                    <button 
                        @click="handleTabChange('proposals')" 
                        class="tab-btn flex items-center gap-2" 
                        :class="{ 'active': activeTab === 'proposals' }"
                    >
                        <span>{{ t('custom_proposal_tab') }}</span>
                        <span 
                            v-if="notifications.proposals > 0"
                            class="px-2 py-0.5 text-[9px] font-bold rounded-full transition-colors"
                            :class="activeTab === 'proposals' ? 'bg-white text-[#4A6B5D]' : 'bg-[#E6E1DA]/60 text-[#8C8275]'"
                        >
                            {{ notifications.proposals }}
                        </span>
                    </button>
                    <button 
                        @click="handleTabChange('pending')" 
                        class="tab-btn flex items-center gap-2" 
                        :class="{ 'active': activeTab === 'pending' }"
                    >
                        <span>{{ t('pending_rejected') }}</span>
                        <span 
                            v-if="notifications.pending > 0"
                            class="px-2 py-0.5 text-[9px] font-bold rounded-full transition-colors"
                            :class="activeTab === 'pending' ? 'bg-white text-[#4A6B5D]' : 'bg-[#E6E1DA]/60 text-[#8C8275]'"
                        >
                            {{ notifications.pending }}
                        </span>
                    </button>
                    <button 
                        @click="handleTabChange('confirmed')" 
                        class="tab-btn flex items-center gap-2" 
                        :class="{ 'active': activeTab === 'confirmed' }"
                    >
                        <span>{{ t('confirmed') }}</span>
                        <span 
                            v-if="notifications.confirmed > 0"
                            class="px-2 py-0.5 text-[9px] font-bold rounded-full transition-colors"
                            :class="activeTab === 'confirmed' ? 'bg-white text-[#4A6B5D]' : 'bg-[#E6E1DA]/60 text-[#8C8275]'"
                        >
                            {{ notifications.confirmed }}
                        </span>
                    </button>
                    <button 
                        @click="handleTabChange('delivered')" 
                        class="tab-btn flex items-center gap-2" 
                        :class="{ 'active': activeTab === 'delivered' }"
                    >
                        <span>{{ t('delivered_tab') }}</span>
                        <span 
                            v-if="notifications.delivered > 0"
                            class="px-2 py-0.5 text-[9px] font-bold rounded-full transition-colors"
                            :class="activeTab === 'delivered' ? 'bg-white text-[#4A6B5D]' : 'bg-[#E6E1DA]/60 text-[#8C8275]'"
                        >
                            {{ notifications.delivered }}
                        </span>
                    </button>
                    <button 
                        @click="handleTabChange('payment_submitted')" 
                        class="tab-btn flex items-center gap-2" 
                        :class="{ 'active': activeTab === 'payment_submitted' }"
                    >
                        <span>{{ t('awaiting_verification') }}</span>
                        <span 
                            v-if="notifications.payment_submitted > 0"
                            class="px-2 py-0.5 text-[9px] font-bold rounded-full transition-colors"
                            :class="activeTab === 'payment_submitted' ? 'bg-white text-[#4A6B5D]' : 'bg-[#E6E1DA]/60 text-[#8C8275]'"
                        >
                            {{ notifications.payment_submitted }}
                        </span>
                    </button>
                    <button 
                        @click="handleTabChange('completed')" 
                        class="tab-btn flex items-center gap-2" 
                        :class="{ 'active': activeTab === 'completed' }"
                    >
                        <span>{{ t('completed_tab') }}</span>
                        <span 
                            v-if="notifications.completed > 0"
                            class="px-2 py-0.5 text-[9px] font-bold rounded-full transition-colors"
                            :class="activeTab === 'completed' ? 'bg-white text-[#4A6B5D]' : 'bg-[#E6E1DA]/60 text-[#8C8275]'"
                        >
                            {{ notifications.completed }}
                        </span>
                    </button>
                    <button 
                        @click="handleTabChange('cancelled')" 
                        class="tab-btn flex items-center gap-2" 
                        :class="{ 'active': activeTab === 'cancelled' }"
                    >
                        <span>{{ t('cancelled_tab') }}</span>
                        <span 
                            v-if="notifications.cancelled > 0"
                            class="px-2 py-0.5 text-[9px] font-bold rounded-full transition-colors"
                            :class="activeTab === 'cancelled' ? 'bg-white text-[#4A6B5D]' : 'bg-[#E6E1DA]/60 text-[#8C8275]'"
                        >
                            {{ notifications.cancelled }}
                        </span>
                    </button>
                </div>

                <!-- Orders List -->
                <div v-if="orders.length > 0" class="space-y-8">
                    <div v-for="order in orders" :key="order.id" class="order-card space-y-6">
                        
                        <!-- Order Header Information -->
                        <div class="flex flex-wrap justify-between items-start gap-4 border-b border-[#EBEFEF] pb-4 text-[10px] sm:text-xs tracking-wider text-[#8C8275] uppercase">
                            <div>
                                <span class="text-[8px] sm:text-[9px] font-bold block mb-1">{{ t('order_id') }}</span>
                                <span class="text-sm sm:text-base font-normal text-[#2D3330] font-serif-luxury tracking-wide">#{{ order.id }}</span>
                            </div>
                            <div>
                                <span class="text-[8px] sm:text-[9px] font-bold block mb-1">{{ t('delivery_event_date_label') }}</span>
                                <span class="text-[10px] sm:text-xs font-semibold text-[#2D3330] whitespace-nowrap">
                                    <i class="far fa-calendar mr-1 text-[#4A6B5D]"></i> {{ order.delivery_date }} ({{ order.delivery_time }})
                                </span>
                            </div>
                            <div>
                                <span class="text-[8px] sm:text-[9px] font-bold block mb-1">{{ t('total_price') }}</span>
                                <span class="text-[10px] sm:text-xs font-semibold text-[#4A6B5D] lowercase whitespace-nowrap">RM <span class="text-xs sm:text-sm font-semibold uppercase">{{ parseFloat(order.total_price).toFixed(2) }}</span></span>
                            </div>
                            <div>
                                <span class="text-[8px] sm:text-[9px] font-bold block mb-1">{{ t('status_label') }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 sm:px-3 sm:py-1 text-[8px] sm:text-[10px] font-semibold border rounded-full uppercase tracking-widest whitespace-nowrap" :class="getStatusBadge(order.status)">
                                    {{ getTranslatedStatus(order.status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar Tracker -->
                        <div v-if="order.status !== 'Cancelled'" class="py-2 sm:py-4">
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-3 sm:mb-6">{{ t('order_timeline_tracker') }}</span>
                            
                            <!-- Custom Proposal Tracker -->
                            <div v-if="order.is_custom_proposal" class="relative flex items-center justify-between w-full">
                                <div class="absolute left-0 right-0 h-0.5 bg-[#E6E1DA] top-1/2 -translate-y-1/2"></div>
                                <div 
                                    class="absolute left-0 h-0.5 bg-[#4A6B5D] top-1/2 -translate-y-1/2 transition-all duration-300"
                                    :style="{
                                        width: 
                                            order.status === 'Pending Proposal' ? '0%' :
                                            order.status === 'Proposal Sent' ? '20%' :
                                            (order.status === 'Pending' || order.status === 'Deposit Rejected') ? '40%' :
                                            order.status === 'Confirmed' ? '60%' :
                                            (order.status === 'Delivered' || order.status === 'Balance Rejected' || order.status === 'Payment Submitted') ? '80%' :
                                            order.status === 'Completed' ? '100%' : '0%'
                                    }"
                                ></div>

                                <!-- Dot 1: Requested -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Pending Proposal', 'Proposal Sent', 'Pending', 'Deposit Rejected', 'Confirmed', 'Delivered', 'Balance Rejected', 'Payment Submitted', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('status_requested') }}</span>
                                </div>

                                <!-- Dot 2: Proposal Sent -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Proposal Sent', 'Pending', 'Deposit Rejected', 'Confirmed', 'Delivered', 'Balance Rejected', 'Payment Submitted', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('status_proposal') }}</span>
                                </div>

                                <!-- Dot 3: Approved / Deposit -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Pending', 'Deposit Rejected', 'Confirmed', 'Delivered', 'Balance Rejected', 'Payment Submitted', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('status_approved') }}</span>
                                </div>

                                <!-- Dot 4: Confirmed -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Confirmed', 'Delivered', 'Balance Rejected', 'Payment Submitted', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('timeline_confirmed') }}</span>
                                </div>

                                <!-- Dot 5: Delivered -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Delivered', 'Balance Rejected', 'Payment Submitted', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('timeline_delivered') }}</span>
                                </div>

                                <!-- Dot 6: Completed -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('timeline_completed') }}</span>
                                </div>
                            </div>

                            <!-- Standard Order Tracker -->
                            <div v-else class="relative flex items-center justify-between w-full">
                                <!-- Track bar line -->
                                <div class="absolute left-0 right-0 h-0.5 bg-[#E6E1DA] top-1/2 -translate-y-1/2"></div>
                                <div 
                                    class="absolute left-0 h-0.5 bg-[#4A6B5D] top-1/2 -translate-y-1/2 transition-all duration-300"
                                    :style="{
                                        width: 
                                            (order.status === 'Pending' && !order.payment_proof) ? '0%' :
                                            ((order.status === 'Pending' && order.payment_proof) || order.status === 'Deposit Rejected') ? '25%' :
                                            order.status === 'Confirmed' ? '50%' :
                                            (order.status === 'Delivered' || order.status === 'Balance Rejected') ? '75%' :
                                            order.status === 'Payment Submitted' ? '88%' :
                                            order.status === 'Completed' ? '100%' : '0%'
                                    }"
                                ></div>

                                <!-- Dot 1: Pending -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Pending', 'Deposit Rejected', 'Payment Submitted', 'Confirmed', 'Delivered', 'Balance Rejected', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('timeline_submitted') }}</span>
                                </div>

                                <!-- Dot 2: Confirmed -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Confirmed', 'Delivered', 'Balance Rejected', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('timeline_confirmed') }}</span>
                                </div>

                                <!-- Dot 3: Delivered -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="['Delivered', 'Balance Rejected', 'Completed'].includes(order.status) ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('timeline_delivered') }}</span>
                                </div>

                                <!-- Dot 4: Completed -->
                                <div class="flex flex-col items-center gap-1.5 relative">
                                    <div class="progress-dot border border-slate-300" :class="order.status === 'Completed' ? 'bg-[#4A6B5D] border-[#4A6B5D]' : 'bg-white'"></div>
                                    <span class="text-[7px] sm:text-[9px] font-semibold text-[#8C8275] uppercase tracking-normal sm:tracking-wider">{{ t('timeline_completed') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action / Error Notes from Admin Section -->
                        <template v-if="order.admin_note && order.status !== 'Pending Proposal' && order.status !== 'Proposal Sent'">
                            <!-- If status is rejected, show red rejection reason box -->
                            <div v-if="['Deposit Rejected', 'Balance Rejected'].includes(order.status)" class="p-3 sm:p-4 bg-[#FDF2F2] border border-[#FADCDD] text-[10px] sm:text-xs">
                                <span class="font-bold text-[#8C3A3A] uppercase tracking-wider block mb-1 text-[9px] sm:text-[10px]">
                                    <i class="fas fa-exclamation-triangle"></i> {{ t('rejection_reason_admin') }}
                                </span>
                                <p class="text-[#8C3A3A] leading-relaxed font-light">{{ order.admin_note }}</p>
                            </div>
                            <!-- Otherwise, show soft info note from admin/owner -->
                            <div v-else class="p-3 sm:p-4 bg-[#FAF7F2] border border-[#E6E1DA] text-[10px] sm:text-xs">
                                <span class="font-bold text-[#4A6B5D] uppercase tracking-wider block mb-1 text-[9px] sm:text-[10px]">
                                    <i class="fas fa-comment-dots"></i> {{ t('note_from_owner') }}
                                </span>
                                <p class="text-[#5C6460] leading-relaxed font-light">{{ order.admin_note }}</p>
                            </div>
                        </template>

                        <!-- Details & Control Panel -->
                        <div class="flex flex-wrap justify-between items-center gap-3 sm:gap-4 bg-[#FAF6F0] p-3 sm:p-4 border border-[#E6E1DA]">
                            <div class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed">
                                <span class="font-bold text-[#2D3330] uppercase tracking-wider block mb-0.5 text-[8px] sm:text-[10px]">{{ t('package') }}:</span>
                                <span class="font-light">{{ order.package_name }}</span>
                            </div>

                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <Link 
                                    v-if="order.status === 'Proposal Sent'"
                                    :href="route('orders.show', { id: order.id })" 
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-2.5 py-1.5 sm:px-4 sm:py-2.5 rounded-md sm:rounded-lg text-[9px] sm:text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5 cursor-pointer"
                                 >
                                    <i class="fas fa-file-invoice-dollar text-[10px]"></i> {{ t('review_approve') }}
                                </Link>
                                <Link 
                                    v-else
                                    :href="route('orders.show', { id: order.id })" 
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold px-2.5 py-1.5 sm:px-4 sm:py-2.5 rounded-md sm:rounded-lg text-[9px] sm:text-xs uppercase tracking-widest transition-colors inline-flex items-center cursor-pointer"
                                >
                                    <i class="fas fa-search-plus mr-1 text-[10px]"></i> {{ t('view_invoice') }}
                                </Link>

                                <!-- Re-upload or upload receipt button (Shown when rejected, delivered, or pending custom proposal deposit) -->
                                <div v-if="['Deposit Rejected', 'Balance Rejected', 'Delivered'].includes(order.status) || (order.status === 'Pending' && order.is_custom_proposal && !order.payment_proof)" class="relative inline-block">
                                    <input 
                                        type="file" 
                                        :id="`reupload-file-${order.id}`" 
                                        class="hidden" 
                                        @change="handleReceiptSelect($event, order.id, (order.status === 'Deposit Rejected' || order.status === 'Pending') ? 'deposit' : 'balance')"
                                        accept="image/jpeg,image/png,image/jpg,application/pdf"
                                    />
                                    <button 
                                        type="button"
                                        @click="triggerFileSelect(order.id)"
                                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-2.5 py-1.5 sm:px-4 sm:py-2.5 rounded-md sm:rounded-lg text-[9px] sm:text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5 cursor-pointer"
                                        :disabled="processingReupload[order.id]"
                                    >
                                        <i class="fas fa-cloud-upload-alt"></i> 
                                        {{ processingReupload[order.id] ? t('uploading') : (order.status === 'Delivered' ? t('upload_balance_proof') : (order.status === 'Pending' ? t('upload_deposit_proof') : t('reupload_receipt_btn'))) }}
                                    </button>
                                    <span v-if="fileErrors[order.id]" class="text-[9px] sm:text-[10px] text-[#8C3A3A] font-semibold absolute top-full left-0 mt-1 whitespace-nowrap">{{ fileErrors[order.id] }}</span>
                                </div>

                                <!-- Download QR Code button -->
                                <button 
                                    v-if="shouldShowQr(order)"
                                    type="button"
                                    @click="downloadQr()"
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#C5D8D1] text-[#4A6B5D] font-semibold px-2.5 py-1.5 sm:px-4 sm:py-2.5 rounded-md sm:rounded-lg text-[9px] sm:text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5 cursor-pointer"
                                >
                                    <i class="fas fa-qrcode"></i>
                                    {{ t('save_qr') }}
                                </button>
 
                                 <!-- Cancellation Button -->
                                 <button 
                                     v-if="canCancel(order)"
                                     @click="handleCancelOrder(order.id)"
                                     class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-semibold px-2.5 py-1.5 sm:px-4 sm:py-2.5 rounded-md sm:rounded-lg text-[9px] sm:text-xs uppercase tracking-widest transition-colors cursor-pointer"
                                 >
                                     <i class="fas fa-times-circle mr-1 text-[10px]"></i> {{ t('cancel_event_btn') }}
                                 </button>
                            </div>
                        </div>

                        <!-- QR Code Panel for Payments -->
                        <div v-if="shouldShowQr(order)" class="border-t border-[#E6E1DA] bg-white flex flex-col sm:flex-row items-center gap-3 sm:gap-5 p-4 sm:p-5">
                            <div 
                                class="flex-shrink-0 w-20 h-20 sm:w-28 sm:h-28 rounded-xl border border-[#E6E1DA] overflow-hidden shadow-sm bg-white p-1.5 cursor-zoom-in relative group"
                                @click="showQrModal = true"
                                title="Click to zoom"
                            >
                                <img :src="resolveQrPath(qrCodePath)" alt="Payment QR Code" class="w-full h-full object-contain transition-transform duration-200 group-hover:scale-105" />
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-200 flex items-center justify-center">
                                    <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 drop-shadow-md text-lg"></i>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1 sm:gap-2 flex-1">
                                <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-[#4A6B5D] flex items-center gap-1.5">
                                    <i class="fas fa-qrcode"></i>
                                    {{ t('payment_qr_code') }}
                                </span>
                                <p class="text-[10px] sm:text-[11px] text-[#5C6460] font-light leading-relaxed">
                                    <template v-if="isDepositPayment(order)">
                                        {{ t('scan_qr_deposit_info').replace('{depositPercent}', depositPercent).replace('{depositAmount}', getDepositAmount(order.total_price).toFixed(2)) }}
                                    </template>
                                    <template v-else>
                                        {{ t('scan_qr_balance_info').replace('{balance}', getBalanceAmount(order.total_price).toFixed(2)) }}
                                    </template>
                                </p>
                                <button 
                                    type="button"
                                    @click="downloadQr()"
                                    class="self-start mt-1 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-md sm:rounded-lg text-[9px] sm:text-[10px] uppercase tracking-widest transition-colors flex items-center gap-1.5 cursor-pointer"
                                >
                                    <i class="fas fa-download"></i>
                                    {{ t('save_qr_code') }}
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-xl border border-[#E6E1DA] p-12 text-center shadow-sm max-w-xl mx-auto">
                    <div class="w-16 h-16 rounded-xl bg-[#FAF6F0] text-[#8C8275] flex items-center justify-center text-xl mx-auto mb-6 border border-[#E6E1DA]">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h4 class="text-[#2D3330] font-normal font-serif-luxury text-2xl uppercase tracking-wider mb-2">{{ t('no_bookings_found') }}</h4>
                    <p class="text-[#8C8275] text-xs mt-1 mb-8 font-light leading-relaxed">{{ t('no_bookings_found_desc') }}</p>
                    <Link 
                        :href="route('menu.index')"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-8 py-3.5 rounded-lg text-xs uppercase tracking-widest transition-colors"
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
                    <div class="w-64 h-64 rounded-xl border-2 border-[#E6E1DA] overflow-hidden bg-white p-2.5 shadow-inner">
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
