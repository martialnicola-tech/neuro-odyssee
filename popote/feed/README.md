# Flux catalogue « Bons plans »

Popote compose les menus de la semaine en privilégiant les produits en action
dans les enseignes de l'utilisateur. Sans flux, une **sélection type** tourne
chaque semaine (déterministe, marquée `demo`). Pour brancher les **vraies
actions**, publier un fichier `promos.json` dans ce dossier.

## Format `promos.json`

```json
[
  {
    "country": "CH",
    "retailer": "Migros",
    "ing": "pouletFilet",
    "discount": 0.25,
    "from": "2026-08-24",
    "to": "2026-08-30"
  }
]
```

- `country` : `CH` ou `FR`
- `retailer` : doit correspondre aux enseignes de `RETAILERS` dans `js/data.js`
  (Migros, Coop, Lidl, Aldi, Denner / Carrefour, E.Leclerc, Intermarché, Lidl, Aldi)
- `ing` : identifiant d'ingrédient de `INGREDIENTS` dans `js/data.js`
- `discount` : fraction de rabais (0.25 = −25 %)
- `from` / `to` : période de validité (AAAA-MM-JJ, inclusives). Les entrées
  expirées ou inconnues sont ignorées ; si aucune entrée n'est valide, la
  sélection démo reprend le relais.

Un exemple complet : `promos.example.json` (renommer en `promos.json` pour
l'activer).

## Automatisation possible

Un script hebdomadaire (cron GitHub Actions, par ex. le lundi matin) peut :
1. récupérer les actions publiées par les enseignes (flyers/API),
2. faire correspondre les produits aux identifiants d'ingrédients Popote,
3. committer le `promos.json` généré — le déploiement Hostinger suit.

Le service worker ne met pas ce fichier en cache (`cache: no-cache` côté app,
en-têtes no-cache côté `.htaccess` racine pour les `.json`).
