# Veille hebdomadaire

Dernier relevé : 2026-11-16

| Élément | Version relevée |
|---|---|
| Dynamic Conditions d'origine (RTO GmbH) | 1.7.5 (dernière mise à jour 2025-02-11) |
| WordPress | 7.1.4 |
| Elementor | 4.4.0 (2026-11-09) |
| Elementor Pro | 4.4.0 (2026-11-09) |
| Notre fork | 1.8.3 |

Résultat : **aucun problème critique ni important**. Quelques points mineurs, listés plus bas, sans correction.

**Audit incomplet sur deux points** (voir « Limites de l'audit »).

## Ce qui a changé depuis le 2026-11-09

- Elementor 4.3.6 → 4.4.0 et Elementor Pro → 4.4.0. Le plugin d'origine et WordPress n'ont pas changé.
- Compatibilité avec Elementor 4.4.0 vérifiée dans le code téléchargé sur wordpress.org (comparaison avec la 4.3.6) :
  - les crochets `elementor/element/after_section_end`, `elementor/element/{élément}/{section}/after_section_end`, `elementor/frontend/before_render` et `after_render`, `elementor/dynamic_tags/register`, `elementor/editor/before_enqueue_styles` et `elementor/preview/enqueue_styles` existent toujours et ne sont pas dépréciés ;
  - les sections `section_advanced` (colonne, section), `_section_style` (widgets) et `section_layout` (conteneur) existent toujours ;
  - les types de contrôles utilisés, les catégories de balises dynamiques, la classe `Tag`, ainsi que `is_edit_mode()` et `is_preview_mode()` n'ont pas changé ;
  - éléments Atomic : seul changement, deux lignes sans effet sur le fork (badge « Nouveau » et une vérification de skin).
- Changelogs d'Elementor et d'Elementor Pro 4.4.0 lus : nouveaux éléments (Liste, Google Maps, Carrousel), MCP, formulaires Atomic. Rien ne retire ni ne modifie ce que le fork utilise.

## Points audités

- Failles publiques de Dynamic Conditions (Patchstack, WPScan, NVD) : une seule, CVE-2025-22642 (XSS, versions ≤ 1.7.4). **Corrigée dans le fork** : les trois sorties concernées sont échappées (`includes/class-public.php` lignes 116, 122 et 129). Comparaison des versions 1.7.4 et 1.7.5 d'origine faite.
- Failles Elementor récentes : XSS d'Elementor ≤ 4.3.5 (corrigée en 4.3.6), faille critique d'envoi de fichiers d'Elementor Pro ≤ 4.2.1 (corrigée en 4.2.2). Aucune ne concerne les fonctions utilisées par le fork. Il faut seulement que les sites soient à jour.
- Relecture ligne par ligne des 8 fichiers PHP et du fichier JS : XSS, données du visiteur, nonce et droits d'accès, SQL, exécution de code, fuite d'information, mise à jour automatique, appels réseau, JavaScript.
  - Aucune action admin ou AJAX, aucune route REST, aucune requête SQL directe, ni `eval` ni `unserialize`.
  - Le mode debug ne s'affiche qu'aux personnes qui peuvent modifier des articles ou des pages, et son contenu est échappé.
  - Mise à jour automatique (`class-updater.php`) : un seul appel réseau, vers une adresse fixe en HTTPS (`api.github.com`). Le zip n'est accepté que s'il vient de `https://github.com/agence-zanetti/agence-zanetti-dynamic-conditions/releases/download/`. Les brouillons et les préversions sont refusés, et le numéro de version est contrôlé. GitHub redirige ensuite le téléchargement vers son propre serveur de fichiers, ce qui est normal et ne peut pas être évité.
  - JavaScript : pas d'`innerHTML`, pas d'`eval`. Un sélecteur CSS invalide est ignoré sans erreur.
- Fichiers embarqués : aucune bibliothèque tierce dans le dépôt.
- Secrets : aucun mot de passe, jeton ou clé dans le dépôt ni dans son historique.
- Outils : `php -l` sans erreur. PHPCS (WordPress.Security, WordPress.DB, phpcs-security-audit) : seulement des faux positifs ou les points mineurs ci-dessous.

## Points mineurs (pas de correction)

1. `includes/class-public.php` lignes 381-382 et 595-599 : l'option « Parse shortcodes » exécute les shortcodes contenus dans la valeur testée. Si la condition lit une donnée que le visiteur peut choisir (par exemple la balise « Paramètre de requête » d'Elementor Pro), il pourrait déclencher un shortcode. Le résultat n'est jamais affiché. L'option est désactivée par défaut, et c'est le comportement d'origine. Conseil : ne pas combiner cette option avec une donnée saisie par le visiteur.
2. `includes/class-public.php` lignes 635 et 638 : les styles CSS d'un bloc masqué restent dans la page. C'est le comportement d'origine, et l'option « Remove styles » le désactive. Seul le CSS est exposé, jamais le contenu.
3. `includes/class-public.php` ligne 656 : la balise d'ouverture de l'élément (option « Hide content only ») est réaffichée telle qu'Elementor l'a produite. Le risque est nul en pratique.
4. `includes/class-number-posts-tag.php` ligne 203 : la balise « Number posts » accepte tous les types de contenu, pas seulement les types publics proposés dans la liste. Elle peut donc afficher le nombre de contenus publiés d'un type interne. Ce n'est qu'un nombre, et seul un éditeur peut le configurer.
5. Il n'y a pas de fichier `index.php` de protection dans `includes/`, `assets/`, `assets/css/` et `assets/js/`. Tous les fichiers PHP bloquent l'accès direct, donc le risque se limite à l'affichage de la liste des fichiers si le serveur l'autorise.
6. `includes/class-updater.php` ligne 74 : l'adresse du site est envoyée à GitHub (dans le User-Agent) lors de la recherche de mise à jour.

## Limites de l'audit

- La fiche Wordfence de Dynamic Conditions n'a pas pu être lue (page inaccessible aux robots). Patchstack, WPScan et NVD ont bien été consultés et concordent : une seule faille, corrigée.
- Elementor Pro est payant, donc son code ne peut pas être téléchargé. Les fonctions Pro utilisées par le fork (balise `numberposts`, filtrage des popups) ont été vérifiées seulement à partir du changelog public, pas dans le code.
