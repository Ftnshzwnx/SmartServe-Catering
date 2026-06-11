<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    orders: {
        type: Array,
        required: true,
    },
    blockedDates: {
        type: Array,
        required: true,
    },
});

const { toast } = useToast();
const { confirm } = useConfirm();
const { t } = useLocalization();

// Calendar State
const today = new Date();
const currentYear = ref(today.getFullYear());
const currentMonth = ref(today.getMonth()); // 0-indexed

function nextMonth() {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
}

function prevMonth() {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
}

// Generate calendar grid
const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;

    const firstDayIndex = new Date(year, month, 1).getDay(); // Day of week (0-6)
    const totalDays = new Date(year, month + 1, 0).getDate(); // Total days in month

    const days = [];

    // Padding for days of previous month
    for (let i = 0; i < firstDayIndex; i++) {
        days.push({ day: null, dateString: null, currentMonth: false });
    }

    // Days of current month
    for (let day = 1; day <= totalDays; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        
        // Find bookings on this date
        const dayBookings = props.orders.filter(order => order.delivery_date === dateStr);
        
        // Check if date is blocked
        const blocked = props.blockedDates.find(bd => bd.blocked_date === dateStr);

        days.push({
            day,
            dateString: dateStr,
            currentMonth: true,
            bookings: dayBookings,
            blockedReason: blocked ? blocked.reason : null,
            isBlocked: !!blocked,
        });
    }

    return days;
});

// Block date form state
const blockForm = useForm({
    blocked_date: '',
    reason: '',
});

function submitBlockDate() {
    blockForm.post(route('admin.blocked-dates.store'), {
        onSuccess: () => {
            blockForm.reset();
            toast(t('admin_calendar_toast_date_blocked'));
        }
    });
}

async function deleteBlockDate(id) {
    if (await confirm(t('admin_calendar_confirm_unblock'), t('admin_calendar_confirm_unblock_title'))) {
        blockForm.delete(route('admin.blocked-dates.delete', { id }), {
            onSuccess: () => {
                toast(t('admin_calendar_toast_unblocked'));
            }
        });
    }
}

// Order hover details popup
const activeDetails = ref(null);
function showDetails(booking) {
    activeDetails.value = booking;
}
function hideDetails() {
    activeDetails.value = null;
}
</script>

