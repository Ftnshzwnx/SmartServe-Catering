<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalization } from '@/Composables/useLocalization';

const { t } = useLocalization();

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    pendingVerification: {
        type: Array,
        required: true,
    },
    upcomingEvents: {
        type: Array,
        required: true,
    },
    recentReviews: {
        type: Array,
        required: true,
    },
});

const { toast } = useToast();
const { confirm, prompt } = useConfirm();

const showDetailsModal = ref(false);
const selectedOrderDetails = ref(null);

function openDetailsModal(order) {
    selectedOrderDetails.value = order;
    showDetailsModal.value = true;
}

function closeDetailsModal() {
    showDetailsModal.value = false;
    selectedOrderDetails.value = null;
}

const verifyForm = useForm({
    action: '',
    admin_note: '',
});

async function handleVerify(orderId, actionType) {
    if (actionType === 'reject') {
        const note = await prompt(t('admin_enter_reject_reason'), t('admin_reject_payment'));
        if (note === null) return;
        if (!note.trim()) {
            toast(t('admin_rejection_reason_required'), 'error');
            return;
        }
        verifyForm.admin_note = note;
    } else {
        verifyForm.admin_note = '';
        if (!(await confirm(t('admin_confirm_approve_payment'), t('admin_approve_payment')))) return;
    }

    verifyForm.action = actionType;
    verifyForm.post(route('admin.orders.verify', { id: orderId }), {
        onSuccess: () => {
            toast(t('admin_toast_status_updated'));
            closeDetailsModal();
        }
    });
}

async function handleDeliver(orderId) {
    if (!(await confirm(t('admin_confirm_deliver'), t('admin_mark_delivered_title')))) return;
    
    router.post(route('admin.orders.deliver', { id: orderId }), {}, {
        onSuccess: () => {
            closeDetailsModal();
        }
    });
}

function getStatusBadge(status) {
    switch (status) {
        case 'Pending':
            return 'bg-amber-50 text-amber-800 border-amber-200';
        case 'Confirmed':
            return 'bg-emerald-50 text-[#4A6B5D] border-emerald-200';
        case 'Payment Submitted':
            return 'bg-indigo-50 text-indigo-800 border-indigo-200';
        case 'Delivered':
            return 'bg-teal-50 text-teal-800 border-teal-200';
        case 'Completed':
            return 'bg-green-50 text-green-800 border-green-200';
        case 'Deposit Rejected':
        case 'Balance Rejected':
            return 'bg-rose-50 text-rose-800 border-rose-200';
        case 'Cancelled':
            return 'bg-slate-100 text-slate-800 border-slate-200';
        default:
            return 'bg-slate-100 text-slate-800 border-slate-200';
    }
}

