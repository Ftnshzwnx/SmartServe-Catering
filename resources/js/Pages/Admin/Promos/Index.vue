<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    promos: {
        type: Array,
        required: true,
    },
});

const { toast } = useToast();
const { confirm } = useConfirm();
const { t, currentLanguage } = useLocalization();

const isAdding = ref(false);

const form = useForm({
    code: '',
    type: 'percent',
    value: '',
    min_spend: '0.00',
    expires_at: '',
});

// --- Search, Filter & Pagination State ---
const searchQuery = ref('');
const filterType = ref('all'); // 'all' | 'percent' | 'fixed'
const filterStatus = ref('all'); // 'all' | 'active' | 'expired'
const currentPage = ref(1);
const promosPerPage = 10;

const filteredPromos = computed(() => {
    let result = props.promos;
    
    // Type Filter
    if (filterType.value !== 'all') {
        result = result.filter(p => p.type === filterType.value);
    }
    
    // Status Filter
    if (filterStatus.value !== 'all') {
        const now = new Date();
        if (filterStatus.value === 'active') {
            result = result.filter(p => !p.expires_at || new Date(p.expires_at) > now);
        } else if (filterStatus.value === 'expired') {
            result = result.filter(p => p.expires_at && new Date(p.expires_at) <= now);
        }
    }
    
    // Search query
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toUpperCase().trim();
        result = result.filter(p => p.code.toUpperCase().includes(query));
    }
    
    return result;
});

const paginatedPromos = computed(() => {
    const start = (currentPage.value - 1) * promosPerPage;
    return filteredPromos.value.slice(start, start + promosPerPage);
});

const totalPages = computed(() => {
    return Math.ceil(filteredPromos.value.length / promosPerPage) || 1;
});

watch([searchQuery, filterType, filterStatus], () => {
    currentPage.value = 1;
});

function toggleAddForm() {
    isAdding.value = !isAdding.value;
    form.reset();
}

function submitPromo() {
    form.post(route('admin.promos.store'), {
        onSuccess: () => {
            isAdding.value = false;
            form.reset();
            toast(t('admin_toast_promo_created'));
        }
    });
}

async function deletePromo(id) {
    if (await confirm(t('admin_confirm_delete_promo'), t('admin_confirm_delete_promo_title'))) {
        form.delete(route('admin.promos.delete', { id }), {
            onSuccess: () => {
                toast(t('admin_toast_promo_deleted'));
            }
        });
    }
}
</script>

