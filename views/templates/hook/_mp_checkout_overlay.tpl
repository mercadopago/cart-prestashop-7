function removeMPOverlay() {
    document.querySelectorAll('.mp-mercadopago-checkout-wrapper')
        .forEach(function(el) { el.remove(); });
    document.querySelectorAll('#mercadopago-checkout')
        .forEach(function(el) { el.remove(); });
    document.querySelectorAll('body > iframe')
        .forEach(function(el) {
            if (el.src && el.src.indexOf('mercadopago') !== -1) {
                el.remove();
            }
        });
    document.body.style.overflow = '';
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
}

function watchForMPClose() {
    function messageHandler(event) {
        if (event.origin && event.origin.indexOf('mercadopago') !== -1) {
            try {
                var msg = typeof event.data === 'string'
                    ? JSON.parse(event.data) : event.data;
                if (msg && (msg.type === 'close' || msg.action === 'finalize')) {
                    window.removeEventListener('message', messageHandler);
                    removeMPOverlay();
                }
            } catch (e) {
                console.error('[MP] Error processing close message:', e);
            }
        }
    }
    window.addEventListener('message', messageHandler);
}
