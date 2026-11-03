<?php
namespace AgenceZanetti\DynamicConditions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Updates from the public GitHub repository.
 *
 * WordPress (5.8+) calls the "update_plugins_{hostname}" filter for plugins whose
 * Update URI points to a non-wordpress.org host. The update then appears in the
 * standard Updates screen, in the plugins list and in MainWP.
 */
final class Updater {
    const REPO        = 'agence-zanetti/agence-zanetti-dynamic-conditions';
    const SLUG        = 'agence-zanetti-dynamic-conditions';
    const ASSET_NAME  = 'agence-zanetti-dynamic-conditions.zip';
    const CACHE_KEY   = 'azdc_github_release';
    const CACHE_OK    = 43200; // 12 h
    const CACHE_ERROR = 3600;  // 1 h

    /** @var self|null */
    private static $instance = null;

    /** @var string */
    private $basename;

    /** @var string */
    private $version;

    public static function init( string $plugin_file, string $version ): void {
        if ( null === self::$instance ) {
            self::$instance = new self( $plugin_file, $version );
        }
    }

    private function __construct( string $plugin_file, string $version ) {
        $this->basename = plugin_basename( $plugin_file );
        $this->version  = $version;

        add_filter( 'update_plugins_github.com', [ $this, 'filter_update' ], 10, 3 );
        add_filter( 'plugins_api', [ $this, 'filter_plugin_info' ], 20, 3 );
        add_filter( 'upgrader_source_selection', [ $this, 'fix_source_dir' ], 10, 4 );
        add_action( 'upgrader_process_complete', [ $this, 'clear_cache' ], 10, 0 );
    }

    public function clear_cache(): void {
        delete_site_transient( self::CACHE_KEY );
    }

    /**
     * Latest published release, cached.
     *
     * @return array|null
     */
    private function get_release() {
        // "Check again" button in Dashboard > Updates bypasses the cache.
        if ( is_admin() && isset( $_GET['force-check'] ) && current_user_can( 'update_plugins' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $this->clear_cache();
        }

        $cached = get_site_transient( self::CACHE_KEY );
        if ( is_array( $cached ) ) {
            return empty( $cached['error'] ) ? $cached : null;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . self::REPO . '/releases/latest',
            [
                'timeout' => 10,
                'headers' => [
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'Agence-Zanetti-Dynamic-Conditions/' . $this->version . '; ' . home_url( '/' ),
                ],
            ]
        );

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            set_site_transient( self::CACHE_KEY, [ 'error' => true ], self::CACHE_ERROR );
            return null;
        }

        $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || empty( $data['tag_name'] ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) ) {
            set_site_transient( self::CACHE_KEY, [ 'error' => true ], self::CACHE_ERROR );
            return null;
        }

        $package = '';
        foreach ( (array) ( $data['assets'] ?? [] ) as $asset ) {
            if ( is_array( $asset ) && ( $asset['name'] ?? '' ) === self::ASSET_NAME ) {
                $package = (string) ( $asset['browser_download_url'] ?? '' );
                break;
            }
        }

        // Only accept a package published in this repository.
        $expected_prefix = 'https://github.com/' . self::REPO . '/releases/download/';
        $version         = ltrim( (string) $data['tag_name'], 'vV' );

        if ( '' === $package || 0 !== strpos( $package, $expected_prefix ) || ! preg_match( '/^\d+(\.\d+){1,3}$/', $version ) ) {
            set_site_transient( self::CACHE_KEY, [ 'error' => true ], self::CACHE_ERROR );
            return null;
        }

        $release = [
            'version'   => $version,
            'package'   => $package,
            'url'       => (string) ( $data['html_url'] ?? 'https://github.com/' . self::REPO ),
            'notes'     => (string) ( $data['body'] ?? '' ),
            'published' => (string) ( $data['published_at'] ?? '' ),
        ];

        set_site_transient( self::CACHE_KEY, $release, self::CACHE_OK );
        return $release;
    }

    /**
     * @param array|false $update
     * @param array       $plugin_data
     * @param string      $plugin_file
     * @return array|false
     */
    public function filter_update( $update, $plugin_data, $plugin_file ) {
        if ( $plugin_file !== $this->basename ) {
            return $update;
        }

        $release = $this->get_release();
        if ( ! $release ) {
            return $update;
        }

        return [
            'slug'         => self::SLUG,
            'version'      => $release['version'],
            'url'          => $release['url'],
            'package'      => $release['package'],
            'requires'     => '6.5',
            'requires_php' => '7.4',
        ];
    }

    /**
     * Content of the "View details" window.
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object|array
     */
    public function filter_plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || ! is_object( $args ) || ( $args->slug ?? '' ) !== self::SLUG ) {
            return $result;
        }

        $release = $this->get_release();
        if ( ! $release ) {
            return $result;
        }

        $notes = '' !== trim( $release['notes'] )
            ? nl2br( esc_html( $release['notes'] ) )
            : esc_html__( 'See the release page on GitHub.', 'dynamicconditions' );

        return (object) [
            'name'          => 'Agence Zanetti - Dynamic Conditions',
            'slug'          => self::SLUG,
            'version'       => $release['version'],
            'author'        => 'Agence Zanetti',
            'homepage'      => 'https://github.com/' . self::REPO,
            'requires'      => '6.5',
            'requires_php'  => '7.4',
            'last_updated'  => $release['published'],
            'download_link' => $release['package'],
            'sections'      => [
                'description' => esc_html__( 'Maintained fork of Dynamic Conditions for Elementor.', 'dynamicconditions' ),
                'changelog'   => $notes,
            ],
        ];
    }

    /**
     * Safety net: make sure the extracted folder keeps the plugin folder name.
     *
     * @param string $source
     * @param string $remote_source
     * @param object $upgrader
     * @param array  $hook_extra
     * @return string
     */
    public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = [] ) {
        if ( ! is_array( $hook_extra ) || ( $hook_extra['plugin'] ?? '' ) !== $this->basename ) {
            return $source;
        }

        $expected = trailingslashit( $remote_source ) . dirname( $this->basename ) . '/';
        if ( trailingslashit( $source ) === $expected ) {
            return $source;
        }

        global $wp_filesystem;
        if ( $wp_filesystem && $wp_filesystem->move( $source, $expected, true ) ) {
            return $expected;
        }

        return $source;
    }
}
