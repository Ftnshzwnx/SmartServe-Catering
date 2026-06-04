<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    packages: {
        type: Array,
        required: true,
    },
});

const showPackageModal = ref(false);
const isEditing = ref(false);
const activePackageId = ref(null);

// Forms
const packageForm = useForm({
    package_name: '',
    price: '',
    min_order: 100,
    description: '',
});

const addonForm = useForm({
    package_id: null,
    addon_name: '',
    price_per_pax: '',
});

function openAddModal() {
    isEditing.value = false;
    activePackageId.value = null;
    packageForm.reset();
    packageForm.clearErrors();
    showPackageModal.value = true;
}

function openEditModal(pkg) {
    isEditing.value = true;
    activePackageId.value = pkg.id;
    packageForm.package_name = pkg.package_name;
    packageForm.price = pkg.price;
    packageForm.min_order = pkg.min_order;
    packageForm.description = pkg.description;
    packageForm.clearErrors();
    showPackageModal.value = true;
}

function closePackageModal() {
    showPackageModal.value = false;
    packageForm.reset();
}

function submitPackage() {
    if (isEditing.value) {
        packageForm.post(route('admin.packages.update', { id: activePackageId.value }), {
            onSuccess: () => {
                alert('Package updated successfully.');
                closePackageModal();
            }
        });
    } else {
        packageForm.post(route('admin.packages.store'), {
            onSuccess: () => {
                alert('Package created successfully.');
                closePackageModal();
            }
        });
    }
}

function deletePackage(id) {
    if (confirm('Are you sure you want to delete this catering package? All associated add-ons will also be removed.')) {
        router.delete(route('admin.packages.delete', { id: id }), {
            onSuccess: () => alert('Package deleted successfully.')
        });
    }
}

function submitAddon(packageId) {
    addonForm.package_id = packageId;
    addonForm.post(route('admin.addons.store'), {
        onSuccess: () => {
            addonForm.reset('addon_name', 'price_per_pax');
            alert('Extra add-on added.');
        }
    });
}

function deleteAddon(id) {
    if (confirm('Remove this add-on item?')) {
        router.delete(route('admin.addons.delete', { id: id }), {
            onSuccess: () => alert('Add-on deleted.')
        });
    }
}
</script>

