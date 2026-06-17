<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
});

const { t, currentLanguage } = useLocalization();
const page = usePage();
const { confirm } = useConfirm();

const depositPercent = computed(() => {
    return parseFloat(page.props.settings?.deposit_percentage || 30);
});

// deposit calculation
const depositAmount = computed(() => {
    return parseFloat(props.order.total_price) * (depositPercent.value / 100);
});

// balance calculation
const balanceAmount = computed(() => {
    return parseFloat(props.order.total_price) * ((100 - depositPercent.value) / 100);
});

function printReceipt() {
    window.print();
}

function approveProposal() {
    router.post(route('orders.proposal.approve', props.order.id));
}

async function rejectProposal() {
    if (await confirm(
        t('confirm_reject_proposal_msg'),
        t('reject_proposal_title'),
        t('yes_reject'),
        t('cancel_btn')
    )) {
        router.post(route('orders.proposal.reject', props.order.id));
    }
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
        case 'Balance Rejected':
            return t('deposit_rejected') || 'Deposit Rejected';
        case 'Cancelled':
            return t('cancelled_tab') || 'Cancelled';
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
    <Head :title="t('invoice_receipt') + ' #' + order.id" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .receipt-container {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.01);
            padding: 16px;
        }
        @media (min-width: 640px) {
            .receipt-container {
                border-radius: 20px;
                padding: 40px;
            }
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .print-area, .print-area * {
                visibility: visible;
            }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </component>

    <AuthenticatedLayout
        :header-title="t('invoice_receipt') + ' #' + order.id"
        :header-desc="t('order_show_desc')"
    >

        <div class="font-sans-modern">
            <div class="max-w-4xl mx-auto px-3 sm:px-6">
                
                <!-- Action Bar (print/PDF buttons) -->
                <div class="flex flex-wrap items-center justify-end gap-2 mb-6 no-print">
                    <a 
                        :href="route('orders.invoice.pdf', { id: order.id })" 
                        class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold px-4 py-2.5 rounded-lg text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5"
                        target="_blank"
                    >
                        <i class="fas fa-file-pdf text-rose-600 text-[10px]"></i> PDF Invoice
                    </a>
                    <a 
                        v-if="['Confirmed', 'Delivered', 'Completed'].includes(order.status)"
                        :href="route('orders.receipt.pdf', { id: order.id })" 
                        class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold px-4 py-2.5 rounded-lg text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5"
                        target="_blank"
                    >
                        <i class="fas fa-file-pdf text-[#4A6B5D] text-[10px]"></i> PDF Receipt
                    </a>
                    <button 
                        @click="printReceipt" 
                        class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold px-4 py-2.5 rounded-lg text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5"
                    >
                        <i class="fas fa-print text-[10px]"></i> {{ t('print_invoice') }}
                    </button>
                    <Link 
                        :href="route('orders.index')" 
                        class="bg-[#2D3330] hover:bg-[#1C201E] text-white font-semibold px-4 py-2.5 rounded-lg text-xs uppercase tracking-widest transition-colors"
                    >
                        {{ t('back_to_bookings') }}
                    </Link>
                </div>
 
                <!-- Print area container -->
                <div class="receipt-container print-area space-y-8">
                    
                    <!-- Proposal Sent Review Banner -->
                    <div v-if="order.status === 'Proposal Sent'" class="bg-[#FAF9F6] border border-[#C5A880] rounded-lg p-3 sm:p-5 no-print space-y-2.5">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-[#FAF7F2] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-sm border border-[#E6E1DA]">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div>
                                <h4 class="font-serif-luxury text-sm sm:text-lg font-normal text-[#2D3330]">
                                    {{ t('review_custom_proposal') }}
                                </h4>
                                <p class="text-[10px] sm:text-xs text-[#8C8275] font-light">
                                    {{ t('caterer_proposed_custom_desc') }}
                                </p>
                            </div>
                        </div>
 
                        <!-- Admin explanation note if any -->
                        <div v-if="order.admin_note" class="bg-white border border-[#E6E1DA] rounded-lg p-3 sm:p-4 text-[10px] sm:text-xs text-[#5C6460] leading-relaxed">
                            <strong class="font-semibold text-[#2D3330] block mb-1 text-[9px] sm:text-[10px]">
                                {{ t('note_from_owner') }}
                            </strong>
                            <p class="font-light">{{ order.admin_note }}</p>
                        </div>
 
                        <div class="grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:gap-2 pt-1 w-full sm:w-auto">
                            <button 
                                type="button"
                                @click="approveProposal"
                                class="w-full justify-center bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-3.5 py-2 sm:px-5 sm:py-2.5 rounded-lg sm:rounded-xl text-[10px] sm:text-xs uppercase tracking-wider font-semibold transition-colors cursor-pointer inline-flex items-center"
                            >
                                <i class="fas fa-check-circle mr-1 text-[10px]"></i>
                                {{ t('approve_pay_deposit') }}
                            </button>
                            <button 
                                type="button"
                                @click="rejectProposal"
                                class="w-full justify-center bg-white hover:bg-red-50 border border-red-200 text-red-600 px-3.5 py-2 sm:px-5 sm:py-2.5 rounded-lg sm:rounded-xl text-[10px] sm:text-xs uppercase tracking-wider font-semibold transition-colors cursor-pointer inline-flex items-center"
                            >
                                <i class="fas fa-times-circle mr-1 text-[10px]"></i>
                                {{ t('reject_cancel') }}
                            </button>
                        </div>
                    </div>
 
                    <!-- Proposal Pending Banner -->
                    <div v-if="order.status === 'Pending Proposal'" class="bg-[#FAF6F0] border border-[#E6E1DA] rounded-lg p-3 sm:p-5 no-print flex items-center gap-2 sm:gap-2.5">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white text-[#8C8275] flex items-center justify-center text-xs sm:text-sm border border-[#E6E1DA] animate-pulse">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div>
                            <h4 class="font-serif-luxury text-sm sm:text-lg font-normal text-[#2D3330]">
                                {{ t('awaiting_owner_proposal') }}
                            </h4>
                            <p class="text-[10px] sm:text-xs text-[#8C8275] font-light">
                                {{ t('caterer_reviewing_wishlist_desc') }}
                            </p>
                        </div>
                    </div>
 
                    <!-- Invoice Header Brand -->
                    <div class="flex flex-col md:flex-row md:justify-between items-start md:items-center gap-4 border-b border-[#EBEFEF] pb-6 sm:pb-8">
                        <div>
                            <div class="flex items-center mb-1.5 sm:mb-2">
                                <ApplicationLogo />
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-[#8C8275] leading-relaxed font-light">
                                Gong Badak, Kuala Terengganu, Terengganu<br>
                                Support Email: contact@smartservecatering.test
                            </p>
                        </div>
                        <div class="text-left md:text-right uppercase tracking-wider text-xs">
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] block mb-0.5">{{ t('invoice_receipt').split(' ')[0] }}</span>
                            <span class="text-base sm:text-xl font-normal text-[#2D3330] font-serif-luxury tracking-wide block">#SSC-{{ order.id }}</span>
                            <span class="text-[8px] sm:text-[9px] text-[#8C8275] font-semibold">{{ t('issued') }}: {{ new Date(order.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
 
                    <!-- Client & Venue Summary Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-8 border-b border-[#EBEFEF] pb-6 sm:pb-8 text-xs font-sans-modern">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1.5 sm:mb-2">{{ t('billed_to') }}:</span>
                            <span class="font-bold text-[#2D3330] text-xs sm:text-sm block uppercase tracking-wider">{{ $page.props.auth.user.full_name || $page.props.auth.user.name }}</span>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] mt-1 leading-relaxed font-light">
                                {{ t('phone') }}: {{ $page.props.auth.user.phone || 'N/A' }}<br>
                                {{ t('email') }}: {{ $page.props.auth.user.email }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1.5 sm:mb-2">{{ t('event_schedule_venue') }}</span>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">
                                <strong class="font-semibold text-[#2D3330]">{{ t('delivery_event_date_label') }}:</strong> {{ order.delivery_date }}<br>
                                <strong class="font-semibold text-[#2D3330]">{{ t('setup_time') }}:</strong> {{ order.delivery_time }}<br>
                                <strong class="font-semibold text-[#2D3330]">Kawasan Penghantaran:</strong> {{ order.delivery_zone || 'N/A' }} <span v-if="parseFloat(order.delivery_fee) > 0">(RM {{ parseFloat(order.delivery_fee).toFixed(2) }})</span><br>
                                <strong class="font-semibold text-[#2D3330]">Destinasi:</strong> {{ order.delivery_address }}
                            </p>
                            <div v-if="order.notes" class="mt-2.5 pt-2.5 border-t border-[#EBEFEF]">
                                <strong class="font-semibold text-[#2D3330] block mb-1 text-[9px] sm:text-[10px]">
                                    <i class="fas fa-sticky-note text-[9px] text-[#4A6B5D] mr-1"></i>
                                    {{ t('customer_notes') }}:
                                </strong>
                                <p class="whitespace-pre-line text-[#5C6460] text-[10px] sm:text-xs font-light">{{ order.notes }}</p>
                            </div>
                        </div>
                    </div>
 
                    <!-- Itemized Pricing Tables -->
                    <div class="space-y-4 font-sans-modern">
                        <span class="text-[9px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('itemized_breakdown') }}</span>
                        
                        <!-- Desktop View Table (hidden on mobile) -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-left border-collapse align-middle">
                                <thead>
                                    <tr class="border-b border-[#E6E1DA] text-[#8C8275] text-[10px] font-bold uppercase tracking-widest">
                                        <th class="py-3 pl-2">{{ t('catering_service_package') }}</th>
                                        <th class="py-3 text-center w-24">{{ t('price_pax') }}</th>
                                        <th class="py-3 text-center w-24">{{ t('guest_qty') }}</th>
                                        <th class="py-3 text-right pr-2 w-32">{{ t('subtotal') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#EBEFEF] text-xs text-[#5C6460]">
                                    <tr v-for="item in order.items" :key="item.id" class="hover:bg-[#FAF6F0]/20">
                                        <td class="py-4 pl-2">
                                            <span class="font-semibold text-[#2D3330] uppercase tracking-wider block">{{ item.package?.package_name || order.package_name }}</span>
                                            
                                            <!-- Dishes grouped by category -->
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2 max-w-xl">
                                                <div 
                                                    v-for="(dishes, category) in getGroupedDishes(item)" 
                                                    :key="category" 
                                                    class="bg-[#FAF7F2] border border-[#EBEFEF] p-1.5 sm:p-2 rounded-md sm:rounded-lg space-y-1"
                                                >
                                                    <span class="text-[8px] font-extrabold text-[#4A6B5D] uppercase tracking-widest block border-b border-[#EBEFEF] pb-0.5">{{ category }}</span>
                                                    <ul class="space-y-0.5">
                                                        <li v-for="dish in dishes" :key="dish.name" class="text-[9px] font-bold text-[#5C6460] flex items-center gap-1">
                                                            <i class="fas fa-check text-[6px] text-[#4A6B5D]"></i> 
                                                            <span>{{ dish.name }}</span>
                                                            <span v-if="dish.isDefault" class="text-[7px] text-[#8C8275] italic font-normal">(Default)</span>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <div v-if="Object.keys(getGroupedDishes(item)).length === 0" class="col-span-2 text-[9px] text-[#8C8275] italic">
                                                    No dishes defined.
                                                </div>
                                            </div>
 
                                            <!-- Selected Addons -->
                                            <div v-if="item.selected_addons && item.selected_addons.length > 0" class="mt-2.5 space-y-1.5 max-w-xl">
                                                <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block">Add-ons:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    <span 
                                                        v-for="addon in item.selected_addons" 
                                                        :key="addon"
                                                        class="inline-flex items-center gap-1 bg-amber-50 border border-amber-200 text-[8px] text-amber-800 font-bold px-2 py-0.5 rounded-full"
                                                    >
                                                        <i class="fas fa-plus text-[6px] text-[#C5A880]"></i> {{ addon }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 text-center whitespace-nowrap">
                                            RM {{ parseFloat(item.price).toFixed(2) }}
                                        </td>
                                        <td class="py-4 text-center">
                                            {{ item.quantity }} {{ t('pax') }}
                                        </td>
                                        <td class="py-4 text-right pr-2 font-semibold text-[#2D3330] whitespace-nowrap">
                                            RM {{ parseFloat(item.subtotal).toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile view card list (hidden on desktop) -->
                        <div class="block sm:hidden space-y-3">
                            <div v-for="item in order.items" :key="item.id" class="border border-[#E6E1DA] rounded-lg p-2.5 bg-[#FAF8F5]/30 space-y-2">
                                <div>
                                    <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('catering_service_package') }}</span>
                                    <span class="text-[10px] font-bold text-[#2D3330] uppercase tracking-wider block">{{ item.package?.package_name || order.package_name }}</span>
                                </div>
                                
                                <div class="flex justify-between text-[10px] border-t border-[#EBEFEF] pt-2">
                                    <div>
                                        <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('price_pax') }}</span>
                                        <span class="text-[10px] text-[#5C6460]">RM {{ parseFloat(item.price).toFixed(2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('guest_qty') }}</span>
                                        <span class="text-[10px] text-[#5C6460]">{{ item.quantity }} {{ t('pax') }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('subtotal') }}</span>
                                        <span class="text-[10px] font-bold text-[#2D3330]">RM {{ parseFloat(item.subtotal).toFixed(2) }}</span>
                                    </div>
                                </div>

                                <!-- Dishes grouped by category -->
                                <div class="border-t border-[#EBEFEF] pt-2 space-y-1.5">
                                    <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('included_dishes') }}</span>
                                    <div class="grid grid-cols-1 gap-1.5">
                                        <div 
                                            v-for="(dishes, category) in getGroupedDishes(item)" 
                                            :key="category" 
                                            class="bg-white border border-[#EBEFEF] p-1.5 rounded-lg space-y-1"
                                        >
                                            <span class="text-[8px] font-extrabold text-[#4A6B5D] uppercase tracking-widest block border-b border-[#EBEFEF] pb-0.5">{{ category }}</span>
                                            <ul class="space-y-0.5">
                                                <li v-for="dish in dishes" :key="dish.name" class="text-[8px] font-semibold text-[#5C6460] flex items-center gap-1">
                                                    <i class="fas fa-check text-[5px] text-[#4A6B5D]"></i> 
                                                    <span>{{ dish.name }}</span>
                                                    <span v-if="dish.isDefault" class="text-[7px] text-[#8C8275] italic font-normal">(Default)</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Addons -->
                                <div v-if="item.selected_addons && item.selected_addons.length > 0" class="border-t border-[#EBEFEF] pt-2 space-y-1">
                                    <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block">Add-ons:</span>
                                    <div class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="addon in item.selected_addons" 
                                            :key="addon"
                                            class="inline-flex items-center gap-1 bg-amber-50 border border-amber-200 text-[8px] text-amber-800 font-bold px-2 py-0.5 rounded-full"
                                        >
                                            <i class="fas fa-plus text-[6px] text-[#C5A880]"></i> {{ addon }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Overview Table -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 border-t border-[#E6E1DA] pt-6 sm:pt-8 font-sans-modern">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1.5 sm:mb-2">{{ t('payment_trans_status') }}</span>
                            
                            <div class="inline-flex items-center px-2 py-0.5 sm:px-3 sm:py-1 text-[8px] sm:text-[10px] font-semibold border rounded-full uppercase tracking-widest mb-2.5 sm:mb-3" :class="getStatusBadge(order.status)">
                                {{ t('status_label') }}: {{ getTranslatedStatus(order.status) }}
                            </div>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">
                                {{ t('payment_desc_invoice') }}
                            </p>
                        </div>
                        
                        <div class="space-y-1.5 sm:space-y-2 text-[10px] sm:text-xs uppercase tracking-wider text-[#8C8275] self-end">
                            <div class="flex justify-between">
                                <span>{{ t('subtotal') }}</span>
                                <span class="font-bold text-[#2D3330]">RM {{ (parseFloat(order.total_price) - parseFloat(order.delivery_fee || 0) + parseFloat(order.discount_amount || 0)).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ t('delivery_fee_label') }} ({{ order.delivery_zone || 'N/A' }})</span>
                                <span class="font-bold text-[#2D3330]">RM {{ parseFloat(order.delivery_fee || 0).toFixed(2) }}</span>
                            </div>
                            <div v-if="parseFloat(order.discount_amount) > 0" class="flex justify-between text-emerald-700 font-semibold">
                                <span>{{ t('discount') }}</span>
                                <span>- RM {{ parseFloat(order.discount_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-[#E6E1DA] pt-2 text-[#8C8275]">  
                                <span>{{ t('grand_total') }}</span>
                                <span class="font-bold text-[#2D3330]">RM {{ parseFloat(order.total_price).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-[#8C3A3A] font-bold">
                                <span>{{ t('deposit_paid_invoice') }}</span>
                                <span>RM {{ depositAmount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ t('remaining_balance_invoice') }}</span>
                                <span>RM {{ balanceAmount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-[#E6E1DA] pt-2.5 sm:pt-3 text-[10px] sm:text-xs font-bold text-[#2D3330] items-center">
                                <span class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-wider">{{ t('grand_total') }}:</span>
                                <span class="text-sm sm:text-base font-black text-[#2D3330]">RM {{ parseFloat(order.total_price).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Details -->
                    <div class="border-t border-[#EBEFEF] pt-8 text-center text-[9px] text-[#8C8275] uppercase tracking-widest">
                        {{ t('thank_you_footer') }}
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
