<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    packages: {
        type: Array,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
    results: {
        type: Array,
        default: () => [],
    },
    searched: {
        type: Boolean,
        default: false,
    },
    input: {
        type: Object,
        default: () => ({ mode: 'guest', budget: '', guest_count: '' }),
    },
    dishes: {
        type: Array,
        default: () => [],
    },
    blockedDates: {
        type: Array,
        default: () => [],
    },
});

const { t, currentLanguage } = useLocalization();

const showCustomForm = ref(false);
const customForm = ref({
    budget: '',
    guest_count: '',
    delivery_date: '',
    delivery_time: '',
    address: '',
    dishes: [],
    notes: '',
});
const formErrors = ref({});
const isSubmitting = ref(false);

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

const submitCustomProposal = () => {
    formErrors.value = {};
    
    if (!customForm.value.budget || customForm.value.budget < 100) {
        formErrors.value.budget = t('val_min_budget');
    }
    if (!customForm.value.guest_count || customForm.value.guest_count < 20) {
        formErrors.value.guest_count = t('val_min_guest_count');
    }
    if (!customForm.value.delivery_date) {
        formErrors.value.delivery_date = t('val_delivery_date_required');
    }
    if (!customForm.value.delivery_time) {
        formErrors.value.delivery_time = t('val_delivery_time_required');
    }
    if (!customForm.value.address) {
        formErrors.value.address = t('val_address_required');
    }
    if (customForm.value.dishes.length === 0) {
        formErrors.value.dishes = t('val_select_wishlist_dish');
    }

    if (Object.keys(formErrors.value).length > 0) return;

    isSubmitting.value = true;
    router.post(route('orders.custom-proposal.store'), customForm.value, {
        onError: (errors) => {
            formErrors.value = errors;
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// Active tab selection for search mode
const searchMode = ref(props.input.mode || 'guest');
const targetBudget = ref(props.input.budget ? parseFloat(props.input.budget) : 5000);
const guestCount = ref(props.input.guest_count ? parseInt(props.input.guest_count) : 150);

// Default package selection to the first available package
const selectedPackageId = ref(props.packages[0]?.id || null);
const selectedAddonIds = ref([]);

// Watch package selection to reset selected addons
watch(selectedPackageId, () => {
    selectedAddonIds.value = [];
});

const selectedPackage = computed(() => {
    return props.packages.find(pkg => pkg.id === selectedPackageId.value) || props.packages[0] || null;
});

// Calculate total cost per pax for selected package + addons
const totalCostPerPax = computed(() => {
    if (!selectedPackage.value) return 0;
    const base = parseFloat(selectedPackage.value.price);
    const addons = selectedPackage.value.addons || [];
    const addonsCost = addons
        .filter(addon => selectedAddonIds.value.includes(addon.id))
        .reduce((sum, addon) => sum + parseFloat(addon.price_per_pax), 0);
    return base + addonsCost;
});

// Compute Pax based on search mode
const computedPax = computed(() => {
    if (!selectedPackage.value) return 0;
    const minOrder = parseInt(selectedPackage.value.min_order);
    
    if (searchMode.value === 'budget') {
        if (targetBudget.value <= 0) return 0;
        const costPerPax = totalCostPerPax.value;
        if (costPerPax <= 0) return minOrder;
        const calculated = Math.floor(targetBudget.value / costPerPax);
        return Math.max(calculated, 0); 
    } else {
        return Math.max(guestCount.value || 0, 0);
    }
});

// Actual guest count to check against minimum requirements
const finalPaxCount = computed(() => {
    if (!selectedPackage.value) return 0;
    const minOrder = parseInt(selectedPackage.value.min_order);
    if (searchMode.value === 'budget') {
        return computedPax.value;
    } else {
        // In guest mode, we automatically adjust to min order if user enters lower guest count
        return Math.max(computedPax.value, minOrder);
    }
});

// Computed Totals based on final pax count
const baseCostTotal = computed(() => {
    if (!selectedPackage.value) return 0;
    return parseFloat(selectedPackage.value.price) * finalPaxCount.value;
});

const addonsCostTotal = computed(() => {
    if (!selectedPackage.value) return 0;
    const addons = selectedPackage.value.addons || [];
    const addonsCostPerPax = addons
        .filter(addon => selectedAddonIds.value.includes(addon.id))
        .reduce((sum, addon) => sum + parseFloat(addon.price_per_pax), 0);
    return addonsCostPerPax * finalPaxCount.value;
});

const grandTotal = computed(() => {
    return baseCostTotal.value + addonsCostTotal.value;
});

const depositAmount = computed(() => {
    return grandTotal.value * 0.3;
});

const balanceAmount = computed(() => {
    return grandTotal.value * 0.7;
});

// Budget validations and differences
const budgetDifference = computed(() => {
    if (searchMode.value !== 'budget') return 0;
    return targetBudget.value - grandTotal.value;
});

const isBudgetInsufficient = computed(() => {
    if (!selectedPackage.value) return false;
    const minOrder = parseInt(selectedPackage.value.min_order);
    if (searchMode.value === 'budget') {
        const minRequiredTotal = totalCostPerPax.value * minOrder;
        return targetBudget.value < minRequiredTotal;
    }
    return false;
});

const isGuestCountBelowMin = computed(() => {
    if (!selectedPackage.value || searchMode.value === 'budget') return false;
    const minOrder = parseInt(selectedPackage.value.min_order);
    return guestCount.value > 0 && guestCount.value < minOrder;
});

const toggleAddon = (addonId) => {
    const idx = selectedAddonIds.value.indexOf(addonId);
    if (idx > -1) {
        selectedAddonIds.value.splice(idx, 1);
    } else {
        selectedAddonIds.value.push(addonId);
    }
};

const selectPackage = (pkgId) => {
    selectedPackageId.value = pkgId;
};

// Book redirect URL
const bookRedirectUrl = computed(() => {
    if (!selectedPackage.value) return '#';
    const paxParam = finalPaxCount.value;
    const addonsParam = selectedAddonIds.value.join(',');
    let url = route('cart.customize', { package_id: selectedPackage.value.id }) + `?pax=${paxParam}`;
    if (addonsParam) {
        url += `&addons=${addonsParam}`;
    }
    return url;
});

// Custom Calendar Dropdown Logic
const showCalendar = ref(false);
const todayDate = new Date();
const calendarYear = ref(todayDate.getFullYear());
const calendarMonth = ref(todayDate.getMonth());

const monthNames = computed(() => [
    t('month_jan'), t('month_feb'), t('month_mar'), t('month_apr'), t('month_may'), t('month_jun'),
    t('month_jul'), t('month_aug'), t('month_sep'), t('month_oct'), t('month_nov'), t('month_dec')
]);

const weekdays = computed(() => [
    t('day_sun'), t('day_mon'), t('day_tue'), t('day_wed'), t('day_thu'), t('day_fri'), t('day_sat')
]);

function prevMonth() {
    if (calendarMonth.value === 0) {
        calendarMonth.value = 11;
        calendarYear.value--;
    } else {
        calendarMonth.value--;
    }
}

function nextMonth() {
    if (calendarMonth.value === 11) {
        calendarMonth.value = 0;
        calendarYear.value++;
    } else {
        calendarMonth.value++;
    }
}

const calendarDays = computed(() => {
    const year = calendarYear.value;
    const month = calendarMonth.value;

    const firstDay = new Date(year, month, 1);
    const startDay = firstDay.getDay();

    const totalDays = new Date(year, month + 1, 0).getDate();
    const prevTotalDays = new Date(year, month, 0).getDate();

    const days = [];

    // Add padding days from previous month
    for (let i = startDay - 1; i >= 0; i--) {
        const d = prevTotalDays - i;
        const prevMonthVal = month === 0 ? 11 : month - 1;
        const prevYearVal = month === 0 ? year - 1 : year;
        const dateStr = `${prevYearVal}-${String(prevMonthVal + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            dateStr,
            dayNumber: d,
            isCurrentMonth: false,
            isDisabled: true,
            isBlocked: false,
            isSelected: false,
        });
    }

    // Add days of current month
    for (let d = 1; d <= totalDays; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const dateObj = new Date(year, month, d);
        dateObj.setHours(0, 0, 0, 0);
        
        const minDateLimit = new Date();
        minDateLimit.setDate(minDateLimit.getDate() + 7);
        minDateLimit.setHours(0, 0, 0, 0);

        const isBeforeMin = dateObj < minDateLimit;
        const isBlocked = props.blockedDates.includes(dateStr);
        const isSelected = customForm.value.delivery_date === dateStr;

        days.push({
            dateStr,
            dayNumber: d,
            isCurrentMonth: true,
            isDisabled: isBeforeMin || isBlocked,
            isBlocked,
            isSelected,
        });
    }

    const remainingCells = 42 - days.length;
    for (let d = 1; d <= remainingCells; d++) {
        const nextMonthVal = month === 11 ? 0 : month + 1;
        const nextYearVal = month === 11 ? year + 1 : year;
        const dateStr = `${nextYearVal}-${String(nextMonthVal + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            dateStr,
            dayNumber: d,
            isCurrentMonth: false,
            isDisabled: true,
            isBlocked: false,
            isSelected: false,
        });
    }

    return days;
});

function selectDate(day) {
    if (day.isDisabled) return;
    customForm.value.delivery_date = day.dateStr;
    showCalendar.value = false;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    const dateObj = new Date(year, month, day);
    return dateObj.toLocaleDateString((currentLanguage.value || currentLanguage) === 'en' ? 'en-US' : 'ms-MY', { day: 'numeric', month: 'short', year: 'numeric' });
}

const formattedSelectedDate = computed(() => {
    return formatDate(customForm.value.delivery_date);
});

onMounted(() => {
    if (customForm.value.delivery_date) {
        const parts = customForm.value.delivery_date.split('-');
        if (parts.length === 3) {
            calendarYear.value = parseInt(parts[0], 10);
            calendarMonth.value = parseInt(parts[1], 10) - 1;
        }
    }
});
</script>

<template>
    <Head :title="t('budget_planner')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .mode-tab {
            padding: 8px 14px;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 12px;
            border: 1px solid #E6E1DA;
            background: white;
            color: #8C8275;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        @media (min-width: 640px) {
            .mode-tab {
                padding: 12px 24px;
                font-size: 0.75rem;
                border-radius: 16px;
                letter-spacing: 0.1em;
            }
        }
        .mode-tab.active {
            background: #4A6B5D;
            color: white;
            border-color: #4A6B5D;
            box-shadow: 0 4px 12px rgba(74, 107, 93, 0.15);
        }
        
        .simulator-container {
            background: white;
            border-radius: 8px;
            padding: 10px;
            border: 1px solid #E6E1DA;
        }
        @media (min-width: 640px) {
            .simulator-container {
                border-radius: 14px;
                padding: 18px;
            }
        }
        
        .package-select-card {
            border: 1px solid #E6E1DA;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 640px) {
            .package-select-card {
                border-radius: 12px;
                padding: 14px;
            }
        }
        .package-select-card:hover {
            border-color: #4A6B5D;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px rgba(74, 107, 93, 0.12);
        }
        .package-select-card.active {
            border-color: #4A6B5D;
            background-color: #FAF9F6;
        }
        
        .active-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4A6B5D;
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            box-shadow: 0 2px 5px rgba(74, 107, 93, 0.2);
        }
        @media (min-width: 640px) {
            .active-badge {
                top: 14px;
                right: 14px;
                width: 22px;
                height: 22px;
                font-size: 10px;
            }
        }

        .addon-item-card {
            border: 1px solid #E6E1DA;
            border-radius: 6px;
            padding: 6px 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            background: white;
        }
        @media (min-width: 640px) {
            .addon-item-card {
                border-radius: 10px;
                padding: 10px 12px;
                gap: 10px;
            }
        }
        .addon-item-card:hover {
            border-color: #4A6B5D;
        }
        .addon-item-card.selected {
            border-color: #4A6B5D;
            background-color: #FAF9F6;
        }
        
        .addon-checkbox {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid #C6C1B9;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            background: white;
            color: transparent;
            font-size: 8px;
            flex-shrink: 0;
        }
        @media (min-width: 640px) {
            .addon-checkbox {
                width: 18px;
                height: 18px;
                border-radius: 6px;
                font-size: 9px;
            }
        }
        .addon-item-card.selected .addon-checkbox {
            background: #4A6B5D;
            border-color: #4A6B5D;
            color: white;
        }

        /* Invoice styling */
        .receipt-card {
            background: #FFFFFF;
            border: 1px solid #E6E1DA;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px -6px rgba(15, 23, 42, 0.03);
        }
        @media (min-width: 640px) {
            .receipt-card {
                border-radius: 14px;
            }
        }
        .receipt-header {
            background: #4A6B5D;
            color: #FAF7F2;
            padding: 8px 10px;
        }
        @media (min-width: 640px) {
            .receipt-header {
                padding: 12px 16px;
            }
        }
        .receipt-body {
            padding: 10px;
        }
        @media (min-width: 640px) {
            .receipt-body {
                padding: 16px;
            }
        }
        .receipt-divider {
            border-top: 1px dashed #E6E1DA;
            margin: 12px 0;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #5C6460;
            padding: 3px 0;
        }
        @media (min-width: 640px) {
            .receipt-row {
                font-size: 0.8rem;
                padding: 4px 0;
            }
        }
        .receipt-row.total {
            font-size: 0.72rem;
            font-weight: 700;
            color: #2D3330;
        }
        @media (min-width: 640px) {
            .receipt-row.total {
                font-size: 1.0rem;
            }
        }
        
        .alert-banner {
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 0.7rem;
            line-height: 1.3;
            display: flex;
            gap: 8px;
        }
        @media (min-width: 640px) {
            .alert-banner {
                border-radius: 16px;
                padding: 16px;
                font-size: 0.75rem;
                line-height: 1.4;
                gap: 12px;
            }
        }
        .alert-banner.success {
            background: rgba(74, 107, 93, 0.08);
            border: 1px solid rgba(74, 107, 93, 0.2);
            color: #2D3330;
        }
        .alert-banner.warning {
            background: rgba(197, 168, 128, 0.1);
            border: 1px solid rgba(197, 168, 128, 0.3);
            color: #2D3330;
        }
        .alert-banner.danger {
            background: rgba(220, 38, 38, 0.05);
            border: 1px solid rgba(220, 38, 38, 0.15);
            color: #2D3330;
        }
    </component>

    <AuthenticatedLayout
        :header-title="t('budget_planner')"
        :header-desc="t('budget_planner_desc')"
    >

        <div class="font-sans-modern">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                
                <!-- Gourmet Hero Banner Card -->
                <div class="bg-[#2D3330] text-[#FAF7F2] rounded-lg sm:rounded-2xl px-3 py-2.5 sm:p-6 md:p-8 border border-[#E6E1DA] shadow-sm flex flex-row md:flex-row justify-between items-center gap-3 md:gap-6 mb-3 sm:mb-8 overflow-hidden relative">
                    <!-- Oatmeal blur decorative circle -->
                    <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-white/5 blur-2xl"></div>
                    
                    <div class="space-y-1 sm:space-y-2.5 relative z-10 max-w-2xl">
                        <span class="text-[7px] sm:text-[10px] font-bold text-[#4A6B5D] bg-[#FAF9F6] border border-[#FAF9F6]/20 px-2 py-0.5 sm:px-3 sm:py-1 rounded-full uppercase tracking-widest inline-block select-none">{{ t('budget_planner') }}</span>
                        <h3 class="text-sm sm:text-xl md:text-3xl lg:text-4xl font-light font-serif-luxury tracking-wide">
                            {{ t('live_estimate') }}
                        </h3>
                        <p class="hidden sm:block text-xs text-[#E6E1DA]/80 font-light leading-relaxed">
                            {{ t('budget_planner_desc') }}
                        </p>
                    </div>
 
                    <!-- Right side illustration image thumbnail -->
                    <div class="hidden md:block w-36 h-36 rounded-2xl overflow-hidden shrink-0 border border-[#FAF7F2]/10 shadow-lg relative z-10">
                        <img src="/img/catering_dish.png" class="w-full h-full object-cover" alt="Gourmet dish" />
                    </div>
                </div>
 
                <!-- Custom Proposal Toggle Banner -->
                <div class="bg-[#FAF9F6] border border-[#E6E1DA] rounded-lg sm:rounded-2xl px-2.5 py-2 sm:p-4 flex flex-row justify-between items-center gap-3 mb-4 sm:mb-6">
                    <div class="space-y-0.5 sm:space-y-1 min-w-0">
                        <h4 class="font-serif-luxury text-xs sm:text-xl font-normal text-[#2D3330] leading-tight">
                            {{ t('prefer_custom_budget') }}
                        </h4>
                        <p class="hidden sm:block text-xs text-[#8C8275] font-light">
                            {{ t('prefer_custom_budget_desc') }}
                        </p>
                    </div>
                    <button 
                        type="button"
                        @click="showCustomForm = !showCustomForm"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg text-[9px] sm:text-xs uppercase tracking-wider font-semibold transition-colors cursor-pointer shrink-0 text-center whitespace-nowrap"
                    >
                        {{ showCustomForm ? t('back_to_budget_calc') : t('request_custom_proposal_btn') }}
                    </button>
                </div>

                <div v-if="!showCustomForm" class="grid lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left Column: Inputs & Selector (8 cols) -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- Step 1: Mode Switch and Input -->
                        <div class="simulator-container space-y-6">
                            <h4 class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-widest border-b border-[#E6E1DA] pb-3 flex items-center gap-2">
                                <span class="w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-[9px] sm:text-[10px]">1</span>
                                {{ t('step_planning_mode') }}
                            </h4>
                            
                            <div class="flex flex-wrap gap-3">
                                <button 
                                    type="button" 
                                    class="mode-tab flex items-center gap-2"
                                    :class="{ 'active': searchMode === 'guest' }"
                                    @click="searchMode = 'guest'"
                                >
                                    <i class="fas fa-users text-xs"></i> {{ t('calc_by_guest') }}
                                </button>
                                <button 
                                    type="button" 
                                    class="mode-tab flex items-center gap-2"
                                    :class="{ 'active': searchMode === 'budget' }"
                                    @click="searchMode = 'budget'"
                                >
                                    <i class="fas fa-wallet text-xs"></i> {{ t('calc_by_budget') }}
                                </button>
                            </div>

                            <div class="max-w-md">
                                <!-- Target Budget Input -->
                                <div v-if="searchMode === 'budget'" class="space-y-2 animate-fade-in">
                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                                        {{ t('enter_target_budget') }}
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-[#8C8275]">RM</span>
                                        <input 
                                            type="number" 
                                            v-model="targetBudget" 
                                            min="1"
                                            class="w-full pl-12 pr-4 py-3 border border-[#E6E1DA] rounded-xl focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2]/40 text-xs font-semibold"
                                        />
                                    </div>
                                </div>

                                <!-- Guest Count Input -->
                                <div v-if="searchMode === 'guest'" class="space-y-2 animate-fade-in">
                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                                        {{ t('enter_guest_count') }}
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#8C8275]"><i class="fas fa-users text-xs"></i></span>
                                        <input 
                                            type="number" 
                                            v-model="guestCount" 
                                            min="1"
                                            class="w-full pl-12 pr-4 py-3 border border-[#E6E1DA] rounded-xl focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2]/40 text-xs font-semibold"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Select Package -->
                        <div class="simulator-container space-y-6">
                            <h4 class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-widest border-b border-[#E6E1DA] pb-3 flex items-center gap-2">
                                <span class="w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-[9px] sm:text-[10px]">2</span>
                                {{ t('step_base_package') }}
                            </h4>

                            <div class="grid grid-cols-2 gap-2 sm:gap-4" :class="packages.length === 1 ? 'max-w-md w-full grid-cols-1' : ''">
                                <div 
                                    v-for="pkg in packages" 
                                    :key="pkg.id"
                                    class="package-select-card"
                                    :class="{ 'active': selectedPackageId === pkg.id }"
                                    @click="selectPackage(pkg.id)"
                                >
                                    <div v-if="selectedPackageId === pkg.id" class="active-badge">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <h5 class="text-[9px] sm:text-base font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wide mb-1 leading-tight">
                                        {{ pkg.package_name }}
                                    </h5>
                                    <div class="text-[9px] sm:text-xs text-[#4A6B5D] font-bold mb-1 sm:mb-2">
                                        RM {{ parseFloat(pkg.price).toFixed(2) }} / {{ t('pax') }}
                                    </div>
                                    <p class="text-[8px] sm:text-[10px] text-[#8C8275] uppercase tracking-wider mb-1.5 sm:mb-2">
                                        {{ t('min_requirement') }}: {{ pkg.min_order }} {{ t('pax') }}
                                    </p>
                                    <div class="text-[8.5px] sm:text-[11px] text-[#5C6460] font-light line-clamp-2 sm:line-clamp-3 border-t border-[#E6E1DA] pt-1.5 sm:pt-2.5 leading-relaxed">
                                        {{ pkg.description }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Add-on Extra Dishes -->
                        <div class="simulator-container space-y-6">
                            <h4 class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-widest border-b border-[#E6E1DA] pb-3 flex items-center gap-2">
                                <span class="w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-[9px] sm:text-[10px]">3</span>
                                {{ t('step_addons') }}
                            </h4>

                            <div v-if="selectedPackage && selectedPackage.addons && selectedPackage.addons.length > 0">
                                <p class="text-[9px] sm:text-[10px] text-[#8C8275] uppercase tracking-wider mb-4">{{ t('addons_desc') }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div 
                                        v-for="addon in selectedPackage.addons" 
                                        :key="addon.id"
                                        class="addon-item-card"
                                        :class="{ 'selected': selectedAddonIds.includes(addon.id) }"
                                        @click="toggleAddon(addon.id)"
                                    >
                                        <div class="addon-checkbox">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="flex-grow">
                                            <span class="font-bold text-[11px] sm:text-xs text-[#2D3330] uppercase block">{{ addon.addon_name }}</span>
                                            <span class="text-[10px] sm:text-xs text-[#4A6B5D] font-semibold">+RM {{ parseFloat(addon.price_per_pax).toFixed(2) }} / {{ t('pax') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-6 border border-dashed border-[#E6E1DA] rounded-2xl text-center text-[#8C8275] text-xs font-light">
                                <i class="fas fa-info-circle mr-1.5 text-xs text-[#C5A880]"></i>
                                {{ t('no_addons_for_package') }}
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Visual Invoice & Guidelines (4 cols) -->
                    <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-6">
                        
                        <!-- Visual Invoice Simulator -->
                        <div class="receipt-card">
                            <div class="receipt-header">
                                <span class="text-[9px] font-bold uppercase tracking-widest block opacity-80">{{ t('live_estimate') }}</span>
                                <h4 class="text-sm sm:text-lg font-normal font-serif-luxury uppercase tracking-wide mt-1 leading-tight">
                                    {{ selectedPackage ? selectedPackage.package_name : '-' }}
                                </h4>
                            </div>

                            <div class="receipt-body space-y-4 font-sans-modern">
                                <!-- Price per pax info -->
                                <div class="receipt-row">
                                    <span>{{ t('base_pkg_price') }}</span>
                                    <span>RM {{ selectedPackage ? parseFloat(selectedPackage.price).toFixed(2) : '0.00' }} / pax</span>
                                </div>

                                <div v-if="selectedAddonIds.length > 0" class="receipt-row text-[#4A6B5D] font-semibold">
                                    <span>{{ t('addon_extra') }}</span>
                                    <span>+RM {{ (totalCostPerPax - (selectedPackage ? parseFloat(selectedPackage.price) : 0)).toFixed(2) }} / pax</span>
                                </div>

                                <div class="receipt-row">
                                    <span>{{ t('combined_cost_pax') }}</span>
                                    <span>RM {{ totalCostPerPax.toFixed(2) }} / pax</span>
                                </div>

                                <div class="receipt-divider"></div>

                                <!-- Guests Pax count -->
                                <div class="receipt-row">
                                    <span>{{ t('pax_recommended') }}</span>
                                    <span class="font-bold text-xs text-[#2D3330]">{{ finalPaxCount }} pax</span>
                                </div>

                                <!-- Base package subtotal -->
                                <div class="receipt-row">
                                    <span>{{ t('base_cost') }}</span>
                                    <span>RM {{ baseCostTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>

                                <!-- Add-ons subtotal -->
                                <div v-if="addonsCostTotal > 0" class="receipt-row">
                                    <span>{{ t('selected_extra_items') }}</span>
                                    <span>RM {{ addonsCostTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>

                                <div class="receipt-divider"></div>

                                <!-- Grand Total -->
                                <div class="receipt-row total">
                                    <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-wider">{{ t('grand_total') }}</span>
                                    <span class="font-serif-luxury text-xs sm:text-2xl font-light">
                                        RM {{ grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                    </span>
                                </div>

                                <!-- Deposit breakdown -->
                                <div class="receipt-row text-[#8C3A3A] font-semibold">
                                    <span>{{ t('deposit_required') }}</span>
                                    <span>RM {{ depositAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>

                                <div class="receipt-row text-[#8C8275]">
                                    <span>{{ t('balance_due') }}</span>
                                    <span>RM {{ balanceAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>

                                <!-- Warning indicators for Budget Mode -->
                                <div v-if="searchMode === 'budget'" class="pt-3">
                                    <div v-if="isBudgetInsufficient" class="alert-banner danger">
                                        <i class="fas fa-exclamation-circle text-red-600 mt-0.5 text-sm"></i>
                                        <div>
                                            <span class="font-bold block">{{ t('insufficient_budget') }}</span>
                                            <span class="text-[#8C8275] text-[10px] block mt-0.5">{{ t('insufficient_budget_desc') }}</span>
                                        </div>
                                    </div>
                                    <div v-else-if="budgetDifference < 0" class="alert-banner warning">
                                        <i class="fas fa-exclamation-triangle text-[#C5A880] mt-0.5 text-sm"></i>
                                        <div>
                                            <span class="font-bold block">{{ t('budget_exceeded') }}</span>
                                            <span class="font-bold text-sm block mt-1 text-[#8C3A3A]">
                                                RM {{ Math.abs(budgetDifference).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else class="alert-banner success">
                                        <i class="fas fa-check-circle text-[#4A6B5D] mt-0.5 text-sm"></i>
                                        <div>
                                            <span class="font-bold block">{{ t('within_budget') }}</span>
                                            <span class="text-[10px] block mt-0.5">
                                                {{ t('leftover_budget') }}: <strong>RM {{ budgetDifference.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Warning for Guest count below minimum -->
                                <div v-if="searchMode === 'guest' && isGuestCountBelowMin" class="pt-3">
                                    <div class="alert-banner warning">
                                        <i class="fas fa-info-circle text-[#C5A880] mt-0.5 text-sm"></i>
                                        <div>
                                            <span class="font-bold block">{{ t('auto_adjusted_min') }}</span>
                                            <span class="text-[#8C8275] text-[10px] block mt-0.5">
                                                Your guest count is below the minimum order of {{ selectedPackage?.min_order }} pax.
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <Link 
                                        v-if="!isBudgetInsufficient && finalPaxCount > 0"
                                        :href="bookRedirectUrl"
                                        class="w-full inline-flex items-center justify-center gap-1.5 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-2.5 px-4 rounded-lg text-[10px] sm:text-xs uppercase tracking-widest transition-colors shadow-sm text-center cursor-pointer"
                                    >
                                        {{ t('book_customize_plan') }} <i class="fas fa-arrow-right text-[10px]"></i>
                                    </Link>
                                    <button 
                                        v-else
                                        disabled
                                        class="w-full inline-flex items-center justify-center gap-1.5 bg-[#E6E1DA] text-[#8C8275] font-semibold py-2.5 px-4 rounded-lg text-[10px] sm:text-xs uppercase tracking-widest cursor-not-allowed text-center"
                                    >
                                        {{ t('book_customize_plan') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Catering Guidelines Card -->
                        <div class="simulator-container space-y-4">
                            <h4 class="text-[10px] sm:text-xs font-bold text-[#2D3330] uppercase tracking-wider flex items-center gap-2">
                                <i class="far fa-compass text-[#4A6B5D] text-[10px] sm:text-sm"></i> {{ t('catering_guidelines') }}
                            </h4>
                            
                            <div class="space-y-3 text-[10px] sm:text-xs">
                                <div>
                                    <span class="font-bold text-[#2D3330] block mb-0.5">{{ t('guide_pax_title') }}</span>
                                    <p class="text-[#8C8275] font-light leading-relaxed">{{ t('guide_pax_desc') }}</p>
                                </div>
                                <div class="border-t border-[#FAF6F0] pt-3">
                                    <span class="font-bold text-[#2D3330] block mb-0.5">{{ t('guide_halal_title') }}</span>
                                    <p class="text-[#8C8275] font-light leading-relaxed">{{ t('guide_halal_desc') }}</p>
                                </div>
                                <div class="border-t border-[#FAF6F0] pt-3">
                                    <span class="font-bold text-[#2D3330] block mb-0.5">{{ t('guide_changes_title') }}</span>
                                    <p class="text-[#8C8275] font-light leading-relaxed">{{ t('guide_changes_desc') }}</p>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Custom Menu Request Form -->
                <div v-else class="simulator-container space-y-8 animate-fade-in mb-10">
                    <div>
                        <!-- Back Link -->
                        <div class="mb-4">
                            <button 
                                type="button" 
                                @click="showCustomForm = false"
                                class="inline-flex items-center gap-2 text-xs text-[#8C8275] hover:text-[#4A6B5D] font-medium transition-colors cursor-pointer"
                            >
                                <i class="fas fa-arrow-left text-[10px]"></i> 
                                {{ t('back_to_budget_calc') }}
                            </button>
                        </div>

                        <h3 class="font-serif-luxury text-xs sm:text-2xl text-[#2D3330] font-normal uppercase tracking-wide">
                            {{ t('request_custom_proposal_title') }}
                        </h3>
                        <p class="hidden sm:block text-xs text-[#8C8275] font-light mt-1">
                            {{ t('request_custom_proposal_subtitle') }}
                        </p>
                    </div>

                    <form @submit.prevent="submitCustomProposal" class="space-y-6">
                        <!-- 2 Column Grid for Form Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Target Budget -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                                    {{ t('target_budget_label') }} *
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-[#8C8275]">RM</span>
                                    <input 
                                        type="number" 
                                        v-model="customForm.budget" 
                                        min="100"
                                        placeholder="e.g. 3000"
                                        class="w-full pl-12 pr-4 py-3 border border-[#E6E1DA] rounded-xl focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF9F6]/40 text-xs font-semibold text-[#2D3330]"
                                        required
                                    />
                                </div>
                                <p v-if="formErrors.budget" class="text-xs text-red-600 font-semibold">{{ formErrors.budget }}</p>
                            </div>

                            <!-- Guest Count -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                                    {{ t('guest_count_label') }} *
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#8C8275]"><i class="fas fa-users text-xs"></i></span>
                                    <input 
                                        type="number" 
                                        v-model="customForm.guest_count" 
                                        min="20"
                                        placeholder="e.g. 150"
                                        class="w-full pl-12 pr-4 py-3 border border-[#E6E1DA] rounded-xl focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF9F6]/40 text-xs font-semibold text-[#2D3330]"
                                        required
                                    />
                                </div>
                                <p v-if="formErrors.guest_count" class="text-xs text-red-600 font-semibold">{{ formErrors.guest_count }}</p>
                            </div>

                            <!-- Delivery Date -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                                    {{ t('event_date_label') }} *
                                </label>
                                
                                <div class="relative">
                                    <!-- Click-Outside Overlay -->
                                    <div v-if="showCalendar" class="fixed inset-0 z-40" @click="showCalendar = false"></div>

                                    <!-- Custom Trigger Button (Looks like an input field) -->
                                    <button 
                                        type="button"
                                        @click="showCalendar = !showCalendar"
                                        class="w-full px-4 py-3 border border-[#E6E1DA] rounded-xl focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF9F6]/40 text-xs font-semibold text-[#2D3330] text-left flex justify-between items-center cursor-pointer h-11 relative z-10"
                                    >
                                        <span :class="customForm.delivery_date ? 'text-[#2D3330]' : 'text-gray-400'">
                                            {{ formattedSelectedDate || t('select_date') }}
                                        </span>
                                        <i class="fas fa-calendar-alt text-[#8C8275]"></i>
                                    </button>

                                    <!-- Custom Calendar Dropdown Panel -->
                                    <div 
                                        v-if="showCalendar" 
                                        class="absolute left-0 mt-2 p-4 bg-white border border-[#E6E1DA] rounded-2xl shadow-xl z-50 w-72 space-y-4 font-sans-modern"
                                    >
                                        <!-- Header: Prev, Month/Year, Next -->
                                        <div class="flex justify-between items-center">
                                            <button type="button" @click="prevMonth" class="w-8 h-8 rounded-lg hover:bg-[#FAF7F2] border border-[#E6E1DA] flex items-center justify-center text-xs text-[#8C8275] cursor-pointer">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                            <span class="text-xs font-bold text-[#2D3330] font-sans-modern">
                                                {{ monthNames[calendarMonth] }} {{ calendarYear }}
                                            </span>
                                            <button type="button" @click="nextMonth" class="w-8 h-8 rounded-lg hover:bg-[#FAF7F2] border border-[#E6E1DA] flex items-center justify-center text-xs text-[#8C8275] cursor-pointer">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>

                                        <!-- Weekdays -->
                                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-[#8C8275]">
                                            <span v-for="day in weekdays" :key="day">{{ day }}</span>
                                        </div>

                                        <!-- Days Grid -->
                                        <div class="grid grid-cols-7 gap-1">
                                            <button
                                                v-for="(day, index) in calendarDays"
                                                :key="index"
                                                type="button"
                                                @click="selectDate(day)"
                                                :disabled="day.isDisabled"
                                                class="h-8 w-8 rounded-lg text-xs font-semibold flex items-center justify-center transition-all cursor-pointer relative"
                                                :class="[
                                                    !day.isCurrentMonth ? 'text-gray-300 pointer-events-none' : '',
                                                    day.isCurrentMonth && !day.isDisabled && !day.isSelected ? 'text-[#2D3330] hover:bg-[#FAF7F2] hover:text-[#4A6B5D]' : '',
                                                    day.isBlocked ? 'bg-rose-50 text-rose-500 border border-rose-200 cursor-not-allowed hover:bg-rose-50 hover:text-rose-500' : '',
                                                    day.isSelected ? 'bg-[#4A6B5D] text-white' : '',
                                                    day.isCurrentMonth && day.isDisabled && !day.isBlocked ? 'text-gray-300 cursor-not-allowed' : '',
                                                ]"
                                            >
                                                {{ day.dayNumber }}
                                                <span v-if="day.isBlocked" class="absolute bottom-1 w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            </button>
                                        </div>

                                        <!-- Legend -->
                                        <div class="flex items-center justify-center gap-4 border-t border-[#EBEFEF] pt-2.5 text-[9px] font-semibold text-[#8C8275] uppercase tracking-wider">
                                            <div class="flex items-center gap-1">
                                                <span class="w-2.5 h-2.5 rounded bg-rose-50 border border-rose-200 block"></span>
                                                <span>{{ t('legend_full') }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="w-2.5 h-2.5 rounded bg-[#4A6B5D] block"></span>
                                                <span>{{ t('legend_selected') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="formErrors.delivery_date" class="text-xs text-red-600 font-semibold">{{ formErrors.delivery_date }}</p>
                                <p class="text-[10px] text-[#8C8275] mt-1">{{ t('book_advance_notice') }}</p>
                            </div>

                            <!-- Delivery Time -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                                    {{ t('delivery_time_label') }} *
                                </label>
                                <input 
                                    type="time" 
                                    v-model="customForm.delivery_time" 
                                    class="w-full px-4 py-3 border border-[#E6E1DA] rounded-xl focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF9F6]/40 text-xs font-semibold text-[#2D3330]"
                                    required
                                />
                                <p v-if="formErrors.delivery_time" class="text-xs text-red-600 font-semibold">{{ formErrors.delivery_time }}</p>
                            </div>
                            
                            <!-- Delivery Address -->
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                                    {{ t('delivery_venue_label') }} *
                                </label>
                                <textarea 
                                    v-model="customForm.address"
                                    rows="3"
                                    placeholder="Provide full details of the venue..."
                                    class="w-full px-4 py-3 border border-[#E6E1DA] rounded-xl focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF9F6]/40 text-xs font-semibold text-[#2D3330]"
                                    required
                                ></textarea>
                                <p v-if="formErrors.address" class="text-xs text-red-600 font-semibold">{{ formErrors.address }}</p>
                            </div>

                            <!-- Special Notes -->
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">
                                    {{ t('additional_notes_label') }}
                                </label>
                                <textarea 
                                    v-model="customForm.notes"
                                    rows="3"
                                    placeholder="E.g., allergies, special service requests, tent rental needs..."
                                    class="w-full px-4 py-3 border border-[#E6E1DA] rounded-xl focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF9F6]/40 text-xs font-semibold text-[#2D3330]"
                                ></textarea>
                                <p v-if="formErrors.notes" class="text-xs text-red-600 font-semibold">{{ formErrors.notes }}</p>
                            </div>

                        </div>

                        <!-- Wishlist Dishes Categories -->
                        <div class="space-y-4 border-t border-[#E6E1DA] pt-6">
                            <div>
                                <h4 class="font-serif-luxury text-sm sm:text-xl text-[#2D3330] font-normal uppercase tracking-wide">
                                    {{ t('select_wishlist_dishes_label') }} *
                                </h4>
                                <p class="text-xs text-[#8C8275] font-light mt-0.5">
                                    {{ t('select_wishlist_dishes_desc') }}
                                </p>
                                <p v-if="formErrors.dishes" class="text-xs text-red-600 font-semibold mt-1">{{ formErrors.dishes }}</p>
                            </div>

                            <div class="space-y-6">
                                <div v-for="(dishesList, category) in dishesByCategory" :key="category" class="space-y-2.5">
                                    <h5 class="text-xs font-bold text-[#4A6B5D] uppercase tracking-wider bg-[#FAF9F6] border border-[#E6E1DA] px-3 py-1.5 rounded-lg inline-block">
                                        {{ category }}
                                    </h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                        <label 
                                            v-for="dish in dishesList" 
                                            :key="dish.id"
                                            class="addon-item-card cursor-pointer"
                                            :class="{ 'selected': customForm.dishes.includes(dish.id) }"
                                        >
                                            <input 
                                                type="checkbox" 
                                                :value="dish.id" 
                                                v-model="customForm.dishes"
                                                class="hidden"
                                            />
                                            <div class="addon-checkbox">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>
                                                <span class="font-semibold text-xs text-[#2D3330] uppercase block">{{ dish.name }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Submit Panel -->
                        <div class="border-t border-[#E6E1DA] pt-4 sm:pt-6 grid grid-cols-2 sm:flex sm:justify-end gap-2 sm:gap-3">
                            <button 
                                type="button"
                                @click="showCustomForm = false"
                                class="px-3 sm:px-6 py-2 sm:py-3 border border-[#E6E1DA] text-[#8C8275] rounded-lg sm:rounded-xl text-[9px] sm:text-xs uppercase tracking-wide sm:tracking-widest font-semibold hover:bg-[#FAF9F6] transition-colors cursor-pointer"
                            >
                                {{ t('cancel') }}
                            </button>
                            <button 
                                type="submit"
                                :disabled="isSubmitting"
                                class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:bg-[#E6E1DA] text-white px-3 sm:px-6 py-2 sm:py-3 rounded-lg sm:rounded-xl text-[9px] sm:text-xs uppercase tracking-wide sm:tracking-widest font-semibold transition-colors cursor-pointer flex items-center justify-center gap-1 sm:gap-2"
                            >
                                <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin mr-1"></i> {{ t('submitting_status') }}</span>
                                <span v-else>{{ t('submit_proposal_request_btn') }}</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
