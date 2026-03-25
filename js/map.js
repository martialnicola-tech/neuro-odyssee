/* ============================================
   LA NEURO-ODYSSÉE — Interactive Leaflet Map
   ============================================ */

const WAYPOINTS = [
  // ── SUISSE : ViaJacobi (Itinéraire national n°4) ──────────────────
  {
    name: "St-Maurice",
    coords: [46.2192, 7.0056],
    type: "start",
    km: 0,
    country: "🇨🇭",
    desc: "Point de départ — Abbaye de St-Maurice, Valais · ViaJacobi"
  },
  {
    name: "Villeneuve",
    coords: [46.3943, 6.9280],
    type: "stage",
    km: 28,
    country: "🇨🇭",
    desc: "Rives du Léman — Entrée en Riviera vaudoise"
  },
  {
    name: "Montreux",
    coords: [46.4312, 6.9107],
    type: "stage",
    km: 42,
    country: "🇨🇭",
    desc: "Riviera vaudoise — Château de Chillon"
  },
  {
    name: "Vevey",
    coords: [46.4628, 6.8442],
    type: "stage",
    km: 48,
    country: "🇨🇭",
    desc: "Bords du lac Léman — Vignobles de Lavaux (UNESCO)"
  },
  {
    name: "Lausanne",
    coords: [46.5197, 6.6323],
    type: "major",
    km: 52,
    country: "🇨🇭",
    desc: "Grande ville étape — Cathédrale gothique · Chemin de Romandie"
  },
  {
    name: "Morges",
    coords: [46.5115, 6.4997],
    type: "stage",
    km: 65,
    country: "🇨🇭",
    desc: "Côte vaudoise — Château de Morges"
  },
  {
    name: "Rolle",
    coords: [46.4572, 6.3373],
    type: "stage",
    km: 78,
    country: "🇨🇭",
    desc: "Villages viticoles — Gilly, Bursins"
  },
  {
    name: "Nyon",
    coords: [46.3833, 6.2383],
    type: "stage",
    km: 100,
    country: "🇨🇭",
    desc: "Colonie romaine — Bords du lac et château"
  },
  {
    name: "Genève",
    coords: [46.2044, 6.1432],
    type: "major",
    km: 122,
    country: "🇨🇭",
    desc: "Fin de la ViaJacobi suisse — Départ Via Gebennensis vers Le Puy"
  },
  // ── FRANCE : Via Gebennensis puis GR 65 ──────────────────────────
  {
    name: "Le Puy-en-Velay",
    coords: [45.0433, 3.8858],
    type: "major",
    km: 472,
    country: "🇫🇷",
    desc: "Porte officielle du GR 65 — Via Podiensis · Cathédrale du Rocher"
  },
  {
    name: "Figeac",
    coords: [44.6042, 2.0317],
    type: "stage",
    km: 650,
    country: "🇫🇷",
    desc: "Lot — Ville médiévale sur les rives du Célé"
  },
  {
    name: "Cahors",
    coords: [44.4483, 1.4411],
    type: "stage",
    km: 760,
    country: "🇫🇷",
    desc: "Pont Valentré — Étape symbolique du chemin"
  },
  {
    name: "Moissac",
    coords: [44.1055, 1.0843],
    type: "stage",
    km: 850,
    country: "🇫🇷",
    desc: "Abbaye Saint-Pierre — Chef-d'œuvre de l'art roman"
  },
  {
    name: "Condom",
    coords: [43.9593, 0.3737],
    type: "stage",
    km: 940,
    country: "🇫🇷",
    desc: "Gascogne — Cathédrale Saint-Pierre, Armagnac"
  },
  {
    name: "St-Jean-Pied-de-Port",
    coords: [43.1630, -1.2382],
    type: "major",
    km: 1222,
    country: "🇫🇷",
    desc: "Passage des Pyrénées — Dernière ville française du GR 65"
  },
  // ── ESPAGNE : Camino Francés ──────────────────────────────────────
  {
    name: "Pampelune",
    coords: [42.8188, -1.6440],
    type: "stage",
    km: 1300,
    country: "🇪🇸",
    desc: "Navarre, Pamplona — Entrée en Espagne"
  },
  {
    name: "Burgos",
    coords: [42.3440, -3.6969],
    type: "major",
    km: 1520,
    country: "🇪🇸",
    desc: "Cathédrale gothique — Cœur de la Castille"
  },
  {
    name: "León",
    coords: [42.5987, -5.5671],
    type: "stage",
    km: 1730,
    country: "🇪🇸",
    desc: "Vitraux de la cathédrale — Avant-dernière grande étape"
  },
  {
    name: "Santiago de Compostela",
    coords: [42.8782, -8.5448],
    type: "end",
    km: 1900,
    country: "🇪🇸",
    desc: "Destination finale — La cathédrale du bout du monde · Buen Camino ✨"
  }
];

