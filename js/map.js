/* ============================================
   LA NEURO-ODYSSÉE, Interactive Leaflet Map
   Parcours réellement marché, départ le 21 août 2026 :
   St-Maurice → Genève → Le Puy → Conques → Toulouse (1 000 km, 7 oct.)
   → Lourdes → Somport → Camino Aragonés → Puente la Reina
   → Camino Francés → Burgos → Meseta → León → O Cebreiro → Santiago
   puis prolongation : Santiago → Porto → Coimbra → Fátima (Portugal)
   NB : kilométrages ancrés sur le repère de Roland (Toulouse = 1 000 km)
   puis étapes standard du Camino ; coordonnées OpenStreetMap, parfois le
   centre de la commune. Sera affiné avec le GPX de Roland.
   ============================================ */

// ── Fond de carte : clé CARTO ───────────────────────────────
// Depuis septembre 2026, CARTO exige une clé API pour ses tuiles raster.
// Sans clé, ses serveurs renvoient des tuiles tamponnées « API KEY REQUIRED ».
// Clé gratuite (jusqu'à 5 millions de requêtes/mois) : https://carto.com/basemaps/apikey/
// Colle-la ci-dessous pour retrouver le fond clair CARTO « Positron ».
// Laissée vide, la carte bascule sur le fond gris clair Esri, qui ne demande
// aucune clé (zoom max 16 au lieu de 18).
const CARTO_KEY = '';

// ── Tolérance « hors tracé » ────────────────────────────────
// Au-delà de cette distance au tracé prévu, on n'essaie plus de traduire la
// position GPS en kilomètres parcourus : on affiche le marqueur là où Roland
// est vraiment, sans faire avancer la coloration du chemin. Sinon un point
// éloigné est « collé » à l'étape la plus proche et la carte annonce une
// progression qui n'a pas eu lieu.
const OFF_ROUTE_KM = 15;

// ── Position actuelle de Roland ─────────────────────────────
// Renseignée à la main, affichée tant que le GPS du téléphone n'envoie rien
// (data/tracking.json vide). Dès que le suivi temps réel fonctionne, c'est lui
// qui prend le dessus et cette valeur est ignorée.
// Mettre à jour : lat/lng du lieu, son nom, et la date d'observation.
const DEPARTURE_DATE = '2026-08-21'; // départ de St-Maurice
const CURRENT_POSITION = {
  lat: 42.3546268,
  lng: -4.86461,
  place: 'Ledigos, province de Palencia',
  date: '2026-11-22'
};

