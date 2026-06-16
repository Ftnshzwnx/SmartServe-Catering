<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalization } from '@/Composables/useLocalization';

const { t } = useLocalization();

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    currentStatus: {
        type: String,
        default: null,
    },
    search: {
        type: String,
        default: '',
    },
    dishes: {
        type: Array,
        default: () => [],
    },
    statusCounts: {
        type: Object,
        required: true,
    },
});

const { toast } = useToast();
const { confirm, prompt } = useConfirm();

const filterStatus = ref(props.currentStatus || '');
const searchQuery = ref(props.search || '');
const expandedRow = ref(null);
const showReceiptModal = ref(false);
const activeReceiptUrl = ref('');
const activeReceiptOrder = ref(null);

const verifyForm = useForm({
    action: '',
    admin_note: '',
});

// Custom Proposal Builder state
const showProposalModal = ref(false);
const selectedProposalOrder = ref(null);
const proposalForm = useForm({
    total_price: '',
    dishes: [],
    admin_note: '',
});

const dishesByCategory = computed(() => {
    if (!props.dishes) return {};
    const groups = {};
    props.dishes.forEach(dish => {
        if (!groups[dish.category]) {
            groups[dish.category] = [];
        }
        groups[dish.category].push(dish);
    });
    return groups;
});

function openProposalBuilder(order) {
    selectedProposalOrder.value = order;
    proposalForm.total_price = order.total_price;
    
    const orderItem = order.items && order.items[0];
    let dishIds = [];
    if (orderItem && orderItem.selected_dishes) {
        dishIds = orderItem.selected_dishes.map(d => d.id).filter(id => id !== undefined);
    }
    
    proposalForm.dishes = dishIds;
    proposalForm.admin_note = '';
    showProposalModal.value = true;
}

function closeProposalModal() {
    showProposalModal.value = false;
    selectedProposalOrder.value = null;
    proposalForm.reset();
}

function isDishInWishlist(dishId) {
    if (!selectedProposalOrder.value) return false;
    const orderItem = selectedProposalOrder.value.items && selectedProposalOrder.value.items[0];
    if (orderItem && orderItem.selected_dishes) {
        return orderItem.selected_dishes.some(d => d.id === dishId);
    }
    return false;
}

function submitProposal() {
    if (!proposalForm.total_price || proposalForm.total_price < 0) {
        toast(t('admin_price_validation_error'), 'error');
        return;
    }
    if (proposalForm.dishes.length === 0) {
        toast(t('admin_dish_validation_error'), 'error');
        return;
    }
    
    proposalForm.post(route('admin.orders.send-proposal', { id: selectedProposalOrder.value.id }), {
        onSuccess: () => {
            toast(t('admin_proposal_sent_toast'));
            closeProposalModal();
        }
    });
}

function applyFilters() {
    const params = {};
    if (filterStatus.value) params.status = filterStatus.value;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    router.get(route('admin.orders', params), {}, { preserveScroll: false });
}

function handleFilterChange() {
    applyFilters();
}

let searchTimeout = null;
function handleSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
}

function clearSearch() {
    clearTimeout(searchTimeout);
    searchQuery.value = '';
    applyFilters();
}

function goToPage(url) {
    if (url) router.visit(url, { preserveScroll: false });
}

function toggleRow(id) {
    expandedRow.value = expandedRow.value === id ? null : id;
}

function openReceipt(order) {
    activeReceiptUrl.value = '/' + order.payment_proof;
    activeReceiptOrder.value = order;
    showReceiptModal.value = true;
}

function closeReceiptModal() {
    showReceiptModal.value = false;
    activeReceiptUrl.value = '';
    activeReceiptOrder.value = null;
}

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
            closeReceiptModal();
        }
    });
}

async function handleDeliver(orderId) {
    if (!(await confirm(t('admin_confirm_deliver'), t('admin_mark_delivered_title')))) return;
    
    router.post(route('admin.orders.deliver', { id: orderId }));
}

function getStatusBadge(status) {
    const map = {
        'Pending Proposal':  'bg-amber-50 text-amber-800 border-amber-200',
        'Proposal Sent':     'bg-teal-50 text-teal-800 border-teal-200',
        'Pending':           'bg-amber-50 text-amber-700 border-amber-200',
        'Confirmed':         'bg-emerald-50 text-[#4A6B5D] border-emerald-200',
        'Payment Submitted': 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'Delivered':         'bg-teal-50 text-teal-700 border-teal-200',
        'Completed':         'bg-green-50 text-green-700 border-green-200',
        'Deposit Rejected':  'bg-rose-50 text-rose-700 border-rose-200',
        'Balance Rejected':  'bg-rose-50 text-rose-700 border-rose-200',
        'Cancelled':         'bg-slate-100 text-slate-600 border-slate-200',
    };
    return map[status] || 'bg-slate-100 text-slate-600 border-slate-200';
}

