/* ============================================================
   Popote — moteur de l'application
   Menus hebdo · batch cooking · liste de courses · Suisse & France
   Données 100 % locales (localStorage), aucun compte, aucun tracking.
   ============================================================ */
'use strict';

/* ---------- État ---------- */
const STORE_KEY = 'popote.v1';
const DAYS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
const DAYS_SHORT = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
const SLOTS = ['midi', 'soir'];
const SLOT_META = { midi: { label: 'midi', e: '☀️' }, soir: { label: 'soir', e: '🌙' } };
const DIETS = { omni: 'Omnivore', vege: 'Végétarien', vegan: 'Végan', pesce: 'Pescétarien' };

const DEFAULTS = {
  onboarded: false,
  setup: {
    country: 'CH', people: 2, diet: 'omni', noPork: false,
    allergies: { gluten: false, lactose: false, nuts: false },
    budget: 0, theme: 'auto',
    promoMode: true,   // composer les menus avec les actions des enseignes
    stores: null,      // enseignes fréquentées (null = toutes celles du pays)
    /* planning par jour ET par créneau (midi / soir) : familles recomposées,
       cantine le midi, invités du dimanche… chaque créneau a ses convives */
    week: [
      { midi: { on: false, people: 2 }, soir: { on: true, people: 2 } },  // lundi
      { midi: { on: false, people: 2 }, soir: { on: true, people: 2 } },
      { midi: { on: false, people: 2 }, soir: { on: true, people: 2 } },
      { midi: { on: false, people: 2 }, soir: { on: true, people: 2 } },  // jeudi
      { midi: { on: false, people: 2 }, soir: { on: false, people: 2 } }, // vendredi
      { midi: { on: false, people: 2 }, soir: { on: false, people: 2 } },
      { midi: { on: false, people: 2 }, soir: { on: false, people: 2 } }, // dimanche
    ],
  },
  plan: null,            // { created, meals: [{rid, day, slot, locked, cooked, double}] }
  checked: {},           // liste de courses : { ingId: true }
  fridge: [],            // ingrédients déjà à la maison
  includePantry: false,  // compter le fond de placard dans la liste
  batchDone: {},         // étapes batch cochées
  history: [],           // derniers rid utilisés (anti-répétition)
  stats: { weeks: 0, cooked: 0, saved: 0 },
};

let S = load();
function load() {
  try {
    const raw = localStorage.getItem(STORE_KEY);
    if (!raw) return structuredClone(DEFAULTS);
    const s = JSON.parse(raw);
    // fusion défensive avec les défauts (migrations futures)
    const merged = { ...structuredClone(DEFAULTS), ...s, setup: { ...DEFAULTS.setup, ...(s.setup || {}), allergies: { ...DEFAULTS.setup.allergies, ...((s.setup || {}).allergies || {}) } } };
    // migration v1 → planning par jour : « N repas à P personnes » devient N jours actifs
    if (s.setup && !s.setup.week) {
      const n = Math.min(7, s.setup.meals || 4), p = s.setup.people || 2;
      merged.setup.week = Array.from({ length: 7 }, (_, i) => ({
        midi: { on: false, people: p }, soir: { on: i < n, people: p },
      }));
      merged.plan = null;
    }
    // migration v2 → créneaux midi/soir : l'ancien jour unique devient le soir
    if (merged.setup.week.some((d) => d && d.on !== undefined)) {
      merged.setup.week = merged.setup.week.map((d) => ({
        midi: { on: false, people: d.people || 2 }, soir: { on: !!d.on, people: d.people || 2 },
      }));
    }
    if (merged.plan) {
      for (const m of merged.plan.meals) if (!m.slot) m.slot = 'soir';
      if (merged.plan.meals.some((m) => m.day === undefined)) merged.plan = null;
    }
    return merged;
  } catch { return structuredClone(DEFAULTS); }
}
function save() { try { localStorage.setItem(STORE_KEY, JSON.stringify(S)); } catch { /* stockage plein ou bloqué */ } }

/* ---------- Utilitaires ---------- */
const $ = (sel, el = document) => el.querySelector(sel);
const $$ = (sel, el = document) => [...el.querySelectorAll(sel)];
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const byId = (id) => RECIPES.find((r) => r.id === id);

function fmtMoney(v) {
  const n = (Math.round(v * 20) / 20).toFixed(2); // arrondi aux 5 centimes
  return S.setup.country === 'CH' ? `${n} CHF` : `${n.replace('.', ',')} €`;
}
function fmtQty(ing, qty, whole = false) {
  const unit = INGREDIENTS[ing][2];
  if (unit === 'pc') {
    // liste de courses : on achète des unités entières ; recette : les demis ont du sens
    const n = whole ? Math.ceil(qty - 1e-9) : Math.ceil(qty * 2) / 2;
    return `${n}`.replace('.', ',');
  }
  const r = Math.ceil(qty / 10) * 10;
  if (unit === 'g') return r >= 1000 ? `${(r / 1000).toFixed(1).replace('.', ',')} kg` : `${r} g`;
  return r >= 1000 ? `${(r / 1000).toFixed(1).replace('.', ',')} L` : `${r} ml`;
}
/* — Bons plans : actions des enseignes appliquées aux prix — */
let PROMOS = weeklyPromos('CH').concat(weeklyPromos('FR')); // sélection démo par défaut
function loadPromoFeed() {
  // flux catalogue réel, publié dans feed/promos.json (voir feed/README.md)
  fetch('feed/promos.json', { cache: 'no-cache' })
    .then((r) => (r.ok ? r.json() : null))
    .then((feed) => {
      if (!Array.isArray(feed) || !feed.length) return;
      const today = new Date().toISOString().slice(0, 10);
      const valid = feed.filter((p) => p.ing && INGREDIENTS[p.ing] && p.discount > 0 && p.discount < 0.9
        && (!p.from || p.from <= today) && (!p.to || p.to >= today));
      if (valid.length) { PROMOS = valid; render(); }
    })
    .catch(() => { /* pas de flux : la sélection démo reste */ });
}
const myStores = () => (S.setup.stores && S.setup.stores.length ? S.setup.stores : RETAILERS[S.setup.country]);
function promoOf(id) {
  if (!S.setup.promoMode) return null;
  const stores = myStores();
  let best = null;
  for (const p of PROMOS) {
    if (p.country !== S.setup.country || p.ing !== id || !stores.includes(p.retailer)) continue;
    if (!best || p.discount > best.discount) best = p;
  }
  return best;
}
const promoFactor = (id) => { const p = promoOf(id); return p ? 1 - p.discount : 1; };

/* prix « exact » (estimation du coût des recettes) */
function rawPrice(ingId, qty, withPromo = true) {
  const i = INGREDIENTS[ingId];
  const p = S.setup.country === 'CH' ? i[4] : i[3];
  const base = i[2] === 'pc' ? qty * p : qty * p / 1000;
  return base * (withPromo ? promoFactor(ingId) : 1);
}
/* prix « d'achat » : quantités arrondies comme en magasin (liste de courses) */
function buyPrice(ingId, qty, withPromo = true) {
  const i = INGREDIENTS[ingId];
  const p = S.setup.country === 'CH' ? i[4] : i[3];
  const base = i[2] === 'pc' ? Math.ceil(qty - 1e-9) * p : (Math.ceil(qty / 10) * 10) * p / 1000;
  return base * (withPromo ? promoFactor(ingId) : 1);
}
function costOf(recipe, people = S.setup.people, withPantry = false, withPromo = true) {
  return recipe.ing.reduce((s, [id, q]) => {
    if (!withPantry && INGREDIENTS[id][1] === 'PL') return s;
    return s + rawPrice(id, q * people, withPromo);
  }, 0);
}
/* part du coût de la recette couverte par des actions (0 → 1) */
function promoShare(r) {
  const full = costOf(r, 1, false, false);
  return full > 0 ? 1 - costOf(r, 1) / full : 0;
}
const costPerPerson = (r) => costOf(r, 1);