// ── Les 6 tronçons historiques ─────────────────────────────
const SEGMENTS = [
  {
    id: 1, name: "Via Jacobi & Chemin de Genève", color: "#2D8C7A",
    kmStart: 0, kmEnd: 771,
    story: "La voie des marchands et des exilés. Route des grands marchés médiévaux, empruntée à l'envers par les réfugiés huguenots fuyant vers la Suisse. Une terre d'accueil et d'échange, du Léman jusqu'à Conques et ses reliques de Sainte Foy."
  },
  {
    id: 2, name: "Liaison Conques → Toulouse", color: "#C2703D",
    kmStart: 771, kmEnd: 1000,
    story: "La route des grands sanctuaires. Au XIIe siècle, les pèlerins reliaient les reliques de Sainte Foy à la basilique Saint-Sernin de Toulouse, l'un des plus grands reliquaires d'Europe, à travers le pays des Bastides, villes fortifiées du XIIIe siècle. Toulouse, atteinte le 7 octobre 2026, marque le cap des 1 000 km."
  },
  {
    id: 3, name: "Voie d'Arles, Lourdes & Somport", color: "#7B5EA7",
    kmStart: 1000, kmEnd: 1367,
    story: "La porte des rois. La Via Tolosana, voie romaine à l'origine, passage principal des Pyrénées au Moyen Âge. Roland y ajoute un crochet non prévu par Lourdes, l'un des plus grands lieux de pèlerinage au monde, avant de franchir le Somport (Summus Portus, « le plus haut col »), jugé plus sûr que les cols basques hantés par les brigands."
  },
  {
    id: 4, name: "Camino Aragonés", color: "#B23A48",
    kmStart: 1367, kmEnd: 1544,
    story: "Le gardien de la Reconquista. Le vieux Royaume d'Aragon, refuge des royaumes chrétiens du Nord aux IXe-Xe siècles. Jaca fut l'un des remparts de la chrétienté. Puis les villages presque désertés d'Artieda, Ruesta et Undués de Lerda, une Espagne rurale saisissante, jusqu'au pont roman de Puente la Reina où les chemins se rejoignent."
  },
  {
    id: 5, name: "Camino Francés, Rioja & Castille", color: "#4A6FA5",
    kmStart: 1544, kmEnd: 1925,
    story: "Le grand chemin. À Puente la Reina, Roland rejoint le Camino Francés, la plus fréquentée des routes de Compostelle. La Navarre d'Estella, les vignobles de la Rioja, le coq et la poule de Santo Domingo de la Calzada, la cathédrale gothique de Burgos. Puis la Meseta : les plaines céréalières de Castille, les villages isolés et des horizons sans fin."
  },
  {
    id: 6, name: "Camino Francés, montagnes de Galice", color: "#8E3B60",
    kmStart: 1925, kmEnd: 2234,
    story: "La dernière montée. Après León et sa cathédrale, la Cruz de Ferro où les pèlerins déposent une pierre, puis l'ascension d'O Cebreiro (1 300 m), porte de la Galice, redoutée des marcheurs médiévaux pour sa neige et ses loups. Les cent derniers kilomètres, les plus parcourus au monde, jusqu'à la cathédrale del Obradoiro."
  },
  {
    id: 7, name: "Prolongation : Compostelle → Fátima", color: "#2E7D5B",
    kmStart: 2234, kmEnd: 2699, prolongation: true,
    story: "Le chemin d'après. Compostelle atteinte, Roland ne s'arrête pas : il redescend le Caminho Português vers le Portugal, 465 km de plus. Les rias de Galice, le Minho franchi à Valença, les chemins pavés du Minho portugais, Porto, Coimbra et son université millénaire, puis le sanctuaire de Fátima. Un second pèlerinage greffé sur le premier."
  }
];