function getTranslatedStatus(status) {
    switch (status) {
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

function getGroupedDishes(item) {
    const dishesList = [];
    if (item.selected_dishes && item.selected_dishes.length > 0) {
        item.selected_dishes.forEach(d => {
            if (typeof d === 'object' && d !== null) {
                dishesList.push({
                    name: d.name,
                    category: d.category || 'Others',
                    isDefault: false
                });
            } else {
                const matchedDish = item.package?.dishes?.find(pd => pd.name === d);
                dishesList.push({
                    name: d,
                    category: matchedDish ? matchedDish.category : 'Others',
                    isDefault: false
                });
            }
        });
    } else if (item.package) {
        const defaultNames = item.package.description 
            ? item.package.description.split('\n').map(name => name.trim()).filter(Boolean)
            : [];
            
        if (defaultNames.length > 0) {
            defaultNames.forEach(name => {
                const matchedDish = item.package.dishes?.find(pd => pd.name === name);
                dishesList.push({
                    name: name,
                    category: matchedDish ? matchedDish.category : 'Others',
                    isDefault: true
                });
            });
        } else if (item.package.dishes && item.package.dishes.length > 0) {
            item.package.dishes.forEach(d => {
                dishesList.push({
                    name: d.name,
                    category: d.category || 'Others',
                    isDefault: true
                });
            });
        }
    }

    // Group by category
    const grouped = {};
    dishesList.forEach(dish => {
        const cat = dish.category;
        if (!grouped[cat]) {
            grouped[cat] = [];
        }
        grouped[cat].push(dish);
    });
    return grouped;
}
</script>

<template>
    <AdminLayout
        :title="t('admin_dashboard_overview')"
        :header-title="t('admin_dashboard')"
        :header-desc="t('admin_operational_desc')"
    >
        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
            <!-- Revenue -->
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs p-3 md:p-6 flex flex-col justify-between gap-2.5 animate-fade-in">
                <div class="flex items-center justify-between gap-1.5">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block truncate" :title="t('admin_total_revenue')">{{ t('admin_total_revenue') }}</span>
                    <div class="w-8 h-8 bg-emerald-50 text-[#4A6B5D] rounded-lg border border-emerald-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div>
                    <span class="text-sm md:text-2xl font-extrabold text-[#2D3330] font-serif-luxury block truncate">RM {{ parseFloat(metrics.totalRevenue || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                </div>
            </div>

            <!-- Total Orders -->
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs p-3 md:p-6 flex flex-col justify-between gap-2.5 animate-fade-in" style="animation-delay: 50ms;">
                <div class="flex items-center justify-between gap-1.5">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block truncate" :title="t('admin_total_bookings')">{{ t('admin_total_bookings') }}</span>
                    <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg border border-blue-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
                <div>
                    <span class="text-sm md:text-2xl font-extrabold text-[#2D3330] font-serif-luxury block truncate">{{ metrics.totalOrders }}</span>
                </div>
            </div>

            <!-- Pending Verification -->
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs p-3 md:p-6 flex flex-col justify-between gap-2.5 animate-fade-in" style="animation-delay: 100ms;">
                <div class="flex items-center justify-between gap-1.5">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block truncate" :title="t('admin_pending_verify')">{{ t('admin_pending_verify') }}</span>
                    <div class="w-8 h-8 bg-amber-50 text-[#C5A880] rounded-lg border border-amber-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div>
                    <span class="text-sm md:text-2xl font-extrabold text-[#2D3330] font-serif-luxury block truncate">{{ metrics.pendingPayment }}</span>
                </div>
            </div>

            <!-- Completed -->
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs p-3 md:p-6 flex flex-col justify-between gap-2.5 animate-fade-in" style="animation-delay: 150ms;">
                <div class="flex items-center justify-between gap-1.5">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block truncate" :title="t('admin_completed_events')">{{ t('admin_completed_events') }}</span>
                    <div class="w-8 h-8 bg-green-50 text-green-600 rounded-lg border border-green-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div>
                    <span class="text-sm md:text-2xl font-extrabold text-[#2D3330] font-serif-luxury block truncate">{{ metrics.completedOrders }}</span>
                </div>
            </div>
        </div>

        <!-- Split Layout Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- LEFT COLUMN: Operational Actions & Upcoming Events (8 cols) -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- Section 1: Bookings Requiring Receipt Verification -->
                <div class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs p-6 md:p-8 space-y-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_pending_verifications') }}</h2>
                            <p class="text-[10px] text-amber-600 font-bold mt-0.5"><i class="fas fa-exclamation-circle mr-1"></i> {{ t('admin_receipts_waiting') }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <Link :href="route('admin.orders')" class="text-xs font-semibold text-[#4A6B5D] hover:underline uppercase tracking-wider text-[10px]">{{ t('admin_view_orders') }}</Link>
                            <span class="text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-0.5 rounded-full">
                                {{ pendingVerification.length }} {{ pendingVerification.length !== 1 ? t('admin_actions_required') : t('admin_action_required') }}
                            </span>
                        </div>
                    </div>

                    <div v-if="pendingVerification.length > 0" class="divide-y divide-[#E6E1DA]">
                        <div 
                            v-for="order in pendingVerification" 
                            :key="order.id"
                            class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 first:pt-0 last:pb-0"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <button 
                                        @click="openDetailsModal(order)"
                                        class="font-extrabold text-xs text-[#4A6B5D] hover:text-[#3D574B] hover:underline cursor-pointer"
                                        title="View Booking Details"
                                    >
                                        #SSC-{{ order.id }}
                                    </button>
                                    <span class="inline-flex text-[9px] font-bold px-2 py-0.5 rounded-full border" :class="getStatusBadge(order.status)">
                                        {{ order.status === 'Pending' ? t('timeline_confirmed') : t('balance_submitted_verification') }}
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-[#2D3330]">
                                    {{ order.user?.full_name || order.user?.name || 'Customer' }} - {{ order.package_name }}
                                </p>
                                <p class="text-[10px] text-[#8C8275] font-semibold">
                                    {{ t('admin_event_date') }}: {{ order.delivery_date }} ({{ order.delivery_time }})
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <!-- View Receipt slip -->
                                <a 
                                    :href="'/' + order.payment_proof" 
                                    target="_blank"
                                    class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold px-3 py-2 rounded-xl text-[10px] uppercase tracking-wider transition-colors flex items-center gap-1"
                                >
                                    <i class="fas fa-file-invoice text-[9px]"></i> {{ t('admin_view_slip') }}
                                </a>
                                <!-- Quick Approve -->
                                <button
                                    @click="handleVerify(order.id, 'approve')"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-2 rounded-xl text-[10px] uppercase tracking-wider transition-colors flex items-center gap-1 cursor-pointer"
                                >
                                    <i class="fas fa-check text-[9px]"></i> {{ t('admin_approve') }}
                                </button>
                                <!-- Quick Reject -->
                                <button
                                    @click="handleVerify(order.id, 'reject')"
                                    class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold px-3 py-2 rounded-xl text-[10px] uppercase tracking-wider transition-colors flex items-center gap-1 cursor-pointer"
                                >
                                    <i class="fas fa-times text-[9px]"></i> {{ t('admin_reject') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="py-10 text-center text-[#8C8275] border border-dashed border-[#E6E1DA] rounded-2xl flex flex-col items-center justify-center">
                        <div class="w-10 h-10 bg-emerald-50 text-[#4A6B5D] rounded-full flex items-center justify-center text-sm mb-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5 class="text-xs font-bold text-[#2D3330]">{{ t('admin_all_caught_up') }}</h5>
                        <p class="text-[10px] text-[#8C8275] mt-1">{{ t('admin_no_pending_receipts') }}</p>
                    </div>
                </div>

                <!-- Section 2: Upcoming Bookings / Deliveries (next 7 days) -->
                <div class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs p-6 md:p-8 space-y-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_upcoming_event_gigs') }}</h2>
                            <p v-if="t('admin_scheduled_bookings_desc')" class="text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ t('admin_scheduled_bookings_desc') }}</p>
                        </div>
                        <Link :href="route('admin.orders')" class="text-xs font-semibold text-[#4A6B5D] hover:underline uppercase tracking-wider text-[10px]">{{ t('admin_view_calendar') }}</Link>
                    </div>

                    <div v-if="upcomingEvents.length > 0" class="overflow-x-auto scrollbar-none pb-2">
                        <table class="w-full text-left border-collapse min-w-[650px]">
                            <thead>
                                <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                    <th class="px-3 sm:px-5 py-2.5 sm:py-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_id') }}</th>
                                    <th class="px-3 sm:px-5 py-2.5 sm:py-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_customer') }}</th>
                                    <th class="px-3 sm:px-5 py-2.5 sm:py-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_package_details') }}</th>
                                    <th class="px-3 sm:px-5 py-2.5 sm:py-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_event_date') }}</th>
                                    <th class="px-3 sm:px-5 py-2.5 sm:py-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">{{ t('status_label') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E6E1DA] text-xs text-[#5C6460]">
                                <tr v-for="order in upcomingEvents" :key="order.id" class="hover:bg-[#FAFAF9] transition-colors">
                                    <td class="px-3 sm:px-5 py-2.5 sm:py-3.5">
                                        <button 
                                            @click="openDetailsModal(order)"
                                            class="font-extrabold text-[#4A6B5D] font-serif-luxury hover:text-[#3D574B] hover:underline cursor-pointer"
                                            title="View Booking Details"
                                        >
                                            #SSC-{{ order.id }}
                                        </button>
                                    </td>
                                    <td class="px-3 sm:px-5 py-2.5 sm:py-3.5 font-bold text-[#2D3330]">{{ order.user?.full_name || order.user?.name || 'Customer' }}</td>
                                    <td class="px-3 sm:px-5 py-2.5 sm:py-3.5 max-w-[150px] truncate" :title="order.package_name">{{ order.package_name }}</td>
                                    <td class="px-3 sm:px-5 py-2.5 sm:py-3.5 font-medium">{{ order.delivery_date }} ({{ order.delivery_time }})</td>
                                    <td class="px-3 sm:px-5 py-2.5 sm:py-3.5 text-center">
                                        <span class="inline-flex text-[9px] font-bold px-2 py-0.5 rounded-full border" :class="getStatusBadge(order.status)">
                                            {{ getTranslatedStatus(order.status) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div v-else class="py-12 text-center text-[#8C8275] border border-dashed border-[#E6E1DA] rounded-2xl">
                        <i class="fas fa-truck text-3xl mb-2 text-slate-300"></i>
                        <h5 class="text-xs font-bold text-[#2D3330]">{{ t('admin_no_upcoming_events') }}</h5>
                        <p class="text-[10px] text-[#8C8275] mt-1">{{ t('admin_no_upcoming_desc') }}</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Quick Links & Recent Feedback Reviews (4 cols) -->
            <div class="lg:col-span-4 space-y-8">
                
                <!-- Quick Navigation Links Panel -->
                <div class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs p-6 md:p-8 space-y-6">
                    <h2 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_quick_operations') }}</h2>
                    
                    <div class="grid grid-cols-1 gap-2.5">
                        <Link 
                            :href="route('admin.orders')" 
                            class="flex items-center justify-between p-3 border border-[#E6E1DA] rounded-2xl hover:border-[#4A6B5D] hover:bg-[#FAF7F2]/40 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0 border border-blue-100">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <span class="text-xs font-bold text-[#5C6460]">{{ t('admin_manage_orders') }}</span>
                            </div>
                            <i class="fas fa-chevron-right text-[9px] text-[#8C8275] transition-transform group-hover:translate-x-0.5"></i>
                        </Link>
                        
                        <Link 
                            :href="route('admin.packages')" 
                            class="flex items-center justify-between p-3 border border-[#E6E1DA] rounded-2xl hover:border-[#4A6B5D] hover:bg-[#FAF7F2]/40 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-[#4A6B5D] flex items-center justify-center text-xs shrink-0 border border-emerald-100">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <span class="text-xs font-bold text-[#5C6460]">{{ t('admin_catering_packages') }}</span>
                            </div>
                            <i class="fas fa-chevron-right text-[9px] text-[#8C8275] transition-transform group-hover:translate-x-0.5"></i>
                        </Link>

                        <Link 
                            :href="route('admin.calendar')" 
                            class="flex items-center justify-between p-3 border border-[#E6E1DA] rounded-2xl hover:border-[#4A6B5D] hover:bg-[#FAF7F2]/40 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xs shrink-0 border border-purple-100">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <span class="text-xs font-bold text-[#5C6460]">{{ t('admin_booking_calendar') }}</span>
                            </div>
                            <i class="fas fa-chevron-right text-[9px] text-[#8C8275] transition-transform group-hover:translate-x-0.5"></i>
                        </Link>

                        <Link 
                            :href="route('admin.reports')" 
                            class="flex items-center justify-between p-3 border border-[#E6E1DA] rounded-2xl hover:border-[#4A6B5D] hover:bg-[#FAF7F2]/40 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-amber-50 text-[#C5A880] flex items-center justify-center text-xs shrink-0 border border-amber-100">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <span class="text-xs font-bold text-[#5C6460]">{{ t('admin_reports_analytics') }}</span>
                            </div>
                            <i class="fas fa-chevron-right text-[9px] text-[#8C8275] transition-transform group-hover:translate-x-0.5"></i>
                        </Link>

                        <Link 
                            :href="route('admin.settings')" 
                            class="flex items-center justify-between p-3 border border-[#E6E1DA] rounded-2xl hover:border-[#4A6B5D] hover:bg-[#FAF7F2]/40 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs shrink-0 border border-slate-200">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <span class="text-xs font-bold text-[#5C6460]">{{ t('admin_system_settings') }}</span>
                            </div>
                            <i class="fas fa-chevron-right text-[9px] text-[#8C8275] transition-transform group-hover:translate-x-0.5"></i>
                        </Link>
                    </div>
                </div>

                <!-- Recent Feedback Reviews summary panel -->
                <div class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs p-6 md:p-8 space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_customer_reviews') }}</h2>
                        <Link :href="route('admin.reviews')" class="text-xs font-semibold text-[#4A6B5D] hover:underline uppercase tracking-wider text-[10px]">{{ t('admin_reply_all') }}</Link>
                    </div>

                    <div v-if="recentReviews.length > 0" class="space-y-4">
                        <div 
                            v-for="review in recentReviews" 
                            :key="review.id" 
                            class="text-xs bg-[#FAF7F2] p-4 rounded-2xl border border-[#E6E1DA] space-y-2"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-[#2D3330] truncate max-w-[130px]">
                                    {{ review.user?.full_name || review.user?.name || 'Customer' }}
                                </span>
                                <!-- Stars -->
                                <div class="flex text-amber-400 text-[9px] shrink-0">
                                    <i v-for="star in 5" :key="star" class="fas fa-star" :class="star <= review.rating ? '' : 'text-gray-300'"></i>
                                </div>
                            </div>
                            <p class="text-[#5C6460] font-medium leading-relaxed italic text-[11px]">
                                "{{ review.review_text || t('admin_no_comment') }}"
                            </p>
                            <span class="text-[9px] font-bold text-[#8C8275] block uppercase tracking-wider">
                                Order #SSC-{{ review.order_id }}
                            </span>
                        </div>
                    </div>
                    
                    <div v-else class="py-8 text-center text-[#8C8275] border border-dashed border-[#E6E1DA] rounded-2xl">
                        <i class="far fa-star text-2xl mb-2 text-slate-300"></i>
                        <h5 class="text-xs font-bold text-[#2D3330]">{{ t('admin_no_reviews_yet') }}</h5>
                        <p class="text-[10px] text-[#8C8275]">{{ t('admin_client_ratings_appear') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Order Viewer Modal -->
        <div v-if="showDetailsModal && selectedOrderDetails" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4 overflow-y-auto">
            <div class="bg-white rounded-3xl border border-[#E6E1DA] w-full max-w-2xl shadow-xl overflow-hidden flex flex-col my-8 animate-fade-in">
                <!-- Modal Header -->
                <div class="bg-[#FAF7F2] px-6 py-4 border-b border-[#E6E1DA] flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="font-extrabold text-sm text-[#4A6B5D] font-serif-luxury uppercase tracking-wide">
                            {{ t('admin_booking') }} #SSC-{{ selectedOrderDetails.id }}
                        </span>
                        <span class="inline-flex text-[9px] font-bold px-2 py-0.5 rounded-full border" :class="getStatusBadge(selectedOrderDetails.status)">
                            {{ selectedOrderDetails.status === 'Pending' ? t('admin_deposit_submitted') : (selectedOrderDetails.status === 'Payment Submitted' ? t('admin_balance_submitted') : getTranslatedStatus(selectedOrderDetails.status)) }}
                        </span>
                    </div>
                    <button @click="closeDetailsModal" class="text-[#8C8275] hover:text-[#2D3330] transition-colors cursor-pointer">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 md:p-8 space-y-6 overflow-y-auto max-h-[60vh] text-xs">
                    <!-- Customer and Event Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-[#E6E1DA]">
                        <div class="space-y-2.5">
                            <h4 class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                <i class="fas fa-user text-[#C5A880]"></i> {{ t('admin_customer_information') }}
                            </h4>
                            <div class="space-y-1">
                                <p class="font-bold text-[#2D3330] text-sm">{{ selectedOrderDetails.user?.full_name || selectedOrderDetails.user?.name || 'Customer' }}</p>
                                <p class="text-[#5C6460] font-semibold"><i class="far fa-envelope mr-1.5 text-gray-400"></i>{{ selectedOrderDetails.user?.email }}</p>
                                <p class="text-[#5C6460] font-semibold"><i class="fas fa-phone-alt mr-1.5 text-gray-400"></i>{{ selectedOrderDetails.user?.phone || t('admin_no_phone') }}</p>
                            </div>
                        </div>
                        <div class="space-y-2.5">
                            <h4 class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                <i class="fas fa-calendar-alt text-[#C5A880]"></i> {{ t('admin_event_details') }}
                            </h4>
                            <div class="space-y-1">
                                <p class="font-bold text-[#2D3330]"><span class="text-[#8C8275] font-semibold">{{ t('admin_date') }}:</span> {{ selectedOrderDetails.delivery_date }}</p>
                                <p class="font-bold text-[#2D3330]"><span class="text-[#8C8275] font-semibold">{{ t('admin_time') }}:</span> {{ selectedOrderDetails.delivery_time }}</p>
                                <p class="font-bold text-[#2D3330]"><span class="text-[#8C8275] font-semibold">{{ t('admin_delivery_option') }}:</span> {{ selectedOrderDetails.delivery_zone || 'Standard' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Venue Address -->
                    <div class="pb-6 border-b border-[#E6E1DA] space-y-2">
                        <h4 class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                            <i class="fas fa-map-marker-alt text-[#C5A880]"></i> {{ t('admin_venue_address') }}
                        </h4>
                        <p class="text-[#2D3330] font-bold leading-relaxed bg-[#FAF7F2] p-3.5 rounded-2xl border border-[#E6E1DA]">
                            {{ selectedOrderDetails.delivery_address || t('admin_no_address') }}
                        </p>
                    </div>

                    <!-- Menu & Packages Items -->
                    <div class="pb-6 border-b border-[#E6E1DA] space-y-3">
                        <h4 class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                            <i class="fas fa-utensils text-[#C5A880]"></i> {{ t('admin_selected_packages_dishes') }}
                        </h4>
                        <div class="space-y-4">
                            <div v-for="item in selectedOrderDetails.items" :key="item.id" class="space-y-3">
                                <div class="flex justify-between items-center bg-[#FAF7F2]/50 px-3 py-2 rounded-xl border border-[#E6E1DA]/60">
                                    <span class="font-bold text-[#2D3330] text-sm">{{ item.package?.package_name || selectedOrderDetails.package_name }}</span>
                                    <span class="text-xs text-[#4A6B5D] font-extrabold">{{ item.quantity }} {{ t('admin_pax') }}</span>
                                </div>
                                
                                <!-- Dishes grouped by category -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-2">
                                    <div 
                                        v-for="(dishes, category) in getGroupedDishes(item)" 
                                        :key="category" 
                                        class="bg-[#FAF7F2] border border-[#E6E1DA] p-3 rounded-2xl space-y-1.5"
                                    >
                                        <span class="text-[9px] font-extrabold text-[#4A6B5D] uppercase tracking-widest block border-b border-[#E6E1DA] pb-1">{{ category }}</span>
                                        <ul class="space-y-1">
                                            <li v-for="dish in dishes" :key="dish.name" class="text-[10px] font-bold text-[#5C6460] flex items-center gap-1.5">
                                                <i class="fas fa-check text-[7px] text-[#4A6B5D]"></i> 
                                                <span>{{ dish.name }}</span>
                                                <span v-if="dish.isDefault" class="text-[8px] text-[#8C8275] italic font-normal">{{ t('admin_default_dish') }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div v-if="Object.keys(getGroupedDishes(item)).length === 0" class="col-span-2 text-[10px] text-[#8C8275] italic">
                                        {{ t('admin_no_dishes') }}
                                    </div>
                                </div>

                                <!-- Selected Addons -->
                                <div v-if="item.selected_addons && item.selected_addons.length > 0" class="mt-2 pl-2 space-y-1.5">
                                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_addons') }}</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-for="addon in item.selected_addons" :key="addon" class="bg-amber-50 border border-amber-200 text-[9px] text-amber-800 font-bold px-2.5 py-1 rounded-lg flex items-center gap-1.5">
                                            <i class="fas fa-plus text-[8px] text-[#C5A880]"></i> {{ addon }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Notes -->
                    <div v-if="selectedOrderDetails.notes" class="pb-6 border-b border-[#E6E1DA] space-y-2">
                        <h4 class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                            <i class="fas fa-comment-dots text-[#C5A880]"></i> {{ t('customer_notes') }}
                        </h4>
                        <p class="text-[#5C6460] font-medium leading-relaxed italic bg-amber-50/40 p-3.5 rounded-2xl border border-amber-100">
                            "{{ selectedOrderDetails.notes }}"
                        </p>
                    </div>

                    <!-- Billing Summary -->
                    <div class="space-y-2.5">
                        <h4 class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                            <i class="fas fa-file-invoice-dollar text-[#C5A880]"></i> {{ t('admin_billing_breakdown') }}
                        </h4>
                        <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-[#E6E1DA] space-y-2">
                            <div class="flex justify-between font-semibold text-[#5C6460]">
                                <span>{{ t('admin_subtotal') }}</span>
                                <span>RM {{ (parseFloat(selectedOrderDetails.total_price) - parseFloat(selectedOrderDetails.delivery_fee || 0) + parseFloat(selectedOrderDetails.discount_amount || 0)).toFixed(2) }}</span>
                            </div>
                            <div v-if="parseFloat(selectedOrderDetails.delivery_fee) > 0" class="flex justify-between font-semibold text-[#5C6460]">
                                <span>{{ t('admin_delivery_fee') }} ({{ selectedOrderDetails.delivery_zone }})</span>
                                <span>RM {{ parseFloat(selectedOrderDetails.delivery_fee).toFixed(2) }}</span>
                            </div>
                            <div v-if="parseFloat(selectedOrderDetails.discount_amount) > 0" class="flex justify-between font-semibold text-emerald-700">
                                <span>{{ t('admin_discount') }}</span>
                                <span>- RM {{ parseFloat(selectedOrderDetails.discount_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-[#E6E1DA] pt-2 mt-2">
                                <span>{{ t('admin_grand_total') }}</span>
                                <span class="font-black text-[#2D3330] text-base">RM {{ parseFloat(selectedOrderDetails.total_price).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-[#FAF7F2] px-6 py-4 border-t border-[#E6E1DA] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <button
                        @click="closeDetailsModal"
                        class="bg-white hover:bg-gray-50 border border-[#E6E1DA] text-[#5C6460] font-bold px-4 py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition-colors cursor-pointer text-center"
                    >
                        {{ t('admin_close') }}
                    </button>
                    
                    <div class="flex items-center gap-2.5 justify-end">
                        <!-- Download Kitchen Slip PDF -->
                        <a 
                            :href="route('orders.invoice.pdf', selectedOrderDetails.id)" 
                            class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5"
                        >
                            <i class="fas fa-file-pdf text-[9px]"></i> {{ t('admin_kitchen_slip') }}
                        </a>

                        <!-- View Receipt slip -->
                        <a 
                            v-if="selectedOrderDetails.payment_proof"
                            :href="'/' + selectedOrderDetails.payment_proof" 
                            target="_blank"
                            class="bg-white hover:bg-gray-50 border border-[#E6E1DA] text-[#5C6460] font-bold px-4 py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5"
                        >
                            <i class="fas fa-file-invoice text-[9px]"></i> {{ t('admin_view_slip') }}
                        </a>
                        
                        <!-- Actions inside modal if order is pending/needs verification -->
                        <template v-if="selectedOrderDetails.status === 'Pending' || selectedOrderDetails.status === 'Payment Submitted'">
                            <button
                                @click="handleVerify(selectedOrderDetails.id, 'approve')"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <i class="fas fa-check text-[9px]"></i> {{ t('admin_approve') }}
                            </button>
                            <button
                                @click="handleVerify(selectedOrderDetails.id, 'reject')"
                                class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold px-4 py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <i class="fas fa-times text-[9px]"></i> {{ t('admin_reject') }}
                            </button>
                        </template>

                        <!-- Actions inside modal if order is confirmed -->
                        <template v-if="selectedOrderDetails.status === 'Confirmed'">
                            <button
                                @click="handleDeliver(selectedOrderDetails.id)"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <i class="fas fa-truck text-[9px]"></i> {{ t('admin_mark_as_delivered') }}
                            </button>
                        </template>
                    </div>
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
