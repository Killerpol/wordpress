<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

use WC_Customer;

/**
 * Request A Quote Helper Class.
 */
class Helper {

    /**
     * Dokan get all request quotes.
     *
     * @since 3.6.0
     *
     * @param $args
     *
     * @return array|object|null
     *
     * @return void
     */
    public static function get_request_quote( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'user_id'        => 0,
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where[] = 'status LIKE %s';
        $data[] = $wpdb->esc_like( $args['status'] );
        if ( '' === $args['status'] ) {
            $where   = [];
            $data    = [];
            $where[] = 'status NOT LIKE %s';
            $data[] = $wpdb->esc_like( 'trash' );
        }

        if ( 0 !== $args['user_id'] ) {
            $where[] = 'user_id = %d';
            $data[]  = $args['user_id'];
        }

        $where  = implode( ' AND ', $where );
        $limit  = 'LIMIT %d, %d';
        $data[] = $args['offset'];
        $data[] = $args['posts_per_page'];

        return $wpdb->get_results(
            $wpdb->prepare(
            // phpcs:ignore
                "SELECT * FROM {$wpdb->prefix}dokan_request_quotes WHERE {$where} ORDER BY {$args['orderby']} {$args['order']} $limit",
                $data
            )
        );
    }

    /**
     * Dokan get vendor specific request quotes.
     *
     * @since 3.6.0
     *
     * @param $args
     *
     * @return array|object|null
     *
     * @return void
     */
    public static function get_request_quote_for_vendor( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'author_id'      => 0,
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where[] = 'rq.status LIKE %s';
        $data[]  = $wpdb->esc_like( $args['status'] );
        if ( '' === $args['status'] ) {
            $where   = [];
            $data    = [];
            $where[] = 'rq.status NOT LIKE %s';
            $data[]  = $wpdb->esc_like( 'trash' );
        }

        if ( 0 !== $args['author_id'] ) {
            $where[] = 'rq.id=rqd.quote_id and rqd.product_id = p.ID and p.post_author=%d';
            $data[]  = $args['author_id'];
        }

        $limit  = 'LIMIT %d, %d';
        $data[] = $args['offset'];
        $data[] = $args['posts_per_page'];
        $where  = implode( ' and ', $where );

        return $wpdb->get_results(
            $wpdb->prepare(
            // phpcs:ignore
                "SELECT rq.* FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p  WHERE {$where} GROUP BY rq.id ORDER BY rq.{$args['orderby']} {$args['order']} {$limit}",
                $data
            )
        );
    }

