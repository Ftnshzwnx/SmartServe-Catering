<script setup>
import { ref, inject, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import FrontLayout from '@/Layouts/FrontLayout.vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    packages: { type: Array, default: () => [] },
});

const openDrawer = inject('openDrawer', () => {});
const { t } = useLocalization();

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
    <Head :title="`${t('our_packages')} — SmartServe Catering`" />

    <FrontLayout :canLogin="canLogin" :canRegister="canRegister">

        <!-- Hero Section -->
        <section class="py-20 bg-white border-b border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="max-w-2xl space-y-4">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-[#8C8275]">
                        <Link href="/" class="hover:text-[#4A6B5D] transition-colors duration-200">{{ t('home_nav') }}</Link>
                        <span class="text-[#D1C8BD] text-[10px] font-normal">/</span>
                        <span class="text-[#4A6B5D]">{{ t('package_nav') }}</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                        {{ t('packages_hero_title_1') }} <span class="italic text-[#4A6B5D]">{{ t('packages_hero_title_2') }}</span>
                    </h1>
                    <p class="text-[#5C6460] text-xs sm:text-sm leading-relaxed font-light max-w-lg">
                        {{ t('packages_hero_desc') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Packages Grid -->
        <section class="py-20 bg-[#FAF7F2] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-12">

                <!-- Dynamic packages from DB -->
                <template v-if="packages && packages.length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div
                            v-for="pkg in packages"
                            :key="pkg.id"
                            class="bg-white border border-[#E6E1DA] rounded-xl sm:rounded-3xl overflow-hidden shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-between"
                        >
                            <div>
                                <div class="aspect-[4/3] bg-[#EADED9] overflow-hidden relative">
                                    <img
                                        :src="getPackageImage(pkg)"
                                        :alt="pkg.package_name"
                                        class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 ease-out"
                                    />
                                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] font-bold text-[#4A6B5D] uppercase tracking-wider">
                                        {{ t('popular_tag') }}
                                    </div>
                                </div>
                                <div class="p-4 sm:p-6 lg:p-8 space-y-3 sm:space-y-4">
                                    <h2 class="text-base sm:text-xl font-normal text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ pkg.package_name }}</h2>
                                    <p class="text-[10px] sm:text-xs text-[#5C6460] font-light leading-relaxed min-h-[48px]">
                                        {{ pkg.description || 'Elevate your event with our carefully curated traditional recipes, styled and portioned to absolute perfection.' }}
                                    </p>
                                    <div class="border-t border-[#FAF7F2] pt-4 space-y-2">
                                        <div class="flex justify-between items-center text-[10px] text-[#8C8275] uppercase tracking-wider font-semibold">
                                            <span>{{ t('min_booking') }}</span>
                                            <span class="text-[#2D3330]">{{ pkg.min_order }} pax</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6 lg:p-8 pt-0 border-t border-[#FAF7F2] flex flex-row items-center justify-between gap-4 mt-auto">
                                <div>
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider font-semibold block">{{ t('starting_from') }}</span>
                                    <span class="text-sm sm:text-lg font-bold text-[#4A6B5D]">RM {{ parseFloat(pkg.price).toFixed(2) }}<span class="text-[10px] sm:text-xs font-normal text-[#8C8275]">/pax</span></span>
                                </div>
                                <Link
                                    :href="route('menu.show', pkg.package_name)"
                                    class="w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-4 py-2 sm:px-5 sm:py-2.5 rounded-lg sm:rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 text-center"
                                >
                                    {{ t('select_package') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Fallback static packages -->
                <template v-else>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <!-- Mockup 1 -->
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl overflow-hidden shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <div class="aspect-[4/3] bg-[#EADED9] overflow-hidden relative">
                                    <img src="/img/hero_catering.png" alt="Wedding Silver Package" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 ease-out" />
                                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] font-bold text-[#4A6B5D] uppercase tracking-wider">{{ t('popular_tag') }}</div>
                                </div>
                                <div class="p-6 lg:p-8 space-y-4">
                                    <h2 class="text-xl font-normal text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('mock_pkg_1_name') }}</h2>
                                    <p class="text-xs text-[#5C6460] font-light leading-relaxed min-h-[48px]">{{ t('mock_pkg_1_desc') }}</p>
                                    <div class="border-t border-[#FAF7F2] pt-4">
                                        <div class="flex justify-between items-center text-[10px] text-[#8C8275] uppercase tracking-wider font-semibold">
                                            <span>{{ t('min_booking') }}</span><span class="text-[#2D3330]">500 pax</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 lg:p-8 pt-0 border-t border-[#FAF7F2] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider font-semibold block">{{ t('starting_from') }}</span>
                                    <span class="text-lg font-bold text-[#4A6B5D]">RM 15.00<span class="text-xs font-normal text-[#8C8275]">/pax</span></span>
                                </div>
                                <Link :href="route('menu.index')" class="w-full sm:w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-5 py-3 sm:py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 text-center">{{ t('select_package') }}</Link>
                            </div>
                        </div>

                        <!-- Mockup 2 -->
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl overflow-hidden shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <div class="aspect-[4/3] bg-[#EADED9] overflow-hidden relative">
                                    <img src="/img/catering_dish.png" alt="Corporate Bronze Package" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 ease-out" />
                                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] font-bold text-[#4A6B5D] uppercase tracking-wider">{{ t('premium_tag') }}</div>
                                </div>
                                <div class="p-6 lg:p-8 space-y-4">
                                    <h2 class="text-xl font-normal text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('mock_pkg_2_name') }}</h2>
                                    <p class="text-xs text-[#5C6460] font-light leading-relaxed min-h-[48px]">{{ t('mock_pkg_2_desc') }}</p>
                                    <div class="border-t border-[#FAF7F2] pt-4">
                                        <div class="flex justify-between items-center text-[10px] text-[#8C8275] uppercase tracking-wider font-semibold">
                                            <span>{{ t('min_booking') }}</span><span class="text-[#2D3330]">100 pax</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 lg:p-8 pt-0 border-t border-[#FAF7F2] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider font-semibold block">{{ t('starting_from') }}</span>
                                    <span class="text-lg font-bold text-[#4A6B5D]">RM 25.00<span class="text-xs font-normal text-[#8C8275]">/pax</span></span>
                                </div>
                                <Link :href="route('menu.index')" class="w-full sm:w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-5 py-3 sm:py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 text-center">{{ t('select_package') }}</Link>
                            </div>
                        </div>

                        <!-- Mockup 3 -->
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl overflow-hidden shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <div class="aspect-[4/3] bg-[#EADED9] overflow-hidden relative">
                                    <img src="/img/aqiqah_catering.png" alt="Aqiqah & Gathering Package" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 ease-out" />
                                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] font-bold text-[#4A6B5D] uppercase tracking-wider">{{ t('value_tag') }}</div>
                                </div>
                                <div class="p-6 lg:p-8 space-y-4">
                                    <h2 class="text-xl font-normal text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('mock_pkg_3_name') }}</h2>
                                    <p class="text-xs text-[#5C6460] font-light leading-relaxed min-h-[48px]">{{ t('mock_pkg_3_desc') }}</p>
                                    <div class="border-t border-[#FAF7F2] pt-4">
                                        <div class="flex justify-between items-center text-[10px] text-[#8C8275] uppercase tracking-wider font-semibold">
                                            <span>{{ t('min_booking') }}</span><span class="text-[#2D3330]">100 pax</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 lg:p-8 pt-0 border-t border-[#FAF7F2] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider font-semibold block">{{ t('starting_from') }}</span>
                                    <span class="text-lg font-bold text-[#4A6B5D]">RM 18.00<span class="text-xs font-normal text-[#8C8275]">/pax</span></span>
                                </div>
                                <Link :href="route('menu.index')" class="w-full sm:w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-5 py-3 sm:py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 text-center">{{ t('select_package') }}</Link>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- CTA Banner -->
                <div class="bg-[#1C201E] rounded-xl sm:rounded-3xl p-6 sm:p-10 text-center space-y-4 sm:space-y-5">
                    <p class="text-[10px] text-[#A8C5B8] uppercase tracking-widest font-semibold">{{ t('special_package') }}</p>
                    <h3 class="text-xl sm:text-3xl font-light font-serif-luxury text-white leading-tight">{{ t('want_something_special') }}</h3>
                    <p class="text-[#8E9993] text-xs sm:text-sm font-light max-w-md mx-auto">{{ t('custom_menu_desc') }}</p>
                    <Link href="/contact" class="inline-block bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-4 py-2.5 sm:px-10 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-colors duration-200 w-full sm:w-auto text-center">
                        {{ t('contact_us') }}
                    </Link>
                </div>

            </div>
        </section>

    </FrontLayout>
</template>
