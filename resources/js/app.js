import './bootstrap';
import Alpine from 'alpinejs';
import { Html5QrcodeScanner } from 'html5-qrcode';

import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Html5QrcodeScanner = Html5QrcodeScanner;
window.Chart = Chart;

// Initialize hardware barcode scanner listener (rapid keystroke buffer)
window.initHardwareScanner = function(callback) {
    let buffer = '';
    let lastKeyTime = Date.now();

    window.addEventListener('keydown', (e) => {
        const currentTime = Date.now();
        const interval = currentTime - lastKeyTime;
        lastKeyTime = currentTime;

        // Barcode scanners input characters very rapidly (< 40-50ms between keys)
        // If human is typing slowly (> 90ms gap), reset the scanner buffer
        if (interval > 90) {
            buffer = '';
        }

        if (e.key === 'Enter') {
            if (buffer.length >= 4) {
                e.preventDefault();
                const scannedCode = buffer;
                buffer = '';
                callback(scannedCode);
            } else {
                buffer = '';
            }
        } else if (e.key.length === 1) {
            buffer += e.key;
        }
    });
};

Alpine.start();