function getTranslatedStatus(status) {
    switch (status) {
        case 'Pending Proposal':
            return t('admin_status_pending_proposal');
        case 'Proposal Sent':
            return t('admin_status_proposal_sent');
        case 'Pending':
            return t('admin_status_pending_deposit');
        case 'Payment Submitted':
            return t('admin_status_payment_resubmitted');
        case 'Confirmed':
            return t('confirmed');
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

function getStatusIcon(status) {
    const icons = {
        'Pending Proposal':  'fa-hourglass-start text-amber-500',
        'Proposal Sent':     'fa-paper-plane text-teal-600',
        'Pending':           'fa-clock text-amber-500',
        'Confirmed':         'fa-check-circle text-[#4A6B5D]',
        'Payment Submitted': 'fa-paper-plane text-indigo-500',
        'Delivered':         'fa-truck text-teal-600',
        'Completed':         'fa-trophy text-green-600',
        'Deposit Rejected':  'fa-times-circle text-rose-500',
        'Balance Rejected':  'fa-times-circle text-rose-500',
        'Cancelled':         'fa-ban text-slate-400',
    };
    return icons[status] || 'fa-circle text-slate-400';
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

function needsAction(status) {
    return ['Pending', 'Payment Submitted', 'Pending Proposal'].includes(status);
}
</script>

<template>
    <AdminLayout
        :title="t('admin_manage_booking_orders')"
        :header-title="t('admin_manage_orders')"
        :header-desc="t('admin_manage_orders_desc')"
    >
        <template #header-action>
            <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl px-4 py-2.5 text-xs font-bold text-[#4A6B5D] flex items-center gap-2 shadow-2xs select-none">
                <i class="fas fa-receipt text-[#C5A880]"></i>
                <span class="text-[#8C8275] uppercase tracking-wider text-[10px]">{{ t('admin_total_bookings') }}:</span>
                <span class="text-[#2D3330] font-extrabold text-sm">{{ statusCounts.all }}</span>
            </div>
        </template>

        <!-- Status Stats Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 select-none">
            <!-- Total Orders -->
            <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 flex items-center justify-between shadow-xs hover:border-[#C5A880]/30 transition-all">
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_total_bookings') }}</span>
                    <span class="text-3xl font-extrabold text-[#2D3330] font-serif-luxury block">{{ statusCounts.all }}</span>
                </div>
                <div class="w-12 h-12 bg-[#FAF7F2] text-[#4A6B5D] border border-[#E6E1DA] rounded-2xl flex items-center justify-center text-lg shadow-2xs">
                    <i class="fas fa-receipt text-sm"></i>
                </div>
            </div>

            <!-- Confirmed / Active -->
            <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 flex items-center justify-between shadow-xs hover:border-[#4A6B5D]/30 transition-all">
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_confirmed_events') }}</span>
                    <span class="text-3xl font-extrabold text-[#4A6B5D] font-serif-luxury block">
                        {{ statusCounts.confirmed + statusCounts.delivered }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-[#4A6B5D] border border-emerald-100 rounded-2xl flex items-center justify-center text-lg shadow-2xs">
                    <i class="fas fa-check-circle text-sm"></i>
                </div>
            </div>

            <!-- Pending / Action Required -->
            <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 flex items-center justify-between shadow-xs hover:border-amber-500/30 transition-all">
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_awaiting_action') }}</span>
                    <span class="text-3xl font-extrabold text-amber-600 font-serif-luxury block">
                        {{ statusCounts.pending_proposal + statusCounts.pending_deposit + statusCounts.payment_submitted }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-500 border border-amber-100 rounded-2xl flex items-center justify-center text-lg shadow-2xs">
                    <i class="fas fa-clock text-sm animate-pulse"></i>
                </div>
            </div>

            <!-- Completed -->
            <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 flex items-center justify-between shadow-xs hover:border-blue-500/30 transition-all">
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_completed_jobs') }}</span>
                    <span class="text-3xl font-extrabold text-blue-700 font-serif-luxury block">{{ statusCounts.completed }}</span>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 border border-blue-100 rounded-2xl flex items-center justify-center text-lg shadow-2xs">
                    <i class="fas fa-trophy text-sm"></i>
                </div>
            </div>
        </div>

        <!-- Search + Filter Toolbar -->
        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-5 shadow-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-grow max-w-2xl">
                    <!-- Search input wrapper -->
                    <div class="relative flex-grow">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8C8275]">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            :placeholder="t('admin_search_orders_placeholder')" 
                            class="w-full h-11 pl-10 pr-9 bg-[#FAF8F5] border border-[#E6E1DA] rounded-2xl text-xs font-semibold text-[#2D3330] placeholder-[#8C8275]/60 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white transition-all"
                            @input="handleSearchInput"
                        />
                        <button 
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#8C8275] hover:text-rose-600 transition-colors"
                            title="Clear Search"
                        >
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Status Filter Dropdown with custom arrow -->
                <div class="flex items-center gap-3 shrink-0">
                    <span class="hidden lg:inline text-[10px] font-bold text-[#8C8275] uppercase tracking-wider select-none">{{ t('admin_filter_status') }}</span>
                    <div class="relative w-full sm:w-48">
                        <select 
                            v-model="filterStatus"
                            @change="handleFilterChange"
                            class="w-full h-11 pl-4 pr-10 bg-[#FAF8F5] border border-[#E6E1DA] rounded-2xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                        >
                            <option value="">{{ t('admin_all_statuses') }}</option>
                            <option value="Pending Proposal">{{ t('admin_status_pending_proposal') }}</option>
                            <option value="Proposal Sent">{{ t('admin_status_proposal_sent') }}</option>
                            <option value="Pending">{{ t('admin_status_pending_deposit') }}</option>
                            <option value="Payment Submitted">{{ t('admin_status_payment_resubmitted') }}</option>
                            <option value="Confirmed">{{ t('confirmed') }}</option>
                            <option value="Delivered">{{ t('delivered_tab') }}</option>
                            <option value="Completed">{{ t('completed_tab') }}</option>
                            <option value="Deposit Rejected">{{ t('deposit_rejected') }}</option>
                            <option value="Balance Rejected">{{ t('balance_rejected') }}</option>
                            <option value="Cancelled">{{ t('cancelled_tab') }}</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-[#8C8275]">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Filter info row -->
            <div class="flex items-center justify-between pt-2 border-t border-[#E6E1DA]">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest">
                        {{ orders.total }} {{ t('admin_orders_found') }}
                    </span>
                    <span v-if="search"
                        class="inline-flex items-center gap-1.5 bg-[#4A6B5D]/10 text-[#4A6B5D] text-[10px] font-bold px-2.5 py-1 rounded-full">
                        <i class="fas fa-search text-[8px]"></i> "{{ search }}"
                        <button @click="clearSearch" class="hover:text-rose-600 cursor-pointer transition-colors ml-0.5">
                            <i class="fas fa-times text-[8px]"></i>
                        </button>
                    </span>
                    <span v-if="currentStatus"
                        class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 text-[10px] font-bold px-2.5 py-1 rounded-full border border-amber-200">
                        <i class="fas fa-filter text-[8px]"></i> {{ getTranslatedStatus(currentStatus) }}
                    </span>
                </div>
                <span class="text-[10px] text-[#8C8275] font-semibold shrink-0">
                    {{ t('admin_page') }} {{ orders.current_page }} / {{ orders.last_page }}
                </span>
            </div>
        </div>

        <!-- Orders Data Table -->
        <div v-if="orders.data.length > 0" class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs overflow-hidden">
            
            <!-- Table Header (Desktop Only) -->
            <div class="hidden md:grid grid-cols-12 gap-3 px-6 py-3 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                <div class="col-span-1 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">{{ t('admin_number_col') }}</div>
                <div class="col-span-1 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_id') }}</div>
                <div class="col-span-2 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_customer') }}</div>
                <div class="col-span-2 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('package') }}</div>
                <div class="col-span-2 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_event_date') }}</div>
                <div class="col-span-1 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-right">{{ t('admin_total_rm') }}</div>
                <div class="col-span-2 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">{{ t('status_label') }}</div>
                <div class="col-span-1 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-right">{{ t('action') }}</div>
            </div>

            <!-- Table Rows -->
            <div v-for="(order, index) in orders.data" :key="order.id" class="border-b border-[#E6E1DA] last:border-0">
                
                <!-- Main Row (Desktop Only) -->
                <div
                    @click="toggleRow(order.id)"
                    class="hidden md:grid grid-cols-12 gap-3 px-6 py-4 items-center cursor-pointer transition-colors hover:bg-[#FAFAF9] group"
                    :class="expandedRow === order.id ? 'bg-[#FAF7F2]' : ''"
                >
                    <!-- No. -->
                    <div class="col-span-1 text-center font-semibold text-[#8C8275] text-xs">
                        {{ orders.from + index }}
                    </div>

                    <!-- Order ID -->
                    <div class="col-span-1">
                        <span class="text-xs font-bold text-[#4A6B5D]">#{{ order.id }}</span>
                        <!-- Needs action dot -->
                        <span v-if="needsAction(order.status)"
                            class="ml-1 inline-block w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse align-middle"
                            :title="t('admin_requires_action')"></span>
                    </div>

                    <!-- Customer -->
                    <div class="col-span-2 min-w-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-7 h-7 rounded-full bg-[#4A6B5D]/10 text-[#4A6B5D] flex items-center justify-center font-bold text-[10px] shrink-0 border border-[#4A6B5D]/20">
                                {{ (order.user?.full_name || order.user?.name || 'C').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-[#2D3330] truncate">
                                    {{ order.user?.full_name || order.user?.name || 'Customer' }}
                                </p>
                                <p class="text-[10px] text-[#8C8275] font-semibold truncate">
                                    {{ order.user?.phone || order.user?.email }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Package -->
                    <div class="col-span-2 min-w-0">
                        <div class="flex flex-col gap-1">
                            <span v-if="order.is_custom_proposal" class="inline-flex self-start px-2 py-0.5 text-[9px] font-bold bg-[#FAF7F2] text-[#4A6B5D] border border-[#C5A880]/50 rounded uppercase tracking-wider">
                                {{ t('admin_custom_request') }}
                            </span>
                            <p class="text-xs font-semibold text-[#2D3330] truncate" :title="order.package_name">
                                {{ order.package_name }}
                            </p>
                        </div>
                    </div>

                    <!-- Event Date -->
                    <div class="col-span-2">
                        <p class="text-xs font-bold text-[#2D3330]">
                            <i class="far fa-calendar-alt text-[#C5A880] mr-1 text-[10px]"></i>
                            {{ order.delivery_date }}
                        </p>
                        <p class="text-[10px] text-[#8C8275] font-semibold mt-0.5">
                            <i class="far fa-clock mr-1 text-[8px]"></i>{{ order.delivery_time }}
                        </p>
                    </div>

                    <!-- Total Price -->
                    <div class="col-span-1 text-right">
                        <span class="text-xs font-bold text-[#C5A880]">
                            {{ parseFloat(order.total_price).toFixed(2) }}
                        </span>
                    </div>

                    <!-- Status Badge -->
                    <div class="col-span-2 flex justify-center">
                        <span class="inline-flex items-center gap-1.5 text-[9px] font-bold px-2.5 py-1 rounded-full border"
                            :class="getStatusBadge(order.status)">
                            <i class="fas text-[7px]" :class="getStatusIcon(order.status)"></i>
                            {{ getTranslatedStatus(order.status) }}
                        </span>
                    </div>

                    <!-- Expand Arrow -->
                    <div class="col-span-1 flex justify-end">
                        <div class="w-7 h-7 rounded-lg border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] transition-all group-hover:border-[#4A6B5D] group-hover:text-[#4A6B5D]"
                            :class="expandedRow === order.id ? 'bg-[#4A6B5D] text-white border-[#4A6B5D]' : ''">
                            <i class="fas fa-chevron-down text-[10px] transition-transform duration-200"
                                :class="expandedRow === order.id ? 'rotate-180' : ''"></i>
                        </div>
                    </div>
                </div>

                <!-- Shopee-style Card (Mobile Only) -->
                <div
                    @click="toggleRow(order.id)"
                    class="flex md:hidden flex-col gap-3 p-4 cursor-pointer transition-colors hover:bg-[#FAFAF9] border-b border-[#E6E1DA]/50 last:border-0 bg-white"
                    :class="expandedRow === order.id ? 'bg-[#FAF7F2]/50' : ''"
                >
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-extrabold text-[#4A6B5D]">#SSC-{{ order.id }}</span>
                            <span v-if="needsAction(order.status)" class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[8px] font-extrabold px-2.5 py-0.5 rounded-full border uppercase tracking-wider" :class="getStatusBadge(order.status)">
                            <i class="fas text-[6px]" :class="getStatusIcon(order.status)"></i>
                            {{ getTranslatedStatus(order.status) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#4A6B5D]/10 text-[#4A6B5D] flex items-center justify-center font-bold text-[10px] shrink-0 border border-[#4A6B5D]/20">
                            {{ (order.user?.full_name || order.user?.name || 'C').charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-grow">
                            <p class="text-xs font-extrabold text-[#2D3330] truncate">
                                {{ order.user?.full_name || order.user?.name || 'Customer' }}
                            </p>
                            <p class="text-[9px] text-[#8C8275] font-semibold truncate">
                                {{ order.user?.phone || order.user?.email }}
                            </p>
                        </div>
                        <div class="text-right shrink-0 pl-2">
                            <span class="text-xs font-black text-[#C5A880] block">RM {{ parseFloat(order.total_price).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between bg-[#FAF7F2] px-3 py-2 rounded-xl border border-[#E6E1DA]/60 text-[9px] font-bold">
                        <div class="truncate pr-2 text-[#5C6460]">
                            <span v-if="order.is_custom_proposal" class="text-[8px] bg-white text-[#4A6B5D] border border-[#C5A880]/40 px-1 py-0.5 rounded uppercase mr-1 inline-block">Custom</span>
                            {{ order.package_name }}
                        </div>
                        <div class="shrink-0 text-right text-[#8C8275]">
                            <i class="far fa-calendar-alt text-[#C5A880] mr-1"></i>{{ order.delivery_date }}
                        </div>
                    </div>

                    <div class="flex justify-center pt-1.5">
                        <i class="fas fa-chevron-down text-[9px] text-[#8C8275] transition-transform duration-200" :class="expandedRow === order.id ? 'rotate-180 text-[#4A6B5D]' : ''"></i>
                    </div>
                </div>

                <!-- Expanded Detail Panel -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="expandedRow === order.id" class="px-4 md:px-6 pb-5 bg-[#FAF7F2] border-t border-[#E6E1DA]/60">
                        <div class="pt-5 grid grid-cols-1 md:grid-cols-3 gap-5">

                            <!-- Venue -->
                            <div class="bg-white rounded-2xl border border-[#E6E1DA] p-4 space-y-1.5">
                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                    <i class="fas fa-map-marker-alt text-[#C5A880]"></i> {{ t('admin_event_venue') }}
                                </span>
                                <p class="text-xs text-[#2D3330] font-semibold leading-relaxed whitespace-pre-line">{{ order.delivery_address }}</p>
                                <div v-if="order.delivery_zone" class="mt-2 pt-2 border-t border-[#E6E1DA]/60 text-[10px] text-[#5C6460]">
                                    <span class="font-bold block">{{ t('admin_delivery_option') }}:</span>
                                    <span>{{ order.delivery_zone }} (RM {{ parseFloat(order.delivery_fee).toFixed(2) }})</span>
                                </div>
                                <div v-if="order.notes" class="mt-2 pt-2 border-t border-[#E6E1DA]/60 text-[10px]">
                                    <span class="font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5 mb-1">
                                        <i class="fas fa-sticky-note text-[#C5A880]"></i> {{ t('customer_notes') }}
                                    </span>
                                    <p class="text-xs text-[#2D3330] font-semibold leading-relaxed whitespace-pre-line">{{ order.notes }}</p>
                                </div>
                            </div>
 
                            <!-- Order Info -->
                             <div class="bg-white rounded-2xl border border-[#E6E1DA] p-4 space-y-3">
                                 <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                     <i class="fas fa-info-circle text-[#C5A880]"></i> {{ t('itemized_breakdown') }}
                                 </span>
                                 <div class="space-y-2 text-xs">
                                     <!-- Itemized list -->
                                     <div v-for="item in order.items" :key="item.id" class="border-b border-[#E6E1DA]/60 pb-2 last:border-0 last:pb-0">
                                         <div class="flex justify-between items-start">
                                             <span class="font-bold text-[#2D3330]">{{ item.package?.package_name || order.package_name }}</span>
                                             <span class="text-[10px] text-[#8C8275] font-semibold">{{ item.quantity }} {{ t('admin_pax') }}</span>
                                         </div>
                                          <!-- Dishes grouped by category -->
                                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                                              <div 
                                                  v-for="(dishes, category) in getGroupedDishes(item)" 
                                                  :key="category" 
                                                  class="bg-[#FAF7F2] border border-[#E6E1DA] p-2.5 rounded-xl space-y-1"
                                              >
                                                  <span class="text-[8px] font-extrabold text-[#4A6B5D] uppercase tracking-widest block border-b border-[#E6E1DA] pb-0.5">{{ category }}</span>
                                                  <ul class="space-y-0.5">
                                                      <li v-for="dish in dishes" :key="dish.name" class="text-[9px] font-bold text-[#5C6460] flex items-center gap-1">
                                                          <i class="fas fa-check text-[6px] text-[#4A6B5D]"></i> 
                                                          <span>{{ dish.name }}</span>
                                                          <span v-if="dish.isDefault" class="text-[7px] text-[#8C8275] italic font-normal">{{ t('admin_default_dish') }}</span>
                                                      </li>
                                                  </ul>
                                              </div>
                                          </div>

                                          <!-- Selected Addons -->
                                          <div v-if="item.selected_addons && item.selected_addons.length > 0" class="mt-2.5 space-y-1">
                                              <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_addons') }}</span>
                                              <div class="flex flex-wrap gap-1">
                                                  <span v-for="addon in item.selected_addons" :key="addon" class="bg-amber-50 border border-amber-200 text-[8px] text-amber-800 font-bold px-2 py-0.5 rounded-md flex items-center gap-1">
                                                      <i class="fas fa-plus text-[6px] text-[#C5A880]"></i> {{ addon }}
                                                  </span>
                                              </div>
                                          </div>
                                     </div>
                                     <div class="flex justify-between pt-1.5 border-t border-[#E6E1DA]/60">
                                         <span class="text-[#8C8275] font-semibold">{{ t('admin_subtotal') }}</span>
                                         <span class="text-[#2D3330] font-bold">RM {{ (parseFloat(order.total_price) - parseFloat(order.delivery_fee || 0) + parseFloat(order.discount_amount || 0)).toFixed(2) }}</span>
                                     </div>
                                     <div v-if="parseFloat(order.delivery_fee) > 0" class="flex justify-between">
                                         <span class="text-[#8C8275] font-semibold">{{ t('admin_delivery_fee') }}</span>
                                         <span class="text-[#2D3330] font-bold">RM {{ parseFloat(order.delivery_fee).toFixed(2) }}</span>
                                     </div>
                                     <div v-if="parseFloat(order.discount_amount) > 0" class="flex justify-between text-emerald-700 font-semibold">
                                         <span class="font-semibold">{{ t('admin_discount') }}</span>
                                         <span>- RM {{ parseFloat(order.discount_amount).toFixed(2) }}</span>
                                     </div>
                                     <div class="flex justify-between border-t border-dashed border-[#E6E1DA]/60 pt-1">
                                         <span class="text-[#8C8275] font-semibold">{{ t('admin_grand_total') }}</span>
                                         <span class="text-sm font-black text-[#2D3330]">RM {{ parseFloat(order.total_price).toFixed(2) }}</span>
                                     </div>
                                     <div class="flex justify-between">
                                         <span class="text-[#8C8275] font-semibold">{{ t('status_label') }}</span>
                                         <span class="inline-flex items-center gap-1 text-[9px] font-bold px-2 py-0.5 rounded-full border" :class="getStatusBadge(order.status)">
                                             {{ getTranslatedStatus(order.status) }}
                                         </span>
                                     </div>
                                     <div v-if="order.admin_note" class="mt-1 pt-1.5 border-t border-[#E6E1DA]">
                                         <span class="text-[#8C8275] font-semibold block mb-0.5">{{ t('admin_note_label') }}</span>
                                         <p class="text-[#2D3330] font-medium leading-relaxed text-[10px] bg-rose-50 border border-rose-100 rounded-lg px-2 py-1.5">{{ order.admin_note }}</p>
                                     </div>
                                 </div>
                             </div>

                            <!-- Actions Panel -->
                            <div class="bg-white rounded-2xl border border-[#E6E1DA] p-4 space-y-3">
                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                    <i class="fas fa-bolt text-[#C5A880]"></i> {{ t('quick_actions') }}
                                </span>

                                <!-- View Receipt -->
                                <div class="space-y-2">
                                    <button v-if="order.payment_proof"
                                        @click.stop="openReceipt(order)"
                                        class="w-full flex items-center justify-center gap-2 bg-[#FAF7F2] hover:bg-[#F0EBE2] border border-[#E6E1DA] text-[#5C6460] font-bold py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer">
                                        <i class="fas fa-file-invoice-dollar text-blue-600"></i> {{ t('admin_view_slip') }}
                                    </button>
                                    <div v-else
                                        class="w-full flex items-center justify-center gap-2 bg-[#FAF7F2] border border-dashed border-[#E6E1DA] text-[#B5AFA8] font-semibold py-2.5 rounded-xl text-xs">
                                        <i class="fas fa-times-circle"></i> {{ t('admin_no_slip_uploaded') }}
                                    </div>

                                    <!-- Download Kitchen Slip -->
                                    <a :href="route('orders.invoice.pdf', order.id)"
                                        @click.stop
                                        class="w-full flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors">
                                        <i class="fas fa-file-pdf text-[10px]"></i> {{ t('admin_kitchen_slip') }}
                                    </a>

                                     <!-- Proposal Actions -->
                                     <div v-if="order.status === 'Pending Proposal'" class="pt-2">
                                         <button
                                             @click.stop="openProposalBuilder(order)"
                                             class="w-full flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer"
                                         >
                                             <i class="fas fa-utensils"></i> {{ t('admin_build_custom_menu') }}
                                         </button>
                                     </div>

                                     <div v-else-if="order.status === 'Proposal Sent'" class="pt-2">
                                         <div class="p-3 bg-emerald-50 border border-emerald-100 text-xs rounded-xl text-center text-[#4A6B5D] font-semibold">
                                             <i class="fas fa-check-circle"></i> {{ t('admin_proposal_sent_waiting').replace('{price}', parseFloat(order.total_price).toFixed(2)) }}
                                         </div>
                                     </div>

                                     <!-- Approve / Reject (only if action needed) -->
                                     <div v-else-if="needsAction(order.status)" class="grid grid-cols-2 gap-2">
                                         <button
                                             @click.stop="handleVerify(order.id, 'approve')"
                                             :disabled="verifyForm.processing"
                                             class="flex items-center justify-center gap-1.5 bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-xs">
                                             <i class="fas fa-check text-[10px]"></i> {{ t('admin_approve') }}
                                         </button>
                                         <button
                                             @click.stop="handleVerify(order.id, 'reject')"
                                             :disabled="verifyForm.processing"
                                             class="flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-700 disabled:opacity-60 text-white font-bold py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-xs">
                                             <i class="fas fa-times text-[10px]"></i> {{ t('admin_reject') }}
                                         </button>
                                     </div>

                                     <!-- Deliver Order (if Confirmed) -->
                                     <div v-if="order.status === 'Confirmed'" class="pt-2">
                                         <button
                                             @click.stop="handleDeliver(order.id)"
                                             class="w-full flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-xs"
                                         >
                                             <i class="fas fa-truck text-[10px]"></i> {{ t('admin_mark_as_delivered') }}
                                         </button>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-20 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-[#FAF7F2] border border-[#E6E1DA] flex items-center justify-center mx-auto text-2xl">
                <i class="fas fa-receipt text-[#8C8275]"></i>
            </div>
            <div>
                <h4 class="text-[#2D3330] font-bold">{{ t('admin_no_orders_found') }}</h4>
                <p class="text-xs text-[#8C8275] mt-1 font-semibold">
                    {{ search || currentStatus ? t('admin_no_orders_filter_desc') : t('admin_no_orders_placed_desc') }}
                </p>
            </div>
            <button v-if="search || currentStatus"
                @click="searchQuery = ''; filterStatus = ''; applyFilters();"
                class="inline-flex items-center gap-2 text-xs font-bold text-[#4A6B5D] hover:underline cursor-pointer">
                <i class="fas fa-undo"></i> {{ t('admin_clear_all_filters') }}
            </button>
        </div>

        <!-- Pagination Bar -->
        <div v-if="orders.data.length > 0" class="bg-white rounded-3xl border border-[#E6E1DA] p-4 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-wider">
                    {{ t('admin_showing_orders').replace('{from}', orders.from).replace('{to}', orders.to).replace('{total}', orders.total) }}
                </span>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button @click="goToPage(orders.prev_page_url)" :disabled="!orders.prev_page_url"
                        class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-xs font-bold transition-colors cursor-pointer"
                        :class="orders.prev_page_url ? 'text-[#4A6B5D] hover:bg-[#FAF7F2] hover:border-[#4A6B5D]' : 'text-[#C6C1B9] cursor-not-allowed bg-[#FAF7F2]'">
                        <i class="fas fa-chevron-left text-[9px]"></i>
                    </button>
                    <template v-for="link in orders.links" :key="link.label">
                        <button v-if="link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;'"
                            @click="goToPage(link.url)" :disabled="!link.url"
                            class="min-w-8 h-8 px-2.5 rounded-xl border text-[11px] font-bold transition-colors cursor-pointer"
                            :class="link.active ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-sm' : link.url ? 'border-[#E6E1DA] text-[#5C6460] hover:bg-[#FAF7F2] hover:border-[#4A6B5D] hover:text-[#4A6B5D]' : 'border-transparent text-[#8C8275] cursor-default'"
                            v-html="link.label">
                        </button>
                    </template>
                    <button @click="goToPage(orders.next_page_url)" :disabled="!orders.next_page_url"
                        class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-xs font-bold transition-colors cursor-pointer"
                        :class="orders.next_page_url ? 'text-[#4A6B5D] hover:bg-[#FAF7F2] hover:border-[#4A6B5D]' : 'text-[#C6C1B9] cursor-not-allowed bg-[#FAF7F2]'">
                        <i class="fas fa-chevron-right text-[9px]"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Payment Slip Modal -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showReceiptModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-[#1B2A22]/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl max-w-2xl w-full relative border border-[#E6E1DA] shadow-2xl overflow-hidden">
                    
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-7 py-5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                        <div>
                            <h3 class="text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_payment_receipt_slip') }}</h3>
                            <p class="text-[10px] text-[#8C8275] font-semibold mt-0.5" v-if="activeReceiptOrder">
                                {{ t('admin_booking') }} #SSC-{{ activeReceiptOrder.id }} — {{ activeReceiptOrder.user?.full_name || activeReceiptOrder.user?.name }}
                            </p>
                        </div>
                        <button @click="closeReceiptModal"
                            class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>

                    <!-- Receipt Viewer -->
                    <div class="p-5">
                        <div class="border border-[#E6E1DA] rounded-2xl overflow-hidden max-h-[60vh] flex items-center justify-center bg-[#FAF7F2]">
                            <iframe v-if="activeReceiptUrl.endsWith('.pdf')" :src="activeReceiptUrl" class="w-full h-[55vh]"></iframe>
                            <img v-else :src="activeReceiptUrl" alt="Payment Slip" class="max-w-full max-h-[55vh] object-contain" />
                        </div>
                    </div>

                    <!-- Modal Footer with Actions -->
                    <div class="px-7 pb-6 flex items-center justify-between gap-3 flex-wrap">
                        <a :href="activeReceiptUrl" target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#C5A880] hover:underline">
                            <i class="fas fa-external-link-alt text-[10px]"></i> {{ t('admin_open_new_tab') }}
                        </a>
                        
                        <div v-if="activeReceiptOrder && needsAction(activeReceiptOrder.status)" class="flex items-center gap-2">
                            <button
                                @click="handleVerify(activeReceiptOrder.id, 'reject')"
                                :disabled="verifyForm.processing"
                                class="flex items-center gap-1.5 border border-rose-200 bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-all cursor-pointer">
                                <i class="fas fa-times text-[10px]"></i> {{ t('admin_reject') }}
                            </button>
                            <button
                                @click="handleVerify(activeReceiptOrder.id, 'approve')"
                                :disabled="verifyForm.processing"
                                class="flex items-center gap-1.5 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-xs">
                                <i class="fas fa-check text-[10px]"></i> {{ t('admin_approve_payment') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Proposal Builder Modal -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showProposalModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-[#1B2A22]/60 backdrop-blur-sm overflow-y-auto">
                <div class="bg-white rounded-3xl max-w-4xl w-full my-8 relative border border-[#E6E1DA] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-7 py-5 border-b border-[#E6E1DA] bg-[#FAF7F2] shrink-0">
                        <div>
                            <h3 class="text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_build_custom_proposal_title') }}</h3>
                            <p class="text-[10px] text-[#8C8275] font-semibold mt-0.5" v-if="selectedProposalOrder">
                                {{ t('admin_booking') }} #SSC-{{ selectedProposalOrder.id }} — {{ selectedProposalOrder.user?.full_name || selectedProposalOrder.user?.name }}
                            </p>
                        </div>
                        <button @click="closeProposalModal"
                            class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="p-7 overflow-y-auto flex-grow space-y-6 text-xs">
                        
                        <!-- Client Request Brief -->
                        <div v-if="selectedProposalOrder" class="bg-[#FAF7F2]/60 border border-[#E6E1DA] rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('admin_target_budget') }}</span>
                                <span class="font-extrabold text-[#C5A880] text-sm font-serif-luxury">RM {{ parseFloat(selectedProposalOrder.total_price).toFixed(2) }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('guest_count') }}</span>
                                <span class="font-bold text-[#2D3330]">{{ selectedProposalOrder.items?.[0]?.quantity || '-' }} {{ t('admin_pax') }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('admin_event_date_time') }}</span>
                                <span class="font-bold text-[#2D3330]">{{ selectedProposalOrder.delivery_date }} ({{ selectedProposalOrder.delivery_time }})</span>
                            </div>
                            <div class="sm:col-span-3 border-t border-[#E6E1DA] pt-3">
                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-0.5">{{ t('admin_client_wishlist') }}</span>
                                <p class="text-[#5C6460] leading-relaxed italic">"{{ selectedProposalOrder.admin_note || t('admin_no_notes_provided') }}"</p>
                            </div>
                        </div>

                        <!-- Menu & Price Form -->
                        <form @submit.prevent="submitProposal" class="space-y-6">
                            <!-- Custom Final Price & Admin Note -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_proposed_final_price') }}</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-[#8C8275]">RM</span>
                                        <input 
                                            v-model="proposalForm.total_price" 
                                            type="number" 
                                            min="0" 
                                            step="0.01"
                                            required
                                            class="w-full pl-12 pr-4 h-11 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] text-[#2D3330]"
                                            :placeholder="t('admin_enter_finalized_cost_placeholder')"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_note_to_customer') }}</label>
                                    <input 
                                        v-model="proposalForm.admin_note" 
                                        type="text" 
                                        class="w-full px-4 h-11 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] text-[#2D3330]"
                                        :placeholder="t('admin_note_placeholder')"
                                    />
                                </div>
                            </div>

                            <!-- Dish Swapper / Wishlist Selector -->
                            <div class="space-y-4 pt-4 border-t border-[#E6E1DA]">
                                <div>
                                    <h4 class="font-serif-luxury text-base text-[#2D3330] font-normal uppercase tracking-wide">{{ t('admin_assemble_proposal_menu') }}</h4>
                                    <p class="text-[10px] text-[#8C8275] font-light mt-0.5">{{ t('admin_assemble_proposal_desc') }}</p>
                                </div>

                                <div class="space-y-6">
                                    <div v-for="(dishesList, category) in dishesByCategory" :key="category" class="space-y-2.5">
                                        <h5 class="text-[9px] font-bold text-[#4A6B5D] uppercase tracking-wider bg-[#FAF8F5] border border-[#E6E1DA] px-2.5 py-1 rounded-md inline-block">
                                            {{ category }}
                                        </h5>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                            <label 
                                                v-for="dish in dishesList" 
                                                :key="dish.id"
                                                class="flex items-center gap-2.5 p-3 rounded-xl border border-[#E6E1DA] cursor-pointer transition-all hover:border-[#4A6B5D]"
                                                :class="proposalForm.dishes.includes(dish.id) ? 'bg-[#FAF7F2] border-[#4A6B5D]' : 'bg-white'"
                                            >
                                                <input 
                                                    type="checkbox" 
                                                    :value="dish.id" 
                                                    v-model="proposalForm.dishes"
                                                    class="rounded text-[#4A6B5D] focus:ring-0 w-4 h-4 border-[#C6C1B9] cursor-pointer"
                                                />
                                                <div class="min-w-0">
                                                    <span class="font-bold text-xs text-[#2D3330] block truncate">{{ dish.name }}</span>
                                                    <span v-if="isDishInWishlist(dish.id)" class="text-[8px] font-bold text-[#FAF7F2] bg-[#4A6B5D] px-1.5 py-0.5 rounded-full uppercase tracking-wider inline-block mt-0.5">
                                                        {{ t('admin_wishlist_tag') }}
                                                    </span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Submit Actions -->
                            <div class="border-t border-[#E6E1DA] pt-5 flex justify-end gap-3 shrink-0">
                                <button 
                                    type="button" 
                                    @click="closeProposalModal"
                                    class="px-5 py-2.5 border border-[#E6E1DA] hover:bg-[#FAF7F2] text-[#8C8275] rounded-xl text-xs uppercase tracking-widest font-semibold transition-colors cursor-pointer"
                                >
                                    {{ t('cancel') }}
                                </button>
                                <button 
                                    type="submit" 
                                    :disabled="proposalForm.processing"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest font-semibold transition-colors cursor-pointer shadow-xs"
                                >
                                    {{ proposalForm.processing ? t('sending_status') : t('admin_send_menu_proposal') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
