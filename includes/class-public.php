<?php
namespace AgenceZanetti\DynamicConditions;

use Elementor\Plugin as Elementor_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Frontend {
    /** @var array<string,array> */
    private $element_settings = [];

    /** @var array<string,array> */
    private $hidden = [];

    /** @var Date_Helper */
    private $date_helper;

    public function __construct() {
        $this->date_helper = new Date_Helper();
    }

    public function enqueue_assets(): void {
        if ( 'edit' === $this->get_mode() ) {
            return;
        }

        wp_enqueue_script( 'azdc-public', AZDC_URL . 'assets/js/public.js', [], AZDC_VERSION, true );
        wp_enqueue_style( 'azdc-public', AZDC_URL . 'assets/css/public.css', [], AZDC_VERSION );
    }

    public function check_popups_condition( $location_manager ): void {
        if ( 'website' !== $this->get_mode() ) {
            return;
        }
        if ( ! class_exists( '\\ElementorPro\\Modules\\ThemeBuilder\\Module' ) || ! is_object( $location_manager ) ) {
            return;
        }
        if ( ! method_exists( $location_manager, 'remove_doc_from_location' ) ) {
            return;
        }

        try {
            $condition_manager = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager();
            $documents = $condition_manager->get_documents_for_location( 'popup' );
            foreach ( $documents as $document_id => $document ) {
                if ( $this->check_condition( $this->get_element_settings( $document ) ) ) {
                    $location_manager->remove_doc_from_location( 'popup', $document_id );
                }
            }
        } catch ( \Throwable $e ) {
            // A popup integration change must never break the public page.
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'AZDC popup condition error: ' . $e->getMessage() );
            }
        }
    }

    public function before_render( $element ): void {
        if ( 'edit' === $this->get_mode() || ! $this->is_element_like( $element ) ) {
            return;
        }

        $settings = $this->get_element_settings( $element );
        if ( ! $this->check_condition( $settings ) ) {
            return;
        }

        $key = $this->element_key( $element );
        $shortcodes = isset( $GLOBALS['shortcode_tags'] ) && is_array( $GLOBALS['shortcode_tags'] )
            ? $GLOBALS['shortcode_tags']
            : [];

        // Nested hidden elements each restore exactly the shortcode registry they inherited.
        $GLOBALS['shortcode_tags'] = [];
        ob_start();

        $this->hidden[ $key ] = [
            'shortcodes' => $shortcodes,
            'ob_level'   => ob_get_level(),
        ];
    }

    public function after_render( $element ): void {
        if ( ! $this->is_element_like( $element ) ) {
            return;
        }

        $key = $this->element_key( $element );
        if ( empty( $this->hidden[ $key ] ) ) {
            return;
        }

        $state = $this->hidden[ $key ];
        unset( $this->hidden[ $key ] );

        $content = '';
        if ( ob_get_level() >= (int) $state['ob_level'] ) {
            $content = (string) ob_get_clean();
        }

        $GLOBALS['shortcode_tags'] = is_array( $state['shortcodes'] ) ? $state['shortcodes'] : [];

        $settings = isset( $this->element_settings[ $key ] ) ? $this->element_settings[ $key ] : $this->get_element_settings( $element );
        $type = method_exists( $element, 'get_type' ) ? (string) $element->get_type() : 'element';

        if ( empty( $settings['dynamicconditions_removeStyles'] ) ) {
            $this->render_assets_from_hidden_content( $content );
        }

        if ( ! empty( $settings['dynamicconditions_hideContentOnly'] ) ) {
            $this->render_empty_wrapper( $element, $content );
        } elseif ( 'column' === $type && ! empty( $settings['dynamicconditions_resizeOtherColumns'] ) ) {
            $size = isset( $settings['_column_size'] ) ? (float) $settings['_column_size'] : 0.0;
            printf( '<div class="dc-hidden-column" data-size="%s" hidden></div>', esc_attr( (string) $size ) );
        }

        if ( ! empty( $settings['dynamicconditions_hideWrapper'] ) && is_scalar( $settings['dynamicconditions_hideWrapper'] ) ) {
            printf(
                '<div class="dc-hide-wrapper" data-selector="%s" hidden></div>',
                esc_attr( (string) $settings['dynamicconditions_hideWrapper'] )
            );
        }

        if ( ! empty( $settings['dynamicconditions_hideOthers'] ) && is_scalar( $settings['dynamicconditions_hideOthers'] ) ) {
            printf(
                '<div class="dc-hide-others" data-selector="%s" hidden></div>',
                esc_attr( (string) $settings['dynamicconditions_hideOthers'] )
            );
        }

        echo '<!-- Agence Zanetti Dynamic Conditions: hidden ' . esc_html( $type ) . ' -->';
    }

    public function check_condition( array $settings ): bool {
        if ( ! $this->has_condition( $settings ) || 'edit' === $this->get_mode() ) {
            return false;
        }

        $condition_met = $this->loop_values( $settings );
        $visibility = (string) self::check_empty( $settings, 'dynamicconditions_visibility', 'hide' );

        if ( 'show' === $visibility ) {
            return ! $condition_met;
        }

        return $condition_met;
    }

    public function has_condition( array $settings ): bool {
        return ! empty( $settings['dynamicconditions_condition'] )
            && ! empty( $settings['dynamicConditionsData']['selectedTag'] );
    }

    public static function check_empty( array $array = [], $key = null, $fallback = null ) {
        if ( empty( $key ) ) {
            return ! empty( $array ) ? $array : $fallback;
        }
        return ! empty( $array[ $key ] ) ? $array[ $key ] : $fallback;
    }

    private function get_element_settings( $element ): array {
        $key = $this->element_key( $element );
        if ( isset( $this->element_settings[ $key ] ) ) {
            return $this->element_settings[ $key ];
        }

        $settings = [];

        // Upstream resolved display values on a clone. Keep that behavior so dynamic-tag
        // resolution cannot mutate the original Elementor element while we inspect it.
        $display_element = $element;
        try {
            if ( is_object( $element ) ) {
                $display_element = clone $element;
            }
        } catch ( \Throwable $e ) {
            $display_element = $element;
        }

        $fields = [
            'dynamicconditions_dynamic',
            'dynamicconditions_condition',
            'dynamicconditions_type',
            'dynamicconditions_resizeOtherColumns',
            'dynamicconditions_hideContentOnly',
            'dynamicconditions_visibility',
            'dynamicconditions_day_value',
            'dynamicconditions_day_value2',
            'dynamicconditions_day_array_value',
            'dynamicconditions_month_value',
            'dynamicconditions_month_value2',
            'dynamicconditions_month_array_value',
            'dynamicconditions_date_value',
            'dynamicconditions_date_value2',
            'dynamicconditions_value',
            'dynamicconditions_value2',
            'dynamicconditions_parse_shortcodes',
            'dynamicconditions_debug',
            'dynamicconditions_hideOthers',
            'dynamicconditions_hideWrapper',
            'dynamicconditions_removeStyles',
            'dynamicconditions_prevent_date_parsing',
            '_column_size',
            '_inline_size',
        ];

        $settings['dynamicconditions_dynamic_raw'] = $this->safe_get_setting_for_display( $display_element, 'dynamicconditions_dynamic' );
        $settings['__dynamic__'] = $this->safe_get_raw_setting( $element, '__dynamic__' );
        $prevent_date_parsing = $this->safe_get_setting_for_display( $display_element, 'dynamicconditions_prevent_date_parsing' );
        $settings['preventDateParsing'] = $prevent_date_parsing;

        if ( empty( $prevent_date_parsing ) ) {
            add_filter( 'date_i18n', [ $this->date_helper, 'filter_date_i18n' ], 10, 4 );
            add_filter( 'get_the_date', [ $this->date_helper, 'filter_post_date' ], 10, 3 );
            add_filter( 'get_the_modified_date', [ $this->date_helper, 'filter_post_date' ], 10, 3 );
        }

        try {
            foreach ( $fields as $field ) {
                if ( 'dynamicconditions_prevent_date_parsing' === $field ) {
                    $settings[ $field ] = $prevent_date_parsing;
                    continue;
                }
                $settings[ $field ] = $this->safe_get_setting_for_display( $display_element, $field );
            }
        } finally {
            if ( empty( $prevent_date_parsing ) ) {
                remove_filter( 'date_i18n', [ $this->date_helper, 'filter_date_i18n' ], 10 );
                remove_filter( 'get_the_date', [ $this->date_helper, 'filter_post_date' ], 10 );
                remove_filter( 'get_the_modified_date', [ $this->date_helper, 'filter_post_date' ], 10 );
            }
        }

        $tag_data = $this->get_dynamic_tag_data( $settings );
        $settings['dynamicConditionsData'] = [
            'id'          => $key,
            'type'        => method_exists( $element, 'get_type' ) ? (string) $element->get_type() : '',
            'name'        => method_exists( $element, 'get_name' ) ? (string) $element->get_name() : '',
            'selectedTag' => $tag_data['selectedTag'],
            'tagData'     => $tag_data['tagData'],
            'tagKey'      => $tag_data['tagKey'],
        ];

        $this->convert_acf_date( $settings, $tag_data );
        $this->element_settings[ $key ] = $settings;
        return $settings;
    }

    private function safe_get_setting_for_display( $element, string $key ) {
        if ( ! is_object( $element ) || ! method_exists( $element, 'get_settings_for_display' ) ) {
            return null;
        }
        try {
            return $element->get_settings_for_display( $key );
        } catch ( \Throwable $e ) {
            return null;
        }
    }

    private function safe_get_raw_setting( $element, string $key ) {
        if ( is_object( $element ) && method_exists( $element, 'get_settings' ) ) {
            try {
                return $element->get_settings( $key );
            } catch ( \Throwable $e ) {
                // Fall through.
            }
        }
        return $this->safe_get_setting_for_display( $element, $key );
    }

    private function get_dynamic_tag_data( array $settings ): array {
        $selected_tag = null;
        $tag_data = [];
        $tag_key = null;

        $dynamic_container = isset( $settings['__dynamic__'] ) && is_array( $settings['__dynamic__'] )
            ? $settings['__dynamic__']
            : [];
        $tag = isset( $dynamic_container['dynamicconditions_dynamic'] )
            ? $dynamic_container['dynamicconditions_dynamic']
            : null;

        if ( is_string( $tag ) && $tag !== '' ) {
            if ( preg_match( '/\bname="([^"]+)"/', $tag, $match ) ) {
                $selected_tag = sanitize_key( $match[1] );
            } else {
                // Preserve the fact that a dynamic binding exists even if Elementor changes its serialization.
                $selected_tag = 'dynamic';
            }

            if ( 0 === strpos( (string) $selected_tag, 'acf-' ) && function_exists( 'get_field_object' ) ) {
                if ( preg_match( '/\bsettings="([^"]+)"/', $tag, $settings_match ) ) {
                    $decoded = json_decode( urldecode( $settings_match[1] ), true );
                    if ( is_array( $decoded ) && ! empty( $decoded['key'] ) && is_string( $decoded['key'] ) ) {
                        $tag_key = $decoded['key'];
                        $field_key = explode( ':', $tag_key )[0];
                        $field = get_field_object( $field_key );
                        $tag_data = is_array( $field ) ? $field : [];
                    }
                }
            }
        } elseif ( is_array( $tag ) && ! empty( $tag ) ) {
            $selected_tag = ! empty( $tag['name'] ) && is_scalar( $tag['name'] )
                ? sanitize_key( (string) $tag['name'] )
                : 'dynamic';
        }

        if ( null === $selected_tag ) {
            $static = isset( $settings['dynamicconditions_dynamic'] ) ? $settings['dynamicconditions_dynamic'] : null;
            if ( is_array( $static ) ) {
                if ( ! empty( $static['url'] ) || ! empty( $static['id'] ) ) {
                    $selected_tag = 'static';
                }
            } elseif ( null !== $static && '' !== $static ) {
                $selected_tag = 'static';
            }
        }

        return [
            'selectedTag' => $selected_tag,
            'tagData'     => $tag_data,
            'tagKey'      => $tag_key,
        ];
    }

    private function convert_acf_date( array &$settings, array $data ): void {
        if ( ! empty( $settings['preventDateParsing'] ) || empty( $data['tagKey'] ) || empty( $data['tagData'] ) ) {
            return;
        }
        if ( ! is_string( $data['selectedTag'] ) || 0 !== strpos( $data['selectedTag'], 'acf-' ) ) {
            return;
        }

        $field = $data['tagData'];
        $allowed_types = [ 'date_time_picker', 'date_picker' ];
        if ( empty( $field['type'] ) || ! in_array( trim( (string) $field['type'] ), $allowed_types, true ) ) {
            return;
        }
        if ( empty( $field['value'] ) || empty( $field['return_format'] ) || ! is_scalar( $field['value'] ) ) {
            return;
        }

        $date = \DateTime::createFromFormat(
            (string) $field['return_format'],
            (string) Date_Helper::untranslate_date( (string) $field['value'] ),
            wp_timezone()
        );
        if ( ! $date ) {
            return;
        }
        if ( 'date_picker' === $field['type'] ) {
            $date->setTime( 0, 0, 0 );
        }
        $settings['dynamicconditions_dynamic'] = $date->getTimestamp();
    }

    private function loop_values( array $settings ): bool {
        $dynamic_values = self::check_empty( $settings, 'dynamicconditions_dynamic' );

        // A media-like associative array represents one value. A list represents many values.
        if ( is_array( $dynamic_values ) && ( isset( $dynamic_values['id'] ) || isset( $dynamic_values['url'] ) ) ) {
            $dynamic_values = [ $dynamic_values ];
        } elseif ( ! is_array( $dynamic_values ) ) {
            $dynamic_values = [ $dynamic_values ];
        }

        $compare_type = (string) self::check_empty( $settings, 'dynamicconditions_type', 'default' );
        [ $check_value, $check_value2 ] = $this->get_check_value( $compare_type, $settings );

        $condition = false;
        $debug_values = [];

        foreach ( $dynamic_values as $dynamic_value ) {
            $normalized = $this->normalize_dynamic_value( $dynamic_value );
            if ( null === $normalized && ! is_null( $dynamic_value ) ) {
                continue;
            }

            if ( ! empty( $settings['dynamicconditions_parse_shortcodes'] ) && is_string( $normalized ) ) {
                $normalized = do_shortcode( $normalized );
            }

            $normalized = $this->parse_dynamic_tag_value( $normalized, $compare_type );
            $debug_values[] = $normalized;

            [ $condition, $break_true, $break_false ] = $this->compare_values(
                (string) self::check_empty( $settings, 'dynamicconditions_condition', '' ),
                $normalized,
                $check_value,
                $check_value2
            );

            if ( $break_true && $condition ) {
                break;
            }
            if ( $break_false && ! $condition ) {
                break;
            }
        }

        $this->render_debug_info( $settings, $debug_values, $check_value, $check_value2, $condition );
        return (bool) $condition;
    }

    private function normalize_dynamic_value( $value ) {
        if ( is_array( $value ) ) {
            if ( ! empty( $value['id'] ) ) {
                $url = wp_get_attachment_url( absint( $value['id'] ) );
                return $url ?: '';
            }
            if ( isset( $value['url'] ) && is_scalar( $value['url'] ) ) {
                return (string) $value['url'];
            }
            return null;
        }
        if ( is_object( $value ) ) {
            return method_exists( $value, '__toString' ) ? (string) $value : null;
        }
        if ( is_bool( $value ) ) {
            return $value ? '1' : '';
        }
        return $value;
    }

    private function compare_values( string $compare, $dynamic_value, $check_value, $check_value2 ): array {
        $break_true = false;
        $break_false = false;
        $condition = false;

        if ( null === $dynamic_value ) {
            $dynamic_value = '';
        }

        switch ( $compare ) {
            case 'equal':
                $condition = ( $check_value == $dynamic_value ); // Intentionally loose for backward compatibility.
                $break_true = true;
                break;

            case 'not_equal':
                $condition = ( $check_value != $dynamic_value ); // Intentionally loose for backward compatibility.
                $break_false = true;
                break;

            case 'contains':
                if ( ! empty( $check_value ) ) {
                    $condition = false !== strpos( $this->string_value( $dynamic_value ), $this->string_value( $check_value ) );
                    $break_true = true;
                }
                break;

            case 'not_contains':
                if ( ! empty( $check_value ) ) {
                    $condition = false === strpos( $this->string_value( $dynamic_value ), $this->string_value( $check_value ) );
                    $break_false = true;
                }
                break;

            case 'empty':
                $condition = empty( $dynamic_value );
                $break_false = true;
                break;

            case 'not_empty':
                $condition = ! empty( $dynamic_value );
                $break_true = true;
                break;

            case 'less':
                if ( is_numeric( $dynamic_value ) ) {
                    $condition = $dynamic_value < $check_value;
                } else {
                    $condition = strlen( $this->string_value( $dynamic_value ) ) < strlen( $this->string_value( $check_value ) );
                }
                $break_true = true;
                break;

            case 'greater':
                if ( is_numeric( $dynamic_value ) ) {
                    $condition = $dynamic_value > $check_value;
                } else {
                    $condition = strlen( $this->string_value( $dynamic_value ) ) > strlen( $this->string_value( $check_value ) );
                }
                $break_true = true;
                break;

            case 'between':
                $condition = $dynamic_value >= $check_value && $dynamic_value <= $check_value2;
                $break_true = true;
                break;

            case 'in_array':
                $haystack = array_map( 'trim', explode( ',', $this->string_value( $check_value ) ) );
                $condition = in_array( $dynamic_value, $haystack, false );
                $break_true = true;
                break;

            case 'in_array_contains':
                $needles = array_filter(
                    array_map( 'trim', explode( ',', $this->string_value( $check_value ) ) ),
                    static function ( $value ) { return '' !== $value; }
                );
                foreach ( $needles as $needle ) {
                    if ( false !== strpos( $this->string_value( $dynamic_value ), $needle ) ) {
                        $condition = true;
                        break;
                    }
                }
                $break_true = true;
                break;
        }

        return [ (bool) $condition, $break_true, $break_false ];
    }

    private function parse_dynamic_tag_value( $value, string $compare_type ) {
        switch ( $compare_type ) {
            case 'days':
                return (int) wp_date( 'N', Date_Helper::string_to_time( $value ) );
            case 'months':
                return (int) wp_date( 'n', Date_Helper::string_to_time( $value ) );
            case 'int':
                return (int) filter_var( $this->string_value( $value ), FILTER_SANITIZE_NUMBER_INT );
            case 'strtotime':
            case 'date':
                return Date_Helper::string_to_time( $value );
            default:
                return $value;
        }
    }

    private function get_check_value( string $compare_type, array $settings ): array {
        $condition = (string) self::check_empty( $settings, 'dynamicconditions_condition', '' );
        $check_value = '';
        $check_value2 = '';

        switch ( $compare_type ) {
            case 'days':
                if ( 'in_array' === $condition ) {
                    $check_value = self::check_empty( $settings, 'dynamicconditions_day_array_value', [] );
                    $check_value = $this->parse_shortcode( $check_value, $settings );
                    $check_value = is_array( $check_value ) ? implode( ',', $check_value ) : $check_value;
                } else {
                    $check_value = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_day_value', '' ), $settings );
                }
                $check_value2 = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_day_value2', '' ), $settings );
                $check_value = Date_Helper::untranslate_date( $check_value );
                $check_value2 = Date_Helper::untranslate_date( $check_value2 );
                break;

            case 'months':
                if ( 'in_array' === $condition ) {
                    $check_value = self::check_empty( $settings, 'dynamicconditions_month_array_value', [] );
                    $check_value = $this->parse_shortcode( $check_value, $settings );
                    $check_value = is_array( $check_value ) ? implode( ',', $check_value ) : $check_value;
                } else {
                    $check_value = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_month_value', '' ), $settings );
                }
                $check_value2 = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_month_value2', '' ), $settings );
                $check_value = Date_Helper::untranslate_date( $check_value );
                $check_value2 = Date_Helper::untranslate_date( $check_value2 );
                break;

            case 'date':
                $check_value = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_date_value', '' ), $settings );
                $check_value2 = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_date_value2', '' ), $settings );
                $check_value = Date_Helper::string_to_time( $check_value );
                $check_value2 = Date_Helper::string_to_time( $check_value2 );
                break;

            case 'strtotime':
                $check_value = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_value', '' ), $settings );
                $check_value2 = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_value2', '' ), $settings );
                $check_value = Date_Helper::string_to_time( $check_value );
                $check_value2 = Date_Helper::string_to_time( $check_value2 );
                break;

            case 'int':
                $check_value = (int) filter_var( $this->string_value( self::check_empty( $settings, 'dynamicconditions_value', '' ) ), FILTER_SANITIZE_NUMBER_INT );
                $check_value2 = (int) filter_var( $this->string_value( self::check_empty( $settings, 'dynamicconditions_value2', '' ) ), FILTER_SANITIZE_NUMBER_INT );
                break;

            case 'default':
            default:
                $check_value = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_value', '' ), $settings );
                $check_value2 = $this->parse_shortcode( self::check_empty( $settings, 'dynamicconditions_value2', '' ), $settings );
                break;
        }

        return [ $check_value, $check_value2 ];
    }

    private function parse_shortcode( $value, array $settings ) {
        if ( ! is_string( $value ) || empty( $settings['dynamicconditions_parse_shortcodes'] ) ) {
            return $value;
        }
        return do_shortcode( $value );
    }

    private function render_debug_info( array $settings, array $dynamic_values, $check_value, $check_value2, bool $condition_met ): void {
        if ( empty( $settings['dynamicconditions_debug'] ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        $payload = [
            'element'       => isset( $settings['dynamicConditionsData']['id'] ) ? $settings['dynamicConditionsData']['id'] : '',
            'tag'           => isset( $settings['dynamicConditionsData']['selectedTag'] ) ? $settings['dynamicConditionsData']['selectedTag'] : '',
            'dynamic_value' => $dynamic_values,
            'condition'     => isset( $settings['dynamicconditions_condition'] ) ? $settings['dynamicconditions_condition'] : '',
            'check_value'   => $check_value,
            'check_value_2' => $check_value2,
            'condition_met' => $condition_met,
        ];

        echo '<details class="azdc-debug"><summary>' . esc_html__( 'Dynamic Conditions debug', 'dynamicconditions' ) . '</summary><pre>';
        echo esc_html( (string) wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
        echo '</pre></details>';
    }

    private function render_assets_from_hidden_content( string $content ): void {
        if ( '' === $content ) {
            return;
        }
        $links = [];
        $styles = [];
        preg_match_all( '/<link\b[^>]*\/?\s*>/i', $content, $links );
        preg_match_all( '/<style\b[^>]*>.*?<\/style>/is', $content, $styles );

        if ( ! empty( $links[0] ) ) {
            echo implode( '', $links[0] ); // Generated by Elementor; intentionally preserved for sibling widgets.
        }
        if ( ! empty( $styles[0] ) ) {
            echo implode( '', $styles[0] ); // Generated by Elementor; intentionally preserved for sibling widgets.
        }
    }

    private function render_empty_wrapper( $element, string $content ): void {
        // Keep upstream behavior where supported without relying on deprecated _render() methods.
        if ( method_exists( $element, 'before_render' ) && method_exists( $element, 'after_render' ) ) {
            try {
                $element->before_render();
                $element->after_render();
                return;
            } catch ( \Throwable $e ) {
                // Fall back to extracting the root wrapper from already-rendered markup.
            }
        }

        $without_assets = preg_replace( '/<style\b[^>]*>.*?<\/style>|<link\b[^>]*\/?\s*>/is', '', $content );
        if ( is_string( $without_assets ) && preg_match( '/^\s*(<([a-zA-Z][a-zA-Z0-9:-]*)\b[^>]*>).*<\/\2>\s*$/s', $without_assets, $match ) ) {
            echo $match[1] . '</' . esc_html( $match[2] ) . '>';
        }
    }

    private function element_key( $element ): string {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            $post_id = get_queried_object_id();
        }
        $element_id = is_object( $element ) && method_exists( $element, 'get_id' ) ? (string) $element->get_id() : spl_object_hash( $element );
        return (string) $post_id . '-' . $element_id;
    }

    private function is_element_like( $element ): bool {
        return is_object( $element ) && method_exists( $element, 'get_id' ) && method_exists( $element, 'get_settings_for_display' );
    }

    private function string_value( $value ): string {
        if ( is_string( $value ) ) {
            return $value;
        }
        if ( is_scalar( $value ) || null === $value ) {
            return (string) $value;
        }
        return '';
    }

    private function get_mode(): string {
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return '';
        }

        try {
            if ( ! empty( Elementor_Plugin::$instance->editor ) && Elementor_Plugin::$instance->editor->is_edit_mode() ) {
                return 'edit';
            }
            if ( ! empty( Elementor_Plugin::$instance->preview ) && Elementor_Plugin::$instance->preview->is_preview_mode() ) {
                return 'preview';
            }
        } catch ( \Throwable $e ) {
            // Treat an unknown context as website instead of failing rendering.
        }

        return 'website';
    }
}
