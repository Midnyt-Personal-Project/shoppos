import './bootstrap';

import Alpine from 'alpinejs';

import Chart from 'chart.js/auto';

window.Chart = Chart;
 
// Make Alpine available globally so Blade x-data attributes work
window.Alpine = Alpine;


 
Alpine.start();
 