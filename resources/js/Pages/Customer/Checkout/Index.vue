<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';
import { useToast } from '@/Composables/useToast';
import axios from 'axios';

const { toast } = useToast();

const props = defineProps({
    cartItems: {
        type: Array,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
    qrCodeFile: {
        type: String,
        default: 'admin/uploads/qr_default.png',
    },
    userData: {
        type: Object,
        required: true,
    },
    blockedDates: {
        type: Array,
        default: () => [],
    },
    activePromos: {
        type: Array,
        default: () => [],
    },
    deliveryZones: {
        type: Array,
        required: true,
    },
});

const { t, currentLanguage } = useLocalization();
const page = usePage();

const depositPercent = computed(() => {
    return parseFloat(page.props.settings?.deposit_percentage || 30);
});

// Promo code states
const promoCode = ref('');
const appliedPromo = ref(null);
const promoMessage = ref('');
const showPromoModal = ref(false);
const showQRModal = ref(false);

const discountAmount = computed(() => {
    return appliedPromo.value ? parseFloat(appliedPromo.value.discount_amount) : 0.00;
});

// Zones list derived dynamically from props.deliveryZones
const zones = computed(() => {
    const list = [
        { value: 'Self-Pickup', label: 'Ambil Sendiri (Self-Pickup @ Gong Badak) - RM 0.00', fee: 0.00 }
    ];
    props.deliveryZones.forEach(zone => {
        list.push({
            value: zone.name,
            label: `${zone.name} - RM ${parseFloat(zone.fee).toFixed(2)}`,
            fee: parseFloat(zone.fee)
        });
    });
    return list;
});

const deliveryZones = computed(() => {
    return zones.value.filter(z => z.value !== 'Self-Pickup');
});

const deliveryFee = computed(() => {
    const found = zones.value.find(z => z.value === form.delivery_zone);
    return found ? found.fee : 0.00;
});

// Calculate grand total after promo discount & delivery fee
const grandTotal = computed(() => {
    const baseTotal = props.cartItems.reduce((sum, item) => sum + parseFloat(item.price) * parseInt(item.quantity), 0.00);
    return Math.max(baseTotal + deliveryFee.value - discountAmount.value, 0.00);
});

// deposit calculation
const depositAmount = computed(() => {
    return grandTotal.value * (depositPercent.value / 100);
});

// balance calculation
const balanceAmount = computed(() => {
    return grandTotal.value * ((100 - depositPercent.value) / 100);
});

// Form state
const form = useForm({
    name: props.userData.full_name || '',
    phone: props.userData.phone || '',
    address: props.userData.address || '',
    delivery_zone: props.deliveryZones.length > 0 ? props.deliveryZones[0].name : '',
    delivery_date: '',
    delivery_time: '',
    receipt: null,
    promo_code_id: null,
    notes: '',
});

// Checkout Method (Delivery / Pickup)
const checkoutMethod = ref('delivery');
const tempAddress = ref(props.userData.address || '');

function setCheckoutMethod(method) {
    checkoutMethod.value = method;
    if (method === 'pickup') {
        if (form.address !== 'Self-Pickup @ Gong Badak') {
            tempAddress.value = form.address;
        }
        form.delivery_zone = 'Self-Pickup';
        form.address = 'Self-Pickup @ Gong Badak';
    } else {
        form.delivery_zone = props.deliveryZones.length > 0 ? props.deliveryZones[0].name : '';
        form.address = tempAddress.value || '';
    }
}

// Error handling helper
const fileError = ref('');

// Date limit helper (min 7 days from today)
const minDateString = computed(() => {
    const date = new Date();
    date.setDate(date.getDate() + 7);
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    const dateObj = new Date(year, month, day);
    return dateObj.toLocaleDateString(currentLanguage.value === 'en' ? 'en-US' : 'ms-MY', { day: 'numeric', month: 'short', year: 'numeric' });
}

const showCalendar = ref(false);
const todayDate = new Date();
const calendarYear = ref(todayDate.getFullYear());
const calendarMonth = ref(todayDate.getMonth());

const monthNames = computed(() => {
    return currentLanguage.value === 'en'
        ? ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
        : ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];
});

const weekdays = computed(() => {
    return currentLanguage.value === 'en'
        ? ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']
        : ['Ah', 'Is', 'Se', 'Ra', 'Kh', 'Ju', 'Sa'];
});

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
        const isSelected = form.delivery_date === dateStr;

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
    form.delivery_date = day.dateStr;
    showCalendar.value = false;
}