<template>
    <Head title="Catering Packages Management" />

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
        .pkg-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.01);
            padding: 28px;
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
        .form-input {
            width: 100%;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            padding: 10px 16px;
            font-size: 0.9rem;
            color: #0f172a;
        }
        .form-input:focus {
            border-color: #c5a880;
            outline: none;
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.15);
        }
        .modal-overlay {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
        }
    </component>

    <div class="min-h-screen bg-[#f8fafc] flex font-body">
        
        <!-- Admin Navigation Sidebar -->
        <aside class="sidebar min-h-screen p-6 flex flex-col justify-between shrink-0 shadow-lg text-slate-300">
            <div class="space-y-8">
                <div class="flex items-center gap-2 border-b border-slate-800 pb-6">
                    <i class="fas fa-concierge-bell text-xl text-[#c5a880]"></i>
                    <span class="text-xl font-extrabold tracking-tight text-white font-title">
                        Smart<span class="text-[#c5a880]">Serve</span> Admin
                    </span>
                </div>

                <nav class="space-y-2">
                    <Link :href="route('admin.dashboard')" class="sidebar-link">
                        <i class="fas fa-chart-line text-sm w-5"></i> Dashboard
                    </Link>
                    <Link :href="route('admin.orders')" class="sidebar-link">
                        <i class="fas fa-receipt text-sm w-5"></i> Manage Orders
                    </Link>
                    <Link :href="route('admin.packages')" class="sidebar-link active">
                        <i class="fas fa-utensils text-sm w-5"></i> Catering Menus
                    </Link>
                    <Link :href="route('admin.settings')" class="sidebar-link">
                        <i class="fas fa-cogs text-sm w-5"></i> Settings
                    </Link>
                </nav>
            </div>

            <div class="border-t border-slate-800 pt-6">
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

        <!-- Main Content Panel -->
        <main class="main-content p-10 space-y-8">
            
            <div class="flex justify-between items-center border-b border-slate-200 pb-6">
                <div>
                    <h1 class="text-3xl font-bold font-title text-slate-800">Catering Packages</h1>
                    <p class="text-xs text-slate-400 mt-1">Add, modify or delete event menu packages and customized add-ons.</p>
                </div>
                <button 
                    @click="openAddModal" 
                    class="bg-[#c5a880] hover:bg-[#b89047] text-white font-bold px-5 py-3 rounded-xl text-xs flex items-center gap-2 shadow transition-colors"
                >
                    <i class="fas fa-plus"></i> Create New Package
                </button>
            </div>

            <!-- Packages grid layout -->
            <div v-if="packages.length > 0" class="space-y-8">
                <div v-for="pkg in packages" :key="pkg.id" class="pkg-card">
                    <div class="grid lg:grid-cols-12 gap-8">
                        
                        <!-- Left Package info details (7 cols) -->
                        <div class="lg:col-span-7 space-y-4">
                            <div class="flex flex-wrap justify-between items-start gap-4">
                                <div>
                                    <h3 class="text-xl font-bold text-slate-800 font-title uppercase">{{ pkg.package_name }}</h3>
                                    <p class="text-xs text-[#c5a880] font-bold mt-1">
                                        RM {{ parseFloat(pkg.price).toFixed(2) }} / pax &nbsp;·&nbsp; Min order requirement: {{ pkg.min_order }} pax
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button 
                                        @click="openEditModal(pkg)"
                                        class="bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold px-3 py-2 rounded-xl text-xs transition-colors flex items-center gap-1"
                                    >
                                        <i class="fas fa-edit text-[10px]"></i> Edit
                                    </button>
                                    <button 
                                        @click="deletePackage(pkg.id)"
                                        class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-bold px-3 py-2 rounded-xl text-xs transition-colors flex items-center gap-1"
                                    >
                                        <i class="fas fa-trash-alt text-[10px]"></i> Delete
                                    </button>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-4">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Included dishes details</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-500">
                                    <div 
                                        v-for="dish in pkg.description.split('\n').map(d=>d.trim()).filter(d=>d !== '')" 
                                        :key="dish"
                                        class="flex items-center gap-1.5"
                                    >
                                        <i class="fas fa-circle-check text-emerald-500 text-[10px]"></i>
                                        <span>{{ dish }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Addons Section (5 cols) -->
                        <div class="lg:col-span-5 bg-slate-50 p-6 rounded-2xl border border-slate-100 flex flex-col justify-between gap-6 self-stretch">
                            <div class="space-y-4">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block border-b border-slate-200 pb-1.5">Extra Add-on Items</span>
                                
                                <div v-if="pkg.addons && pkg.addons.length > 0" class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    <div 
                                        v-for="addon in pkg.addons" 
                                        :key="addon.id" 
                                        class="flex justify-between items-center text-xs text-slate-600 bg-white p-2 rounded-lg border border-slate-150"
                                    >
                                        <span>{{ addon.addon_name }} (+RM {{ parseFloat(addon.price_per_pax).toFixed(2) }} / pax)</span>
                                        <button 
                                            @click="deleteAddon(addon.id)" 
                                            class="text-red-400 hover:text-red-600 transition-colors w-6 h-6 rounded-full hover:bg-red-50 flex items-center justify-center"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-slate-400 italic">No extra custom items configured.</div>
                            </div>

                            <!-- Add addon form -->
                            <form @submit.prevent="submitAddon(pkg.id)" class="grid grid-cols-12 gap-2 border-t border-slate-200 pt-4">
                                <div class="col-span-6">
                                    <input 
                                        type="text" 
                                        v-model="addonForm.addon_name" 
                                        class="form-input text-xs" 
                                        placeholder="Add-on name" 
                                        required
                                    />
                                </div>
                                <div class="col-span-4">
                                    <input 
                                        type="number" 
                                        step="0.01"
                                        v-model="addonForm.price_per_pax" 
                                        class="form-input text-xs" 
                                        placeholder="Price / pax" 
                                        required
                                    />
                                </div>
                                <div class="col-span-2">
                                    <button 
                                        type="submit" 
                                        class="w-full h-full bg-[#c5a880] text-white rounded-lg flex items-center justify-center hover:bg-[#b89047] transition-colors"
                                        :disabled="addonForm.processing"
                                    >
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-3xl border border-slate-100 p-20 text-center">
                <i class="fas fa-utensils text-slate-200 text-4xl mb-4"></i>
                <h4 class="text-slate-400 font-bold">No packages configured.</h4>
                <p class="text-xs text-slate-400 mt-1">Get started by creating your first catering menu package above.</p>
            </div>

        </main>
    </div>

    <!-- Package Add/Edit Dialog Modal -->
    <div v-if="showPackageModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 modal-overlay">
        <div class="bg-white rounded-3xl max-w-xl w-full p-8 relative space-y-6">
            <button 
                @click="closePackageModal" 
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center"
            >
                <i class="fas fa-times text-lg"></i>
            </button>

            <h3 class="text-lg font-bold text-slate-800 font-title">
                {{ isEditing ? 'Edit Catering Package' : 'Create New Catering Package' }}
            </h3>

            <form @submit.prevent="submitPackage" class="space-y-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Package Category Name</label>
                    <input 
                        type="text" 
                        v-model="packageForm.package_name" 
                        class="form-input" 
                        placeholder="e.g. Wedding Set A / Aqiqah Custom" 
                        required
                    />
                    <span v-if="packageForm.errors.package_name" class="text-xs text-red-500 font-semibold">{{ packageForm.errors.package_name }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Base Price Per Pax (RM)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            v-model="packageForm.price" 
                            class="form-input" 
                            placeholder="e.g. 15.50" 
                            required
                        />
                        <span v-if="packageForm.errors.price" class="text-xs text-red-500 font-semibold">{{ packageForm.errors.price }}</span>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Min Order requirement (Pax)</label>
                        <input 
                            type="number" 
                            v-model="packageForm.min_order" 
                            class="form-input" 
                            placeholder="e.g. 100" 
                            required
                        />
                        <span v-if="packageForm.errors.min_order" class="text-xs text-red-500 font-semibold">{{ packageForm.errors.min_order }}</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block font-title">Included Dishes List (One dish per line)</label>
                    <textarea 
                        v-model="packageForm.description" 
                        rows="6" 
                        class="form-input" 
                        placeholder="Nasi Minyak&#10;Ayam Masak Merah&#10;Gulai Daging&#10;Acar Buah" 
                        required
                    ></textarea>
                    <span v-if="packageForm.errors.description" class="text-xs text-red-500 font-semibold">{{ packageForm.errors.description }}</span>
                </div>

                <div class="border-t border-slate-100 pt-4 flex justify-end gap-2">
                    <button 
                        type="button" 
                        @click="closePackageModal" 
                        class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-500 font-bold px-4 py-2.5 rounded-xl text-xs transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="bg-[#c5a880] hover:bg-[#b89047] text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow transition-colors"
                        :disabled="packageForm.processing"
                    >
                        {{ isEditing ? 'Save Changes' : 'Create Package' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
