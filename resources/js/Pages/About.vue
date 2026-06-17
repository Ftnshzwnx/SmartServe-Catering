<script setup>
import { Head, Link } from '@inertiajs/vue3';
import FrontLayout from '@/Layouts/FrontLayout.vue';
import { useLocalization } from '@/Composables/useLocalization';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
});

const { t } = useLocalization();
</script>

<template>
    <Head title="About Us — SmartServe Catering" />

    <FrontLayout :canLogin="canLogin" :canRegister="canRegister">
        <!-- Page Hero -->
        <section class="relative bg-[#1C201E] text-white overflow-hidden py-16 sm:py-28 lg:py-40 font-sans-modern">
            <div class="absolute inset-0 opacity-10 bg-[url('/img/hero_catering.png')] bg-cover bg-center"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-[#1C201E] via-[#1C201E]/80 to-[#4A6B5D]/30"></div>
            <div class="relative max-w-7xl mx-auto px-6 lg:px-8 text-center space-y-4 sm:space-y-6 z-10">
                <div class="flex items-center justify-center gap-2 text-[10px] sm:text-xs font-semibold uppercase tracking-widest text-[#A8C5B8]">
                    <Link href="/" class="hover:text-white transition-colors duration-200">{{ t('home_nav') }}</Link>
                    <span class="text-white/30 text-[9px] sm:text-[10px] font-normal">/</span>
                    <span class="text-white/60">{{ t('about_nav') }}</span>
                </div>
                <h1 class="text-3xl sm:text-5xl lg:text-7xl font-light tracking-tight font-serif-luxury leading-[1.1]">
                    {{ t('about_title_1') }} <br />
                    <span class="italic text-[#A8C5B8]">{{ t('about_title_2') }}</span>
                </h1>
                <p class="text-xs sm:text-base text-[#8E9993] max-w-xl mx-auto font-light leading-relaxed">
                    {{ t('about_desc_1') }}
                </p>
            </div>
        </section>

        <!-- Our Story Section -->
        <section class="py-12 sm:py-24 bg-white border-b border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-12 gap-6 sm:gap-12 lg:gap-16 items-center">
                    <!-- Left: Image -->
                    <div class="lg:col-span-5">
                        <div class="relative">
                            <div class="aspect-[4/5] bg-[#EADED9] overflow-hidden rounded-xl sm:rounded-2xl shadow-md sm:shadow-lg">
                                <img
                                    src="/img/hero_catering.png"
                                    alt="SmartServe Catering Story"
                                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-700 ease-out"
                                />
                            </div>
                            <!-- Floating badge -->
                            <div class="absolute -bottom-4 -right-4 bg-[#4A6B5D] text-white rounded-xl sm:rounded-2xl p-3 sm:p-5 shadow-lg sm:shadow-xl">
                                <div class="text-xl sm:text-3xl font-light font-serif-luxury">13+</div>
                                <div class="text-[8px] sm:text-[9px] uppercase tracking-widest font-semibold opacity-80 mt-0.5 sm:mt-1">{{ t('years_serving') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Story Text -->
                    <div class="lg:col-span-7 space-y-5 sm:space-y-8 mt-4 sm:mt-0">
                        <div class="space-y-2 sm:space-y-3">
                            <span class="text-[#4A6B5D] font-bold text-[10px] sm:text-xs uppercase tracking-widest block">{{ t('about_subtitle') }}</span>
                            <h2 class="text-2xl sm:text-4xl lg:text-5xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                                {{ t('about_title_1') }} <br />
                                <span class="italic text-[#4A6B5D]">{{ t('about_title_2') }}</span>
                            </h2>
                        </div>
                        <div class="space-y-3 sm:space-y-5 text-[#5C6460] font-light leading-relaxed text-xs sm:text-sm md:text-base">
                            <p>{{ t('about_desc_1') }}</p>
                            <p>{{ t('about_desc_2') }}</p>
                            <p class="text-xs sm:text-sm text-[#8C8275] italic border-l-2 border-[#4A6B5D] pl-3 sm:pl-4">
                                {{ t('about_quote') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Milestone Stats -->
        <section class="py-12 sm:py-20 bg-[#FAF7F2] border-b border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center mb-8 sm:mb-14 space-y-1.5 sm:space-y-2">
                    <span class="text-[10px] sm:text-xs font-semibold text-[#8C8275] tracking-widest uppercase block">{{ t('milestones_tag') }}</span>
                    <h2 class="text-xl sm:text-3xl lg:text-4xl font-light text-[#1C201E] font-serif-luxury uppercase tracking-wider">
                        {{ t('achievements_title') }}
                    </h2>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-6">
                    <div class="text-center space-y-1.5 sm:space-y-3 bg-white border border-[#E6E1DA] rounded-xl sm:rounded-2xl p-4 sm:p-8 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                        <div class="text-2xl sm:text-4xl lg:text-5xl font-light text-[#4A6B5D] font-serif-luxury">500+</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest">{{ t('stats_events') }}</div>
                    </div>
                    <div class="text-center space-y-1.5 sm:space-y-3 bg-white border border-[#E6E1DA] rounded-xl sm:rounded-2xl p-4 sm:p-8 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                        <div class="text-2xl sm:text-4xl lg:text-5xl font-light text-[#4A6B5D] font-serif-luxury">10,000+</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest">{{ t('stats_guests') }}</div>
                    </div>
                    <div class="text-center space-y-1.5 sm:space-y-3 bg-white border border-[#E6E1DA] rounded-xl sm:rounded-2xl p-4 sm:p-8 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                        <div class="text-2xl sm:text-4xl lg:text-5xl font-light text-[#4A6B5D] font-serif-luxury">100%</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest">{{ t('stats_halal') }}</div>
                    </div>
                    <div class="text-center space-y-1.5 sm:space-y-3 bg-white border border-[#E6E1DA] rounded-xl sm:rounded-2xl p-4 sm:p-8 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                        <div class="text-2xl sm:text-4xl lg:text-5xl font-light text-[#4A6B5D] font-serif-luxury">15+</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest">{{ t('stats_recipes') }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Brand Values -->
        <section class="py-12 sm:py-24 bg-white border-b border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-8 sm:space-y-14">
                <div class="text-center space-y-2 sm:space-y-3">
                    <span class="text-[10px] sm:text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('values_title') }}</span>
                    <h2 class="text-xl sm:text-3xl lg:text-4xl font-light text-[#1C201E] font-serif-luxury uppercase tracking-wider">
                        {{ t('values_title') }}
                    </h2>
                    <p class="text-[11px] sm:text-xs text-[#8C8275] max-w-md mx-auto font-light leading-relaxed">
                        {{ t('values_desc') }}
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-8">
                    <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-5 sm:p-8 space-y-3 sm:space-y-4 hover:-translate-y-1 sm:hover:-translate-y-2 hover:shadow-md sm:hover:shadow-lg transition-all duration-300 group">
                        <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-base sm:text-xl font-bold group-hover:bg-[#4A6B5D] group-hover:text-white transition-colors duration-300">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3 class="text-sm sm:text-xl font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('value_1_title') }}</h3>
                        <p class="text-xs sm:text-sm text-[#5C6460] leading-relaxed font-light">{{ t('value_1_desc') }}</p>
                    </div>
                    <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-5 sm:p-8 space-y-3 sm:space-y-4 hover:-translate-y-1 sm:hover:-translate-y-2 hover:shadow-md sm:hover:shadow-lg transition-all duration-300 group">
                        <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-base sm:text-xl font-bold group-hover:bg-[#4A6B5D] group-hover:text-white transition-colors duration-300">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="text-sm sm:text-xl font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('value_2_title') }}</h3>
                        <p class="text-xs sm:text-sm text-[#5C6460] leading-relaxed font-light">{{ t('value_2_desc') }}</p>
                    </div>
                    <div class="bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-5 sm:p-8 space-y-3 sm:space-y-4 hover:-translate-y-1 sm:hover:-translate-y-2 hover:shadow-md sm:hover:shadow-lg transition-all duration-300 group sm:col-span-2 md:col-span-1">
                        <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-base sm:text-xl font-bold group-hover:bg-[#4A6B5D] group-hover:text-white transition-colors duration-300">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="text-sm sm:text-xl font-semibold text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('value_3_title') }}</h3>
                        <p class="text-xs sm:text-sm text-[#5C6460] leading-relaxed font-light">{{ t('value_3_desc') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Timeline / Journey Section -->
        <section class="py-12 sm:py-24 bg-[#FAF7F2] font-sans-modern">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 space-y-8 sm:space-y-14">
                <div class="text-center space-y-2 sm:space-y-3">
                    <span class="text-[10px] sm:text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('journey_tag') || 'JOURNEY' }}</span>
                    <h2 class="text-xl sm:text-3xl lg:text-4xl font-light text-[#1C201E] font-serif-luxury uppercase tracking-wider">
                        {{ t('milestones_subtitle') }}
                    </h2>
                </div>

                <div class="relative">
                    <!-- Vertical line -->
                    <div class="absolute left-4 md:left-1/2 top-0 bottom-0 w-px bg-[#E6E1DA] -translate-x-1/2"></div>

                    <div class="space-y-8 sm:space-y-12">
                        <!-- Timeline Item 1 -->
                        <div class="relative flex flex-col md:flex-row md:items-center gap-4 sm:gap-6 md:gap-12">
                            <div class="md:w-1/2 md:text-right md:pr-10 pl-10 md:pl-0">
                                <div class="text-[9px] sm:text-[10px] text-[#4A6B5D] font-bold uppercase tracking-widest mb-0.5 sm:mb-1">2013</div>
                                <h3 class="text-sm sm:text-lg font-semibold text-[#1C201E] font-serif-luxury">{{ t('journey_2009_title') }}</h3>
                                <p class="text-[11px] sm:text-xs text-[#5C6460] font-light leading-relaxed mt-1 sm:mt-2">
                                    {{ t('journey_2009_desc') }}
                                </p>
                            </div>
                            <div class="absolute left-1.5 md:relative md:left-auto flex-shrink-0 w-5 h-5 sm:w-8 sm:h-8 rounded-full bg-[#4A6B5D] border-2 sm:border-4 border-[#FAF7F2] md:mx-0 flex items-center justify-center">
                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-white"></div>
                            </div>
                            <div class="hidden md:block md:w-1/2 md:pl-10"></div>
                        </div>

                        <!-- Timeline Item 2 -->
                        <div class="relative flex flex-col md:flex-row md:items-center gap-4 sm:gap-6 md:gap-12">
                            <div class="hidden md:block md:w-1/2 md:text-right md:pr-10"></div>
                            <div class="absolute left-1.5 md:relative md:left-auto flex-shrink-0 w-5 h-5 sm:w-8 sm:h-8 rounded-full bg-[#4A6B5D] border-2 sm:border-4 border-[#FAF7F2] flex items-center justify-center">
                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-white"></div>
                            </div>
                            <div class="md:w-1/2 md:pl-10 pl-10 md:pl-10">
                                <div class="text-[9px] sm:text-[10px] text-[#4A6B5D] font-bold uppercase tracking-widest mb-0.5 sm:mb-1">2023</div>
                                <h3 class="text-sm sm:text-lg font-semibold text-[#1C201E] font-serif-luxury">{{ t('journey_2015_title') }}</h3>
                                <p class="text-[11px] sm:text-xs text-[#5C6460] font-light leading-relaxed mt-1 sm:mt-2">
                                    {{ t('journey_2015_desc') }}
                                </p>
                            </div>
                        </div>

                        <!-- Timeline Item 3 -->
                        <div class="relative flex flex-col md:flex-row md:items-center gap-4 sm:gap-6 md:gap-12">
                            <div class="md:w-1/2 md:text-right md:pr-10 pl-10 md:pl-0">
                                <div class="text-[9px] sm:text-[10px] text-[#4A6B5D] font-bold uppercase tracking-widest mb-0.5 sm:mb-1">2026</div>
                                <h3 class="text-sm sm:text-lg font-semibold text-[#1C201E] font-serif-luxury">{{ t('journey_2020_title') }}</h3>
                                <p class="text-[11px] sm:text-xs text-[#5C6460] font-light leading-relaxed mt-1 sm:mt-2">
                                    {{ t('journey_2020_desc') }}
                                </p>
                            </div>
                            <div class="absolute left-1.5 md:relative md:left-auto flex-shrink-0 w-5 h-5 sm:w-8 sm:h-8 rounded-full bg-[#4A6B5D] border-2 sm:border-4 border-[#FAF7F2] flex items-center justify-center">
                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-white"></div>
                            </div>
                            <div class="hidden md:block md:w-1/2 md:pl-10"></div>
                        </div>

                        <!-- Timeline Item 4 -->
                        <div class="relative flex flex-col md:flex-row md:items-center gap-4 sm:gap-6 md:gap-12">
                            <div class="hidden md:block md:w-1/2 md:text-right md:pr-10"></div>
                            <div class="absolute left-1.5 md:relative md:left-auto flex-shrink-0 w-5 h-5 sm:w-8 sm:h-8 rounded-full bg-[#1C201E] border-2 sm:border-4 border-[#FAF7F2] flex items-center justify-center">
                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-white"></div>
                            </div>
                            <div class="md:w-1/2 md:pl-10 pl-10">
                                <div class="text-[9px] sm:text-[10px] text-[#4A6B5D] font-bold uppercase tracking-widest mb-0.5 sm:mb-1">{{ t('now_label') }}</div>
                                <h3 class="text-sm sm:text-lg font-semibold text-[#1C201E] font-serif-luxury">{{ t('journey_now_title') }}</h3>
                                <p class="text-[11px] sm:text-xs text-[#5C6460] font-light leading-relaxed mt-1 sm:mt-2">
                                    {{ t('journey_now_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-12 sm:py-20 bg-white border-t border-[#E6E1DA] font-sans-modern text-center">
            <div class="max-w-2xl mx-auto px-6 space-y-4 sm:space-y-6">
                <span class="text-[9px] sm:text-[10px] text-[#4A6B5D] uppercase tracking-widest font-bold block">{{ t('start_journey_title') }}</span>
                <h2 class="text-xl sm:text-3xl lg:text-4xl font-light text-[#1C201E] font-serif-luxury">
                    {{ t('ready_dream_event') }}
                </h2>
                <p class="text-[#5C6460] text-xs sm:text-sm font-light leading-relaxed">
                    {{ t('ready_dream_desc') }}
                </p>
                <div class="flex flex-col items-stretch sm:flex-row sm:items-center justify-center gap-3 pt-2 w-full">
                    <Link
                        href="/contact"
                        class="w-full sm:w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center px-6 py-2.5 sm:px-8 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md flex items-center justify-center whitespace-nowrap"
                    >
                        {{ t('contact_us') }}
                    </Link>
                    <Link
                        href="/packages"
                        class="w-full sm:w-auto bg-transparent border border-[#4A6B5D] hover:bg-[#4A6B5D]/10 text-[#4A6B5D] text-center px-6 py-2.5 sm:px-8 sm:py-4 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 flex items-center justify-center whitespace-nowrap"
                    >
                        {{ t('our_packages') }}
                    </Link>
                </div>
            </div>
        </section>
    </FrontLayout>
</template>
