<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation;

class Shortcode {

    /**
     * Class construct.
     */
    public function __construct() {
        add_shortcode( 'dokan-request-quote', [ $this, 'render_shortcode' ] );
    }

    /**
     * Render [dokan-request-quote] shortcode.
     *
     * @since 3.6.0
     *
     * @param mixed $args
     *
     * @return false|string
     */
    public function render_shortcode( $args ) {
        $quote_session    = Session::init();
        $quotes           = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );
        $quote_totals     = [
            '_subtotal'      => 0,
            '_offered_total' => 0,
            '_tax_total'     => 0,
            '_total'         => 0,
        ];
        if ( ! empty( $quotes ) ) {
            foreach ( $quotes as $quote_item_key => $quote_item ) {
                if ( isset( $quote_item['quantity'] ) && empty( $quote_item['quantity'] ) ) {
                    unset( $quotes[ $quote_item_key ] );
                }

                if ( ! isset( $quote_item['data'] ) ) {
                    unset( $quotes[ $quote_item_key ] );
                }
            }

            $quote_totals = ( new Helper() )->get_calculated_totals( $quote_session->get( DOKAN_SESSION_QUOTE_KEY ) );
        }
        ob_start();
        dokan_get_template_part(
            'dokan-request-quote-shortcode-page', '', [
                'request_quote_shortcode' => true,
                'quotes'                  => $quotes,
                'quote_totals'            => $quote_totals,
            ]
        );

        return ob_get_clean();
    }

}