const WAYPOINTS = [
  // ── TRONÇON 1 · Via Jacobi & Chemin de Genève ──
  { name: "St-Maurice", coords: [46.2192, 7.0056], type: "start", km: 0, country: "🇨🇭", desc: "Départ le 21 août 2026 · Abbaye fondée en 515, l'une des plus anciennes d'Occident toujours en activité" },
  { name: "Montreux", coords: [46.4312, 6.9107], type: "stage", km: 48, country: "🇨🇭", desc: "Riviera vaudoise, Château de Chillon" },
  { name: "Lausanne", coords: [46.5197, 6.6323], type: "major", km: 78, country: "🇨🇭", desc: "Cathédrale gothique, rives du Léman" },
  { name: "Nyon", coords: [46.3833, 6.2390], type: "stage", km: 118, country: "🇨🇭", desc: "Côte lémanique, ville romaine de Noviodunum" },
  { name: "Genève", coords: [46.2044, 6.1432], type: "major", km: 143, country: "🇨🇭", desc: "Fin de la Via Jacobi, passage en France" },
  { name: "Seyssel", coords: [45.9590, 5.8320], type: "stage", km: 211, country: "🇫🇷", desc: "Vallée du Rhône, premier tronçon savoyard" },
  { name: "Yenne", coords: [45.7030, 5.7560], type: "stage", km: 257, country: "🇫🇷", desc: "Pied du Mont du Chat, Avant-pays savoyard" },
  { name: "La Côte-St-André", coords: [45.3940, 5.2600], type: "stage", km: 343, country: "🇫🇷", desc: "Plaines de l'Isère, pays de Berlioz" },
  { name: "Chavanay", coords: [45.4160, 4.7260], type: "stage", km: 411, country: "🇫🇷", desc: "Traversée du Rhône, vignobles en terrasses" },
  { name: "Le Puy-en-Velay", coords: [45.0430, 3.8850], type: "major", km: 537, country: "🇫🇷", desc: "Cathédrale UNESCO et chapelle St-Michel d'Aiguilhe sur son rocher volcanique · jonction avec la Via Podiensis (GR65)" },
  { name: "Saugues", coords: [44.9600, 3.5480], type: "stage", km: 589, country: "🇫🇷", desc: "Margeride, pays de la Bête du Gévaudan" },
  { name: "Aumont-Aubrac", coords: [44.7220, 3.2830], type: "stage", km: 638, country: "🇫🇷", desc: "Plateau de l'Aubrac, burons et grands espaces" },
  { name: "Espalion", coords: [44.5220, 2.7620], type: "stage", km: 711, country: "🇫🇷", desc: "Vallée du Lot, Pont-Vieux classé" },
  { name: "Conques", coords: [44.5990, 2.3970], type: "major", km: 771, country: "🇫🇷", desc: "Abbatiale Sainte-Foy et son tympan du Jugement dernier · l'une des plus belles découvertes du voyage · fin du tronçon 1" },

  // ── TRONÇON 2 · Liaison Conques → Toulouse ──
  { name: "Villefranche-de-Rouergue", coords: [44.3520, 2.0370], type: "stage", km: 832, country: "🇫🇷", desc: "Bastide royale du XIIIe siècle" },
  { name: "Najac", coords: [44.2190, 1.9770], type: "stage", km: 857, country: "🇫🇷", desc: "Forteresse perchée sur son éperon rocheux" },
  { name: "Cordes-sur-Ciel", coords: [44.0640, 1.9530], type: "stage", km: 898, country: "🇫🇷", desc: "Bastide « dans le ciel », construite après les guerres cathares" },
  { name: "Gaillac", coords: [43.9010, 1.8970], type: "stage", km: 931, country: "🇫🇷", desc: "Vignoble parmi les plus anciens de France" },
  { name: "Toulouse", coords: [43.6045, 1.4440], type: "major", km: 1000, country: "🇫🇷", desc: "Arrivée le 7 octobre 2026, cap des 1 000 km franchi · place du Capitole, basilique Saint-Sernin, la Ville rose · fin du tronçon 2" },

  // ── TRONÇON 3 · Voie d'Arles, Lourdes & Somport ──
  { name: "L'Isle-Jourdain", coords: [43.6120, 1.0830], type: "stage", km: 1037, country: "🇫🇷", desc: "Entrée en Gascogne, Via Tolosana" },
  { name: "Auch", coords: [43.6460, 0.5860], type: "major", km: 1082, country: "🇫🇷", desc: "Cathédrale Sainte-Marie, capitale de la Gascogne" },
  { name: "Marciac", coords: [43.5240, 0.1630], type: "stage", km: 1142, country: "🇫🇷", desc: "Bastide gasconne, terre de jazz" },
  { name: "Lourdes", coords: [43.0955, -0.0456], type: "major", km: 1202, country: "🇫🇷", desc: "Crochet non prévu au programme · sanctuaire Notre-Dame, grotte de Massabielle et basiliques, les Pyrénées en arrière-plan" },
  { name: "Oloron-Sainte-Marie", coords: [43.1940, -0.6110], type: "stage", km: 1297, country: "🇫🇷", desc: "Cité du Béarn au confluent de deux gaves · portail roman sculpté de la cathédrale Sainte-Marie" },
  { name: "Col du Somport", coords: [42.7960, -0.5250], type: "major", km: 1367, country: "🇫🇷", desc: "Summus Portus, environ 1 630 m · passage pyrénéen emprunté depuis l'Antiquité · sortie de France · fin du tronçon 3" },

  // ── TRONÇON 4 · Camino Aragonés ──
  { name: "Villanúa", coords: [42.6787, -0.5310], type: "stage", km: 1385, country: "🇪🇸", desc: "Première halte espagnole, dans la descente des Pyrénées" },
  { name: "Jaca", coords: [42.5710, -0.5500], type: "major", km: 1400, country: "🇪🇸", desc: "Ancienne capitale du royaume d'Aragon · cathédrale romane du XIe siècle et citadelle pentagonale" },
  { name: "Artieda", coords: [42.5868, -0.9835], type: "stage", km: 1445, country: "🇪🇸", desc: "Village de la Jacetania, Espagne rurale préservée" },
  { name: "Ruesta", coords: [42.5888, -1.0762], type: "stage", km: 1457, country: "🇪🇸", desc: "Maisons abandonnées et vestiges médiévaux, une atmosphère saisissante" },
  { name: "Undués de Lerda", coords: [42.5655, -1.1689], type: "stage", km: 1472, country: "🇪🇸", desc: "Hameau des Cinco Villas, soixante habitants" },
  { name: "Sangüesa", coords: [42.5750, -1.2810], type: "stage", km: 1486, country: "🇪🇸", desc: "Portail sculpté de Santa María la Real" },
  { name: "Puente la Reina", coords: [42.6720, -1.8150], type: "major", km: 1544, country: "🇪🇸", desc: "Pont roman à six arches du XIe siècle · jonction avec le Camino Francés · fin du tronçon 4" },

  // ── TRONÇON 5 · Camino Francés, Rioja & Castille ──
  { name: "Estella", coords: [42.6710, -2.0320], type: "stage", km: 1566, country: "🇪🇸", desc: "« Estella la Bella » · palais des rois de Navarre" },
  { name: "Los Arcos", coords: [42.5697, -2.1923], type: "stage", km: 1587, country: "🇪🇸", desc: "Église Santa María et architecture historique navarraise" },
  { name: "Logroño", coords: [42.4660, -2.4450], type: "major", km: 1615, country: "🇪🇸", desc: "Capitale de La Rioja · vieille ville, cathédrale et tradition gastronomique" },
  { name: "Nájera", coords: [42.4167, -2.7337], type: "stage", km: 1644, country: "🇪🇸", desc: "Monastère Santa María la Real, ancienne cité royale" },
  { name: "Santo Domingo de la Calzada", coords: [42.4407, -2.9536], type: "stage", km: 1665, country: "🇪🇸", desc: "Un coq et une poule vivants dans la cathédrale, souvenir d'une légende médiévale" },
  { name: "Belorado", coords: [42.4267, -3.1965], type: "stage", km: 1687, country: "🇪🇸", desc: "Atteinte après une journée de vents violents" },
  { name: "Burgos", coords: [42.3440, -3.6970], type: "major", km: 1737, country: "🇪🇸", desc: "Cathédrale gothique UNESCO, l'une des grandes merveilles du parcours · promenades le long de l'Arlanzón" },
  { name: "Hornillos del Camino", coords: [42.3385, -3.9260], type: "stage", km: 1758, country: "🇪🇸", desc: "Entrée dans la Meseta, 44 habitants" },
  { name: "Hontanas", coords: [42.3126, -4.0445], type: "stage", km: 1777, country: "🇪🇸", desc: "Village invisible jusqu'au dernier moment, caché dans un repli de la plaine" },
  { name: "Castrojeriz", coords: [42.2884, -4.1406], type: "stage", km: 1787, country: "🇪🇸", desc: "Dominé par son château médiéval · ruines du couvent de San Antón sur le chemin" },
  { name: "Frómista", coords: [42.2677, -4.4055], type: "stage", km: 1812, country: "🇪🇸", desc: "Église San Martín, l'un des sommets de l'art roman espagnol" },
  { name: "Carrión de los Condes", coords: [42.3385, -4.6017], type: "stage", km: 1831, country: "🇪🇸", desc: "Ancienne cité de pèlerinage au cœur de la Tierra de Campos" },
  { name: "Ledigos", coords: [42.3546, -4.8646], type: "stage", km: 1854, country: "🇪🇸", desc: "Position de Roland au 22 novembre 2026 · village de 59 habitants, à 883 m d'altitude" },
  { name: "Sahagún", coords: [42.3719, -5.0313], type: "stage", km: 1870, country: "🇪🇸", desc: "Berceau de l'art mudéjar en brique, à mi-chemin du Camino Francés" },
  { name: "El Burgo Ranero", coords: [42.4223, -5.2187], type: "stage", km: 1888, country: "🇪🇸", desc: "Maisons de terre crue au bord de la plaine léonaise" },
  { name: "Mansilla de las Mulas", coords: [42.4991, -5.4169], type: "stage", km: 1907, country: "🇪🇸", desc: "Remparts médiévaux, dernière halte avant León" },
  { name: "León", coords: [42.5935, -5.5616], type: "major", km: 1925, country: "🇪🇸", desc: "Prochaine grande étape · cathédrale gothique et ses vitraux · fin du tronçon 5" },

  // ── TRONÇON 6 · Camino Francés, montagnes de Galice ──
  { name: "Hospital de Órbigo", coords: [42.4636, -5.8819], type: "stage", km: 1958, country: "🇪🇸", desc: "Long pont médiéval du Paso Honroso" },
  { name: "Astorga", coords: [42.4554, -6.0529], type: "major", km: 1974, country: "🇪🇸", desc: "Cathédrale et palais épiscopal de Gaudí, ancienne Asturica Augusta romaine" },
  { name: "Rabanal del Camino", coords: [42.4817, -6.2843], type: "stage", km: 1994, country: "🇪🇸", desc: "1 145 m · début de la montée vers les monts de León" },
  { name: "Molinaseca", coords: [42.5218, -6.4461], type: "stage", km: 2020, country: "🇪🇸", desc: "Après la Cruz de Ferro, où les pèlerins déposent une pierre" },
  { name: "Ponferrada", coords: [42.5460, -6.5900], type: "major", km: 2028, country: "🇪🇸", desc: "Château des Templiers, gardiens des pèlerins · entrée dans le Bierzo" },
  { name: "O Cebreiro", coords: [42.7177, -7.0530], type: "major", km: 2080, country: "🇪🇸", desc: "1 300 m · porte de la Galice, montée redoutée des marcheurs médiévaux · pallozas aux toits de chaume" },
  { name: "Triacastela", coords: [42.7622, -7.2141], type: "stage", km: 2101, country: "🇪🇸", desc: "Descente dans les vallées galiciennes" },
  { name: "Sarria", coords: [42.7710, -7.4335], type: "stage", km: 2119, country: "🇪🇸", desc: "Les 100 derniers kilomètres, point de départ le plus fréquenté du Camino" },
  { name: "Portomarín", coords: [42.8162, -7.6814], type: "stage", km: 2141, country: "🇪🇸", desc: "Village reconstruit pierre par pierre au-dessus du barrage du Miño" },
  { name: "Palas de Rei", coords: [42.8978, -7.8408], type: "stage", km: 2166, country: "🇪🇸", desc: "A Ulloa, bocage et chemins creux" },
  { name: "Arzúa", coords: [42.9237, -8.1528], type: "stage", km: 2195, country: "🇪🇸", desc: "Pays du fromage galicien, avant-dernière étape" },
  { name: "O Pedrouzo", coords: [42.9047, -8.3624], type: "stage", km: 2214, country: "🇪🇸", desc: "Dernière nuit avant la cité de l'apôtre" },
  { name: "Santiago de Compostela", coords: [42.8806, -8.5446], type: "end", km: 2234, country: "🇪🇸", desc: "La cathédrale del Obradoiro · but du Camino, atteint au km 2 234 ⭐" },

  // ── TRONÇON 7 · Prolongation vers Fátima (Caminho Português) ──
  { name: "Padrón", coords: [42.7608, -8.6217], type: "stage", km: 2259, country: "🇪🇸", desc: "Là où la barque de l'apôtre aurait touché terre · début de la descente vers le sud" },
  { name: "Caldas de Reis", coords: [42.6183, -8.6614], type: "stage", km: 2278, country: "🇪🇸", desc: "Thermes romains encore en activité" },
  { name: "Pontevedra", coords: [42.4281, -8.6041], type: "stage", km: 2301, country: "🇪🇸", desc: "Vieille ville des rias, sanctuaire de la Peregrina" },
  { name: "Redondela", coords: [42.2799, -8.6163], type: "stage", km: 2321, country: "🇪🇸", desc: "Les viaducs et le fond de la ria de Vigo" },
  { name: "Tui", coords: [42.0669, -8.6638], type: "stage", km: 2353, country: "🇪🇸", desc: "Cathédrale forteresse au bord du Minho, dernière ville espagnole" },
  { name: "Valença", coords: [41.9917, -8.6064], type: "major", km: 2355, country: "🇵🇹", desc: "Entrée au Portugal · forteresse Vauban face à Tui, de l'autre côté du Minho" },
  { name: "Ponte de Lima", coords: [41.7468, -8.5735], type: "stage", km: 2391, country: "🇵🇹", desc: "Le plus ancien bourg du Portugal, son pont romain sur le Lima" },
  { name: "Barcelos", coords: [41.5314, -8.6192], type: "stage", km: 2425, country: "🇵🇹", desc: "Le coq de Barcelos, emblème du pays, né d'une légende de pèlerin" },
  { name: "Porto", coords: [41.1502, -8.6103], type: "major", km: 2480, country: "🇵🇹", desc: "Les quais du Douro, les azulejos, les caves de Porto · bascule sur le Caminho de Fátima" },
  { name: "Albergaria-a-Velha", coords: [40.6907, -8.4792], type: "stage", km: 2545, country: "🇵🇹", desc: "Pinèdes et eucalyptus de la Beira Litoral" },
  { name: "Coimbra", coords: [40.2112, -8.4291], type: "major", km: 2605, country: "🇵🇹", desc: "Université fondée en 1290, classée UNESCO · ancienne capitale du Portugal" },
  { name: "Ansião", coords: [39.9114, -8.4360], type: "stage", km: 2665, country: "🇵🇹", desc: "Collines du centre, dernière étape avant le sanctuaire" },
  { name: "Fátima", coords: [39.6255, -8.6659], type: "fatima", km: 2699, country: "🇵🇹", desc: "Sanctuaire de Notre-Dame de Fátima · terme de la prolongation, au km 2 699 🕊️" }
];

