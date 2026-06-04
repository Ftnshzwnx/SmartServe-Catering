<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

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

const { t } = useLocalization();

// 30% deposit calculation
const depositAmount = computed(() => {
    return parseFloat(props.order.total_price) * 0.3;
});

// 70% balance calculation
const balanceAmount = computed(() => {
    return parseFloat(props.order.total_price) * 0.7;
});

function printReceipt() {
    window.print();
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
    <Head :title="t('invoice_receipt') + ' #' + order.id" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .receipt-container {
            background: #ffffff;
            border-radius: 0px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.01);
            padding: 40px;
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

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between no-print font-sans-modern">
                <div class="flex flex-col">
                    <span class="text-[10px] text-[#8C8275] font-bold uppercase tracking-widest mb-1">
                        <Link :href="route('orders.index')" class="hover:text-[#4A6B5D] transition-colors">{{ t('orders') }}</Link>
                        <span class="mx-2 text-[#E6E1DA]">/</span>
                        <span class="text-[#4A6B5D]">{{ t('invoice_receipt') }} #{{ order.id }}</span>
                    </span>
                    <h2 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                        {{ t('invoice_receipt') }}
                    </h2>
                </div>
                
                <div class="flex items-center gap-2">
                    <button 
                        @click="printReceipt" 
                        class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold px-4 py-2.5 rounded-none text-xs uppercase tracking-widest transition-colors flex items-center gap-1.5"
                    >
                        <i class="fas fa-print text-[10px]"></i> {{ t('print_invoice') }}
                    </button>
                    <Link 
                        :href="route('orders.index')" 
                        class="bg-[#2D3330] hover:bg-[#1C201E] text-white font-semibold px-4 py-2.5 rounded-none text-xs uppercase tracking-widest transition-colors"
                    >
                        {{ t('back_to_bookings') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[#FAF7F2] min-h-[calc(100vh-80px)] font-sans-modern">
            <div class="max-w-4xl mx-auto px-6">
                
                <!-- Print area container -->
                <div class="receipt-container print-area space-y-8">
                    
                    <!-- Invoice Header Brand -->
                    <div class="flex flex-col md:flex-row md:justify-between items-start md:items-center gap-4 border-b border-[#EBEFEF] pb-8">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-6 h-6 rounded-full bg-[#4A6B5D] flex items-center justify-center text-white text-[10px] font-bold font-sans-modern">SS</span>
                                <span class="text-lg font-bold tracking-wider text-[#2D3330] uppercase">
                                    Smart<span class="text-[#4A6B5D]">Serve</span>
                                </span>
                            </div>
                            <p class="text-[11px] text-[#8C8275] leading-relaxed font-light">
                                Gong Badak, Kuala Terengganu, Terengganu<br>
                                Support Email: contact@smartservecatering.test
                            </p>
                        </div>
                        <div class="text-left md:text-right uppercase tracking-wider text-xs">
                            <span class="text-[9px] font-bold text-[#8C8275] block mb-0.5">{{ t('invoice_receipt').split(' ')[0] }}</span>
                            <span class="text-xl font-normal text-[#2D3330] font-serif-luxury tracking-wide block">#SSC-{{ order.id }}</span>
                            <span class="text-[9px] text-[#8C8275] font-semibold">{{ t('issued') }}: {{ new Date(order.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>

                    <!-- Client & Venue Summary Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-b border-[#EBEFEF] pb-8 text-xs font-sans-modern">
                        <div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block mb-2">{{ t('billed_to') }}:</span>
                            <span class="font-bold text-[#2D3330] text-sm block uppercase tracking-wider">{{ $page.props.auth.user.full_name || $page.props.auth.user.name }}</span>
                            <p class="text-xs text-[#5C6460] mt-1 leading-relaxed font-light">
                                {{ t('phone') }}: {{ $page.props.auth.user.phone || 'N/A' }}<br>
                                {{ t('email') }}: {{ $page.props.auth.user.email }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block mb-2">{{ t('event_schedule_venue') }}</span>
                            <p class="text-xs text-[#5C6460] leading-relaxed font-light">
                                <strong class="font-semibold text-[#2D3330]">{{ t('delivery_event_date_label') }}:</strong> {{ order.delivery_date }}<br>
                                <strong class="font-semibold text-[#2D3330]">{{ t('setup_time') }}:</strong> {{ order.delivery_time }}<br>
                                <strong class="font-semibold text-[#2D3330]">{{ t('venue_address') }}:</strong><br>
                                <span class="block mt-0.5 whitespace-pre-line">{{ order.delivery_address }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Itemized Pricing Tables -->
                    <div class="space-y-4 font-sans-modern">
                        <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('itemized_breakdown') }}</span>
                        
                        <div class="overflow-x-auto">
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
                                        <td class="py-4 pl-2 font-semibold text-[#2D3330] uppercase tracking-wider">
                                            {{ item.package?.package_name || order.package_name }}
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
                    </div>

                    <!-- Payment Overview Table -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-[#E6E1DA] pt-8 font-sans-modern">
                        <div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block mb-2">{{ t('payment_trans_status') }}</span>
                            
                            <div class="inline-flex items-center px-3 py-1 text-[10px] font-semibold border rounded-none uppercase tracking-widest mb-3" :class="getStatusBadge(order.status)">
                                {{ t('status_label') }}: {{ getTranslatedStatus(order.status) }}
                            </div>
                            <p class="text-xs text-[#5C6460] leading-relaxed font-light">
                                {{ t('payment_desc_invoice') }}
                            </p>
                        </div>
                        
                        <div class="space-y-2 text-xs uppercase tracking-wider text-[#8C8275] self-end">
                            <div class="flex justify-between">
                                <span>{{ t('base_cost') }}</span>
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
                            <div class="flex justify-between border-t border-[#E6E1DA] pt-3 text-sm font-bold text-[#2D3330] items-center">
                                <span class="text-xs font-semibold text-[#8C8275] uppercase tracking-wider">{{ t('grand_total') }}:</span>
                                <span class="text-base font-normal font-serif-luxury tracking-wide">RM {{ parseFloat(order.total_price).toFixed(2) }}</span>
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
