// system/assets/js/safety.js
// Live Location Tracker, Geofenced Dangerous-Zone Monitor & SOS Emergency Dispatcher

document.addEventListener('DOMContentLoaded', () => {
    // 1. Geolocation Tracking Engine
    function sendLocationPing(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        const formData = new FormData();
        formData.append('latitude', lat);
        formData.append('longitude', lng);
        formData.append('accuracy', accuracy);

        fetch('api/location.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.alerts_triggered > 0) {
                // Dangerous zone entered! Trigger urgent alert modal
                data.data.alerts.forEach(alert => {
                    if (window.showToast) {
                        window.showToast(`🚨 ${alert.message}`, 'danger');
                    }
                    showSafetyZoneAlertModal(alert);
                });
            }
        })
        .catch(err => console.warn('Location ping error:', err));
    }

    if ("geolocation" in navigator) {
        // Initial ping
        navigator.geolocation.getCurrentPosition(sendLocationPing, (err) => console.warn("Geolocation permission:", err));

        // Periodic background location ping every 30 seconds
        setInterval(() => {
            navigator.geolocation.getCurrentPosition(sendLocationPing);
        }, 30000);
    }

    // 2. Dangerous Zone Alert Modal Popup
    function showSafetyZoneAlertModal(alert) {
        let modal = document.getElementById('safety-zone-alert-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'safety-zone-alert-modal';
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-card" style="border-top:6px solid #DC2626;">
                    <div class="modal-header">
                        <h3 style="color:#DC2626;display:flex;align-items:center;gap:8px;">🚨 DANGEROUS ZONE ALERT</h3>
                        <button onclick="closeModal('safety-zone-alert-modal')" class="btn btn-outline" style="padding:4px 10px;">✕</button>
                    </div>
                    <div id="safety-alert-body"></div>
                    <div style="margin-top:20px;display:flex;gap:12px;">
                        <button onclick="closeModal('safety-zone-alert-modal')" class="btn btn-primary" style="flex:1;">I Acknowledge & Caution</button>
                        <button onclick="triggerEmergencySOS('Accident')" class="btn btn-danger">Trigger SOS</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        document.getElementById('safety-alert-body').innerHTML = `
            <p style="font-size:16px;font-weight:700;margin-bottom:8px;">${alert.title}</p>
            <p style="font-size:14px;color:#4B5563;margin-bottom:12px;">${alert.description}</p>
            <div style="background:#FEE2E2;padding:12px;border-radius:8px;color:#991B1B;font-size:13px;font-weight:600;">
                Risk Type: ${alert.risk_type} | Level: ${alert.risk_level} <br>
                Proximity: ${alert.distance_meters} meters from epicenter
            </div>
        `;
        modal.style.display = 'flex';
    }

    // 3. Floating 1-Tap SOS Emergency Dispatcher
    window.triggerEmergencySOS = function(category = 'Medical') {
        if (!confirm('🚨 CONFIRM EMERGENCY SOS TRIGGER?\n\nThis will immediately send your live location and alert your Travel Team, Tour Leader, and Local Emergency Services.')) {
            return;
        }

        navigator.geolocation.getCurrentPosition((pos) => {
            const formData = new FormData();
            formData.append('category', category);
            formData.append('latitude', pos.coords.latitude);
            formData.append('longitude', pos.coords.longitude);

            fetch('api/sos.php?action=trigger', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showSOSConfirmationModal(data.data);
                } else {
                    alert('SOS dispatch error: ' + data.message);
                }
            });
        }, () => {
            // Fallback without coordinates
            const formData = new FormData();
            formData.append('category', category);
            fetch('api/sos.php?action=trigger', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => showSOSConfirmationModal(data.data));
        });
    };

    function showSOSConfirmationModal(data) {
        let modal = document.getElementById('sos-active-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'sos-active-modal';
            modal.className = 'modal-overlay';
            document.body.appendChild(modal);
        }

        let resourcesList = '';
        if (data.nearby_resources && data.nearby_resources.length > 0) {
            data.nearby_resources.forEach(r => {
                resourcesList += `
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #eee;">
                        <div>
                            <strong>${r.name}</strong> (${r.type})<br>
                            <small style="color:#666;">${r.address} (${r.distance_km || 0} km away)</small>
                        </div>
                        <a href="tel:${r.phone}" class="btn btn-danger" style="padding:6px 12px;font-size:12px;">📞 Call ${r.phone}</a>
                    </div>
                `;
            });
        }

        modal.innerHTML = `
            <div class="modal-card" style="border-top:6px solid #DC2626;max-width:600px;">
                <div class="modal-header">
                    <h3 style="color:#DC2626;">🚨 EMERGENCY SOS ACTIVATED</h3>
                    <button onclick="closeModal('sos-active-modal')" class="btn btn-outline" style="padding:4px 10px;">✕</button>
                </div>
                <div style="background:#FEE2E2;padding:16px;border-radius:12px;margin-bottom:20px;color:#991B1B;">
                    <strong>Alert Status: ACTIVE</strong><br>
                    Category: ${data.category}<br>
                    Team & Organization Admin have been broadcasted!
                </div>
                <h4 style="margin-bottom:12px;">Nearby Emergency Resources:</h4>
                <div style="max-height:220px;overflow-y:auto;margin-bottom:20px;">
                    ${resourcesList || '<p>Loading emergency directory...</p>'}
                </div>
                <button onclick="closeModal('sos-active-modal')" class="btn btn-outline" style="width:100%;">Close Modal</button>
            </div>
        `;
        modal.style.display = 'flex';
    }
});
