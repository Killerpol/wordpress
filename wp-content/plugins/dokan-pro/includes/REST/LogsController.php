<?php

namespace WeDevs\DokanPro\REST;

use WeDevs\DokanPro\Admin\ReportLogExporter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WeDevs\Dokan\Abstracts\DokanRESTAdminController;

class LogsController extends DokanRESTAdminController {

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'logs';

    /**
     * Register all routes related with logs
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base, [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_logs' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => array_merge(
                        $this->get_collection_params(),
                        $this->get_logs_params()
                    ),
                ],
            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->base . '/export', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'export_logs' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => array_merge(
                        $this->get_collection_params(),
                        $this->get_logs_params()
                    ),
                ],
            ]
        );
    }

    /**
     * Retrieves the query params for the collections.
     *
     * @since 3.4.1
     *
     * @return array Query parameters for the collection.
     */
    public function get_logs_params() {
        return [
            'vendor_id'    => [
                'description'       => 'Vendor IDs to filter form',
                'type'              => [ 'array', 'integer' ],
                'default'           => [],
                'validate_callback' => 'rest_validate_request_arg',
                'items'             => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
            'order_id'     => [
                'description'       => 'Order IDs to filter form',
                'type'              => [ 'array', 'integer' ],
                'default'           => [],
                'validate_callback' => 'rest_validate_request_arg',
                'items'             => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
            'order_status' => [
                'description' => 'Order status to filter form',
                'required'    => false,
                'type'        => [ 'string', 'array' ],
                'default'     => '',
            ],
            'orderby'      => [
                'description' => 'Filter by column',
                'required'    => false,
                'type'        => 'string',
                'default'     => 'order_id',
            ],
            'order'        => [
                'description' => 'Order by type',
                'required'    => false,
                'type'        => 'string',
                'enum'        => [ 'desc', 'asc' ],
                'default'     => 'desc',
            ],
            'return'       => [
                'description' => 'How data will be returned',
                'type'        => 'string',
                'required'    => false,
                'enum'        => [ 'all', 'ids', 'count' ],
                'context'     => [ 'view' ],
                'default'     => 'all',
            ],
        ];
    }

    /**
     * Get all logs
     *
     * @since 2.9.4
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_logs( $request ) {
        $params = $request->get_params();

        $params['return'] = 'count';
        $items_count = dokan_pro()->reports->get_logs( $params );
        if ( is_wp_error( $items_count ) ) {
            return $items_count->get_error_message();
        }

        $params['return'] = 'ids';
        $results = dokan_pro()->reports->get_logs( $params );
        $logs    = $this->prepare_logs_data( $results, $request );

        $response = rest_ensure_response( $logs );
        $response = $this->format_collection_response( $response, $request, $items_count );

        return $response;
    }

    /**
     * Export all logs, send a json response after writing chunk data in file
     *
     * @since 3.4.1
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function export_logs( $request ) {
        include_once DOKAN_PRO_INC . '/Admin/ReportLogExporter.php';

        $params = $request->get_params();
        $params['return'] = 'ids';
        $step   = isset( $params['page'] ) ? absint( $params['page'] ) : 1; // phpcs:ignore
        $logs   = $this->prepare_logs_data( dokan_pro()->reports->get_logs( $params ), $request );

        // get counts
        $params['return'] = 'count';
        $items_count = dokan_pro()->reports->get_logs( $params );

        $exporter = new ReportLogExporter();
        $exporter->set_items( $logs );
        $exporter->set_page( $step );
        $exporter->set_limit( $params['per_page'] );
        $exporter->set_total_rows( $items_count );
        $exporter->generate_file();

        if ( $exporter->get_percent_complete() >= 100 ) {
            return rest_ensure_response(
                [
                    'step'       => 'done',
                    'percentage' => 100,
                    'url'        => add_query_arg(
                        [
                            'download-order-log-csv' => wp_create_nonce( 'download-order-log-csv-nonce' ),
                        ], admin_url( 'admin.php' )
                    ),
                ]
            );
        } else {
            return rest_ensure_response(
                [
                    'step'       => ++$step,
                    'percentage' => $exporter->get_percent_complete(),
                    'columns'    => $exporter->get_column_names(),
                ]
            );
        }
    }

    /**
     * Prepare Log items for response
     *
     * @param mixed           $results
     * @param WP_REST_Request $request
     *
     * @return array
     */
    public function prepare_logs_data( $results, $request ) {
        global $wpdb;
        $logs     = [];
        $statuses = wc_get_order_statuses();

        foreach ( $results as $order_id ) {
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$wpdb->prefix}dokan_orders
                    WHERE order_id = %d",
                    $order_id
                )
            );

            if ( ! $result ) {
                continue;
            }

            $is_subscription_product = false;

            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }

            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();

