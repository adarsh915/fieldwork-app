@extends('admin.layouts.app')

@section('title', 'Live Location Tracking')
@section('breadcrumb', 'FIELD OPERATIONS')
@section('page_title', 'Live Location Tracking')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<style>
    .tracking-controls{background:#fff;padding:.9rem 1rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.1);margin-bottom:1rem}
    .tracking-layout{display:flex;gap:12px;height:calc(100vh - 240px);min-height:520px}
    .worker-panel{width:300px;flex:none;background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.1);display:flex;flex-direction:column;overflow:hidden}
    .panel-top{padding:12px;border-bottom:1px solid #e5e7eb}
    .panel-top input[type=search]{width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px}
    .chips{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
    .chip{font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer}
    .chip.on{background:#111827;color:#fff;border-color:#111827}
    #workerList{flex:1;overflow-y:auto}
    .w-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid #f3f4f6;cursor:pointer}
    .w-item:hover{background:#f9fafb}
    .w-item.sel{background:#eef2ff}
    .w-av{width:34px;height:34px;border-radius:50%;color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;flex:none}
    .w-name{font-size:13px;font-weight:600;color:#111827;line-height:1.2}
    .w-sub{font-size:11px;color:#6b7280}
    .map-wrap{flex:1;position:relative;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);background:#e5e7eb}
    #tracking-map{height:100%;width:100%}
    @media (max-width:900px){.tracking-layout{flex-direction:column;height:auto}.worker-panel{width:100%;max-height:260px}.map-wrap{height:65vh}}

    /* markers */
    .wm-wrap{position:relative;width:40px;height:40px}
    .worker-marker{width:40px;height:40px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff}
    .marker-active{background:#10b981;animation:pulse 2s infinite}
    .marker-idle{background:#f59e0b}
    .marker-offline{background:#9ca3af}
    .wm-label{position:absolute;top:44px;left:50%;transform:translateX(-50%);white-space:nowrap;background:#fff;color:#111827;font-size:11px;font-weight:600;padding:2px 7px;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,.3);pointer-events:none}
    .wm-label small{color:#6b7280;font-weight:500}
    @keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.7)}50%{box-shadow:0 0 0 10px rgba(16,185,129,0)}}
    .route-arrow{font-size:14px;line-height:14px;color:#4f46e5;text-align:center;text-shadow:0 0 2px #fff}

    /* popup */
    .leaflet-popup-content-wrapper{border-radius:12px;padding:0}
    .leaflet-popup-content{margin:0;min-width:250px}
    .popup-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:12px 16px;border-radius:12px 12px 0 0}
    .popup-body{padding:14px 16px}
    .stat-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #e5e7eb}
    .stat-row:last-of-type{border-bottom:none}
    .stat-label{font-size:13px;color:#6b7280}
    .stat-value{font-size:13px;font-weight:600;color:#111827}
    .status-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;text-transform:uppercase}
    .status-active{background:#d1fae5;color:#065f46}
    .status-idle{background:#fed7aa;color:#92400e}
    .status-offline{background:#e5e7eb;color:#374151}
    .pop-actions{display:flex;gap:8px;margin-top:10px}
    .pop-actions a,.pop-actions button{flex:1;text-align:center;font-size:12px;font-weight:600;padding:7px 8px;border-radius:8px;border:1px solid #c7d2fe;background:#eef2ff;color:#3730a3;cursor:pointer;text-decoration:none}
    .pop-actions button.primary{background:#4f46e5;color:#fff;border-color:#4f46e5}

    /* map controls */
    .legend-box{background:#fff;padding:10px 14px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.15)}
    .legend-item{display:flex;align-items:center;gap:8px;margin:5px 0;font-size:12px}
    .legend-color{width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.3)}
    .map-btn{display:flex;align-items:center;justify-content:center;width:34px;height:34px;background:#fff;border:none;border-bottom:1px solid #e5e7eb;cursor:pointer;font-size:16px;color:#374151}
    .map-btn:hover{background:#f3f4f6}
    .leaflet-control-layers{border-radius:8px!important;box-shadow:0 2px 8px rgba(0,0,0,.15)!important}
</style>
@endpush

@section('content')

<div class="tracking-controls">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-ink-600">Auto Refresh:</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="autoRefresh" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                </label>
                <span class="text-xs text-ink-400" id="refreshStatus">Every 30s</span>
            </div>
            <button type="button" id="refreshNow" class="btn-sm btn-secondary">
                <x-lucide-refresh-cw class="w-4 h-4" /> Refresh Now
            </button>
            <button type="button" id="showAll" class="btn-sm btn-secondary">Show all</button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-ink-500">Route date</span>
                <input type="date" id="routeDate" class="text-sm border border-gray-300 rounded-md px-2 py-1">
                <button type="button" id="clearRoute" class="btn-sm btn-secondary">Clear route</button>
            </div>
            <label id="shopsToggleWrap" class="hidden items-center gap-2 text-sm text-ink-600 cursor-pointer">
                <input type="checkbox" id="shopsToggle"> Show shops &amp; geofence
            </label>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-sm"><span class="font-semibold text-brand-600" id="activeCount">0</span> <span class="text-ink-500">Active</span></div>
            <div class="text-sm"><span class="font-semibold text-ink-700" id="totalCount">0</span> <span class="text-ink-500">Tracked</span></div>
            <div class="text-xs text-ink-400" id="lastUpdated">Last updated: --</div>
        </div>
    </div>
</div>

<div class="tracking-layout">
    <aside class="worker-panel">
        <div class="panel-top">
            <input type="search" id="workerSearch" placeholder="Search worker or ID...">
            <div class="chips" id="statusChips">
                <button class="chip on" data-status="all">All</button>
                <button class="chip" data-status="active">Active</button>
                <button class="chip" data-status="idle">Idle</button>
                <button class="chip" data-status="offline">Offline</button>
            </div>
        </div>
        <div id="workerList"></div>
    </aside>
    <div class="map-wrap" id="mapWrap"><div id="tracking-map"></div></div>
</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
(function () {
    'use strict';

    // ─── Config ────────────────────────────────────────────────────────
    const URLS = {
        latest: "{{ route('admin.tracking.api.latest-locations') }}",
        route:  "{{ url('admin/tracking/api/route-history') }}",
        // Optional: create a route returning {success:true, shops:[{id,name,latitude,longitude,location_status}]}
        // and name it admin.tracking.api.shops. The "Show shops" toggle appears only if it exists.
        shops:  @json(Route::has('admin.tracking.api.shops') ? route('admin.tracking.api.shops') : '')
    };
    const GEOFENCE_RADIUS = 300;      // meters (match your geofence_radius_meters setting)
    const REFRESH_MS = 30000;
    const STOP_MIN_MINUTES = 5;       // a halt this long is shown as a "stop"
    const STOP_RADIUS_M = 40;

    // ─── State ─────────────────────────────────────────────────────────
    let map, cluster, routeLayer, shopLayer, accCircle = null;
    let autoTimer = null, loading = false, firstLoad = true, shopsLoaded = false;
    let filterStatus = 'all', searchText = '', selectedId = null;
    const markers = {}, workers = {};

    // ─── Helpers ───────────────────────────────────────────────────────
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const initials = n => String(n || '?').split(' ').filter(Boolean).map(x => x[0]).join('').toUpperCase().substring(0, 2);
    const statusColor = s => s === 'active' ? '#10b981' : s === 'idle' ? '#f59e0b' : '#9ca3af';
    const statusLabel = s => ({active: 'Active', idle: 'Idle', offline: 'Offline'}[s] || 'Offline');
    const markerClass = s => 'marker-' + (['active', 'idle'].includes(s) ? s : 'offline');
    const localDate = () => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };

    function ago(min) {
        min = Math.round(Number(min) || 0);
        if (min < 1) return 'now';
        if (min < 60) return min + 'm';
        return Math.floor(min / 60) + 'h ' + (min % 60) + 'm';
    }
    function distM(a, b) {
        const R = 6371000, r = d => d * Math.PI / 180;
        const dLat = r(b[0] - a[0]), dLng = r(b[1] - a[1]);
        const h = Math.sin(dLat / 2) ** 2 + Math.cos(r(a[0])) * Math.cos(r(b[0])) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(h));
    }
    function bearing(a, b) {
        const r = d => d * Math.PI / 180, t = d => d * 180 / Math.PI;
        const y = Math.sin(r(b[1] - a[1])) * Math.cos(r(b[0]));
        const x = Math.cos(r(a[0])) * Math.sin(r(b[0])) - Math.sin(r(a[0])) * Math.cos(r(b[0])) * Math.cos(r(b[1] - a[1]));
        return (t(Math.atan2(y, x)) + 360) % 360;
    }
    function timeOf(p) {
        const v = p.recorded_at || p.captured_at || p.created_at || p.timestamp || p.time;
        const t = v ? new Date(v).getTime() : NaN;
        return isNaN(t) ? null : t;
    }
    const fmtTime = t => t ? new Date(t).toLocaleTimeString('en-IN', {hour: '2-digit', minute: '2-digit'}) : '';

    function toast(message, type = 'success') {
        const el = document.createElement('div');
        el.className = 'fixed top-20 right-6 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm ' +
            (type === 'success' ? 'bg-green-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-red-500');
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    }

    // ─── Map setup ─────────────────────────────────────────────────────
    function initMap() {
        map = L.map('tracking-map', {zoomControl: false}).setView([22.9, 79.0], 5);

        // Keyless tile sources (Carto basemaps now show "API KEY REQUIRED")
        const street = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles © Esri', maxZoom: 19
        }).addTo(map);
        const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors', maxZoom: 19
        });
        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles © Esri', maxZoom: 19
        });

        L.control.layers({'Map': street, 'OpenStreetMap': osm, 'Satellite': satellite}, null, {position: 'topright', collapsed: true}).addTo(map);
        L.control.zoom({position: 'bottomright'}).addTo(map);
        L.control.scale({position: 'bottomleft', imperial: false}).addTo(map);

        cluster = L.markerClusterGroup({maxClusterRadius: 45, showCoverageOnHover: false}).addTo(map);
        routeLayer = L.layerGroup().addTo(map);
        shopLayer = L.layerGroup();

        map.on('popupclose', clearAcc);
        addLegend();
        addMapButtons();
    }

    function addLegend() {
        const c = L.control({position: 'bottomleft'});
        c.onAdd = () => {
            const d = L.DomUtil.create('div', 'legend-box');
            d.innerHTML = '<div style="font-weight:600;margin-bottom:6px;font-size:12px">Worker status</div>' +
                '<div class="legend-item"><div class="legend-color" style="background:#10b981"></div>Active (&lt; 5 min)</div>' +
                '<div class="legend-item"><div class="legend-color" style="background:#f59e0b"></div>Idle (5-30 min)</div>' +
                '<div class="legend-item"><div class="legend-color" style="background:#9ca3af"></div>Offline (&gt; 30 min)</div>';
            return d;
        };
        c.addTo(map);
    }

    function addMapButtons() {
        const c = L.control({position: 'topleft'});
        c.onAdd = () => {
            const d = L.DomUtil.create('div', 'leaflet-bar');
            d.innerHTML = '<button class="map-btn" id="btnFull" title="Fullscreen">⛶</button>' +
                          '<button class="map-btn" id="btnAll" title="Show all workers">⌖</button>';
            L.DomEvent.disableClickPropagation(d);
            return d;
        };
        c.addTo(map);
        document.getElementById('btnFull').addEventListener('click', () => {
            const wrap = document.getElementById('mapWrap');
            if (document.fullscreenElement) document.exitFullscreen();
            else wrap.requestFullscreen && wrap.requestFullscreen();
        });
        document.getElementById('btnAll').addEventListener('click', fitAll);
        document.addEventListener('fullscreenchange', () => setTimeout(() => map.invalidateSize(), 200));
    }

    // ─── Worker markers (updated in place, no flicker) ─────────────────
    function buildIcon(w) {
        return L.divIcon({
            className: 'custom-marker',
            html: '<div class="wm-wrap"><div class="worker-marker ' + markerClass(w.status) + '">' + esc(initials(w.name)) + '</div>' +
                  '<div class="wm-label">' + esc(w.name) + ' <small>' + esc(ago(w.minutes_ago)) + '</small></div></div>',
            iconSize: [40, 40], iconAnchor: [20, 20]
        });
    }

    function popupHtml(w) {
        const t = ago(w.minutes_ago) + ' ago';
        const lat = Number(w.latitude), lng = Number(w.longitude);
        return '<div class="popup-header"><div style="font-weight:600;font-size:15px">' + esc(w.name) + '</div>' +
            '<div style="font-size:12px;opacity:.9;margin-top:2px">' + esc(w.employee_id || 'No ID') + '</div></div>' +
            '<div class="popup-body">' +
            '<div class="stat-row"><span class="stat-label">Status</span><span class="stat-value"><span class="status-badge status-' + esc(w.status) + '">' + statusLabel(w.status) + '</span></span></div>' +
            '<div class="stat-row"><span class="stat-label">Last seen</span><span class="stat-value">' + esc(t) + '</span></div>' +
            '<div class="stat-row"><span class="stat-label">Location</span><span class="stat-value">' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</span></div>' +
            (w.accuracy ? '<div class="stat-row"><span class="stat-label">Accuracy</span><span class="stat-value">±' + Number(w.accuracy).toFixed(0) + ' m</span></div>' : '') +
            (w.battery_level ? '<div class="stat-row"><span class="stat-label">Battery</span><span class="stat-value">' + Number(w.battery_level).toFixed(0) + '%</span></div>' : '') +
            '<div class="pop-actions">' +
            '<button class="primary" onclick="window.liveTracking.showRoute(\'' + esc(w.user_id) + '\')">View route</button>' +
            '<a target="_blank" rel="noopener" href="https://www.google.com/maps?q=' + lat + ',' + lng + '">Open in Maps</a>' +
            (w.phone ? '<a href="tel:' + esc(w.phone) + '">Call</a>' : '') +
            '</div></div>';
    }

    function animateTo(m, to) {
        const from = m.getLatLng();
        if (Math.abs(from.lat - to[0]) < 1e-7 && Math.abs(from.lng - to[1]) < 1e-7) return;
        const start = performance.now(), dur = 800;
        (function step(now) {
            const k = Math.min(1, (now - start) / dur);
            m.setLatLng([from.lat + (to[0] - from.lat) * k, from.lng + (to[1] - from.lng) * k]);
            if (k < 1) requestAnimationFrame(step);
        })(start);
    }

    function syncWorkers(list) {
        const seen = new Set();
        list.forEach(w => {
            const id = String(w.user_id);
            seen.add(id);
            workers[id] = w;
            let m = markers[id];
            if (m) {
                animateTo(m, [w.latitude, w.longitude]);
                m.setIcon(buildIcon(w));
                m.setPopupContent(popupHtml(w));
            } else {
                m = L.marker([w.latitude, w.longitude], {icon: buildIcon(w)});
                m.bindPopup(popupHtml(w), {maxWidth: 300, className: 'worker-popup'});
                m.on('popupopen', () => { selectedId = id; showAcc(id); renderList(); });
                markers[id] = m;
            }
        });
        Object.keys(markers).forEach(id => {
            if (!seen.has(id)) { cluster.removeLayer(markers[id]); delete markers[id]; delete workers[id]; }
        });
        applyFilter();
        renderList();
        if (firstLoad && list.length) { fitAll(); firstLoad = false; }
    }

    function matches(w) {
        const okStatus = filterStatus === 'all' || w.status === filterStatus;
        const okText = !searchText || (String(w.name) + ' ' + (w.employee_id || '')).toLowerCase().includes(searchText);
        return okStatus && okText;
    }

    function applyFilter() {
        Object.keys(markers).forEach(id => {
            const show = matches(workers[id]), has = cluster.hasLayer(markers[id]);
            if (show && !has) cluster.addLayer(markers[id]);
            if (!show && has) cluster.removeLayer(markers[id]);
        });
    }

    function fitAll() {
        const pts = Object.keys(markers).filter(id => matches(workers[id])).map(id => [workers[id].latitude, workers[id].longitude]);
        if (pts.length) map.fitBounds(L.latLngBounds(pts), {padding: [60, 60], maxZoom: 15});
    }

    function showAcc(id) {
        clearAcc();
        const w = workers[id];
        if (w && w.accuracy) {
            accCircle = L.circle([w.latitude, w.longitude], {radius: Number(w.accuracy), color: '#4f46e5', weight: 1, fillOpacity: 0.12}).addTo(map);
        }
    }
    function clearAcc() { if (accCircle) { map.removeLayer(accCircle); accCircle = null; } }

    // ─── Side panel ────────────────────────────────────────────────────
    function renderList() {
        const rank = {active: 0, idle: 1, offline: 2};
        const items = Object.values(workers).filter(matches)
            .sort((a, b) => (rank[a.status] ?? 3) - (rank[b.status] ?? 3) || String(a.name).localeCompare(String(b.name)));
        const el = document.getElementById('workerList');
        if (!items.length) { el.innerHTML = '<div style="padding:16px;font-size:13px;color:#6b7280">No workers found</div>'; return; }
        el.innerHTML = items.map(w =>
            '<div class="w-item ' + (String(w.user_id) === String(selectedId) ? 'sel' : '') + '" data-id="' + esc(w.user_id) + '">' +
            '<div class="w-av" style="background:' + statusColor(w.status) + '">' + esc(initials(w.name)) + '</div>' +
            '<div><div class="w-name">' + esc(w.name) + '</div>' +
            '<div class="w-sub">' + statusLabel(w.status) + ' · ' + esc(ago(w.minutes_ago)) + (w.battery_level ? ' · ' + Number(w.battery_level).toFixed(0) + '%' : '') + '</div></div></div>'
        ).join('');
    }

    function focusWorker(id) {
        const m = markers[id];
        if (!m) return;
        selectedId = id;
        map.flyTo(m.getLatLng(), 16, {duration: 0.8});
        map.once('moveend', () => {
            if (cluster.hasLayer(m)) cluster.zoomToShowLayer(m, () => m.openPopup());
        });
    }

    // ─── Route view ────────────────────────────────────────────────────
    async function showRoute(userId) {
        const date = document.getElementById('routeDate').value || localDate();
        try {
            const res = await fetch(URLS.route + '/' + encodeURIComponent(userId) + '?date=' + date, {headers: {'Accept': 'application/json'}});
            const data = await res.json();
            routeLayer.clearLayers();
            const pts = data.success && data.data && data.data.route ? data.data.route : [];
            if (!pts.length) { toast('No route data for ' + date, 'warning'); return; }

            const ll = pts.map(p => [Number(p.latitude), Number(p.longitude)]);
            const line = L.polyline(ll, {color: '#4f46e5', weight: 4, opacity: 0.8}).addTo(routeLayer);

            // direction arrows
            const gap = Math.max(1, Math.floor(ll.length / 12));
            for (let i = 0; i + 1 < ll.length; i += gap) {
                if (distM(ll[i], ll[i + 1]) < 5) continue;
                L.marker(ll[i], {interactive: false, icon: L.divIcon({
                    className: '', iconSize: [14, 14], iconAnchor: [7, 7],
                    html: '<div class="route-arrow" style="transform:rotate(' + bearing(ll[i], ll[i + 1]).toFixed(0) + 'deg)">▲</div>'
                })}).addTo(routeLayer);
            }

            // hover points with time
            const step = Math.ceil(ll.length / 400);
            ll.forEach((p, i) => {
                if (i % step) return;
                const t = fmtTime(timeOf(pts[i]));
                L.circleMarker(p, {radius: 4, color: '#4f46e5', weight: 1, fillColor: '#fff', fillOpacity: 1})
                    .bindTooltip(t || ('Point ' + (i + 1))).addTo(routeLayer);
            });

            // stops (halts of STOP_MIN_MINUTES or more)
            let anchor = 0;
            const addStop = (i0, i1) => {
                const t0 = timeOf(pts[i0]), t1 = timeOf(pts[i1]);
                if (t0 && t1 && (t1 - t0) / 60000 >= STOP_MIN_MINUTES) {
                    L.circleMarker(ll[i0], {radius: 8, color: '#b45309', weight: 2, fillColor: '#f59e0b', fillOpacity: 0.9})
                        .bindTooltip('Stop · ' + Math.round((t1 - t0) / 60000) + ' min (' + fmtTime(t0) + ' - ' + fmtTime(t1) + ')').addTo(routeLayer);
                }
            };
            for (let i = 1; i < ll.length; i++) {
                if (distM(ll[anchor], ll[i]) > STOP_RADIUS_M) { addStop(anchor, i - 1); anchor = i; }
            }
            addStop(anchor, ll.length - 1);

            // start / end
            const dot = (c, txt) => L.divIcon({className: '', iconSize: [22, 22], iconAnchor: [11, 11],
                html: '<div style="width:22px;height:22px;border-radius:50%;background:' + c + ';color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)">' + txt + '</div>'});
            L.marker(ll[0], {icon: dot('#16a34a', 'S')}).bindTooltip('Start ' + fmtTime(timeOf(pts[0]))).addTo(routeLayer);
            L.marker(ll[ll.length - 1], {icon: dot('#dc2626', 'E')}).bindTooltip('End ' + fmtTime(timeOf(pts[pts.length - 1]))).addTo(routeLayer);

            map.fitBounds(line.getBounds(), {padding: [60, 60]});
            toast('Route loaded: ' + (data.data.total_distance_km ?? '?') + ' km on ' + date);
        } catch (e) {
            console.error(e);
            toast('Failed to load route', 'error');
        }
    }

    // ─── Shops & geofence (optional) ───────────────────────────────────
    async function loadShops() {
        if (shopsLoaded || !URLS.shops) return;
        try {
            const res = await fetch(URLS.shops, {headers: {'Accept': 'application/json'}});
            const data = await res.json();
            (data.shops || []).forEach(s => {
                if (s.latitude == null || s.longitude == null) return;
                const ll = [Number(s.latitude), Number(s.longitude)];
                const col = s.location_status === 'verified' ? '#16a34a' : s.location_status === 'provisional' ? '#f59e0b' : '#dc2626';
                L.circle(ll, {radius: GEOFENCE_RADIUS, color: col, weight: 1, dashArray: '4 4', fillOpacity: 0.05}).addTo(shopLayer);
                L.circleMarker(ll, {radius: 6, color: '#fff', weight: 2, fillColor: col, fillOpacity: 1})
                    .bindTooltip(esc(s.name) + ' · ' + esc(s.location_status || 'no location')).addTo(shopLayer);
            });
            shopsLoaded = true;
        } catch (e) { console.error(e); toast('Failed to load shops', 'error'); }
    }

    // ─── Data refresh ──────────────────────────────────────────────────
    async function fetchLatest() {
        if (loading) return;
        loading = true;
        try {
            const res = await fetch(URLS.latest, {headers: {'Accept': 'application/json'}});
            const data = await res.json();
            if (data.success) {
                syncWorkers(data.locations);
                document.getElementById('activeCount').textContent = data.locations.filter(w => w.status === 'active').length;
                document.getElementById('totalCount').textContent = data.count ?? data.locations.length;
                document.getElementById('lastUpdated').textContent = 'Last updated: ' +
                    new Date().toLocaleTimeString('en-IN', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            }
        } catch (e) {
            console.error('Error fetching locations:', e);
        } finally { loading = false; }
    }

    function startAuto() { stopAuto(); autoTimer = setInterval(fetchLatest, REFRESH_MS); }
    function stopAuto() { if (autoTimer) { clearInterval(autoTimer); autoTimer = null; } }

    // ─── Wire up ───────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initMap();
        document.getElementById('routeDate').value = localDate();
        fetchLatest();
        startAuto();

        document.getElementById('refreshNow').addEventListener('click', fetchLatest);
        document.getElementById('showAll').addEventListener('click', fitAll);
        document.getElementById('clearRoute').addEventListener('click', () => routeLayer.clearLayers());

        document.getElementById('autoRefresh').addEventListener('change', e => {
            if (e.target.checked) { startAuto(); document.getElementById('refreshStatus').textContent = 'Every 30s'; }
            else { stopAuto(); document.getElementById('refreshStatus').textContent = 'Disabled'; }
        });

        document.getElementById('workerSearch').addEventListener('input', e => {
            searchText = e.target.value.trim().toLowerCase();
            applyFilter(); renderList();
        });
        document.getElementById('statusChips').addEventListener('click', e => {
            const b = e.target.closest('.chip'); if (!b) return;
            document.querySelectorAll('#statusChips .chip').forEach(c => c.classList.remove('on'));
            b.classList.add('on');
            filterStatus = b.dataset.status;
            applyFilter(); renderList();
        });
        document.getElementById('workerList').addEventListener('click', e => {
            const it = e.target.closest('.w-item'); if (it) focusWorker(it.dataset.id);
        });

        if (URLS.shops) {
            const wrap = document.getElementById('shopsToggleWrap');
            wrap.classList.remove('hidden'); wrap.classList.add('flex');
            document.getElementById('shopsToggle').addEventListener('change', async e => {
                if (e.target.checked) { await loadShops(); shopLayer.addTo(map); }
                else map.removeLayer(shopLayer);
            });
        }
    });

    window.liveTracking = {showRoute: showRoute};
    window.addEventListener('beforeunload', stopAuto);
})();
</script>
@endpush