/* ============================================
   LA NEURO-ODYSSÉE, Interactive Leaflet Map
   Nouveau tracé 2026 : 6 chemins historiques
   St-Maurice → Genève → Le Puy → Conques → Toulouse
   → Somport → Aragon → Camino Olvidado → Invierno → Santiago
   NB : tracé approximatif, sera affiné avec le GPX de Roland.
   ============================================ */

// ── Les 6 tronçons historiques ─────────────────────────────
const SEGMENTS = [
  {
    id: 1, name: "Via Jacobi & Chemin de Genève", color: "#2D8C7A",
    kmStart: 0, kmEnd: 675,
    story: "La voie des marchands et des exilés. Route des grands marchés médiévaux, empruntée à l'envers par les réfugiés huguenots fuyant vers la Suisse. Une terre d'accueil et d'échange, du Léman jusqu'à Conques et ses reliques de Sainte Foy."
  },
  {
    id: 2, name: "Liaison Conques → Toulouse", color: "#C2703D",
    kmStart: 675, kmEnd: 875,
    story: "La route des grands sanctuaires. Au XIIe siècle, les pèlerins reliaient les reliques de Sainte Foy à la basilique Saint-Sernin de Toulouse, l'un des plus grands reliquaires d'Europe, à travers le pays des Bastides, villes fortifiées du XIIIe siècle."
  },
  {
    id: 3, name: "Voie d'Arles & Col du Somport", color: "#7B5EA7",
    kmStart: 875, kmEnd: 1155,
    story: "La porte des rois. La Via Tolosana, voie romaine à l'origine, passage principal des Pyrénées au Moyen Âge. Le Somport (Summus Portus, « le plus haut col ») était jugé plus sûr que les cols basques hantés par les brigands."
  },
  {
    id: 4, name: "Camino Aragonés & Rioja", color: "#B23A48",
    kmStart: 1155, kmEnd: 1500,
    story: "Le gardien de la Reconquista. Le vieux Royaume d'Aragon, refuge des royaumes chrétiens du Nord aux IXe-Xe siècles. Jaca et le monastère de Leyre furent les remparts de la chrétienté : les paysages de la résistance médiévale espagnole."
  },
  {
    id: 5, name: "Camino Olvidado, le Chemin Oublié", color: "#4A6FA5",
    kmStart: 1500, kmEnd: 1860,
    story: "La route secrète des premiers pèlerins de l'an 900. Quand la plaine était un champ de bataille, les marcheurs rasaient les montagnes Cantabriques, cachés par les forêts. Abandonné quand le Camino Francés fut créé, d'où son nom de « Chemin Oublié »."
  },
  {
    id: 6, name: "Camino de Invierno, le Chemin d'Hiver", color: "#8E3B60",
    kmStart: 1860, kmEnd: 2200,
    story: "L'astuce des pèlerins. Pour éviter la montée d'O Cebreiro bloquée par la neige et les loups, ils suivaient la vallée du Sil et son microclimat, où l'on fait du vin depuis l'époque romaine (Ribeira Sacra). La route du bon sens et de la survie hivernale."
  }
];

