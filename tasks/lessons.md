# Lessons Learned

## [2026-03-24] | Static site with i18n | Always verify data-i18n keys match locales JSON exactly
## [2026-03-24] | Multi-page static site | Repeat nav/footer inline since no server-side includes
[2026-08-04] | Déploiement FTP groupé a poussé data/*.json locaux (périmés, mars) et ÉCRASÉ les données serveur (posts.json = articles du journal de Roland, temoignages.json, stats.json). | RÈGLE : data/ = ÉTAT DU SERVEUR (rempli par l'admin PHP), ne JAMAIS l'inclure dans un déploiement. Ne déployer un data/*.json que si on vient de le régénérer À PARTIR de la version live (get → merge → put). Récupération : sauvegardes Hostinger hPanel.
[2026-08-04, suite] | Verdict final après restauration Hostinger (sauvegarde 01/08) : posts.json était DÉJÀ vide le 01/08 (mtime 25 mars) → le déploiement n'a RIEN détruit (fichier vide écrasé par fichier vide). Les images avril-juin dans images/posts/ sont des orphelines de posts supprimés antérieurement via l'admin. | La règle « ne jamais déployer data/ » reste ABSOLUE (le risque était réel), mais pas de perte avérée. Toujours vérifier les mtime/sauvegardes AVANT de conclure à une perte de données.
