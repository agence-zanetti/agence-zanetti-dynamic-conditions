<?php
namespace AgenceZanetti\DynamicConditions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Date_Helper {
    public function filter_date_i18n( $formatted_date, $requested_format, $unix_timestamp, $gmt ) {
        return $unix_timestamp;
    }

    public function filter_post_date( $the_time, $date_format, $post ) {
        if ( empty( $date_format ) ) {
            $date_format = (string) get_option( 'date_format' );
        }

        $date = \DateTime::createFromFormat( $date_format, self::untranslate_date( (string) $the_time ) );
        if ( ! $date ) {
            $date = \DateTime::createFromFormat( $date_format, (string) $the_time );
        }

        return $date ? $date->getTimestamp() : $the_time;
    }

    /**
     * Convert a date-like value to a timestamp while preserving an existing timestamp.
     */
    public static function string_to_time( $value = '' ): int {
        if ( self::is_timestamp( $value ) ) {
            return (int) $value;
        }

        if ( ! is_scalar( $value ) || $value === '' ) {
            return 0;
        }

        $value = self::untranslate_date( (string) $value );

        // ACF date pickers commonly use Ymd internally. strtotime() is inconsistent with it.
        if ( preg_match( '/^\d{8}$/', $value ) ) {
            $date = \DateTimeImmutable::createFromFormat( '!Ymd', $value, wp_timezone() );
            if ( $date instanceof \DateTimeImmutable ) {
                return $date->getTimestamp();
            }
        }

        $timestamp = strtotime( $value, time() );
        return false === $timestamp ? 0 : (int) $timestamp;
    }

    public static function is_timestamp( $value ): bool {
        if ( ! is_numeric( $value ) ) {
            return false;
        }

        $string = (string) $value;
        if ( $string === '' || (float) $value < 0 ) {
            return false;
        }

        try {
            new \DateTimeImmutable( '@' . $string );
        } catch ( \Throwable $e ) {
            return false;
        }

        return true;
    }

    public static function untranslate_date( $value = '' ) {
        if ( ! is_string( $value ) || $value === '' ) {
            return $value;
        }

        return str_ireplace(
            array_merge( array_values( self::get_days_translated() ), array_values( self::get_months_translated() ) ),
            array_merge( array_values( self::get_days() ), array_values( self::get_months() ) ),
            $value
        );
    }

    public static function get_months_translated(): array {
        return [
            1  => __( 'January' ),
            2  => __( 'February' ),
            3  => __( 'March' ),
            4  => __( 'April' ),
            5  => __( 'May' ),
            6  => __( 'June' ),
            7  => __( 'July' ),
            8  => __( 'August' ),
            9  => __( 'September' ),
            10 => __( 'October' ),
            11 => __( 'November' ),
            12 => __( 'December' ),
        ];
    }

    public static function get_days_translated(): array {
        return [
            1 => __( 'Monday' ),
            2 => __( 'Tuesday' ),
            3 => __( 'Wednesday' ),
            4 => __( 'Thursday' ),
            5 => __( 'Friday' ),
            6 => __( 'Saturday' ),
            7 => __( 'Sunday' ),
        ];
    }

    private static function get_months(): array {
        return [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }

    private static function get_days(): array {
        return [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
            5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
        ];
    }
}
