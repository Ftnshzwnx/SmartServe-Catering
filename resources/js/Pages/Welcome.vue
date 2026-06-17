<script setup>
import { inject, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import FrontLayout from '@/Layouts/FrontLayout.vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    reviews: { type: Array, default: () => [] },
});

const { t } = useLocalization();

// Inject openDrawer from FrontLayout
const openDrawer = inject('openDrawer', () => {});

// Infinite scrolling marquee setup
const marqueeReviews = computed(() => {
    if (!props.reviews || props.reviews.length === 0) return [];
    
    // We want enough reviews so the scroll loop is smooth. Let's repeat the array until we have at least 10 items.
    let list = [...props.reviews];
    while (list.length < 10) {
        list = [...list, ...props.reviews];
    }
    // For infinite scroll, we duplicate the list so the end of first half transitions seamlessly into start of second half.
    return [...list, ...list];
});

const marqueeDuration = computed(() => {
    const itemsCount = marqueeReviews.value.length / 2;
    // 10 seconds per card for a very smooth slow motion
    return `${itemsCount * 10}s`;
});
</script>

<template>
    <Head title="SmartServe Catering — Premium Catering Terengganu" />

    <FrontLayout :canLogin="canLogin" :canRegister="canRegister">

        <!-- Asymmetrical Hero Section -->
        <header class="relative overflow-hidden py-16 lg:py-28 font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">

                    <!-- Left Column: Copy -->
                    <div class="lg:col-span-7 space-y-8 text-left z-10">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#EBEFEF] text-[#4A6B5D] text-xs font-semibold tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#4A6B5D] animate-pulse"></span>
                            {{ t('hero_subtitle') }}
                        </div>

                        <h1 class="text-3xl sm:text-5xl lg:text-7xl font-light tracking-tight text-[#1C201E] leading-[1.1] font-serif-luxury">
                            {{ t('hero_title_1') }} <br />
                            <span class="italic text-[#4A6B5D]">{{ t('hero_title_2') }}</span><br />
                            {{ t('hero_title_3') }}
                        </h1>

                        <p class="text-xs sm:text-base lg:text-lg text-[#5C6460] leading-relaxed max-w-xl font-light">
                            {{ t('hero_desc') }}
                        </p>

                        <div class="flex flex-col sm:flex-row gap-2.5 sm:gap-4 pt-4 items-stretch sm:items-center w-full sm:w-auto">
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('login')"
                                class="w-full sm:w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center px-6 py-2.5 sm:px-8 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md flex items-center justify-center whitespace-nowrap"
                            >
                                {{ t('begin_experience') }}
                            </Link>
                            <Link
                                v-else
                                :href="route('packages')"
                                class="w-full sm:w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center px-6 py-2.5 sm:px-8 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md flex items-center justify-center whitespace-nowrap"
                            >
                                {{ t('begin_experience') }}
                            </Link>
                            <Link
                                href="/about"
                                class="w-full sm:w-auto bg-transparent hover:bg-[#FAF7F2] border border-[#D1C8BD] text-[#2D3330] text-center px-6 py-2.5 sm:px-8 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 flex items-center justify-center whitespace-nowrap"
                            >
                                {{ t('read_story') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Right Column: Framed Hero Image -->
                    <div class="lg:col-span-5 flex justify-center lg:justify-end">
                        <div class="image-frame w-full max-w-[420px] aspect-[4/5] bg-[#EADED9] overflow-hidden shadow-lg z-10">
                            <img
                                src="/img/hero_catering.png"
                                alt="Minimalist Luxury Buffet Catering Design"
                                class="w-full h-full object-cover grayscale-[10%] hover:scale-105 transition-transform duration-700 ease-out"
                            />
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- About Teaser Section -->
        <section class="py-24 bg-white border-y border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-16">
                <!-- Two-Column Header -->
                <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                    <div class="lg:col-span-5 space-y-4">
                        <span class="text-[#4A6B5D] font-bold text-xs uppercase tracking-widest block">{{ t('about_subtitle') }}</span>
                        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                            {{ t('about_title_1') }} <br />
                            <span class="italic text-[#4A6B5D]">{{ t('about_title_2') }}</span>
                        </h2>
                    </div>
                    <div class="lg:col-span-7 space-y-6 text-[#5C6460] font-light leading-relaxed text-xs sm:text-sm md:text-base">
                        <p>{{ t('about_desc_1') }}</p>
                        <p>{{ t('about_desc_2') }}</p>
                        <Link href="/about" class="inline-flex items-center gap-2 text-xs font-semibold text-[#4A6B5D] uppercase tracking-widest hover:gap-3 transition-all duration-200">
                            {{ t('read_more') }} <i class="fas fa-arrow-right text-[10px]"></i>
                        </Link>
                    </div>
                </div>

                <!-- Milestone Counter Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 py-8 sm:py-12 border-y border-[#E6E1DA]/60">
                    <div class="text-center space-y-2">
                        <div class="text-2xl sm:text-3xl lg:text-5xl font-light text-[#4A6B5D] font-serif-luxury">500+</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest">{{ t('stats_events') }}</div>
                    </div>
                    <div class="text-center space-y-2">
                        <div class="text-2xl sm:text-3xl lg:text-5xl font-light text-[#4A6B5D] font-serif-luxury">10,000+</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest">{{ t('stats_guests') }}</div>
                    </div>
                    <div class="text-center space-y-2">
                        <div class="text-2xl sm:text-3xl lg:text-5xl font-light text-[#4A6B5D] font-serif-luxury">100%</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest">{{ t('stats_halal') }}</div>
                    </div>
                    <div class="text-center space-y-2">
                        <div class="text-2xl sm:text-3xl lg:text-5xl font-light text-[#4A6B5D] font-serif-luxury">15+</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest">{{ t('stats_recipes') }}</div>
                    </div>
                </div>

                <!-- Brand Values Cards -->
                <div class="space-y-6 sm:space-y-8">
                    <div class="text-center">
                        <span class="text-xs font-semibold text-[#8C8275] tracking-widest uppercase block mb-2">{{ t('values_title') }}</span>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4 sm:gap-8">
                        <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-2xl p-4 sm:p-6 space-y-2 sm:space-y-3 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-sm font-bold">01</div>
                            <h3 class="text-sm sm:text-lg font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('value_1_title') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('value_1_desc') }}</p>
                        </div>
                        <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-2xl p-4 sm:p-6 space-y-2 sm:space-y-3 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-sm font-bold">02</div>
                            <h3 class="text-sm sm:text-lg font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('value_2_title') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('value_2_desc') }}</p>
                        </div>
                        <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-2xl p-4 sm:p-6 space-y-2 sm:space-y-3 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-sm font-bold">03</div>
                            <h3 class="text-sm sm:text-lg font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('value_3_title') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('value_3_desc') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Packages CTA Section -->
        <section class="py-20 bg-[#FAF7F2] border-b border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-5">
                        <span class="text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('premium_selection') }}</span>
                        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                            {{ t('packages_title') }}
                        </h2>
                        <p class="text-xs sm:text-sm text-[#5C6460] font-light leading-relaxed max-w-md">
                            {{ t('packages_subtitle') }}
                        </p>
                        <Link
                            href="/packages"
                            class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-4 py-2.5 sm:px-8 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md"
                        >
                            {{ t('view_all_packages') }} <i class="fas fa-arrow-right text-[10px]"></i>
                        </Link>
                    </div>
                    <!-- Mini preview cards -->
                    <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
                        <div class="bg-white border border-[#E6E1DA] rounded-xl sm:rounded-2xl overflow-hidden shadow-xs hover:-translate-y-1 transition-transform duration-300">
                            <div class="aspect-square bg-[#EADED9] overflow-hidden">
                                <img src="/img/hero_catering.png" alt="Wedding Package" class="w-full h-full object-cover hover:scale-110 transition-transform duration-500" />
                            </div>
                            <div class="p-2 sm:p-3">
                                <p class="text-[8px] sm:text-[10px] font-bold text-[#1C201E] uppercase tracking-wider">Wedding</p>
                                <p class="text-[8px] sm:text-[10px] text-[#8C8275] font-light mt-0.5">dari RM 15/pax</p>
                            </div>
                        </div>
                        <div class="bg-white border border-[#E6E1DA] rounded-xl sm:rounded-2xl overflow-hidden shadow-xs hover:-translate-y-1 transition-transform duration-300 mt-4 sm:mt-6">
                            <div class="aspect-square bg-[#EADED9] overflow-hidden">
                                <img src="/img/catering_dish.png" alt="Corporate Package" class="w-full h-full object-cover hover:scale-110 transition-transform duration-500" />
                            </div>
                            <div class="p-2 sm:p-3">
                                <p class="text-[8px] sm:text-[10px] font-bold text-[#1C201E] uppercase tracking-wider">Corporate</p>
                                <p class="text-[8px] sm:text-[10px] text-[#8C8275] font-light mt-0.5">dari RM 25/pax</p>
                            </div>
                        </div>
                        <div class="bg-white border border-[#E6E1DA] rounded-xl sm:rounded-2xl overflow-hidden shadow-xs hover:-translate-y-1 transition-transform duration-300">
                            <div class="aspect-square bg-[#EADED9] overflow-hidden">
                                <img src="/img/aqiqah_catering.png" alt="Aqiqah Package" class="w-full h-full object-cover hover:scale-110 transition-transform duration-500" />
                            </div>
                            <div class="p-2 sm:p-3">
                                <p class="text-[8px] sm:text-[10px] font-bold text-[#1C201E] uppercase tracking-wider">Aqiqah</p>
                                <p class="text-[8px] sm:text-[10px] text-[#8C8275] font-light mt-0.5">dari RM 18/pax</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Booking Policies Section -->
        <section id="policies" class="py-24 bg-white border-t border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-14">

                <!-- Section Header -->
                <div class="text-center space-y-3">
                    <span class="text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('booking_policies') }}</span>
                    <h2 class="text-3xl lg:text-4xl font-light text-[#1C201E] font-serif-luxury uppercase tracking-wider">
                        {{ t('booking_policies_title') }}
                    </h2>
                    <p class="text-xs text-[#8C8275] max-w-md mx-auto font-light leading-relaxed">
                        {{ t('booking_policies_subtitle') }}
                    </p>
                </div>

                <!-- Policy Cards Grid -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">

                    <!-- Policy Card 1: Deposit -->
                    <div class="group bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-4 sm:p-7 space-y-3 sm:space-y-5 hover:-translate-y-1 hover:shadow-lg hover:border-[#4A6B5D]/30 transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-2xl bg-[#EBEFEF] group-hover:bg-[#4A6B5D] text-[#4A6B5D] group-hover:text-white flex items-center justify-center text-xs sm:text-base transition-all duration-300">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest bg-white border border-[#E6E1DA] px-2.5 py-1 rounded-full">01</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('policy_header_1') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('policy_desc_1') }}</p>
                        </div>
                    </div>

                    <!-- Policy Card 2: Cancellation -->
                    <div class="group bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-4 sm:p-7 space-y-3 sm:space-y-5 hover:-translate-y-1 hover:shadow-lg hover:border-[#4A6B5D]/30 transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-2xl bg-[#EBEFEF] group-hover:bg-[#4A6B5D] text-[#4A6B5D] group-hover:text-white flex items-center justify-center text-xs sm:text-base transition-all duration-300">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest bg-white border border-[#E6E1DA] px-2.5 py-1 rounded-full">02</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('policy_header_2') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('policy_desc_2') }}</p>
                        </div>
                    </div>

                    <!-- Policy Card 3: Menu Change -->
                    <div class="group bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-4 sm:p-7 space-y-3 sm:space-y-5 hover:-translate-y-1 hover:shadow-lg hover:border-[#4A6B5D]/30 transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-2xl bg-[#EBEFEF] group-hover:bg-[#4A6B5D] text-[#4A6B5D] group-hover:text-white flex items-center justify-center text-xs sm:text-base transition-all duration-300">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest bg-white border border-[#E6E1DA] px-2.5 py-1 rounded-full">03</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('policy_header_3') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('policy_desc_3') }}</p>
                        </div>
                    </div>

                    <!-- Policy Card 4: Guest Count -->
                    <div class="group bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-4 sm:p-7 space-y-3 sm:space-y-5 hover:-translate-y-1 hover:shadow-lg hover:border-[#4A6B5D]/30 transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-2xl bg-[#EBEFEF] group-hover:bg-[#4A6B5D] text-[#4A6B5D] group-hover:text-white flex items-center justify-center text-xs sm:text-base transition-all duration-300">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest bg-white border border-[#E6E1DA] px-2.5 py-1 rounded-full">04</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('policy_header_4') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('policy_desc_4') }}</p>
                        </div>
                    </div>

                    <!-- Policy Card 5: Halal -->
                    <div class="group bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-4 sm:p-7 space-y-3 sm:space-y-5 hover:-translate-y-1 hover:shadow-lg hover:border-[#4A6B5D]/30 transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-2xl bg-[#EBEFEF] group-hover:bg-[#4A6B5D] text-[#4A6B5D] group-hover:text-white flex items-center justify-center text-xs sm:text-base transition-all duration-300">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest bg-white border border-[#E6E1DA] px-2.5 py-1 rounded-full">05</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('policy_header_5') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('policy_desc_5') }}</p>
                        </div>
                    </div>

                    <!-- Policy Card 6: Dispute -->
                    <div class="group bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-4 sm:p-7 space-y-3 sm:space-y-5 hover:-translate-y-1 hover:shadow-lg hover:border-[#4A6B5D]/30 transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-2xl bg-[#EBEFEF] group-hover:bg-[#4A6B5D] text-[#4A6B5D] group-hover:text-white flex items-center justify-center text-xs sm:text-base transition-all duration-300">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest bg-white border border-[#E6E1DA] px-2.5 py-1 rounded-full">06</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('policy_header_6') }}</h3>
                            <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed font-light">{{ t('policy_desc_6') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Trust Footer Banner -->
                <div class="bg-[#1C201E] rounded-xl sm:rounded-3xl p-4 sm:p-8 flex flex-col md:flex-row items-center justify-between gap-4 sm:gap-6">
                    <div class="flex items-center gap-3 sm:gap-4 w-full md:w-auto">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-[#4A6B5D]/30 text-[#A8C5B8] flex items-center justify-center text-sm sm:text-lg flex-shrink-0">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div>
                            <p class="text-white text-xs sm:text-sm font-semibold font-serif-luxury uppercase tracking-wider">{{ t('agreement_banner_title') }}</p>
                            <p class="text-[#8E9993] text-[10px] sm:text-xs font-light mt-0.5 leading-relaxed">
                                {{ t('agreement_banner_desc') }}
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/contact"
                        class="w-full md:w-auto flex-shrink-0 bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-4 py-2.5 sm:px-7 sm:py-3 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-colors duration-200 whitespace-nowrap text-center justify-center"
                    >
                        {{ t('ask_us') }}
                    </Link>
                </div>

            </div>
        </section>

        <!-- FAQ CTA Section -->
        <section class="py-16 sm:py-20 bg-[#FAF7F2] border-t border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-8 sm:gap-12 items-center">
                    <div class="space-y-4 sm:space-y-5">
                        <span class="text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('faq') }}</span>
                        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                            {{ t('faq_title') }}
                        </h2>
                        <p class="text-xs sm:text-sm text-[#5C6460] font-light leading-relaxed max-w-md">
                            {{ t('faq_subtitle') }}
                        </p>
                        <Link
                            href="/faq"
                            class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-4 py-2.5 sm:px-8 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md w-full sm:w-auto text-center justify-center"
                        >
                            {{ t('view_all_questions') }} <i class="fas fa-arrow-right text-[10px]"></i>
                        </Link>
                    </div>
                    <!-- Quick FAQ preview -->
                    <div class="space-y-2.5 sm:space-y-3">
                        <div class="bg-white border border-[#E6E1DA] rounded-xl px-4 py-3 sm:rounded-2xl sm:px-6 sm:py-4 flex items-center justify-between shadow-xs hover:border-[#4A6B5D]/40 transition-colors">
                            <span class="text-xs sm:text-sm font-medium text-[#1C201E]">{{ t('q_min_guests') }}</span>
                            <i class="fas fa-chevron-right text-[10px] text-[#8C8275]"></i>
                        </div>
                        <div class="bg-white border border-[#E6E1DA] rounded-xl px-4 py-3 sm:rounded-2xl sm:px-6 sm:py-4 flex items-center justify-between shadow-xs hover:border-[#4A6B5D]/40 transition-colors">
                            <span class="text-xs sm:text-sm font-medium text-[#1C201E]">{{ t('q_halal') }}</span>
                            <i class="fas fa-chevron-right text-[10px] text-[#8C8275]"></i>
                        </div>
                        <div class="bg-white border border-[#E6E1DA] rounded-xl px-4 py-3 sm:rounded-2xl sm:px-6 sm:py-4 flex items-center justify-between shadow-xs hover:border-[#4A6B5D]/40 transition-colors">
                            <span class="text-xs sm:text-sm font-medium text-[#1C201E]">{{ t('q_menu_change') }}</span>
                            <i class="fas fa-chevron-right text-[10px] text-[#8C8275]"></i>
                        </div>
                        <div class="bg-white border border-[#E6E1DA] rounded-xl px-4 py-3 sm:rounded-2xl sm:px-6 sm:py-4 flex items-center justify-between shadow-xs hover:border-[#4A6B5D]/40 transition-colors">
                            <span class="text-xs sm:text-sm font-medium text-[#1C201E]">{{ t('q_payment') }}</span>
                            <i class="fas fa-chevron-right text-[10px] text-[#8C8275]"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Reviews & Testimonials Section -->
        <section v-if="reviews && reviews.length > 0" id="testimonials" class="py-16 sm:py-24 bg-[#FAF7F2] border-t border-[#E6E1DA] font-sans-modern overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-10 sm:space-y-14">

                <!-- Section Header + Average Rating -->
                <div class="flex flex-col lg:flex-row items-center justify-between gap-6 sm:gap-10">
                    <div class="space-y-2.5 sm:space-y-3 text-center lg:text-left">
                        <span class="text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('reviews_ratings') }}</span>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-light text-[#1C201E] font-serif-luxury uppercase tracking-wider">
                            {{ t('customer_testimonials') }}
                        </h2>
                        <p class="text-[10px] sm:text-xs text-[#8C8275] max-w-md">
                            {{ t('testimonials_subtitle') }}
                        </p>
                    </div>

                    <!-- Average Rating Badge -->
                    <div class="flex-shrink-0 bg-white border border-[#E6E1DA] rounded-xl sm:rounded-3xl px-6 py-4 sm:px-8 sm:py-6 text-center shadow-sm min-w-[120px] sm:min-w-[160px] w-full sm:w-auto">
                        <div class="text-3xl sm:text-5xl font-light text-[#2D3330] font-serif-luxury leading-none mb-1 sm:mb-2">
                            {{ (reviews.reduce((s, r) => s + r.rating, 0) / reviews.length).toFixed(1) }}
                        </div>
                        <div class="flex items-center justify-center gap-0.5 mb-1.5 sm:mb-2">
                            <i v-for="s in 5" :key="s" class="fa-star text-xs sm:text-sm"
                               :class="s <= Math.round(reviews.reduce((acc, r) => acc + r.rating, 0) / reviews.length) ? 'fas text-[#C5A880]' : 'far text-zinc-200'">
                            </i>
                        </div>
                        <span class="text-[8px] sm:text-[10px] text-[#8C8275] uppercase tracking-widest font-semibold">{{ reviews.length }} {{ t('reviews_ratings') }}</span>
                    </div>
                </div>

                <!-- Review Cards Marquee (Infinite scrolling left-to-right) -->
                <div class="relative w-full overflow-hidden py-4 select-none">
                    <!-- Fade gradients on sides for high-end look -->
                    <div class="absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-[#FAF7F2] to-transparent z-10 pointer-events-none"></div>
                    <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-[#FAF7F2] to-transparent z-10 pointer-events-none"></div>

                    <!-- Scrolling Track -->
                    <div 
                        class="flex gap-6 w-max animate-marquee-right hover:[animation-play-state:paused]"
                        :style="{ animationDuration: marqueeDuration }"
                    >
                        <div
                            v-for="(review, index) in marqueeReviews"
                            :key="`${review.id}-${index}`"
                            class="w-[280px] sm:w-[360px] flex-shrink-0 bg-white border border-[#E6E1DA] rounded-xl p-4 sm:rounded-2xl sm:p-6 shadow-sm flex flex-col justify-between space-y-4 sm:space-y-5 hover:border-[#C5A880]/50 hover:shadow-md transition-all duration-300"
                        >
                            <!-- Top: Stars + Quote -->
                            <div class="space-y-2.5 sm:space-y-3">
                                <!-- Stars row -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-0.5">
                                        <i v-for="star in 5" :key="star" class="fa-star text-xs sm:text-sm"
                                           :class="star <= review.rating ? 'fas text-[#C5A880]' : 'far text-zinc-200'">
                                        </i>
                                    </div>
                                    <!-- Decorative quote mark -->
                                    <span class="text-2xl sm:text-4xl text-[#E6E1DA] font-serif leading-none select-none">"</span>
                                </div>

                                <!-- Review text -->
                                <p class="text-[10px] sm:text-xs text-[#5C6460] leading-relaxed italic">
                                    "{{ review.review_text || (t('current_language') === 'en' ? 'Excellent catering service! Highly recommended.' : 'Servis katering yang sangat baik! Sangat disyorkan.') }}"
                                </p>
                            </div>

                            <!-- Bottom: Customer info + admin reply -->
                            <div class="space-y-3">
                                <div class="border-t border-[#EBEFEF] pt-3 sm:pt-4 flex items-center gap-2 sm:gap-3">
                                    <!-- Avatar initial -->
                                    <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-full bg-[#4A6B5D] text-white flex items-center justify-center text-[10px] sm:text-xs font-bold uppercase flex-shrink-0">
                                        {{ (review.user?.full_name || review.user?.name || 'C').charAt(0) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-bold text-[#2D3330] text-[10px] sm:text-[11px] uppercase tracking-wider block truncate">
                                            {{ review.user?.full_name || review.user?.name || 'Verified Customer' }}
                                        </span>
                                        <span class="text-[9px] sm:text-[10px] block truncate">
                                            {{ review.order?.package_name || 'SmartServe Package' }}
                                        </span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] text-[#C6C1B9] flex-shrink-0">
                                        {{ new Date(review.created_at).toLocaleDateString('ms-MY', { month: 'short', year: 'numeric' }) }}
                                    </span>
                                </div>

                                <!-- Admin Reply -->
                                <div v-if="review.admin_reply" class="bg-[#F5F7F6] border border-[#E0E8E4] rounded-lg p-2.5 sm:rounded-xl sm:p-3 space-y-1">
                                    <div class="flex items-center gap-1.5 text-[8px] sm:text-[9px] font-bold text-[#4A6B5D] uppercase tracking-widest">
                                        <i class="fas fa-reply text-[8px]"></i> SmartServe
                                    </div>
                                    <p class="text-[9px] sm:text-[10px] text-[#5C6460] leading-relaxed">{{ review.admin_reply }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>


        <!-- Contact CTA Section -->
        <section id="contact" class="py-16 sm:py-24 bg-white border-t border-[#E6E1DA] font-sans-modern text-center">
            <div class="max-w-2xl mx-auto px-6 space-y-5 sm:space-y-6">
                <span class="text-[10px] text-[#4A6B5D] uppercase tracking-widest font-bold block">{{ t('contact_us') }}</span>
                <h2 class="text-2xl sm:text-3xl lg:text-5xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                    {{ t('contact_title') }}
                </h2>
                <p class="text-[#5C6460] text-xs sm:text-sm font-light leading-relaxed max-w-lg mx-auto">
                    {{ t('contact_subtitle') }}
                </p>
                <div class="flex flex-col items-stretch sm:flex-row sm:items-center justify-center gap-3 pt-4 w-full">
                    <Link
                        href="/contact"
                        class="w-full sm:w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center px-6 py-2.5 sm:px-10 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md flex items-center justify-center whitespace-nowrap"
                    >
                        {{ t('contact_submit') }}
                    </Link>
                    <a
                        :href="'https://wa.me/' + ($page.props.settings.contact_phone || '019-2094670').replace(/[^0-9]/g, '').replace(/^0/, '60')"
                        target="_blank"
                        class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-center px-6 py-2.5 sm:px-10 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 flex items-center justify-center gap-1.5 sm:gap-2 shadow-md whitespace-nowrap"
                    >
                        <i class="fab fa-whatsapp text-xs sm:text-sm flex-shrink-0"></i>
                        {{ t('contact_whatsapp_btn') }}
                    </a>
                </div>
            </div>
        </section>

    </FrontLayout>
</template>

<style scoped>
@keyframes marquee-right {
    0% {
        transform: translateX(-50%);
    }
    100% {
        transform: translateX(0%);
    }
}

.animate-marquee-right {
    animation: marquee-right linear infinite;
}
</style>
