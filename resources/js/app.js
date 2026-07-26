import './bootstrap';

import Alpine from 'alpinejs';

import Chart from 'chart.js/auto';

window.Chart = Chart;
 
// Make Alpine available globally so Blade x-data attributes work
window.Alpine = Alpine;


 
Alpine.start();

// Register the lightweight PWA service worker. It deliberately never caches POS pages/data.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
