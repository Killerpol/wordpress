<?php

/**
 * Plugin Name:  LWC-WhatsApp-Order - Order via WhatsApp
 * Plugin URI: https://livelyworks.net
 * Description: LWC-WhatsApp-Order - Order via WhatsApp
 * Author: livelyworks
 * Author URI: https://livelyworks.net
 * Version: 3.0.1
 * Text Domain:       lwc-whatsapp-order
 * Domain Path:       /languages
 */

use Automattic\Jetpack\Constants;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'whatsapp_order_init_gateway_class', 0);

/**
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'whatsapp_order_add_gateway_class');
function whatsapp_order_add_gateway_class($gateways)
{
    $gateways[] = 'LWC_WhatsAppOrder_Gateway'; // your class name is here
    return $gateways;
}


// Registers WooCommerce Blocks integration.
add_action('woocommerce_blocks_loaded', 'woocommerce_whatsapp_order_block_support');

/**
     * Registers WooCommerce Blocks integration.
     *
     */
function woocommerce_whatsapp_order_block_support()
{
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once  'blocks/class-lw-wc-whatsapp-order-blocks.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new LWC_WhatsAppOrder_Gateway_Blocks_Support());
            }
        );
    }
}

function whatsapp_order_init_gateway_class()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    class LWC_WhatsAppOrder_Gateway extends WC_Payment_Gateway
    {
        protected $instructions;
        protected $enable_for_methods;
        protected $enable_for_virtual;
        protected $whatsapp_number;
        protected $disable_other_gateways_at_checkout;
        protected $send_payment_link;
        protected $send_view_order_link;
        protected $enabled_on_thank_you_page;
        protected $whatsapp_redirect_method;
        protected $send_order_meta_data;
        protected $ignore_order_meta_fields;
        protected $ignore_underscore_order_meta;
        protected $use_emoticons_in_message;
        protected $whatsapp_api;

        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {
            // Setup general properties.
            $this->setup_properties();

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Get settings.
            $this->title              = $this->get_option('title');
            $this->description        = $this->get_option('description');
            $this->instructions       = $this->get_option('instructions');
            $this->enable_for_methods = $this->get_option('enable_for_methods', array());
            $this->enable_for_virtual = $this->get_option('enable_for_virtual', 'yes') === 'yes';
            $this->whatsapp_number    = $this->get_option('whatsapp_number');
            $this->disable_other_gateways_at_checkout = $this->get_option('disable_other_gateways_at_checkout', 'yes') === 'yes';
            $this->send_payment_link = $this->get_option('send_payment_link', 'yes') === 'yes';
            $this->send_view_order_link = $this->get_option('send_view_order_link', 'yes') === 'yes';
            $this->enabled_on_thank_you_page = $this->get_option('enabled_on_thank_you_page');
            $this->whatsapp_redirect_method = $this->get_option('whatsapp_redirect_method');
            $this->send_order_meta_data = $this->get_option('send_order_meta_data', 'yes') === 'yes';
            $this->ignore_order_meta_fields   = $this->get_option('ignore_order_meta_fields');
            $this->ignore_underscore_order_meta   = $this->get_option('ignore_underscore_order_meta', 'yes') === 'yes';
            $this->use_emoticons_in_message   = $this->get_option('use_emoticons_in_message', 'yes') === 'yes';

            // internal vars
            $this->whatsapp_api = 'https://api.whatsapp.com/';
            if(wp_is_mobile()) {
                $this->whatsapp_api = 'whatsapp://';
            }

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            add_filter('woocommerce_endpoint_order-received_title', array($this, 'thank_you_title_update'), 20, 2);
            add_filter('woocommerce_thankyou_order_received_text', array($this, 'thank_you_text_update'), 30, 2);
            add_filter('woocommerce_payment_complete_order_status', array($this, 'change_payment_complete_order_status'), 10, 3);
            add_action('admin_notices', array($this, 'number_missing_notice'));

            add_filter('woocommerce_available_payment_gateways', array($this, 'manage_payment_gateways_availability'), 100, 1);
            // Customer Emails.
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
            // load text domains
            add_action('init', array($this, 'load_textdomain'));
            // admin css
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts_and_styles'));
            //  css
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_and_styles'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_settings_link'));
        }

        /**
         * Setup general properties for the gateway.
         */
        protected function setup_properties()
        {
            $this->id                 = 'lwc-whatsapp-order';
            $this->icon               = apply_filters('woocommerce_whatsapp_icon', plugin_dir_url(__FILE__) . '/assets/imgs/whatsapp-icon.png');
            $this->method_title       = __('WhatsApp Order', 'lwc-whatsapp-order');
            $this->method_description = __('Send order via WhatsApp', 'lwc-whatsapp-order');
            $this->has_fields         = false;
        }

        /**
         * Initialize Gateway Settings Form Fields.
         */
        public function init_form_fields()
        {
            $this->load_textdomain();
            $this->form_fields = array(
                'enabled'            => array(
                    'title'       => __('Enable/Disable as Payment Method', 'lwc-whatsapp-order'),
                    'label'       => __('Enable WhatsApp Order at Payment Method', 'lwc-whatsapp-order'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no',
                ),
                'title'              => array(
                    'title'       => __('Title', 'lwc-whatsapp-order'),
                    'type'        => 'text',
                    'description' => __('Method title that the customer will see on your checkout.', 'lwc-whatsapp-order'),
                    'default'     => 'WhatsApp Order',
                    'desc_tip'    => false,
                ),
                'description'        => array(
                    'title'       => __('Description', 'lwc-whatsapp-order'),
                    'type'        => 'textarea',
                    'description' => __('Method description that the customer will see on your website.', 'lwc-whatsapp-order'),
                    'default'     => __('Send your Order via WhatsApp', 'lwc-whatsapp-order'),
                    'desc_tip'    => false,
                ),
                'whatsapp_number' => array(
                    'title'       => __('WhatsApp Number', 'lwc-whatsapp-order'),
                    'type'        => 'number',
                    'description' => __('Your WhatsApp Mobile Number where you want to receive orders. include your country code without leading 0 and +', 'lwc-whatsapp-order'),
                    'default'     => '',
                    'desc_tip'    => false,
                    'class'       => 'lw-number-field'
                ),
                'whatsapp_redirect_method'            => array(
                    'title'       => __('Auto Redirect', 'lwc-whatsapp-order'),
                    'label'       => __('Enable WhatsApp Order link on Thank you page or auto redirect user to WhatsApp', 'lwc-whatsapp-order'),
                    'type'        => 'select',
                    'description' => __('Do you want user to auto redirect to WhatsApp or want to show link to send order.', 'lwc-whatsapp-order'),
                    'default'     => 'auto_redirect_to_whatsapp',
                    'class'       => 'wc-enhanced-select',
                    'options'     => [
                        'auto_redirect_to_whatsapp' => __('Auto Redirect User to Send Order via WhatsApp', 'lwc-whatsapp-order'),
                        'whatsapp_link' => __('Show the link to Send Order via WhatsApp', 'lwc-whatsapp-order'),
                    ]
                ),
                'enabled_on_thank_you_page'            => array(
                    'title'       => __('Enable on Thank you page for other Payment Gateways', 'lwc-whatsapp-order'),
                    'label'       => __('Enable WhatsApp Order button on Order Thank you page for other payment gateways', 'lwc-whatsapp-order'),
                    'type'        => 'select',
                    'description' => __('Choose whatever you want to have send order via WhatsApp for other payment methods on thank you page.', 'lwc-whatsapp-order'),
                    'default'     => 'whatsapp_link',
                    'class'       => 'wc-enhanced-select',
                    'options'     => [
                        'no' => __('No', 'lwc-whatsapp-order'),
                        'whatsapp_link' => __('Show the link to Send Order via WhatsApp', 'lwc-whatsapp-order'),
                        'auto_redirect_to_whatsapp' => __('Auto Redirect User to Send Order via WhatsApp', 'lwc-whatsapp-order'),
                    ]
                ),
                'instructions'       => array(
                    'title'       => __('Instructions', 'lwc-whatsapp-order'),
                    'type'        => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page.', 'lwc-whatsapp-order'),
                    'default'     => '',
                    'desc_tip'    => false,
                ),
                'enable_for_methods' => array(
                    'title'             => __('Enable for shipping methods', 'lwc-whatsapp-order'),
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 400px;',
                    'default'           => '',
                    'description'       => __('If WhatsApp Order is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'lwc-whatsapp-order'),
                    'options'           => $this->load_shipping_method_options(),
                    'desc_tip'          => false,
                    'custom_attributes' => array(
                        'data-placeholder' => __('Select shipping methods', 'lwc-whatsapp-order'),
                    ),
                ),
                'enable_for_virtual' => array(
                    'title'   => __('Accept for virtual orders', 'lwc-whatsapp-order'),
                    'label'   => __('Accept WhatsApp Order if the order is virtual', 'lwc-whatsapp-order'),
                    'type'    => 'checkbox',
                    'default' => 'yes',
                ),
                'disable_other_gateways_at_checkout' => array(
                    'title'   => __('Disable Other Payment Gateways at Checkout', 'lwc-whatsapp-order'),
                    'label'   => __('Check if you want to disable other payment gateways at checkout', 'lwc-whatsapp-order'),
                    'description'       => __('It will disable all other payment gateways at checkout but on Order Pay page it will be enabled.', 'lwc-whatsapp-order'),
                    'type'    => 'checkbox',
                    'default' => 'yes',
                    'desc_tip'          => false,
                ),
                'send_payment_link' => array(
                    'title'   => __('Send Payment/Pay Now link', 'lwc-whatsapp-order'),
                    'label'   => __('Check if you want to send payment link in order message', 'lwc-whatsapp-order'),
                    'description'       => __('User will be able to make payment using available payment gateways via the provided link.', 'lwc-whatsapp-order'),
                    'type'    => 'checkbox',
                    'default' => 'yes',
                ),
                'send_view_order_link' => array(
                    'title'   => __('Send View Order link', 'lwc-whatsapp-order'),
                    'label'   => __('Check if you want to send view order link in order message', 'lwc-whatsapp-order'),
                    'description'       => __('User will be able to see the order on website via the provided link.', 'lwc-whatsapp-order'),
                    'type'    => 'checkbox',
                    'default' => 'no',
                ),
                'send_order_meta_data' => array(
                    'title'   => __('Send Order Meta Data', 'lwc-whatsapp-order'),
                    'label'   => __('Send Order Meta Data'),
                    'description'       => __("If you are using any plugin for custom fields for checkout etc, you may need to enable it, so that data should be in WhatsApp Message", 'lwc-whatsapp-order'),
                    'type'    => 'checkbox',
                    'default' => 'no',
                ),
                'ignore_order_meta_fields' => array(
                    'title'       => __('Ignore Order Meta Fields', 'lwc-whatsapp-order'),
                    'type'        => 'text',
                    'description' => __('Add your comma separated field names here, so it can be ignore for the WhatsApp order message. Fields starts with _ (underscore) will be ignored by default.', 'lwc-whatsapp-order'),
                    // 'default'     => __('WhatsApp Order', 'lwc-whatsapp-order'),
                    'desc_tip'    => false,
                ),
                'use_emoticons_in_message' => array(
                    'title'   => __('Use smileys/emoticons in WhatsApp Message', 'lwc-whatsapp-order'),
                    'label'   => __('Check if you want to use emoticons like 👉 in message', 'lwc-whatsapp-order'),
                    'description'       => __("If enabled it will use emoticons in WhatsApp message.", 'lwc-whatsapp-order'),
                    'type'    => 'checkbox',
                    'default' => 'yes',
                ),
                'ignore_underscore_order_meta' => array(
                    'title'   => __('Ignore Underscore (_) Order Meta Data', 'lwc-whatsapp-order'),
                    'label'   => __('Check if you want to ignore order meta data started with _', 'lwc-whatsapp-order'),
                    'description'       => __('It will ignore meta data item which keys are started with _ (underscore) from order info', 'lwc-whatsapp-order'),
                    'type'    => 'checkbox',
                    'default' => 'yes',
                ),
            );
        }

        /**
         * If whatsapp number missing show error
         */
        public function number_missing_notice()
        {
            $this->load_textdomain();
            // if not enabled
            if ($this->enabled == 'no') {
                return;
            }
            // Check required fields.
            if (!$this->whatsapp_number) {
                ?>
                <div class="woocommerce-message error">
                    <p>
                        <?php
                                        echo wp_kses_post(sprintf(
                                            __('Please enter your WhatsApp Mobile Number <a href="%s">here</a> to be able to Receive Order via WhatsApp using WhatsApp Order - WhatsApp WooCommerce Ordering plugin.', 'lwc-whatsapp-order'),
                                            admin_url('admin.php?page=wc-settings&tab=checkout&section=lwc-whatsapp-order')
                                        )); ?>
                    </p>
                </div>
<?php
            }
        }

        /**
         * Check If The Gateway Is Available For Use.
         *
         * @return bool
         */
        public function is_available()
        {
            $order          = null;
            $needs_shipping = false;

            // Test if shipping is needed first.
            if (WC()->cart && WC()->cart->needs_shipping()) {
                $needs_shipping = true;
            } elseif (is_page(wc_get_page_id('checkout')) && 0 < get_query_var('order-pay')) {
                $order_id = absint(get_query_var('order-pay'));
                $order    = wc_get_order($order_id);

                // Test if order needs shipping.
                if (0 < count($order->get_items())) {
                    foreach ($order->get_items() as $item) {
                        $_product = $item->get_product();
                        if ($_product && $_product->needs_shipping()) {
                            $needs_shipping = true;
                            break;
                        }
                    }
                }
            }

            $needs_shipping = apply_filters('woocommerce_cart_needs_shipping', $needs_shipping);

            // Virtual order, with virtual disabled.
            if (!$this->enable_for_virtual && !$needs_shipping) {
                return false;
            }

            // Only apply if all packages are being shipped via chosen method, or order is virtual.
            if (!empty($this->enable_for_methods) && $needs_shipping) {
                $order_shipping_items            = is_object($order) ? $order->get_shipping_methods() : false;
                $chosen_shipping_methods_session = WC()->session->get('chosen_shipping_methods');

                if ($order_shipping_items) {
                    $canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids($order_shipping_items);
                } else {
                    $canonical_rate_ids = $this->get_canonical_package_rate_ids($chosen_shipping_methods_session);
                }

                if (!count($this->get_matching_rates($canonical_rate_ids))) {
                    return false;
                }
            }

            return parent::is_available();
        }

        /**
         * Checks to see whether or not the admin settings are being accessed by the current request.
         *
         * @return bool
         */
        private function is_accessing_settings()
        {
            if (is_admin()) {
                // phpcs:disable WordPress.Security.NonceVerification
                if (!isset($_REQUEST['page']) || 'wc-settings' !== $_REQUEST['page']) {
                    return false;
                }
                if (!isset($_REQUEST['tab']) || 'checkout' !== $_REQUEST['tab']) {
                    return false;
                }
                if (!isset($_REQUEST['section']) || 'lwc-whatsapp-order' !== $_REQUEST['section']) {
                    return false;
                }
                // phpcs:enable WordPress.Security.NonceVerification

                return true;
            }

            if (Constants::is_true('REST_REQUEST')) {
                global $wp;
                if (isset($wp->query_vars['rest_route']) && false !== strpos($wp->query_vars['rest_route'], '/payment_gateways')) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Loads all of the shipping method options for the enable_for_methods field.
         *
         * @return array
         */
        private function load_shipping_method_options()
        {
            $this->load_textdomain();
            // Since this is expensive, we only want to do it if we're actually on the settings page.
            if (!$this->is_accessing_settings()) {
                return array();
            }

            $data_store = WC_Data_Store::load('shipping-zone');
            $raw_zones  = $data_store->get_zones();

            foreach ($raw_zones as $raw_zone) {
                $zones[] = new WC_Shipping_Zone($raw_zone);
            }

            $zones[] = new WC_Shipping_Zone(0);

            $options = array();
            foreach (WC()->shipping()->load_shipping_methods() as $method) {
                $options[$method->get_method_title()] = array();

                // Translators: %1$s shipping method name.
                $options[$method->get_method_title()][$method->id] = sprintf(__('Any &quot;%1$s&quot; method', 'lwc-whatsapp-order'), $method->get_method_title());

                foreach ($zones as $zone) {
                    $shipping_method_instances = $zone->get_shipping_methods();

                    foreach ($shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance) {
                        if ($shipping_method_instance->id !== $method->id) {
                            continue;
                        }

                        $option_id = $shipping_method_instance->get_rate_id();

                        // Translators: %1$s shipping method title, %2$s shipping method id.
                        $option_instance_title = sprintf(__('%1$s (#%2$s)', 'lwc-whatsapp-order'), $shipping_method_instance->get_title(), $shipping_method_instance_id);

                        // Translators: %1$s zone name, %2$s shipping method instance name.
                        $option_title = sprintf(__('%1$s &ndash; %2$s', 'lwc-whatsapp-order'), $zone->get_id() ? $zone->get_zone_name() : __('Other locations', 'lwc-whatsapp-order'), $option_instance_title);

                        $options[$method->get_method_title()][$option_id] = $option_title;
                    }
                }
            }

            return $options;
        }

        /**
         * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
         * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
         * @return array $canonical_rate_ids    Rate IDs in a canonical format.
         */
        private function get_canonical_order_shipping_item_rate_ids($order_shipping_items)
        {
            $canonical_rate_ids = array();

            foreach ($order_shipping_items as $order_shipping_item) {
                $canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
            }

            return $canonical_rate_ids;
        }

        /**
         * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
         * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
         * @return array $canonical_rate_ids  Rate IDs in a canonical format.
         */
        private function get_canonical_package_rate_ids($chosen_package_rate_ids)
        {
            $shipping_packages  = WC()->shipping()->get_packages();
            $canonical_rate_ids = array();

            if (!empty($chosen_package_rate_ids) && is_array($chosen_package_rate_ids)) {
                foreach ($chosen_package_rate_ids as $package_key => $chosen_package_rate_id) {
                    if (!empty($shipping_packages[$package_key]['rates'][$chosen_package_rate_id])) {
                        $chosen_rate          = $shipping_packages[$package_key]['rates'][$chosen_package_rate_id];
                        $canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
                    }
                }
            }

            return $canonical_rate_ids;
        }

        /**
         * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
         * @param array $rate_ids Rate ids to check.
         * @return array
         */
        private function get_matching_rates($rate_ids)
        {
            // First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
            return array_unique(array_merge(array_intersect((array) $this->enable_for_methods, $rate_ids), array_intersect((array) $this->enable_for_methods, array_unique(array_map('wc_get_string_before_colon', $rate_ids)))));
        }

        /**
         * Manage Payment Gateways Availability
         *
         * @param array $available_gateways
         * @return array
         */
        public function manage_payment_gateways_availability($available_gateways)
        {
            // Order Pay page
            if (is_wc_endpoint_url('order-pay')) {
                // Get an instance of the WC_Order Object
                $order = wc_get_order(get_query_var('order-pay'));
                // Loop through payment gateways 'pending', 'on-hold', 'processing'
                foreach ($available_gateways as $gateways_id => $gateways) {
                    // Keep paypal only for "pending" order status
                    if (($gateways_id === $this->id) && $order->has_status('pending')) {
                        unset($available_gateways[$gateways_id]);
                    }
                }
            }
            //Checkout page
            if (is_checkout() && !is_wc_endpoint_url()) {
                if ($this->disable_other_gateways_at_checkout) {
                    foreach ($available_gateways as $gateways_id => $gateways) {
                        // Keep paypal only for "pending" order status
                        if (($gateways_id !== $this->id)) {
                            unset($available_gateways[$gateways_id]);
                        }
                    }
                }
            }
            return $available_gateways;
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id Order ID.
         * @return array
         */
        public function process_payment($order_id)
        {
            $this->load_textdomain();
            $order = wc_get_order($order_id);

            if ($order->get_total() > 0) {
                // Mark as processing or on-hold (payment won't be taken until delivery).
                $order->update_status($order->has_downloadable_item() ? 'on-hold' : 'pending', __('Payment to be made.', 'lwc-whatsapp-order'));
            } else {
                $order->payment_complete();
            }

            // send mail to customer
            $mailer = WC()->mailer();
            $mails = $mailer->get_emails();
            if (! empty($mails)) {
                foreach ($mails as $mail) {
                    if ($mail->id == 'new_order' || $mail->id == 'customer_processing_order') {
                        $mail->trigger($order_id);
                    }
                }
            }

            // Remove cart.
            WC()->cart->empty_cart();

            // Return thankyou redirect.
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }

        /**
         * Output for the order received page and whatsapp order preparation
         */
        public function thankyou_page()
        {
            $this->load_textdomain();
            $vendorWhatsAppNumber = $this->whatsapp_number;
            if ($this->whatsapp_redirect_method == 'whatsapp_link') {
                $link = "<a style='color: white;text-decoration:none;' href='{$this->whatsapp_api}send?phone=$vendorWhatsAppNumber&text={$this->lw_order_info_whatsapp()}'><div style='font-size:1.6em;margin-right:8px' class='dashicons dashicons-whatsapp'></div> " . __('Send Order by WhatsApp', 'lwc-whatsapp-order') . "</a>";
                echo '<div class="woocommerce-message" role="alert" style="background-color: #0bb30b;color: white;"> ' . __('Your order is received in the system and you can send the same order via WhatsApp ', 'lwc-whatsapp-order') . $link . '</div>';
            } else {
                echo __('Please wait while we redirect you to send order using WhatsApp ... ', 'lwc-whatsapp-order') . "<script>
                window.location.href = '{$this->whatsapp_api}send?phone=$vendorWhatsAppNumber&text={$this->lw_order_info_whatsapp()}';
                </script>
                ";
            }
            if ($this->instructions) {
                echo wp_kses_post(wpautop(wptexturize($this->instructions)));
            }
        }

        public function lw_order_info_whatsapp()
        {
            $this->load_textdomain();
            // data preparations for order
            $order_id = wc_get_order_id_by_order_key($_GET['key']);
            $order = wc_get_order($order_id);
            $orderDate = wc_format_datetime($order->get_date_created());
            $email = $order->get_billing_email();
            $orderCurrency = $order->get_currency();
            $order_status = $order->get_status();
            $orderCurrencySymbol = get_woocommerce_currency_symbol($orderCurrency);
            $paymentUrl = $order->get_checkout_payment_url();
            // total formatting
            $totalAmount = $orderCurrencySymbol . '' . $order->get_total() . ' ' . $orderCurrency;
            // whatsapp number where we need to send order
            $vendorWhatsAppNumber = $this->whatsapp_number;
            // get the order item
            $order_items = $order->get_items();
            // order details container
            $orderDetails = '';
            // loop through the each item
            foreach ($order_items as $index => $item) {
                // get product item
                $product = $item->get_product();
                // if product not found
                if (!$product) {
                    continue;
                }
                // list up the item
                $orderDetails .= <<<EOT
{$this->emoticons_in_message("⭐")} {$product->get_name()} x {$item->get_quantity()} => $orderCurrencySymbol{$item->get_subtotal()} $orderCurrency\n
EOT;
                foreach ($item->get_formatted_meta_data() as $itemMeta) {
                    $orderDetails .= strip_tags($itemMeta->display_key) . ': ' . strip_tags($itemMeta->display_value) . "\n";
                }
            }
            // list up the totals
            $orderDetails .= <<<EOT
\n--------------------------------\n
EOT;
            foreach ($order->get_order_item_totals() as $key => $total) {
                $itemValue = strip_tags(('payment_method' === $key) ? esc_html($total['value']) : wp_kses_post($total['value']));
                if (('payment_method' === $key)) {
                    continue;
                }
                $orderDetails .= <<<EOT

{$total['label']} $itemValue
EOT;
            }
            $orderDetails .= "\n\n--------------------------------\n\n";
            // if has customer note
            if ($order->get_customer_note()) {
                $orderDetails .=  esc_html(__('Nota:', 'lwc-whatsapp-order')) . "\n";
                $orderDetails .=  wp_kses_post(nl2br(wptexturize($order->get_customer_note()))) . "\n";
                $orderDetails .= "\n--------------------------------\n\n";
            }
            // check if needs to show shipping
            $show_shipping = !wc_ship_to_billing_address_only() && $order->needs_shipping_address();
            // billing details
            //$orderDetails .=  $this->emoticons_in_message("🗒 ") . esc_html(__('Dirección de Envio:', 'lwc-whatsapp-order')) . "\n\n";
            //$orderDetails .= esc_html(implode("\n", $order->get_address())) . "\n";

//modificar y reemplazar que ira la tienda elegida
// Obtener la tienda elegida del pedido
$tienda = get_post_meta($order->get_id(), 'Tienda', true);

$first_name = $order->get_billing_first_name();
$last_name = $order->get_billing_last_name();




// Modificar y reemplazar que irá la tienda elegida
$orderDetails .= $this->emoticons_in_message("🗒 ") . esc_html(__('Tienda Elegida:', 'lwc-whatsapp-order')) . "\n\n";

if ($tienda) {
    $orderDetails .= esc_html($tienda) . "\n"; // Agregar la tienda elegida al mensaje
} else {
    $orderDetails .= esc_html(__('No se ha seleccionado ninguna tienda.', 'lwc-whatsapp-order')) . "\n"; // Mensaje si no se seleccionó ninguna tienda
}

$orderDetails .= $this->emoticons_in_message("👤 ") . esc_html(__('Nombre:', 'lwc-whatsapp-order')) . " " . esc_html($first_name) . " " . esc_html($last_name) . "\n";






            // if show shipping
            if ($show_shipping) {
                $orderDetails .= "\n--------------------------------\n\n";
                $orderDetails .= $this->emoticons_in_message("🚚 ") . esc_html(__('Dirección de envío:', 'lwc-whatsapp-order')) . "\n\n";
                $orderDetails .= esc_html(implode("\n", $order->get_address('shipping'))) . "\n";
            }
            if($this->send_order_meta_data) {
                $hasAdditionalMetaDataProcessed = false;
                foreach ($order->get_meta_data() as $metaKey => $metaValue) {
                    if(($this->ignore_underscore_order_meta and (substr($metaValue->key, 0, 1) == '_'))
                        or ($this->ignore_order_meta_fields
                            and in_array($metaValue->key, explode(',', sanitize_text_field($this->ignore_order_meta_fields))))) {
                        continue;
                    }
                    if(!$hasAdditionalMetaDataProcessed) {
                        $orderDetails .= "\n--------------------------------\n";
                    }
                    $metaTitle = str_replace(['_', '-'], ' ', mb_convert_case($metaValue->key, MB_CASE_TITLE));
                    $orderDetails .= "$metaTitle : $metaValue->value\n";
                    $hasAdditionalMetaDataProcessed = true;
                }
            }
            $orderDetails .= "\n";
            // send payment link in message
            if ($this->send_payment_link) {
                //$orderDetails .= "\n--------------------------------\n";
                //$orderDetails .= $this->emoticons_in_message("💳 ") . esc_html(__('Pagar Ahora', 'lwc-whatsapp-order')) . "\n";
                //$orderDetails .= $paymentUrl . "\n\n";
            }
            // send view order link in message
            if ($this->send_view_order_link) {
                $orderDetails .= $this->emoticons_in_message("👁 ") . esc_html(__('Ver pedido', 'lwc-whatsapp-order')) . "\n";
                $orderDetails .= $order->get_view_order_url() . "\n\n";
            }

            $siteName = get_bloginfo('name');
            $orderNumber = sprintf(__('Número de orden    : %s', 'lwc-whatsapp-order'), $order_id);
            $orderStatus = sprintf(__('Estado del pedido    : %s', 'lwc-whatsapp-order'), $order_status);
            $orderDate = sprintf(__('Fecha            : %s', 'lwc-whatsapp-order'), $orderDate);
            $orderEmail = sprintf(__('Correo           : %s', 'lwc-whatsapp-order'), $email);
            $formattedTotal = sprintf(__('Monto Total    : %s', 'lwc-whatsapp-order'), $totalAmount);
            $orderDetailsTitle = __('Detalles del pedido:', 'lwc-whatsapp-order');
            $orderTitle = sprintf(__('Nuevo pedido recibido @ %s', 'lwc-whatsapp-order'), $siteName);
            $orderMeta = "";
            // grabbed data from after order table items
            ob_start();
            do_action('woocommerce_order_details_after_order_table_items', $order);
            $rawOrderMeta = ob_get_clean();
            $rawOrderMeta = trim(strip_tags($rawOrderMeta));
            $orderMeta = '';
            // split the string by the newline character, no limit, without empty
            foreach (preg_split("/[\r\n]+/", $rawOrderMeta, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                // trim these line
                $orderMeta .= trim($line) . "\n";
            }
            if ($orderMeta) {
                $orderMeta = "\n" . $orderMeta . "\n";
            }
            // whatsapp order message formatting
            $whatsappMessage = "
{$this->emoticons_in_message("👉")} $orderTitle\n
--------------------------------\n
{$this->emoticons_in_message("#️⃣")} $orderNumber
{$this->emoticons_in_message("🔆")} $orderStatus
{$this->emoticons_in_message("🗓")} $orderDate
{$this->emoticons_in_message("📧")} $orderEmail
{$this->emoticons_in_message("💰")} $formattedTotal
$orderMeta
{$this->emoticons_in_message("🔍")} $orderDetailsTitle \n
$orderDetails
";
            $whatsappMessage = urlencode(html_entity_decode($whatsappMessage));

            return $whatsappMessage;
        }

        /**
         * order submit update title
         */
        public function thank_you_title_update()
        {
            $this->load_textdomain();
            return __('Thank you', 'lwc-whatsapp-order');
        }

        /**
         * order submit update text
         */
        public function thank_you_text_update()
        {
            $this->load_textdomain();
            $order_id = wc_get_order_id_by_order_key($_GET['key']);
            $order = wc_get_order($order_id);
            if ($order->get_payment_method() !== $this->id) {
                // whatsapp number where we need to send order
                $vendorWhatsAppNumber = $this->whatsapp_number;
                if ($this->enabled_on_thank_you_page == 'whatsapp_link') {
                    $link = "<a style='color: white;text-decoration:none;' href='{$this->whatsapp_api}send?phone=$vendorWhatsAppNumber&text={$this->lw_order_info_whatsapp()}'><div style='font-size:1.6em;margin-right:8px' class='dashicons dashicons-whatsapp'></div> " . __('Send Order by WhatsApp', 'lwc-whatsapp-order') . "</a>";
                    return '<div class="woocommerce-message" role="alert" style="background-color: #0bb30b;color: white;"> ' . __('Your order is received in the system and you can send the same order via WhatsApp ', 'lwc-whatsapp-order') . $link . '</div>';
                } elseif ($this->enabled_on_thank_you_page == 'auto_redirect_to_whatsapp') {
                    return __('Please wait while we redirect you to send order using WhatsApp ... ', 'lwc-whatsapp-order') . "<script>
                    window.location.href = '{$this->whatsapp_api}send?phone=$vendorWhatsAppNumber&text={$this->lw_order_info_whatsapp()}';
                    </script>
                    ";
                }
            }
        }

        /**
         * Change payment complete order status to completed for WhatsApp orders.
         *
         * @param  string         $status Current order status.
         * @param  int            $order_id Order ID.
         * @param  WC_Order|false $order Order object.
         * @return string
         */
        public function change_payment_complete_order_status($status, $order_id = 0, $order = false)
        {
            if ($order && $this->id === $order->get_payment_method()) {
                $status = 'completed';
            }
            return $status;
        }

        /**
         * Add content to the WC emails.
         *
         * @param WC_Order $order Order object.
         * @param bool     $sent_to_admin  Sent to admin.
         * @param bool     $plain_text Email format: plain text or HTML.
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {
            if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) {
                echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
            }
        }

        /**
         * Load text domain
         *
         * @return void
         */
        public function load_textdomain()
        {
            load_plugin_textdomain('lwc-whatsapp-order', false, dirname(plugin_basename(__FILE__)) . DIRECTORY_SEPARATOR . 'languages');
        }

        /**
         * Enqueue Styles for Admin Section
         *
         * @param string $hook_suffix
         * @return void
         */
        public function admin_enqueue_scripts_and_styles($hook_suffix)
        {
            // Check if it's the ?page=yourpagename. If not, just empty return before executing the following scripts.
            if ($hook_suffix === 'woocommerce_page_wc-settings') {
                // Load your css.
                wp_register_style('custom_wp_admin_css', plugin_dir_url(__FILE__) . '/assets/css/admin-custom.css', false, '1.0.0');
                wp_enqueue_style('custom_wp_admin_css');
            }
        }
        /**
         * Enqueue Styles
         *
         * @return void
         */
        public function enqueue_scripts_and_styles()
        {
            // Load your css.
            wp_register_style('lmc_whatsapp_order_css', plugin_dir_url(__FILE__) . '/assets/css/custom.css', false, '2.2.0');
            wp_enqueue_style('lmc_whatsapp_order_css');
        }

        /**
         * Add settings link to plugins page
         *
         * @param array $links
         * @return array
         * @since 1.9.0
         */
        public function plugin_settings_link($links)
        {
            $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=' . $this->id . '">' . __('Settings', 'lwc-whatsapp-order') . '</a>';
            if(!in_array($settings_link, $links)) {
                array_unshift($links, $settings_link);
            }
            return $links;
        }

        /**
         * Determine whatever to Emoticons or not
         *
         * @param string $string
         * @return string
         * @since 2.5.0
         */
        public function emoticons_in_message($emoticon = '')
        {
            if($this->use_emoticons_in_message) {
                return $emoticon;
            }
            return '';
        }

        /**
         * Get the plugin folder public url
         *
         * @param string $addonUrlIfAny
         * @return string
         * @since 0.0.0
         */
        public function this_plugin_dir_url($addonUrlIfAny = '')
        {
            return plugin_dir_url(__FILE__) . $addonUrlIfAny;
        }

        /**
         * Plugin url.
         *
         * @return string
         */
        public function plugin_abspath()
        {
            return trailingslashit(plugin_dir_path(__FILE__));
        }
    }
}

if (! function_exists('__logDebug')) {
    /**
     * Debug log
     *
     * @param mixed $log
     * @return void
     * @since 3.0.0
     */
    function __logDebug($log)
    {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}
