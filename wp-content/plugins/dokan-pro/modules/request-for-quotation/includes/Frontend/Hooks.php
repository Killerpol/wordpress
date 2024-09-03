<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation\Frontend;

use WC_Product;
use WC_Emails;
use Exception;
use WC_Data_Exception;
use WP_Error;

use WC_Product_Addons_Helper;

use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;
use WeDevs\DokanPro\Modules\RequestForQuotation\Session;
use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;
use WeDevs\Dokan\ReverseWithdrawal\Helper as ReverseWithdrawalHelper;
use WeDevs\Dokan\ReverseWithdrawal\SettingsHelper as ReverseWithdrawalSettingsHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class for Frontend Hooks integration.
 *
 * @since 3.6.0
 */
class Hooks {

    /**
     * @var mixed $quote_rules
     */
    public $quote_rules;
    /**
     * @var mixed $single_quote_rule
     */
    public $single_quote_rule;

    /**
     * @var array $group_child_products
     */
    public static $group_child_products;

    /**
     * Construct for hooks class.
     */
    public function __construct() {
        add_filter( 'dokan_localized_args', [ $this, 'add_nonce_to_dokan_localized_args' ] );

        // Loads frontend scripts and styles
        add_action( 'init', [ $this, 'register_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // Hide price for selected products.
        add_filter( 'woocommerce_get_price_html', [ $this, 'remove_woocommerce_price_html' ], 10, 2 );

        // Process and initialize the hooks.
        add_action( 'init', [ $this, 'add_archive_page_hooks' ] );
        add_action( 'woocommerce_single_product_summary', [ $this, 'custom_product_button' ], 1, 0 );
        add_action( 'woocommerce_before_single_product', [ $this, 'custom_product_button' ], 1, 0 ); // Elementor issue fixed

        // Save quote data.
        add_action( 'template_redirect', [ $this, 'insert_customer_quote' ] );

        // Add endpoint of quote and process its content.
        add_filter( 'woocommerce_account_menu_items', [ $this, 'dokan_new_menu_items' ] );
        add_action( 'woocommerce_account_' . DOKAN_MY_ACCOUNT_ENDPOINT . '_endpoint', [ $this, 'dokan_endpoint_content' ] );
        add_action( 'dokan_my_account_request_quote_heading', [ $this, 'my_account_request_quote_heading' ] );
        add_action( 'dokan_my_account_request_quote_details', [ $this, 'request_quote_details' ], 10, 4 );
        add_filter( 'the_title', [ $this, 'dokan_endpoint_title' ] );

        // For vendor
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'add_quote_menu' ], 10 );
        add_action( 'dokan_load_custom_template', [ $this, 'load_quote_template' ], 10, 1 );
        add_action( 'dokan_vendor_request_quote_heading', [ $this, 'vendor_request_quote_heading' ] );
        add_action( 'dokan_vendor_request_quote_details', [ $this, 'vendor_request_quote_details' ], 10, 2 );
        add_filter( 'dokan_dashboard_settings_heading_title', [ $this, 'load_settings_header' ], 12, 2 );

        add_action( 'dokan_request_quote_list', [ $this, 'dokan_request_quote_list' ], 10, 3 );
    }

    /**
     * Add nonce to login form popup response
     *
     * @since 3.6.0
     *
     * @param array $default_script
     *
     * @return array
     */
    public function add_nonce_to_dokan_localized_args( array $default_script ): array {
        $default_script['dokan_request_quote_nonce'] = wp_create_nonce( 'dokan_request_quote_nonce' );
        $default_script['valid_price_error']         = __( 'Please enter a valid price', 'dokan' );
        $default_script['valid_quantity_error']      = __( 'Please enter a valid quantity', 'dokan' );

        return $default_script;
    }

