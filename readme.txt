=== Agence Zanetti – Dynamic Conditions ===
Contributors: agence-zanetti
Tags: elementor, conditions, dynamic tags, visibility
Requires at least: 6.5
Requires PHP: 7.4

PHP 8.1 or newer is recommended for maintained production environments.
Stable tag: 1.8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Maintained fork of Dynamic Conditions by RTO GmbH.

== Description ==

Agence Zanetti – Dynamic Conditions is a maintained fork of Dynamic Conditions 1.7.5.
It intentionally preserves the original Elementor setting IDs beginning with `dynamicconditions_`
and the `numberposts` dynamic-tag name so existing Elementor content can continue to work without a data migration.

This fork must replace the original plugin. Do not run both at the same time.

Requirements: Elementor and Elementor Pro must be active. The plugin checks these dependencies at runtime and shows an administrator notice when one is missing.

Main goals of 1.8.0:

* Preserve existing Dynamic Conditions data and behavior.
* Use current generic Elementor frontend render hooks in addition to the historical control hooks.
* Add a control-registration fallback for Elementor 4 Atomic elements whose names begin with `e-`.
* Harden PHP 8 handling for arrays, null values, strings, ACF dates and CSS selectors.
* Remove the original inline JavaScript/document.execCommand implementation from the Widget-ID control.
* Keep debug output restricted to logged-in users who can edit posts/pages and escape the debug payload.
* Avoid changing the process-wide PHP locale while resolving dates.
* Guard optional ACF and Elementor Pro integrations to avoid fatal errors.

== Installation ==

1. Back up the site and database.
2. Upload this plugin while the original Dynamic Conditions plugin is still installed but do not activate both.
3. Deactivate the original Dynamic Conditions plugin.
4. Activate Agence Zanetti – Dynamic Conditions.
5. Test representative pages/templates, especially ACF date conditions, Loop/Theme Builder templates, popups and any Expert options.
6. Remove the original plugin only after validation.

== Compatibility ==

The fork keeps the original `dynamicconditions_*` keys and the `numberposts` dynamic tag.
It is designed as a drop-in replacement for Dynamic Conditions 1.7.5.

Because Elementor 4 changes its element architecture, Atomic support should be validated on the target site before broad production deployment.

The 1.8.0 package has passed PHP syntax checks, JavaScript syntax checks and local smoke tests for core condition evaluation. This is not a formal third-party security audit or a substitute for staging tests with the exact WordPress/Elementor/ACF stack used by a production site.

== Changelog ==

= 1.8.2 =
* Nom affiché : Agence Zanetti – Dynamic Conditions. Le fichier VEILLE.md n'est plus inclus dans le zip.

= 1.8.1 =
* Added updates from the public GitHub repository (agence-zanetti/agence-zanetti-dynamic-conditions). Updates now appear in WordPress and MainWP.
* Update URI now points to the GitHub repository.

= 1.8.0 =
* Fork renamed to Agence Zanetti Dynamic Conditions.
* Preserved existing Elementor storage keys and the Number posts tag name.
* Replaced hard-coded frontend render hooks with Elementor's generic before/after render hooks.
* Added Atomic e-* control-registration fallback.
* Added activation conflict protection against the original plugin.
* Hardened dynamic-value normalization for PHP 8+.
* Hardened ACF date parsing and missing-ACF handling.
* Removed inline script and deprecated document.execCommand copy behavior from Widget-ID.
* Escaped/sanitized plugin-generated HTML and debug output.
* Reworked nested shortcode suppression/restoration when an element is hidden.
* Replaced public jQuery dependency with defensive vanilla JavaScript.
* Added invalid CSS-selector guards for Hide wrapper / Hide other elements.
* Preserved hidden-element style/link output unless Remove Styles is enabled.
* Preserved legacy column-resize markers and behavior.
* Fixed shortcode parsing consistency for weekday/month comparison values.
* Avoided process-wide setlocale changes during date evaluation.
