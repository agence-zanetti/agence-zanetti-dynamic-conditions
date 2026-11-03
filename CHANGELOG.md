# Agence Zanetti Dynamic Conditions

## 1.8.2

- Nom affiché : Agence Zanetti – Dynamic Conditions. Le fichier VEILLE.md n'est plus inclus dans le zip.

## 1.8.1

- Added updates from the public GitHub repository (`agence-zanetti/agence-zanetti-dynamic-conditions`). Updates now appear in the WordPress Updates screen, the plugins list and MainWP.
- `Update URI` now points to the GitHub repository.

## 1.8.0

Base: Dynamic Conditions 1.7.5 by RTO GmbH (GPL-2.0-or-later).

### Compatibility retained

- All saved Elementor control IDs beginning with `dynamicconditions_` are retained.
- The dynamic tag name `numberposts` is retained.
- Existing condition types are retained: equal, not equal, contains, not contains, empty, not empty, between, less, greater, in array and in array contains.
- Existing compare types are retained: text, date, weekdays, months, string-to-time and integer.
- Existing expert options are retained: shortcode parsing, prevent date parsing, hide wrapper, hide other elements, hidden-content-only, style removal, column resize and debug mode.
- Popup filtering is retained.

### Changes from upstream 1.7.5

1. **Plugin identity**
   - New plugin name and folder, so the fork can be installed alongside the old plugin before the switchover.
   - New PHP namespace (`AgenceZanetti\\DynamicConditions`) to avoid PHP class collisions during installation.
   - Activation is blocked while the original plugin is loaded.

2. **Elementor rendering compatibility**
   - Frontend filtering now uses `elementor/frontend/before_render` and `elementor/frontend/after_render` instead of separate hooks for widget/section/column/container.
   - This reduces maintenance when Elementor adds element types.

3. **Elementor 4 Atomic compatibility**
   - Historical control hooks are kept for classic elements.
   - A generic `elementor/element/after_section_end` fallback registers controls on Atomic elements whose names start with `e-`.
   - Registration is guarded per Elementor element object to prevent duplicate sections or recursive hook execution.

4. **Security/hardening**
   - Plugin-generated attributes/text/debug output are escaped.
   - Debug data is rendered only for users who can edit posts/pages.
   - The old Widget-ID inline `<script>` and `document.execCommand('copy')` code are removed.
   - Optional ACF calls are guarded with `function_exists()`.
   - Invalid CSS selectors in frontend helper behavior are caught instead of throwing JavaScript errors.
   - Dynamic arrays/objects/null values are normalized before string operations, preventing PHP 8 TypeErrors.
   - Popup integration exceptions are contained so an Elementor Pro API change cannot fatal the whole frontend.

5. **Date handling**
   - No process-wide `setlocale(LC_ALL, ...)` changes are made.
   - Localized WordPress day/month names are converted without changing the PHP process locale.
   - ACF date fields are parsed defensively and `Ymd` values get explicit handling.

6. **Hidden rendering**
   - Nested hidden elements restore the shortcode registry exactly instead of merging a shared global cache.
   - `<style>` and `<link>` tags are still preserved by default, matching upstream behavior.
   - Legacy hidden-column resize, hide-wrapper and hide-others markers remain compatible.

7. **Logic robustness**
   - `contains`/`not_contains` and array-contains comparisons cannot receive arrays/objects directly in `strpos()`.
   - Comma-separated `in_array` values are trimmed.
   - Empty needles are ignored for `in_array_contains` instead of matching every string.
   - Shortcode parsing is applied consistently to weekday/month comparison values.

8. **Frontend JavaScript**
   - Removed the runtime jQuery dependency.
   - Added defensive checks for missing rows, zero widths and invalid selectors.
   - Resize work is lightly debounced.

### Intentionally not changed

- No database migration.
- No renamed `dynamicconditions_*` setting keys.
- No new condition builder or AND/OR system.
- No change to loose equality semantics (`==` / `!=`), to avoid silently changing existing conditions.
- No automatic deactivation or deletion of the original plugin.
