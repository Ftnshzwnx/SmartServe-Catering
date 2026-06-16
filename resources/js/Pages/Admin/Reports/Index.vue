<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Chart } from 'chart.js/auto';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    salesData: {
        type: Array,
        required: true,
    },
    statusDistribution: {
        type: Array,
        required: true,
    },
    packagePopularity: {
        type: Array,
        required: true,
    },
    kpis: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    availableYears: {
        type: Array,
        required: true,
    },
    addonPopularity: {
        type: Array,
        required: true,
    },
});

const { t } = useLocalization();

function getMonthName(monthNumber) {
    return t('month_' + monthNumber);
}

const monthsList = [
    { value: 1, name: 'January' },
    { value: 2, name: 'February' },
    { value: 3, name: 'March' },
    { value: 4, name: 'April' },
    { value: 5, name: 'May' },
    { value: 6, name: 'June' },
    { value: 7, name: 'July' },
    { value: 8, name: 'August' },
    { value: 9, name: 'September' },
    { value: 10, name: 'October' },
    { value: 11, name: 'November' },
    { value: 12, name: 'December' },
];

const viewMode = ref(props.filters.viewMode);
const year = ref(props.filters.year);
const month = ref(props.filters.month);

const totalPackageBookings = computed(() => {
    return props.packagePopularity.reduce((sum, p) => sum + p.bookings_count, 0);
});

const totalStatusBookings = computed(() => {
    return props.statusDistribution.reduce((sum, s) => sum + s.count, 0);
});

const totalAddonBookings = computed(() => {
    return props.addonPopularity.reduce((sum, a) => sum + a.bookings_count, 0);
});

function getStatusLabel(status) {
    const map = {
        'Pending': t('pending'),
        'Confirmed': t('confirmed'),
        'Payment Submitted': t('awaiting_verification'),
        'Delivered': t('delivered_tab'),
        'Completed': t('completed_tab'),
        'Deposit Rejected': t('deposit_rejected'),
        'Balance Rejected': t('balance_rejected'),
        'Cancelled': t('cancelled_tab'),
    };
    return map[status] || status;
}

const statusConfig = {
    'Pending': { color: '#F59E0B' },
    'Confirmed': { color: '#0F766E' },
    'Payment Submitted': { color: '#4F46E5' },
    'Delivered': { color: '#0D9488' },
    'Completed': { color: '#16A34A' },
    'Deposit Rejected': { color: '#DC2626' },
    'Balance Rejected': { color: '#DC2626' },
    'Cancelled': { color: '#64748B' },
};

const salesChartCanvas = ref(null);
const statusChartCanvas = ref(null);
const packageChartCanvas = ref(null);

let salesChart = null;
let statusChart = null;
let packageChart = null;