// Current progress (km walked — 0 at start)
const CURRENT_KM = 0;

let mapInstance = null;

function initMap() {
  const mapEl = document.getElementById('map');
  if (!mapEl) return;

  // Create map centered on route midpoint
  mapInstance = L.map('map', {
    zoomControl: false,
    scrollWheelZoom: true,
    attributionControl: true
  });

  // Custom zoom control position
  L.control.zoom({ position: 'bottomright' }).addTo(mapInstance);

  // Tile layer — CartoDB Positron (clean, minimal, perfect for routes)
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 18
  }).addTo(mapInstance);

  // Draw the route polyline
  const routeCoords = WAYPOINTS.map(w => w.coords);

  // Background shadow line
  L.polyline(routeCoords, {
    color: 'rgba(0,0,0,0.12)',
    weight: 6,
    lineCap: 'round',
    lineJoin: 'round',
    smoothFactor: 1
  }).addTo(mapInstance);

  // Main route line
  const routeLine = L.polyline(routeCoords, {
    color: '#2D8C7A',
    weight: 4,
    opacity: 0.9,
    lineCap: 'round',
    lineJoin: 'round',
    smoothFactor: 1,
    dashArray: null
  }).addTo(mapInstance);

  // Animated dashes for "to walk" portion (if current km < 1900)
  if (CURRENT_KM < 1900) {
    const futureStart = getCoordAtKm(CURRENT_KM);
    const futureCoords = WAYPOINTS
      .filter(w => w.km > CURRENT_KM)
      .map(w => w.coords);

    if (futureStart) futureCoords.unshift(futureStart);

    L.polyline(futureCoords, {
      color: '#2D8C7A',
      weight: 4,
      opacity: 0.3,
      lineCap: 'round',
      dashArray: '8 8',
      smoothFactor: 1
    }).addTo(mapInstance);

    // Solid portion for walked km
    const walkedCoords = WAYPOINTS
      .filter(w => w.km <= CURRENT_KM)
      .map(w => w.coords);

    if (walkedCoords.length > 1) {
      L.polyline(walkedCoords, {
        color: '#1E6B5E',
        weight: 5,
        opacity: 1,
        lineCap: 'round',
        smoothFactor: 1
      }).addTo(mapInstance);
    }
  }

  // Add markers for each waypoint
  WAYPOINTS.forEach((waypoint, index) => {
    addWaypointMarker(waypoint, index);
  });

  // Add current position marker (pulsing gold dot)
  if (CURRENT_KM > 0 && CURRENT_KM < 1900) {
    addCurrentPositionMarker();
  }

  // Fit the map to show the full route with padding
  mapInstance.fitBounds(routeLine.getBounds(), {
    padding: [40, 40],
    maxZoom: 7
  });

  // Add a subtle info panel
  addMapLegend();
}

function createMarkerIcon(waypoint) {
  let html, size, anchor;

  if (waypoint.type === 'start') {
    html = `<div style="font-size:1.6rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">🏔️</div>`;
    size = [32, 32];
    anchor = [16, 16];
  } else if (waypoint.type === 'end') {
    html = `<div style="font-size:1.6rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">⭐</div>`;
    size = [32, 32];
    anchor = [16, 16];
  } else if (waypoint.type === 'major') {
    html = `
      <div style="
        width: 14px; height: 14px;
        background: #F0A500;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(240,165,0,0.5);
      "></div>`;
    size = [14, 14];
    anchor = [7, 7];
  } else {
    html = `
      <div style="
        width: 10px; height: 10px;
        background: #2D8C7A;
        border: 2px solid white;
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(45,140,122,0.4);
      "></div>`;
    size = [10, 10];
    anchor = [5, 5];
  }

  return L.divIcon({
    html,
    iconSize: size,
    iconAnchor: anchor,
    className: ''
  });
}