<template>
    <AdminLayout 
        :title="t('admin_promos_title')"
        :header-title="t('admin_promos_header_title')"
        :header-desc="t('admin_promos_desc')"
    >
        <!-- Action Buttons Row -->
        <div class="flex flex-wrap items-center justify-end gap-3">
            <button 
                @click="toggleAddForm"
                class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow-xs transition-colors uppercase tracking-widest cursor-pointer"
            >
                <i class="fas" :class="isAdding ? 'fa-times' : 'fa-plus'"></i>
                <span>{{ isAdding ? t('admin_close_builder') : t('admin_create_promo_code') }}</span>
            </button>
        </div>

        <!-- New Promo Form -->
        <div v-if="isAdding" class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs max-w-2xl animate-fade-in space-y-6">
            <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">{{ t('admin_new_discount_voucher') }}</h3>
            
            <form @submit.prevent="submitPromo" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_promo_code_identifier') }}</label>
                    <input 
                        type="text" 
                        v-model="form.code" 
                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                        :placeholder="t('admin_promo_code_placeholder')" 
                        required
                    />
                    <span v-if="form.errors.code" class="text-xs text-red-500 font-semibold block">{{ form.errors.code }}</span>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_discount_type') }}</label>
                    <select v-model="form.type" class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" required>
                        <option value="percent">{{ t('admin_percent_discount') }}</option>
                        <option value="fixed">{{ t('admin_fixed_discount') }}</option>
                    </select>
                    <span v-if="form.errors.type" class="text-xs text-red-500 font-semibold block">{{ form.errors.type }}</span>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_discount_value') }}</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        v-model="form.value" 
                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                        :placeholder="t('admin_discount_value_placeholder')" 
                        required
                    />
                    <span v-if="form.errors.value" class="text-xs text-red-500 font-semibold block">{{ form.errors.value }}</span>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_min_spend_boundary') }}</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        v-model="form.min_spend" 
                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                        required
                    />
                    <span v-if="form.errors.min_spend" class="text-xs text-red-500 font-semibold block">{{ form.errors.min_spend }}</span>
                </div>

                <div class="space-y-1.5 md:col-span-2">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_expiry_date_optional') }}</label>
                    <input 
                        type="datetime-local" 
                        v-model="form.expires_at" 
                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                    />
                    <span v-if="form.errors.expires_at" class="text-xs text-red-500 font-semibold block">{{ form.errors.expires_at }}</span>
                </div>

                <div class="md:col-span-2 pt-4 border-t border-[#E6E1DA] flex justify-end">
                    <button 
                        type="submit" 
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-6 py-3.5 rounded-xl text-xs uppercase tracking-widest shadow transition-colors cursor-pointer"
                        :disabled="form.processing"
                    >
                        {{ t('admin_create_discount_voucher_btn') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Filters Card -->
        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-grow max-w-2xl">
                <!-- Search Input -->
                <div class="relative flex-grow">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8C8275]">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        :placeholder="t('admin_search_vouchers_placeholder')" 
                        class="w-full h-11 pl-10 pr-9 bg-[#FAF8F5] border border-[#E6E1DA] rounded-2xl text-xs font-semibold text-[#2D3330] placeholder-[#8C8275]/60 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white transition-all"
                    />
                    <button 
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#8C8275] hover:text-rose-600 transition-colors cursor-pointer"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Type & Status Filters -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <!-- Voucher Type Filter Dropdown -->
                <div class="relative w-full sm:w-40">
                    <select 
                        v-model="filterType"
                        class="w-full h-11 pl-4 pr-10 bg-[#FAF8F5] border border-[#E6E1DA] rounded-2xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                    >
                        <option value="all">{{ t('admin_all_types') }}</option>
                        <option value="percent">{{ t('admin_percent_discount') }}</option>
                        <option value="fixed">{{ t('admin_fixed_discount') }}</option>
                    </select>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-[#8C8275]">
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </span>
                </div>

                <!-- Status Filter Dropdown -->
                <div class="relative w-full sm:w-40">
                    <select 
                        v-model="filterStatus"
                        class="w-full h-11 pl-4 pr-10 bg-[#FAF8F5] border border-[#E6E1DA] rounded-2xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                    >
                        <option value="all">{{ t('admin_all_statuses') }}</option>
                        <option value="active">{{ t('admin_active') }}</option>
                        <option value="expired">{{ t('admin_expired') }}</option>
                    </select>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-[#8C8275]">
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- List Table Card -->
        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_active_past_promos') }}</h3>
                <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-full px-3.5 py-1 text-xs font-bold text-[#4A6B5D] flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-ticket-alt text-[10px]"></i>
                    <span>{{ t('admin_total_vouchers').replace('{count}', promos.length) }}</span>
                </div>
            </div>

            <div v-if="filteredPromos.length > 0" class="overflow-x-auto scrollbar-none pb-2">
                <table class="w-full text-left border-collapse text-xs text-[#5C6460] min-w-[800px]">
                    <thead>
                        <tr class="border-b border-[#E6E1DA] text-[#8C8275] font-bold uppercase tracking-wider">
                            <th class="py-3.5 pl-2 text-center w-12">{{ t('admin_number_col') }}</th>
                            <th class="py-3.5 pl-2">{{ t('admin_voucher_code_col') }}</th>
                            <th class="py-3.5">{{ currentLanguage === 'en' ? 'Type' : 'Jenis' }}</th>
                            <th class="py-3.5">{{ currentLanguage === 'en' ? 'Value' : 'Nilai' }}</th>
                            <th class="py-3.5">{{ t('min_spend_label') }}</th>
                            <th class="py-3.5">{{ t('admin_expires_at_col') }}</th>
                            <th class="py-3.5 text-right pr-2">{{ t('admin_actions_col') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E1DA]">
                        <tr v-for="(promo, index) in paginatedPromos" :key="promo.id" class="hover:bg-[#FAF7F2]/40 transition-colors">
                            <td class="py-4 pl-2 text-center font-semibold text-[#8C8275]">
                                {{ (currentPage - 1) * promosPerPage + index + 1 }}
                            </td>
                            <td class="py-4 pl-2 font-mono font-bold text-[#2D3330] text-sm tracking-wide">
                                {{ promo.code }}
                            </td>
                            <td class="py-4 uppercase font-semibold text-[#8C8275] text-[10px]">
                                {{ promo.type }}
                            </td>
                            <td class="py-4 font-bold text-[#2D3330]">
                                {{ promo.type === 'percent' ? parseFloat(promo.value) + '%' : 'RM ' + parseFloat(promo.value).toFixed(2) }}
                            </td>
                            <td class="py-4 text-[#5C6460]">
                                RM {{ parseFloat(promo.min_spend).toFixed(2) }}
                            </td>
                            <td class="py-4 text-[#5C6460]">
                                {{ promo.expires_at ? new Date(promo.expires_at).toLocaleString() : (currentLanguage === 'en' ? 'Never' : 'Tiada Had') }}
                            </td>
                            <td class="py-4 text-right pr-2">
                                <button 
                                    @click="deletePromo(promo.id)"
                                    class="text-[#8C8275] hover:text-rose-700 p-2 text-xs rounded-lg hover:bg-rose-50 transition-colors cursor-pointer"
                                    :title="t('admin_delete_voucher_title')"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination for Promos -->
                <div v-if="filteredPromos.length > 0" class="flex justify-between items-center p-4 border-t border-[#E6E1DA]">
                    <button 
                        @click="currentPage = Math.max(1, currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-3.5 py-1.5 border border-[#E6E1DA] rounded-xl text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="currentPage === 1 ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <i class="fas fa-chevron-left text-[8px]"></i>
                        <span>{{ t('admin_prev_page') }}</span>
                    </button>
                    
                    <span class="text-xs font-semibold text-[#8C8275]">
                        {{ currentPage }} / {{ totalPages }}
                    </span>
                    
                    <button 
                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="px-3.5 py-1.5 border border-[#E6E1DA] rounded-xl text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="currentPage === totalPages ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <span>{{ t('admin_next_page') }}</span>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                    </button>
                </div>
            </div>

            <!-- Empty / No matches state -->
            <div v-else class="py-12 text-center text-[#8C8275] space-y-3">
                <div class="w-12 h-12 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] flex items-center justify-center mx-auto text-xl">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div>
                    <h5 class="font-bold text-sm text-[#2D3330]">{{ t('admin_no_promos_found') }}</h5>
                    <p class="text-xs text-[#8C8275] mt-1">{{ searchQuery || filterType !== 'all' || filterStatus !== 'all' ? t('admin_no_packages_matching_filter') : t('admin_get_started_promo_desc') }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
