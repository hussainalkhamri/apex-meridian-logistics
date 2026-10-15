import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '../css/tracking-map.css';
import { createElement, Ship, Plane, Truck, MapPin } from 'lucide';
import { airRoute, routeLine, routePosition, routeDistance, connectRouteEndpoints } from './tracking-route';
import worldMapUrl from '../data/tracking/world.json?url';

const views = new WeakMap();

function seaRoute(origin, destination) {
    return new Promise((resolve, reject) => {
        const worker = new Worker(new URL('./sea-route.worker.js', import.meta.url), { type: 'module' });
        const timeout = setTimeout(() => { worker.terminate(); reject(new Error('Sea route timeout')); }, 20000);
        worker.onmessage = ({ data }) => { clearTimeout(timeout); worker.terminate(); resolve(data.geometry ? routeLine(data.geometry) : null); };
        worker.onerror = () => { clearTimeout(timeout); worker.terminate(); reject(new Error('Sea route failed')); };
        worker.postMessage({ origin, destination });
    });
}

function createView(dialog) {
    const settings = JSON.parse(dialog.querySelector('[data-tracking-map-settings]').textContent);
    const labels = settings.labels;
    const get = (name) => dialog.querySelector(`[data-map-${name}]`);
    const set = (name, value) => { get(name).textContent = value ?? '—'; };
    const feedback = (message) => { set('feedback', message); get('feedback').hidden = !message; };
    let map;
    let tiles;
    let layers;
    let marker;
    let bounds;
    let loadedAt = 0;
    let loading = false;
    let opener;
    let tileFailed = false;
    let baseMapReady = false;
    let detailedTilesLoaded = false;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

    const fit = () => { if (map && bounds?.isValid()) map.fitBounds(bounds, { padding: [36, 36], maxZoom: 9, animate: !reduced }); };
    const activateTab = (name, focus = false) => {
        dialog.querySelectorAll('[data-map-tab]').forEach((tab) => {
            const selected = tab.dataset.mapTab === name;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
            document.getElementById(tab.getAttribute('aria-controls')).hidden = !selected;
            if (selected && focus) tab.focus();
        });
        if (name === 'map' && map) requestAnimationFrame(() => { map.invalidateSize(); fit(); });
    };

    get('close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => { opener?.focus(); });
    dialog.querySelectorAll('[data-map-tab]').forEach((tab) => {
        tab.addEventListener('click', () => activateTab(tab.dataset.mapTab));
        tab.addEventListener('keydown', (event) => {
            if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();
                activateTab(event.key === 'Home' ? 'map' : event.key === 'End' ? 'details' : tab.dataset.mapTab === 'map' ? 'details' : 'map', true);
            }
        });
    });
    get('fit').addEventListener('click', fit);
    get('locate').addEventListener('click', () => { if (marker) { map.setView(marker.getLatLng(), Math.max(map.getZoom(), 6), { animate: !reduced }); marker.openPopup(); } });
    get('refresh').addEventListener('click', () => { tileFailed = false; tiles?.redraw(); load(); });
    new ResizeObserver(() => map?.invalidateSize()).observe(get('canvas'));

    const popup = (heading, value) => {
        const node = document.createElement('div');
        node.className = 'tracking-map-popup';
        const title = document.createElement('strong');
        title.textContent = heading;
        const detail = document.createElement('p');
        detail.textContent = value;
        node.append(title, detail);
        return node;
    };

    async function loadBaseMap() {
        const response = await fetch(worldMapUrl);
        if (!response.ok) return;
        const world = await response.json();
        map.createPane('localGeography').style.zIndex = 150;
        L.geoJSON(world, { pane: 'localGeography', interactive: false, style: { color: '#a9bdc0', weight: 0.8, fillColor: '#eef2ee', fillOpacity: 1 } }).addTo(map);
        const countryLabels = L.layerGroup().addTo(map);
        const updateLabels = () => {
            countryLabels.clearLayers();
            const occupied = [];
            for (const { properties: country } of world.features) {
                const latlng = [country.label[1], country.label[0]];
                if (map.getZoom() < country.min_zoom || !map.getBounds().contains(latlng)) continue;
                const point = map.latLngToContainerPoint(latlng);
                if (occupied.some(p => Math.abs(p.x - point.x) < 100 && Math.abs(p.y - point.y) < 24)) continue;
                occupied.push(point);
                const label = document.createElement('span');
                label.textContent = document.documentElement.lang === 'ar' ? country.name_ar : country.name;
                L.marker(latlng, { pane: 'localGeography', interactive: false, keyboard: false, icon: L.divIcon({ html: label, className: 'tracking-country-label', iconSize: [100, 20], iconAnchor: [50, 10] }) }).addTo(countryLabels);
            }
        };
        map.on('moveend', updateLabels);
        updateLabels();
        map.attributionControl.addAttribution('<a href="https://www.naturalearthdata.com/">Natural Earth</a>');
        baseMapReady = true;
        get('basemap').hidden = detailedTilesLoaded;
    }

    async function load() {
        if (loading) return;
        loading = true;
        get('refresh').disabled = true;
        get('locate').disabled = true;
        layers?.clearLayers();
        marker = null;
        bounds = null;
        feedback(labels.loading);
        try {
            const response = await fetch(settings.url, { headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(15000) });
            if (!response.ok) throw new Error('Map data unavailable');
            const data = await response.json();
            const schedule = data.schedule;
            set('state', labels[schedule.state]);
            set('percent', schedule.percent === null ? '—' : `${schedule.percent}%`);
            get('progress').style.width = `${schedule.percent ?? 0}%`;
            set('etd', schedule.etd); set('eta', schedule.eta);
            set('duration', schedule.total_days === null ? '—' : `${schedule.total_days} ${labels.days}`);
            set('remaining', schedule.remaining_days === null ? '—' : `${schedule.remaining_days} ${labels.days}`);
            set('calculated', `${labels.calculated}: ${new Date(schedule.calculated_at).toLocaleString(document.documentElement.lang, { timeZone: schedule.timezone })} (${schedule.timezone})`);
            set('distance', '—');
            set('route-note', labels.route_notes[data.mode] || labels.multimodal);
            if (!data.origin || !data.destination) { feedback(labels.missing_coordinates); return; }
            set('origin', data.origin.label); set('destination', data.destination.label);
            let line = null;
            try {
                if (data.mode === 'SEA') line = await seaRoute(data.origin.coordinates, data.destination.coordinates);
                else if (data.mode === 'AIR') line = airRoute(data.origin.coordinates, data.destination.coordinates);
                else if (data.mode === 'LAND' && data.road_geometry) line = routeLine(data.road_geometry);
                if (line && data.mode !== 'AIR') line = connectRouteEndpoints(line, data.origin.coordinates, data.destination.coordinates, data.mode === 'SEA' ? 200 : 25);
            } catch { line = null; }

            const routeCoords = line?.geometry.coordinates;
            const origin = routeCoords?.[0] || data.origin.coordinates;
            const destination = routeCoords?.[routeCoords.length - 1] || data.destination.coordinates;
            const latLng = (point) => [point[1], point[0]];
            bounds = L.latLngBounds([latLng(origin), latLng(destination)]);
            for (const [point, label, name] of [[origin, labels.origin, data.origin.label], [destination, labels.destination, data.destination.label]]) {
                L.circleMarker(latLng(point), { radius: 7, weight: 3, color: '#ffffff', fillColor: '#087b9f', fillOpacity: 1 })
                    .addTo(layers).bindPopup(popup(label, name));
            }
            if (line) {
                const coordinates = routeCoords.map(latLng);
                const color = { SEA: '#0785ad', AIR: '#7856b4', LAND: '#14815f' }[data.mode];
                L.polyline(coordinates, { color: '#ffffff', opacity: 0.85, weight: 7 }).addTo(layers);
                L.polyline(coordinates, { color, weight: 4, opacity: 0.95, dashArray: data.mode === 'AIR' ? '9 7' : undefined }).addTo(layers);
                bounds.extend(L.latLngBounds(coordinates));
                set('distance', `${Math.round(routeDistance(line)).toLocaleString()} ${labels.km}`);
                const position = routePosition(line, schedule.fraction);
                if (position) {
                    const icon = document.createElement('div');
                    icon.className = 'tracking-vehicle-icon';
                    icon.append(createElement({ SEA: Ship, AIR: Plane, LAND: Truck }[data.mode] || MapPin, { width: 21, height: 21 }));
                    marker = L.marker(latLng(position.coordinates), {
                        icon: L.divIcon({ html: icon, className: 'tracking-vehicle-marker', iconSize: [42, 42], iconAnchor: [21, 21] }),
                        title: labels.locate, alt: labels.locate, zIndexOffset: 1000,
                    }).addTo(layers).bindPopup(popup(labels.estimated, `${schedule.percent}% · ${labels[schedule.state]}`));
                    get('locate').disabled = false;
                }
                feedback(tileFailed && !baseMapReady ? labels.tiles_error : '');
            } else {
                feedback(data.mode === 'MULTIMODAL' ? labels.multimodal : labels.route_unavailable);
            }
            loadedAt = Date.now();
            fit();
        } catch {
            feedback(labels.fetch_error);
        } finally {
            loading = false;
            get('refresh').disabled = false;
        }
    }

    return {
        async open() {
            opener = document.activeElement;
            if (!dialog.open) dialog.showModal();
            activateTab('map');
            if (!map) {
                map = L.map(get('canvas'), { center: [20, 45], zoom: 2, minZoom: 2, maxZoom: 18, zoomAnimation: !reduced, fadeAnimation: !reduced });
                map.zoomControl.setPosition('topleft');
                loadBaseMap().catch(() => { if (tileFailed && !loading) feedback(labels.tiles_error); });
                tiles = L.tileLayer(settings.tileUrl, { attribution: settings.tileAttribution, maxZoom: 19, referrerPolicy: 'strict-origin-when-cross-origin' }).addTo(map);
                tiles.on('tileload', () => { detailedTilesLoaded = true; get('basemap').hidden = true; });
                tiles.on('tileerror', () => { tileFailed = true; if (baseMapReady) get('basemap').hidden = false; else if (!loading) feedback(labels.tiles_error); });
                layers = L.layerGroup().addTo(map);
                L.control.scale({ imperial: false }).addTo(map);
            }
            map.invalidateSize();
            if (Date.now() - loadedAt > 60000) await load();
            else fit();
        },
    };
}

export async function openTrackingMap(dialog) {
    if (!dialog) return;
    if (!views.has(dialog)) views.set(dialog, createView(dialog));
    await views.get(dialog).open();
}
