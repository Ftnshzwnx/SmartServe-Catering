<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    currentStatus: {
        type: String,
        default: '',
    },
    search: {
        type: String,
        default: '',
    },
});

const { toast } = useToast();
const { confirm } = useConfirm();

const filterStatus = ref(props.currentStatus || '');
const searchQuery = ref(props.search || '');

const form = useForm({});

function applyFilters() {
    const params = {};
    if (filterStatus.value) params.status = filterStatus.value;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    router.get(route('admin.customers', params), {}, { preserveState: true, preserveScroll: true });
}

function handleFilterChange() {
    applyFilters();
}

let searchTimeout = null;
function handleSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
}

function clearSearch() {
    clearTimeout(searchTimeout);
    searchQuery.value = '';
    applyFilters();
}

const goToPage = (url) => {
    if (url) {
        router.visit(url, {
            preserveState: true,
            preserveScroll: true
        });
    }
}

async function toggleAccess(user) {
    const actionText = user.is_blacklisted ? 'restore active status for' : 'suspend access for';
    const confirmTitle = user.is_blacklisted ? 'Activate Account' : 'Suspend Account';
    
    if (await confirm(`Are you sure you want to ${actionText} ${user.full_name || user.name}?`, confirmTitle)) {
        form.post(route('admin.customers.toggle', { id: user.id }), {
            onSuccess: () => {
                toast(user.is_blacklisted ? 'Customer account activated successfully.' : 'Customer account suspended successfully.');
            }
        });
    }
}
</script>