const WAYPOINTS = [
  // ── TRONÇON 1 · Via Jacobi & Chemin de Genève ──
  { name: "St-Maurice", coords: [46.2192, 7.0056], type: "start", km: 0, country: "🇨🇭", desc: "Point de départ, Abbaye de St-Maurice, Valais · Via Jacobi" },
  { name: "Montreux", coords: [46.4312, 6.9107], type: "stage", km: 42, country: "🇨🇭", desc: "Riviera vaudoise, Château de Chillon" },
  { name: "Lausanne", coords: [46.5197, 6.6323], type: "major", km: 68, country: "🇨🇭", desc: "Cathédrale gothique, rives du Léman" },
  { name: "Nyon", coords: [46.3833, 6.2390], type: "stage", km: 103, country: "🇨🇭", desc: "Côte lémanique, ville romaine de Noviodunum" },
  { name: "Genève", coords: [46.2044, 6.1432], type: "major", km: 125, country: "🇨🇭", desc: "Départ du Chemin de Genève (Via Gebennensis), terre d'accueil des huguenots" },
  { name: "Seyssel", coords: [45.9590, 5.8320], type: "stage", km: 185, country: "🇫🇷", desc: "Vallée du Rhône, premier tronçon savoyard" },
  { name: "Yenne", coords: [45.7030, 5.7560], type: "stage", km: 225, country: "🇫🇷", desc: "Pied du Mont du Chat, Avant-pays savoyard" },
  { name: "La Côte-St-André", coords: [45.3940, 5.2600], type: "stage", km: 300, country: "🇫🇷", desc: "Plaines de l'Isère, pays de Berlioz" },
  { name: "Chavanay", coords: [45.4160, 4.7260], type: "stage", km: 360, country: "🇫🇷", desc: "Traversée du Rhône, vignobles en terrasses" },
  { name: "Le Puy-en-Velay", coords: [45.0430, 3.8850], type: "major", km: 470, country: "🇫🇷", desc: "Haut lieu du pèlerinage, jonction avec la Via Podiensis (GR65)" },
  { name: "Saugues", coords: [44.9600, 3.5480], type: "stage", km: 515, country: "🇫🇷", desc: "Margeride, pays de la Bête du Gévaudan" },
  { name: "Aumont-Aubrac", coords: [44.7220, 3.2830], type: "stage", km: 558, country: "🇫🇷", desc: "Plateau de l'Aubrac, burons et grands espaces" },
  { name: "Espalion", coords: [44.5220, 2.7620], type: "stage", km: 622, country: "🇫🇷", desc: "Vallée du Lot, Pont-Vieux classé" },
  { name: "Conques", coords: [44.5990, 2.3970], type: "major", km: 675, country: "🇫🇷", desc: "Abbatiale Sainte-Foy, reliques qui guérissaient la cécité · fin du tronçon 1" },

  // ── TRONÇON 2 · Liaison Conques → Toulouse ──
  { name: "Villefranche-de-Rouergue", coords: [44.3520, 2.0370], type: "stage", km: 728, country: "🇫🇷", desc: "Bastide royale du XIIIe siècle" },
  { name: "Najac", coords: [44.2190, 1.9770], type: "stage", km: 750, country: "🇫🇷", desc: "Forteresse perchée sur son éperon rocheux" },
  { name: "Cordes-sur-Ciel", coords: [44.0640, 1.9530], type: "stage", km: 786, country: "🇫🇷", desc: "Bastide « dans le ciel », construite après les guerres cathares" },
  { name: "Gaillac", coords: [43.9010, 1.8970], type: "stage", km: 815, country: "🇫🇷", desc: "Vignoble parmi les plus anciens de France" },
  { name: "Toulouse", coords: [43.6045, 1.4440], type: "major", km: 875, country: "🇫🇷", desc: "Basilique Saint-Sernin, l'un des plus grands reliquaires d'Europe · fin du tronçon 2" },

  // ── TRONÇON 3 · Voie d'Arles & Col du Somport ──
  { name: "L'Isle-Jourdain", coords: [43.6120, 1.0830], type: "stage", km: 905, country: "🇫🇷", desc: "Entrée en Gascogne, Via Tolosana" },
  { name: "Auch", coords: [43.6460, 0.5860], type: "major", km: 950, country: "🇫🇷", desc: "Cathédrale Sainte-Marie, capitale de la Gascogne" },
  { name: "Marciac", coords: [43.5240, 0.1630], type: "stage", km: 995, country: "🇫🇷", desc: "Bastide gasconne, terre de jazz" },
  { name: "Morlaàs", coords: [43.3480, -0.2630], type: "stage", km: 1055, country: "🇫🇷", desc: "Ancienne capitale du Béarn" },
  { name: "Oloron-Sainte-Marie", coords: [43.1940, -0.6110], type: "stage", km: 1090, country: "🇫🇷", desc: "Portail roman classé UNESCO, porte des Pyrénées" },
  { name: "Col du Somport", coords: [42.7960, -0.5250], type: "major", km: 1155, country: "🇫🇷", desc: "Summus Portus, 1 632 m · passage historique des Pyrénées, voie romaine · fin du tronçon 3" },

  // ── TRONÇON 4 · Camino Aragonés & Rioja ──
  { name: "Jaca", coords: [42.5710, -0.5500], type: "major", km: 1185, country: "🇪🇸", desc: "Première capitale du Royaume d'Aragon, rempart de la chrétienté" },
  { name: "Sangüesa", coords: [42.5750, -1.2810], type: "stage", km: 1255, country: "🇪🇸", desc: "Portail sculpté de Santa María la Real" },
  { name: "Puente la Reina", coords: [42.6720, -1.8150], type: "major", km: 1310, country: "🇪🇸", desc: "Le pont roman où les chemins se rejoignent" },
  { name: "Estella", coords: [42.6710, -2.0320], type: "stage", km: 1333, country: "🇪🇸", desc: "« La Tolède du Nord », joyau navarrais" },
  { name: "Logroño", coords: [42.4660, -2.4450], type: "stage", km: 1381, country: "🇪🇸", desc: "Capitale de la Rioja, pays du vin" },
  { name: "Burgos", coords: [42.3440, -3.6970], type: "major", km: 1500, country: "🇪🇸", desc: "Cathédrale gothique UNESCO · bifurcation vers le Chemin Oublié · fin du tronçon 4" },

  // ── TRONÇON 5 · Camino Olvidado ──
  { name: "Aguilar de Campoo", coords: [42.7940, -4.2570], type: "stage", km: 1600, country: "🇪🇸", desc: "Entrée sur le Viejo Camino, le chemin de l'an 900" },
  { name: "Cervera de Pisuerga", coords: [42.8700, -4.5000], type: "stage", km: 1625, country: "🇪🇸", desc: "Contreforts des montagnes Cantabriques" },
  { name: "Guardo", coords: [42.7900, -4.8430], type: "stage", km: 1660, country: "🇪🇸", desc: "Vallées minières de la montagne palentine" },
  { name: "Cistierna", coords: [42.8050, -5.1300], type: "stage", km: 1692, country: "🇪🇸", desc: "Le chemin caché à l'abri des forêts" },
  { name: "La Robla", coords: [42.8000, -5.6300], type: "stage", km: 1737, country: "🇪🇸", desc: "Piémont léonais, loin de la plaine dangereuse" },
  { name: "Igüeña", coords: [42.7120, -6.2850], type: "stage", km: 1810, country: "🇪🇸", desc: "Vallées secrètes du Bierzo oriental" },
  { name: "Ponferrada", coords: [42.5460, -6.5900], type: "major", km: 1860, country: "🇪🇸", desc: "Château des Templiers, gardiens des pèlerins · fin du tronçon 5" },

  // ── TRONÇON 6 · Camino de Invierno ──
  { name: "Las Médulas", coords: [42.5100, -6.7670], type: "stage", km: 1885, country: "🇪🇸", desc: "Anciennes mines d'or romaines, paysage UNESCO" },
  { name: "O Barco de Valdeorras", coords: [42.4150, -6.9820], type: "stage", km: 1915, country: "🇪🇸", desc: "Vallée du Sil, microclimat des vignes" },
  { name: "Quiroga", coords: [42.4750, -7.2700], type: "stage", km: 1960, country: "🇪🇸", desc: "Gorges du Sil, oliviers et vignobles" },
  { name: "Monforte de Lemos", coords: [42.5220, -7.5140], type: "major", km: 1995, country: "🇪🇸", desc: "Cœur de la Ribeira Sacra, vin depuis l'époque romaine" },
  { name: "Chantada", coords: [42.6090, -7.7710], type: "stage", km: 2035, country: "🇪🇸", desc: "Traversée du Miño, terres galiciennes" },
  { name: "Lalín", coords: [42.6600, -8.1110], type: "stage", km: 2090, country: "🇪🇸", desc: "Le Deza, dernières collines avant Santiago" },
  { name: "Ponte Ulla", coords: [42.7960, -8.4390], type: "stage", km: 2150, country: "🇪🇸", desc: "Dernière vallée avant la cité de l'apôtre" },
  { name: "Santiago de Compostela", coords: [42.8806, -8.5446], type: "end", km: 2200, country: "🇪🇸", desc: "La cathédrale del Obradoiro, le bout du chemin ⭐" }
];