    /**
     * Dokan get vendor specific request quotes total.
     *
     * @since 3.6.0
     *
     * @param $args
     *
     * @return array|object|null
     *
     * @return void
     */
    public static function count_request_quote_for_vendor( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'author_id'      => 0,
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where[] = 'rq.status LIKE %s';
        $data[]  = $wpdb->esc_like( $args['status'] );
        if ( '' === $args['status'] ) {
            $where   = [];
            $data    = [];
            $where[] = 'rq.status NOT LIKE %s';
            $data[]  = $wpdb->esc_like( 'trash' );
        }

        if ( 0 !== $args['author_id'] ) {
            $where[] = 'rq.id=rqd.quote_id and rqd.product_id = p.ID and p.post_author=%d';
            $data[]  = $args['author_id'];
        }

        $where = implode( ' and ', $where );

        return $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore
                "SELECT count(rq.id) as total_count FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p WHERE 1=1 and {$where} GROUP BY rq.id",
                $data
            )
        );
    }

    /**
     * Dokan get all request quote rules.
     *
     * @since 3.6.0
     *
     * @param $args
     *
     * @return array|object|null
     *
     * @return void
     */
    public static function get_quote_rules( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where[] = 'status LIKE %s';
        $data[]  = $wpdb->esc_like( $args['status'] );
        if ( '' === $args['status'] ) {
            $where   = [];
            $data    = [];
            $where[] = 'status NOT LIKE %s';
            $data[]  = $wpdb->esc_like( 'trash' );
        }
        $where = implode( ' and ', $where );
        $limit  = 'LIMIT %d, %d';
        $data[] = $args['offset'];
        $data[] = $args['posts_per_page'];
        return $wpdb->get_results(
            $wpdb->prepare(
            // phpcs:ignore
                "SELECT * FROM {$wpdb->prefix}dokan_request_quote_rules WHERE {$where} ORDER BY {$args['orderby']} {$args['order']} {$limit}",
                $data
            )
        );
    }

    /**
     * Dokan get all request quote rules.
     *
     * @since 3.6.0
     *
     * @return array|object|null
     */
    public static function get_all_quote_rules() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dokan_request_quote_rules WHERE `status` = 'publish'"
        );
    }

    /**
     * Dokan get all request quote details.
     *
     * @since 3.6.0
     *
     * @param int $quote_id
     *
     * @return array|object|null
     */
    public static function get_request_quote_details_by_quote_id( $quote_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT  * FROM {$wpdb->prefix}dokan_request_quote_details WHERE quote_id = %d",
                $quote_id
            )
        );
    }

    /**
     * Dokan get all request quote details by vendor id.
     *
     * @since 3.6.0
     *
     * @param int $quote_id
     * @param int $vendor_id
     *
     * @return array|object|null
     */
    public static function get_request_quote_details_by_vendor_id( $quote_id, $vendor_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT rqd.* FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p  WHERE rqd.quote_id=%d and rq.id=rqd.quote_id and rqd.product_id = p.ID and p.post_author=%d",
                $quote_id, $vendor_id
            )
        );
    }

    /**
     * Dokan get all request quote details.
     *
     * @since 3.6.0
     *
     * @param int $quote_id
     *
     * @return array|object|null
     */
    public static function get_request_quote_by_id( $quote_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dokan_request_quotes WHERE id = %d",
                $quote_id
            )
        );
    }

    /**
     * Dokan get all request quote details.
     *
     * @since 3.6.0
     *
     * @param int $quote_id
     * @param int $vendor_id
     *
     * @return array|object|null
     */
    public static function get_request_quote_vendor_by_id( $quote_id, $vendor_id ) {
        global $wpdb;

        $where = [ 'rq.id = %d' ];

        $data = [ $quote_id ];

        if ( 0 !== $vendor_id ) {
            $where[] = 'rq.id = rqd.quote_id AND rqd.product_id = p.ID AND p.post_author = %d';
            $data[]  = $vendor_id;
        }

        $where = implode( ' AND ', $where );

        return $wpdb->get_row(
            $wpdb->prepare(
            // phpcs:ignore
                "SELECT rq.* FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p  WHERE {$where}",
                $data
            )
        );
    }

    /**
     * Dokan get all request quote details.
     *
     * @since 3.6.0
     *
     * @param $rule_id
     *
     * @return array|object|null
     */
    public static function get_quote_rule_by_id( $rule_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dokan_request_quote_rules WHERE id = %d",
                $rule_id
            )
        );
    }

    /**
     * Get the count of total quotes
     *
     * @since 3.6.0
     *
     * @return object
     */
    public static function get_request_quote_count() {
        global $wpdb;

        $results             = (array) $wpdb->get_results( "SELECT status, count(id) as num_quotes FROM {$wpdb->prefix}dokan_request_quotes GROUP BY status", ARRAY_A );
        $counts              = array_fill_keys( get_post_stati(), 0 );
        $counts['approve'] = 0;
        $counts['converted'] = 0;
        foreach ( $results as $row ) {
            $counts[ $row['status'] ] = $row['num_quotes'];
        }

        return (object) $counts;
    }

    /**
     * Get the count of total quotes
     *
     * @since 3.6.0
     *
     * @return object
     */
    public static function get_quote_rules_count() {
        global $wpdb;

        $results = (array) $wpdb->get_results( "SELECT `status`, count(id) as num_quotes FROM {$wpdb->prefix}dokan_request_quote_rules GROUP BY status", ARRAY_A );
        $counts  = array_fill_keys( get_post_stati(), 0 );
        foreach ( $results as $row ) {
            $counts[ $row['status'] ] = $row['num_quotes'];
        }

        return (object) $counts;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function create_request_quote( $args ) {
        global $wpdb;

        if ( empty( $args['quote_title'] ) ) {
            return new \WP_Error( 'no-name', __( 'You must provide a name.', 'dokan' ) );
        }

        if ( ! empty( $args['customer_info'] ) ) {
            $args['customer_info'] = maybe_serialize( $args['customer_info'] );
        }

        $defaults = [
            'user_id'       => 0,
            'order_id'      => 0,
            'quote_title'   => '',
            'customer_info' => '',
            'status'        => 'pending',
            'created_at'    => dokan_current_datetime()->getTimestamp(),
        ];

        $data = self::trim_extra_params( $args, $defaults );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dokan_request_quotes',
            $data,
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ( ! $inserted ) {
            return new \WP_Error( 'failed-to-insert', __( 'Failed to insert data', 'dokan' ) );
        }

        return $wpdb->insert_id;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function create_quote_rule( $args ) {
        global $wpdb;

        if ( empty( $args['rule_name'] ) ) {
            return new \WP_Error( 'no-name', __( 'You must provide a name.', 'dokan' ) );
        }

        $defaults = [
            'vendor_id'            => get_current_user_id(),
            'rule_name'            => '',
            'hide_price'           => 0,
            'hide_price_text'      => '',
            'hide_cart_button'     => 'replace',
            'apply_on_all_product' => 0,
            'button_text'          => __( 'Add to quote', 'dokan' ),
            'rule_priority'        => 0,
            'rule_contents'        => [],
            'status'               => 'publish',
            'created_at'           => dokan_current_datetime()->getTimestamp(),
        ];

        $data = self::trim_extra_params( $args, $defaults );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dokan_request_quote_rules',
            $data,
            [
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ( ! $inserted ) {
            return new \WP_Error( 'failed-to-insert', __( 'Failed to insert data', 'dokan' ) );
        }

        return $wpdb->insert_id;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function create_request_quote_details( $args ) {
        global $wpdb;

        if ( empty( $args['quote_id'] ) ) {
            return new \WP_Error( 'no-name', __( 'Requested details can\'t be saved.', 'dokan' ) );
        }

        if ( empty( $args['product_id'] ) ) {
            return new \WP_Error( 'no-name', __( 'No products found to save', 'dokan' ) );
        }

        $defaults = [
            'quote_id'    => '',
            'product_id'  => [],
            'quantity'    => 0,
            'offer_price' => [],
        ];

        $data     = wp_parse_args( $args, $defaults );
        $inserted = false;
        if ( 0 !== $data['quantity'] ) {
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'dokan_request_quote_details',
                $data,
                [
                    '%d',
                    '%d',
                    '%d',
                    '%f',
                ]
            );
        }

        if ( ! $inserted ) {
            return new \WP_Error( 'failed-to-insert', __( 'Failed to insert data', 'dokan' ) );
        }

        return $wpdb->insert_id;
    }

    /**
     * Change status.
     *
     * @since 3.6.0
     *
     * @param string $table_name
     * @param int    $id
     * @param string $status
     *
     * @return bool|int|\WP_Error
     */
    public static function change_status( $table_name, $id, $status = 'pending' ) {
        global $wpdb;

        $data['status'] = $status;

        $updated = $wpdb->update(
            $wpdb->prefix . "{$table_name}",
            $data,
            [ 'id' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( ! $updated ) {
            return new \WP_Error( 'failed-to-update', __( 'Failed to update data', 'dokan' ) );
        }

        return $updated;
    }

    /**
     * Update dokan request quote converted.
     *
     * @since 3.6.0
     *
     * @param $quote_id
     * @param $converted_by
     * @param $order_id
     *
     * @return bool|int|\WP_Error
     */
    public static function update_dokan_request_quote_converted( $quote_id, $converted_by, $order_id = 0 ) {
        global $wpdb;

        $updated = $wpdb->update(
            $wpdb->prefix . 'dokan_request_quotes',
            [
                'converted_by' => $converted_by,
                'order_id'     => $order_id,
            ],
            [ 'id' => $quote_id ],
            [ '%s', '%d' ],
            [ '%d' ]
        );

        if ( ! $updated ) {
            return new \WP_Error( 'failed-to-update', __( 'Failed to update data', 'dokan' ) );
        }

        return $updated;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function update_request_quote( $quote_id, $args ) {
        global $wpdb;

        $defaults = [
            'user_id'       => 0,
            'order_id'      => 0,
            'quote_title'   => '',
            'customer_info' => '',
            'status'        => 'pending',
            'created_at'    => dokan_current_datetime()->getTimestamp(),
            'converted_by'  => 'Admin',
            'updated_at'    => dokan_current_datetime()->getTimestamp(),
        ];

        $data = self::trim_extra_params( $args, $defaults );

        $updated = $wpdb->update(
            $wpdb->prefix . 'dokan_request_quotes',
            $data,
            [ 'id' => $quote_id ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ],
            [ '%d' ]
        );

        if ( ! $updated ) {
            return new \WP_Error( 'failed-to-update', __( 'Failed to update data', 'dokan' ) );
        }

        return $updated;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function update_quote_rule( $rule_id, $args ) {
        global $wpdb;

        $defaults = [
            'rule_name'            => '',
            'hide_price'           => 0,
            'hide_price_text'      => '',
            'hide_cart_button'     => 'replace',
            'apply_on_all_product' => 0,
            'button_text'          => __( 'Add to quote', 'dokan' ),
            'rule_priority'        => 0,
            'rule_contents'        => '',
            'status'               => 'publish',
            'created_at'           => dokan_current_datetime()->getTimestamp(),
        ];

        $data = self::trim_extra_params( $args, $defaults );

        $updated = $wpdb->update(
            $wpdb->prefix . 'dokan_request_quote_rules',
            $data,
            [ 'id' => $rule_id ],
            [
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
            ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            return new \WP_Error( 'failed-to-update', __( 'Failed to update data', 'dokan' ) );
        }

        return $updated;
    }

    /**
     * Trash/Delete with an id form given table.
     *
     * @since 3.6.0
     *
     * @param string $key
     * @param int    $id
     * @param string $where
     * @param false  $force
     *
     * @return bool|int
     */
    public static function delete( $key, $id, $where, $force = false ) {
        global $wpdb;

        $table_name = '';
        if ( 'quote_rules' === $key ) {
            $table_name = 'dokan_request_quote_rules';
        } elseif ( 'quotes' === $key ) {
            $table_name = 'dokan_request_quotes';
        } elseif ( 'quote_details' === $key ) {
            $table_name = 'dokan_request_quote_details';
        }

        if ( empty( $table_name ) ) {
            return false;
        }

        if ( $force ) {
            return $wpdb->delete(
                $wpdb->prefix . "{$table_name}",
                [ "{$where}" => $id ],
                [ '%d' ]
            );
        }

        $data['status'] = 'trash';

        return $wpdb->update(
            $wpdb->prefix . "{$table_name}",
            $data,
            [ "{$where}" => $id ],
            [
                '%s',
            ],
            [ '%d' ]
        );
    }

    /**
     * Convert quote to_order.
     *
     * @since 3.6.0
     *
     * @param mixed $quote
     * @param mixed $quote_details
     *
     * @throws \Exception
     * @return void|int
     */
    public static function convert_quote_to_order( $quote, $quote_details ) {
        if ( empty( $quote_details ) ) {
            return;
        }

        // Now we create the order
        $quote_order = new \WC_Order();

        foreach ( $quote_details as $quote_detail ) {
            $product       = wc_get_product( $quote_detail->product_id );
            $price         = $product->get_price();
            $offered_price = isset( $quote_detail->offer_price ) ? floatval( $quote_detail->offer_price ) : $price;

            $product->set_price( $offered_price );

            $quote_order->add_product( $product, $quote_detail->quantity );
        }

        if ( ! empty( intval( $quote->user_id ) ) ) {
            $customer = new WC_Customer( $quote->user_id );
        } else {
            $customer = new WC_Customer();
        }
        $customer_billing  = $customer->get_billing();
        $customer_shipping = $customer->get_shipping();

        $quote_order->set_address( $customer_billing, 'billing' );
        $quote_order->set_address( $customer_shipping, 'shipping' );

        $quote_order->set_customer_id( intval( $quote->user_id ) );
        $quote_order->set_customer_note( __( 'Created by converting quote to order.', 'dokan' ) );
        $quote_order->set_created_via( __( 'Dokan Request Quote.', 'dokan' ) );

        $quote_order->calculate_totals();
        $order_id = $quote_order->save();
        dokan_sync_insert_order( $order_id );

        // Sending the new order email
        $email_new_order = WC()->mailer()->get_emails()['WC_Email_Customer_On_Hold_Order'];
        $email_new_order->trigger( $order_id, $quote_order );

        return $order_id;

        // If there is any possibility of more than one vendor then use this /do_action( 'woocommerce_checkout_update_order_meta', $order_id );/ to split orders.
    }

    /**
     * Get the quote subtotal.
     *
     * @since 3.6.0
     *
     * @param array $contents
     *
     * @return int[] formatted price
     */
    public function get_calculated_totals( $contents = [] ) {
        $quote_totals = [
            '_subtotal'      => 0,
            '_offered_total' => 0,
            '_tax_total'     => 0,
            '_total'         => 0,
        ];

        if ( empty( $contents ) ) {
            $quote_session = Session::init();
            $contents = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );
        }

        if ( empty( $contents ) ) {
            return $quote_totals;
        }

        foreach ( $contents as $quote_item_key => $quote_item ) {
            if ( ! isset( $quote_item['data'] ) || ! is_object( $quote_item['data'] ) ) {
                continue;
            }

            $product       = $quote_item['data'];
            $quantity      = $quote_item['quantity'];
            $price         = empty( $quote_item['addons_price'] ) ? floatval( $product->get_price() ) : floatval( $quote_item['addons_price'] );
            $offered_price = isset( $quote_item['offered_price'] ) ? floatval( $quote_item['offered_price'] ) : $price;

            $quote_totals['_offered_total'] += $offered_price * intval( $quantity );

            if ( ! $product->is_taxable() ) {
                $product_subtotal           = $price * $quantity;
                $quote_totals['_subtotal']  += $product_subtotal;
                $quote_totals['_tax_total'] += 0;
                continue;
            }

            if ( ! wc_prices_include_tax() ) {
                $product_subtotal = wc_get_price_including_tax(
                    $product,
                    [
                        'qty'   => $quantity,
                        'price' => $price,
                    ]
                );
            } else {
                $product_subtotal = wc_get_price_excluding_tax(
                    $product, [
                        'qty'   => $quantity,
                        'price' => $price,
                    ]
                );
            }

            $difference_price = ( $price * $quantity ) - $product_subtotal;

            if ( $difference_price < 0 ) {
                $difference_price = $difference_price * - 1;
            }

            $quote_totals['_subtotal']  += $price * $quantity;
            $quote_totals['_tax_total'] += $difference_price;
        }

        $quote_totals['_total'] = $quote_totals['_subtotal'] + $quote_totals['_tax_total'];

        return $quote_totals;
    }

    /**
     * This method will check if quote is enabled for catalog mode
     *
     * @since 3.7.4
     *
     * @param int $vendor_id
     *
     * @return bool
     */
    public static function is_quote_support_disabled_for_catalog_mode( $vendor_id = 0 ) {
        if ( ! class_exists( '\WeDevs\Dokan\CatalogMode\Helper' ) ) {
            return true; // catalog mode is not available, so load the quote template
        }

        // check if admin enabled catalog mode
        if ( ! \WeDevs\Dokan\CatalogMode\Helper::is_enabled_by_admin() ) {
            return true; // catalog mode is not enabled, so load the quote template
        }

        if ( ! $vendor_id ) {
            $vendor_id = dokan_get_current_user_id();
        }

        $settings = \WeDevs\Dokan\CatalogMode\Helper::get_vendor_catalog_mode_settings( $vendor_id );

        return 'off' === $settings['request_a_quote_enabled'];
    }

    /**
     * Extra params trimmer method.
     *
     * @since 3.7.14
     *
     * @param array $args     Passed arguments
     * @param array $defaults Default arguments
     *
     * @return array
     */
    public static function trim_extra_params( $args, $defaults ) {
        $filtered_args = array_filter( $args, function( $key ) use ( $defaults ) {
            return array_key_exists( $key, $defaults );
        }, ARRAY_FILTER_USE_KEY );

        return wp_parse_args( $filtered_args, $defaults );
    }

    /**
     * Returns site administrators id
     *
     * @since 3.9.3
     *
     * @return int
     */
    protected static function get_site_admin_id(): int {
        $administrator = current( get_users( [ 'role' => 'administrator' ] ) );
        return ! empty( $administrator ) ? $administrator->ID: 0;
    }

    /**
     * Creates add to quote page
     *
     * @since 3.9.3
     *
     * @return int
     */
    protected static function create_add_to_quote_page(): int {

        $new_page = [
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => static::get_site_admin_id(),
            'post_name'      => 'request-quote',
            'post_title'     => 'Request for Quote',
            'post_content'   => '[dokan-request-quote]',
            'post_parent'    => 0,
            'comment_status' => 'closed',
        ];
        $page_id = wp_insert_post( $new_page );
        $page_id = is_wp_error( $page_id ) ? 0: $page_id;
        update_option( 'dokan_request_quote_page_id', $page_id );
        return $page_id;
    }

    /**
     * Returns quote page id, creates is necessary
     *
     * @since 3.9.3
     *
     * @return int
     */
    public static function get_quote_page_id(): int {
        $page_id = get_option( 'dokan_request_quote_page_id', 0 );
        $page    = get_post( $page_id );

        if ( ! $page || 'publish' !== $page->post_status ) {
            $page_id = static::create_add_to_quote_page();
        }

        return apply_filters( 'dokan_get_translated_page_id', $page_id );
    }
}
