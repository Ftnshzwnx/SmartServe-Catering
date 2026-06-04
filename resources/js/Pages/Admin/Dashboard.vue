<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    monthlySales: {
        type: Array,
        required: true,
    },
    recentOrders: {
        type: Array,
        required: true,
    },
});

function getMonthName(monthNumber) {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[monthNumber - 1] || 'Month ' + monthNumber;
}

// Find max revenue for scaling simple visual bar charts
const maxRevenue = computed(() => {
    if (props.monthlySales.length === 0) return 1;
    return Math.max(...props.monthlySales.map(s => parseFloat(s.revenue || 0)), 1);
});

function getBarHeightPercentage(revenue) {
    return (parseFloat(revenue) / maxRevenue.value) * 100 + '%';
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
    <Head title="Admin Dashboard Overview" />

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
        .metric-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.01);
        }
        .chart-container {
            background: #ffffff;
            border-radius: 24px;
            padding: 28px;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .bar-fill {
            background: linear-gradient(180deg, #c5a880 0%, #b89047 100%);
            border-radius: 6px 6px 0 0;
            transition: height 0.5s ease;
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
                    <Link :href="route('admin.dashboard')" class="sidebar-link active">
                        <i class="fas fa-chart-line text-sm w-5"></i> Dashboard
                    </Link>
                    <Link :href="route('admin.orders')" class="sidebar-link">
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
                <!-- Log out -->
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
        <main class="main-content p-10 space-y-10">
            <!-- Upper greeting bar -->
            <div class="flex justify-between items-center border-b border-slate-200 pb-6">
                <div>
                    <h1 class="text-3xl font-bold font-title text-slate-800">Admin Dashboard</h1>
                    <p class="text-xs text-slate-400 mt-1">Review metrics, verify receipts and manage business settings.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('admin.orders', { status: 'Pending' })" 
                        class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow-sm transition-colors"
                    >
                        Pending Actions
                        <span v-if="metrics.pendingOrders > 0" class="bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center font-bold text-[10px]">
                            {{ metrics.pendingOrders }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Revenue -->
                <div class="metric-card flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Total Revenue</span>
                        <span class="text-2xl font-extrabold text-[#0f172a] font-title">RM {{ parseFloat(metrics.totalRevenue || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="metric-card flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Total Bookings</span>
                        <span class="text-2xl font-extrabold text-[#0f172a] font-title">{{ metrics.totalOrders }}</span>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>

                <!-- Pending Verification -->
                <div class="metric-card flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Pending Verify</span>
                        <span class="text-2xl font-extrabold text-[#0f172a] font-title">{{ metrics.pendingPayment }}</span>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 text-[#c5a880] rounded-xl flex items-center justify-center text-lg">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>

                <!-- Completed -->
                <div class="metric-card flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Completed Events</span>
                        <span class="text-2xl font-extrabold text-[#0f172a] font-title">{{ metrics.completedOrders }}</span>
                    </div>
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-lg">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            <!-- Visual Bar Chart for Sales (pure css) & Recent Table -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Sales chart visualizer (7 cols) -->
                <div class="lg:col-span-7 chart-container space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-bold text-slate-800 font-title">Monthly Sales Performance (RM)</h2>
                        <span class="text-xs text-slate-400">Current Year</span>
                    </div>

                    <div v-if="monthlySales.length > 0" class="h-64 flex items-end justify-between gap-4 border-b border-slate-100 pb-4">
                        <div 
                            v-for="sale in monthlySales" 
                            :key="sale.month" 
                            class="flex-grow flex flex-col items-center gap-2 group relative"
                        >
                            <!-- Tooltip value -->
                            <div class="absolute bottom-full mb-1 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-800 text-white text-[10px] font-bold px-2 py-1 rounded shadow pointer-events-none">
                                RM {{ parseFloat(sale.revenue).toLocaleString() }}
                            </div>
                            <!-- Simple bar -->
                            <div 
                                class="bar-fill w-full"
                                :style="{ height: getBarHeightPercentage(sale.revenue) }"
                            ></div>
                            <span class="text-[10px] font-bold text-slate-400">{{ getMonthName(sale.month).substring(0, 3) }}</span>
                        </div>
                    </div>
                    <div v-else class="h-64 flex flex-col items-center justify-center text-slate-300 border border-dashed border-slate-200 rounded-xl">
                        <i class="fas fa-chart-bar text-3xl mb-2"></i>
                        <span class="text-xs font-bold">No completed sales data available this year.</span>
                    </div>
                </div>

                <!-- Recent Orders list (5 cols) -->
                <div class="lg:col-span-5 chart-container space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-bold text-slate-800 font-title">Recent Bookings</h2>
                        <Link :href="route('admin.orders')" class="text-xs font-bold text-[#c5a880] hover:underline">View All</Link>
                    </div>

                    <div v-if="recentOrders.length > 0" class="divide-y divide-slate-100">
                        <div v-for="order in recentOrders" :key="order.id" class="py-3.5 flex justify-between items-center gap-4 first:pt-0">
                            <div>
                                <span class="font-bold text-xs text-slate-700 block font-title">#SSC-{{ order.id }} - {{ order.user?.full_name || order.user?.name || 'Customer' }}</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ order.delivery_date }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-xs text-[#0f172a] block">RM {{ parseFloat(order.total_price).toFixed(2) }}</span>
                                <span class="inline-flex text-[9px] font-bold px-2 py-0.5 rounded-full border mt-1" :class="getStatusBadge(order.status)">
                                    {{ order.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center text-slate-300">
                        <i class="fas fa-receipt text-3xl mb-2"></i>
                        <h5 class="text-xs font-bold">No bookings registered.</h5>
                    </div>
                </div>
            </div>

        </main>
    </div>
</template>