const KM_SANTIAGO = 2234;     // arrivée à Compostelle, le but du Camino
const PROLONGATION_KM = 465;  // Compostelle → Fátima
const TOTAL_KM = 2699;        // parcours complet, prolongation incluse
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

  if (CARTO_KEY) {
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png?key=' + encodeURIComponent(CARTO_KEY), {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 18
    }).addTo(mapInstance);
  } else {
    // Fond de secours sans clé, proche du gris clair de CARTO Positron.
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ',
      maxZoom: 16
    }).addTo(mapInstance);
  }

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
      color: seg.color, weight: seg.prolongation ? 3 : 4, opacity: 0.35,
      dashArray: seg.prolongation ? '2 8' : '7 9',
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
      color: seg.color, weight: seg.prolongation ? 4.5 : 5.5, opacity: 1,
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
function projectOnRoute(lat, lng) {
  let bestKm = 0, bestD = Infinity;
  for (let km = 0; km <= TOTAL_KM; km += 5) {
    const c = getCoordAtKm(km);
    if (!c) continue;
    const d = trackHaversine(lat, lng, c[0], c[1]);
    if (d < bestD) { bestD = d; bestKm = km; }
  }
  return { km: bestKm, dist: bestD };
}

function kmFromLatLng(lat, lng) {
  return projectOnRoute(lat, lng).km;
}

