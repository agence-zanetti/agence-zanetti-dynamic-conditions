# Agence Zanetti – Dynamic Conditions

Fork maintenu par l'Agence Zanetti du plugin [Dynamic Conditions](https://wordpress.org/plugins/dynamicconditions/) (RTO GmbH, GPL-2.0-or-later) pour Elementor.

- Remplace le plugin d'origine : ne jamais activer les deux en même temps.
- Conserve les réglages existants (`dynamicconditions_*`) et la balise dynamique `numberposts`.
- Les sites qui utilisent le fork reçoivent les mises à jour depuis les releases de ce dépôt (écran Mises à jour de WordPress et MainWP).

## Publier une nouvelle version

1. Mettre à jour le numéro de version dans `agence-zanetti-dynamic-conditions.php` (en-tête `Version` et constante `AZDC_VERSION`) et dans `readme.txt` (`Stable tag`).
2. Ajouter une section `## x.y.z` en haut de `CHANGELOG.md`.
3. Fusionner dans `main` : la release et le zip `agence-zanetti-dynamic-conditions.zip` sont créés automatiquement.
