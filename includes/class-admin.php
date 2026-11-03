<?php
namespace AgenceZanetti\DynamicConditions;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as Dynamic_Tags_Module;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Admin {
    /** @var array<string,bool> */
    private $registered = [];

    public function enqueue_styles(): void {
        wp_enqueue_style( 'azdc-admin', AZDC_URL . 'assets/css/admin.css', [], AZDC_VERSION );
    }

    /**
     * Add controls using the original dynamicconditions_* IDs so existing Elementor JSON stays compatible.
     */
    public function add_condition_fields( $element, $section_id = null, $args = null ): void {
        if ( ! is_object( $element ) || ! method_exists( $element, 'start_controls_section' ) || ! method_exists( $element, 'add_control' ) ) {
            return;
        }

        $hash = spl_object_hash( $element );
        if ( isset( $this->registered[ $hash ] ) ) {
            return;
        }
        // Mark first to prevent recursion when end_controls_section() fires generic hooks.
        $this->registered[ $hash ] = true;

        $type = method_exists( $element, 'get_type' ) ? (string) $element->get_type() : 'element';
        $render_type = 'template';

        $value_conditions = [
            'equal', 'not_equal', 'contains', 'not_contains', 'less', 'greater',
            'between', 'in_array', 'in_array_contains',
        ];
        $all_conditions = [
            'equal', 'not_equal', 'contains', 'not_contains', 'less', 'greater',
            'between', 'empty', 'not_empty',
        ];

        $categories = $this->dynamic_categories( [
            'BASE_GROUP', 'TEXT_CATEGORY', 'URL_CATEGORY', 'GALLERY_CATEGORY',
            'IMAGE_CATEGORY', 'MEDIA_CATEGORY', 'POST_META_CATEGORY', 'COLOR_CATEGORY',
        ] );
        $text_categories = $this->dynamic_categories( [
            'BASE_GROUP', 'TEXT_CATEGORY', 'URL_CATEGORY', 'POST_META_CATEGORY',
        ] );

        $element->start_controls_section(
            'dynamicconditions_section',
            [
                'tab'   => Controls_Manager::TAB_ADVANCED,
                'label' => __( 'Dynamic Conditions', 'dynamicconditions' ),
            ]
        );

        $element->add_control(
            'dynamicconditions_dynamic',
            [
                'label'       => __( 'Dynamic Tag', 'dynamicconditions' ),
                'type'        => Controls_Manager::MEDIA,
                'dynamic'     => [ 'active' => true, 'categories' => $categories ],
                'render_type' => $render_type,
                'placeholder' => __( 'Select condition field', 'dynamicconditions' ),
            ]
        );

        $element->add_control(
            'dynamicconditions_visibility',
            [
                'label'       => __( 'Show/Hide', 'dynamicconditions' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'hide',
                'options'     => [
                    'show' => __( 'Show when condition met', 'dynamicconditions' ),
                    'hide' => __( 'Hide when condition met', 'dynamicconditions' ),
                ],
                'render_type' => $render_type,
                'separator'   => 'before',
            ]
        );

        $element->add_control(
            'dynamicconditions_condition',
            [
                'label'       => __( 'Condition', 'dynamicconditions' ),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => false,
                'label_block' => true,
                'options'     => [
                    'equal'             => __( 'Is equal to', 'dynamicconditions' ),
                    'not_equal'         => __( 'Is not equal to', 'dynamicconditions' ),
                    'contains'          => __( 'Contains', 'dynamicconditions' ),
                    'not_contains'      => __( 'Does not contain', 'dynamicconditions' ),
                    'empty'             => __( 'Is empty', 'dynamicconditions' ),
                    'not_empty'         => __( 'Is not empty', 'dynamicconditions' ),
                    'between'           => __( 'Between', 'dynamicconditions' ),
                    'less'              => __( 'Less than', 'dynamicconditions' ),
                    'greater'           => __( 'Greater than', 'dynamicconditions' ),
                    'in_array'          => __( 'In array', 'dynamicconditions' ),
                    'in_array_contains' => __( 'In array contains', 'dynamicconditions' ),
                ],
                'description'  => __( 'Select your condition for this element visibility.', 'dynamicconditions' ),
                'prefix_class' => 'dc-has-condition dc-condition-',
                'render_type'  => $render_type,
            ]
        );

        $element->add_control(
            'dynamicconditions_type',
            [
                'label'       => __( 'Compare Type', 'dynamicconditions' ),
                'type'        => Controls_Manager::SELECT,
                'multiple'    => false,
                'label_block' => true,
                'options'     => [
                    'default'    => __( 'Text', 'dynamicconditions' ),
                    'date'       => __( 'Date', 'dynamicconditions' ),
                    'days'       => __( 'Weekdays', 'dynamicconditions' ),
                    'months'     => __( 'Months', 'dynamicconditions' ),
                    'strtotime'  => __( 'String to time', 'dynamicconditions' ),
                    'int'        => __( 'Integer', 'dynamicconditions' ),
                ],
                'default'     => 'default',
                'render_type' => $render_type,
                'description' => __( 'Select what you want to compare.', 'dynamicconditions' ),
                'condition'   => [ 'dynamicconditions_condition' => $value_conditions ],
            ]
        );

        $element->add_control(
            'dynamicconditions_value',
            [
                'type'        => Controls_Manager::TEXTAREA,
                'label'       => __( 'Conditional value', 'dynamicconditions' ),
                'description' => __( 'Add your conditional value to compare here.', 'dynamicconditions' ),
                'render_type' => $render_type,
                'dynamic'     => [ 'active' => true, 'categories' => $text_categories ],
                'condition'   => [
                    'dynamicconditions_condition' => $value_conditions,
                    'dynamicconditions_type'      => [ 'default', 'strtotime', 'int' ],
                ],
            ]
        );

        $element->add_control(
            'dynamicconditions_value2',
            [
                'type'        => Controls_Manager::TEXTAREA,
                'label'       => __( 'Conditional value', 'dynamicconditions' ) . ' 2',
                'description' => __( 'Add a second condition value when “Between” is selected.', 'dynamicconditions' ),
                'render_type' => $render_type,
                'dynamic'     => [ 'active' => true, 'categories' => $text_categories ],
                'condition'   => [
                    'dynamicconditions_condition' => [ 'between' ],
                    'dynamicconditions_type'      => [ 'default', 'strtotime', 'int' ],
                ],
            ]
        );

        $element->add_control(
            'dynamicconditions_date_value',
            [
                'type'        => Controls_Manager::DATE_TIME,
                'label'       => __( 'Conditional value', 'dynamicconditions' ),
                'description' => __( 'Add your conditional value to compare here.', 'dynamicconditions' ),
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => $value_conditions,
                    'dynamicconditions_type'      => 'date',
                ],
            ]
        );

        $element->add_control(
            'dynamicconditions_date_value2',
            [
                'type'        => Controls_Manager::DATE_TIME,
                'label'       => __( 'Conditional value', 'dynamicconditions' ) . ' 2',
                'description' => __( 'Add a second condition value when “Between” is selected.', 'dynamicconditions' ),
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => [ 'between' ],
                    'dynamicconditions_type'      => 'date',
                ],
            ]
        );

