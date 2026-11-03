<?php
namespace AgenceZanetti\DynamicConditions;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use ElementorPro\Modules\DynamicTags\Module as Pro_Dynamic_Tags_Module;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Number_Posts_Tag extends Tag {
    const WRAPPED_TAG = false;

    public function get_name() {
        // Must stay identical to the upstream tag name for saved Elementor data.
        return 'numberposts';
    }

    public function get_title() {
        return __( 'Number posts', 'dynamicconditions' );
    }

    public function get_group() {
        return [ Pro_Dynamic_Tags_Module::POST_GROUP ];
    }

    public function get_categories() {
        return [ Pro_Dynamic_Tags_Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {
        $categories = [];
        foreach ( get_categories( [ 'hide_empty' => false ] ) as $category ) {
            $categories[ (int) $category->term_id ] = $category->name;
        }

        $post_types = [];
        foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) {
            $post_types[ $post_type->name ] = $post_type->labels->singular_name ?: $post_type->label;
        }

        $this->add_control(
            'category',
            [
                'label'       => __( 'Category', 'elementor-pro' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'default'     => [],
                'options'     => $categories,
                'multiple'    => true,
            ]
        );

        $this->add_control(
            'posttypes',
            [
                'label'       => __( 'Post-Types', 'elementor-pro' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'default'     => [],
                'options'     => $post_types,
                'multiple'    => true,
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        $category_ids = isset( $settings['category'] ) && is_array( $settings['category'] )
            ? array_filter( array_map( 'absint', $settings['category'] ) )
            : [];

        $requested_types = isset( $settings['posttypes'] ) && is_array( $settings['posttypes'] )
            ? array_map( 'sanitize_key', $settings['posttypes'] )
            : [];
        $allowed_types = array_keys( get_post_types() );
        $post_types = array_values( array_intersect( $requested_types, $allowed_types ) );

        $posts = get_posts(
            [
                'category'       => implode( ',', $category_ids ),
                'post_type'      => $post_types ?: 'any',
                'numberposts'    => -1,
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'post_status'    => 'publish',
            ]
        );

        echo esc_html( (string) count( $posts ) );
    }
}
