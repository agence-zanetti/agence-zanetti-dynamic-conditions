# Veille hebdomadaire

Dernier relevé : 2026-11-23

| Élément | Version relevée |
|---|---|
| Dynamic Conditions d'origine (RTO GmbH) | 1.7.5 (dernière mise à jour 2025-02-11) |
| WordPress | 7.1.5 |
| Elementor | 4.4.1 (2026-11-16) |
| Elementor Pro | 4.4.1 (2026-11-16) |
| Notre fork | 1.8.3 |

Résultat : **aucun problème critique ni important**. Les points mineurs déjà connus restent valables (liste plus bas), sans correction.

**Audit incomplet sur deux points** (voir « Limites de l'audit »).

## Ce qui a changé depuis le 2026-11-16

- WordPress 7.1.4 → 7.1.5 (version de maintenance), Elementor 4.4.0 → 4.4.1, Elementor Pro 4.4.0 → 4.4.1. Le plugin d'origine n'a pas changé. Le code du fork n'a pas changé non plus.
- Compatibilité avec Elementor 4.4.1 vérifiée dans le code téléchargé sur wordpress.org (comparaison avec la 4.4.0) :
  - les fichiers modifiés concernent la sécurité des attributs personnalisés des éléments Atomic, la sauvegarde des documents, les images, la bibliothèque de modèles, MCP et « Agent Ready » ;
  - aucun ne touche ce que le fork utilise : les crochets (`elementor/element/...after_section_end`, `elementor/frontend/before_render` et `after_render`, `elementor/dynamic_tags/register`, `elementor/editor/before_enqueue_styles`, `elementor/preview/enqueue_styles`), les types de contrôles, la classe `Tag`, le module des balises dynamiques, `is_edit_mode()` et `is_preview_mode()` sont identiques.
- Changelogs d'Elementor et d'Elementor Pro 4.4.1 lus : compatibilité WordPress 7.2, renforcement de la sécurité des attributs et des droits, corrections MCP. Rien ne concerne les contrôles, les balises dynamiques ou le rendu utilisés par le fork.

## Points audités

- Failles publiques de Dynamic Conditions (Patchstack, WPScan, NVD) : toujours une seule, CVE-2025-22642 (XSS, versions ≤ 1.7.4). **Corrigée dans le fork** : les trois sorties concernées sont échappées (`includes/class-public.php` lignes 116, 122 et 129). Vérifié à nouveau en comparant les versions 1.7.4 et 1.7.5 d'origine.
- Failles Elementor récentes :
  - CVE-2026-109878 : faille XSS d'Elementor jusqu'à la 4.4.0, **corrigée en 4.4.1**. Elle ne concerne pas les fonctions utilisées par le fork, mais **les sites doivent passer à Elementor 4.4.1**.
  - CVE-2026-2307 (fuite de données utilisateurs, Elementor ≤ 4.0.5) et CVE-2026-104718 (XSS, Elementor < 4.3.6) : anciennes versions, sans lien avec le fork.
- Relecture ligne par ligne des 8 fichiers PHP, du fichier JS et du fichier de publication GitHub : XSS, données du visiteur, nonce et droits d'accès, SQL, exécution de code, fuite d'information, mise à jour automatique, appels réseau, JavaScript.
  - Aucune action admin ou AJAX, aucune route REST, aucune requête SQL directe, ni `eval` ni `unserialize`.
  - Le mode debug ne s'affiche qu'aux personnes qui peuvent modifier des articles ou des pages, et son contenu est échappé.
  - Mise à jour automatique (`class-updater.php`) : un seul appel réseau, vers une adresse fixe en HTTPS (`api.github.com`). Le zip n'est accepté que s'il vient de `https://github.com/agence-zanetti/agence-zanetti-dynamic-conditions/releases/download/`. Brouillons et préversions refusés, numéro de version contrôlé.
  - JavaScript : pas d'`innerHTML`, pas d'`eval`.
- Fichiers embarqués : aucune bibliothèque tierce dans le dépôt.
- Secrets : aucun mot de passe, jeton ou clé dans le dépôt ni dans son historique.
- Outils : `php -l` sans erreur. PHPCS (WordPress.Security, WordPress.DB, phpcs-security-audit) : mêmes résultats que la semaine dernière, seulement des faux positifs ou les points mineurs ci-dessous.

## Points mineurs (pas de correction)

1. `includes/class-public.php` lignes 381-382 et 595-599 : l'option « Parse shortcodes » exécute les shortcodes contenus dans la valeur testée. Si la condition lit une donnée que le visiteur peut choisir (par exemple la balise « Paramètre de requête » d'Elementor Pro), il pourrait déclencher un shortcode. Le résultat n'est jamais affiché. L'option est désactivée par défaut, et c'est le comportement d'origine. Conseil : ne pas combiner cette option avec une donnée saisie par le visiteur.
2. `includes/class-public.php` lignes 635 et 638 : les styles CSS d'un bloc masqué restent dans la page. C'est le comportement d'origine, et l'option « Remove styles » le désactive. Seul le CSS est exposé, jamais le contenu.
3. `includes/class-public.php` ligne 656 : la balise d'ouverture de l'élément (option « Hide content only ») est réaffichée telle qu'Elementor l'a produite. Le risque est nul en pratique.
4. `includes/class-number-posts-tag.php` ligne 203 : la balise « Number posts » accepte tous les types de contenu, pas seulement les types publics proposés dans la liste. Elle peut donc afficher le nombre de contenus publiés d'un type interne. Ce n'est qu'un nombre, et seul un éditeur peut le configurer.
5. Il n'y a pas de fichier `index.php` de protection dans `includes/`, `assets/`, `assets/css/` et `assets/js/`. Tous les fichiers PHP bloquent l'accès direct, donc le risque se limite à l'affichage de la liste des fichiers si le serveur l'autorise.
6. `includes/class-updater.php` ligne 74 : l'adresse du site est envoyée à GitHub (dans le User-Agent) lors de la recherche de mise à jour.

## Limites de l'audit

- La fiche Wordfence de Dynamic Conditions n'a pas pu être lue (page vide pour les robots). Patchstack, WPScan et NVD ont bien été consultés et concordent : une seule faille, corrigée.
- Elementor Pro est payant, donc son code ne peut pas être téléchargé. Les fonctions Pro utilisées par le fork (balise `numberposts`, filtrage des popups) ont été vérifiées seulement à partir du changelog public, pas dans le code.