// ── Compteur de progression de la page (section « Le Curseur de Roland ») ──
// Ces chiffres étaient codés en dur à 0 dans trajet.html : on les alimente
// depuis la position réelle, pour que la page et la carte racontent la même chose.
function updateProgressWidget(km) {
  // Deux phases : la barre mesure Compostelle jusqu'à Santiago, puis la
  // prolongation vers Fátima. « km parcourus » reste le total marché.
  const inProlongation = km > KM_SANTIAGO;
  const phaseKm = inProlongation ? km - KM_SANTIAGO : km;
  const phaseTotal = inProlongation ? PROLONGATION_KM : KM_SANTIAGO;
  const pct = Math.max(0, Math.min(100, (phaseKm / phaseTotal) * 100));
  const fmt = n => Math.round(n).toLocaleString('fr-FR');
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

  const bar = document.querySelector('.progress-bar[data-progress]');
  if (bar) {
    bar.setAttribute('data-progress', pct.toFixed(1));
    bar.style.width = pct.toFixed(1) + '%';   // au cas où l'animation ait déjà eu lieu
  }

  set('barFrom', inProlongation ? 'Santiago 🇪🇸 ⭐' : 'St-Maurice 🇨🇭');
  set('barTo', inProlongation ? 'Fátima 🇵🇹 🕊️' : 'Santiago 🇪🇸 ⭐');
  set('currentKm', fmt(phaseKm));
  set('statKm', fmt(km));
  set('totalKmLabel', fmt(phaseTotal) + ' km');

  const dep = new Date(DEPARTURE_DATE + 'T00:00:00');
  const days = Math.floor((Date.now() - dep.getTime()) / 86400000);
  if (days >= 0) set('statDays', String(days));

  const next = WAYPOINTS.find(w => w.km > km);
  set('statNext', next ? next.name : WAYPOINTS[WAYPOINTS.length - 1].name);
}

