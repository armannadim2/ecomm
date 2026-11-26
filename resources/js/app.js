/*
 * Storefront JavaScript. Alpine is bundled here (≈16 KB compressed) instead of loading the
 * whole Livewire script (≈106 KB) on every shop page: the storefront has no Livewire components.
 * The admin (Filament) keeps Livewire and its own Alpine.
 */
import Alpine from 'alpinejs';
import './campaign-effects';
import cookieConsent from './cookie-consent';
import { addToCart, cartStore, miniCart } from './cart';

window.Alpine = Alpine;
cartStore(Alpine);
Alpine.data('cookieConsent', cookieConsent);
Alpine.data('miniCart', miniCart);
Alpine.data('addToCart', addToCart);
Alpine.start();
