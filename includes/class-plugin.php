<?php
namespace AgenceZanetti\DynamicConditions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Plugin {
    /** @var self|null */
    private static $instance = null;

    /** @var Admin */
    private $admin;

    /** @var Frontend */
    private $frontend;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->admin = new Admin();
        $this->frontend = new Frontend();
    }

    public function run(): void {
        load_plugin_textdomain( 'dynamicconditions', false, dirname( plugin_basename( AZDC_FILE ) ) . '/languages' );

        // Historical control locations. These preserve the familiar panel location on classic Elementor elements.
        add_action( 'elementor/element/column/section_advanced/after_section_end', [ $this->admin, 'add_condition_fields' ], 10, 3 );
        add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this->admin, 'add_condition_fields' ], 10, 3 );
        add_action( 'elementor/element/common/_section_style/after_section_end', [ $this->admin, 'add_condition_fields' ], 10, 3 );
        add_action( 'elementor/element/popup/section_advanced/after_section_end', [ $this->admin, 'add_condition_fields' ], 10, 3 );
        add_action( 'elementor/element/container/section_layout/after_section_end', [ $this->admin, 'add_condition_fields' ], 10, 3 );

        // Elementor 4 Atomic elements are not all covered by the historical hard-coded hooks.
        add_action( 'elementor/element/after_section_end', [ $this->admin, 'add_atomic_condition_fields' ], 100, 3 );

        add_action( 'elementor/editor/before_enqueue_styles', [ $this->admin, 'enqueue_styles' ] );
        add_action( 'elementor/preview/enqueue_styles', [ $this->admin, 'enqueue_styles' ] );

        // Generic render hooks cover widgets, legacy sections/columns, containers and current Atomic element types.
        add_action( 'elementor/frontend/before_render', [ $this->frontend, 'before_render' ], 10, 1 );
        add_action( 'elementor/frontend/after_render', [ $this->frontend, 'after_render' ], 10, 1 );
        add_action( 'elementor/theme/before_do_popup', [ $this->frontend, 'check_popups_condition' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this->frontend, 'enqueue_assets' ] );

        add_action( 'elementor/dynamic_tags/register', [ $this, 'register_dynamic_tags' ], 10, 1 );
    }

    public function register_dynamic_tags( $dynamic_tags ): void {
        $tag = new Number_Posts_Tag();
        if ( is_object( $dynamic_tags ) && method_exists( $dynamic_tags, 'register' ) ) {
            $dynamic_tags->register( $tag );
        } elseif ( is_object( $dynamic_tags ) && method_exists( $dynamic_tags, 'register_tag' ) ) {
            $dynamic_tags->register_tag( $tag );
        }
    }
}