function initLiveTracking() {
  if (!mapInstance) return;
  liveLayer = L.layerGroup().addTo(mapInstance);

  async function refresh() {
    try {
      // Le GPS du téléphone fait foi ; à défaut, la position saisie à la main.
      let t = null;
      try {
        const res = await fetch('data/tracking.json', { cache: 'no-store' });
        if (res.ok) t = await res.json();
      } catch (e) { /* hors ligne : on garde la position manuelle */ }

      const live = (t && t.current && typeof t.current.lat === 'number') ? t.current : null;
      const cur = live || CURRENT_POSITION;
      if (!cur || typeof cur.lat !== 'number') return;
      liveLayer.clearLayers();

      const proj = projectOnRoute(cur.lat, cur.lng);
      const onRoute = proj.dist <= OFF_ROUTE_KM;
      const km = proj.km;
      const seg = onRoute ? segmentForKm(km) : null;

      // 1) La couleur du tracé avance jusqu'à sa position, seulement s'il est
      //    bien sur le tracé prévu (sinon on ne colorie rien : voir OFF_ROUTE_KM)
      if (onRoute) {
        drawWalkedUpTo(km);
        updateProgressWidget(km);
      }

      // 2) Son tracé GPS réel (fin, par-dessus)
      if (live && t && Array.isArray(t.trail) && t.trail.length > 1) {
        L.polyline(t.trail, { color: '#1a2332', weight: 2.5, opacity: 0.85, lineCap: 'round', smoothFactor: 1 }).addTo(liveLayer);
      }

      // 3) Le marqueur pulsant
      const pulseIcon = L.divIcon({ html: '<div class="marker-pulse"></div>', iconSize: [20, 20], iconAnchor: [10, 10], className: '' });
      const marker = L.marker([cur.lat, cur.lng], { icon: pulseIcon, zIndexOffset: 2000 }).addTo(liveLayer);
      let when = '';
      if (cur.time) {
        try { when = new Date(cur.time * 1000).toLocaleString('fr-CH', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }); } catch (e) {}
      } else if (cur.date) {
        try { when = new Date(cur.date + 'T12:00:00').toLocaleDateString('fr-CH', { day: '2-digit', month: '2-digit', year: 'numeric' }); } catch (e) { when = cur.date; }
      }
      const batt = (cur.batt != null) ? ` · 🔋 ${Math.round(cur.batt)}%` : '';
      marker.bindPopup(`
        <div style="padding:4px 2px;">
          <div class="popup-title">📍 Roland est ici</div>
          ${cur.place ? `<div style="font-size:0.82rem; color:#4a5568; margin-bottom:0.2rem;">${cur.place}</div>` : ''}
          ${onRoute
            ? `<div class="popup-km">km ${Math.round(km).toLocaleString('fr-FR')} / ${(km > KM_SANTIAGO ? TOTAL_KM : KM_SANTIAGO).toLocaleString('fr-FR')}${km > KM_SANTIAGO ? ' · prolongation vers Fátima' : ''}</div>`
            : `<div class="popup-km">À ${Math.round(proj.dist).toLocaleString('fr-FR')} km du tracé prévu</div>`}
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
  } else if (waypoint.type === 'fatima') {
    html = `<div style="font-size:1.6rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">🕊️</div>`;
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

  const typeLabels = { start: 'Départ', end: 'Compostelle', fatima: 'Terme de la prolongation', major: 'Étape Majeure', stage: 'Étape' };
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
        <span>ℹ️ Les 7 chemins</span>
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
          2 234 km jusqu'à Compostelle<br>+ 465 km de prolongation vers Fátima<br>4 pays · 7 chemins
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