function addWaypointMarker(waypoint, index) {
  const icon = createMarkerIcon(waypoint);
  const marker = L.marker(waypoint.coords, { icon, zIndexOffset: waypoint.type === 'end' ? 1000 : 0 });

  const typeLabels = {
    start: 'Départ',
    end: 'Arrivée',
    major: 'Étape Majeure',
    stage: 'Étape'
  };

  const popupContent = `
    <div style="min-width: 180px; padding: 4px 2px;">
      <div class="popup-title">${waypoint.country} ${waypoint.name}</div>
      <div class="popup-km">km ${waypoint.km.toLocaleString('fr-FR')}</div>
      <div style="margin-top: 0.5rem; font-size: 0.82rem; color: #718096; line-height: 1.5;">${waypoint.desc}</div>
      <span class="popup-type ${waypoint.type}">${typeLabels[waypoint.type] || 'Étape'}</span>
    </div>
  `;

  marker.bindPopup(popupContent, {
    maxWidth: 240,
    className: 'custom-popup'
  });

  // Show label on hover for major points
  if (waypoint.type !== 'stage' || index === 0 || index === WAYPOINTS.length - 1) {
    marker.bindTooltip(waypoint.name, {
      permanent: false,
      direction: 'top',
      className: 'map-tooltip',
      offset: [0, -10]
    });
  }

  marker.addTo(mapInstance);
}

function addCurrentPositionMarker() {
  const currentCoord = getCoordAtKm(CURRENT_KM);
  if (!currentCoord) return;

  const pulseIcon = L.divIcon({
    html: `
      <div class="marker-pulse"></div>
    `,
    iconSize: [20, 20],
    iconAnchor: [10, 10],
    className: ''
  });

  const marker = L.marker(currentCoord, { icon: pulseIcon, zIndexOffset: 2000 });
  marker.bindPopup(`
    <div style="padding: 4px 2px;">
      <div class="popup-title">📍 Position actuelle</div>
      <div class="popup-km">km ${CURRENT_KM.toLocaleString('fr-FR')} / 1 900</div>
      <div style="margin-top: 0.4rem; font-size: 0.8rem; color: #718096;">Roland est ici</div>
    </div>
  `);

  marker.addTo(mapInstance);
}

function getCoordAtKm(targetKm) {
  if (targetKm <= 0) return WAYPOINTS[0].coords;
  if (targetKm >= 1900) return WAYPOINTS[WAYPOINTS.length - 1].coords;

  for (let i = 0; i < WAYPOINTS.length - 1; i++) {
    const a = WAYPOINTS[i];
    const b = WAYPOINTS[i + 1];
    if (targetKm >= a.km && targetKm <= b.km) {
      const t = (targetKm - a.km) / (b.km - a.km);
      return [
        a.coords[0] + (b.coords[0] - a.coords[0]) * t,
        a.coords[1] + (b.coords[1] - a.coords[1]) * t
      ];
    }
  }
  return null;
}

function addMapLegend() {
  const legend = L.control({ position: 'bottomleft' });
  legend.onAdd = function() {
    const div = L.DomUtil.create('div', 'map-legend');
    div.style.cssText = `
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(8px);
      padding: 0.75rem 1rem;
      border-radius: 0.75rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      font-family: Inter, sans-serif;
      font-size: 0.75rem;
      border: 1px solid rgba(45,140,122,0.2);
      min-width: 160px;
    `;
    div.innerHTML = `
      <div style="font-weight:700; color:#1a2332; margin-bottom:0.5rem; font-size:0.8rem;">Route St-Maurice → Santiago</div>
      <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.3rem; color:#718096;">
        <div style="width:20px; height:3px; background:#1E6B5E; border-radius:2px;"></div> Chemin parcouru
      </div>
      <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.3rem; color:#718096;">
        <div style="width:20px; height:3px; background:#2D8C7A; opacity:0.35; border-radius:2px; border-top: 2px dashed #2D8C7A; height:0;"></div> Chemin à venir
      </div>
      <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.3rem; color:#718096;">
        <div style="width:10px; height:10px; background:#F0A500; border-radius:50%; border:2px solid white; box-shadow:0 1px 4px rgba(240,165,0,0.4);"></div> Étape majeure
      </div>
      <div style="display:flex; align-items:center; gap:0.5rem; color:#718096;">
        <div style="width:8px; height:8px; background:#2D8C7A; border-radius:50%; border:2px solid white;"></div> Étape
      </div>
      <div style="margin-top:0.5rem; padding-top:0.5rem; border-top:1px solid rgba(0,0,0,0.08); color:#1E6B5E; font-weight:600;">
        1 900 km · 3 pays
      </div>
    `;
    return div;
  };
  legend.addTo(mapInstance);
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initMap);

// Export for use
window.NeuroMap = { init: initMap, waypoints: WAYPOINTS, currentKm: CURRENT_KM };
