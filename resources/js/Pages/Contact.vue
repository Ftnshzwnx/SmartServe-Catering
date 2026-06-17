<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import FrontLayout from '@/Layouts/FrontLayout.vue';
import { useLocalization } from '@/Composables/useLocalization';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    packages: { type: Array, default: () => [] },
});

const { t } = useLocalization();
const { toast } = useToast();
const page = usePage();

// Contact Form — backed by Inertia useForm for real server submission
const contactForm = useForm({
    name: '',
    email: '',
    phone: '',
    date: '',
    pax: '',
    type: 'wedding',
    message: '',
});

const contactSuccess = computed(() => !!page.props.flash?.success);

const handleContactSubmit = () => {
    contactForm.post(route('contact.send'), {
        preserveScroll: true,
        onSuccess: () => {
            contactForm.reset();
        },
    });
};

const handleWhatsAppClick = () => {
    const text = t('inquiry_whatsapp_text')
        .replace('{name}', contactForm.name || 'Pelanggan')
        .replace('{date}', contactForm.date || 'TBD')
        .replace('{pax}', contactForm.pax || 'TBD')
        .replace('{type}', t(`contact_type_${contactForm.type}`))
        .replace('{message}', contactForm.message || 'Tiada mesej tambahan.');
    const phoneNum = (page.props.settings.contact_phone || '012-3456789').replace(/[^0-9]/g, '').replace(/^0/, '60');
    const url = `https://wa.me/${phoneNum}?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
};

const mapUrl = computed(() => {
    const address = page.props.settings.business_address || 'Gong Badak, Kuala Terengganu, Terengganu, Malaysia';
    return `https://maps.google.com/maps?q=${encodeURIComponent(address)}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
});
</script>