<template>
    <AdminLayout 
        :title="t('admin_calendar_dashboard_title')"
        :header-title="t('admin_calendar_header_title')"
        :header-desc="t('admin_calendar_desc')"
    >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Interactive Calendar Grid (8 cols) -->
            <div class="lg:col-span-8 space-y-6">
                <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
                    
                    <!-- Month selector navigation -->
                    <div class="flex justify-between items-center pb-2">
                        <h2 class="text-xl font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                            {{ t('month_' + (currentMonth + 1)) }} {{ currentYear }}
                        </h2>
                        <div class="flex items-center gap-2">
                            <button 
                                @click="prevMonth"
                                class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] p-2 rounded-xl text-xs transition-colors shadow-xs cursor-pointer w-9 h-9 flex items-center justify-center"
                            >
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button 
                                @click="nextMonth"
                                class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] p-2 rounded-xl text-xs transition-colors shadow-xs cursor-pointer w-9 h-9 flex items-center justify-center"
                            >
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Days of Week Headers -->
                    <div class="grid grid-cols-7 text-center text-[10px] font-bold text-[#8C8275] uppercase tracking-widest border-b border-[#E6E1DA] pb-3">
                        <div>{{ t('admin_calendar_sun') }}</div>
                        <div>{{ t('admin_calendar_mon') }}</div>
                        <div>{{ t('admin_calendar_tue') }}</div>
                        <div>{{ t('admin_calendar_wed') }}</div>
                        <div>{{ t('admin_calendar_thu') }}</div>
                        <div>{{ t('admin_calendar_fri') }}</div>
                        <div>{{ t('admin_calendar_sat') }}</div>
                    </div>

                    <!-- Cells Grid -->
                    <div class="grid grid-cols-7 gap-2">
                        <div 
                            v-for="(dayObj, idx) in calendarDays" 
                            :key="idx" 
                            class="min-h-[110px] rounded-2xl p-2.5 flex flex-col justify-between transition-all border"
                            :class="[
                                dayObj.day ? 'bg-white border-[#E6E1DA]' : 'bg-[#FAF7F2]/40 border-transparent',
                                dayObj.isBlocked ? 'bg-rose-50/60 border-rose-100' : ''
                            ]"
                        >
                            <span v-if="dayObj.day" class="text-xs font-bold text-[#8C8275]" :class="{ 'text-rose-500': dayObj.isBlocked }">
                                {{ dayObj.day }}
                            </span>

                            <div v-if="dayObj.day" class="flex-grow flex flex-col justify-end gap-1 mt-2.5">
                                <!-- Blocked Badge -->
                                <span 
                                    v-if="dayObj.isBlocked" 
                                    class="bg-rose-100 text-rose-700 text-[8px] font-extrabold uppercase px-1.5 py-0.5 rounded-lg text-center truncate"
                                    :title="dayObj.blockedReason"
                                >
                                    {{ t('admin_calendar_blocked_badge') }}
                                </span>

                                <!-- Confirmed Booking Badges -->
                                <button 
                                    v-for="booking in dayObj.bookings" 
                                    :key="booking.id"
                                    @click="showDetails(booking)"
                                    class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 text-[#4A6B5D] text-[8px] font-bold uppercase p-1 rounded-lg text-left truncate transition-colors flex items-center gap-1 cursor-pointer"
                                >
                                    <i class="fas fa-circle text-[4px] text-[#4A6B5D]"></i>
                                    <span>#SSC-{{ booking.id }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Block Dates Manager Panel (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Block new Date Form -->
                <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs space-y-4">
                    <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2">{{ t('admin_calendar_block_form_title') }}</h3>
                    
                    <form @submit.prevent="submitBlockDate" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_calendar_target_date') }}</label>
                            <input 
                                type="date" 
                                v-model="blockForm.blocked_date" 
                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                required
                            />
                            <span v-if="blockForm.errors.blocked_date" class="text-xs text-red-500 font-semibold block mt-1">{{ blockForm.errors.blocked_date }}</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-wider block">{{ t('admin_calendar_reason_label') }}</label>
                            <input 
                                type="text" 
                                v-model="blockForm.reason" 
                                class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                                :placeholder="t('admin_calendar_reason_placeholder')"
                            />
                            <span v-if="blockForm.errors.reason" class="text-xs text-red-500 font-semibold block mt-1">{{ blockForm.errors.reason }}</span>
                        </div>

                        <button 
                            type="submit" 
                            class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold w-full py-3 rounded-xl text-xs uppercase tracking-widest shadow transition-colors cursor-pointer"
                            :disabled="blockForm.processing"
                        >
                            <i class="fas fa-lock mr-1.5"></i> {{ t('admin_calendar_block_submit_btn') }}
                        </button>
                    </form>
                </div>

                <!-- Blocked Dates List -->
                <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs space-y-4 max-h-[400px] overflow-y-auto">
                    <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2">{{ t('admin_calendar_blocked_list_title') }}</h3>

                    <div v-if="blockedDates.length > 0" class="divide-y divide-[#E6E1DA]">
                        <div 
                            v-for="bd in blockedDates" 
                            :key="bd.id" 
                            class="py-3.5 flex justify-between items-center text-xs text-[#5C6460] first:pt-0"
                        >
                            <div>
                                <span class="font-bold text-[#2D3330] block">{{ bd.blocked_date }}</span>
                                <span class="text-[10px] text-[#8C8275] font-semibold block mt-1">{{ bd.reason || t('admin_calendar_no_reason') }}</span>
                            </div>
                            <button 
                                @click="deleteBlockDate(bd.id)"
                                class="text-rose-500 hover:text-rose-700 p-2 text-xs rounded-lg hover:bg-rose-50 transition-colors cursor-pointer"
                                :title="t('admin_calendar_unblock_btn_title')"
                            >
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-[#8C8275] text-xs italic">
                        {{ t('admin_calendar_no_blocked_dates') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Details Popover Modal -->
        <div v-if="activeDetails" class="fixed inset-0 bg-[#1B2A22]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl border border-[#E6E1DA] shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
                <div class="bg-[#1B2A22] text-white p-6 flex justify-between items-center border-b border-[#24372D]">
                    <h3 class="text-sm font-bold font-serif-luxury uppercase tracking-wider">{{ t('admin_calendar_modal_title').replace('{id}', activeDetails.id) }}</h3>
                    <button @click="hideDetails" class="text-white/60 hover:text-white transition-colors w-8 h-8 rounded-full hover:bg-white/5 flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-4 text-xs text-[#5C6460]">
                    <div class="grid grid-cols-2 gap-4 border-b border-[#E6E1DA] pb-4">
                        <div>
                            <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_calendar_modal_cust_name') }}</span>
                            <span class="font-bold text-[#2D3330]">{{ activeDetails.user?.full_name || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_calendar_modal_cust_phone') }}</span>
                            <span class="font-bold text-[#2D3330]">{{ activeDetails.user?.phone || 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="border-b border-[#E6E1DA] pb-4 space-y-2">
                        <div>
                            <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_calendar_modal_packages') }}</span>
                            <span class="font-bold text-[#2D3330]">{{ activeDetails.package_name }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_calendar_modal_total') }}</span>
                            <span class="font-bold text-[#4A6B5D] text-sm">RM {{ parseFloat(activeDetails.total_price).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div>
                            <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_calendar_modal_setup_time') }}</span>
                            <span class="font-semibold text-[#2D3330]">{{ activeDetails.delivery_time }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_calendar_modal_venue') }}</span>
                            <span class="block whitespace-pre-line leading-relaxed text-[#5C6460] bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl p-3 mt-1.5">{{ activeDetails.delivery_address }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-[#E6E1DA]">
                        <Link 
                            :href="route('admin.orders', { status: activeDetails.status })" 
                            class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 py-2.5 rounded-xl text-[10px] uppercase tracking-widest shadow transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>{{ t('admin_calendar_modal_orders_mgmt_btn') }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
