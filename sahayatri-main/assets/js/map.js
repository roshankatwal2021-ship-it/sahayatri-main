// system/assets/js/map.js
// Leaflet.js Interactive Safety Map & Layer Controller

function initSahayatriMap() {
    const mapElement = document.getElementById('sahayatri-map') || document.getElementById('map');
    if (!mapElement) return;

    if (window.sahayatriMap) {
        window.sahayatriMap.invalidateSize();
        return;
    }

    if (typeof L === 'undefined') {
        console.warn('Leaflet is not yet loaded.');
        return;
    }

    const mapId = mapElement.id;
    // Default center: Pokhara / Nepal (28.2095, 83.9855)
    const map = L.map(mapId).setView([28.2095, 83.9855], 13);
    window.sahayatriMap = map;

    // OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | SAHAYATRI Safety Platform'
    }).addTo(map);

    // Layer groups for filtering
    const safetyZonesLayer = L.layerGroup().addTo(map);
    const attractionsLayer = L.layerGroup().addTo(map);
    const hotelsLayer = L.layerGroup().addTo(map);
    const emergencyLayer = L.layerGroup().addTo(map);

    // 1. Fetch & Render Safety Zones (Polygons / Circles)
    fetch('api/safety.php?action=zones')
        .then(res => res.json())
        .then(response => {
            if (response.success && response.data && response.data.zones) {
                response.data.zones.forEach(zone => {
                    let color = '#16A34A'; // SAFE
                    if (zone.risk_level === 'CAUTION') color = '#EAB308';
                    if (zone.risk_level === 'HIGH') color = '#F97316';
                    if (zone.risk_level === 'CRITICAL') color = '#DC2626';

                    const circle = L.circle([parseFloat(zone.latitude), parseFloat(zone.longitude)], {
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.35,
                        radius: parseInt(zone.radius_meters)
                    }).addTo(safetyZonesLayer);

                    circle.bindPopup(`
                        <div style="font-family:sans-serif; min-width:180px;">
                            <h4 style="margin:0 0 6px 0;color:${color};">⚠️ ${zone.title}</h4>
                            <p style="margin:0 0 6px 0;font-size:12px;color:#334155;">${zone.description || ''}</p>
                            <span class="badge risk-${zone.risk_level.toLowerCase()}">${zone.risk_badge_html || zone.risk_level}</span>
                            <br><small style="color:#64748B;">Radius: ${zone.radius_meters}m | Risk: ${zone.risk_type}</small>
                        </div>
                    `);
                });
            }
        }).catch(err => console.error("Safety zone layer error:", err));

    // 2. Fetch & Render Attractions
    fetch('api/destinations.php?action=list')
        .then(res => res.json())
        .then(response => {
            if (response.success && response.data && response.data.destinations) {
                response.data.destinations.forEach(dest => {
                    if (!dest.latitude || !dest.longitude) return;
                    const marker = L.marker([parseFloat(dest.latitude), parseFloat(dest.longitude)]).addTo(attractionsLayer);
                    marker.bindPopup(`
                        <div style="font-family:sans-serif;width:200px;">
                            ${dest.image_url ? `<img src="${dest.image_url}" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:6px;">` : ''}
                            <h4 style="margin:0;">🏛️ ${dest.name}</h4>
                            <p style="font-size:11px;color:#666;margin:4px 0;">${dest.category} · ${dest.region}</p>
                        </div>
                    `);
                });
            }
        });

    // 3. Fetch & Render Accommodations
    fetch('api/accommodations.php?action=list')
        .then(res => res.json())
        .then(response => {
            if (response.success && response.data && response.data.accommodations) {
                response.data.accommodations.forEach(hotel => {
                    if (!hotel.latitude || !hotel.longitude) return;
                    const marker = L.marker([parseFloat(hotel.latitude), parseFloat(hotel.longitude)]).addTo(hotelsLayer);
                    marker.bindPopup(`
                        <div style="font-family:sans-serif;width:200px;">
                            <h4 style="margin:0;">🏨 ${hotel.name}</h4>
                            <p style="font-size:12px;margin:4px 0;color:#2563EB;">NPR ${Number(hotel.price_per_night).toLocaleString()} / night</p>
                            <span style="background:#DBEAFE;color:#1D4ED8;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:bold;">🛡️ Trust Score: ${hotel.trust_score}/100</span>
                        </div>
                    `);
                });
            }
        });

    // 4. Fetch & Render Emergency Resources
    fetch('api/sos.php?action=list_resources')
        .then(res => res.json())
        .then(response => {
            if (response.success && response.data && response.data.resources) {
                response.data.resources.forEach(res => {
                    if (!res.latitude || !res.longitude) return;
                    const iconSymbol = res.type === 'Hospital' ? '🏥' : (res.type === 'Tourist Police' ? '🚓' : '🚑');
                    const marker = L.marker([parseFloat(res.latitude), parseFloat(res.longitude)]).addTo(emergencyLayer);
                    marker.bindPopup(`
                        <div style="font-family:sans-serif;">
                            <h4 style="margin:0;">${iconSymbol} ${res.name}</h4>
                            <p style="font-size:12px;margin:4px 0;">📞 <b>${res.phone}</b></p>
                            <p style="font-size:11px;color:#666;margin:0;">${res.address}, ${res.city}</p>
                        </div>
                    `);
                });
            }
        });

    // Add Layer Control
    const overlays = {
        "⚠️ Safety & Risk Zones": safetyZonesLayer,
        "🏛️ Tourist Attractions": attractionsLayer,
        "🏨 Hotels & Homestays": hotelsLayer,
        "🏥 Emergency Services": emergencyLayer
    };
    L.control.layers(null, overlays).addTo(map);

    setTimeout(() => {
        map.invalidateSize();
    }, 250);
}

window.initSahayatriMap = initSahayatriMap;

document.addEventListener('DOMContentLoaded', () => {
    initSahayatriMap();
});
