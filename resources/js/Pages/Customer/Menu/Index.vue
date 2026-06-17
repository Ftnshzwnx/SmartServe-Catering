<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
});

const { t, currentLanguage } = useLocalization();

const searchQuery = ref('');
const activeTab = ref('all');

function getCategoryKey(packageName) {
    const lower = packageName.toLowerCase();
    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding') || lower.includes('tunang') || lower.includes('engagement')) {
        return 'wedding';
    }
    if (lower.includes('corporate') || lower.includes('korporat') || lower.includes('seminar') || lower.includes('office') || lower.includes('mesyuarat')) {
        return 'corporate';
    }
    if (lower.includes('aqiqah') || lower.includes('cukur') || lower.includes('baby') || lower.includes('birthday') || lower.includes('lahir') || lower.includes('kenduri') || lower.includes('family')) {
        return 'aqiqah';
    }
    return 'other';
}

const filteredPackages = computed(() => {
    return props.packages.filter(pkg => {
        // Filter by tab
        if (activeTab.value !== 'all') {
            const cat = getCategoryKey(pkg.package_name);
            if (cat !== activeTab.value) return false;
        }
        
        // Filter by search query
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const nameMatch = pkg.package_name.toLowerCase().includes(query);
            const descMatch = pkg.description?.toLowerCase().includes(query) || false;
            return nameMatch || descMatch;
        }
        
        return true;
    });
});

function getPackageBadge(pkg) {
    const lower = pkg.package_name.toLowerCase();
    if (lower.includes('gold')) {
        return { text: t('popular_tag'), colors: 'bg-amber-50 text-amber-800 border-amber-200' };
    }
    if (lower.includes('platinum')) {
        return { text: t('signature_tag'), colors: 'bg-indigo-50 text-indigo-800 border-indigo-200' };
    }
    if (lower.includes('premium')) {
        return { text: t('premium_tag'), colors: 'bg-[#FAF6F0] text-[#4A6B5D] border-[#E6E1DA]' };
    }
    if (lower.includes('kenduri') || lower.includes('standard')) {
        return { text: t('value_tag'), colors: 'bg-emerald-50 text-emerald-800 border-emerald-200' };
    }
    return null;
}

function getCategoryIcon(name) {
    const lower = name.toLowerCase();
    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding') || lower.includes('tunang') || lower.includes('engagement')) {
        return {
            icon: 'fa-heart',
            colors: 'text-[#8C3A3A] bg-[#FDF2F2] border-[#FADCDD]'
        };
    }
    if (lower.includes('corporate') || lower.includes('korporat') || lower.includes('seminar') || lower.includes('office') || lower.includes('mesyuarat')) {
        return {
            icon: 'fa-briefcase',
            colors: 'text-[#3D574B] bg-[#EBEFEF] border-[#D1DEDB]'
        };
    }
    if (lower.includes('aqiqah') || lower.includes('cukur') || lower.includes('baby') || lower.includes('birthday') || lower.includes('lahir') || lower.includes('kenduri') || lower.includes('family')) {
        return {
            icon: 'fa-birthday-cake',
            colors: 'text-[#6E5D4F] bg-[#FAF6F0] border-[#EADED9]'
        };
    }
    return {
        icon: 'fa-utensils',
        colors: 'text-[#4A6B5D] bg-[#FAF7F2] border-[#E6E1DA]'
    };
}

