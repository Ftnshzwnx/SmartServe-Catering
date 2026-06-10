<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    reviews: {
        type: Array,
        required: true,
    },
});

const { toast } = useToast();

const replyingTo = ref(null);

const form = useForm({
    admin_reply: '',
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
            toast('Reply submitted successfully.');
        }
    });
}
</script>

<template>
    <AdminLayout 
        title="Customer Reviews & Ratings Management"
        header-title="Customer Reviews & Ratings"
        header-desc="Monitor guest feedback, average ratings, and submit direct responses to testimonials."
    >
        <!-- Average stats overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 flex items-center justify-between shadow-xs">
                <div>
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1">Average Rating</span>
                    <div class="flex items-center gap-2">
                        <span class="text-3xl font-extrabold text-[#2D3330] font-serif-luxury">
                            {{ reviews.length ? (reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length).toFixed(1) : '0.0' }}
                        </span>
                        <div class="flex text-amber-400 text-sm">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-500 border border-amber-100 rounded-xl flex items-center justify-center text-lg">
                    <i class="fas fa-star"></i>
                </div>
            </div>

            <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 flex items-center justify-between shadow-xs">
                <div>
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1">Total Testimonials</span>
                    <span class="text-3xl font-extrabold text-[#2D3330] font-serif-luxury">{{ reviews.length }}</span>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-500 border border-blue-100 rounded-xl flex items-center justify-center text-lg">
                    <i class="fas fa-comments"></i>
                </div>
            </div>

            <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 flex items-center justify-between shadow-xs">
                <div>
                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1">Pending Replies</span>
                    <span class="text-3xl font-extrabold text-[#2D3330] font-serif-luxury">
                        {{ reviews.filter(r => !r.admin_reply).length }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-rose-50 text-rose-500 border border-rose-100 rounded-xl flex items-center justify-center text-lg">
                    <i class="fas fa-reply"></i>
                </div>
            </div>
        </div>

        <!-- Reviews Table Card -->
        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
            <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">Review Logs</h3>

            <div v-if="reviews.length > 0" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E6E1DA] text-[#8C8275] text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 pl-2 text-center w-12">No.</th>
                            <th class="py-3.5 pl-2">Customer</th>
                            <th class="py-3.5">Order Details</th>
                            <th class="py-3.5 text-center">Rating</th>
                            <th class="py-3.5">Review Comment</th>
                            <th class="py-3.5">Admin Response</th>
                            <th class="py-3.5 text-right pr-2">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E1DA] text-xs text-[#5C6460]">
                        <tr v-for="(review, index) in reviews" :key="review.id" class="hover:bg-[#FAF7F2]/40 transition-colors">
                            <td class="py-4 pl-2 text-center font-semibold text-[#8C8275]">
                                {{ index + 1 }}
                            </td>
                            <td class="py-4 pl-2">
                                <div class="font-bold text-[#2D3330]">{{ review.user?.full_name || 'N/A' }}</div>
                                <div class="text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ review.user?.email }}</div>
                            </td>
                            <td class="py-4">
                                <div class="font-semibold text-[#2D3330]">#SSC-{{ review.order_id }}</div>
                                <div class="text-[10px] text-[#8C8275] font-semibold mt-0.5">{{ review.order?.package_name }}</div>
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex justify-center text-amber-400 gap-0.5">
                                    <i v-for="s in 5" :key="s" class="fa-star text-[10px]" :class="s <= review.rating ? 'fas' : 'far'"></i>
                                </div>
                                <span class="text-[10px] text-[#8C8275] font-semibold block mt-1.5">({{ review.rating }}/5)</span>
                            </td>
                            <td class="py-4 max-w-xs whitespace-normal break-words italic text-[#5C6460]">
                                "{{ review.review_text || 'No comment.' }}"
                                <div class="text-[9px] text-[#8C8275] font-bold not-italic mt-2">Submitted: {{ new Date(review.created_at).toLocaleDateString() }}</div>
                            </td>
                            <td class="py-4 max-w-xs whitespace-normal break-words">
                                <span v-if="review.admin_reply" class="text-[#4A6B5D] font-semibold bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl px-3 py-1.5 block">
                                    {{ review.admin_reply }}
                                </span>
                                <span v-else class="text-[#8C8275] italic">No response yet.</span>
                            </td>
                            <td class="py-4 text-right pr-2">
                                <button 
                                    @click="openReplyModal(review)" 
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-3 py-2 rounded-xl text-[10px] uppercase tracking-wider transition-colors inline-flex items-center gap-1.5 shadow-xs cursor-pointer"
                                >
                                    <i class="fas" :class="review.admin_reply ? 'fa-edit' : 'fa-reply'"></i>
                                    <span>{{ review.admin_reply ? 'Edit Reply' : 'Reply' }}</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="py-12 text-center text-[#8C8275] space-y-3">
                <div class="w-12 h-12 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] flex items-center justify-center mx-auto text-xl">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <h5 class="font-bold text-sm text-[#2D3330]">No customer reviews yet.</h5>
                    <p class="text-xs text-[#8C8275] mt-1">Customer reviews will populate here after orders are marked delivered or completed.</p>
                </div>
            </div>
        </div>

        <!-- Reply Modal -->
        <div v-if="replyingTo" class="fixed inset-0 bg-[#1B2A22]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl border border-[#E6E1DA] shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in">
                <div class="bg-[#1B2A22] text-white p-6 flex justify-between items-center border-b border-[#24372D]">
                    <h3 class="text-base font-bold font-serif-luxury uppercase tracking-wider">Reply to {{ replyingTo.user?.full_name }}</h3>
                    <button @click="closeReplyModal" class="text-white/60 hover:text-white transition-colors w-8 h-8 rounded-full hover:bg-white/5 flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form @submit.prevent="submitReply" class="p-6 space-y-4">
                    <div class="bg-[#FAF7F2] rounded-2xl p-4 border border-[#E6E1DA] space-y-2">
                        <div class="flex items-center gap-1">
                            <i v-for="s in replyingTo.rating" :key="s" class="fas fa-star text-[10px] text-amber-400"></i>
                            <i v-for="s in (5 - replyingTo.rating)" :key="s" class="far fa-star text-[10px] text-[#E6E1DA]"></i>
                        </div>
                        <p class="text-xs text-[#5C6460] italic">"{{ replyingTo.review_text || 'No comment.' }}"</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-[#8C8275] uppercase tracking-wider">Admin Reply Response</label>
                        <textarea 
                            v-model="form.admin_reply"
                            rows="4" 
                            class="w-full text-xs border border-[#E6E1DA] rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] transition-shadow text-[#2D3330]" 
                            placeholder="Write your professional response..."
                            required
                        ></textarea>
                        <span v-if="form.errors.admin_reply" class="text-xs text-red-500 font-semibold">{{ form.errors.admin_reply }}</span>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-4 border-t border-[#E6E1DA]">
                        <button 
                            type="button" 
                            @click="closeReplyModal" 
                            class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest shadow transition-colors cursor-pointer"
                        >
                            {{ form.processing ? 'Submitting...' : 'Submit Reply' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
