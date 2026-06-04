<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    orders: {
        type: Array,
        required: true,
    },
    currentStatus: {
        type: String,
        default: null,
    },
});

const filterStatus = ref(props.currentStatus || '');
const selectedOrder = ref(null);
const rejectionNotes = ref('');
const showReceiptModal = ref(false);
const activeReceiptUrl = ref('');

// Forms
const verifyForm = useForm({
    action: '',
    admin_note: '',
});

function handleFilterChange() {
    router.get(route('admin.orders', filterStatus.value ? { status: filterStatus.value } : {}));
}

function openReceipt(url) {
    activeReceiptUrl.value = '/' + url;
    showReceiptModal.value = true;
}

function closeReceiptModal() {
    showReceiptModal.value = false;
    activeReceiptUrl.value = '';
}

function handleVerify(orderId, actionType) {
    if (actionType === 'reject') {
        const note = prompt('Please enter the rejection reason / notes for the customer:');
        if (note === null) return; // cancelled prompt
        if (!note.trim()) {
            alert('Rejection note is required to reject payment verification.');
            return;
        }
        verifyForm.admin_note = note;
    } else {
        verifyForm.admin_note = '';
        if (!confirm('Are you sure you want to approve this payment receipt?')) return;
    }
    
    verifyForm.action = actionType;
    verifyForm.post(route('admin.orders.verify', { id: orderId }), {
        onSuccess: () => {
            alert('Order status has been updated and email notice dispatched.');
        }
    });
}

function getStatusBadge(status) {
    switch (status) {
        case 'Pending':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Confirmed':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Payment Submitted':
            return 'bg-indigo-100 text-indigo-800 border-indigo-200';
        case 'Delivered':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Completed':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'Deposit Rejected':
        case 'Balance Rejected':
            return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'Cancelled':
            return 'bg-slate-100 text-slate-800 border-slate-200';
        default:
            return 'bg-slate-100 text-slate-800 border-slate-200';
    }
}
</script>