const TOTAL_KM = 2200;
// Progression de secours si aucun GPS (mise à jour auto par le suivi temps réel)
const CURRENT_KM = 0;

let mapInstance = null;
let walkedLayer = null;   // portion parcourue (colorée au fur et à mesure)
let liveLayer = null;     // marqueur GPS + tracé réel

function initMap() {
  const mapEl = document.getElementById('map');
  if (!mapEl) return;

  mapInstance = L.map('map', {
    zoomControl: false,
    scrollWheelZoom: true,
    attributionControl: true
  });

  L.control.zoom({ position: 'bottomright' }).addTo(mapInstance);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 18
  }).addTo(mapInstance);

  // Ombre douce sous tout le tracé
  const allCoords = WAYPOINTS.map(w => w.coords);
  const routeLine = L.polyline(allCoords, {
    color: 'rgba(0,0,0,0.10)', weight: 7, lineCap: 'round', lineJoin: 'round', smoothFactor: 1
  }).addTo(mapInstance);

  // ── Tracé « à venir » : un trait par tronçon, dans sa couleur, estompé ──
  SEGMENTS.forEach(seg => {
    const path = pathBetweenKm(seg.kmStart, seg.kmEnd);
    if (path.length < 2) return;
    const line = L.polyline(path, {
      color: seg.color, weight: 4, opacity: 0.35, dashArray: '7 9',
      lineCap: 'round', lineJoin: 'round', smoothFactor: 1
    }).addTo(mapInstance);
    line.bindPopup(segmentPopupHtml(seg), { maxWidth: 300, className: 'custom-popup' });
  });

  // Couche « parcouru » (remplie au fil de l'avancée GPS)
  walkedLayer = L.layerGroup().addTo(mapInstance);
  drawWalkedUpTo(CURRENT_KM);

  // Marqueurs d'étapes
  WAYPOINTS.forEach((waypoint, index) => addWaypointMarker(waypoint, index));

  mapInstance.fitBounds(routeLine.getBounds(), { padding: [40, 40], maxZoom: 7 });

  addMapLegend();
  initLiveTracking();
  initPhotoMarkers();
}