const applyFilters = () => {
    router.get(route('admin.reports'), {
        view_mode: viewMode.value,
        year: year.value,
        month: month.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch([viewMode, year, month], () => {
    applyFilters();
});

watch(() => props.filters, (newFilters) => {
    if (newFilters.viewMode !== viewMode.value) viewMode.value = newFilters.viewMode;
    if (newFilters.year !== year.value) year.value = newFilters.year;
    if (newFilters.month !== month.value) month.value = newFilters.month;
}, { deep: true });

const initCharts = () => {
    if (salesChart) salesChart.destroy();
    if (statusChart) statusChart.destroy();
    if (packageChart) packageChart.destroy();

    // 1. Sales Performance Line Chart
    if (salesChartCanvas.value) {
        const labels = props.salesData.map(s => s.label);
        const data = props.salesData.map(s => parseFloat(s.revenue));
        const prevData = props.salesData.map(s => parseFloat(s.prev_revenue));

        salesChart = new Chart(salesChartCanvas.value, {
            type: 'line',
            data: {
                labels: labels.length > 0 ? labels : [t('admin_reports_no_sales_data')],
                datasets: [
                    {
                        label: t('admin_reports_chart_legend_revenue_year').replace('{year}', props.filters.year),
                        data: data.length > 0 ? data : [0],
                        borderColor: '#4A6B5D',
                        backgroundColor: 'rgba(74, 107, 93, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#4A6B5D',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#C5A880',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2,
                    },
                    ...(props.filters.viewMode === 'monthly' ? [{
                        label: t('admin_reports_chart_legend_revenue_year').replace('{year}', props.filters.year - 1),
                        data: prevData.length > 0 ? prevData : [0],
                        borderColor: '#C5A880',
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.35,
                        borderWidth: 2,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#C5A880',
                        pointBorderWidth: 1.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }] : [])
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: props.filters.viewMode === 'monthly',
                        position: 'top',
                        labels: {
                            color: '#2D3330',
                            font: { family: 'Plus Jakarta Sans', size: 9, weight: '600' },
                            boxWidth: 12,
                            padding: 10,
                        }
                    },
                    tooltip: {
                        backgroundColor: '#2D3330',
                        titleColor: '#FAF7F2',
                        bodyColor: '#FAF7F2',
                        titleFont: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                        padding: 10,
                        callbacks: {
                            label: (context) => `${context.dataset.label}: RM ${context.raw.toLocaleString(undefined, {minimumFractionDigits: 2})}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#8C8275', font: { family: 'Plus Jakarta Sans', size: 10, weight: '600' } }
                    },
                    y: {
                        border: { dash: [4, 4] },
                        grid: { color: '#E6E1DA' },
                        ticks: {
                            color: '#8C8275',
                            font: { family: 'Plus Jakarta Sans', size: 10, weight: '500' },
                            callback: (val) => 'RM ' + val.toLocaleString()
                        }
                    }
                }
            }
        });
    }

    // 2. Status Doughnut Chart
    if (statusChartCanvas.value) {
        const statusColors = {
            'Pending': '#F59E0B',
            'Confirmed': '#0F766E',
            'Payment Submitted': '#4F46E5',
            'Delivered': '#0D9488',
            'Completed': '#16A34A',
            'Deposit Rejected': '#DC2626',
            'Balance Rejected': '#DC2626',
            'Cancelled': '#64748B',
        };

        const labels = props.statusDistribution.map(s => getStatusLabel(s.status));
        const data = props.statusDistribution.map(s => s.count);
        const colors = props.statusDistribution.map(s => statusColors[s.status] || '#CBD5E1');

        statusChart = new Chart(statusChartCanvas.value, {
            type: 'doughnut',
            data: {
                labels: labels.length > 0 ? labels : [t('admin_reports_no_bookings_found')],
                datasets: [{
                    data: data.length > 0 ? data : [1],
                    backgroundColor: data.length > 0 ? colors : ['#E6E1DA'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2D3330',
                        titleFont: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                        padding: 10,
                        callbacks: {
                            label: (context) => {
                                if (props.statusDistribution.length === 0) return ` 0 ${t('admin_reports_chart_tooltip_bookings').trim()}`;
                                return ` ${context.label}: ${context.raw} ${context.raw === 1 ? t('admin_reports_chart_tooltip_booking').trim() : t('admin_reports_chart_tooltip_bookings').trim()}`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Package Popularity Doughnut Chart
    if (packageChartCanvas.value) {
        const labels = props.packagePopularity.map(p => p.package_name);
        const bookings = props.packagePopularity.map(p => p.bookings_count);
        const palette = ['#4A6B5D', '#C5A880', '#8C8275', '#A2BAAE', '#E2D3BE', '#D3C4B2'];

        packageChart = new Chart(packageChartCanvas.value, {
            type: 'doughnut',
            data: {
                labels: labels.length > 0 ? labels : [t('admin_reports_no_pkgs_booked')],
                datasets: [
                    {
                        data: bookings.length > 0 ? bookings : [1],
                        backgroundColor: bookings.length > 0 ? palette.slice(0, labels.length) : ['#E6E1DA'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#2D3330',
                            font: { family: 'Plus Jakarta Sans', size: 9, weight: '600' },
                            padding: 8,
                            boxWidth: 8,
                            usePointStyle: true,
                        }
                    },
                    tooltip: {
                        backgroundColor: '#2D3330',
                        titleFont: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                        padding: 10,
                        callbacks: {
                            label: (context) => {
                                if (props.packagePopularity.length === 0) return ` 0 ${t('admin_reports_chart_tooltip_bookings').trim()}`;
                                return ` ${context.label}: ${context.raw} ${context.raw === 1 ? t('admin_reports_chart_tooltip_booking').trim() : t('admin_reports_chart_tooltip_bookings').trim()}`;
                            }
                        }
                    }
                }
            }
        });
    }
};

onMounted(() => {
    initCharts();
});

watch(() => props.salesData, () => {
    initCharts();
}, { deep: true });
</script>

<template>
    <AdminLayout
        :title="t('admin_reports_dashboard_title')"
        :header-title="t('admin_reports_header_title')"
        :header-desc="t('admin_reports_desc')"
    >
        <template #header-action>
            <div class="flex flex-wrap items-center gap-3 bg-white border border-[#E6E1DA] rounded-2xl p-2 shadow-xs">
                <!-- View Mode Toggle Buttons -->
                <div class="flex bg-[#FAF7F2] p-1 rounded-xl border border-[#E6E1DA]">
                    <button
                        type="button"
                        @click="viewMode = 'monthly'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer"
                        :class="viewMode === 'monthly' ? 'bg-[#4A6B5D] text-white shadow-xs' : 'text-[#8C8275] hover:text-[#2D3330]'"
                    >
                        {{ t('admin_reports_monthly') }}
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'daily'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer"
                        :class="viewMode === 'daily' ? 'bg-[#4A6B5D] text-white shadow-xs' : 'text-[#8C8275] hover:text-[#2D3330]'"
                    >
                        {{ t('admin_reports_daily') }}
                    </button>
                </div>

                <!-- Year Select Dropdown -->
                <div class="relative">
                    <select
                        v-model="year"
                        class="bg-[#FAF7F2] border border-[#E6E1DA] text-[#2D3330] rounded-xl pl-3 pr-8 py-1.5 text-xs font-bold focus:outline-none focus:border-[#4A6B5D] focus:ring-1 focus:ring-[#4A6B5D] cursor-pointer appearance-none"
                    >
                        <option v-for="y in availableYears" :key="y" :value="y">
                            {{ t('admin_reports_year') }} {{ y }}
                        </option>
                    </select>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-[#8C8275] text-[10px]">
                        <i class="fas fa-chevron-down"></i>
                    </span>
                </div>

                <!-- Month Select Dropdown (only visible in daily view mode) -->
                <div v-if="viewMode === 'daily'" class="relative">
                    <select
                        v-model="month"
                        class="bg-[#FAF7F2] border border-[#E6E1DA] text-[#2D3330] rounded-xl pl-3 pr-8 py-1.5 text-xs font-bold focus:outline-none focus:border-[#4A6B5D] focus:ring-1 focus:ring-[#4A6B5D] cursor-pointer appearance-none"
                    >
                        <option v-for="m in monthsList" :key="m.value" :value="m.value">
                            {{ t('month_' + m.value) }}
                        </option>
                    </select>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-[#8C8275] text-[10px]">
                        <i class="fas fa-chevron-down"></i>
                    </span>
                </div>
            </div>
        </template>

        <!-- KPI Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
            <!-- Total Revenue -->
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs p-3 md:p-6 flex flex-col justify-between gap-2.5 animate-fade-in">
                <div class="flex items-center justify-between gap-1.5">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block truncate" :title="t('admin_reports_total_revenue')">{{ t('admin_reports_total_revenue') }}</span>
                    <div class="w-8 h-8 bg-emerald-50 text-[#4A6B5D] rounded-lg border border-emerald-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div>
                    <span class="text-sm md:text-2xl font-extrabold text-[#2D3330] font-serif-luxury block truncate">RM {{ parseFloat(kpis.totalRevenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                    <span v-if="t('admin_reports_timeframe_desc')" class="text-[8px] text-[#8C8275] block mt-1 font-semibold truncate">{{ t('admin_reports_timeframe_desc') }}</span>
                </div>
            </div>

            <!-- Average Order Value -->
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs p-3 md:p-6 flex flex-col justify-between gap-2.5 animate-fade-in" style="animation-delay: 50ms;">
                <div class="flex items-center justify-between gap-1.5">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block truncate" :title="t('admin_reports_avg_order_value')">{{ t('admin_reports_avg_order_value') }}</span>
                    <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg border border-blue-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-calculator"></i>
                    </div>
                </div>
                <div>
                    <span class="text-sm md:text-2xl font-extrabold text-[#2D3330] font-serif-luxury block truncate">RM {{ parseFloat(kpis.averageOrderValue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                    <span v-if="t('admin_reports_avg_order_desc')" class="text-[8px] text-[#8C8275] block mt-1 font-semibold truncate">{{ t('admin_reports_avg_order_desc') }}</span>
                </div>
            </div>

            <!-- Cancellation Rate -->
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs p-3 md:p-6 flex flex-col justify-between gap-2.5 animate-fade-in" style="animation-delay: 100ms;">
                <div class="flex items-center justify-between gap-1.5">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block truncate" :title="t('admin_reports_cancellation_rate')">{{ t('admin_reports_cancellation_rate') }}</span>
                    <div class="w-8 h-8 bg-rose-50 text-rose-600 rounded-lg border border-rose-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
                <div>
                    <span class="text-sm md:text-2xl font-extrabold text-[#2D3330] font-serif-luxury block truncate">{{ kpis.cancellationRate }}%</span>
                    <span v-if="t('admin_reports_cancellation_desc')" class="text-[8px] text-rose-500 block mt-1 font-bold truncate">{{ t('admin_reports_cancellation_desc').replace('{count}', kpis.totalBookings) }}</span>
                </div>
            </div>

            <!-- Best Seller -->
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs p-3 md:p-6 flex flex-col justify-between gap-2.5 animate-fade-in" style="animation-delay: 150ms;">
                <div class="flex items-center justify-between gap-1.5">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block truncate" :title="t('admin_reports_top_package')">{{ t('admin_reports_top_package') }}</span>
                    <div class="w-8 h-8 bg-amber-50 text-[#C5A880] rounded-lg border border-amber-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-crown"></i>
                    </div>
                </div>
                <div>
                    <span class="text-xs md:text-lg font-bold text-[#2D3330] block truncate" :title="kpis.topPackage">{{ kpis.topPackage }}</span>
                    <span v-if="t('admin_reports_top_package_desc')" class="text-[8px] text-[#C5A880] block mt-1 font-bold uppercase tracking-wider truncate">{{ t('admin_reports_top_package_desc') }}</span>
                </div>
            </div>
        </div>

        <!-- Business Health & Audit Board -->
        <div class="bg-[#FAF7F2] rounded-2xl md:rounded-3xl border border-[#E6E1DA] p-4 md:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6 shadow-2xs select-none">
            <div class="space-y-1">
                <h3 class="text-xs font-bold text-[#4A6B5D] uppercase tracking-widest flex items-center gap-1.5">
                    <i class="fas fa-shield-alt text-[#C5A880]"></i>
                    {{ t('admin_reports_business_health') }}
                </h3>
                <p v-if="t('admin_reports_business_health_desc')" class="text-[10px] text-[#8C8275] font-semibold">{{ t('admin_reports_business_health_desc') }}</p>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 md:gap-12 flex-grow max-w-3xl justify-end">
                <!-- Repeat Customer Rate -->
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_reports_repeat_clients') }}</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-lg font-extrabold text-[#2D3330]">{{ kpis.repeatCustomerRate }}%</span>
                        <span class="text-[9px] text-emerald-600 font-bold">{{ t('admin_reports_retention') }}</span>
                    </div>
                </div>

                <!-- Customer Lifetime Value (LTV) -->
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_reports_customer_ltv') }}</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-lg font-extrabold text-[#2D3330]">RM {{ parseFloat(kpis.customerLtv).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                        <span class="text-[9px] text-[#C5A880] font-bold">{{ t('admin_reports_avg_spend') }}</span>
                    </div>
                </div>

                <!-- Lost Revenue (Cancellations) -->
                <div class="space-y-1 col-span-2 sm:col-span-1">
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_reports_lost_revenue') }}</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-lg font-extrabold text-rose-600">RM {{ parseFloat(kpis.lostRevenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                        <span class="text-[9px] text-rose-500 font-bold">{{ t('admin_reports_lost_revenue_audit') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8">
            <!-- Monthly / Daily Sales Chart (7 cols) -->
            <div class="lg:col-span-7 bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] shadow-xs p-4 md:p-6 space-y-4 flex flex-col justify-between">
                <div>
                    <h2 class="text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                        {{ filters.viewMode === 'daily' ? t('admin_reports_daily_revenue') : t('admin_reports_monthly_revenue') }}
                    </h2>
                    <p v-if="t('admin_reports_sales_perf_daily') || t('admin_reports_sales_perf_monthly') || t('admin_reports_completed_orders_only')" class="text-[10px] text-[#8C8275] font-semibold mt-0.5">
                        {{ filters.viewMode === 'daily' 
                            ? t('admin_reports_sales_perf_daily').replace('{month}', getMonthName(filters.month)).replace('{year}', filters.year) 
                            : t('admin_reports_sales_perf_monthly').replace('{year}', filters.year) 
                        }} {{ t('admin_reports_completed_orders_only') }}
                    </p>
                </div>

                <div class="relative h-60 w-full">
                    <canvas ref="salesChartCanvas" v-show="salesData.length > 0"></canvas>
                    <div v-if="salesData.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-[#8C8275] border border-dashed border-[#E6E1DA] rounded-xl">
                        <i class="fas fa-chart-line text-2xl mb-1.5"></i>
                        <span class="text-xs font-bold">{{ t('admin_reports_no_sales_data') }}</span>
                    </div>
                </div>
            </div>

            <!-- Booking Status Distribution Doughnut Chart & Custom Legend Grid (5 cols) -->
            <div class="lg:col-span-5 bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] shadow-xs p-4 md:p-6 flex flex-col justify-between space-y-4 md:space-y-5">
                <div>
                    <h2 class="text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_reports_booking_statuses') }}</h2>
                    <p v-if="t('admin_reports_booking_statuses_desc')" class="text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ t('admin_reports_booking_statuses_desc') }}</p>
                </div>
 
                <div class="relative h-44 w-full flex items-center justify-center">
                    <canvas ref="statusChartCanvas" v-show="statusDistribution.length > 0"></canvas>
                    <div v-if="statusDistribution.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-[#8C8275] border border-dashed border-[#E6E1DA] rounded-2xl">
                        <i class="fas fa-chart-pie text-3xl mb-2"></i>
                        <span class="text-xs font-bold">{{ t('admin_reports_no_bookings_found') }}</span>
                    </div>
                </div>

                <!-- Custom Grid Legend -->
                <div v-show="statusDistribution.length > 0" class="grid grid-cols-2 gap-2.5 border-t border-[#E6E1DA] pt-4">
                    <div 
                        v-for="s in statusDistribution" 
                        :key="s.status"
                        class="flex items-center justify-between p-2 rounded-xl border border-[#E6E1DA] bg-white shadow-3xs"
                    >
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span 
                                class="w-2 h-2 rounded-full shrink-0" 
                                :style="{ backgroundColor: statusConfig[s.status]?.color || '#CBD5E1' }"
                            ></span>
                            <span class="text-[10px] font-bold text-[#2D3330] truncate" :title="getStatusLabel(s.status)">
                                {{ getStatusLabel(s.status) }}
                            </span>
                        </div>
                        <div class="text-right shrink-0 pl-1">
                            <span class="text-[10px] font-extrabold text-[#2D3330] block leading-none">
                                {{ s.count }}
                            </span>
                            <span class="text-[8px] font-bold text-[#8C8275] block mt-0.5">
                                {{ totalStatusBookings > 0 ? Math.round((s.count / totalStatusBookings) * 100) : 0 }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Package Popularity Split Layout (Leaderboard + Share Distribution) (12 cols) -->
            <div class="lg:col-span-12 bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] shadow-xs p-4 md:p-6 space-y-4 md:space-y-6">
                <div>
                    <h2 class="text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_reports_pkg_popularity_title') }}</h2>
                    <p v-if="t('admin_reports_pkg_popularity_desc')" class="text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ t('admin_reports_pkg_popularity_desc') }}</p>
                </div>

                <div v-show="packagePopularity.length > 0" class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8 items-center">
                    <!-- Left: Leaderboard (7 cols) -->
                    <div class="lg:col-span-7 space-y-4">
                        <div class="overflow-x-auto rounded-xl border border-[#E6E1DA]">
                            <table class="w-full min-w-[650px] text-left border-collapse">
                                <thead>
                                    <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                        <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider">{{ t('admin_reports_rank') }}</th>
                                        <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider">{{ t('admin_reports_package_name') }}</th>
                                        <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider text-center">{{ t('admin_reports_bookings') }}</th>
                                        <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider w-1/3">{{ t('admin_reports_popularity_share') }}</th>
                                        <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider text-right">{{ t('admin_reports_revenue_rm') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E6E1DA]">
                                    <tr v-for="(p, index) in packagePopularity" :key="p.package_name" class="hover:bg-[#FAF7F2]/50 transition-colors">
                                        <!-- Rank badge -->
                                        <td class="p-3">
                                            <span v-if="index === 0" class="w-6 h-6 rounded-full bg-amber-100 border border-amber-200 text-amber-800 flex items-center justify-center font-bold text-xs select-none" title="1st Best Seller">🥇</span>
                                            <span v-else-if="index === 1" class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 text-slate-800 flex items-center justify-center font-bold text-xs select-none" title="2nd Place">🥈</span>
                                            <span v-else-if="index === 2" class="w-6 h-6 rounded-full bg-orange-100 border border-orange-200 text-orange-800 flex items-center justify-center font-bold text-xs select-none" title="3rd Place">🥉</span>
                                            <span v-else class="w-6 h-6 rounded-full bg-gray-50 border border-gray-200 text-gray-600 flex items-center justify-center font-bold text-[10px] select-none">#{{ index + 1 }}</span>
                                        </td>
                                        <!-- Package Name -->
                                        <td class="p-3 font-bold text-[#2D3330] text-xs">
                                            {{ p.package_name }}
                                        </td>
                                        <!-- Bookings count -->
                                        <td class="p-3 text-center font-semibold text-[#2D3330] text-xs">
                                            {{ p.bookings_count }}
                                        </td>
                                        <!-- Progress bar -->
                                        <td class="p-3">
                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 bg-[#FAF7F2] rounded-full flex-grow border border-[#E6E1DA] overflow-hidden">
                                                    <div 
                                                        class="h-full rounded-full transition-all duration-500"
                                                        :class="index === 0 ? 'bg-[#4A6B5D]' : (index === 1 ? 'bg-[#C5A880]' : 'bg-[#8C8275]')"
                                                        :style="{ width: totalPackageBookings > 0 ? ((p.bookings_count / totalPackageBookings) * 100) + '%' : '0%' }"
                                                    ></div>
                                                </div>
                                                <span class="text-[9px] font-bold text-[#8C8275] tracking-tight min-w-[32px]">
                                                    {{ totalPackageBookings > 0 ? Math.round((p.bookings_count / totalPackageBookings) * 100) : 0 }}%
                                                </span>
                                            </div>
                                        </td>
                                        <!-- Total Revenue -->
                                        <td class="p-3 text-right font-extrabold text-[#4A6B5D] text-xs">
                                            RM {{ parseFloat(p.total_revenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right: Share Chart (5 cols) -->
                    <div class="lg:col-span-5 flex flex-col items-center justify-center border-t lg:border-t-0 lg:border-l border-[#E6E1DA] pt-6 lg:pt-0 lg:pl-8 space-y-4">
                        <div class="relative h-56 w-full max-w-[280px]">
                            <canvas ref="packageChartCanvas"></canvas>
                        </div>
                        <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_reports_freq_dist') }}</span>
                    </div>
                </div>

                <div v-if="packagePopularity.length === 0" class="flex flex-col items-center justify-center text-[#8C8275] border border-dashed border-[#E6E1DA] rounded-2xl py-12">
                    <i class="fas fa-utensils text-3xl mb-2"></i>
                    <span class="text-xs font-bold">{{ t('admin_reports_no_pkgs_booked') }}</span>
                </div>
            </div>

            <!-- Add-on Popularity Analysis Leaderboard (12 cols) -->
            <div class="lg:col-span-12 bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] shadow-xs p-4 md:p-6 space-y-4 md:space-y-6">
                <div>
                    <h2 class="text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_reports_addon_popularity_title') }}</h2>
                    <p v-if="t('admin_reports_addon_popularity_desc')" class="text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ t('admin_reports_addon_popularity_desc') }}</p>
                </div>

                <div v-show="addonPopularity.length > 0" class="overflow-x-auto rounded-xl border border-[#E6E1DA]">
                    <table class="w-full min-w-[650px] text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider w-16">{{ t('admin_reports_rank') }}</th>
                                <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider">{{ t('admin_reports_addon_item_name') }}</th>
                                <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider text-center w-32">{{ t('admin_reports_times_selected') }}</th>
                                <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider w-1/3">{{ t('admin_reports_popularity_share') }}</th>
                                <th class="p-3 text-[9px] font-bold text-[#8C8275] uppercase tracking-wider text-right w-40">{{ t('admin_reports_revenue_generated') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E1DA]">
                            <tr v-for="(a, index) in addonPopularity" :key="a.addon_name" class="hover:bg-[#FAF7F2]/50 transition-colors">
                                <!-- Rank Badge -->
                                <td class="p-3">
                                    <span v-if="index === 0" class="w-6 h-6 rounded-full bg-amber-100 border border-amber-200 text-amber-800 flex items-center justify-center font-bold text-xs select-none" title="Top Add-on">🥇</span>
                                    <span v-else-if="index === 1" class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 text-slate-800 flex items-center justify-center font-bold text-xs select-none" title="2nd Place">🥈</span>
                                    <span v-else-if="index === 2" class="w-6 h-6 rounded-full bg-orange-100 border border-orange-200 text-orange-800 flex items-center justify-center font-bold text-xs select-none" title="3rd Place">🥉</span>
                                    <span v-else class="w-6 h-6 rounded-full bg-gray-50 border border-gray-200 text-gray-600 flex items-center justify-center font-bold text-[10px] select-none">#{{ index + 1 }}</span>
                                </td>
                                <!-- Addon name -->
                                <td class="p-3 font-bold text-xs text-[#2D3330]">
                                    {{ a.addon_name }}
                                </td>
                                <!-- Bookings count -->
                                <td class="p-3 text-center text-xs font-semibold text-[#2D3330]">
                                    {{ a.bookings_count }}
                                </td>
                                <!-- Progress bar -->
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 bg-[#FAF7F2] rounded-full flex-grow border border-[#E6E1DA] overflow-hidden">
                                            <div 
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="index === 0 ? 'bg-[#4A6B5D]' : (index === 1 ? 'bg-[#C5A880]' : 'bg-[#8C8275]')"
                                                :style="{ width: totalAddonBookings > 0 ? ((a.bookings_count / totalAddonBookings) * 100) + '%' : '0%' }"
                                            ></div>
                                        </div>
                                        <span class="text-[9px] font-bold text-[#8C8275] tracking-tight min-w-[32px]">
                                            {{ totalAddonBookings > 0 ? Math.round((a.bookings_count / totalAddonBookings) * 100) : 0 }}%
                                        </span>
                                    </div>
                                </td>
                                <!-- Revenue Generated -->
                                <td class="p-3 text-right text-xs font-extrabold text-[#4A6B5D]">
                                    RM {{ parseFloat(a.total_revenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="addonPopularity.length === 0" class="flex flex-col items-center justify-center text-[#8C8275] border border-dashed border-[#E6E1DA] rounded-2xl py-8">
                    <i class="fas fa-ticket-alt text-3xl mb-2"></i>
                    <span class="text-xs font-bold">{{ t('admin_reports_no_addons_ordered') }}</span>
                </div>
            </div>
        </div>

        <!-- Export Center Panel Card -->
        <div class="bg-white rounded-2xl md:rounded-3xl border border-[#E6E1DA] shadow-xs p-4 md:p-6 space-y-4 md:space-y-6">
            <div>
                <h2 class="text-sm md:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_reports_export_center') }}</h2>
                <p v-if="t('admin_reports_export_center_desc')" class="text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ t('admin_reports_export_center_desc') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- CSV Excel Box -->
                <div class="border border-[#E6E1DA] rounded-2xl p-5 flex items-start gap-4 hover:border-emerald-500/30 hover:bg-emerald-50/10 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0 border border-emerald-100">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="space-y-2.5 flex-grow">
                        <h4 class="text-xs font-bold text-[#2D3330] uppercase tracking-wide">{{ t('admin_reports_csv_title') }}</h4>
                        <p v-if="t('admin_reports_csv_desc')" class="text-[11px] text-[#8C8275] leading-relaxed">{{ t('admin_reports_csv_desc') }}</p>
                        <a 
                            :href="route('admin.reports.export', { format: 'csv', view_mode: filters.viewMode, year: filters.year, month: filters.month })"
                            class="inline-flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold px-4 py-2 rounded-xl text-[10px] uppercase tracking-widest border border-emerald-200 transition-colors cursor-pointer"
                            target="_blank"
                        >
                            <i class="fas fa-download"></i> {{ t('admin_reports_csv_btn') }}
                        </a>
                    </div>
                </div>

                <!-- PDF Box -->
                <div class="border border-[#E6E1DA] rounded-2xl p-5 flex items-start gap-4 hover:border-rose-500/30 hover:bg-rose-50/10 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shrink-0 border border-rose-100">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="space-y-2.5 flex-grow">
                        <h4 class="text-xs font-bold text-[#2D3330] uppercase tracking-wide">{{ t('admin_reports_pdf_title') }}</h4>
                        <p v-if="t('admin_reports_pdf_desc')" class="text-[11px] text-[#8C8275] leading-relaxed">{{ t('admin_reports_pdf_desc') }}</p>
                        <a 
                            :href="route('admin.reports.export', { format: 'pdf', view_mode: filters.viewMode, year: filters.year, month: filters.month })"
                            class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold px-4 py-2 rounded-xl text-[10px] uppercase tracking-widest border border-rose-200 transition-colors cursor-pointer"
                            target="_blank"
                        >
                            <i class="fas fa-download"></i> {{ t('admin_reports_pdf_btn') }}
                        </a>
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