    /**
     * Remove woocommerce price html.
     *
     * @since 3.6.0
     *
     * @param             $price
     * @param WC_Product $product
     *
     * @return mixed
     */
    public function remove_woocommerce_price_html( $price, WC_Product $product ) {
        // For shop single page loop main product.
        if ( 'grouped' === $product->get_type() ) {
            self::$group_child_products = $product->get_children();

            return $price;
        }

        // For shop single page loop child product.
        if ( ! empty( self::$group_child_products ) && in_array( $product->get_id(), self::$group_child_products, true ) ) {
            return $price;
        }

        if ( 'variation' === $product->get_type() ) {
            $product_id = $product->get_parent_id();
            $product    = wc_get_product( $product_id );
        }

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        $applicable_rule = null;

        foreach ( $this->quote_rules as $rule ) {
            if ( false === apply_filters( 'dokan_request_a_quote_apply_rules', true, $product, $rule ) || ! $this->check_rule_for_product( $rule, $product->get_id() ) ) {
                continue;
            }

            // Checking if there are no capable rule is set or current loop rule priority is less or lower than the previous rule.
            if ( null === $applicable_rule || $applicable_rule->rule_priority >= $rule->rule_priority ) {
                $applicable_rule = $rule;
            }
        }

        if ( dokan_is_product_author( $product->get_id() ) && dokan_is_seller_dashboard() ) {
            return $price;
        } elseif ( null !== $applicable_rule && $applicable_rule->hide_price && ! dokan_is_product_author( $product->get_id() ) ) {
            add_filter( 'dokan_should_render_stripe_express_payment_request_button', '__return_false' );
            return $applicable_rule->hide_price_text;
        }

        return $price;
    }

