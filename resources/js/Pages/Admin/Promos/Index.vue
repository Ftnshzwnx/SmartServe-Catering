<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    promos: {
        type: Array,
        required: true,
    },
});

const { toast } = useToast();
const { confirm } = useConfirm();

const isAdding = ref(false);

const form = useForm({
    code: '',
    type: 'percent',
    value: '',
    min_spend: '0.00',
    expires_at: '',
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
            toast('Promo code created successfully.');
        }
    });
}

async function deletePromo(id) {
    if (await confirm('Are you sure you want to delete this promo code?', 'Delete Promo Code')) {
        form.delete(route('admin.promos.delete', { id }), {
            onSuccess: () => {
                toast('Promo code deleted successfully.');
            }
        });
    }
}
</script>

<template>
    <AdminLayout 
        title="Promo & Discount Code Management"
        header-title="Promo Codes"
        header-desc="Configure special event discounts, voucher codes, and minimum spend boundaries."
    >
        <!-- Action Buttons Row -->
        <div class="flex flex-wrap items-center justify-end gap-3">
            <button 
                @click="toggleAddForm"
                class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow-xs transition-colors uppercase tracking-widest cursor-pointer"
            >
                <i class="fas" :class="isAdding ? 'fa-times' : 'fa-plus'"></i>
                <span>{{ isAdding ? 'Close Builder' : 'Create Promo Code' }}</span>
            </button>
        </div>

        <!-- New Promo Form -->
        <div v-if="isAdding" class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs max-w-2xl animate-fade-in space-y-6">
            <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide border-b border-[#E6E1DA] pb-2.5">New Discount Voucher</h3>
            
            <form @submit.prevent="submitPromo" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">Promo Code Identifier</label>
                    <input 
                        type="text" 
                        v-model="form.code" 
                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                        placeholder="e.g. MERDEKA70, RAYA10" 
                        required
                    />
                    <span v-if="form.errors.code" class="text-xs text-red-500 font-semibold block">{{ form.errors.code }}</span>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">Discount Type</label>
                    <select v-model="form.type" class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" required>
                        <option value="percent">Percent (%) Discount</option>
                        <option value="fixed">Fixed Flat Rate (RM)</option>
                    </select>
                    <span v-if="form.errors.type" class="text-xs text-red-500 font-semibold block">{{ form.errors.type }}</span>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">Discount Value (Rate/Amt)</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        v-model="form.value" 
                        class="w-full rounded-xl border-[#E6E1DA] text-[#2D3330] p-3 text-xs focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D]" 
                        placeholder="e.g. 10 for 10% or RM 10" 
                        required
                    />
                    <span v-if="form.errors.value" class="text-xs text-red-500 font-semibold block">{{ form.errors.value }}</span>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">Minimum Spend Boundary (RM)</label>
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
                    <label class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">Expiry Date (Optional)</label>
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
                        Create Discount Voucher
                    </button>
                </div>
            </form>
        </div>

        <!-- List Table Card -->
        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">Active & Past Promo Vouchers</h3>
                <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-full px-3.5 py-1 text-xs font-bold text-[#4A6B5D] flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-ticket-alt text-[10px]"></i>
                    <span>Total: {{ promos.length }}</span>
                </div>
            </div>

            <div v-if="promos.length > 0" class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs text-[#5C6460]">
                    <thead>
                        <tr class="border-b border-[#E6E1DA] text-[#8C8275] font-bold uppercase tracking-wider">
                            <th class="py-3.5 pl-2 text-center w-12">No.</th>
                            <th class="py-3.5 pl-2">Voucher Code</th>
                            <th class="py-3.5">Type</th>
                            <th class="py-3.5">Value</th>
                            <th class="py-3.5">Min Spend</th>
                            <th class="py-3.5">Expires At</th>
                            <th class="py-3.5 text-right pr-2">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E1DA]">
                        <tr v-for="(promo, index) in promos" :key="promo.id" class="hover:bg-[#FAF7F2]/40 transition-colors">
                            <td class="py-4 pl-2 text-center font-semibold text-[#8C8275]">
                                {{ index + 1 }}
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
                                {{ promo.expires_at ? new Date(promo.expires_at).toLocaleString() : 'Never' }}
                            </td>
                            <td class="py-4 text-right pr-2">
                                <button 
                                    @click="deletePromo(promo.id)"
                                    class="text-rose-500 hover:text-rose-700 p-2 text-xs rounded-lg hover:bg-rose-50 transition-colors cursor-pointer"
                                    title="Delete Voucher"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="py-12 text-center text-[#8C8275] space-y-3">
                <div class="w-12 h-12 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] flex items-center justify-center mx-auto text-xl">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div>
                    <h5 class="font-bold text-sm text-[#2D3330]">No promotional codes found.</h5>
                    <p class="text-xs text-[#8C8275] mt-1">Get started by creating your first promotional code above.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