/* — planning par jour et par créneau (midi / soir) — */
function activeSlots() {
  const out = [];
  for (let day = 0; day < 7; day++) for (const slot of SLOTS) if (S.setup.week[day][slot].on) out.push({ day, slot });
  return out;
}
const peopleFor = (day, slot) => Math.max(1, S.setup.week[day][slot].people);
const effPeople = (m) => peopleFor(m.day, m.slot) * (m.double ? 2 : 1); // 🍱 repas doublé = restes
const totalPortions = () => (S.plan ? S.plan.meals.reduce((s, m) => s + effPeople(m), 0) : 0);
const mealCost = (m) => costOf(byId(m.rid), effPeople(m));
const slotLabel = (m) => `${DAYS[m.day]} ${SLOT_META[m.slot].e} ${SLOT_META[m.slot].label}`;

function toast(msg) {
  const t = $('#toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(toast._t);
  toast._t = setTimeout(() => t.classList.remove('show'), 2600);
}

function vibrate(ms) { try { navigator.vibrate && navigator.vibrate(ms); } catch { /* pas grave */ } }

/* ---------- Génération du menu ---------- */
function pool() {
  const a = S.setup.allergies;
  return RECIPES.filter((r) => {
    if (S.setup.diet === 'vegan' && r.cat !== 'vegan') return false;
    if (S.setup.diet === 'vege' && !['vege', 'vegan'].includes(r.cat)) return false;
    if (S.setup.diet === 'pesce' && r.cat === 'viande') return false;
    if (S.setup.noPork && r.pk) return false;
    if (a.gluten && r.g) return false;
    if (a.lactose && r.l) return false;
    if (a.nuts && r.nu) return false;
    return true;
  });
}

function scoreRecipe(r, picked, month, slot = 'soir', day = 0) {
  let sc = Math.random() * 1.6;
  if (r.season.length) sc += r.season.includes(month) ? 2 : -2.5;
  if (S.history.includes(r.id)) sc -= 2.2;
  for (const p of picked) {
    if (p.base === r.base) sc -= 1.6;
    if (p.cat === r.cat && ['viande', 'poisson'].includes(r.cat)) sc -= 0.7;
    if (p.id === r.id) sc -= 100;
  }
  // léger bonus batch : les recettes qui se préparent bien à l'avance
  if (r.tags.includes('batch')) sc += 0.4;
  // bons plans : privilégier les recettes dont les ingrédients sont en action
  if (S.setup.promoMode) sc += 3 * promoShare(r);
  // le midi en semaine : rapide et léger ; le week-end, tout est permis
  if (slot === 'midi') {
    if (r.t + r.c <= 30) sc += 1.2;
    else if (day < 5 && r.t + r.c > 45) sc -= 1.8;
    if (r.tags.includes('rapide') || r.tags.includes('léger')) sc += 0.6;
  }
  return sc;
}

function pickBest(p, picked, month, exclude = [], slot = 'soir', day = 0) {
  let best = null, bestSc = -Infinity;
  for (const r of p) {
    if (exclude.includes(r.id)) continue;
    const sc = scoreRecipe(r, picked, month, slot, day);
    if (sc > bestSc) { bestSc = sc; best = r; }
  }
  return best;
}

const slotOrder = (a, b) => (a.day - b.day) || (a.slot === b.slot ? 0 : a.slot === 'midi' ? -1 : 1);

function generateWeek(keepLocked = true) {
  const p = pool();
  const slots = activeSlots();
  if (!slots.length) { S.plan = { created: Date.now(), meals: [] }; save(); return; }
  if (p.length < Math.min(slots.length, 4)) { toast('Pas assez de recettes avec ces filtres — élargis tes critères.'); return; }
  const month = new Date().getMonth() + 1;
  const old = (S.plan && S.plan.meals) || [];
  const meals = slots.map(({ day, slot }) => {
    const prev = old.find((m) => m.day === day && m.slot === slot);
    if (keepLocked && prev && prev.locked && byId(prev.rid)) return { ...prev, cooked: false };
    return { rid: null, day, slot, locked: false, cooked: false };
  });
  const pickedR = () => meals.filter((m) => m.rid).map((m) => byId(m.rid));
  for (const m of meals) {
    if (m.rid) continue;
    m.rid = pickBest(p, pickedR(), month, [], m.slot, m.day).id;
  }
  // respect du budget : remplace le repas le plus cher si on dépasse de >5 %
  const budget = S.setup.budget;
  if (budget > 0) {
    for (let guard = 0; guard < 4; guard++) {
      const total = meals.reduce((s, m) => s + costOf(byId(m.rid), peopleFor(m.day, m.slot)), 0);
      if (total <= budget * 1.05) break;
      const sorted = meals.filter((m) => !m.locked).sort((a, b) => costOf(byId(b.rid), peopleFor(b.day, b.slot)) - costOf(byId(a.rid), peopleFor(a.day, a.slot)));
      if (!sorted.length) break;
      const expensive = sorted[0];
      const used = meals.map((m) => m.rid);
      const cheaper = p.filter((r) => !used.includes(r.id) && costOf(r) < costOf(byId(expensive.rid)) * 0.75)
        .sort((a, b) => costOf(a) - costOf(b))[0];
      if (!cheaper) break;
      expensive.rid = cheaper.id;
    }
  }
  meals.sort(slotOrder);
  S.plan = { created: Date.now(), meals };
  S.checked = {};
  S.batchDone = {};
  S.history = [...meals.map((m) => m.rid), ...S.history].slice(0, 18);
  S.stats.weeks += 1;
  save();
}

/* après modification des créneaux actifs : garde les repas existants,
   complète les nouveaux créneaux, retire ceux qui sont désactivés */
function reconcilePlan() {
  if (!S.plan) return;
  const slots = activeSlots();
  const month = new Date().getMonth() + 1;
  const kept = S.plan.meals.filter((m) => slots.some((s) => s.day === m.day && s.slot === m.slot));
  const p = pool();
  for (const { day, slot } of slots) {
    if (kept.some((m) => m.day === day && m.slot === slot)) continue;
    const best = pickBest(p, kept.map((m) => byId(m.rid)), month, kept.map((m) => m.rid), slot, day);
    if (best) kept.push({ rid: best.id, day, slot, locked: false, cooked: false });
  }
  kept.sort(slotOrder);
  S.plan.meals = kept;
  S.batchDone = {};
  save();
}

function planTotal() { return S.plan ? S.plan.meals.reduce((s, m) => s + mealCost(m), 0) : 0; }

/* ---------- Liste de courses ---------- */
function shoppingItems() {
  if (!S.plan) return [];
  const agg = {};
  for (const m of S.plan.meals) {
    const r = byId(m.rid);
    const n = effPeople(m); // convives du jour, ×2 si repas doublé
    for (const [id, q] of r.ing) {
      agg[id] = (agg[id] || 0) + q * n;
    }
  }
  return Object.entries(agg).map(([id, qty]) => ({
    id, qty,
    rayon: INGREDIENTS[id][1],
    name: INGREDIENTS[id][0],
    price: buyPrice(id, qty),
    saved: buyPrice(id, qty, false) - buyPrice(id, qty), // gain grâce aux actions
    promo: promoOf(id),
    pantry: INGREDIENTS[id][1] === 'PL',
    fridge: S.fridge.includes(id),
  }));
}
function listCost(items) {
  return items.filter((it) => !it.fridge && (!it.pantry || S.includePantry)).reduce((s, it) => s + it.price, 0);
}
function listAsText() {
  const items = shoppingItems();
  const groups = {};
  for (const it of items) {
    if (it.fridge || (it.pantry && !S.includePantry)) continue;
    (groups[it.rayon] = groups[it.rayon] || []).push(it);
  }
  let out = `🍲 Popote — liste de courses (${S.plan.meals.length} repas, ${totalPortions()} portions)\n`;
  for (const [ray, its] of Object.entries(groups)) {
    out += `\n${RAYONS[ray].e} ${RAYONS[ray].n}\n`;
    for (const it of its) out += `  ☐ ${it.name} — ${fmtQty(it.id, it.qty, true)}\n`;
  }
  out += `\n≈ ${fmtMoney(listCost(items))}\nGénérée avec Popote 🍲`;
  return out;
}

/* ---------- Batch cooking ---------- */
function batchPlan() {
  if (!S.plan) return null;
  const recipes = S.plan.meals.map((m) => byId(m.rid));
  const ordered = [...recipes].sort((a, b) => (b.oven - a.oven) || (b.c - a.c));
  const phases = [
    { n: '🔥 Mise en route', steps: [] },
    { n: '🔪 Mise en place', steps: [] },
    { n: '🍳 Cuissons', steps: [] },
    { n: '🫙 Finitions & stockage', steps: [] },
  ];
  const boot = ['Sors tous les ingrédients et pèse ce que tu peux — ça évite les allers-retours.'];
  if (ordered.some((r) => r.oven)) boot.push('Préchauffe le four à 200 °C.');
  if (ordered.some((r) => r.ing.some(([id]) => ['riz', 'rizBasmati', 'pates', 'lentillesVertes', 'quinoa', 'gnocchi', 'pommeDeTerre'].includes(id)))) boot.push("Mets une grande casserole d'eau à bouillir.");
  phases[0].steps = boot.map((t) => ({ r: null, t }));
  for (const r of ordered) {
    for (const [ph, t] of r.steps) {
      phases[ph].steps.push({ r, t });
    }
    phases[3].steps.push({ r, t: `Laisser refroidir, portionner en boîtes. ${r.tip}`, store: true });
  }
  const active = recipes.reduce((s, r) => s + r.t, 0);
  const passive = Math.max(0, ...recipes.map((r) => r.c));
  const batchTime = Math.round(active * 0.7 + passive);
  const soloTime = active + recipes.reduce((s, r) => s + Math.round(r.c * 0.3), 0) + recipes.length * 10;
  return { phases, batchTime, savedTime: Math.max(0, soloTime - batchTime) };
}

/* ---------- Rendu ---------- */
const APP = $('#app');
let TAB = 'week';

function render() {
  if (!S.onboarded) { renderOnboarding(); return; }
  $('#tabbar').style.display = '';
  const views = { week: renderWeek, list: renderList, batch: renderBatch, fridge: renderFridge, me: renderMe };
  views[TAB]();
  $$('#tabbar button').forEach((b) => b.classList.toggle('on', b.dataset.tab === TAB));
}

function switchTab(t) { TAB = t; window.scrollTo(0, 0); render(); }

/* — Semaine — */
function renderWeek() {
  if (!S.plan) { generateWeek(false); }
  const total = planTotal();
  const budget = S.setup.budget;
  const month = new Date().toLocaleDateString('fr-CH', { month: 'long' });
  const nMeals = S.plan.meals.length;
  const portions = totalPortions();
  const kcalAvg = nMeals ? Math.round(S.plan.meals.reduce((s, m) => s + byId(m.rid).kcal, 0) / nMeals) : 0;

  APP.innerHTML = `
  <header class="hero">
    <div class="hero-top">
      <div>
        <h1>Ta semaine <span class="flag">${S.setup.country === 'CH' ? '🇨🇭' : '🇫🇷'}</span></h1>
        <p class="sub">${nMeals} repas · ${portions} portions · saison : ${esc(month)}</p>
      </div>
      <span class="hero-btns">
        <button class="btn-round" id="print" title="Imprimer le menu et la liste">🖨️</button>
        <button class="btn-round" id="regen" title="Régénérer la semaine">🎲</button>
      </span>
    </div>
    <div class="stats-row">
      <div class="stat"><b>${fmtMoney(total)}</b><span>courses estimées</span></div>
      <div class="stat"><b>${portions ? fmtMoney(total / portions) : '—'}</b><span>par portion</span></div>
      <div class="stat"><b>${kcalAvg} kcal</b><span>moy. / repas</span></div>
    </div>
    ${budget > 0 ? budgetBar(total, budget) : ''}
    ${promoBanner()}
  </header>
  <section class="cards">
    ${S.plan.meals.map((m, i) => mealCard(m, i)).join('')}
    <button class="add-day" id="addDay">+ Ajouter un repas (midi ou soir)</button>
  </section>
  <div class="hint">👥 Touche le badge personnes d'un repas pour ajuster les convives de ce jour-là (enfants en garde alternée, invités…) · 🔒 verrouille tes préférés avant de relancer les dés · ↔️ échange · ✅ cuisiné</div>`;

  $('#regen').onclick = () => { generateWeek(true); render(); toast('Nouvelle semaine générée ✨'); };
  $('#print').onclick = printWeek;
  const pb = $('#promoBanner');
  if (pb) pb.onclick = openPromoSheet;
  $('#addDay').onclick = openAddDay;
  $$('.meal').forEach((el) => {
    const i = +el.dataset.i;
    const m = S.plan.meals[i];
    $('.m-open', el).onclick = () => openRecipe(m.rid, peopleFor(m.day, m.slot));
    $('.m-people', el).onclick = (e) => { e.stopPropagation(); openDaySheet(m.day, m.slot); };
    $('.m-lock', el).onclick = (e) => { e.stopPropagation(); m.locked = !m.locked; save(); render(); };
    $('.m-swap', el).onclick = (e) => { e.stopPropagation(); openSwap(i); };
    $('.m-cook', el).onclick = (e) => {
      e.stopPropagation();
      m.cooked = !m.cooked;
      const delta = (PRIX_REPAS_REF[S.setup.country] - costPerPerson(byId(m.rid))) * peopleFor(m.day, m.slot);
      S.stats.cooked += m.cooked ? 1 : -1;
      S.stats.saved += m.cooked ? delta : -delta;
      save(); render();
      if (m.cooked) toast(`Bien joué ! ≈ ${fmtMoney(delta)} économisés vs plats livrés 💪`);
    };
  });
}

/* réglage rapide d'un créneau : convives, doubler, ou retirer le repas */
function openDaySheet(day, slot) {
  const label = `${DAYS[day].toLowerCase()} ${SLOT_META[slot].label}`;
  const paint = () => {
    const n = peopleFor(day, slot);
    const m = S.plan && S.plan.meals.find((x) => x.day === day && x.slot === slot);
    $('#sheet-inner').innerHTML = `
      <h2>${DAYS[day]} ${SLOT_META[slot].e} ${SLOT_META[slot].label}</h2>
      <p class="sub">Qui mange à la maison à ce repas-là ?</p>
      <div class="row-field big"><span>👥 Personnes ${esc(label)}</span>
        <span class="stepper"><button id="dMinus">−</button><b>${n}</b><button id="dPlus">+</button></span>
      </div>
      ${m ? `<label class="check big-check"><input type="checkbox" id="dDouble" ${m.double ? 'checked' : ''}>
        🍱 <span><b>Cuisiner en double</b><br><small>portions ×2 : les restes font un autre repas (≈ ${fmtMoney(PRIX_REPAS_REF[S.setup.country] * n)} de plats à l'emporter économisés)</small></span></label>` : ''}
      <button class="btn-danger" id="dOff">Pas de repas ${esc(label)} — retirer ce créneau</button>
      <button class="btn-primary" id="dOk">C'est noté</button>`;
    $('#dMinus').onclick = () => { S.setup.week[day][slot].people = Math.max(1, n - 1); save(); paint(); };
    $('#dPlus').onclick = () => { S.setup.week[day][slot].people = Math.min(10, n + 1); save(); paint(); };
    const dd = $('#dDouble');
    if (dd) dd.onchange = () => { m.double = dd.checked; save(); paint(); };
    $('#dOff').onclick = () => { S.setup.week[day][slot].on = false; save(); reconcilePlan(); closeSheet(); render(); toast(`${DAYS[day]} ${SLOT_META[slot].label} retiré du planning`); };
    $('#dOk').onclick = () => { closeSheet(); render(); };
  };
  openSheet('');
  paint();
}

/* ajouter un créneau non planifié (midi ou soir) */
function openAddDay() {
  const off = [];
  for (let i = 0; i < 7; i++) for (const slot of SLOTS) if (!S.setup.week[i][slot].on) off.push({ day: i, slot });
  if (!off.length) { toast('Tous les repas de la semaine sont déjà planifiés 💪'); return; }
  openSheet(`
    <h2>Ajouter un repas</h2>
    <p class="sub">Un repas sera généré pour ce créneau, avec son propre nombre de convives — midi rapide, soir plus généreux.</p>
    <div class="swap-list">
      ${off.map(({ day, slot }) => `<button class="swap-item" data-day="${day}" data-slot="${slot}"><span class="m-emoji">${SLOT_META[slot].e}</span><span><b>${DAYS[day]} ${SLOT_META[slot].label}</b><small>${peopleFor(day, slot)} pers. par défaut — ajustable ensuite</small></span></button>`).join('')}
    </div>`);
  $$('.swap-item[data-day]').forEach((b) => b.onclick = () => {
    const day = +b.dataset.day, slot = b.dataset.slot;
    S.setup.week[day][slot].on = true;
    save(); reconcilePlan(); closeSheet(); render();
    toast(`${DAYS[day]} ${SLOT_META[slot].label} ajouté à ta semaine ✨`);
  });
}

/* — Bandeau bons plans : actions actives dans tes enseignes — */
function activePromos() {
  const stores = myStores();
  return PROMOS.filter((p) => p.country === S.setup.country && stores.includes(p.retailer) && INGREDIENTS[p.ing]);
}
function promoBanner() {
  if (!S.setup.promoMode) return '';
  const promos = activePromos();
  if (!promos.length) return '';
  const saved = S.plan ? S.plan.meals.reduce((s, m) => s + (costOf(byId(m.rid), effPeople(m), false, false) - mealCost(m)), 0) : 0;
  return `<button class="promo-banner" id="promoBanner">
    🏷️ <b>${promos.length} actions</b> dans tes magasins cette semaine
    ${saved > 0.05 ? ` · <b>${fmtMoney(saved)}</b> gagnés sur ce menu` : ''} <span class="chev">›</span>
  </button>`;
}

function openPromoSheet() {
  const promos = activePromos().sort((a, b) => b.discount - a.discount);
  const demo = promos.some((p) => p.demo);
  const p2 = pool();
  openSheet(`
    <h2>🏷️ Les actions de la semaine</h2>
    <p class="sub">${S.setup.country === 'CH' ? 'Suisse' : 'France'} · tes enseignes : ${myStores().join(', ')}${demo ? ' · <b>sélection type</b> (branche le flux catalogue pour les vraies actions de tes magasins)' : ''}</p>
    <div class="promo-list">
      ${promos.map((p) => {
        const uses = p2.filter((r) => r.ing.some(([id]) => id === p.ing)).slice(0, 2);
        return `<div class="promo-item">
          <div class="promo-line">
            <span class="promo-name">${esc(INGREDIENTS[p.ing][0])}</span>
            <span class="promo-tag">−${Math.round(p.discount * 100)} %</span>
            <span class="promo-store">${esc(p.retailer)}</span>
          </div>
          ${uses.length ? `<div class="promo-uses">${uses.map((r) => `<button class="promo-recipe" data-rid="${r.id}">${r.e} ${esc(r.n)}</button>`).join('')}</div>` : ''}
        </div>`;
      }).join('')}
    </div>
    <p class="fine">Le menu de la semaine privilégie automatiquement ces produits — relance les dés 🎲 pour en profiter au maximum.</p>`);
  $$('.promo-recipe').forEach((b) => b.onclick = () => { closeSheet(); openRecipe(b.dataset.rid); });
}

function budgetBar(total, budget) {
  const pct = Math.min(100, Math.round((total / budget) * 100));
  const over = total > budget;
  return `<div class="budget ${over ? 'over' : ''}">
    <div class="budget-bar"><i style="width:${pct}%"></i></div>
    <span>${over ? 'Budget dépassé de ' + fmtMoney(total - budget) : 'Budget : ' + fmtMoney(total) + ' / ' + fmtMoney(budget)}</span>
  </div>`;
}

function mealCard(m, i) {
  const r = byId(m.rid);
  const n = peopleFor(m.day, m.slot);
  return `
  <article class="meal ${m.cooked ? 'cooked' : ''}" data-i="${i}">
    <button class="m-open">
      <span class="m-emoji">${r.e}</span>
      <span class="m-main">
        <span class="m-day">${slotLabel(m)} <span class="m-people" role="button" tabindex="0" title="Ajuster les convives de ce repas">👥 ${n}</span>${m.double ? '<span class="m-double" title="Portions doublées : restes pour un autre repas">🍱 ×2</span>' : ''}</span>
        <span class="m-name">${esc(r.n)}</span>
        <span class="m-meta">⏱ ${r.t + r.c} min · ${fmtMoney(costPerPerson(r))}/pers · ${r.kcal} kcal ${catBadge(r)}${promoShare(r) > 0.03 ? ' <i class="badge promo-b" title="Des ingrédients de cette recette sont en action">🏷️ promo</i>' : ''}</span>
      </span>
    </button>
    <span class="m-actions">
      <button class="m-lock ${m.locked ? 'active' : ''}" title="${m.locked ? 'Déverrouiller' : 'Garder ce repas'}">${m.locked ? '🔒' : '🔓'}</button>
      <button class="m-swap" title="Échanger ce repas">↔️</button>
      <button class="m-cook ${m.cooked ? 'active' : ''}" title="Marquer comme cuisiné">✅</button>
    </span>
  </article>`;
}

function catBadge(r) {
  const map = { vegan: ['Végan', 'vgn'], vege: ['Végé', 'vge'], viande: ['Viande', 'vnd'], poisson: ['Poisson', 'psn'] };
  const [label, cls] = map[r.cat];
  return `<i class="badge ${cls}">${label}</i>`;
}

/* — Échange de repas — */
function openSwap(i) {
  const current = S.plan.meals.map((m) => m.rid);
  const month = new Date().getMonth() + 1;
  const alts = pool()
    .filter((r) => !current.includes(r.id))
    .map((r) => ({ r, sc: scoreRecipe(r, S.plan.meals.filter((_, j) => j !== i).map((m) => byId(m.rid)), month, S.plan.meals[i].slot, S.plan.meals[i].day) }))
    .sort((a, b) => b.sc - a.sc)
    .slice(0, 10);
  openSheet(`
    <h2>Remplacer « ${esc(byId(S.plan.meals[i].rid).n)} »</h2>
    <div class="swap-list">
      ${alts.map(({ r }) => `
        <button class="swap-item" data-rid="${r.id}">
          <span class="m-emoji">${r.e}</span>
          <span><b>${esc(r.n)}</b><small>⏱ ${r.t + r.c} min · ${fmtMoney(costPerPerson(r))}/pers ${catBadge(r)}</small></span>
        </button>`).join('')}
    </div>`);
  $$('.swap-item').forEach((b) => b.onclick = () => {
    S.plan.meals[i] = { rid: b.dataset.rid, day: S.plan.meals[i].day, locked: false, cooked: false };
    save(); closeSheet(); render(); toast('Repas remplacé 👍');
  });
}

/* — Liste de courses — */
function renderList() {
  if (!S.plan) { generateWeek(false); }
  const items = shoppingItems();
  const cost = listCost(items);
  const order = ['FL', 'VP', 'CR', 'EP', 'SU', 'BO', 'PL'];
  const groups = order.map((ray) => ({ ray, items: items.filter((it) => it.rayon === ray && !it.fridge) })).filter((g) => g.items.length);
  const fridgeItems = items.filter((it) => it.fridge);
  const done = items.filter((it) => S.checked[it.id] && !it.fridge && (!it.pantry || S.includePantry)).length;
  const totalCount = items.filter((it) => !it.fridge && (!it.pantry || S.includePantry)).length;

  APP.innerHTML = `
  <header class="hero">
    <div class="hero-top">
      <div><h1>Courses 🛒</h1><p class="sub">${totalCount} articles · ${done} cochés</p></div>
      <button class="btn-round" id="share" title="Partager la liste">📤</button>
    </div>
    <div class="stats-row">
      <div class="stat"><b>${fmtMoney(cost)}</b><span>total estimé</span></div>
      <div class="stat"><b>${totalPortions() ? fmtMoney(cost / totalPortions()) : '—'}</b><span>par portion</span></div>
      <div class="stat"><b>${totalPortions()}</b><span>portions</span></div>
    </div>
    ${S.setup.budget > 0 ? budgetBar(cost, S.setup.budget) : ''}
    ${(() => {
      const saved = items.filter((it) => !it.fridge && !it.pantry).reduce((s, it) => s + it.saved, 0);
      return saved > 0.05 ? `<div class="promo-saved">🏷️ ${fmtMoney(saved)} économisés grâce aux actions de tes enseignes</div>` : '';
    })()}
  </header>
  ${groups.map((g) => `
    <section class="ray ${g.ray === 'PL' ? 'pantry' : ''}">
      <h3>${RAYONS[g.ray].e} ${RAYONS[g.ray].n}${g.ray === 'PL' ? `
        <label class="mini-toggle"><input type="checkbox" id="incPantry" ${S.includePantry ? 'checked' : ''}> compter dans le total</label>` : ''}</h3>
      ${g.items.map((it) => `
        <label class="item ${S.checked[it.id] ? 'done' : ''}">
          <input type="checkbox" data-id="${it.id}" ${S.checked[it.id] ? 'checked' : ''}>
          <span class="i-name">${esc(it.name)}${it.promo && !it.pantry ? `<i class="i-promo">🏷️ −${Math.round(it.promo.discount * 100)} % ${esc(it.promo.retailer)}</i>` : ''}</span>
          <span class="i-qty">${fmtQty(it.id, it.qty, true)}</span>
          <span class="i-price">${(!it.pantry || S.includePantry) ? fmtMoney(it.price) : ''}</span>
        </label>`).join('')}
    </section>`).join('')}
  ${fridgeItems.length ? `
    <section class="ray fridge-done">
      <h3>🧊 Déjà chez toi (via ton frigo)</h3>
      ${fridgeItems.map((it) => `<div class="item done"><span class="i-name">${esc(it.name)}</span><span class="i-qty">${fmtQty(it.id, it.qty, true)}</span><span class="i-price">économisé</span></div>`).join('')}
    </section>` : ''}
  <div class="hint">💡 Les prix sont des moyennes ${S.setup.country === 'CH' ? 'suisses (Migros, Coop, Lidl…)' : 'françaises (Carrefour, Leclerc, Lidl…)'} — utiles pour comparer et budgéter, pas au centime près.</div>`;

  $$('.item input[data-id]').forEach((cb) => cb.onchange = () => {
    if (cb.checked) S.checked[cb.dataset.id] = true; else delete S.checked[cb.dataset.id];
    save(); renderList();
  });
  const inc = $('#incPantry');
  if (inc) inc.onchange = () => { S.includePantry = inc.checked; save(); renderList(); };
  $('#share').onclick = async () => {
    const text = listAsText();
    try {
      if (navigator.share) { await navigator.share({ text }); return; }
      await navigator.clipboard.writeText(text);
      toast('Liste copiée dans le presse-papier 📋');
    } catch { toast('Partage annulé'); }
  };
}

/* — Batch cooking — */
function renderBatch() {
  if (!S.plan) { generateWeek(false); }
  const plan = batchPlan();
  let idx = 0;
  const totalSteps = plan.phases.reduce((s, p) => s + p.steps.length, 0);
  const doneSteps = Object.keys(S.batchDone).length;
  const pct = totalSteps ? Math.round((doneSteps / totalSteps) * 100) : 0;

  APP.innerHTML = `
  <header class="hero">
    <div class="hero-top">
      <div><h1>Batch cooking 🍳</h1><p class="sub">Tous tes repas de la semaine en une session</p></div>
    </div>
    <div class="stats-row">
      <div class="stat"><b>≈ ${plan.batchTime} min</b><span>session totale</span></div>
      <div class="stat"><b>≈ ${plan.savedTime} min</b><span>gagnées vs 1 par 1</span></div>
      <div class="stat"><b>${totalPortions()}</b><span>portions prêtes</span></div>
    </div>
    <div class="budget"><div class="budget-bar"><i style="width:${pct}%"></i></div><span>${pct} % de la session</span></div>
  </header>
  ${plan.phases.map((ph) => `
    <section class="phase">
      <h3>${ph.n}</h3>
      ${ph.steps.map((st) => {
        const k = idx++;
        return `<label class="bstep ${S.batchDone[k] ? 'done' : ''}">
          <input type="checkbox" data-k="${k}" ${S.batchDone[k] ? 'checked' : ''}>
          <span>${st.r ? `<i class="bstep-r">${st.r.e} ${esc(st.r.n)}</i>` : ''}${esc(st.t)}${timerBtn(st.t)}</span>
        </label>`;
      }).join('')}
    </section>`).join('')}
  <div class="hint">🫙 Conservation : 3-4 jours au frigo dans des boîtes hermétiques, 3 mois au congélateur. Étiquette avec le nom et la date — futur-toi te dira merci.</div>`;

  $$('.bstep input').forEach((cb) => cb.onchange = () => {
    if (cb.checked) S.batchDone[cb.dataset.k] = true; else delete S.batchDone[cb.dataset.k];
    save(); renderBatch();
    if (Object.keys(S.batchDone).length === totalSteps) { toast('Session terminée — ta semaine est prête ! 🎉'); vibrate([100, 50, 100]); }
  });
  bindTimers();
}

function timerBtn(text) {
  const m = /(\d+)\s*(?:min|h\b)/.exec(text);
  if (!m) return '';
  let mins = parseInt(m[1], 10);
  if (/h\b/.test(m[0])) mins *= 60;
  if (mins < 2 || mins > 240) return '';
  return ` <button class="timer-btn" data-min="${mins}">⏲ ${mins} min</button>`;
}

/* — Frigo / anti-gaspi — */
function renderFridge() {
  const order = ['FL', 'VP', 'CR', 'EP', 'SU', 'BO'];
  const ideas = RECIPES
    .filter((r) => pool().includes(r))
    .map((r) => {
      const ings = r.ing.filter(([id]) => INGREDIENTS[id][1] !== 'PL');
      const have = ings.filter(([id]) => S.fridge.includes(id)).length;
      return { r, have, total: ings.length, pct: ings.length ? have / ings.length : 0 };
    })
    .filter((x) => x.have >= 2)
    .sort((a, b) => b.pct - a.pct || b.have - a.have)
    .slice(0, 6);

  APP.innerHTML = `
  <header class="hero">
    <div class="hero-top">
      <div><h1>Ton frigo 🧊</h1><p class="sub">Coche ce que tu as déjà : retiré de la liste de courses, et on te souffle des idées anti-gaspi.</p></div>
      ${S.fridge.length ? '<button class="btn-round" id="clearFridge" title="Tout vider">🧹</button>' : ''}
    </div>
    ${S.fridge.length ? `<div class="stats-row"><div class="stat"><b>${S.fridge.length}</b><span>ingrédients chez toi</span></div></div>` : ''}
  </header>
  ${ideas.length ? `
  <section class="ray">
    <h3>💡 Idées avec ce que tu as</h3>
    ${ideas.map(({ r, have, total }) => `
      <button class="swap-item idea" data-rid="${r.id}">
        <span class="m-emoji">${r.e}</span>
        <span><b>${esc(r.n)}</b><small>${have}/${total} ingrédients déjà chez toi · ${fmtMoney(costPerPerson(r))}/pers</small></span>
      </button>`).join('')}
  </section>` : ''}
  ${order.map((ray) => {
    const ings = Object.entries(INGREDIENTS).filter(([, v]) => v[1] === ray);
    return `<section class="ray"><h3>${RAYONS[ray].e} ${RAYONS[ray].n}</h3>
      <div class="chips">
        ${ings.map(([id, v]) => `<button class="chip ${S.fridge.includes(id) ? 'on' : ''}" data-id="${id}">${esc(v[0])}</button>`).join('')}
      </div></section>`;
  }).join('')}`;

  $$('.chip').forEach((c) => c.onclick = () => {
    const id = c.dataset.id;
    S.fridge = S.fridge.includes(id) ? S.fridge.filter((x) => x !== id) : [...S.fridge, id];
    save(); renderFridge();
  });
  $$('.idea').forEach((b) => b.onclick = () => openRecipe(b.dataset.rid));
  const cf = $('#clearFridge');
  if (cf) cf.onclick = () => { S.fridge = []; save(); renderFridge(); };
}

/* — Éditeur de planning : 7 jours × 2 créneaux (midi / soir), convives par créneau — */
function weekEditorHTML(week, idPrefix) {
  const cell = (d, i, slot) => {
    const c = d[slot];
    return `<span class="we-slot ${c.on ? 'on' : ''}" data-day="${i}" data-slot="${slot}">
      <button class="we-tgl" type="button" title="${DAYS[i]} ${SLOT_META[slot].label} : ${c.on ? 'planifié' : 'pas de repas'}">${SLOT_META[slot].e}</button>
      <span class="we-people" ${c.on ? '' : 'hidden'}>
        <button class="we-minus" type="button">−</button><b>${c.people}</b><button class="we-plus" type="button">+</button>
      </span>
    </span>`;
  };
  return `<div class="week-editor" id="${idPrefix}">
    <div class="we-head"><span></span><span>${SLOT_META.midi.e} midi</span><span>${SLOT_META.soir.e} soir</span></div>
    ${week.map((d, i) => `
      <div class="we-row ${d.midi.on || d.soir.on ? 'on' : ''}">
        <span class="we-day">${DAYS_SHORT[i]}</span>
        ${cell(d, i, 'midi')}
        ${cell(d, i, 'soir')}
      </div>`).join('')}
  </div>`;
}
function bindWeekEditor(idPrefix, week, onChange) {
  $$(`#${idPrefix} .we-slot`).forEach((el) => {
    const i = +el.dataset.day, slot = el.dataset.slot;
    const c = () => week[i][slot];
    $('.we-tgl', el).onclick = () => { c().on = !c().on; onChange(); };
    const minus = $('.we-minus', el), plus = $('.we-plus', el);
    if (minus) minus.onclick = () => { c().people = Math.max(1, c().people - 1); onChange(); };
    if (plus) plus.onclick = () => { c().people = Math.min(10, c().people + 1); onChange(); };
  });
}

/* — Profil / réglages / stats — */
function renderMe() {
  const st = S.setup;
  APP.innerHTML = `
  <header class="hero">
    <div class="hero-top"><div><h1>Toi & Popote ⚙️</h1><p class="sub">Tout est stocké sur ton appareil. Aucun compte, aucun tracking.</p></div></div>
    <div class="stats-row">
      <div class="stat"><b>${S.stats.weeks}</b><span>semaines planifiées</span></div>
      <div class="stat"><b>${S.stats.cooked}</b><span>repas cuisinés</span></div>
      <div class="stat"><b>${fmtMoney(Math.max(0, S.stats.saved))}</b><span>économisés*</span></div>
    </div>
  </header>
  <section class="ray form">
    <h3>🌍 Pays & foyer</h3>
    <div class="seg" id="segCountry">
      <button data-v="CH" class="${st.country === 'CH' ? 'on' : ''}">🇨🇭 Suisse (CHF)</button>
      <button data-v="FR" class="${st.country === 'FR' ? 'on' : ''}">🇫🇷 France (EUR)</button>
    </div>
    <p class="sub we-help">Active les repas de midi ☀️ et du soir 🌙 jour par jour, chacun avec ses convives — cantine à midi, garde alternée, invités du week-end : tout se règle ici.</p>
    ${weekEditorHTML(st.week, 'weMe')}
    <div class="row-field"><span>Budget courses / semaine <small>(0 = libre)</small></span>
      <input type="number" id="budget" min="0" step="5" value="${st.budget || 0}" class="num"> ${st.country === 'CH' ? 'CHF' : '€'}
    </div>
  </section>
  <section class="ray form">
    <h3>🥗 Régime & allergies</h3>
    <div class="seg wrap" id="segDiet">
      ${Object.entries(DIETS).map(([k, v]) => `<button data-v="${k}" class="${st.diet === k ? 'on' : ''}">${v}</button>`).join('')}
    </div>
    <label class="check"><input type="checkbox" id="noPork" ${st.noPork ? 'checked' : ''}> Sans porc</label>
    <label class="check"><input type="checkbox" id="aGluten" ${st.allergies.gluten ? 'checked' : ''}> Sans gluten</label>
    <label class="check"><input type="checkbox" id="aLactose" ${st.allergies.lactose ? 'checked' : ''}> Sans lactose</label>
    <label class="check"><input type="checkbox" id="aNuts" ${st.allergies.nuts ? 'checked' : ''}> Sans fruits à coque</label>
  </section>
  <section class="ray form">
    <h3>🏷️ Bons plans</h3>
    <label class="check"><input type="checkbox" id="promoMode" ${st.promoMode ? 'checked' : ''}> Composer mes menus avec les actions de la semaine</label>
    <p class="sub">Tes enseignes ${st.country === 'CH' ? 'suisses' : 'françaises'} :</p>
    <div class="chips" id="storeChips">
      ${RETAILERS[st.country].map((r) => `<button class="chip ${myStores().includes(r) ? 'on' : ''}" data-store="${esc(r)}">${esc(r)}</button>`).join('')}
    </div>
    <p class="fine">${PROMOS.some((p) => p.demo) ? 'Sélection type de produits souvent en action — le flux catalogue réel se branche via feed/promos.json.' : 'Flux catalogue actif ✅'}</p>
  </section>
  <section class="ray form">
    <h3>🎨 Apparence</h3>
    <div class="seg" id="segTheme">
      <button data-v="auto" class="${st.theme === 'auto' ? 'on' : ''}">Auto</button>
      <button data-v="light" class="${st.theme === 'light' ? 'on' : ''}">☀️ Clair</button>
      <button data-v="dark" class="${st.theme === 'dark' ? 'on' : ''}">🌙 Sombre</button>
    </div>
  </section>
  <section class="ray about">
    <h3>🍲 Pourquoi Popote ?</h3>
    <ul>
      <li>✅ <b>100 % gratuit</b>, sans compte ni abonnement</li>
      <li>🇨🇭🇫🇷 <b>Suisse & France</b> : prix et monnaie adaptés à ton pays</li>
      <li>📴 <b>Fonctionne hors-ligne</b> — installe-la depuis ton navigateur</li>
      <li>🔒 <b>Vie privée totale</b> : tes données ne quittent jamais ton appareil</li>
      <li>🍂 Saisons, 🧊 anti-gaspi frigo, ⏲ minuteurs, 📤 partage de liste</li>
    </ul>
    <p class="fine">* Économies estimées par rapport à un repas livré ou un plat préparé (${fmtMoney(PRIX_REPAS_REF[st.country])} / portion en moyenne).</p>
    <button class="btn-danger" id="reset">Réinitialiser toutes mes données</button>
  </section>`;

  const upd = (fn, regen = false) => { fn(); save(); if (regen) { S.plan = null; } renderMe(); };
  $$('#segCountry button').forEach((b) => b.onclick = () => upd(() => { S.setup.country = b.dataset.v; S.setup.stores = null; }));
  $$('#segDiet button').forEach((b) => b.onclick = () => upd(() => { S.setup.diet = b.dataset.v; S.plan = null; }));
  $$('#segTheme button').forEach((b) => b.onclick = () => upd(() => { S.setup.theme = b.dataset.v; applyTheme(); }));
  bindWeekEditor('weMe', S.setup.week, () => { save(); reconcilePlan(); renderMe(); });
  $('#promoMode').onchange = (e) => upd(() => { S.setup.promoMode = e.target.checked; });
  $$('#storeChips .chip').forEach((c) => c.onclick = () => upd(() => {
    const store = c.dataset.store;
    let stores = myStores().slice();
    stores = stores.includes(store) ? stores.filter((x) => x !== store) : [...stores, store];
    if (!stores.length) stores = [store]; // au moins une enseigne
    S.setup.stores = stores;
  }));
  $('#budget').onchange = (e) => upd(() => { S.setup.budget = Math.max(0, +e.target.value || 0); });
  $('#noPork').onchange = (e) => upd(() => { S.setup.noPork = e.target.checked; S.plan = null; });
  $('#aGluten').onchange = (e) => upd(() => { S.setup.allergies.gluten = e.target.checked; S.plan = null; });
  $('#aLactose').onchange = (e) => upd(() => { S.setup.allergies.lactose = e.target.checked; S.plan = null; });
  $('#aNuts').onchange = (e) => upd(() => { S.setup.allergies.nuts = e.target.checked; S.plan = null; });
  $('#reset').onclick = () => {
    if (confirm('Effacer réglages, menus et statistiques ? (irréversible)')) {
      localStorage.removeItem(STORE_KEY); S = load(); render();
    }
  };
}

/* — Fiche recette & mode cuisine — */
function openRecipe(rid, people = S.setup.people) {
  const r = byId(rid);
  openSheet(`
    <div class="r-head"><span class="r-emoji">${r.e}</span>
      <div><h2>${esc(r.n)}</h2>
      <p class="sub">⏱ ${r.t} min actif${r.c ? ` + ${r.c} min de cuisson` : ''} · ${fmtMoney(costPerPerson(r))}/pers · ${r.kcal} kcal ${catBadge(r)}</p></div>
    </div>
    <h3>Ingrédients <small>pour ${people} pers.</small></h3>
    <ul class="r-ing">
      ${r.ing.map(([id, q]) => `<li><span>${esc(INGREDIENTS[id][0])}</span><b>${fmtQty(id, q * people)}</b></li>`).join('')}
    </ul>
    <h3>Préparation</h3>
    <ol class="r-steps">${r.steps.map(([, t]) => `<li>${esc(t)}${timerBtn(t)}</li>`).join('')}</ol>
    <p class="r-tip">💡 ${esc(r.tip)}</p>
    <button class="btn-primary" id="cookMode">👨‍🍳 Mode cuisine pas-à-pas</button>`);
  bindTimers();
  $('#cookMode').onclick = () => { closeSheet(); openCookMode(r); };
}

function openCookMode(r) {
  let step = 0;
  const total = r.steps.length;
  const paint = () => {
    $('#sheet-inner').innerHTML = `
      <div class="cook">
        <p class="cook-head">${r.e} ${esc(r.n)} — étape ${step + 1}/${total}</p>
        <p class="cook-step">${esc(r.steps[step][1])}</p>
        <div class="cook-timer">${timerBtn(r.steps[step][1]) || ''}</div>
        <div class="cook-nav">
          <button id="cPrev" ${step === 0 ? 'disabled' : ''}>← Précédent</button>
          <button id="cNext" class="btn-primary">${step === total - 1 ? 'Terminé 🎉' : 'Suivant →'}</button>
        </div>
      </div>`;
    bindTimers();
    $('#cPrev').onclick = () => { step--; paint(); };
    $('#cNext').onclick = () => {
      if (step === total - 1) { closeSheet(); toast('Bon appétit ! 😋'); return; }
      step++; paint();
    };
  };
  openSheet('');
  paint();
}

/* — Bottom sheet — */
function openSheet(html) {
  $('#sheet-inner').innerHTML = html;
  $('#sheet').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeSheet() {
  $('#sheet').classList.remove('open');
  document.body.style.overflow = '';
}

/* — Minuteurs — */
let TIMER = null;
function bindTimers() {
  $$('.timer-btn').forEach((b) => b.onclick = (e) => { e.preventDefault(); e.stopPropagation(); startTimer(+b.dataset.min); });
}
function startTimer(mins) {
  stopTimer();
  const end = Date.now() + mins * 60000;
  const el = $('#timer');
  el.style.display = 'flex';
  const tick = () => {
    const left = Math.max(0, end - Date.now());
    const m = Math.floor(left / 60000), s = Math.floor((left % 60000) / 1000);
    $('#timer-val').textContent = `${m}:${String(s).padStart(2, '0')}`;
    if (left <= 0) { timerDone(); }
  };
  tick();
  TIMER = setInterval(tick, 500);
}
function stopTimer() { if (TIMER) clearInterval(TIMER); TIMER = null; $('#timer').style.display = 'none'; }
function timerDone() {
  stopTimer();
  vibrate([200, 100, 200, 100, 400]);
  beep();
  toast('⏲ Minuteur terminé !');
}
function beep() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    [0, 0.3, 0.6].forEach((t) => {
      const o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.frequency.value = 880; g.gain.setValueAtTime(0.25, ctx.currentTime + t);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + 0.25);
      o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + 0.3);
    });
  } catch { /* audio bloqué */ }
}