<template>
    <Head title="Contact Us — SmartServe Catering" />

    <FrontLayout :canLogin="canLogin" :canRegister="canRegister">
        <!-- Page Hero -->
        <section class="relative bg-[#1C201E] text-white overflow-hidden py-16 sm:py-24 lg:py-36 font-sans-modern">
            <div class="absolute inset-0 opacity-10 bg-[url('/img/hero_catering.png')] bg-cover bg-center"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-[#1C201E] via-[#1C201E]/80 to-[#4A6B5D]/30"></div>
            <div class="relative max-w-7xl mx-auto px-6 lg:px-8 text-center space-y-4 sm:space-y-6 z-10">
                <div class="flex items-center justify-center gap-2 text-[10px] sm:text-xs font-semibold uppercase tracking-widest text-[#A8C5B8]">
                    <Link href="/" class="hover:text-white transition-colors duration-200">{{ t('home_nav') }}</Link>
                    <span class="text-white/30 text-[9px] sm:text-[10px] font-normal">/</span>
                    <span class="text-white/60">{{ t('contact_nav') }}</span>
                </div>
                <h1 class="text-3xl sm:text-5xl lg:text-7xl font-light tracking-tight font-serif-luxury leading-[1.1]">
                    {{ t('contact_title') }}
                </h1>
                <p class="text-xs sm:text-base text-[#8E9993] max-w-lg mx-auto font-light leading-relaxed">
                    {{ t('contact_subtitle') }}
                </p>
            </div>
        </section>

        <!-- Contact Info & Form Grid -->
        <section class="py-12 sm:py-24 bg-[#FAF7F2] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-12 gap-6 sm:gap-12 lg:gap-16 items-start">

                    <!-- Left: Info Cards -->
                    <div class="lg:col-span-5 space-y-6 sm:space-y-8">
                        <!-- Header -->
                        <div class="space-y-2 sm:space-y-3">
                            <span class="text-[10px] sm:text-xs font-semibold text-[#4A6B5D] uppercase tracking-widest block">{{ t('connect_coordinates') }}</span>
                            <h2 class="text-xl sm:text-3xl font-light text-[#1C201E] font-serif-luxury">
                                {{ t('connect_coordinates') }}
                            </h2>
                        </div>

                        <!-- Info List -->
                        <ul class="space-y-3 sm:space-y-4">
                            <!-- Alamat Kami -->
                            <li class="flex items-center justify-between gap-4 p-2 sm:p-3 -mx-2 sm:-mx-3 rounded-xl sm:rounded-2xl hover:bg-white hover:shadow-xs border border-transparent hover:border-[#E6E1DA] transition-all duration-300 group">
                                <a :href="'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent($page.props.settings.business_address || 'Gong Badak, Kuala Terengganu, Terengganu, Malaysia')" target="_blank" class="flex items-start gap-3 sm:gap-4 flex-grow">
                                    <span class="w-9 h-9 sm:w-11 sm:h-11 flex-shrink-0 rounded-xl sm:rounded-2xl bg-white border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-sm shadow-xs transition-colors group-hover:border-[#4A6B5D]/30 group-hover:bg-[#FAF7F2]">
                                        <i class="fa fa-map-marker-alt"></i>
                                    </span>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[#2D3330] block text-[10px] sm:text-xs uppercase tracking-wider">{{ t('address_label') }}</span>
                                        <span class="text-xs sm:text-sm text-[#5C6460] font-light group-hover:text-[#4A6B5D] transition-colors">{{ $page.props.settings.business_address || 'Gong Badak, Kuala Terengganu, Terengganu, Malaysia' }}</span>
                                    </div>
                                </a>
                                <div class="pr-2 flex items-center">
                                    <i class="fas fa-arrow-right text-[10px] text-[#8C8275] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>
                            </li>

                            <!-- Hotline Langsung -->
                            <li class="flex items-center justify-between gap-4 p-2 sm:p-3 -mx-2 sm:-mx-3 rounded-xl sm:rounded-2xl hover:bg-white hover:shadow-xs border border-transparent hover:border-[#E6E1DA] transition-all duration-300 group">
                                <a :href="'tel:' + ($page.props.settings.contact_phone || '012-3456789')" class="flex items-start gap-3 sm:gap-4 flex-grow">
                                    <span class="w-9 h-9 sm:w-11 sm:h-11 flex-shrink-0 rounded-xl sm:rounded-2xl bg-white border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-sm shadow-xs transition-colors group-hover:border-[#4A6B5D]/30 group-hover:bg-[#FAF7F2]">
                                        <i class="fa fa-phone-alt"></i>
                                    </span>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[#2D3330] block text-[10px] sm:text-xs uppercase tracking-wider">{{ t('hotline_label') }}</span>
                                        <span class="text-xs sm:text-sm text-[#5C6460] font-light group-hover:text-[#4A6B5D] transition-colors">{{ $page.props.settings.contact_phone || '012-3456789' }}</span>
                                    </div>
                                </a>
                                <div class="pr-2 flex items-center">
                                    <i class="fas fa-arrow-right text-[10px] text-[#8C8275] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>
                            </li>

                            <!-- E-mel -->
                            <li class="flex items-center justify-between gap-4 p-2 sm:p-3 -mx-2 sm:-mx-3 rounded-xl sm:rounded-2xl hover:bg-white hover:shadow-xs border border-transparent hover:border-[#E6E1DA] transition-all duration-300 group">
                                <a :href="'mailto:' + ($page.props.settings.contact_email || 'info@smartservecatering.com')" class="flex items-start gap-3 sm:gap-4 flex-grow">
                                    <span class="w-9 h-9 sm:w-11 sm:h-11 flex-shrink-0 rounded-xl sm:rounded-2xl bg-white border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-xs sm:text-sm shadow-xs transition-colors group-hover:border-[#4A6B5D]/30 group-hover:bg-[#FAF7F2]">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[#2D3330] block text-[10px] sm:text-xs uppercase tracking-wider">{{ t('email_label') }}</span>
                                        <span class="text-xs sm:text-sm text-[#5C6460] font-light group-hover:text-[#4A6B5D] transition-colors">{{ $page.props.settings.contact_email || 'info@smartservecatering.com' }}</span>
                                    </div>
                                </a>
                                <div class="pr-2 flex items-center">
                                    <i class="fas fa-arrow-right text-[10px] text-[#8C8275] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>
                            </li>

                            <!-- WhatsApp -->
                            <li class="flex items-center justify-between gap-4 p-2 sm:p-3 -mx-2 sm:-mx-3 rounded-xl sm:rounded-2xl hover:bg-white hover:shadow-xs border border-transparent hover:border-[#E6E1DA] transition-all duration-300 group">
                                <a :href="'https://wa.me/' + ($page.props.settings.contact_phone || '012-3456789').replace(/[^0-9]/g, '').replace(/^0/, '60')" target="_blank" class="flex items-start gap-3 sm:gap-4 flex-grow">
                                    <span class="w-9 h-9 sm:w-11 sm:h-11 flex-shrink-0 rounded-xl sm:rounded-2xl bg-white border border-[#E6E1DA] text-emerald-600 flex items-center justify-center text-xs sm:text-sm shadow-xs transition-colors group-hover:border-emerald-500/30 group-hover:bg-emerald-50/50">
                                        <i class="fab fa-whatsapp"></i>
                                    </span>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[#2D3330] block text-[10px] sm:text-xs uppercase tracking-wider">WhatsApp</span>
                                        <span class="text-xs sm:text-sm text-[#5C6460] font-light group-hover:text-[#4A6B5D] transition-colors">{{ t('whatsapp_click_desc') }}</span>
                                    </div>
                                </a>
                                <div class="pr-2 flex items-center">
                                    <i class="fas fa-arrow-right text-[10px] text-[#8C8275] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>
                            </li>
                        </ul>

                        <!-- Coverage Area Card -->
                        <div class="bg-white border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-5 sm:p-7 space-y-4 sm:space-y-5 shadow-sm">
                            <div class="flex items-center justify-between border-b border-[#E6E1DA]/60 pb-3 sm:pb-4">
                                <span class="text-[9px] sm:text-[10px] font-bold text-[#4A6B5D] uppercase tracking-widest">{{ t('service_standard') }}</span>
                                <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            </div>
                            <p class="text-[11px] sm:text-xs text-[#5C6460] font-light leading-relaxed">
                                {{ t('service_area_desc') }}
                            </p>
                            <div class="grid grid-cols-2 gap-2 sm:gap-3 text-[9px] sm:text-[10px] font-semibold text-[#2D3330] uppercase tracking-wider">
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <i class="fas fa-check-circle text-[#4A6B5D]"></i> Gong Badak
                                </div>
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <i class="fas fa-check-circle text-[#4A6B5D]"></i> Kuala Nerus
                                </div>
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <i class="fas fa-check-circle text-[#4A6B5D]"></i> Kuala Terengganu
                                </div>
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <i class="fas fa-check-circle text-[#4A6B5D]"></i> Marang
                                </div>
                            </div>
                        </div>

                        <!-- Business Hours Card -->
                        <div class="bg-white border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-5 sm:p-7 space-y-3 sm:space-y-4 shadow-sm">
                            <h4 class="text-[9px] sm:text-[10px] font-bold text-[#4A6B5D] uppercase tracking-widest border-b border-[#E6E1DA]/60 pb-2 sm:pb-3">{{ t('business_hours_title') }}</h4>
                            <ul class="space-y-2 text-[11px] sm:text-xs text-[#5C6460] font-light">
                                <li class="flex justify-between">
                                    <span>{{ t('business_days_label') }}</span>
                                    <span class="font-semibold text-[#2D3330]">8:00 AM – 6:00 PM</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Right: Inquiry Form -->
                    <div class="lg:col-span-7">
                        <div class="bg-white border border-[#E6E1DA] rounded-xl sm:rounded-3xl p-5 sm:p-8 lg:p-10 shadow-sm">
                            <div class="space-y-2 mb-6 sm:mb-8">
                                <h2 class="text-lg sm:text-2xl font-light text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('contact_title') }}</h2>
                                <p class="text-[11px] sm:text-xs text-[#8C8275] font-light leading-relaxed">{{ t('contact_subtitle') }}</p>
                            </div>

                            <!-- Success Message -->
                            <div v-if="contactSuccess" class="mb-6 p-4 sm:p-5 bg-emerald-50 border border-emerald-200 rounded-xl sm:rounded-2xl flex items-start gap-3">
                                <i class="fas fa-check-circle text-emerald-600 mt-0.5"></i>
                                <div>
                                    <p class="text-xs sm:text-sm font-semibold text-emerald-800">{{ t('inquiry_success') }}</p>
                                    <p class="text-[11px] sm:text-xs text-emerald-700 font-light mt-1">{{ t('contact_reply_notice') }}</p>
                                </div>
                            </div>

                            <form @submit.prevent="handleContactSubmit" class="space-y-4 sm:space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                    <div>
                                        <InputLabel for="contact-name" :value="t('contact_name')" class="text-[10px] sm:text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-name"
                                            type="text"
                                            class="mt-1.5 block w-full rounded-lg sm:rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-2.5 px-3.5 sm:py-3.5 sm:px-4"
                                            v-model="contactForm.name"
                                            :placeholder="t('placeholder_name')"
                                        />
                                        <p v-if="contactForm.errors.name" class="mt-1 text-[10px] text-red-500">{{ contactForm.errors.name }}</p>
                                    </div>
                                    <div>
                                        <InputLabel for="contact-email" :value="t('contact_email')" class="text-[10px] sm:text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-email"
                                            type="email"
                                            class="mt-1.5 block w-full rounded-lg sm:rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-2.5 px-3.5 sm:py-3.5 sm:px-4"
                                            v-model="contactForm.email"
                                            :placeholder="t('placeholder_email')"
                                        />
                                        <p v-if="contactForm.errors.email" class="mt-1 text-[10px] text-red-500">{{ contactForm.errors.email }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                    <div>
                                        <InputLabel for="contact-phone" :value="t('contact_phone')" class="text-[10px] sm:text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-phone"
                                            type="text"
                                            class="mt-1.5 block w-full rounded-lg sm:rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-2.5 px-3.5 sm:py-3.5 sm:px-4"
                                            v-model="contactForm.phone"
                                            :placeholder="t('placeholder_phone')"
                                        />
                                        <p v-if="contactForm.errors.phone" class="mt-1 text-[10px] text-red-500">{{ contactForm.errors.phone }}</p>
                                    </div>
                                    <div>
                                        <InputLabel for="contact-date" :value="t('contact_date')" class="text-[10px] sm:text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-date"
                                            type="date"
                                            class="mt-1.5 block w-full rounded-lg sm:rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-2.5 px-3.5 sm:py-3.5 sm:px-4"
                                            v-model="contactForm.date"
                                        />
                                        <p v-if="contactForm.errors.date" class="mt-1 text-[10px] text-red-500">{{ contactForm.errors.date }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                    <div>
                                        <InputLabel for="contact-pax" :value="t('contact_pax')" class="text-[10px] sm:text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-pax"
                                            type="number"
                                            class="mt-1.5 block w-full rounded-lg sm:rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-2.5 px-3.5 sm:py-3.5 sm:px-4"
                                            v-model="contactForm.pax"
                                            :placeholder="t('placeholder_pax')"
                                        />
                                        <p v-if="contactForm.errors.pax" class="mt-1 text-[10px] text-red-500">{{ contactForm.errors.pax }}</p>
                                    </div>
                                    <div>
                                        <InputLabel for="contact-type" :value="t('contact_type')" class="text-[10px] sm:text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <div class="relative mt-1.5">
                                            <select
                                                id="contact-type"
                                                class="block w-full rounded-lg sm:rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-2.5 px-3.5 sm:py-3.5 sm:px-4 appearance-none pr-10 focus:outline-none"
                                                v-model="contactForm.type"
                                            >
                                                <option value="wedding">{{ t('contact_type_wedding') }}</option>
                                                <option value="corporate">{{ t('contact_type_corporate') }}</option>
                                                <option value="social">{{ t('contact_type_social') }}</option>
                                                <option value="other">{{ t('contact_type_other') }}</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#8C8275]">
                                                <i class="fas fa-chevron-down text-[10px]"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="contact-message" :value="t('contact_message')" class="text-[10px] sm:text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <textarea
                                        id="contact-message"
                                        rows="4"
                                        class="mt-1.5 block w-full rounded-lg sm:rounded-xl border border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-2.5 px-3.5 sm:py-3.5 sm:px-4 focus:outline-none"
                                        v-model="contactForm.message"
                                        :placeholder="t('placeholder_message')"
                                    ></textarea>
                                    <p v-if="contactForm.errors.message" class="mt-1 text-[10px] text-red-500">{{ contactForm.errors.message }}</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                    <button
                                        type="submit"
                                        class="w-full sm:w-auto bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-2.5 sm:py-4 px-6 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 cursor-pointer shadow-sm flex items-center justify-center whitespace-nowrap"
                                        :disabled="contactForm.processing"
                                    >
                                        <template v-if="contactForm.processing">
                                            <i class="fas fa-spinner animate-spin mr-1.5 sm:mr-2 text-[10px] sm:text-xs"></i> {{ t('sending_status_msg') }}
                                        </template>
                                        <template v-else>
                                            {{ t('contact_submit') }}
                                        </template>
                                    </button>
                                    <button
                                        type="button"
                                        @click="handleWhatsAppClick"
                                        class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-center py-2.5 sm:py-4 px-6 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-widest transition-all duration-200 cursor-pointer flex items-center justify-center gap-1.5 sm:gap-2 shadow-sm whitespace-nowrap"
                                    >
                                        <i class="fab fa-whatsapp text-xs sm:text-sm flex-shrink-0"></i>
                                        {{ t('contact_whatsapp_btn') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Google Maps Embed Placeholder -->
        <section class="h-[400px] bg-[#EADED9] relative overflow-hidden border-t border-[#E6E1DA]">
            <iframe
                :src="mapUrl"
                class="w-full h-full"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
        </section>
    </FrontLayout>
</template>
