<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const qrPreviewUrl = ref('');
const fileError = ref('');

// Form state
const form = useForm({
    business_name: props.settings.business_name || 'SmartServe Catering',
    qr_code: null,
});

function handleFileChange(event) {
    const file = event.target.files[0];
    fileError.value = '';
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            fileError.value = 'File size must be less than 2MB.';
            form.qr_code = null;
            qrPreviewUrl.value = '';
            return;
        }
        form.qr_code = file;
        qrPreviewUrl.value = URL.createObjectURL(file);
    }
}

function submitSettings() {
    form.post(route('admin.settings.update'), {
        forceFormData: true,
        onSuccess: () => {
            alert('Business configurations and payment credentials updated successfully.');
            form.reset('qr_code');
            qrPreviewUrl.value = '';
        }
    });
}
</script>

<template>
    <Head title="Catering Business Settings" />

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
        .settings-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.01);
            padding: 30px;
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
            padding: 12px 16px;
            font-size: 0.95rem;
            color: #0f172a;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            border-color: #c5a880;
            outline: none;
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.15);
        }
        .qr-placeholder {
            border: 2px dashed rgba(197, 168, 128, 0.3);
            border-radius: 16px;
            background: #fffdf9;
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
                    <Link :href="route('admin.packages')" class="sidebar-link">
                        <i class="fas fa-utensils text-sm w-5"></i> Catering Menus
                    </Link>
                    <Link :href="route('admin.settings')" class="sidebar-link active">
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
            <div class="border-b border-slate-200 pb-6">
                <h1 class="text-3xl font-bold font-title text-slate-800">Business Settings</h1>
                <p class="text-xs text-slate-400 mt-1">Configure company profile information, banking references, and QR payment credentials.</p>
            </div>

            <div class="max-w-3xl">
                <form @submit.prevent="submitSettings" class="settings-card space-y-8">
                    
                    <!-- Section: General -->
                    <div class="space-y-4">
                        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">General Credentials</h3>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Business / Company Name</label>
                            <input 
                                type="text" 
                                v-model="form.business_name" 
                                class="form-input" 
                                placeholder="e.g. SmartServe Catering Enterprise"
                                required
                            />
                            <span v-if="form.errors.business_name" class="text-xs text-red-500 font-semibold">{{ form.errors.business_name }}</span>
                        </div>
                    </div>

                    <!-- Section: QR Code Payment -->
                    <div class="space-y-4">
                        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">Customer Payment Credentials</h3>
                        <p class="text-xs text-slate-400">Upload or replace the default merchant QR Code displayed on the customer checkout page. Allowed formats: JPEG, PNG.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                            <!-- QR code Preview -->
                            <div class="qr-placeholder p-6 text-center space-y-3">
                                <span class="text-[10px] font-bold text-[#c5a880] uppercase tracking-widest block">Current Merchant QR</span>
                                
                                <div class="inline-block p-2 bg-white border border-slate-100 rounded-xl shadow-sm">
                                    <img 
                                        v-if="qrPreviewUrl" 
                                        :src="qrPreviewUrl" 
                                        alt="New QR Preview" 
                                        class="w-36 h-36 object-contain"
                                    />
                                    <img 
                                        v-else-if="settings.qr_code_path" 
                                        :src="'/' + settings.qr_code_path" 
                                        alt="Current QR Code" 
                                        class="w-36 h-36 object-contain"
                                    />
                                    <div v-else class="w-36 h-36 bg-slate-100 rounded flex flex-col items-center justify-center text-slate-300">
                                        <i class="fas fa-qrcode text-3xl mb-1"></i>
                                        <span class="text-[9px] font-bold">NO FILE</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload actions -->
                            <div class="space-y-4">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Upload Merchant QR Image</label>
                                
                                <div class="border-2 border-dashed border-slate-200 hover:border-[#c5a880] rounded-xl p-6 text-center cursor-pointer transition-colors relative bg-slate-50/50">
                                    <input 
                                        type="file" 
                                        @change="handleFileChange"
                                        accept="image/jpeg,image/png,image/jpg"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    />
                                    <div class="space-y-1.5 pointer-events-none text-xs">
                                        <div class="text-[#c5a880] text-lg mx-auto">
                                            <i class="fas fa-image"></i>
                                        </div>
                                        <span class="font-bold text-slate-700 block">
                                            {{ form.qr_code ? form.qr_code.name : 'Choose new image file' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 block">
                                            JPEG, PNG (Max 2MB)
                                        </span>
                                    </div>
                                </div>
                                <span v-if="fileError" class="text-xs text-red-500 font-semibold block">{{ fileError }}</span>
                                <span v-if="form.errors.qr_code" class="text-xs text-red-500 font-semibold block">{{ form.errors.qr_code }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit action -->
                    <div class="border-t border-slate-100 pt-6 flex justify-end">
                        <button 
                            type="submit" 
                            class="bg-[#c5a880] hover:bg-[#b89047] text-white font-bold px-6 py-3 rounded-xl text-xs shadow transition-colors"
                            :disabled="form.processing"
                        >
                            <i class="fas fa-save mr-1.5"></i> Save Settings
                        </button>
                    </div>

                </form>
            </div>

        </main>
    </div>
</template>