const formattedSelectedDate = computed(() => {
    return formatDate(form.delivery_date);
});

onMounted(() => {
    if (form.delivery_date) {
        const parts = form.delivery_date.split('-');
        if (parts.length === 3) {
            calendarYear.value = parseInt(parts[0], 10);
            calendarMonth.value = parseInt(parts[1], 10) - 1;
        }
    }
});

async function verifyPromo() {
    promoMessage.value = '';
    const baseTotal = props.cartItems.reduce((sum, item) => sum + parseFloat(item.price) * parseInt(item.quantity), 0.00);

    try {
        const response = await axios.post(route('checkout.apply-promo'), {
            code: promoCode.value,
            subtotal: baseTotal
        });

        if (response.data.valid) {
            appliedPromo.value = response.data;
            form.promo_code_id = response.data.promo_code_id;
            promoMessage.value = response.data.message;
        } else {
            promoMessage.value = response.data.message;
            appliedPromo.value = null;
            form.promo_code_id = null;
        }
    } catch (error) {
        promoMessage.value = 'Failed to apply promo code. Please try again.';
        appliedPromo.value = null;
        form.promo_code_id = null;
    }
}

function removePromo() {
    appliedPromo.value = null;
    form.promo_code_id = null;
    promoCode.value = '';
    promoMessage.value = '';
}

function handleFileChange(event) {
    const file = event.target.files[0];
    fileError.value = '';
    if (file) {
        if (file.size > 4 * 1024 * 1024) {
            fileError.value = t('file_size_error') || 'File size must be less than 4MB.';
            form.receipt = null;
            return;
        }
        form.receipt = file;
    }
}

function submitCheckout() {
    form.post(route('checkout.place'), {
        forceFormData: true,
        onSuccess: () => {
            toast(t('toast_order_submitted') || 'Your order request has been submitted successfully.');
        },
        onError: (errors) => {
            if (errors.receipt) {
                fileError.value = errors.receipt;
            }
        }
    });
}
const copySuccess = ref(false);
function copyAccountNumber() {
    const accNo = page.props.settings?.bank_account_no || '563064123456';
    navigator.clipboard.writeText(accNo);
    copySuccess.value = true;
    setTimeout(() => {
        copySuccess.value = false;
    }, 2000);
}
</script>