function segmentPopupHtml(seg) {
  return `
    <div style="padding:4px 2px; max-width:280px;">
      <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.35rem;">
        <span style="width:14px; height:14px; border-radius:4px; background:${seg.color}; display:inline-block; flex-shrink:0;"></span>
        <span class="popup-title" style="line-height:1.25;">${seg.name}</span>
      </div>
      <div class="popup-km">km ${seg.kmStart.toLocaleString('fr-FR')} → ${seg.kmEnd.toLocaleString('fr-FR')}</div>
      <div style="margin-top:0.5rem; font-size:0.8rem; color:#4a5568; line-height:1.55;">${seg.story}</div>
    </div>`;
}

// ── Coloration progressive : dessine la portion 0 → km en traits pleins ──
function drawWalkedUpTo(km) {
  if (!walkedLayer) return;
  walkedLayer.clearLayers();
  if (!km || km <= 0) return;
  SEGMENTS.forEach(seg => {
    if (km <= seg.kmStart) return;
    const end = Math.min(km, seg.kmEnd);
    const path = pathBetweenKm(seg.kmStart, end);
    if (path.length < 2) return;
    L.polyline(path, {
      color: seg.color, weight: 5.5, opacity: 1,
      lineCap: 'round', lineJoin: 'round', smoothFactor: 1
    }).addTo(walkedLayer);
  });
}