<template>
    <AdminLayout 
        title="Customer Accounts Management"
        header-title="Customers"
        header-desc="View registered customers and manage their login and booking access status."
    >
        <template #header-action>
            <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl px-4 py-2.5 text-xs font-bold text-[#4A6B5D] flex items-center gap-2 shadow-2xs select-none">
                <i class="fas fa-users text-[#C5A880]"></i>
                <span class="text-[#8C8275] uppercase tracking-wider text-[10px]">Total Customers:</span>
                <span class="text-[#2D3330] font-extrabold text-sm">{{ users.total }}</span>
            </div>
        </template>

        <!-- Filters Card -->
        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-grow max-w-2xl">
                <!-- Search input wrapper -->
                <div class="relative flex-grow">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8C8275]">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Search name, email, phone, address..." 
                        class="w-full h-11 pl-10 pr-9 bg-[#FAF8F5] border border-[#E6E1DA] rounded-2xl text-xs font-semibold text-[#2D3330] placeholder-[#8C8275]/60 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white transition-all"
                        @input="handleSearchInput"
                    />
                    <button 
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#8C8275] hover:text-rose-600 transition-colors"
                        title="Clear Search"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Status Filter Dropdown with custom arrow -->
            <div class="flex items-center gap-3 shrink-0">
                <span class="hidden lg:inline text-[10px] font-bold text-[#8C8275] uppercase tracking-wider select-none">Filter Status:</span>
                <div class="relative w-full sm:w-48">
                    <select 
                        v-model="filterStatus"
                        @change="handleFilterChange"
                        class="w-full h-11 pl-4 pr-10 bg-[#FAF8F5] border border-[#E6E1DA] rounded-2xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Customers</option>
                        <option value="suspended">Suspended Accounts</option>
                    </select>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-[#8C8275]">
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
            <h3 class="text-base font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">Registered Customers</h3>

            <div v-if="users.data.length > 0" class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs text-[#5C6460]">
                    <thead>
                        <tr class="border-b border-[#E6E1DA] text-[#8C8275] font-bold uppercase tracking-wider">
                            <th class="py-3.5 pl-2 text-center w-12">No.</th>
                            <th class="py-3.5 pl-2">Full Name</th>
                            <th class="py-3.5">Email Address</th>
                            <th class="py-3.5">Phone</th>
                            <th class="py-3.5">Default Address</th>
                            <th class="py-3.5 text-center">Access Status</th>
                            <th class="py-3.5 text-right pr-2">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E1DA]">
                        <tr v-for="(user, index) in users.data" :key="user.id" class="hover:bg-[#FAF7F2]/40 transition-colors">
                            <td class="py-4 pl-2 text-center font-semibold text-[#8C8275]">
                                {{ users.from + index }}
                            </td>
                            <td class="py-4 pl-2 font-semibold text-[#2D3330]">
                                {{ user.full_name || user.name }}
                            </td>
                            <td class="py-4 text-[#5C6460] font-medium">
                                {{ user.email }}
                            </td>
                            <td class="py-4 text-[#8C8275] font-semibold">
                                {{ user.phone || 'N/A' }}
                            </td>
                            <td class="py-4 text-[#8C8275] font-semibold max-w-xs truncate" :title="user.address">
                                {{ user.address || 'N/A' }}
                            </td>
                            <td class="py-4 text-center">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider"
                                    :class="user.is_blacklisted ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-[#4A6B5D] border-emerald-200'"
                                >
                                    {{ user.is_blacklisted ? 'Suspended' : 'Active' }}
                                </span>
                            </td>
                            <td class="py-4 text-right pr-2">
                                <button 
                                    @click="toggleAccess(user)"
                                    class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors shadow-xs border cursor-pointer mx-auto md:ml-auto md:mr-0"
                                    :class="user.is_blacklisted 
                                        ? 'bg-emerald-50 hover:bg-emerald-100 border-emerald-200 text-[#4A6B5D]' 
                                        : 'bg-rose-50 hover:bg-rose-100 border-rose-200 text-rose-600'"
                                    :title="user.is_blacklisted ? 'Activate Account' : 'Suspend Account'"
                                >
                                    <i class="fas text-xs" :class="user.is_blacklisted ? 'fa-user-check' : 'fa-user-slash'"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="py-12 text-center text-[#8C8275] space-y-3">
                <div class="w-12 h-12 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] flex items-center justify-center mx-auto text-xl">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h5 class="font-bold text-sm text-[#2D3330]">No customers found matching the criteria.</h5>
                </div>
            </div>

            <!-- Pagination Controls -->
            <div v-if="users.data.length > 0" class="pt-6 border-t border-[#E6E1DA] flex flex-wrap items-center justify-between gap-4">
                <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-wider">
                    Showing {{ users.from }}–{{ users.to }} of {{ users.total }} customers
                </span>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button @click="goToPage(users.prev_page_url)" :disabled="!users.prev_page_url"
                        class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-xs font-bold transition-colors cursor-pointer"
                        :class="users.prev_page_url ? 'text-[#4A6B5D] hover:bg-[#FAF7F2] hover:border-[#4A6B5D]' : 'text-[#C6C1B9] cursor-not-allowed bg-[#FAF7F2]'">
                        <i class="fas fa-chevron-left text-[9px]"></i>
                    </button>
                    <template v-for="link in users.links" :key="link.label">
                        <button v-if="link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;'"
                            @click="goToPage(link.url)" :disabled="!link.url"
                            class="min-w-8 h-8 px-2.5 rounded-xl border text-[11px] font-bold transition-colors cursor-pointer"
                            :class="link.active ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-sm' : link.url ? 'border-[#E6E1DA] text-[#5C6460] hover:bg-[#FAF7F2] hover:border-[#4A6B5D] hover:text-[#4A6B5D]' : 'border-transparent text-[#8C8275] cursor-default'"
                            v-html="link.label">
                        </button>
                    </template>
                    <button @click="goToPage(users.next_page_url)" :disabled="!users.next_page_url"
                        class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-xs font-bold transition-colors cursor-pointer"
                        :class="users.next_page_url ? 'text-[#4A6B5D] hover:bg-[#FAF7F2] hover:border-[#4A6B5D]' : 'text-[#C6C1B9] cursor-not-allowed bg-[#FAF7F2]'">
                        <i class="fas fa-chevron-right text-[9px]"></i>
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
