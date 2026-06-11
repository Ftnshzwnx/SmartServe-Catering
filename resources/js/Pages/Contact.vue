<script setup>
import { ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
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

// Contact Form Logic
const contactForm = ref({
    name: '',
    email: '',
    phone: '',
    date: '',
    pax: '',
    type: 'wedding',
    message: ''
});

const contactErrors = ref({});
const contactSuccess = ref(false);
const contactSubmitting = ref(false);

const validateContactForm = () => {
    const errors = {};
    if (!contactForm.value.name.trim()) errors.name = t('error_name_required');
    if (!contactForm.value.email.trim()) {
        errors.email = t('error_email_required');
    } else if (!/\S+@\S+\.\S+/.test(contactForm.value.email)) {
        errors.email = t('error_email_invalid');
    }
    if (!contactForm.value.phone.trim()) errors.phone = t('error_phone_required');
    if (!contactForm.value.date) errors.date = t('error_date_required');
    if (!contactForm.value.pax || parseInt(contactForm.value.pax) <= 0) errors.pax = t('error_pax_required');
    if (!contactForm.value.message.trim()) errors.message = t('error_message_required');

    contactErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const handleContactSubmit = () => {
    if (!validateContactForm()) return;
    contactSubmitting.value = true;
    setTimeout(() => {
        contactSubmitting.value = false;
        contactSuccess.value = true;
        toast(t('inquiry_success'));
        contactForm.value = { name: '', email: '', phone: '', date: '', pax: '', type: 'wedding', message: '' };
        contactErrors.value = {};
    }, 1200);
};

const handleWhatsAppClick = () => {
    const page = usePage();
    const text = t('inquiry_whatsapp_text')
        .replace('{name}', contactForm.value.name || 'Pelanggan')
        .replace('{date}', contactForm.value.date || 'TBD')
        .replace('{pax}', contactForm.value.pax || 'TBD')
        .replace('{type}', t(`contact_type_${contactForm.value.type}`))
        .replace('{message}', contactForm.value.message || 'Tiada mesej tambahan.');
    const phoneNum = (page.props.settings.contact_phone || '019-2094670').replace(/[^0-9]/g, '').replace(/^0/, '60');
    const url = `https://wa.me/${phoneNum}?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Contact Us — SmartServe Catering" />

    <FrontLayout :canLogin="canLogin" :canRegister="canRegister">
        <!-- Page Hero -->
        <section class="relative bg-[#1C201E] text-white overflow-hidden py-24 lg:py-36 font-sans-modern">
            <div class="absolute inset-0 opacity-10 bg-[url('/img/hero_catering.png')] bg-cover bg-center"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-[#1C201E] via-[#1C201E]/80 to-[#4A6B5D]/30"></div>
            <div class="relative max-w-7xl mx-auto px-6 lg:px-8 text-center space-y-6 z-10">
                <div class="flex items-center justify-center gap-2 text-xs font-semibold uppercase tracking-widest text-[#A8C5B8]">
                    <Link href="/" class="hover:text-white transition-colors duration-200">{{ t('home_nav') }}</Link>
                    <span class="text-white/30 text-[10px] font-normal">/</span>
                    <span class="text-white/60">{{ t('contact_nav') }}</span>
                </div>
                <h1 class="text-5xl lg:text-7xl font-light tracking-tight font-serif-luxury leading-[1.1]">
                    {{ t('contact_title') }}
                </h1>
                <p class="text-base text-[#8E9993] max-w-lg mx-auto font-light leading-relaxed">
                    {{ t('contact_subtitle') }}
                </p>
            </div>
        </section>

        <!-- Contact Info & Form Grid -->
        <section class="py-24 bg-[#FAF7F2] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-start">

                    <!-- Left: Info Cards -->
                    <div class="lg:col-span-5 space-y-8">
                        <!-- Header -->
                        <div class="space-y-3">
                            <span class="text-xs font-semibold text-[#4A6B5D] uppercase tracking-widest block">{{ t('connect_coordinates') }}</span>
                            <h2 class="text-3xl font-light text-[#1C201E] font-serif-luxury">
                                {{ t('connect_coordinates') }}
                            </h2>
                        </div>

                        <!-- Info List -->
                        <ul class="space-y-4">
                            <!-- Alamat Kami -->
                            <li class="flex items-center justify-between gap-4 p-3 -mx-3 rounded-2xl hover:bg-white hover:shadow-xs border border-transparent hover:border-[#E6E1DA] transition-all duration-300 group">
                                <a :href="'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent($page.props.settings.business_address || 'Gong Badak, Kuala Terengganu, Terengganu, Malaysia')" target="_blank" class="flex items-start gap-4 flex-grow">
                                    <span class="w-11 h-11 flex-shrink-0 rounded-2xl bg-white border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-sm shadow-xs transition-colors group-hover:border-[#4A6B5D]/30 group-hover:bg-[#FAF7F2]">
                                        <i class="fa fa-map-marker-alt"></i>
                                    </span>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[#2D3330] block text-xs uppercase tracking-wider">{{ t('address_label') }}</span>
                                        <span class="text-sm text-[#5C6460] font-light group-hover:text-[#4A6B5D] transition-colors">{{ $page.props.settings.business_address || 'Gong Badak, Kuala Terengganu, Terengganu, Malaysia' }}</span>
                                    </div>
                                </a>
                                <div class="pr-2 flex items-center">
                                    <i class="fas fa-arrow-right text-[10px] text-[#8C8275] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>
                            </li>

                            <!-- Hotline Langsung -->
                            <li class="flex items-center justify-between gap-4 p-3 -mx-3 rounded-2xl hover:bg-white hover:shadow-xs border border-transparent hover:border-[#E6E1DA] transition-all duration-300 group">
                                <a :href="'tel:' + ($page.props.settings.contact_phone || '019-2094670')" class="flex items-start gap-4 flex-grow">
                                    <span class="w-11 h-11 flex-shrink-0 rounded-2xl bg-white border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-sm shadow-xs transition-colors group-hover:border-[#4A6B5D]/30 group-hover:bg-[#FAF7F2]">
                                        <i class="fa fa-phone-alt"></i>
                                    </span>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[#2D3330] block text-xs uppercase tracking-wider">{{ t('hotline_label') }}</span>
                                        <span class="text-sm text-[#5C6460] font-light group-hover:text-[#4A6B5D] transition-colors">{{ $page.props.settings.contact_phone || '019-2094670' }}</span>
                                    </div>
                                </a>
                                <div class="pr-2 flex items-center">
                                    <i class="fas fa-arrow-right text-[10px] text-[#8C8275] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>
                            </li>

                            <!-- E-mel -->
                            <li class="flex items-center justify-between gap-4 p-3 -mx-3 rounded-2xl hover:bg-white hover:shadow-xs border border-transparent hover:border-[#E6E1DA] transition-all duration-300 group">
                                <a :href="'mailto:' + ($page.props.settings.contact_email || 'info@smartservecatering.com')" class="flex items-start gap-4 flex-grow">
                                    <span class="w-11 h-11 flex-shrink-0 rounded-2xl bg-white border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center text-sm shadow-xs transition-colors group-hover:border-[#4A6B5D]/30 group-hover:bg-[#FAF7F2]">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[#2D3330] block text-xs uppercase tracking-wider">{{ t('email_label') }}</span>
                                        <span class="text-sm text-[#5C6460] font-light group-hover:text-[#4A6B5D] transition-colors">{{ $page.props.settings.contact_email || 'info@smartservecatering.com' }}</span>
                                    </div>
                                </a>
                                <div class="pr-2 flex items-center">
                                    <i class="fas fa-arrow-right text-[10px] text-[#8C8275] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>
                            </li>

                            <!-- WhatsApp -->
                            <li class="flex items-center justify-between gap-4 p-3 -mx-3 rounded-2xl hover:bg-white hover:shadow-xs border border-transparent hover:border-[#E6E1DA] transition-all duration-300 group">
                                <a :href="'https://wa.me/' + ($page.props.settings.contact_phone || '019-2094670').replace(/[^0-9]/g, '').replace(/^0/, '60')" target="_blank" class="flex items-start gap-4 flex-grow">
                                    <span class="w-11 h-11 flex-shrink-0 rounded-2xl bg-white border border-[#E6E1DA] text-emerald-600 flex items-center justify-center text-sm shadow-xs transition-colors group-hover:border-emerald-500/30 group-hover:bg-emerald-50/50">
                                        <i class="fab fa-whatsapp"></i>
                                    </span>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[#2D3330] block text-xs uppercase tracking-wider">WhatsApp</span>
                                        <span class="text-sm text-[#5C6460] font-light group-hover:text-[#4A6B5D] transition-colors">{{ t('whatsapp_click_desc') }}</span>
                                    </div>
                                </a>
                                <div class="pr-2 flex items-center">
                                    <i class="fas fa-arrow-right text-[10px] text-[#8C8275] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>
                            </li>
                        </ul>

                        <!-- Coverage Area Card -->
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-7 space-y-5 shadow-sm">
                            <div class="flex items-center justify-between border-b border-[#E6E1DA]/60 pb-4">
                                <span class="text-[10px] font-bold text-[#4A6B5D] uppercase tracking-widest">{{ t('service_standard') }}</span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            </div>
                            <p class="text-xs text-[#5C6460] font-light leading-relaxed">
                                {{ t('service_area_desc') }}
                            </p>
                            <div class="grid grid-cols-2 gap-3 text-[10px] font-semibold text-[#2D3330] uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-[#4A6B5D]"></i> Gong Badak
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-[#4A6B5D]"></i> Kuala Nerus
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-[#4A6B5D]"></i> Kuala Terengganu
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-[#4A6B5D]"></i> Marang
                                </div>
                            </div>
                        </div>

                        <!-- Business Hours Card -->
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-7 space-y-4 shadow-sm">
                            <h4 class="text-[10px] font-bold text-[#4A6B5D] uppercase tracking-widest border-b border-[#E6E1DA]/60 pb-3">{{ t('business_hours_title') }}</h4>
                            <ul class="space-y-2 text-xs text-[#5C6460] font-light">
                                <li class="flex justify-between">
                                    <span>{{ t('business_days_label') }}</span>
                                    <span class="font-semibold text-[#2D3330]">8:00 AM – 6:00 PM</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Right: Inquiry Form -->
                    <div class="lg:col-span-7">
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl p-8 lg:p-10 shadow-sm">
                            <div class="space-y-2 mb-8">
                                <h2 class="text-2xl font-light text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('contact_title') }}</h2>
                                <p class="text-xs text-[#8C8275] font-light leading-relaxed">{{ t('contact_subtitle') }}</p>
                            </div>

                            <!-- Success Message -->
                            <div v-if="contactSuccess" class="mb-6 p-5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3">
                                <i class="fas fa-check-circle text-emerald-600 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-800">{{ t('inquiry_success') }}</p>
                                    <p class="text-xs text-emerald-700 font-light mt-1">{{ t('contact_reply_notice') }}</p>
                                </div>
                            </div>

                            <form @submit.prevent="handleContactSubmit" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="contact-name" :value="t('contact_name')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-name"
                                            type="text"
                                            class="mt-1.5 block w-full rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-3.5 px-4"
                                            v-model="contactForm.name"
                                            :placeholder="t('placeholder_name')"
                                        />
                                        <p v-if="contactErrors.name" class="mt-1 text-xs text-red-500">{{ contactErrors.name }}</p>
                                    </div>
                                    <div>
                                        <InputLabel for="contact-email" :value="t('contact_email')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-email"
                                            type="email"
                                            class="mt-1.5 block w-full rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-3.5 px-4"
                                            v-model="contactForm.email"
                                            :placeholder="t('placeholder_email')"
                                        />
                                        <p v-if="contactErrors.email" class="mt-1 text-xs text-red-500">{{ contactErrors.email }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="contact-phone" :value="t('contact_phone')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-phone"
                                            type="text"
                                            class="mt-1.5 block w-full rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-3.5 px-4"
                                            v-model="contactForm.phone"
                                            :placeholder="t('placeholder_phone')"
                                        />
                                        <p v-if="contactErrors.phone" class="mt-1 text-xs text-red-500">{{ contactErrors.phone }}</p>
                                    </div>
                                    <div>
                                        <InputLabel for="contact-date" :value="t('contact_date')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-date"
                                            type="date"
                                            class="mt-1.5 block w-full rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-3.5 px-4"
                                            v-model="contactForm.date"
                                        />
                                        <p v-if="contactErrors.date" class="mt-1 text-xs text-red-500">{{ contactErrors.date }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="contact-pax" :value="t('contact_pax')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <TextInput
                                            id="contact-pax"
                                            type="number"
                                            class="mt-1.5 block w-full rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-3.5 px-4"
                                            v-model="contactForm.pax"
                                            :placeholder="t('placeholder_pax')"
                                        />
                                        <p v-if="contactErrors.pax" class="mt-1 text-xs text-red-500">{{ contactErrors.pax }}</p>
                                    </div>
                                    <div>
                                        <InputLabel for="contact-type" :value="t('contact_type')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <div class="relative mt-1.5">
                                            <select
                                                id="contact-type"
                                                class="block w-full rounded-xl border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-3.5 px-4 appearance-none pr-10 focus:outline-none"
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
                                    <InputLabel for="contact-message" :value="t('contact_message')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <textarea
                                        id="contact-message"
                                        rows="4"
                                        class="mt-1.5 block w-full rounded-xl border border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-[#FAF7F2] text-xs py-3.5 px-4 focus:outline-none"
                                        v-model="contactForm.message"
                                        :placeholder="t('placeholder_message')"
                                    ></textarea>
                                    <p v-if="contactErrors.message" class="mt-1 text-xs text-red-500">{{ contactErrors.message }}</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 pt-2">
                                    <button
                                        type="submit"
                                        class="flex-1 bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-4 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all duration-200 cursor-pointer shadow-sm"
                                        :disabled="contactSubmitting"
                                    >
                                        <template v-if="contactSubmitting">
                                            <i class="fas fa-spinner animate-spin mr-2"></i> {{ t('sending_status_msg') }}
                                        </template>
                                        <template v-else>
                                            {{ t('contact_submit') }}
                                        </template>
                                    </button>
                                    <button
                                        type="button"
                                        @click="handleWhatsAppClick"
                                        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-center py-4 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all duration-200 cursor-pointer flex items-center justify-center gap-2"
                                    >
                                        <i class="fab fa-whatsapp text-sm"></i>
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
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3977.038977857766!2d103.1363!3d5.3302!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31b6ba2b8344e13d%3A0x7d02ebbd3006eb49!2sGong+Badak%2C+Kuala+Terengganu%2C+Terengganu!5e0!3m2!1sen!2smy!4v1700000000000"
                class="w-full h-full"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
        </section>
    </FrontLayout>
</template>