function getPackageImage(pkg) {
    if (pkg.image && pkg.image !== 'placeholder.jpg') {
        return pkg.image.startsWith('/') ? pkg.image : '/' + pkg.image;
    }
    
    const weddingImages = [
        'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=800&q=80', // Elegant table setting
        'https://images.unsplash.com/photo-1555244162-803834f70033?auto=format&fit=crop&w=800&q=80', // Luxury buffet setup
        'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=800&q=80', // Banquet food display
        'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?auto=format&fit=crop&w=800&q=80'  // Wedding reception food
    ];

    const aqiqahImages = [
        'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80', // BBQ/Roasted meats feast
        'https://images.unsplash.com/photo-1560684352-8497838a2229?auto=format&fit=crop&w=800&q=80', // Buffet pans
        'https://images.unsplash.com/photo-1532636875304-0c8fe119ff91?auto=format&fit=crop&w=800&q=80', // Catering lamb/beef dishes
        'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80'  // Gourmet roasted dish
    ];

    const corporateImages = [
        'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80', // Canape appetizers
        'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?auto=format&fit=crop&w=800&q=80', // High tea/buffet platters
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=800&q=80', // Premium dining event
        'https://images.unsplash.com/photo-1555244162-803834f70033?auto=format&fit=crop&w=800&q=80'  // Gourmet catering banquet
    ];

    const generalMalayImages = [
        'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&w=800&q=80', // Biryani Rice feast
        'https://images.unsplash.com/photo-1601050690597-df056fb4ce78?auto=format&fit=crop&w=800&q=80', // Traditional curry/roti setup
        'https://images.unsplash.com/photo-1541832676-9b763b0239ab?auto=format&fit=crop&w=800&q=80', // Asian gourmet hot pot/feast
        'https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&w=800&q=80'  // Asian style skewers/catering
    ];

    const name = pkg.package_name || '';
    const lower = name.toLowerCase();
    
    // Hash function to consistently select the same image for the same package name
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash);

    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding') || lower.includes('tunang') || lower.includes('engagement') || lower.includes('nikah')) {
        return weddingImages[index % weddingImages.length];
    }
    if (lower.includes('aqiqah') || lower.includes('cukur') || lower.includes('baby') || lower.includes('birthday') || lower.includes('lahir') || lower.includes('family') || lower.includes('kenduri')) {
        return aqiqahImages[index % aqiqahImages.length];
    }
    if (lower.includes('korporat') || lower.includes('corporate') || lower.includes('event') || lower.includes('seminar') || lower.includes('mesyuarat')) {
        return corporateImages[index % corporateImages.length];
    }
    return generalMalayImages[index % generalMalayImages.length];
}
</script>

