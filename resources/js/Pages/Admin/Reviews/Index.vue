<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    reviews: {
        type: Array,
        required: true,
    },
});

const { toast } = useToast();
const { t } = useLocalization();

const replyingTo = ref(null);

const form = useForm({
    admin_reply: '',
});

// --- Search, Filter & Pagination State ---
const searchQuery = ref('');
const filterRating = ref('all'); // 'all' | '5' | '4' | '3' | '2' | '1'
const filterStatus = ref('all'); // 'all' | 'pending' | 'replied'
const currentPage = ref(1);
const reviewsPerPage = 10;

const filteredReviews = computed(() => {
    let result = props.reviews;
    
    // Rating Filter
    if (filterRating.value !== 'all') {
        const ratingNum = parseInt(filterRating.value);
        result = result.filter(r => r.rating === ratingNum);
    }
    
    // Status Filter
    if (filterStatus.value !== 'all') {
        if (filterStatus.value === 'pending') {
            result = result.filter(r => !r.admin_reply);
        } else if (filterStatus.value === 'replied') {
            result = result.filter(r => r.admin_reply);
        }
    }
    
    // Search Query
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        result = result.filter(r => 
            (r.user?.full_name || '').toLowerCase().includes(query) ||
            (r.user?.email || '').toLowerCase().includes(query) ||
            `#ssc-${r.order_id}`.includes(query) ||
            `${r.order_id}`.includes(query) ||
            (r.review_text || '').toLowerCase().includes(query) ||
            (r.admin_reply || '').toLowerCase().includes(query)
        );
    }
    
    return result;
});

const paginatedReviews = computed(() => {
    const start = (currentPage.value - 1) * reviewsPerPage;
    return filteredReviews.value.slice(start, start + reviewsPerPage);
});

const totalPages = computed(() => {
    return Math.ceil(filteredReviews.value.length / reviewsPerPage) || 1;
});

watch([searchQuery, filterRating, filterStatus], () => {
    currentPage.value = 1;
});

function openReplyModal(review) {
    replyingTo.value = review;
    form.admin_reply = review.admin_reply || '';
}

function closeReplyModal() {
    replyingTo.value = null;
    form.reset();
}

function submitReply() {
    form.post(route('admin.reviews.reply', { id: replyingTo.value.id }), {
        onSuccess: () => {
            closeReplyModal();
            toast(t('admin_reviews_toast_reply_submitted'));
        }
    });
}
</script>

