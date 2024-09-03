<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Payments Blocks integration
 *
 * @since 1.0.3
 */
final class LWC_WhatsAppOrder_Gateway_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var LWC_WhatsAppOrder_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'lwc-whatsapp-order';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option("woocommerce_lwc-whatsapp-order_settings", [] );
		$this->gateway  = new LWC_WhatsAppOrder_Gateway();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_asset_path = $this->gateway->plugin_abspath()  .'/assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array(
				'dependencies' => array(),
				'version'      => '3.0.0'
			);

            wp_register_script(
                'lw-wc-whatsapp-order-blocks',
                $this->gateway->this_plugin_dir_url('assets/js/frontend/blocks.js'),
                $script_asset[ 'dependencies' ],
                $script_asset[ 'version' ],
                true
            );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'lw-wc-whatsapp-order-blocks', 'lwc-whatsapp-order',  $this->gateway->plugin_abspath() . 'languages/' );
		}
        // wp_enqueue_script('lw-wc-whatsapp-order-blocks');
		return [ 'lw-wc-whatsapp-order-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
		];
	}
}