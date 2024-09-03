<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Frontend;

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;

defined( 'ABSPATH' ) || exit();

/**
 * Verification Setup Wizard.
 *
 * @since 3.11.1
 */
class SetupWizard {

    /**
     * SetupWizard class constructor.
     *
     * @since 3.11.1
     */
    public function __construct() {
        add_filter( 'dokan_seller_wizard_steps', [ $this, 'add_wizard_steps' ] );
        add_action( 'dokan_setup_wizard_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'dokan_setup_wizard_enqueue_scripts', 'wp_print_media_templates' );
        add_action( 'dokan_seller_wizard_after_store_setup_form', [ $this, 'make_store_step_required' ] );
    }

    /**
     * Adds a wizard step.
     *
     * @since 3.11.1
     *
     * @param array $steps Step array.
     *
     * @return array
     */
    public function add_wizard_steps( array $steps ): array {
        $offset = array_search( 'next_steps', array_keys( $steps ) );

        $verification_step = [
            'verifications' => [
                'name'    => __( 'Verifications', 'dokan' ),
                'view'    => [ $this, 'dokan_setup_verifications_view' ],
                'handler' => '',
            ],
        ];

        // todo: replace with existing method `dokan_array_insert_after`
        return array_merge
        (
            array_slice( $steps, 0, $offset ),
            $verification_step,
            array_slice( $steps, $offset, null )
        );
    }

    /**
     * Verification template views.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function dokan_setup_verifications_view() {
        $next = add_query_arg(
            [
                'step'            => 'next_steps',
                '_admin_sw_nonce' => wp_create_nonce( 'dokan_admin_setup_wizard_nonce' ),
            ]
        );
        dokan_get_template_part(
            'vendor-verification/setup-wizard', '', [
                'is_vendor_verification' => true,
                'next_step_link'         => $next,
            ]
        );
    }

    /**
     * Enqueue style and script for setup wizard.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function enqueue_scripts() {
        /**
         * @var \WeDevs\Dokan\Assets $dokan_assets Dokan Assets class.
         */
        $dokan_assets = dokan()->scripts;

        add_filter( 'dokan_force_load_extra_args', function ( $force ) { return true; } );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        $dokan_assets->enqueue_front_scripts();
        $dokan_assets->load_dokan_global_scripts();
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'dokan-tooltip' );
        wp_enqueue_script( 'dokan-form-validate' );
        wp_enqueue_script( 'dokan-script' );
        dokan_pro()->module->vendor_verification->assets->enqueue_scripts();
    }

    /**
     * Make store step required.
     * This is required for the vendor verification setup wizard.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function make_store_step_required() {
        $verification_method_address = ( new VerificationMethod() )
            ->query( [
            'kind' => VerificationMethod::TYPE_ADDRESS,
            'limit' => 1,
        ] );

        $verification_method_address = reset( $verification_method_address );

        if ( ! $verification_method_address || ! $verification_method_address->is_enabled() || !  $verification_method_address->is_required() ) {
            return;
        }

        ?>
        <script type="text/javascript">
            (function($){
                $(function() {
                    let skip_button = $('.store-step-skip-btn');
                    skip_button.hide();
                });
            })(jQuery);
        </script>
        <?php
    }
}