/* — Onboarding — */
function renderOnboarding() {
  $('#tabbar').style.display = 'none';
  let step = 0;
  const tmp = structuredClone(S.setup);
  const steps = [
    () => `
      <div class="ob-hero">🍲</div>
      <h1>Bienvenue sur Popote</h1>
      <p>Tes menus de la semaine, ta liste de courses et ton batch cooking — <b>gratuit</b>, <b>hors-ligne</b>, et tes données restent chez toi.</p>
      <p class="sub">2 minutes de réglages, des années de « on mange quoi ce soir ? » en moins.</p>`,
    () => `
      <h1>Tu fais tes courses où ?</h1>
      <div class="seg big" id="obCountry">
        <button data-v="CH" class="${tmp.country === 'CH' ? 'on' : ''}">🇨🇭<br>Suisse<br><small>prix en CHF</small></button>
        <button data-v="FR" class="${tmp.country === 'FR' ? 'on' : ''}">🇫🇷<br>France<br><small>prix en EUR</small></button>
      </div>`,
    () => `
      <h1>Ta semaine type</h1>
      <p class="sub">Active les repas de midi ☀️ et du soir 🌙 que tu veux planifier, et règle qui mange à chaque repas — cantine à midi, garde alternée, invités du dimanche : chaque créneau a ses convives.</p>
      <div class="row-field big"><span>Foyer au complet</span>
        <span class="stepper"><button id="obPm">−</button><b id="obPv">${tmp.people}</b><button id="obPp">+</button></span>
      </div>
      ${weekEditorHTML(tmp.week, 'weOb')}`,
    () => `
      <h1>Tes goûts</h1>
      <div class="seg wrap" id="obDiet">
        ${Object.entries(DIETS).map(([k, v]) => `<button data-v="${k}" class="${tmp.diet === k ? 'on' : ''}">${v}</button>`).join('')}
      </div>
      <label class="check"><input type="checkbox" id="obPork" ${tmp.noPork ? 'checked' : ''}> Sans porc</label>
      <label class="check"><input type="checkbox" id="obGluten" ${tmp.allergies.gluten ? 'checked' : ''}> Sans gluten</label>
      <label class="check"><input type="checkbox" id="obLactose" ${tmp.allergies.lactose ? 'checked' : ''}> Sans lactose</label>
      <label class="check"><input type="checkbox" id="obNuts" ${tmp.allergies.nuts ? 'checked' : ''}> Sans fruits à coque</label>`,
  ];
  const paint = () => {
    APP.innerHTML = `<div class="ob">
      ${steps[step]()}
      <div class="ob-nav">
        ${step > 0 ? '<button id="obBack">←</button>' : '<span></span>'}
        <span class="dots">${steps.map((_, i) => `<i class="${i === step ? 'on' : ''}"></i>`).join('')}</span>
        <button id="obNext" class="btn-primary">${step === steps.length - 1 ? 'C\'est parti ! 🚀' : 'Suivant'}</button>
      </div>
    </div>`;
    const back = $('#obBack');
    if (back) back.onclick = () => { step--; paint(); };
    $('#obNext').onclick = () => {
      if (step === 2 && !tmp.week.some((d) => d.midi.on || d.soir.on)) { toast('Choisis au moins un repas à planifier 😉'); return; }
      if (step < steps.length - 1) { step++; paint(); return; }
      S.setup = tmp; S.onboarded = true; save();
      generateWeek(false); TAB = 'week'; render();
      toast('Ta première semaine est prête ✨');
    };
    $$('#obCountry button').forEach((b) => b.onclick = () => { tmp.country = b.dataset.v; paint(); });
    $$('#obDiet button').forEach((b) => b.onclick = () => { tmp.diet = b.dataset.v; paint(); });
    const bind = (id, fn) => { const el = $(id); if (el) el.onclick = fn; };
    // « foyer au complet » : ajuste d'un coup tous les créneaux
    const setAll = (n) => { tmp.people = n; tmp.week.forEach((d) => { d.midi.people = n; d.soir.people = n; }); paint(); };
    bind('#obPm', () => setAll(Math.max(1, tmp.people - 1)));
    bind('#obPp', () => setAll(Math.min(10, tmp.people + 1)));
    if ($('#weOb')) bindWeekEditor('weOb', tmp.week, paint);
    const chk = (id, fn) => { const el = $(id); if (el) el.onchange = (e) => fn(e.target.checked); };
    chk('#obPork', (v) => { tmp.noPork = v; });
    chk('#obGluten', (v) => { tmp.allergies.gluten = v; });
    chk('#obLactose', (v) => { tmp.allergies.lactose = v; });
    chk('#obNuts', (v) => { tmp.allergies.nuts = v; });
  };
  paint();
}

