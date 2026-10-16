import './bootstrap';
import Alpine from 'alpinejs';
import { Html5QrcodeScanner } from 'html5-qrcode';

window.Alpine = Alpine;
window.Html5QrcodeScanner = Html5QrcodeScanner;

// Initialize hardware barcode scanner listener (rapid keystroke buffer)
window.initHardwareScanner = function(callback) {
    let buffer = '';
    let lastKeyTime = Date.now();

    window.addEventListener('keydown', (e) => {
        // Ignore if user is currently typing in an input or textarea
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        const isInputField = activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select';

        const currentTime = Date.now();
        if (currentTime - lastKeyTime > 100) {
            buffer = '';
        }
        lastKeyTime = currentTime;

        if (e.key === 'Enter') {
            if (buffer.length >= 6) {
                e.preventDefault();
                callback(buffer);
                buffer = '';
            }
        } else if (e.key.length === 1) {
            buffer += e.key;
        }
    });
};

Alpine.start();
