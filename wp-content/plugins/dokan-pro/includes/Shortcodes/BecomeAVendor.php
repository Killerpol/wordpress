<?php

namespace WeDevs\DokanPro\Shortcodes;

use WeDevs\Dokan\Abstracts\DokanShortcode;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BecomeAVendor extends DokanShortcode {
    /**
     * Shortcode name
     *
     * @since 3.7.25
     *
     * @var string Shortcode name
     */
    protected $shortcode = 'dokan-customer-migration';

    /**
     * Render best selling products
     *
     * @since 3.7.25
     *
     * @param array $atts
     *
     * @return string
     */
    public function render_shortcode( $atts ) {
        ob_start();
        dokan()->frontend_manager->become_a_vendor->load_customer_to_vendor_update_template();
        wp_enqueue_script( 'dokan-vendor-registration' );
        return ob_get_clean();
    }
}