        $element->add_control(
            'dynamicconditions_day_array_value',
            [
                'type'        => Controls_Manager::SELECT2,
                'label'       => __( 'Conditional value', 'dynamicconditions' ),
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => [ 'in_array' ],
                    'dynamicconditions_type'      => 'days',
                ],
                'description' => __( 'Add your conditional value to compare here.', 'dynamicconditions' ),
                'options'     => Date_Helper::get_days_translated(),
                'multiple'    => true,
            ]
        );

        $element->add_control(
            'dynamicconditions_day_value',
            [
                'type'        => Controls_Manager::SELECT,
                'label'       => __( 'Conditional value', 'dynamicconditions' ),
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => array_values( array_diff( $value_conditions, [ 'in_array' ] ) ),
                    'dynamicconditions_type'      => 'days',
                ],
                'description' => __( 'Add your conditional value to compare here.', 'dynamicconditions' ),
                'options'     => Date_Helper::get_days_translated(),
            ]
        );

        $element->add_control(
            'dynamicconditions_day_value2',
            [
                'type'        => Controls_Manager::SELECT,
                'label'       => __( 'Conditional value', 'dynamicconditions' ) . ' 2',
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => [ 'between' ],
                    'dynamicconditions_type'      => 'days',
                ],
                'description' => __( 'Add a second condition value when “Between” is selected.', 'dynamicconditions' ),
                'options'     => Date_Helper::get_days_translated(),
            ]
        );

        $element->add_control(
            'dynamicconditions_month_array_value',
            [
                'type'        => Controls_Manager::SELECT2,
                'label'       => __( 'Conditional value', 'dynamicconditions' ),
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => [ 'in_array' ],
                    'dynamicconditions_type'      => 'months',
                ],
                'description' => __( 'Add your conditional value to compare here.', 'dynamicconditions' ),
                'options'     => Date_Helper::get_months_translated(),
                'multiple'    => true,
            ]
        );

        $element->add_control(
            'dynamicconditions_month_value',
            [
                'type'        => Controls_Manager::SELECT,
                'label'       => __( 'Conditional value', 'dynamicconditions' ),
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => array_values( array_diff( $value_conditions, [ 'in_array' ] ) ),
                    'dynamicconditions_type'      => 'months',
                ],
                'description' => __( 'Add your conditional value to compare here.', 'dynamicconditions' ),
                'options'     => Date_Helper::get_months_translated(),
            ]
        );

        $element->add_control(
            'dynamicconditions_month_value2',
            [
                'type'        => Controls_Manager::SELECT,
                'label'       => __( 'Conditional value', 'dynamicconditions' ) . ' 2',
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => [ 'between' ],
                    'dynamicconditions_type'      => 'months',
                ],
                'description' => __( 'Add a second condition value when “Between” is selected.', 'dynamicconditions' ),
                'options'     => Date_Helper::get_months_translated(),
            ]
        );

        $element->add_control(
            'dynamicconditions_in_array_description',
            [
                'type'        => Controls_Manager::RAW_HTML,
                'render_type' => $render_type,
                'condition'   => [ 'dynamicconditions_condition' => [ 'in_array' ] ],
                'show_label'  => false,
                'raw'         => esc_html__( 'Use comma-separated values to check whether the dynamic value equals one of the items.', 'dynamicconditions' ),
            ]
        );

        $element->add_control(
            'dynamicconditions_in_array_contains_description',
            [
                'type'        => Controls_Manager::RAW_HTML,
                'render_type' => $render_type,
                'condition'   => [ 'dynamicconditions_condition' => [ 'in_array_contains' ] ],
                'show_label'  => false,
                'raw'         => esc_html__( 'Use comma-separated values to check whether the dynamic value contains one of the items.', 'dynamicconditions' ),
            ]
        );

        $language = strtolower( substr( (string) get_locale(), 0, 2 ) );
        $language = preg_match( '/^[a-z]{2}$/', $language ) ? $language : 'en';
        $date_help_url = 'https://www.php.net/manual/' . rawurlencode( $language ) . '/function.strtotime.php';
        $element->add_control(
            'dynamicconditions_date_description',
            [
                'type'        => Controls_Manager::RAW_HTML,
                'render_type' => $render_type,
                'condition'   => [
                    'dynamicconditions_condition' => $value_conditions,
                    'dynamicconditions_type'      => 'strtotime',
                ],
                'show_label' => false,
                'raw'        => sprintf(
                    '<div class="elementor-control-field-description"><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></div>',
                    esc_url( $date_help_url ),
                    esc_html__( 'Supported Date and Time Formats', 'dynamicconditions' )
                ),
            ]
        );

        $element->add_control(
            'dynamicconditions_hr',
            [
                'type'      => Controls_Manager::DIVIDER,
                'style'     => 'thick',
                'condition' => [ 'dynamicconditions_condition' => $value_conditions ],
            ]
        );

        $element->add_control(
            'dynamicconditions_hideContentOnly',
            [
                'type'        => Controls_Manager::SWITCHER,
                'label'       => __( 'Hide only content', 'dynamicconditions' ),
                'description' => __( 'Hide only the inner content and keep an empty element wrapper.', 'dynamicconditions' ),
                'return_value'=> 'on',
                'render_type' => $render_type,
                'condition'   => [ 'dynamicconditions_condition' => $all_conditions ],
            ]
        );

        $element->add_control(
            'dynamicconditions_removeStyles',
            [
                'type'         => Controls_Manager::SWITCHER,
                'label'        => __( 'Remove Styles', 'dynamicconditions' ),
                'description'  => __( 'Remove style and stylesheet-link tags generated by the hidden element. This can affect other elements.', 'dynamicconditions' ),
                'return_value' => 'on',
                'render_type'  => $render_type,
                'condition'    => [ 'dynamicconditions_condition' => $all_conditions ],
            ]
        );

        if ( 'column' === $type ) {
            $element->add_control(
                'dynamicconditions_resizeOtherColumns',
                [
                    'type'         => Controls_Manager::SWITCHER,
                    'label'        => __( 'Resize other columns', 'dynamicconditions' ),
                    'render_type'  => $render_type,
                    'condition'    => [
                        'dynamicconditions_condition'       => $all_conditions,
                        'dynamicconditions_hideContentOnly!'=> 'on',
                    ],
                    'return_value' => 'on',
                ]
            );
        }

        $element->add_control(
            'dynamicconditions_headline_expert',
            [
                'label'     => __( 'Expert', 'dynamicconditions' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $element->add_control(
            'dynamicconditions_parse_shortcodes',
            [
                'type'        => Controls_Manager::SWITCHER,
                'label'       => __( 'Parse shortcodes', 'dynamicconditions' ),
                'render_type' => $render_type,
            ]
        );

        $element->add_control(
            'dynamicconditions_prevent_date_parsing',
            [
                'type'        => Controls_Manager::SWITCHER,
                'label'       => __( 'Prevent date parsing', 'dynamicconditions' ),
                'render_type' => $render_type,
            ]
        );

        $element->add_control(
            'dynamicconditions_hr3',
            [ 'type' => Controls_Manager::DIVIDER, 'style' => 'thick' ]
        );

        $element->add_control(
            'dynamicconditions_hideWrapper',
            [
                'type'        => Controls_Manager::TEXT,
                'label'       => __( 'Hide wrapper', 'dynamicconditions' ),
                'description' => __( 'Hide a parent matching this CSS selector.', 'dynamicconditions' ),
                'placeholder' => 'selector',
                'render_type' => $render_type,
            ]
        );

        $element->add_control(
            'dynamicconditions_hideOthers',
            [
                'type'        => Controls_Manager::TEXT,
                'label'       => __( 'Hide other elements', 'dynamicconditions' ),
                'description' => __( 'Hide all other elements matching this CSS selector.', 'dynamicconditions' ),
                'placeholder' => 'selector',
                'render_type' => $render_type,
            ]
        );

        $element->add_control(
            'dynamicconditions_hr4',
            [ 'type' => Controls_Manager::DIVIDER, 'style' => 'thick' ]
        );

        // Keep the original storage key but remove the inline script/document.execCommand implementation.
        $element_id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
        $element->add_control(
            'dynamicconditions_widget_id',
            [
                'type'        => Controls_Manager::TEXT,
                'label'       => __( 'Widget-ID', 'dynamicconditions' ),
                'default'     => sanitize_text_field( $element_id ),
                'description' => __( 'Elementor element ID. Kept for backward compatibility; no inline copy script is injected.', 'dynamicconditions' ),
                'render_type' => $render_type,
            ]
        );

        $element->add_control(
            'dynamicconditions_hr5',
            [ 'type' => Controls_Manager::DIVIDER, 'style' => 'thick' ]
        );

        $element->add_control(
            'dynamicconditions_debug',
            [
                'type'        => Controls_Manager::SWITCHER,
                'label'       => __( 'Debug-Mode', 'dynamicconditions' ),
                'render_type' => $render_type,
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Fallback for Elementor 4 Atomic elements. Classic element types keep their historical hooks.
     */
    public function add_atomic_condition_fields( $element, $section_id = null, $args = null ): void {
        if ( ! is_object( $element ) || ! method_exists( $element, 'get_name' ) ) {
            return;
        }
        $name = (string) $element->get_name();
        if ( 0 !== strpos( $name, 'e-' ) ) {
            return;
        }
        $this->add_condition_fields( $element, $section_id, $args );
    }

    private function dynamic_categories( array $constant_names ): array {
        $categories = [];
        foreach ( $constant_names as $name ) {
            $constant = Dynamic_Tags_Module::class . '::' . $name;
            if ( defined( $constant ) ) {
                $categories[] = constant( $constant );
            }
        }
        return array_values( array_unique( $categories ) );
    }
}