<template>
    <Head title="Manage Booking Orders" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');
        .font-title { font-family: 'Outfit', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        .sidebar {
            width: 260px;
            background: #0f172a;
        }
        .main-content {
            width: calc(100% - 260px);
        }
        .order-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.01);
            padding: 24px;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #94a3b8;
            transition: all 0.2s ease;
        }
        .sidebar-link:hover, .sidebar-link.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }
        .sidebar-link.active {
            border-left: 3px solid #c5a880;
        }
        .form-select {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            padding: 10px 16px;
            font-size: 0.85rem;
            color: #475569;
        }
        .form-select:focus {
            border-color: #c5a880;
            outline: none;
            box-shadow: none;
        }
        .modal-overlay {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
        }
    </component>

    <div class="min-h-screen bg-[#f8fafc] flex font-body">
        
        <!-- Admin Navigation Sidebar -->
        <aside class="sidebar min-h-screen p-6 flex flex-col justify-between shrink-0 shadow-lg text-slate-300">
            <div class="space-y-8">
                <!-- Branding logo -->
                <div class="flex items-center gap-2 border-b border-slate-800 pb-6">
                    <i class="fas fa-concierge-bell text-xl text-[#c5a880]"></i>
                    <span class="text-xl font-extrabold tracking-tight text-white font-title">
                        Smart<span class="text-[#c5a880]">Serve</span> Admin
                    </span>
                </div>

                <!-- Nav list links -->
                <nav class="space-y-2">
                    <Link :href="route('admin.dashboard')" class="sidebar-link">
                        <i class="fas fa-chart-line text-sm w-5"></i> Dashboard
                    </Link>
                    <Link :href="route('admin.orders')" class="sidebar-link active">
                        <i class="fas fa-receipt text-sm w-5"></i> Manage Orders
                    </Link>
                    <Link :href="route('admin.packages')" class="sidebar-link">
                        <i class="fas fa-utensils text-sm w-5"></i> Catering Menus
                    </Link>
                    <Link :href="route('admin.settings')" class="sidebar-link">
                        <i class="fas fa-cogs text-sm w-5"></i> Settings
                    </Link>
                </nav>
            </div>

            <div class="border-t border-slate-800 pt-6">
                <Link 
                    :href="route('logout')" 
                    method="post" 
                    as="button" 
                    class="w-full flex items-center gap-2 px-4 py-2.5 rounded-xl hover:bg-red-500/10 hover:text-red-400 text-xs font-bold text-slate-400 transition-colors"
                >
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </Link>
            </div>
        </aside>

        <!-- Main Dashboard Content -->
        <main class="main-content p-10 space-y-8">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-slate-200 pb-6">
                <div>
                    <h1 class="text-3xl font-bold font-title text-slate-800">Manage Orders</h1>
                    <p class="text-xs text-slate-400 mt-1">Review active events, verify customer payments, and cancel orders.</p>
                </div>
                <div>
                    <select v-model="filterStatus" @change="handleFilterChange" class="form-select w-56">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending Deposit Verification</option>
                        <option value="Payment Submitted">Payment Re-submitted</option>
                        <option value="Confirmed">Confirmed Booking</option>
                        <option value="Delivered">Delivered (Pending Balance)</option>
                        <option value="Completed">Completed Bookings</option>
                        <option value="Deposit Rejected">Deposit Rejected</option>
                        <option value="Balance Rejected">Balance Rejected</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <!-- Orders Table List -->
            <div v-if="orders.length > 0" class="space-y-6">
                <div v-for="order in orders" :key="order.id" class="order-card space-y-6">
                    
                    <!-- Top section summary info -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-xs font-bold text-slate-400 block mb-0.5">ORDER ID</span>
                            <span class="text-base font-bold text-slate-800 font-title">#SSC-{{ order.id }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-400 block mb-0.5">CUSTOMER</span>
                            <span class="text-sm font-semibold text-slate-700 block">
                                {{ order.user?.full_name || order.user?.name || 'Customer' }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">{{ order.user?.phone }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-400 block mb-0.5">EVENT DATE</span>
                            <span class="text-sm font-semibold text-slate-700 block">
                                <i class="far fa-calendar text-[#c5a880] mr-1"></i> {{ order.delivery_date }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">Time: {{ order.delivery_time }}</span>
                        </div>
                        <div class="md:text-right">
                            <span class="text-xs font-bold text-slate-400 block mb-0.5">TOTAL PRICE</span>
                            <span class="text-base font-bold text-[#b89047] block">RM {{ parseFloat(order.total_price).toFixed(2) }}</span>
                            <span class="inline-flex text-[9px] font-bold px-2 py-0.5 rounded-full border mt-1" :class="getStatusBadge(order.status)">
                                {{ order.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Inner address/package info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-slate-500">
                        <div>
                            <strong class="text-slate-700 block mb-1">Catering Packages Ordered:</strong>
                            <p class="leading-relaxed">{{ order.package_name }}</p>
                        </div>
                        <div>
                            <strong class="text-slate-700 block mb-1">Event Delivery Venue:</strong>
                            <p class="leading-relaxed whitespace-pre-line">{{ order.delivery_address }}</p>
                        </div>
                    </div>

                    <!-- Verification Controls Area -->
                    <div class="flex flex-wrap justify-between items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <div class="text-xs text-slate-400 flex items-center gap-2">
                            <span class="font-bold text-slate-600">Payment Slip Proof:</span>
                            <button 
                                v-if="order.payment_proof" 
                                @click="openReceipt(order.payment_proof)"
                                class="text-blue-500 hover:text-blue-700 font-bold flex items-center gap-1 hover:underline"
                            >
                                <i class="fas fa-file-invoice-dollar"></i> View Receipt File
                            </button>
                            <span v-else class="text-slate-400"><i class="fas fa-times-circle"></i> No slip uploaded</span>
                        </div>

                        <!-- Action controls if pending verification status -->
                        <div v-if="['Pending', 'Payment Submitted'].includes(order.status)" class="flex items-center gap-2">
                            <button 
                                @click="handleVerify(order.id, 'approve')"
                                class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 rounded-lg text-xs transition-colors flex items-center gap-1"
                                :disabled="verifyForm.processing"
                            >
                                <i class="fas fa-check-circle"></i> Approve Payment
                            </button>
                            <button 
                                @click="handleVerify(order.id, 'reject')"
                                class="bg-red-500 hover:bg-red-600 text-white font-bold px-4 py-2 rounded-lg text-xs transition-colors flex items-center gap-1"
                                :disabled="verifyForm.processing"
                            >
                                <i class="fas fa-times-circle"></i> Reject Receipt
                            </button>
                        </div>
                    </div>

                </div>
            </div>
            <div v-else class="bg-white rounded-3xl border border-slate-100 p-20 text-center">
                <i class="fas fa-receipt text-slate-200 text-4xl mb-4"></i>
                <h4 class="text-slate-400 font-bold">No orders registered here.</h4>
            </div>

        </main>
    </div>

    <!-- Payment Slip Modal View -->
    <div v-if="showReceiptModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 modal-overlay">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-8 relative space-y-6">
            <button 
                @click="closeReceiptModal" 
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center"
            >
                <i class="fas fa-times text-lg"></i>
            </button>

            <h3 class="text-lg font-bold text-slate-800 font-title">Review Payment Receipt Slip</h3>
            
            <div class="border border-slate-100 rounded-2xl overflow-hidden max-h-[70vh] flex items-center justify-center bg-slate-50">
                <iframe 
                    v-if="activeReceiptUrl.endsWith('.pdf')" 
                    :src="activeReceiptUrl" 
                    class="w-full h-[60vh]"
                ></iframe>
                <img 
                    v-else 
                    :src="activeReceiptUrl" 
                    alt="Receipt Slip File" 
                    class="max-w-full max-h-[60vh] object-contain"
                />
            </div>
            <div class="text-center">
                <a 
                    :href="activeReceiptUrl" 
                    target="_blank" 
                    class="inline-flex items-center gap-2 text-xs font-bold text-[#c5a880] hover:underline"
                >
                    <i class="fas fa-external-link-alt"></i> Open File in New Tab
                </a>
            </div>
        </div>
    </div>
</template>
