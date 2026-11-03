(function () {
    'use strict';

    function directColumns(row) {
        return Array.prototype.filter.call(row.children || [], function (child) {
            return child.classList && child.classList.contains('elementor-column');
        });
    }

    function closestRow(marker) {
        return marker.closest('.elementor-row, .elementor-container');
    }

    function resetColumns() {
        document.querySelectorAll('.dc-hidden-column').forEach(function (marker) {
            var row = closestRow(marker);
            if (!row) return;
            directColumns(row).forEach(function (column) {
                column.style.width = '';
            });
        });
    }

    function resizeColumns() {
        document.querySelectorAll('.dc-hidden-column').forEach(function (marker) {
            var hiddenSize = parseFloat(marker.dataset.size || '0');
            var row = closestRow(marker);
            if (!row || !hiddenSize) return;

            var columns = directColumns(row);
            if (!columns.length || !row.getBoundingClientRect().width) return;

            var rowWidth = row.getBoundingClientRect().width;
            var sizes = columns.map(function (column) {
                return (column.getBoundingClientRect().width / rowWidth) * 100;
            });
            var total = sizes.reduce(function (sum, size) { return sum + size; }, 0);
            if (!total) return;

            columns.forEach(function (column, index) {
                var size = sizes[index];
                if (size < 100) {
                    column.style.width = (size + (hiddenSize * (size / total))) + '%';
                }
            });
        });
    }

    function hideWrappers() {
        document.querySelectorAll('.dc-hide-wrapper[data-selector]').forEach(function (marker) {
            var selector = marker.dataset.selector;
            if (!selector) return;
            try {
                var wrapper = marker.closest(selector);
                if (wrapper) wrapper.style.display = 'none';
            } catch (error) {
                // Invalid CSS selectors are ignored instead of breaking all conditions JS.
            }
        });
    }

    function hideOthers() {
        document.querySelectorAll('.dc-hide-others[data-selector]').forEach(function (marker) {
            var selector = marker.dataset.selector;
            if (!selector) return;
            try {
                document.querySelectorAll(selector).forEach(function (element) {
                    element.style.display = 'none';
                });
            } catch (error) {
                // Invalid CSS selectors are ignored instead of throwing.
            }
        });
    }

    function apply() {
        resetColumns();
        resizeColumns();
        hideWrappers();
        hideOthers();
    }

    var resizeTimer;
    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(apply, 80);
    }, { passive: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', apply);
    } else {
        apply();
    }

    window.addEventListener('elementor/frontend/init', apply);
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', apply);
    }
})();
