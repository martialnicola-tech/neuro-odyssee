# Vendre l'app

1. **Compte** : Apple Developer Program (99 USD/an). Programme « Small Business » : commission Apple de 15 % sous 1 M USD/an, sinon 30 %.
2. **Achat intégré** : créer dans App Store Connect un produit non consommable avec l'identifiant `productID` de `Store/Store.swift` (à remplacer par votre identifiant). Tester avec un fichier StoreKit Configuration dans Xcode.
3. **Marques** : aucun nom, logo ou dessin reconnaissable de Rolex, Breitling, Blancpain, etc. (refus App Review + risque juridique). Les cadrans fournis sont originaux.
4. **Votre propre marque** : un cadran à son design est possible si vous en êtes titulaire ou avez une licence écrite. Ajoutez un `WatchModel` dans `Faces/DialStyle.swift`.
5. **Fiche App Store** : icône 1024x1024 (`Assets.xcassets/AppIcon`), captures d'écran Apple Watch, politique de confidentialité (l'app ne collecte rien).
6. **Publication** : Xcode > Product > Archive > Distribute App > App Store Connect, puis TestFlight avant la soumission.