/* — Impression : menu de la semaine + liste, à coller sur le frigo — */
function printWeek() {
  if (!S.plan || !S.plan.meals.length) { toast("Rien à imprimer — génère d'abord ta semaine."); return; }
  const items = shoppingItems();
  const order = ['FL', 'VP', 'CR', 'EP', 'SU', 'BO', 'PL'];
  const groups = order
    .map((ray) => ({ ray, items: items.filter((it) => it.rayon === ray && !it.fridge && (!it.pantry || S.includePantry)) }))
    .filter((g) => g.items.length);
  $('#print-area').innerHTML = `
    <h1>🍲 Popote — ma semaine</h1>
    <table class="p-menu"><tbody>
      ${S.plan.meals.map((m) => {
        const r = byId(m.rid);
        return `<tr><td class="p-day">${slotLabel(m)}<br><small>${effPeople(m)} portion(s)${m.double ? ' · 🍱 ×2' : ''}</small></td>
          <td>${r.e} ${esc(r.n)}</td><td class="p-time">${r.t + r.c} min</td></tr>`;
      }).join('')}
    </tbody></table>
    <h2>Liste de courses — ≈ ${fmtMoney(listCost(items))}</h2>
    <div class="p-cols">
      ${groups.map((g) => `<div><h3>${RAYONS[g.ray].n}</h3><ul>${g.items.map((it) => `<li>☐ ${esc(it.name)} — ${fmtQty(it.id, it.qty, true)}</li>`).join('')}</ul></div>`).join('')}
    </div>
    <p class="p-foot">Généré avec Popote · gratuit · Suisse & France</p>`;
  window.print();
}

/* — Thème — */
function applyTheme() {
  const t = S.setup.theme;
  document.documentElement.dataset.theme = t === 'auto' ? '' : t;
}

/* ---------- Démarrage ---------- */
applyTheme();
$$('#tabbar button').forEach((b) => b.onclick = () => switchTab(b.dataset.tab));
$('#sheet').addEventListener('click', (e) => { if (e.target.id === 'sheet') closeSheet(); });
$('#sheet-close').onclick = closeSheet;
$('#timer-stop').onclick = stopTimer;
render();
loadPromoFeed();

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js').catch(() => { /* hors-ligne indisponible, l'app marche quand même */ });
}