<template>
    <Head :title="t('catering_packages')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .stepper-item {
            position: relative;
            flex: 1 1 0%;
            text-align: center;
        }
        .stepper-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 13px;
            left: 50%;
            width: 100%;
            height: 1px;
            background-color: #E6E1DA;
            z-index: 1;
        }
        @media (min-width: 640px) {
            .stepper-item:not(:last-child)::after {
                top: 16px;
            }
        }
        .stepper-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: white;
            border: 1px solid #E6E1DA;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            margin: 0 auto 6px auto;
            position: relative;
            z-index: 2;
            transition: all 0.3s;
        }
        @media (min-width: 640px) {
            .stepper-circle {
                width: 32px;
                height: 32px;
                font-size: 11px;
                margin: 0 auto 8px auto;
            }
        }
        .stepper-item.active .stepper-circle {
            background: #4A6B5D;
            border-color: #4A6B5D;
            color: white;
            box-shadow: 0 4px 10px rgba(74, 107, 93, 0.2);
        }
        
        .menu-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .menu-card:hover {
            transform: translateY(-4px);
            border-color: #4A6B5D;
            box-shadow: 0 12px 24px -10px rgba(74, 107, 93, 0.15);
        }
        
        .pillar-card {
            border: 1px solid #E6E1DA;
            border-radius: 20px;
            padding: 20px;
        }
        
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-none {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </component>

    <AuthenticatedLayout
        :header-title="t('our_menu')"
        :header-desc="t('our_menu_desc')"
    >

        <div class="font-sans-modern">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 space-y-6">
                
                <!-- 1. CATERING STEPPER PROGRESS BAR -->
                <div class="bg-white border border-[#E6E1DA] rounded-xl sm:rounded-3xl py-2 px-3 sm:py-4 sm:px-6 shadow-xs max-w-3xl mx-auto">
                    <div class="flex items-center justify-between w-full relative">
                        <!-- Step 1 -->
                        <div class="stepper-item active">
                            <div class="stepper-circle">1</div>
                            <span class="text-[7.5px] sm:text-[9px] font-bold uppercase tracking-widest text-[#4A6B5D]">
                                {{ t('select_occasion_stepper') }}
                            </span>
                        </div>
                        <!-- Step 2 -->
                        <div class="stepper-item">
                            <div class="stepper-circle">2</div>
                            <span class="text-[7.5px] sm:text-[9px] font-bold uppercase tracking-widest text-[#8C8275]">
                                {{ t('customize_menu_stepper') }}
                            </span>
                        </div>
                        <!-- Step 3 -->
                        <div class="stepper-item">
                            <div class="stepper-circle">3</div>
                            <span class="text-[7.5px] sm:text-[9px] font-bold uppercase tracking-widest text-[#8C8275]">
                                {{ t('confirm_booking_stepper') }}
                            </span>
                        </div>
                    </div>
                </div>
 
                <!-- 2. SPLIT LAYOUT -->
                <div class="grid lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- Left Column: Occasion Catalog (8 cols) -->
                    <div class="lg:col-span-8 space-y-6 min-w-0">
                        <div class="bg-white border border-[#E6E1DA] rounded-lg sm:rounded-2xl p-2.5 sm:p-5 shadow-xs space-y-2.5 sm:space-y-5 min-w-0">
                            
                            <!-- Search & Filter Header -->
                            <div class="space-y-3 sm:space-y-4">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div>
                                        <span class="text-[8px] sm:text-[10px] font-bold text-[#4A6B5D] uppercase tracking-widest block mb-0.5 sm:mb-1">{{ t('premium_selection') }}</span>
                                        <h3 class="text-sm sm:text-2xl font-light text-[#2D3330] font-serif-luxury tracking-wide">
                                            {{ t('select_occasion') }}
                                        </h3>
                                    </div>
                                    
                                    <!-- Search Input -->
                                    <div class="relative w-full md:w-72 shrink-0">
                                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-[10px] sm:text-xs">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input 
                                            type="text" 
                                            v-model="searchQuery" 
                                            :placeholder="t('search_placeholder')"
                                            class="w-full pl-7 pr-3 py-1.5 text-[10px] sm:text-xs border border-[#E6E1DA] rounded-lg focus:outline-none focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2]/40 text-[#2D3330] placeholder-slate-400 transition-colors"
                                        />
                                    </div>
                                </div>
                                
                                <p class="text-[9.5px] sm:text-xs text-[#8C8275] font-light leading-relaxed">
                                    {{ t('select_event_type_desc') }}
                                </p>
 
                                 <!-- Category Tab Filters -->
                                <div class="flex overflow-x-auto whitespace-nowrap scrollbar-none gap-1.5 pt-1 border-b border-[#FAF6F0] pb-2.5 sm:pb-4">
                                    <button 
                                        @click="activeTab = 'all'" 
                                        class="flex-shrink-0 whitespace-nowrap px-2.5 py-1 sm:px-3.5 sm:py-2 rounded-md sm:rounded-lg text-[8.5px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none"
                                        :class="activeTab === 'all' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                                    >
                                        {{ t('all_packages') }}
                                    </button>
                                    <button 
                                        @click="activeTab = 'wedding'" 
                                        class="flex-shrink-0 whitespace-nowrap px-2.5 py-1 sm:px-3.5 sm:py-2 rounded-md sm:rounded-lg text-[8.5px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none"
                                        :class="activeTab === 'wedding' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                                    >
                                        {{ t('wedding_packages') }}
                                    </button>
                                    <button 
                                        @click="activeTab = 'corporate'" 
                                        class="flex-shrink-0 whitespace-nowrap px-2.5 py-1 sm:px-3.5 sm:py-2 rounded-md sm:rounded-lg text-[8.5px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none"
                                        :class="activeTab === 'corporate' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                                    >
                                        {{ t('corporate_packages') }}
                                    </button>
                                    <button 
                                        @click="activeTab = 'aqiqah'" 
                                        class="flex-shrink-0 whitespace-nowrap px-2.5 py-1 sm:px-3.5 sm:py-2 rounded-md sm:rounded-lg text-[8.5px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none"
                                        :class="activeTab === 'aqiqah' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                                    >
                                        {{ t('aqiqah_family') }}
                                    </button>
                                </div>
                            </div>
 
                            <!-- Package Grid -->
                            <div v-if="filteredPackages.length > 0" class="grid grid-cols-2 gap-3 sm:gap-6 min-w-0">
                                <!-- Package Category Card -->
                                <div 
                                    v-for="pkg in filteredPackages" 
                                    :key="pkg.package_name"
                                    class="bg-white rounded-lg sm:rounded-xl border border-[#E6E1DA] flex flex-col justify-between overflow-hidden menu-card relative min-w-0"
                                >
                                    <!-- Top Image Banner & floating badge -->
                                    <div class="relative h-24 sm:h-44 w-full bg-slate-100 overflow-hidden">
                                        <img :src="getPackageImage(pkg)" class="w-full h-full object-cover transition-transform duration-500 hover:scale-105" alt="Package image" />
                                        
                                        <!-- Floating Icon Badge (Left) -->
                                        <div class="absolute top-2 left-2 w-6 h-6 sm:w-8 sm:h-8 rounded-md sm:rounded-lg flex items-center justify-center text-xs sm:text-sm border shadow-sm" :class="getCategoryIcon(pkg.package_name).colors">
                                            <i class="fas" :class="getCategoryIcon(pkg.package_name).icon"></i>
                                        </div>
 
                                        <!-- Floating Popularity Badge (Right) -->
                                        <span 
                                            v-if="getPackageBadge(pkg)" 
                                            class="absolute top-2 right-2 px-1.5 py-0.5 sm:px-2.5 sm:py-1 text-[7.5px] sm:text-[9px] font-bold border rounded-full uppercase tracking-wider shadow-xs" 
                                            :class="getPackageBadge(pkg).colors"
                                        >
                                            {{ getPackageBadge(pkg).text }}
                                        </span>
                                    </div>
 
                                    <div class="p-2 sm:p-4 flex-grow flex flex-col justify-between">
                                        <div class="space-y-2.5 sm:space-y-3">
                                            <div>
                                                <h4 class="text-[10px] sm:text-base font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wide truncate" :title="pkg.package_name">
                                                    {{ pkg.package_name }}
                                                </h4>
                                                <p class="text-[7.5px] sm:text-[10px] text-[#8C8275] uppercase tracking-wider font-light mt-0.5">
                                                    {{ t('min_requirement') }}: {{ pkg.min_order }} {{ t('pax') }}
                                                </p>
                                            </div>
 
                                            <!-- Menu highlights checklist (First 4 dishes) -->
                                            <div class="border-t border-[#FAF6F0] pt-2 sm:pt-3">
                                                <span class="text-[7.5px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mb-1.5">{{ t('menu_highlights') }}</span>
                                                <ul class="grid grid-cols-2 sm:grid-cols-2 gap-x-2 gap-y-1 text-[9px] sm:text-[11px] text-[#5C6460]">
                                                    <li 
                                                        v-for="dish in (pkg.description || '').split('\n').map(d => d.trim()).filter(d => d).slice(0, 4)" 
                                                        :key="dish"
                                                        class="flex items-center gap-1 truncate"
                                                        :title="dish"
                                                     >
                                                        <i class="fas fa-check text-[#4A6B5D] text-[8px] sm:text-[9px] shrink-0"></i>
                                                        <span class="font-light truncate">{{ dish }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
 
                                        <div class="space-y-2 sm:space-y-3 mt-3 sm:mt-5">
                                            <div class="text-[8px] sm:text-[10px] font-bold text-[#4A6B5D] bg-[#FAF9F6] border border-[#E6E1DA] py-1.5 px-2 sm:py-2 sm:px-3 rounded-md sm:rounded-lg flex flex-col xs:flex-row xs:justify-between xs:items-center gap-0.5 xs:gap-1 min-w-0">
                                                <span class="text-[7px] sm:text-[9px] font-medium text-[#8C8275] truncate">{{ t('starting_from') }}</span>
                                                <span class="text-[#4A6B5D] shrink-0">RM {{ parseFloat(pkg.price).toFixed(2) }}</span>
                                            </div>
 
                                            <Link 
                                                :href="route('menu.show', { category: pkg.package_name })"
                                                class="w-full inline-flex items-center justify-center gap-1 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-1.5 px-2 sm:py-2 sm:px-3.5 rounded-md sm:rounded-lg text-[8.5px] sm:text-xs uppercase tracking-widest transition-colors duration-200"
                                            >
                                                <i class="fas fa-search-plus text-[8px] sm:text-[10px]"></i> {{ t('view_packages') }}
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
 
                            <!-- Empty State -->
                            <div v-else class="py-16 text-center">
                                <i class="fas fa-search fa-2x text-[#8C8275] mb-4"></i>
                                <h5 class="text-[#2D3330] font-serif-luxury text-sm sm:text-xl mb-1">
                                    {{ t('no_packages_criteria') }}
                                </h5>
                                <p class="text-xs text-[#8C8275]">
                                    {{ t('adjust_search_filters_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
 
                    <!-- Right Column: Brand Guarantees & Simulator (4 cols) -->
                    <div class="lg:col-span-4 grid grid-cols-2 lg:grid-cols-1 gap-3 sm:gap-6">
                        
                        <!-- Dynamic Budget Planner Promo Card (FIXED contrast issue with bg-[#2D3330]) -->
                        <div class="bg-[#2D3330] border border-[#E6E1DA] rounded-lg sm:rounded-xl p-2.5 sm:p-4.5 text-[#FAF7F2] space-y-2.5 sm:space-y-4">
                            <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-[#4A6B5D]/20 text-[#4A6B5D] border border-[#4A6B5D]/30 flex items-center justify-center text-xs sm:text-base shrink-0">
                                <i class="fas fa-calculator text-white"></i>
                            </div>
                            <div class="space-y-1 sm:space-y-2">
                                <h4 class="text-xs sm:text-base font-normal font-serif-luxury uppercase tracking-wider text-[#FAF7F2]">
                                    {{ t('budget_planner') }}
                                </h4>
                                <p class="text-[9.5px] sm:text-[11px] text-[#FAF7F2]/80 leading-relaxed font-light">
                                    {{ t('budget_planner_desc_panel') }}
                                </p>
                            </div>
                            <Link 
                                :href="route('budget.planner')"
                                class="w-full inline-flex items-center justify-center gap-1 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-1.5 px-3 rounded-md sm:rounded-lg text-[8.5px] sm:text-xs uppercase tracking-widest transition-colors duration-200 text-center"
                            >
                                {{ t('open_planner') }} <i class="fas fa-arrow-right text-[8.5px] sm:text-10px]"></i>
                            </Link>
                        </div>
 
                        <!-- Brand Guarantees Pillars (bg-white explicitly added) -->
                        <div class="bg-white border border-[#E6E1DA] rounded-lg sm:rounded-xl p-2.5 sm:p-4.5 space-y-2.5 sm:space-y-5">
                            <h4 class="text-[10px] sm:text-xs font-bold text-[#2D3330] uppercase tracking-widest border-b border-[#E6E1DA] pb-2 sm:pb-3">
                                {{ t('smartserve_guarantees') }}
                            </h4>
                            
                            <div class="space-y-4 sm:space-y-5">
                                <!-- Pillar 1 -->
                                <div class="flex gap-2 sm:gap-3">
                                    <div class="w-6.5 h-6.5 sm:w-8 sm:h-8 rounded-md sm:rounded-lg bg-[#FAF9F6] border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-[10px] sm:text-xs shrink-0 mt-0.5">
                                        <i class="fas fa-award"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-[10px] sm:text-xs text-[#2D3330] block mb-0.5">
                                            {{ t('heritage_flavors_title') }}
                                        </span>
                                        <p class="text-[8.5px] sm:text-[10px] text-[#8C8275] font-light leading-relaxed">
                                            {{ t('heritage_flavors_desc') }}
                                        </p>
                                    </div>
                                </div>
 
                                <!-- Pillar 2 -->
                                <div class="flex gap-2 sm:gap-3">
                                    <div class="w-6.5 h-6.5 sm:w-8 sm:h-8 rounded-md sm:rounded-lg bg-[#FAF9F6] border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-[10px] sm:text-xs shrink-0 mt-0.5">
                                        <i class="fas fa-shield-virus"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-[10px] sm:text-xs text-[#2D3330] block mb-0.5">
                                            {{ t('sanitized_containment_title') }}
                                        </span>
                                        <p class="text-[8.5px] sm:text-[10px] text-[#8C8275] font-light leading-relaxed">
                                            {{ t('sanitized_containment_desc') }}
                                        </p>
                                    </div>
                                </div>
 
                                <!-- Pillar 3 -->
                                <div class="flex gap-2 sm:gap-3">
                                    <div class="w-6.5 h-6.5 sm:w-8 sm:h-8 rounded-md sm:rounded-lg bg-[#FAF9F6] border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-[10px] sm:text-xs shrink-0 mt-0.5">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-[10px] sm:text-xs text-[#2D3330] block mb-0.5">
                                            {{ t('headcount_rules_title') }}
                                        </span>
                                        <p class="text-[8.5px] sm:text-[10px] text-[#8C8275] font-light leading-relaxed">
                                            {{ t('headcount_rules_desc') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
 
                    </div>
 
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

