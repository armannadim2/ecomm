/*
 * Storefront JavaScript. Alpine is bundled here (≈16 KB compressed) instead of loading the
 * whole Livewire script (≈106 KB) on every shop page: the storefront has no Livewire components.
 * The admin (Filament) keeps Livewire and its own Alpine.
 */
import Alpine from 'alpinejs';
import './campaign-effects';
import cookieConsent from './cookie-consent';

window.Alpine = Alpine;
Alpine.data('cookieConsent', cookieConsent);
Alpine.start();
