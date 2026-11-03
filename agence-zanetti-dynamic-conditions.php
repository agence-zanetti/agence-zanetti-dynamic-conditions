<?php
/**
 * Plugin Name: Agence Zanetti - Dynamic Conditions
 * Description: Conditional visibility for Elementor. Maintained fork of Dynamic Conditions by RTO GmbH, preserving existing dynamicconditions_* settings.
 * Plugin URI: https://github.com/agence-zanetti/agence-zanetti-dynamic-conditions
 * Version: 1.8.3
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Agence Zanetti
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/agence-zanetti/agence-zanetti-dynamic-conditions
 * Text Domain: dynamicconditions
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AZDC_VERSION', '1.8.3' );
define( 'AZDC_FILE', __FILE__ );
define( 'AZDC_DIR', plugin_dir_path( __FILE__ ) );
define( 'AZDC_URL', plugin_dir_url( __FILE__ ) );

// Updates from GitHub. Loaded before the dependency checks so updates keep working
// even when Elementor is temporarily missing.
require_once AZDC_DIR . 'includes/class-updater.php';
\AgenceZanetti\DynamicConditions\Updater::init( AZDC_FILE, AZDC_VERSION );

/**
 * Do not boot the fork while the original plugin is active.
 * Keeping both active can register the same Elementor controls and dynamic tag twice.
 */
function azdc_original_plugin_loaded(): bool {
    return defined( 'DynamicConditions_VERSION' )
        || class_exists( '\\DynamicConditions\\Lib\\DynamicConditions', false )
        || class_exists( '\\DynamicConditions\\Admin\\DynamicConditionsAdmin', false );
}

function azdc_activation_check(): void {
    if ( azdc_original_plugin_loaded() ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'Deactivate the original “Dynamic Conditions” plugin before activating Agence Zanetti - Dynamic Conditions.', 'dynamicconditions' ),
            esc_html__( 'Plugin activation blocked', 'dynamicconditions' ),
            [ 'back_link' => true ]
        );
    }
}
register_activation_hook( __FILE__, 'azdc_activation_check' );

function azdc_dependency_notice(): void {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    $missing = [];
    if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
        $missing[] = 'Elementor';
    }
    if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
        $missing[] = 'Elementor Pro';
    }

    if ( $missing ) {
        printf(
            '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
            esc_html__( 'Agence Zanetti - Dynamic Conditions:', 'dynamicconditions' ),
            esc_html( sprintf( __( 'missing dependency: %s.', 'dynamicconditions' ), implode( ', ', $missing ) ) )
        );
    }
}

function azdc_conflict_notice(): void {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    printf(
        '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
        esc_html__( 'Agence Zanetti - Dynamic Conditions:', 'dynamicconditions' ),
        esc_html__( 'the original Dynamic Conditions plugin is active. Deactivate it before using this fork.', 'dynamicconditions' )
    );
}

function azdc_boot(): void {
    static $booted = false;
    if ( $booted ) {
        return;
    }

    if ( azdc_original_plugin_loaded() ) {
        add_action( 'admin_notices', 'azdc_conflict_notice' );
        return;
    }

    if ( ! defined( 'ELEMENTOR_VERSION' ) || ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
        add_action( 'admin_notices', 'azdc_dependency_notice' );
        return;
    }

    $booted = true;

    require_once AZDC_DIR . 'includes/class-date.php';
    require_once AZDC_DIR . 'includes/class-admin.php';
    require_once AZDC_DIR . 'includes/class-public.php';
    require_once AZDC_DIR . 'includes/class-number-posts-tag.php';
    require_once AZDC_DIR . 'includes/class-plugin.php';

    \AgenceZanetti\DynamicConditions\Plugin::instance()->run();
}

// Boot after every active plugin has been loaded. This is deliberately later than
// Elementor's own file loading so Elementor Pro is detectable regardless of plugin order.
if ( did_action( 'plugins_loaded' ) ) {
    azdc_boot();
} else {
    add_action( 'plugins_loaded', 'azdc_boot', 30 );
}