                if ( $product && 'product_pack' === $product->get_type() ) {
                    $is_subscription_product = true;
                    break;
                }
            }

            $order_total    = $order->get_total();
            $has_refund     = (bool) $order->get_total_refunded();
            $total_shipping = $order->get_shipping_total() ? $order->get_shipping_total() : 0;

            $total_shipping_tax = $order->get_shipping_tax();

            $tax_totals = 0;
            if ( $order->get_tax_totals() ) :
                foreach ( $order->get_tax_totals() as $tax ) :
                    $tax_totals = $tax_totals + $tax->amount;
                endforeach;
            endif;

            $tax_totals -= $order->get_shipping_tax();

            $shipping_tax_refunded_total = dokan()->commission->get_total_shipping_tax_refunded( $order );
            $tax_refunded_total          = $order->get_total_tax_refunded() - $shipping_tax_refunded_total;
            $shipping_refunded_total     = $order->get_total_shipping_refunded();

            /**
             * Payment gateway fee minus from admin commission earning
             * net amount is excluding gateway fee, so we need to deduct it from the admin commission
             * otherwise the admin commission will be including gateway fees
             */
            $is_subscription_product = apply_filters( 'dokan_log_exclude_commission', $is_subscription_product, $result );
            $processing_fee          = (float) $order->get_meta( 'dokan_gateway_fee' );
            $commission              = $is_subscription_product ? (float) $result->order_total : (float) $result->order_total - (float) $result->net_amount;

            if ( $processing_fee && $processing_fee > 0 ) {
                $commission = $commission - $processing_fee;
            }

            /**
             * In case of refund, we are not excluding gateway fee; in case of stripe full/partial refund net amount can be negative
             */
            if ( $commission < 0 ) {
                $commission = 0;
            }

            $dp = 2; // 2 decimal points

            $gateway_fee_paid_by = $order->get_meta( 'dokan_gateway_fee_paid_by', true );

            if ( ! empty( $processing_fee ) && empty( $gateway_fee_paid_by ) ) {
                /**
                 * @since 3.7.15 dokan_gateway_fee_paid_by meta key returns empty value if gateway fee is paid admin
                 */
                $gateway_fee_paid_by = $order->get_payment_method() === 'dokan-stripe-connect' ? 'admin' : 'seller';
            }

            if ( method_exists( dokan()->commission, 'get_shipping_tax_fee_recipient' ) ) {
                $shipping_tax_recipient = dokan()->commission->get_shipping_tax_fee_recipient( $order );
            } else {
                $shipping_tax_recipient = dokan()->commission->get_tax_fee_recipient( $result->order_id );
            }

            $logs[] = [
                'order_id'                    => $result->order_id,
                'vendor_id'                   => $result->seller_id,
                'vendor_name'                 => dokan()->vendor->get( $result->seller_id )->get_shop_name(),
                'previous_order_total'        => wc_format_decimal( $order_total, $dp ),
                'order_total'                 => wc_format_decimal( $result->order_total, $dp ),
                'vendor_earning'              => $is_subscription_product ? 0 : wc_format_decimal( $result->net_amount, $dp ),
                'commission'                  => wc_format_decimal( $commission, $dp ),
                'dokan_gateway_fee'           => $processing_fee ? wc_format_decimal( $processing_fee, $dp ) : 0,
                'gateway_fee_paid_by'         => $gateway_fee_paid_by ? $gateway_fee_paid_by : '',
                'shipping_total'              => wc_format_decimal( $total_shipping, $dp ),
                'shipping_total_refunded'     => wc_format_decimal( $shipping_refunded_total, $dp ),
                'shipping_total_remains'      => wc_format_decimal( $total_shipping - $shipping_refunded_total, $dp ),
                'has_shipping_refund'         => ! empty( $shipping_refunded_total ),
                'shipping_total_tax'          => wc_format_decimal( $total_shipping_tax, $dp ),
                'shipping_total_tax_refunded' => wc_format_decimal( $shipping_tax_refunded_total, $dp ),
                'shipping_total_tax_remains'  => wc_format_decimal( $total_shipping_tax - $shipping_tax_refunded_total, $dp ),
                'has_shipping_tax_refund'     => ! empty( $shipping_tax_refunded_total ),
                'tax_total'                   => wc_format_decimal( $tax_totals, $dp ),
                'tax_total_refunded'          => wc_format_decimal( $tax_refunded_total, $dp ),
                'tax_total_remains'           => wc_format_decimal( $tax_totals - $tax_refunded_total, $dp ),
                'has_tax_refund'              => ! empty( $tax_refunded_total ),
                'status'                      => $statuses[ $result->order_status ],
                'date'                        => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
                'has_refund'                  => $has_refund,
                'shipping_recipient'          => dokan()->commission->get_shipping_fee_recipient( $result->order_id ),
                'shipping_tax_recipient'      => $shipping_tax_recipient,
                'tax_recipient'               => dokan()->commission->get_tax_fee_recipient( $result->order_id ),
            ];
        }

        return $logs;
    }
}
