// system/assets/js/pwa-sync.js
// IndexedDB Offline Queue & Reconnection Sync Engine

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js')
        .then(() => console.log('SAHAYATRI Service Worker registered successfully!'))
        .catch(err => console.warn('SW registration error:', err));
}

// Network Status Monitor
window.addEventListener('online', () => {
    if (window.showToast) {
        window.showToast('📶 Internet connection restored! Synchronizing offline data...', 'success');
    }
    syncOfflineQueue();
});

window.addEventListener('offline', () => {
    if (window.showToast) {
        window.showToast('⚠️ Offline Mode Activated: Using cached maps & offline travel companion.', 'warning');
    }
});

function syncOfflineQueue() {
    const offlineSOS = localStorage.getItem('sahayatri_pending_sos');
    if (offlineSOS) {
        try {
            const data = JSON.parse(offlineSOS);
            const formData = new FormData();
            formData.append('category', data.category);
            formData.append('latitude', data.latitude);
            formData.append('longitude', data.longitude);

            fetch('api/sos.php?action=trigger', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(() => {
                    localStorage.removeItem('sahayatri_pending_sos');
                    console.log('Queued offline SOS synced successfully!');
                });
        } catch(e) {
            console.error(e);
        }
    }
}