<template>
    <AdminLayout 
        :title="t('admin_reviews_dashboard_title')"
        :header-title="t('admin_reviews_header_title')"
        :header-desc="t('admin_reviews_desc')"
    >
        <!-- Average stats overview -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
            <!-- Avg Rating Card -->
            <div class="bg-white border border-[#E6E1DA] rounded-2xl md:rounded-3xl p-3 md:p-6 flex items-center justify-between shadow-xs col-span-1">
                <div class="min-w-0 flex-grow pr-2">
                    <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1 truncate">{{ t('admin_reviews_avg_rating') }}</span>
                    <div class="flex items-center gap-1.5">
                        <span class="text-lg sm:text-2xl md:text-3xl font-extrabold text-[#2D3330] font-serif-luxury">
                            {{ reviews.length ? (reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length).toFixed(1) : '0.0' }}
                        </span>
                        <div class="flex text-amber-400 text-xs md:text-sm">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-amber-50 text-amber-500 border border-amber-100 rounded-xl flex items-center justify-center text-xs sm:text-sm md:text-lg shrink-0">
                    <i class="fas fa-star"></i>
                </div>
            </div>

            <!-- Total Testimonials Card -->
            <div class="bg-white border border-[#E6E1DA] rounded-2xl md:rounded-3xl p-3 md:p-6 flex items-center justify-between shadow-xs col-span-1">
                <div class="min-w-0 flex-grow pr-2">
                    <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1 truncate">{{ t('admin_reviews_total_testimonials') }}</span>
                    <span class="text-lg sm:text-2xl md:text-3xl font-extrabold text-[#2D3330] font-serif-luxury">{{ reviews.length }}</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-blue-50 text-blue-500 border border-blue-100 rounded-xl flex items-center justify-center text-xs sm:text-sm md:text-lg shrink-0">
                    <i class="fas fa-comments"></i>
                </div>
            </div>

            <!-- Pending Replies Card -->
            <div class="bg-white border border-[#E6E1DA] rounded-2xl md:rounded-3xl p-3 md:p-6 flex items-center justify-between shadow-xs col-span-2 md:col-span-1">
                <div class="min-w-0 flex-grow pr-2">
                    <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1 truncate">{{ t('admin_reviews_pending_replies') }}</span>
                    <span class="text-lg sm:text-2xl md:text-3xl font-extrabold text-[#2D3330] font-serif-luxury">
                        {{ reviews.filter(r => !r.admin_reply).length }}
                    </span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-rose-50 text-rose-500 border border-rose-100 rounded-xl flex items-center justify-center text-xs sm:text-sm md:text-lg shrink-0">
                    <i class="fas fa-reply"></i>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="bg-white border border-[#E6E1DA] rounded-2xl md:rounded-3xl p-3 sm:p-4 md:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 mt-4 sm:mt-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-grow max-w-2xl w-full">
                <!-- Search Input -->
                <div class="relative flex-grow w-full">
                    <span class="absolute inset-y-0 left-0 pl-2.5 sm:pl-3.5 flex items-center pointer-events-none text-[#8C8275]">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        :placeholder="t('admin_search_reviews_placeholder')" 
                        class="w-full h-9 sm:h-11 pl-8 sm:pl-10 pr-8 sm:pr-9 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl sm:rounded-2xl text-xs font-semibold text-[#2D3330] placeholder-[#8C8275]/60 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white transition-all"
                    />
                    <button 
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-2.5 sm:pr-3.5 flex items-center text-[#8C8275] hover:text-rose-600 transition-colors cursor-pointer"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto shrink-0">
                <!-- Rating Filter -->
                <div class="relative w-full sm:w-40">
                    <select 
                        v-model="filterRating"
                        class="w-full h-9 sm:h-11 pl-3 sm:pl-4 pr-8 sm:pr-10 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl sm:rounded-2xl text-[11px] sm:text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                    >
                        <option value="all">{{ t('admin_all_ratings') }}</option>
                        <option value="5">5 {{ t('admin_rating_stars').replace('{stars}', '5') }}</option>
                        <option value="4">4 {{ t('admin_rating_stars').replace('{stars}', '4') }}</option>
                        <option value="3">3 {{ t('admin_rating_stars').replace('{stars}', '3') }}</option>
                        <option value="2">2 {{ t('admin_rating_stars').replace('{stars}', '2') }}</option>
                        <option value="1">1 {{ t('admin_rating_star') }}</option>
                    </select>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 sm:pr-4 pointer-events-none text-[#8C8275]">
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </span>
                </div>

                <!-- Reply Status Filter -->
                <div class="relative w-full sm:w-48">
                    <select 
                        v-model="filterStatus"
                        class="w-full h-9 sm:h-11 pl-3 sm:pl-4 pr-8 sm:pr-10 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl sm:rounded-2xl text-[11px] sm:text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                    >
                        <option value="all">{{ t('admin_all_statuses') }}</option>
                        <option value="pending">{{ t('admin_reviews_pending_replies') }}</option>
                        <option value="replied">{{ t('admin_reviews_response_col') }}</option>
                    </select>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 sm:pr-4 pointer-events-none text-[#8C8275]">
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Reviews Table Card -->
        <div class="bg-white border border-[#E6E1DA] rounded-2xl md:rounded-3xl p-3 sm:p-5 md:p-8 shadow-xs space-y-4 sm:space-y-6">
            <h3 class="text-sm sm:text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">{{ t('admin_reviews_logs_title') }}</h3>

            <div v-if="filteredReviews.length > 0" class="overflow-x-auto scrollbar-none pb-2">
                <table class="w-full text-left border-collapse min-w-[850px]">
                    <thead>
                        <tr class="border-b border-[#E6E1DA] text-[#8C8275] text-[10px] sm:text-xs font-bold uppercase tracking-wider">
                            <th class="px-2 sm:px-4 py-1.5 sm:py-3 text-center w-12">{{ t('admin_reviews_no_col') }}</th>
                            <th class="px-2 sm:px-4 py-1.5 sm:py-3">{{ t('admin_reviews_customer_col') }}</th>
                            <th class="px-2 sm:px-4 py-1.5 sm:py-3">{{ t('admin_reviews_order_col') }}</th>
                            <th class="px-2 sm:px-4 py-1.5 sm:py-3 text-center">{{ t('admin_reviews_rating_col') }}</th>
                            <th class="px-2 sm:px-4 py-1.5 sm:py-3">{{ t('admin_reviews_comment_col') }}</th>
                            <th class="px-2 sm:px-4 py-1.5 sm:py-3">{{ t('admin_reviews_response_col') }}</th>
                            <th class="px-2 sm:px-4 py-1.5 sm:py-3 text-right">{{ t('admin_reviews_action_col') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E1DA] text-[10px] sm:text-xs text-[#5C6460]">
                        <tr v-for="(review, index) in paginatedReviews" :key="review.id" class="hover:bg-[#FAF7F2]/40 transition-colors">
                            <td class="px-2 sm:px-4 py-1.5 sm:py-3 text-center font-semibold text-[#8C8275]">
                                {{ (currentPage - 1) * reviewsPerPage + index + 1 }}
                            </td>
                            <td class="px-2 sm:px-4 py-1.5 sm:py-3">
                                <div class="font-bold text-[#2D3330] text-[10px] sm:text-xs">{{ review.user?.full_name || 'N/A' }}</div>
                                <div class="text-[9px] sm:text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ review.user?.email }}</div>
                            </td>
                            <td class="px-2 sm:px-4 py-1.5 sm:py-3">
                                <div class="font-semibold text-[#2D3330] text-[10px] sm:text-xs">#SSC-{{ review.order_id }}</div>
                                <div class="text-[9px] sm:text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ review.order?.package_name }}</div>
                            </td>
                            <td class="px-2 sm:px-4 py-1.5 sm:py-3 text-center">
                                <div class="flex justify-center text-amber-400 gap-0.5">
                                    <i v-for="s in 5" :key="s" class="fa-star text-[10px]" :class="s <= review.rating ? 'fas' : 'far'"></i>
                                </div>
                                <span class="text-[9px] sm:text-[10px] text-[#8C8275] font-semibold block mt-1">({{ review.rating }}/5)</span>
                            </td>
                            <td class="px-2 sm:px-4 py-1.5 sm:py-3 max-w-xs whitespace-normal break-words italic text-[#5C6460] text-[10px] sm:text-xs">
                                "{{ review.review_text || t('admin_reviews_no_comment') }}"
                                <div class="text-[8px] sm:text-[9px] text-[#8C8275] font-bold not-italic mt-1.5">{{ t('admin_reviews_submitted_at').replace('{date}', new Date(review.created_at).toLocaleDateString()) }}</div>
                            </td>
                            <td class="px-2 sm:px-4 py-1.5 sm:py-3 max-w-xs whitespace-normal break-words text-[10px] sm:text-xs">
                                <span v-if="review.admin_reply" class="text-[#4A6B5D] font-semibold bg-[#FAF7F2] border border-[#E6E1DA] rounded-lg px-2 py-1 block">
                                    {{ review.admin_reply }}
                                </span>
                                <span v-else class="text-[#8C8275] italic text-[10px] sm:text-xs">{{ t('admin_reviews_no_response') }}</span>
                            </td>
                            <td class="px-2 sm:px-4 py-1.5 sm:py-3 text-right">
                                <button 
                                    @click="openReplyModal(review)" 
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg text-[9px] sm:text-[10px] uppercase tracking-wider transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                >
                                    <i class="fas text-[9px] sm:text-[10px]" :class="review.admin_reply ? 'fa-edit' : 'fa-reply'"></i>
                                    <span>{{ review.admin_reply ? t('admin_reviews_edit_reply_btn') : t('admin_reviews_reply_btn') }}</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination for Reviews -->
                <div v-if="filteredReviews.length > 0" class="flex justify-between items-center p-3 sm:p-4 border-t border-[#E6E1DA]">
                    <button 
                        @click="currentPage = Math.max(1, currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-2.5 py-1 border border-[#E6E1DA] rounded-lg text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="currentPage === 1 ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <i class="fas fa-chevron-left text-[8px]"></i>
                        <span>{{ t('admin_prev_page') }}</span>
                    </button>
                    
                    <span class="text-[10px] sm:text-xs font-semibold text-[#8C8275]">
                        {{ currentPage }} / {{ totalPages }}
                    </span>
                    
                    <button 
                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="px-2.5 py-1 border border-[#E6E1DA] rounded-lg text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
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
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <h5 class="font-bold text-sm text-[#2D3330]">{{ t('admin_reviews_no_reviews_title') }}</h5>
                    <p class="text-xs text-[#8C8275] mt-1">{{ searchQuery || filterRating !== 'all' || filterStatus !== 'all' ? t('admin_no_packages_matching_filter') : t('admin_reviews_no_reviews_desc') }}</p>
                </div>
            </div>
        </div>

        <!-- Reply Modal -->
        <div v-if="replyingTo" class="fixed inset-0 bg-[#1B2A22]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-[#E6E1DA] shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in">
                <div class="bg-[#1B2A22] text-white p-4 sm:p-6 flex justify-between items-center border-b border-[#24372D]">
                    <h3 class="text-sm sm:text-base font-bold font-serif-luxury uppercase tracking-wider">{{ t('admin_reviews_modal_title').replace('{name}', replyingTo.user?.full_name || 'Customer') }}</h3>
                    <button @click="closeReplyModal" class="text-white/60 hover:text-white transition-colors w-7 h-7 sm:w-8 sm:h-8 rounded-full hover:bg-white/5 flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                
                <form @submit.prevent="submitReply" class="p-4 sm:p-6 space-y-3.5 sm:space-y-4">
                    <div class="bg-[#FAF7F2] rounded-xl p-3 sm:p-4 border border-[#E6E1DA] space-y-1.5 sm:space-y-2">
                        <div class="flex items-center gap-1">
                            <i v-for="s in replyingTo.rating" :key="s" class="fas fa-star text-[10px] text-amber-400"></i>
                            <i v-for="s in (5 - replyingTo.rating)" :key="s" class="far fa-star text-[10px] text-[#E6E1DA]"></i>
                        </div>
                        <p class="text-xs text-[#5C6460] italic">"{{ replyingTo.review_text || t('admin_reviews_no_comment') }}"</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] sm:text-xs font-bold text-[#8C8275] uppercase tracking-wider">{{ t('admin_reviews_modal_label') }}</label>
                        <textarea 
                            v-model="form.admin_reply"
                            rows="4" 
                            class="w-full text-[11px] sm:text-xs border border-[#E6E1DA] rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] transition-shadow text-[#2D3330]" 
                            :placeholder="t('admin_reviews_modal_placeholder')"
                            required
                        ></textarea>
                        <span v-if="form.errors.admin_reply" class="text-xs text-red-500 font-semibold">{{ form.errors.admin_reply }}</span>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-3 border-t border-[#E6E1DA]">
                        <button 
                            type="button" 
                            @click="closeReplyModal" 
                            class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-3 py-2 rounded-lg text-[10px] sm:text-xs uppercase tracking-wider transition-colors cursor-pointer w-full sm:w-auto text-center"
                        >
                            {{ t('admin_reviews_modal_cancel') }}
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-3 py-2 rounded-lg text-[10px] sm:text-xs uppercase tracking-wider shadow transition-colors cursor-pointer w-full sm:w-auto text-center"
                        >
                            {{ form.processing ? t('admin_reviews_modal_submitting') : t('admin_reviews_modal_submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
