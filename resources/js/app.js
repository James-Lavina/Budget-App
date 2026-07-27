require('./bootstrap');

import AOS from 'aos';
import 'aos/dist/aos.css';

document.addEventListener('DOMContentLoaded', () => {
    AOS.init({
        duration: 450,
        once: true,
        easing: 'ease-out-cubic',
    });
});