<template>
    <Head :title="t('secure_checkout')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .checkout-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 30px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 25px -4px rgba(15, 23, 42, 0.02);
        }
        .form-input {
            width: 100%;
            border-radius: 12px;
            border: 1px solid #E6E1DA;
            padding: 12px 16px;
            font-size: 0.85rem;
            color: #2D3330;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #FCFAF7;
        }
        .form-input:focus {
            border-color: #4A6B5D;
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(74, 107, 93, 0.08);
        }
        .btn-premium-primary {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-premium-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.15),
                transparent
            );
            transition: all 0.6s ease;
        }
        .btn-premium-primary:hover::before {
            left: 100%;
        }
        .btn-premium-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(74, 107, 93, 0.35);
        }
        .file-upload-area {
            border: 2px dashed #E6E1DA;
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: #FCFAF7;
        }
        .file-upload-area:hover {
            border-color: #4A6B5D;
            background: rgba(74, 107, 93, 0.02);
        }
        .file-upload-area.has-file {
            border-color: #4A6B5D;
            background: #FAFBFB;
        }
        .qr-card {
            background: #2D3330;
            border: 1px solid #C5A880;
            border-radius: 20px;
            color: #FAF7F2;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
        }
        .price-summary-box {
            background: #FAF6F0;
            border: 1px solid #E6E1DA;
            border-radius: 16px;
            padding: 20px;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            padding: 8px 0;
            border-bottom: 1px solid #EBEFEF;
            color: #5C6460;
        }
        .price-row:last-child {
            border: none;
        }
    </component>

    <AuthenticatedLayout
        header-title=""
        header-desc=""
    >
        <div class="font-sans-modern">
            <div class="max-w-6xl mx-auto px-6">
                
                <!-- Luxury Cover Banner -->
                <div class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-r from-[#2D3330] via-[#3A4540] to-[#4A6B5D] p-8 md:p-10 text-white border border-[#E6E1DA]/10 shadow-lg relative">
                    <!-- Decor blurs -->
                    <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-[#C5A880]/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#C5A880]/20 border border-[#C5A880]/30 rounded-full text-[10px] font-bold text-[#E6CBA3] uppercase tracking-widest">
                                <i class="fas fa-shield-alt"></i> {{ t('secure_checkout') || 'Pembayaran Selamat' }}
                            </div>
                            <h1 class="text-3xl md:text-4xl font-normal font-serif-luxury tracking-wide uppercase leading-tight">
                                {{ t('secure_checkout') || 'Selesaikan Tempahan' }}
                            </h1>
                            <p class="text-xs md:text-sm text-[#E6E1DA]/80 max-w-2xl font-light leading-relaxed">
                                {{ t('checkout_desc_banner') || 'Sahkan butiran majlis anda, muat naik resit bayaran deposit (30%), dan hantar tempahan untuk pengesahan pihak kami.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left: Details & Payment (8 cols) -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- Event Details Form -->
                        <form @submit.prevent="submitCheckout" class="space-y-6">
                            
                            <div class="checkout-card space-y-6">
                                <h3 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#EBEFEF] pb-3 flex items-center gap-2">
                                    <i class="fas fa-calendar-check text-[#4A6B5D] text-sm"></i> {{ t('event_delivery_details') }}
                                </h3>

                                <!-- Delivery/Pickup Segmented Control -->
                                <div class="flex bg-[#FCFAF7] border border-[#E6E1DA] rounded-xl p-1 font-sans-modern">
                                    <button 
                                        type="button"
                                        @click="setCheckoutMethod('delivery')"
                                        class="flex-grow flex-1 py-3 text-xs font-bold uppercase tracking-wider rounded-lg transition-all cursor-pointer flex items-center justify-center gap-2"
                                        :class="checkoutMethod === 'delivery' ? 'bg-[#4A6B5D] text-white shadow-xs' : 'text-[#8C8275] hover:text-[#2D3330]'"
                                    >
                                        <i class="fas fa-truck text-[10px]"></i> {{ t('delivery_tab') || 'Penghantaran' }}
                                    </button>
                                    <button 
                                        type="button"
                                        @click="setCheckoutMethod('pickup')"
                                        class="flex-grow flex-1 py-3 text-xs font-bold uppercase tracking-wider rounded-lg transition-all cursor-pointer flex items-center justify-center gap-2"
                                        :class="checkoutMethod === 'pickup' ? 'bg-[#4A6B5D] text-white shadow-xs' : 'text-[#8C8275] hover:text-[#2D3330]'"
                                    >
                                        <i class="fas fa-store text-[10px]"></i> Ambil Sendiri (Pickup)
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Full Name -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                            <i class="fas fa-user text-[10px] text-[#4A6B5D]"></i>
                                            {{ t('customer_name') }}
                                        </label>
                                        <input 
                                            type="text" 
                                            v-model="form.name" 
                                            class="form-input"
                                            placeholder="Your full name"
                                            required
                                        />
                                        <span v-if="form.errors.name" class="text-xs text-red-500 font-semibold">{{ form.errors.name }}</span>
                                    </div>

                                    <!-- Phone Number -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                            <i class="fas fa-phone text-[10px] text-[#4A6B5D]"></i>
                                            {{ t('phone_number') }}
                                        </label>
                                        <input 
                                            type="text" 
                                            v-model="form.phone" 
                                            class="form-input"
                                            placeholder="e.g. 0123456789"
                                            required
                                        />
                                        <span v-if="form.errors.phone" class="text-xs text-red-500 font-semibold">{{ form.errors.phone }}</span>
                                    </div>

                                    <!-- Delivery Date -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                            <i class="fas fa-calendar-alt text-[10px] text-[#4A6B5D]"></i>
                                            {{ t('delivery_event_date') }}
                                        </label>
                                        
                                        <div class="relative">
                                            <!-- Click-Outside Overlay -->
                                            <div v-if="showCalendar" class="fixed inset-0 z-40" @click="showCalendar = false"></div>

                                            <!-- Custom Trigger Button (Looks like an input field) -->
                                            <button 
                                                type="button"
                                                @click="showCalendar = !showCalendar"
                                                class="form-input text-left flex justify-between items-center cursor-pointer h-11 relative z-10 w-full"
                                            >
                                                <span :class="form.delivery_date ? 'text-[#2D3330]' : 'text-gray-400'">
                                                    {{ formattedSelectedDate || (currentLanguage === 'en' ? 'Select Date' : 'Pilih Tarikh') }}
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
                                                        <span>{{ currentLanguage === 'en' ? 'Full' : 'Penuh' }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        <span class="w-2.5 h-2.5 rounded bg-[#4A6B5D] block"></span>
                                                        <span>{{ currentLanguage === 'en' ? 'Selected' : 'Dipilih' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <span class="text-[9px] text-[#8C8275] font-semibold uppercase tracking-wider block mt-1">
                                            <i class="fas fa-info-circle"></i> Batal 7 hari sebelum (Min 7 Days).
                                        </span>
                                        <span v-if="form.errors.delivery_date" class="text-xs text-red-500 font-semibold">{{ form.errors.delivery_date }}</span>
                                    </div>

                                    <!-- Delivery Time -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                            <i class="fas fa-clock text-[10px] text-[#4A6B5D]"></i>
                                            {{ checkoutMethod === 'pickup' ? (t('pickup_time') || 'Masa Pengambilan') : t('preferred_delivery_time') }}
                                        </label>
                                        <input 
                                            type="time" 
                                            v-model="form.delivery_time" 
                                            class="form-input"
                                            required
                                        />
                                        <span v-if="form.errors.delivery_time" class="text-xs text-red-500 font-semibold">{{ form.errors.delivery_time }}</span>
                                    </div>
                                </div>

                                <!-- Delivery Zone -->
                                <div v-if="checkoutMethod === 'delivery'" class="space-y-2">
                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                        <i class="fas fa-truck text-[10px] text-[#4A6B5D]"></i>
                                        Kawasan Penghantaran
                                    </label>
                                    <select 
                                        v-model="form.delivery_zone" 
                                        class="form-input cursor-pointer"
                                        required
                                    >
                                        <option v-for="zone in deliveryZones" :key="zone.value" :value="zone.value">
                                            {{ zone.label }}
                                        </option>
                                    </select>
                                    <span v-if="form.errors.delivery_zone" class="text-xs text-red-500 font-semibold block">{{ form.errors.delivery_zone }}</span>
                                </div>

                                <!-- Delivery Address -->
                                <div v-if="checkoutMethod === 'delivery'" class="space-y-2">
                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                        <i class="fas fa-map-marker-alt text-[10px] text-[#4A6B5D]"></i>
                                        {{ t('event_venue_address') }}
                                    </label>
                                    <textarea 
                                        v-model="form.address" 
                                        rows="3" 
                                        class="form-input"
                                        placeholder="Enter the complete address for catering delivery"
                                        required
                                    ></textarea>
                                    <span v-if="form.errors.address" class="text-xs text-red-500 font-semibold">{{ form.errors.address }}</span>
                                </div>

                                <!-- Pickup Location Info Card -->
                                <div v-if="checkoutMethod === 'pickup'" class="p-5 bg-[#FAF6F0] border border-[#E6E1DA] rounded-2xl space-y-2.5 font-sans-modern">
                                    <span class="font-bold text-[#4A6B5D] text-xs uppercase tracking-widest block flex items-center gap-1.5">
                                        <i class="fas fa-map-marked-alt text-xs"></i> Lokasi Pengambilan (Pickup Location):
                                    </span>
                                    <p class="text-xs text-[#2D3330] font-semibold leading-relaxed">
                                        {{ page.props.settings?.business_address || 'SmartServe Catering, Gong Badak, Kuala Nerus, Terengganu, Malaysia' }}
                                    </p>
                                    
                                    <!-- Interactive Map -->
                                    <iframe 
                                        class="w-full h-48 rounded-xl border border-[#E6E1DA] shadow-inner mt-2"
                                        :src="'https://maps.google.com/maps?q=' + encodeURIComponent(page.props.settings?.business_address || 'SmartServe Catering, Gong Badak, Kuala Terengganu, Terengganu') + '&t=&z=15&ie=UTF8&iwloc=&output=embed'"
                                        allowfullscreen="" 
                                        loading="lazy"
                                    ></iframe>
                                    
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider block font-semibold pt-1">
                                        <i class="fas fa-info-circle text-[#C5A880]"></i> Sila ambil tempahan anda mengikut masa persediaan/penghantaran yang ditetapkan. Caj penghantaran adalah percuma (RM 0.00).
                                    </span>
                                </div>

                                <!-- Customer Notes -->
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1.5">
                                        <i class="fas fa-sticky-note text-[10px] text-[#4A6B5D]"></i>
                                        {{ t('customer_notes') }}
                                    </label>
                                    <textarea 
                                        v-model="form.notes" 
                                        rows="3" 
                                        class="form-input"
                                        :placeholder="t('customer_notes_placeholder')"
                                    ></textarea>
                                    <span v-if="form.errors.notes" class="text-xs text-red-500 font-semibold">{{ form.errors.notes }}</span>
                                </div>
                            </div>

                            <!-- Payment Instructions & Receipt Upload -->
                            <div class="checkout-card space-y-6">
                                <h3 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#EBEFEF] pb-3 flex items-center gap-2">
                                    <i class="fas fa-receipt text-[#4A6B5D] text-sm"></i> {{ t('payment_slip_deposit') }}
                                </h3>

                                <div class="p-5 bg-rose-50 border border-rose-100 rounded-2xl space-y-2.5">
                                    <span class="font-bold text-[#8C3A3A] text-xs uppercase tracking-widest block flex items-center gap-1.5">
                                        <i class="fas fa-exclamation-circle text-xs"></i> Deposit Diperlukan ({{ depositPercent }}%):
                                    </span>
                                    <p class="text-xs text-[#5C6460] leading-relaxed font-medium">
                                        Pihak katering memerlukan bayaran deposit sebanyak {{ depositPercent }}% untuk mengesahkan tarikh tempahan majlis anda. 
                                        Sila buat pembayaran sebanyak:
                                        <strong class="text-[#8C3A3A] text-lg font-normal font-serif-luxury block mt-1 tracking-wide">RM {{ depositAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</strong>
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                                    <!-- QR Card (DuitNow & Maybank details) -->
                                    <div class="qr-card p-6 flex flex-col justify-between space-y-4">
                                        <div class="flex justify-between items-center border-b border-white/10 pb-3">
                                            <span class="text-[10px] font-bold text-[#C5A880] uppercase tracking-widest">{{ t('scan_to_pay') }}</span>
                                            <span class="text-[9px] font-bold uppercase tracking-widest text-[#E6CBA3] bg-white/5 border border-white/10 px-2 py-0.5 rounded">DuitNow QR</span>
                                        </div>

                                        <div class="p-3 bg-white rounded-xl flex flex-col items-center justify-center shadow-inner self-center cursor-pointer group hover:bg-zinc-50 border border-transparent hover:border-[#C5A880]/30 transition-all duration-300" @click="showQRModal = true">
                                            <!-- Dynamically load the QR file if it exists -->
                                            <img 
                                                v-if="qrCodeFile" 
                                                :src="'/' + qrCodeFile" 
                                                alt="QR Code" 
                                                class="w-40 h-40 object-contain mx-auto transition-transform group-hover:scale-105 duration-300"
                                            />
                                            <div v-else class="w-40 h-40 bg-[#FAF7F2] flex flex-col items-center justify-center text-[#8C8275]">
                                                <i class="fas fa-qrcode text-4xl mb-2"></i>
                                                <span class="text-[10px] font-bold uppercase tracking-widest">{{ t('qr_not_configured') }}</span>
                                            </div>
                                            <!-- Magnifying glass / Click to enlarge indicator -->
                                            <span v-if="qrCodeFile" class="text-[9px] text-[#8C8275] group-hover:text-[#4A6B5D] font-semibold uppercase tracking-wider mt-1.5 flex items-center gap-1 transition-colors">
                                                <i class="fas fa-search-plus text-[8px]"></i> {{ t('click_to_enlarge') || 'Klik untuk besarkan' }}
                                            </span>
                                        </div>

                                        <div class="space-y-2 border-t border-white/10 pt-3">
                                            <div class="text-[10px] text-[#E6E1DA] uppercase tracking-wider font-semibold">
                                                <span class="text-[#C5A880] block text-xs font-bold leading-tight mb-1">
                                                    {{ page.props.settings?.bank_account_name || 'SmartServe Catering Enterprise' }}
                                                </span>
                                                {{ page.props.settings?.bank_name || 'Maybank' }} Account:
                                            </div>
                                            <div class="flex items-center justify-between bg-white/5 border border-white/10 rounded-lg p-2 text-xs">
                                                <span class="font-mono font-bold tracking-widest text-[#FAF7F2]">
                                                    {{ page.props.settings?.bank_account_no || '563064123456' }}
                                                </span>
                                                <button 
                                                    type="button" 
                                                    @click="copyAccountNumber" 
                                                    class="text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md transition-all cursor-pointer"
                                                    :class="copySuccess ? 'bg-emerald-600 text-white' : 'bg-[#C5A880] text-[#2D3330] hover:bg-[#b89047]'"
                                                >
                                                    <i class="fas" :class="copySuccess ? 'fa-check' : 'fa-copy'"></i> {{ copySuccess ? 'Copied' : 'Copy' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- File Upload Form -->
                                    <div class="flex flex-col justify-center space-y-4">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('upload_payment_slip') }}</label>
                                        
                                        <div 
                                            class="file-upload-area"
                                            :class="{ 'has-file': form.receipt }"
                                        >
                                            <input 
                                                type="file" 
                                                @change="handleFileChange"
                                                accept="image/jpeg,image/png,image/jpg,application/pdf"
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                required
                                            />
                                            <div class="space-y-3 pointer-events-none">
                                                <div class="w-12 h-12 bg-white rounded-full text-[#4A6B5D] flex items-center justify-center mx-auto text-lg border border-[#E6E1DA] shadow-2xs">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <div class="space-y-1">
                                                    <span class="text-xs font-bold text-[#2D3330] block uppercase tracking-wide">
                                                        {{ form.receipt ? form.receipt.name : t('select_receipt_file') }}
                                                    </span>
                                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider block font-medium">
                                                        {{ t('accepted_formats_desc') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <span v-if="fileError" class="text-xs text-red-500 font-semibold block">{{ fileError }}</span>
                                    </div>
                                </div>

                                <div class="border-t border-[#E6E1DA] pt-6 flex flex-col sm:flex-row items-center gap-3">
                                    <Link 
                                        :href="route('cart.index')" 
                                        class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 bg-white hover:bg-rose-50 border border-rose-200 text-rose-600 font-semibold py-4 px-6 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer"
                                    >
                                        <i class="fas fa-times text-[10px]"></i> {{ t('cancel') || 'Batal' }}
                                    </Link>
                                    <button 
                                        type="submit" 
                                        class="btn-premium-primary w-full sm:flex-1 inline-flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-4 px-6 rounded-xl text-xs uppercase tracking-widest transition-colors shadow-sm cursor-pointer"
                                        :disabled="form.processing"
                                    >
                                        <i class="fas fa-shield-alt text-[10px]"></i> {{ t('confirm_booking_submit') }}
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>

                    <!-- Right: Checkout Items Summary (4 cols) -->
                    <div class="lg:col-span-4 sticky top-24 space-y-6 font-sans-modern">
                        <div class="checkout-card space-y-6">
                            <h3 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider border-b border-[#EBEFEF] pb-3">{{ t('selected_packages') }}</h3>

                            <div class="divide-y divide-[#EBEFEF] max-h-80 overflow-y-auto pr-1">
                                <div v-for="item in cartItems" :key="item.id" class="py-4 space-y-2.5 first:pt-0">
                                    <div class="flex justify-between items-start gap-2">
                                        <div>
                                            <span class="font-normal text-[#2D3330] font-serif-luxury text-base uppercase tracking-wide block leading-tight">{{ item.package_name }}</span>
                                            <span class="text-[10px] text-[#8C8275] font-semibold uppercase tracking-wider block mt-0.5">{{ item.quantity }} {{ t('pax') }}</span>
                                        </div>
                                        <span class="font-normal font-serif-luxury text-sm text-[#2D3330] whitespace-nowrap">
                                            RM {{ (parseFloat(item.price) * parseInt(item.quantity)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                        </span>
                                    </div>
                                    <!-- Selected Add-ons -->
                                    <div v-if="item.selected_addons && item.selected_addons.length > 0" class="flex flex-wrap gap-1.5">
                                        <span 
                                            v-for="addon in item.selected_addons" 
                                            :key="addon"
                                            class="bg-[#FAF8F5] text-[9px] text-[#D98A29] font-medium px-2 py-0.5 border border-[#F5E6CD]"
                                        >
                                            + {{ addon }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Promo Code Area -->
                            <div class="border-t border-[#E6E1DA] pt-4 space-y-2.5 font-sans-modern">
                                <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('promo_code') }}</label>
                                <div class="flex gap-2">
                                    <input 
                                        type="text" 
                                        v-model="promoCode" 
                                        class="form-input text-xs uppercase" 
                                        placeholder="ENTER CODE"
                                        :disabled="appliedPromo"
                                    />
                                    <button 
                                        type="button" 
                                        @click="verifyPromo" 
                                        class="bg-[#2D3330] hover:bg-[#1C201E] text-white text-xs font-semibold px-4 rounded-xl transition-all uppercase tracking-wider cursor-pointer"
                                        :disabled="!promoCode || appliedPromo"
                                    >
                                        {{ t('apply_promo_btn') }}
                                    </button>
                                </div>
                                <div v-if="activePromos && activePromos.length > 0 && !appliedPromo" class="mt-1">
                                    <button 
                                        type="button" 
                                        @click="showPromoModal = true"
                                        class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[#C5A880] hover:text-[#b89047] transition-colors cursor-pointer"
                                    >
                                        <i class="fas fa-ticket-alt text-[9px]"></i> {{ t('view_available_promos') || 'Lihat Kod Promo Tersedia' }}
                                    </button>
                                </div>
                                <div v-if="promoMessage" class="text-[10px] font-bold mt-1" :class="appliedPromo ? 'text-emerald-700' : 'text-[#8C3A3A]'">
                                    {{ promoMessage }}
                                </div>
                                <div v-if="appliedPromo" class="flex justify-between items-center text-[10px] bg-emerald-50 text-emerald-800 border border-emerald-100 rounded px-2.5 py-1.5 mt-2">
                                    <span>Applied: <strong>{{ appliedPromo.code }}</strong></span>
                                    <button type="button" @click="removePromo" class="text-red-500 hover:text-red-700 font-bold uppercase text-[9px] tracking-wider ml-2">Remove</button>
                                </div>
                            </div>

                            <!-- Invoice Pricing breakdown -->
                            <div class="price-summary-box space-y-1.5 font-sans-modern">
                                <div class="price-row">
                                    <span>{{ t('subtotal') || 'Subjumlah' }}</span>
                                    <span class="font-bold text-[#2D3330]">
                                        RM {{ cartItems.reduce((sum, item) => sum + parseFloat(item.price) * parseInt(item.quantity), 0.00).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                    </span>
                                </div>
                                <div class="price-row">
                                    <span>Caj Penghantaran ({{ form.delivery_zone }}):</span>
                                    <span class="font-bold text-[#2D3330]">
                                        RM {{ deliveryFee.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                    </span>
                                </div>
                                <div v-if="appliedPromo" class="price-row text-emerald-700 font-semibold">
                                    <span>{{ t('discount') || 'Diskaun' }}</span>
                                    <span>- RM {{ discountAmount.toFixed(2) }}</span>
                                </div>
                                <div class="price-row total flex justify-between items-center border-t border-[#E6E1DA] pt-2 mt-1.5">
                                    <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-wider">Jumlah Kasar:</span>
                                    <span class="text-xl font-normal text-[#4A6B5D] font-serif-luxury tracking-wide">
                                        RM {{ grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                    </span>
                                </div>
                                <div class="price-row text-[#8C3A3A] font-semibold">
                                    <span>Deposit Diperlukan ({{ depositPercent }}%)</span>
                                    <span>RM {{ depositAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>
                                <div class="price-row text-[#8C8275]">
                                    <span>Baki Perlu Dijelaskan ({{ 100 - depositPercent }}%)</span>
                                    <span>RM {{ balanceAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
        
        <!-- Promo Codes Modal -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showPromoModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-[#1B2A22]/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl max-w-md w-full relative border border-[#E6E1DA] shadow-2xl overflow-hidden flex flex-col max-h-[80vh]">
                    
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                        <div>
                            <h3 class="text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                <i class="fas fa-ticket-alt text-[#C5A880] mr-1 text-[10px]"></i> {{ t('available_promo_codes') }}
                            </h3>
                        </div>
                        <button @click="showPromoModal = false"
                            class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="p-6 overflow-y-auto space-y-4">
                        <div v-if="activePromos && activePromos.length > 0" class="space-y-3">
                            <div 
                                v-for="promo in activePromos" 
                                :key="promo.id" 
                                class="bg-[#FAF8F5] border border-[#EBEFEF] rounded-2xl p-4 flex flex-col justify-between gap-3 hover:border-[#C5A880] transition-colors"
                            >
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <span class="inline-block bg-[#FAF6F0] border border-[#FAF0D9] text-[#C5A880] text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-lg tracking-wider mb-1.5">
                                            {{ promo.code }}
                                        </span>
                                        <p class="text-xs font-bold text-[#2D3330]">
                                            <template v-if="promo.type === 'percent'">
                                                {{ parseFloat(promo.value) }}% {{ t('discount') || 'Diskaun' }}
                                            </template>
                                            <template v-else>
                                                RM {{ parseFloat(promo.value).toFixed(2) }} {{ t('discount') || 'Diskaun' }}
                                            </template>
                                        </p>
                                    </div>
                                    <button 
                                        type="button"
                                        @click="promoCode = promo.code; verifyPromo(); showPromoModal = false;"
                                        class="bg-[#2D3330] hover:bg-[#1C201E] text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition-colors cursor-pointer uppercase tracking-wider shrink-0"
                                    >
                                        {{ t('use_promo_code') }}
                                    </button>
                                </div>
                                <div class="border-t border-[#EBEFEF] pt-2 flex flex-col gap-1 text-[9px] text-[#8C8275] font-semibold uppercase tracking-wider">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-shopping-bag text-[8px]"></i>
                                        <span>{{ t('min_spend_label') }}: RM {{ parseFloat(promo.min_spend).toFixed(2) }}</span>
                                    </div>
                                    <div v-if="promo.expires_at" class="flex items-center gap-1.5">
                                        <i class="fas fa-calendar-times text-[8px]"></i>
                                        <span>{{ t('expires_label') }}: {{ new Date(promo.expires_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 space-y-2">
                            <i class="fas fa-ticket-alt text-[#8C8275] text-2xl opacity-40"></i>
                            <p class="text-xs text-[#8C8275] font-semibold">{{ t('no_active_promos') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- QR Code Modal -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showQRModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-[#1B2A22]/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl max-w-sm w-full relative border border-[#E6E1DA] shadow-2xl overflow-hidden flex flex-col items-center">
                    
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between w-full px-6 py-4 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                        <h3 class="text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                            <i class="fas fa-qrcode text-[#C5A880] mr-1 text-[10px]"></i> Imbas QR Code
                        </h3>
                        <button @click="showQRModal = false"
                            class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>

                    <!-- Modal Body (QR View) -->
                    <div class="p-6 bg-white flex flex-col items-center justify-center space-y-4 w-full">
                        <div class="p-4 bg-white border border-[#E6E1DA] rounded-2xl shadow-sm">
                            <img 
                                v-if="qrCodeFile"
                                :src="'/' + qrCodeFile" 
                                alt="DuitNow QR Code" 
                                class="w-72 h-72 object-contain mx-auto"
                            />
                        </div>
                        <div class="text-center font-sans-modern">
                            <span class="text-[10px] text-[#C5A880] font-bold uppercase tracking-wider block">SmartServe Catering</span>
                            <span class="text-xs text-[#2D3330] font-bold block mt-0.5">DuitNow QR Payment</span>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
