<?php

namespace WeDevs\DokanPro\Modules\VendorVerification;

defined( 'ABSPATH' ) || exit;

/**
 * Assets Class.
 *
 * @since 3.11.1
 */
class Assets {
    /**
     * Class Constructor.
     *
     * @since 3.11.1
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_scripts' ], 99 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Enqueue scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @since unknown
     * @since 3.11.1 Migrated to Separate class.
     *
     * @uses  wp_enqueue_script()
     * @uses  wp_localize_script()
     * @uses  wp_enqueue_style
     */
    public function enqueue_scripts() {
        global $wp;

        if (
            (
                isset( $wp->query_vars['settings'] )
                && 'verification' === $wp->query_vars['settings']
            )
            || (
                isset( $_GET['page'] )
                && isset( $_GET['step'] )
                && 'dokan-seller-setup' === sanitize_text_field( wp_unslash( $_GET['page'] ) )
                && 'verifications' === sanitize_text_field( wp_unslash( $_GET['step'] ) )
            )
        ) {
            $data = [
                'upload_title' => __( 'Upload Proof', 'dokan' ),
                'insert_title' => __( 'Insert Proof', 'dokan' ),
            ];

            wp_enqueue_style( 'dokan-verification-styles' );
            wp_enqueue_script( 'dokan-verification-scripts' );
            wp_localize_script( 'dokan-verification-scripts', 'verify_data', $data );

            wp_enqueue_script( 'wc-country-select' );
            wp_enqueue_script( 'dokan-form-validate' );
        }
    }

    /**
     * Register scripts and styles.
     *
     * Allows plugin assets to be loaded.
     *
     * @since unknown
     * @since 3.11.1 Migrated to Separate class.
     *
     * @uses  wp_register_style()
     * @uses  wp_register_script()
     */
    public function register_scripts() {
        [ $suffix, $script_version ] = dokan_get_script_suffix_and_version();

        wp_register_style( 'dokan-verification-styles', DOKAN_VERFICATION_PLUGIN_ASSEST . '/css/style.css', [], $script_version );
        wp_register_script( 'dokan-verification-scripts', DOKAN_VERFICATION_PLUGIN_ASSEST . '/js/script.js', [ 'jquery' ], $script_version, true );
    }

}