    /**
     * Add archive page hooks.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function add_archive_page_hooks() {
        // Replace add to cart button with custom button on shop page.
        add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'replace_loop_add_to_cart_link' ], 10, 2 );

        // Add Custom button along with add to cart button on shop page.
        add_action( 'woocommerce_after_shop_loop_item', [ $this, 'custom_add_to_quote_button' ], 11, 2 );
    }

    /**
     * Replace loop add to cart link.
     *
     * @since 3.6.0
     *
     * @param $html
     * @param $product
     *
     * @return mixed|string
     */
    public function replace_loop_add_to_cart_link( $html, $product ) {
        $cart_txt = $html;

        if ( 'simple' !== $product->get_type() ) {
            return $html;
        }

        if ( ! $product->is_in_stock() && ! SettingsHelper::is_out_of_stock_enabled() ) {
            return $html;
        }

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        $applicable_rule = null;

        foreach ( $this->quote_rules as $rule ) {
            if ( ! $this->check_rule_for_product( $rule, $product->get_id() ) ) {
                continue;
            }

            if ( false === apply_filters( 'dokan_request_a_quote_apply_rules', true, $product, $rule ) ) {
                continue;
            }

            // Checking if there are no capable rule is set or current loop rule priority is less or lower than the previous rule.
            if ( null === $applicable_rule || $applicable_rule->rule_priority >= $rule->rule_priority ) {
                $applicable_rule = $rule;
            }
        }

        if ( null !== $applicable_rule && 'replace' === $applicable_rule->hide_cart_button ) {
            if ( ! dokan_is_product_author( $product->get_id() ) ) {
                wp_enqueue_script( 'dokan-request-a-quote-frontend' );
                return '<a href="javascript:void(0)" rel="nofollow" data-quantity="1" class="dokan_request_button button product_type_' . esc_attr( $product->get_type() ) . ' dokan_add_to_quote_button" data-product_id="' . intval( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" aria-label="Add &ldquo;' . esc_attr( $product->get_title() ) . '&rdquo; to your quote" >' . esc_html( $applicable_rule->button_text ) . '</a>';
            }
        }

        if ( $this->check_required_addons( $product->get_id() ) ) {
            //WooCommerce Product Add-ons compatibility
            return $html;
        }

        return $cart_txt;
    }

    /**
     * Check rule for product.
     *
     * @since 3.6.0
     *
     * @param $rule
     * @param $product_id
     *
     * @return bool
     */
    public function check_rule_for_product( $rule, $product_id ): bool {
        $rule_contents = maybe_unserialize( $rule->rule_contents );
        if ( ! is_user_logged_in() && ( in_array( 'guest', (array) $rule_contents['selected_user_role'], true ) ) ) {
            if ( $this->validate_rules( $rule, $rule_contents, $product_id ) ) {
                return true;
            }

            return false;
        }

        $current_user      = wp_get_current_user();
        $current_user_caps = array_keys( $current_user->caps );
        if ( array_intersect( $current_user_caps, (array) $rule_contents['selected_user_role'] ) && ( $this->validate_rules( $rule, $rule_contents, $product_id ) ) ) {
            return true;
        }

        $current_user_role = current( $current_user->roles );
        if ( ! in_array( $current_user_role, (array) $rule_contents['selected_user_role'], true ) ) {
            return false;
        }
        if ( $this->validate_rules( $rule, $rule_contents, $product_id ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate rules.
     *
     * @since 3.6.0
     *
     * @param $rule
     * @param $rule_contents
     * @param $product_id
     *
     * @return bool
     */
    public function validate_rules( $rule, $rule_contents, $product_id ): bool {
        if ( $rule->apply_on_all_product ) {
            return true;
        }

        if ( in_array( $product_id, (array) $rule_contents['product_ids'], true ) ) {
            return true;
        }

        if ( ! empty( $rule_contents['category_ids'] ) ) {
            foreach ( $rule_contents['category_ids'] as $cat ) {
                if ( has_term( $cat, 'product_cat', $product_id ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Custom_add_to_quote_button.
     *
     * @since 3.6.0
     *
     * @return string|void
     */
    public function custom_add_to_quote_button() {
        global $product;

        if ( ! $product->is_in_stock() && ! SettingsHelper::is_out_of_stock_enabled() ) {
            return;
        }

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        $applicable_rule = null;

        foreach ( $this->quote_rules as $rule ) {
            if ( ! $this->check_rule_for_product( $rule, $product->get_id() ) ) {
                continue;
            }

            if ( $this->check_required_addons( $product->get_id() ) ) {
                return __( 'Select options', 'dokan' );
            }

            if ( false === apply_filters( 'dokan_request_a_quote_apply_rules', true, $product, $rule ) ) {
                continue;
            }

            if ( null === $applicable_rule || $applicable_rule->rule_priority >= $rule->rule_priority ) {
                $applicable_rule = $rule;
            }
        }

        if ( null === $applicable_rule || 'replace' === $applicable_rule->hide_cart_button  ) {
            return;
        }

        if ( ! dokan_is_product_author( $product->get_id() ) ) {
            if ( 'simple' === $product->get_type() ) {
                wp_enqueue_script( 'dokan-request-a-quote-frontend' );
                echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . esc_attr( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="button dokan_request_button add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_html( $applicable_rule->button_text ) . '</a>';
            } elseif ( 'keep_and_add_new' === $applicable_rule->hide_cart_button && ! empty( $applicable_rule->button_text ) ) {
                wp_enqueue_script( 'dokan-request-a-quote-frontend' );
                echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . esc_attr( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="button dokan_request_button add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_html( $applicable_rule->button_text ) . '</a>';
            }
        }
    }

    /**
     * Check required addons.
     *
     * @since 3.6.0
     *
     * @param $product_id
     *
     * @return bool
     */
    public function check_required_addons( $product_id ): bool {
        // No parent add-ons, but yes to global.
        if ( in_array( 'woocommerce-product-addons/woocommerce-product-addons.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            $addons = WC_Product_Addons_Helper::get_product_addons( $product_id );

            if ( ! empty( $addons ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Custom product button.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function custom_product_button() {
        global $product;

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        // check if reverse withdrawal feature is enabled
        if ( ReverseWithdrawalSettingsHelper::is_enabled() ) {
            // get vendor id
            $vendor_id = dokan_get_vendor_by_product( $product, true );
            // check if action is taken for this vendor
            $failed_actions = ReverseWithdrawalHelper::get_failed_actions_by_vendor( $vendor_id );
            if ( in_array( 'enable_catalog_mode', $failed_actions, true ) ) {
                return;
            }
        }

        foreach ( $this->quote_rules as $rule ) {
            if ( ! $this->check_rule_for_product( $rule, $product->get_id() ) ) {
                continue;
            }

            if ( 'replace' !== $rule->hide_cart_button && 'keep_and_add_new' !== $rule->hide_cart_button ) {
                continue;
            }

            if ( false === apply_filters( 'dokan_request_a_quote_apply_rules', true, $product, $rule ) ) {
                continue;
            }

            // Checking if already there is an applied rule and if current loop rule priority if higher than the old rule loop priority then skip.
            if ( ! empty( $this->single_quote_rule ) && $this->single_quote_rule->rule_priority < $rule->rule_priority ) {
                continue;
            }

            $this->single_quote_rule = $rule;

            if ( 'variable' === $product->get_type() ) {
                remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
                add_action( 'woocommerce_single_variation', [ $this, 'custom_button_replacement' ], 30 );
            } else {
                remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
                add_action( 'woocommerce_simple_add_to_cart', [ $this, 'custom_button_replacement' ], 30 );
            }
            add_filter( 'dokan_stripe_express_should_render_payment_request_button', '__return_false' );
        }
    }

    /**
     * Custom button replacement.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function custom_button_replacement() {
        global $product;

        $template_name = 'variable' === $product->get_type() ? 'variable' : 'custom-button';

        wp_enqueue_script( 'dokan-request-a-quote-frontend' );

        dokan_get_template_part(
            $template_name, '', [
                'single_quote_rule'    => $this->single_quote_rule,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Enqueue scripts.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        global $wp;

        if (
            ( dokan_is_seller_dashboard() && isset( $wp->query_vars['requested-quotes'] ) )
            || ( is_account_page() && isset( $wp->query_vars['request-a-quote'] ) )
            || is_page( Helper::get_quote_page_id() )
            || dokan_is_store_page()
            || is_checkout()
            || is_product()
            || is_shop()
        ) {
            wp_enqueue_script( 'dokan-request-a-quote-frontend' );
            wp_enqueue_style( 'dokan-request-a-quote-frontend' );
        }
    }

    /**
     * Register scripts.
     *
     * @since 3.7.4
     *
     * @return void
     */
    public function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        wp_register_script(
            'dokan-request-a-quote-frontend',
            DOKAN_RAQ_ASSETS . '/js/main.js',
            [ 'jquery' ],
            $version,
            true
        );
        wp_register_style(
            'dokan-request-a-quote-frontend',
            DOKAN_RAQ_ASSETS . '/css/request-a-quote-front.css',
            [],
            $version
        );
    }

    /**
     * Insert customer quote.
     *
     * @since 3.6.0
     *
     * @throws WC_Data_Exception
     * @throws Exception
     * @return void
     */
    public function insert_customer_quote() {
        if ( empty( $_POST ) ) {
            return;
        }

        if ( empty( $_POST['dokan_quote_nonce'] ) || ! wp_verify_nonce( esc_attr( sanitize_text_field( wp_unslash( $_POST['dokan_quote_nonce'] ) ) ), 'save_dokan_quote_action' ) ) {
            return;
        }

        if ( ! empty( $_POST['dokan_convert_to_order_customer'] ) ) {
            $quote_id     = sanitize_text_field( wp_unslash( $_POST['dokan_convert_to_order_customer'] ) );
            $converted_by = empty( $_POST['converted_by'] ) ? 'Admin' : sanitize_text_field( wp_unslash( $_POST['converted_by'] ) );
            $converted    = $this->convert_to_order( $quote_id, $converted_by );
            if ( is_wp_error( $converted ) ) {
                wc_add_notice( $converted->get_error_message(), 'error' );

                return;
            }
            $quote_order = wc_get_order( $converted );

            /* translators: %1$s: Quote id, %2$s: Order id */
            wc_add_notice( sprintf( __( 'Your Quote# %1$s has been converted to Order# %2$s.', 'dokan' ), $quote_id, $quote_order->get_id() ), 'success' );

            if ( 'Customer' === $converted_by ) {
                wp_safe_redirect( $quote_order->get_checkout_payment_url() );
                exit;
            }

            return;
        }

        if ( ! empty( $_POST['approved_by_vendor_button'] ) ) {
            $quote_id = sanitize_text_field( wp_unslash( $_POST['approved_by_vendor_button'] ) );
            $status   = ! empty( $_POST['approved_by_vendor'] ) ? sanitize_text_field( wp_unslash( $_POST['approved_by_vendor'] ) ) : 'approve';
            $updated  = Helper::change_status( 'dokan_request_quotes', $quote_id, $status );
            if ( is_wp_error( $updated ) ) {
                wc_add_notice( $updated->get_error_message(), 'error' );

                return;
            }
            if ( ! $updated ) {
                wc_add_notice( __( 'Something went wrong! Your quote could not be updated.', 'dokan' ), 'error' );

                return;
            }

            wc_add_notice( __( 'Your quote has been successfully updated.', 'dokan' ), 'success' );

            return;
        }

        if ( ! empty( $_POST['dokan_update_quote'] ) ) {
            $updated = $this->dokan_update_quote();
            if ( is_wp_error( $updated ) ) {
                wc_add_notice( $updated->get_error_message(), 'error' );

                return;
            }
            if ( ! $updated ) {
                wc_add_notice( __( 'Something went wrong! Your quote could not be updated.', 'dokan' ), 'error' );

                return;
            }

            wc_add_notice( __( 'Your quote has been successfully updated.', 'dokan' ), 'success' );

            return;
        }

        $quote_session = Session::init();
        $quotes        = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );
        if ( empty( $quotes ) ) {
            wc_add_notice( __( 'No item found in quote basket.', 'dokan' ), 'error' );

            return;
        }

        if ( ! is_user_logged_in() && ( empty( $_POST['email_field'] ) || empty( $_POST['phone_field'] ) ) ) {
            wc_add_notice( __( 'Please provide all the required information.', 'dokan' ), 'error' );

            return;
        }

        if ( isset( $_POST['dokan_quote_save_action'] ) ) {
            $this->dokan_quote_save_action( $quotes );
        }
    }

    /**
     * Dokan save quote.
     *
     * @since 3.6.0
     *
     * @param $quotes
     *
     * @return void
     */
    public function dokan_quote_save_action( $quotes ) {
        if ( empty( $_POST['dokan_quote_nonce'] ) || ! wp_verify_nonce( esc_attr( sanitize_text_field( wp_unslash( $_POST['dokan_quote_nonce'] ) ) ), 'save_dokan_quote_action' ) ) {
            return;
        }

        if ( empty( $quotes ) ) {
            wc_add_notice( __( 'No item found in quote basket.', 'dokan' ), 'error' );

            return;
        }

        unset( $_POST['dokan_quote_save_action'] );

        $user          = wp_get_current_user();
        $customer_info = [];
        if ( ! empty( $user ) ) {
            $customer_info['name_field']    = $user->display_name;
            $customer_info['email_field']   = $user->user_email;
            $customer_info['company_field'] = '';
            $customer_info['phone_field']   = '';
        }

        if ( ! is_user_logged_in() ) {
            $customer_info['name_field']    = ! empty( $_POST['name_field'] ) ? sanitize_text_field( wp_unslash( $_POST['name_field'] ) ) : '';
            $customer_info['email_field']   = ! empty( $_POST['email_field'] ) ? sanitize_text_field( wp_unslash( $_POST['email_field'] ) ) : '';
            $customer_info['company_field'] = ! empty( $_POST['company_field'] ) ? sanitize_text_field( wp_unslash( $_POST['company_field'] ) ) : '';
            $customer_info['phone_field']   = ! empty( $_POST['phone_field'] ) ? dokan_sanitize_phone_number( wp_unslash( $_POST['phone_field'] ) ) : '';
        }
        $request_quote['customer_info'] = $customer_info;
        $request_quote['quote_title']   = "({$customer_info['name_field']})";
        $request_quote['user_id']       = ! empty( get_current_user_id() ) ? get_current_user_id() : 0;

        $request_quote_id = Helper::create_request_quote( $request_quote );

        if ( is_wp_error( $request_quote_id ) ) {
            wc_add_notice( $request_quote_id->get_error_message(), 'error' );

            return;
        }

        if ( ! empty( $request_quote_id ) && $request_quote_id > 0 ) {
            $quote_details['quote_id'] = $request_quote_id;
            foreach ( $quotes as $quote ) {
                $quote_details['product_id']  = $quote['product_id'];
                $quote_details['quantity']    = $quote['quantity'];
                $quote_details['offer_price'] = $quote['offered_price'];

                Helper::create_request_quote_details( $quote_details );
            }

            WC_Emails::instance();
            do_action( 'after_dokan_request_quote_inserted', $request_quote_id );
            $quote_session = Session::init();
            $quote_session->delete( DOKAN_SESSION_QUOTE_KEY );
            wc_add_notice( __( 'Your quote has been submitted successfully.', 'dokan' ), 'success' );

            // Redirect to the newly created request quote page.
            wp_safe_redirect( rtrim( wc_get_account_endpoint_url( 'request-a-quote' ), '/' ) . "/{$request_quote_id}" );

            return;
        }
        wc_add_notice( __( 'Something went wrong! Your quote not saved.', 'dokan' ), 'error' );
    }

    /**
     * Dokan update quote.
     *
     * @since 3.6.0
     *
     * @return void | bool | WP_Error
     */
    public function dokan_update_quote() {
        if ( empty( $_POST['dokan_quote_nonce'] ) || ! wp_verify_nonce( esc_attr( sanitize_text_field( wp_unslash( $_POST['dokan_quote_nonce'] ) ) ), 'save_dokan_quote_action' ) ) {
            return;
        }

        $quote_id    = ! empty( $_POST['dokan_update_quote'] ) ? sanitize_text_field( wp_unslash( $_POST['dokan_update_quote'] ) ) : 0;
        $offer_price = ! empty( $_POST['offer_price'] ) ? array_map( 'floatval', wp_unslash( $_POST['offer_price'] ) ) : [];
        $quote_qty   = ! empty( $_POST['quote_qty'] ) ? array_map( 'absint', wp_unslash( $_POST['quote_qty'] ) ) : [];
        if ( ( empty( $offer_price ) || min( $offer_price ) <= 0 ) ) {
            return new WP_Error( 'error', __( 'Please enter a valid offer price.', 'dokan' ) );
        }
        if ( empty( $quote_qty ) || min( $quote_qty ) <= 0 ) {
            return new WP_Error( 'error', __( 'Please enter a valid quantity.', 'dokan' ) );
        }
        $converted_by      = empty( $_POST['updated_by'] ) ? 'Admin' : sanitize_text_field( wp_unslash( $_POST['updated_by'] ) );
        $old_quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );
        Helper::update_dokan_request_quote_converted( $quote_id, $converted_by );
        Helper::delete( 'quote_details', $quote_id, 'quote_id', true );
        $quote_details['quote_id'] = $quote_id;
        foreach ( $offer_price as $key => $price ) {
            $quote_details['product_id']  = $key;
            $quote_details['quantity']    = $quote_qty[ $key ];
            $quote_details['offer_price'] = $price;
            Helper::create_request_quote_details( $quote_details );
        }

        $new_quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );
        if ( empty( $new_quote_details ) ) {
            Helper::delete( 'quotes', $quote_id, 'id', true );
        }

        WC_Emails::instance();
        do_action( 'after_dokan_request_quote_updated', $quote_id, $old_quote_details, $new_quote_details );

        return true;
    }

    /**
     * Dokan new menu items.
     *
     * @since 3.6.0
     *
     * @param $items
     *
     * @return array
     */
    public function dokan_new_menu_items( $items ): array {
        $menu_items = [
            DOKAN_MY_ACCOUNT_ENDPOINT => esc_html__( 'Request Quotes', 'dokan' ),
        ];

        return array_slice( $items, 0, 2, true ) + $menu_items + array_slice( $items, 1, count( $items ), true );
    }

    /**
     * Dokan endpoint title.
     *
     * @since 3.6.0
     *
     * @param $title
     *
     * @return mixed|string
     */
    public function dokan_endpoint_title( $title ) {
        global $wp_query;
        if ( isset( $wp_query->query_vars[ DOKAN_MY_ACCOUNT_ENDPOINT ] ) && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
            // New page title.
            $title = esc_html__( 'Requested Quotes', 'dokan' );
            remove_filter( 'the_title', [ $this, 'endpoint_title' ] );
        }

        return $title;
    }

    /**
     * Dokan endpoint content.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function dokan_endpoint_content() {
        if ( ! is_user_logged_in() ) {
            return;
        }
        $quote_id = get_query_var( DOKAN_MY_ACCOUNT_ENDPOINT );
        if ( ! empty( $quote_id ) ) {
            $data['quote']         = Helper::get_request_quote_by_id( $quote_id );
            $data['quote_details'] = Helper::get_request_quote_details_by_quote_id( $quote_id );
            if ( empty( $data['quote'] ) ) {
                return;
            }

            dokan_get_template_part(
                'customer/quote-details-my-account', '', [
                    'quote'                => $data['quote'],
                    'quote_details'        => $data['quote_details'],
                    'request_quote_vendor' => true,
                ]
            );

            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $limit   = empty( $_REQUEST['per_page'] ) ? 10 : sanitize_text_field( wp_unslash( $_REQUEST['per_page'] ) );
        $page_no = isset( $_REQUEST['page_no'] ) ? absint( sanitize_text_field( wp_unslash( $_REQUEST['page_no'] ) ) ) : 1;
        $offset  = ( $page_no * $limit ) - $limit;

        $args              = [
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'status'         => ( empty( $_REQUEST['status'] ) || $_REQUEST['status'] === 'all' ) ? '' : sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ),
            'order'          => empty( $_REQUEST['order'] ) ? 'DESC' : sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ),
            'orderby'        => empty( $_REQUEST['orderby'] ) ? 'id' : sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ),
            'user_id'        => dokan_get_current_user_id(),
            'page_no'        => $page_no,
        ];
        $vendor_all_quotes = Helper::get_request_quote( $args );
        $total_count       = Helper::get_request_quote_count();
        $total_page        = ceil( array_sum( (array) $total_count ) / $args['posts_per_page'] );
        $pagination_html   = $this->get_pagination( $total_page, $args['page_no'] );

        dokan_get_template_part(
            'customer/quote-list-table', '', [
                'vendor_all_quotes'    => $vendor_all_quotes,
                'pagination_html'      => $pagination_html,
                'account_endpoint'     => DOKAN_ACCOUNT_ENDPOINT,
                'request_quote_vendor' => true,
            ]
        );
        // phpcs:enable
    }

    /**
     * Vendor request quote details.
     *
     * @since 3.6.0
     *
     * @param $quote_details
     * @param $quote
     *
     * @return void
     */
    public function vendor_request_quote_details( $quote_details, $quote ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $updated_by = 'Vendor';
        dokan_get_template_part(
            'quote_details', '', [
                'quote'                => $quote,
                'quote_details'        => $quote_details,
                'approved_by_vendor'   => true,
                'converted_by'         => 'Vendor',
                'updated_by'           => $updated_by,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Request quote list table.
     *
     * @since 3.6.0
     *
     * @param $vendor_all_quotes
     * @param $endpoint
     * @param $pagination_html
     *
     * @return void
     */
    public function dokan_request_quote_list( $vendor_all_quotes, $endpoint, $pagination_html ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        dokan_get_template_part(
            'quote-list', '', [
                'vendor_all_quotes'    => $vendor_all_quotes,
                'endpoint'             => $endpoint,
                'pagination_html'      => $pagination_html,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Vendor request quote heading.
     *
     * @since 3.6.0
     *
     * @param $quote
     *
     * @return void
     */
    public function vendor_request_quote_heading( $quote ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $customer_info  = maybe_unserialize( $quote->customer_info );
        $customer_name  = ! empty( $customer_info['name_field'] ) ? $customer_info['name_field'] : '';
        $customer_email = ! empty( $customer_info['email_field'] ) ? $customer_info['email_field'] : '';
        dokan_get_template_part(
            'vendor/vendor_request_quote_heading', '', [
                'quote'                => $quote,
                'customer_name'        => $customer_name,
                'customer_email'       => $customer_email,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Customer request quote details.
     *
     * @since 3.6.0
     *
     * @param $quote_details
     * @param $quote
     *
     * @return void
     */
    public function request_quote_details( $quote_details, $quote ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $updated_by = 'Customer';
        dokan_get_template_part(
            'quote_details', '', [
                'quote'                => $quote,
                'quote_details'        => $quote_details,
                'converted_by'         => 'Customer',
                'updated_by'           => $updated_by,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Customer request quote heading.
     *
     * @since 3.6.0
     *
     * @param $quote
     *
     * @return void
     */
    public function my_account_request_quote_heading( $quote ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        dokan_get_template_part(
            'customer/request_quote_heading', '', [
                'quote'                => $quote,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Convert to order.
     *
     * @since 3.6.0
     *
     * @param $quote_id
     * @param $converted_by
     *
     * @throws Exception
     * @return int | WP_Error
     */
    public function convert_to_order( $quote_id, $converted_by ) {
        if ( empty( $quote_id ) ) {
            return new WP_Error( 'no_quote_found', __( 'No quote found', 'dokan' ), [ 'status' => 404 ] );
        }

        $quote         = (object) Helper::get_request_quote_by_id( $quote_id );
        $quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );

        $order_id = Helper::convert_quote_to_order( $quote, $quote_details );

        Helper::change_status( 'dokan_request_quotes', $quote_id, 'converted' );
        Helper::update_dokan_request_quote_converted( $quote_id, $converted_by, $order_id );

        return $order_id;
    }

    /**
     * Add vendor rma menu
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function add_quote_menu( $urls ) {
        $menu = [
            'title'      => __( 'Request Quotes', 'dokan' ),
            'icon'       => '<i class="fa fa-list" aria-hidden="true"></i>',
            'url'        => dokan_get_navigation_url( DOKAN_VENDOR_ENDPOINT ),
            'pos'        => 53,
            'permission' => 'dokan_view_request_quote_menu',
        ];

        if ( dokan_is_seller_enabled( dokan_get_current_user_id() ) ) {
            $urls[ DOKAN_VENDOR_ENDPOINT ] = $menu;
        }

        return $urls;
    }

    /**
     * Load quote template for vendor
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function load_quote_template( $query_vars ) {
        if ( ! isset( $query_vars[ DOKAN_VENDOR_ENDPOINT ] ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            dokan_get_template_part(
                'global/dokan-error', '', [
                    'deleted' => false,
                    'message' => __( 'You have no permission to view this requests page', 'dokan' ),
                ]
            );

            return;
        }

        $quote_id = get_query_var( DOKAN_VENDOR_ENDPOINT );
        if ( ! empty( $quote_id ) ) {
            $data['quote'] = Helper::get_request_quote_vendor_by_id( $quote_id, dokan_get_current_user_id() );
            if ( empty( $data['quote'] ) ) {
                dokan_get_template_part(
                    'global/dokan-error', '', [
                        'deleted' => false,
                        'message' => __( 'You have no permission to view this requests page', 'dokan' ),
                    ]
                );

                return;
            }
            $data['quote_details'] = Helper::get_request_quote_details_by_vendor_id( $quote_id, dokan_get_current_user_id() );
            dokan_get_template_part(
                'vendor/vendor-quote-details', '', [
                    'vendor_endpoint'      => DOKAN_VENDOR_ENDPOINT,
                    'quote_id'             => $quote_id,
                    'data'                 => $data,
                    'request_quote_vendor' => true,
                ]
            );

            return;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $limit   = empty( $_REQUEST['per_page'] ) ? 15 : sanitize_text_field( wp_unslash( $_REQUEST['per_page'] ) );
        $page_no = isset( $_REQUEST['page_no'] ) ? absint( wp_unslash( $_REQUEST['page_no'] ) ) : 1;
        $offset  = ( $page_no * $limit ) - $limit;
        $args    = [
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'status'         => ( empty( $_REQUEST['status'] ) || $_REQUEST['status'] === 'all' ) ? '' : sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ),
            'author_id'      => dokan_get_current_user_id(),
            'order'          => empty( $_REQUEST['order'] ) ? 'DESC' : sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ),
            'orderby'        => empty( $_REQUEST['orderby'] ) ? 'id' : sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ),
            'page_no'        => $page_no,
        ];

        $vendor_all_quotes = Helper::get_request_quote_for_vendor( $args );
        $total_count       = Helper::count_request_quote_for_vendor( $args );
        $total_page        = ceil( count( $total_count ) / $args['posts_per_page'] );

        $pagination_html = $this->get_pagination( $total_page, $args['page_no'] );

        dokan_get_template_part(
            'vendor/vendor-quote-list-table', '', [
                'vendor_endpoint'      => DOKAN_VENDOR_ENDPOINT,
                'vendor_all_quotes'    => $vendor_all_quotes,
                'pagination_html'      => $pagination_html,
                'request_quote_vendor' => true,
            ]
        );

        // phpcs:enable
    }

    /**
     * Load Settings Header
     *
     * @since 3.6.0
     *
     * @param string $header
     * @param string $query_vars
     *
     * @return string
     *
     */
    public function load_settings_header( string $header, string $query_vars ): string {
        if ( DOKAN_VENDOR_ENDPOINT === $query_vars ) {
            $header = __( 'Request Quote', 'dokan' );
        }

        return $header;
    }

    /**
     * Get pagination.
     *
     * @since 3.6.0
     *
     * @param int | string $total_page
     * @param int | string $page_no
     *
     * @return string
     */
    public function get_pagination( $total_page, $page_no ): string {
        $pagination_html = '';
        if ( $total_page > 1 ) {
            $pagination_html = '<div class="pagination-wrap">';
            $page_links      = paginate_links(
                [
                    'base'      => add_query_arg( 'page_no', '%#%' ),
                    'format'    => '',
                    'type'      => 'array',
                    'prev_text' => __( '&laquo; Previous', 'dokan' ),
                    'next_text' => __( 'Next &raquo;', 'dokan' ),
                    'total'     => $total_page,
                    'current'   => $page_no,
                ]
            );
            $pagination_html .= '<ul class="pagination"><li>';
            $pagination_html .= join( "</li>\n\t<li>", $page_links );
            $pagination_html .= "</li>\n</ul>\n";
            $pagination_html .= '</div>';
        }

        return $pagination_html;
    }
}
