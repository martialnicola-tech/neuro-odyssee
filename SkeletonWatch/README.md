# SkeletonWatch

Faux cadran Apple Watch « squelette » (SwiftUI, watchOS 10+).

- Crâne dessiné en vectoriel (`Shape`), mâchoire qui claque chaque seconde
- Cage thoracique en fond, heure et date en serif « os »
- Toucher l'écran : change la couleur des yeux (rouge, vert, bleu, éteint) + haptique
- Mode always-on : yeux atténués, mâchoire fermée

## Lancer
```
brew install xcodegen
cd SkeletonWatch && xcodegen generate
open SkeletonWatch.xcodeproj
```
Choisir un simulateur Apple Watch et lancer. Adapter `bundleIdPrefix` et l'équipe de signature dans `project.yml`.

Note : watchOS n'autorise pas les cadrans tiers ; c'est une app qui s'ouvre au poignet.