// Chemin (liste de coords) entre deux kilométrages, extrémités interpolées
function pathBetweenKm(kmA, kmB) {
  const path = [];
  const start = getCoordAtKm(kmA);
  if (start) path.push(start);
  WAYPOINTS.forEach(w => { if (w.km > kmA && w.km < kmB) path.push(w.coords); });
  const end = getCoordAtKm(kmB);
  if (end) path.push(end);
  return path;
}

function getCoordAtKm(targetKm) {
  if (targetKm <= 0) return WAYPOINTS[0].coords;
  if (targetKm >= TOTAL_KM) return WAYPOINTS[WAYPOINTS.length - 1].coords;
  for (let i = 0; i < WAYPOINTS.length - 1; i++) {
    const a = WAYPOINTS[i], b = WAYPOINTS[i + 1];
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

function segmentForKm(km) {
  return SEGMENTS.find(s => km >= s.kmStart && km <= s.kmEnd) || null;
}

// ---- Suivi GPS temps réel (fait avancer la couleur du tracé) ----
function trackHaversine(la1, lo1, la2, lo2) {
  const R = 6371;
  const dLa = (la2 - la1) * Math.PI / 180;
  const dLo = (lo2 - lo1) * Math.PI / 180;
  const a = Math.sin(dLa / 2) ** 2 + Math.cos(la1 * Math.PI / 180) * Math.cos(la2 * Math.PI / 180) * Math.sin(dLo / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// Projette la position GPS sur le tracé pour estimer les km parcourus
function kmFromLatLng(lat, lng) {
  let bestKm = 0, bestD = Infinity;
  for (let km = 0; km <= TOTAL_KM; km += 5) {
    const c = getCoordAtKm(km);
    if (!c) continue;
    const d = trackHaversine(lat, lng, c[0], c[1]);
    if (d < bestD) { bestD = d; bestKm = km; }
  }
  return bestKm;
}

function initLiveTracking() {
  if (!mapInstance) return;
  liveLayer = L.layerGroup().addTo(mapInstance);

  async function refresh() {
    try {
      const res = await fetch('data/tracking.json', { cache: 'no-store' });
      if (!res.ok) return;
      const t = await res.json();
      if (!t || !t.current || typeof t.current.lat !== 'number') return;
      liveLayer.clearLayers();

      const cur = t.current;
      const km = kmFromLatLng(cur.lat, cur.lng);
      const seg = segmentForKm(km);

      // 1) La couleur du tracé avance jusqu'à sa position
      drawWalkedUpTo(km);

      // 2) Son tracé GPS réel (fin, par-dessus)
      if (Array.isArray(t.trail) && t.trail.length > 1) {
        L.polyline(t.trail, { color: '#1a2332', weight: 2.5, opacity: 0.85, lineCap: 'round', smoothFactor: 1 }).addTo(liveLayer);
      }

      // 3) Le marqueur pulsant
      const pulseIcon = L.divIcon({ html: '<div class="marker-pulse"></div>', iconSize: [20, 20], iconAnchor: [10, 10], className: '' });
      const marker = L.marker([cur.lat, cur.lng], { icon: pulseIcon, zIndexOffset: 2000 }).addTo(liveLayer);
      let when = '';
      if (cur.time) { try { when = new Date(cur.time * 1000).toLocaleString('fr-CH', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }); } catch (e) {} }
      const batt = (cur.batt != null) ? ` · 🔋 ${Math.round(cur.batt)}%` : '';
      marker.bindPopup(`
        <div style="padding:4px 2px;">
          <div class="popup-title">📍 Roland est ici</div>
          <div class="popup-km">km ${Math.round(km).toLocaleString('fr-FR')} / ${TOTAL_KM.toLocaleString('fr-FR')}</div>
          ${seg ? `<div style="margin-top:0.35rem; font-size:0.78rem; color:${seg.color}; font-weight:700;">${seg.name}</div>` : ''}
          ${when ? `<div style="margin-top:0.35rem; font-size:0.75rem; color:#718096;">Mis à jour : ${when}${batt}</div>` : ''}
        </div>
      `);
    } catch (e) { /* silencieux */ }
  }

  refresh();
  setInterval(refresh, 45000);
}

// ---- Photos épinglées sur la carte ----
async function initPhotoMarkers() {
  if (!mapInstance) return;
  try {
    const res = await fetch('data/photos-carte.json', { cache: 'no-store' });
    if (!res.ok) return;
    const photos = await res.json();
    if (!Array.isArray(photos) || !photos.length) return;

    photos.forEach(p => {
      if (typeof p.lat !== 'number' || typeof p.lng !== 'number' || !p.img) return;
      const icon = L.divIcon({
        html: `<div style="width:38px;height:38px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.35);overflow:hidden;background:#fff;">
                 <img src="${p.img}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
               </div>`,
        iconSize: [38, 38],
        iconAnchor: [19, 19],
        className: ''
      });
      const marker = L.marker([p.lat, p.lng], { icon, zIndexOffset: 1500 }).addTo(mapInstance);
      let when = '';
      if (p.time) { try { when = new Date(p.time * 1000).toLocaleDateString('fr-CH', { day: '2-digit', month: 'long', year: 'numeric' }); } catch (e) {} }
      marker.bindPopup(`
        <div style="padding:2px; max-width:240px;">
          <a href="${p.img}" target="_blank" rel="noopener"><img src="${p.img}" alt="" style="width:100%;border-radius:8px;display:block;"></a>
          ${p.caption ? `<div style="margin-top:0.5rem; font-size:0.85rem; color:#1a2332; line-height:1.45;">${p.caption}</div>` : ''}
          ${when ? `<div style="margin-top:0.3rem; font-size:0.72rem; color:#718096;">📸 ${when}</div>` : ''}
        </div>
      `, { maxWidth: 260 });
    });
  } catch (e) { /* silencieux */ }
}

function createMarkerIcon(waypoint) {
  let html, size, anchor;

  if (waypoint.type === 'start') {
    html = `<div style="font-size:1.6rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">🏔️</div>`;
    size = [32, 32]; anchor = [16, 16];
  } else if (waypoint.type === 'end') {
    html = `<div style="font-size:1.6rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">⭐</div>`;
    size = [32, 32]; anchor = [16, 16];
  } else if (waypoint.type === 'major') {
    html = `
      <div style="
        width: 14px; height: 14px;
        background: #F0A500;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(240,165,0,0.5);
      "></div>`;
    size = [14, 14]; anchor = [7, 7];
  } else {
    html = `
      <div style="
        width: 10px; height: 10px;
        background: #2D8C7A;
        border: 2px solid white;
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(45,140,122,0.4);
      "></div>`;
    size = [10, 10]; anchor = [5, 5];
  }

  return L.divIcon({ html, iconSize: size, iconAnchor: anchor, className: '' });
}

function addWaypointMarker(waypoint, index) {
  const icon = createMarkerIcon(waypoint);
  const marker = L.marker(waypoint.coords, { icon, zIndexOffset: waypoint.type === 'end' ? 1000 : 0 });

  const typeLabels = { start: 'Départ', end: 'Arrivée', major: 'Étape Majeure', stage: 'Étape' };
  const seg = segmentForKm(waypoint.km);

  const popupContent = `
    <div style="min-width: 180px; padding: 4px 2px;">
      <div class="popup-title">${waypoint.country} ${waypoint.name}</div>
      <div class="popup-km">km ${waypoint.km.toLocaleString('fr-FR')}</div>
      ${seg ? `<div style="margin-top:0.3rem; font-size:0.72rem; color:${seg.color}; font-weight:700;">${seg.name}</div>` : ''}
      <div style="margin-top: 0.5rem; font-size: 0.82rem; color: #718096; line-height: 1.5;">${waypoint.desc}</div>
      <span class="popup-type ${waypoint.type}">${typeLabels[waypoint.type] || 'Étape'}</span>
    </div>
  `;

  marker.bindPopup(popupContent, { maxWidth: 240, className: 'custom-popup' });

  if (waypoint.type !== 'stage' || index === 0 || index === WAYPOINTS.length - 1) {
    marker.bindTooltip(waypoint.name, {
      permanent: false, direction: 'top', className: 'map-tooltip', offset: [0, -10]
    });
  }

  marker.addTo(mapInstance);
}

function addMapLegend() {
  const legend = L.control({ position: 'bottomleft' });
  legend.onAdd = function() {
    const div = L.DomUtil.create('div', 'map-legend');
    div.style.cssText = `
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(8px);
      padding: 0.55rem 0.85rem;
      border-radius: 0.75rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      font-family: Inter, sans-serif;
      font-size: 0.75rem;
      border: 1px solid rgba(45,140,122,0.2);
    `;
    const collapsed = window.innerWidth < 640;
    const segRows = SEGMENTS.map(s => `
      <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.3rem; color:#4a5568;">
        <div style="width:18px; height:4px; background:${s.color}; border-radius:2px; flex-shrink:0;"></div>
        <span style="line-height:1.25;">${s.name}</span>
      </div>`).join('');
    div.innerHTML = `
      <div id="legendToggle" style="display:flex; align-items:center; gap:0.45rem; cursor:pointer; user-select:none; font-weight:700; color:#1a2332; font-size:0.8rem;">
        <span>ℹ️ Les 6 chemins</span>
        <span id="legendChevron" style="font-size:0.7rem; color:#718096; transition:transform .2s;">${collapsed ? '▸' : '▾'}</span>
      </div>
      <div id="legendBody" style="display:${collapsed ? 'none' : 'block'}; margin-top:0.5rem; min-width:190px; max-width:230px;">
        ${segRows}
        <div style="display:flex; align-items:center; gap:0.5rem; margin:0.45rem 0 0.3rem; color:#718096; padding-top:0.45rem; border-top:1px solid rgba(0,0,0,0.08);">
          <div style="width:18px; height:0; border-top:2px dashed #9ab; flex-shrink:0;"></div> À parcourir
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem; color:#718096;">
          <div style="width:18px; height:4px; background:#1E6B5E; border-radius:2px; flex-shrink:0;"></div> Parcouru (couleur pleine)
        </div>
        <div style="margin-top:0.5rem; padding-top:0.5rem; border-top:1px solid rgba(0,0,0,0.08); color:#1E6B5E; font-weight:600;">
          2 200 km · 3 pays · 6 chemins historiques
        </div>
      </div>
    `;
    L.DomEvent.disableClickPropagation(div);
    L.DomEvent.disableScrollPropagation(div);
    div.querySelector('#legendToggle').addEventListener('click', function() {
      const body = div.querySelector('#legendBody');
      const chev = div.querySelector('#legendChevron');
      const isOpen = body.style.display !== 'none';
      body.style.display = isOpen ? 'none' : 'block';
      chev.textContent = isOpen ? '▸' : '▾';
    });
    return div;
  };
  legend.addTo(mapInstance);
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initMap);

// Export for use
window.NeuroMap = { init: initMap, waypoints: WAYPOINTS, segments: SEGMENTS, currentKm: CURRENT_KM };
