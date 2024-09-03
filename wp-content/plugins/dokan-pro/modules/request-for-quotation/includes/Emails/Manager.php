<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Emails;

/**
 * Dokan email handler class
 *
 * @package Dokan
 */
class Manager {

    /**
     * Load automatically when class initiate
     */
    public function __construct() {
        //Dokan Email filters for WC Email
        add_filter( 'woocommerce_email_classes', array( $this, 'load_dokan_emails' ), 35 );
    }

    /**
     * Add Dokan Email classes in WC Email
     *
     * @since 3.6.0
     *
     * @param array $wc_emails
     *
     * @return array $wc_emails
     */
    public function load_dokan_emails( $wc_emails ) {
        $wc_emails['Dokan_Email_New_Request_Quote'] = new NewQuote();
        $wc_emails['Dokan_Email_Update_Request_Quote'] = new UpdateQuote();

        return $wc_emails;
    }
}
