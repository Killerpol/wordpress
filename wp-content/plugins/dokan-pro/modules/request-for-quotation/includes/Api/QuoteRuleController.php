<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Api;

use WP_Error;
use WP_REST_Server;
use WeDevs\Dokan\Abstracts\DokanRESTController;
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;

/**
 * Request A Quote Controller Class
 *
 * @since 3.6.0
 */
class QuoteRuleController extends DokanRESTController {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1/request-for-quote';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'quote-rule';

    /**
     * Register all request quote route
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base, [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_dokan_quotes_rules' ],
                    'args'                => $this->get_collection_params(),
                    'permission_callback' => [ $this, 'get_quote_rule_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_quote_rule' ],
                    'permission_callback' => [ $this, 'create_quote_rule_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ],
                'schema' => [ $this, 'get_rule_item_schema' ],
            ]
        );
        register_rest_route(
            $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)/', [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the object.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_dokan_single_quote_rule' ],
                    'permission_callback' => [ $this, 'get_quote_rule_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_quote_rule' ],
                    'permission_callback' => [ $this, 'get_quote_rule_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ],

                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_quote_rule' ],
                    'permission_callback' => [ $this, 'get_quote_rule_permissions_check' ],
                ],

            ]
        );
        register_rest_route(
            $this->namespace, '/' . $this->base . '/batch', [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'quote_rule_batch_items' ],
                    'permission_callback' => [ $this, 'batch_items_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)/restore', [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the object.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'restore_quote_rule' ],
                    'permission_callback' => [ $this, 'restore_quote_rule_permissions_check' ],
                ],
            ]
        );
    }

    /**
     * Get single object
     *
     * @since 3.6.0
     *
     * @return array|null
     */
    public function get_quote_rule_object_by_id( $rule_id ) {
        return Helper::get_quote_rule_by_id( $rule_id );
    }

    /**
     * Get all request_quote
     *
     * @since 3.6.0
     *
     * @param mixed $request
     *
     * @return object
     */
    public function get_dokan_quotes_rules( $request ) {
        $status  = ( empty( $request['status'] ) || $request['status'] === 'all' ) ? '' : $request['status'];
        $limit   = empty( $request['per_page'] ) ? 10 : $request['per_page'];
        $order   = empty( $request['order'] ) ? 'ASC' : $request['order'];
        $orderby = empty( $request['orderby'] ) ? 'id' : $request['orderby'];
        $offset  = empty( $request['page'] ) ? 1 : ( $request['page'] - 1 ) * $limit;

        $args = [
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'status'         => $status,
            'order'          => $order,
            'orderby'        => $orderby,
        ];

        $data = Helper::get_quote_rules( $args );

        $result = [];
        if ( ! empty( $data ) ) {
            foreach ( $data as $key => $value ) {
                $res      = $this->prepare_response_for_rule_object( $value, $request );
                $result[] = $this->prepare_response_for_collection( $res );
            }
        }

        $response = rest_ensure_response( $result );
        $count    = Helper::get_quote_rules_count();

        $response->header( 'X-Status-All', ( $count->publish + $count->trash + $count->draft ) );
        $response->header( 'X-Status-Publish', $count->publish );
        $response->header( 'X-Status-Trash', $count->trash );
        $response->header( 'X-Status-Draft', $count->draft );

        $found_post = $count->publish + $count->trash + $count->draft;

        return $this->format_collection_response( $response, $request, $found_post );
    }

    /**
     * Create request_quote
     *
     * @since 3.6.0
     *
     * @return \WP_Error
     */
    public function create_quote_rule( $request ) {
        $params                         = $request->get_params();
        $rule_contents['product_ids']   = [];
        $rule_contents['category_ids']  = [];
        $params['apply_on_all_product'] = ( 'true' === $params['apply_on_all_product'] || '1' === $params['apply_on_all_product'] ) ? 1 : 0;
        if ( ! empty( $params['apply_on_all_product'] ) || 1 !== $params['apply_on_all_product'] ) {
            $rule_contents['product_ids']  = ! empty( $params['product_ids'] ) ? array_map( 'absint', wp_unslash( $params['product_ids'] ) ) : '';
            $rule_contents['category_ids'] = ! empty( $params['category_ids'] ) ? array_map( 'absint', wp_unslash( $params['category_ids'] ) ) : '';
        }

        $rule_contents['selected_user_role'] = ! empty( $params['selected_user_role'] ) ? array_map( 'sanitize_text_field', wp_unslash( $params['selected_user_role'] ) ) : '';
        $params['rule_contents']             = maybe_serialize( $rule_contents );

        if ( empty( $params['selected_user_role'] ) ) {
            return new WP_Error( 'select-no-user-role', __( 'You must select at least one role.', 'dokan' ), [ 'status' => 400 ] );
        }

        $params['vendor_id'] = get_current_user_id();
        $quote_rule          = Helper::create_quote_rule( $params );
        if ( is_wp_error( $quote_rule ) ) {
            return new WP_Error( $quote_rule->get_error_code(), $quote_rule->get_error_message(), [ 'status' => 404 ] );
        }

        $data = $this->prepare_response_for_rule_object( (object) $this->get_quote_rule_object_by_id( (int) $quote_rule ), $request );

        return rest_ensure_response( $data );
    }

    /**
     * Get quote rule permissions check.
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function get_quote_rule_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Get quote rule permissions check.
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function batch_items_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Get restore request_quote permissions check
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function restore_quote_rule_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Create request_quote permissions check
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function create_quote_rule_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Prepare data for response
     *
     * @since 3.6.0
     *
     * @return array
     */
    public function prepare_response_for_rule_object( $object, $request ) {
        $rule_contents = maybe_unserialize( $object->rule_contents );

        $data = [
            'id'                   => $object->id,
            'rule_name'            => $object->rule_name,
            'selected_user_role'   => ! empty( $rule_contents['selected_user_role'] && is_array( $rule_contents['selected_user_role'] ) ) ? implode( ', ', $rule_contents['selected_user_role'] ) : '',
            'category_ids'         => ! empty( $rule_contents['category_ids'] ) ? $rule_contents['category_ids'] : '',
            'product_ids'          => ! empty( $rule_contents['product_ids'] ) ? $rule_contents['product_ids'] : '',
            'hide_price'           => ( (int) $object->hide_price === 1 ) ? __( 'Yes', 'dokan' ) : __( 'No', 'dokan' ),
            'hide_cart_button'     => $object->hide_cart_button,
            'button_text'          => $object->button_text,
            'apply_on_all_product' => $object->apply_on_all_product,
            'rule_priority'        => $object->rule_priority,
            'status'               => $object->status,
            'created_at'           => dokan_format_date( $object->created_at ),
        ];

        $response = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $object, $request ) );

        return apply_filters( 'dokan_rest_prepare_quote_rule_object', $response, $object, $request );
    }

    /**
     * Prepare data for response
     *
     * @since 3.6.0
     *
     * @return array
     */
    public function prepare_response_for_single_rule_object( $object, $request ) {
        $rule_contents = maybe_unserialize( $object->rule_contents );

        $products = [];
        if ( ! empty( $rule_contents['product_ids'] ) ) {
            foreach ( $rule_contents['product_ids'] as $key => $product ) {
                $prod                   = wc_get_product( $product )->get_data();
                $products[ $key ]['id']   = $prod['id'];
                $products[ $key ]['name'] = $prod['name'];
            }
        }

        $data = [
            'id'                   => $object->id,
            'rule_name'            => $object->rule_name,
            'selected_user_role'   => ! empty( $rule_contents['selected_user_role'] ) ? $rule_contents['selected_user_role'] : [],
            'category_ids'         => ! empty( $rule_contents['category_ids'] ) ? $rule_contents['category_ids'] : [],
            'product_ids'          => $products,
            'hide_price'           => $object->hide_price,
            'hide_price_text'      => $object->hide_price_text,
            'hide_cart_button'     => $object->hide_cart_button,
            'button_text'          => $object->button_text,
            'apply_on_all_product' => $object->apply_on_all_product,
            'rule_priority'        => $object->rule_priority,
            'status'               => $object->status,
            'created_at'           => dokan_format_date( $object->created_at ),
        ];

        $response = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $object, $request ) );

        return apply_filters( 'dokan_rest_prepare_quote_rule_object', $response, $object, $request );
    }

    /**
     * Prepare links for the request.
     *
     * @param mixed            $object  \WC_Data Object data.
     * @param \WP_REST_Request $request Request object.
     *
     * @return array                   Links for the given post.
     */
    protected function prepare_links( $object, $request ) {
        return [
            'self'       => [
                'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->base, $object->id ) ),
            ],
            'collection' => [
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->base ) ),
            ],
        ];
    }

    /**
     * Get collection params.
     *
     * @since 3.6.0
     *
     * @return array
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();
        $params = array_merge(
            $params,
            [
                'status' => [
                    'type'        => 'string',
                    'description' => __( 'Request Quote status', 'dokan' ),
                    'required'    => false,
                ],
            ]
        );
        unset( $params['search'] );

        return $params;
    }

    /**
     * Retrieves the contact schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_rule_item_schema() {
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'contact',
            'type'       => 'object',
            'properties' => [
                'id'                   => [
                    'description' => __( 'Unique identifier for the object.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'rule_name'            => [
                    'description'       => __( 'Name of the rule', 'dokan' ),
                    'type'              => 'string',
                    'context'           => [ 'view', 'edit' ],
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'selected_user_role' => [
                    'type'              => 'array',
                    'description'       => __( 'Rule apply for users.', 'dokan' ),
                    'required'          => true,
                    'context'           => [ 'edit' ],
                    'sanitize_callback' => 'wc_clean',
                    'minItems'          => 1,
                    'items'             => array(
                        'type' => 'string',
                    ),
                ],
                'category_ids' => [
                    'description'       => __( 'Product categories', 'dokan' ),
                    'type'              => 'array',
                    'required'          => false,
                    'context'           => [ 'edit' ],
                    'sanitize_callback' => 'wc_clean',
                    'items'             => array(
                        'type' => 'integer',
                    ),
                ],
                'product_ids' => [
                    'type'              => 'array',
                    'description'       => __( 'Product ids', 'dokan' ),
                    'required'          => false,
                    'context'           => [ 'edit' ],
                    'sanitize_callback' => 'wc_clean',
                    'items'             => array(
                        'type' => 'integer',
                    ),
                ],
                'rule_priority' => [
                    'type'        => 'number',
                    'description' => __( 'Rule priority', 'dokan' ),
                    'required'    => false,
                    'context'           => [ 'view', 'edit' ],
                ],
                'hide_price' => [
                    'type'        => 'boolean',
                    'description' => __( 'Hide price', 'dokan' ),
                    'required'    => true,
                    'context'           => [ 'view', 'edit' ],
                ],
                'hide_price_text' => [
                    'type'              => 'string',
                    'description'       => __( 'Hide price text', 'dokan' ),
                    'required'          => true,
                    'context'           => [ 'edit' ],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'hide_cart_button' => [
                    'type'              => 'string',
                    'description'       => __( 'Hide cart button', 'dokan' ),
                    'required'          => true,
                    'context'           => [ 'edit' ],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'button_text' => [
                    'type'              => 'string',
                    'description'       => __( 'Button text', 'dokan' ),
                    'required'          => true,
                    'context'           => [ 'view', 'edit' ],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'apply_on_all_product' => [
                    'type'        => 'boolean',
                    'description' => __( 'Apply on all product', 'dokan' ),
                    'required'    => true,
                    'context'     => [ 'view', 'edit' ],
                ],
                'status' => [
                    'description' => __( 'Status of the rule.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'created_at'           => [
                    'description' => __( "The date the object was published, in the site's timezone.", 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema( $this->schema );
    }

    /**
     * Get single request_quote object
     *
     * @since 3.6.0
     *
     * @return \WP_Error
     */
    public function get_dokan_single_quote_rule( $request ) {
        $rule_id = $request['id'];

        if ( empty( $rule_id ) ) {
            return new WP_Error( 'no_qupte_found', __( 'No quote found', 'dokan' ), [ 'status' => 404 ] );
        }

        $data   = (object) $this->get_quote_rule_object_by_id( (int) $rule_id );
        $result = [];
        if ( ! empty( $data ) ) {
            $res    = $this->prepare_response_for_single_rule_object( (object) $data, $request );
            $result = $this->prepare_response_for_collection( $res );
        }

        return rest_ensure_response( $result );
    }

    /**
     * Update rule
     *
     * @since 3.6.0
     *
     * @return \WP_Error
     */
    public function update_quote_rule( $request ) {
        if ( isset( $request['rule_name'] ) && empty( trim( $request['rule_name'] ) ) ) {
            return new WP_Error( 'no_title', __( 'Rule name is required', 'dokan' ), [ 'status' => 404 ] );
        }

        $rule_id   = ! empty( trim( $request['id'] ) ) ? trim( $request['id'] ) : '';
        $rule_data = (array) $this->get_quote_rule_object_by_id( $rule_id );

        if ( empty( $rule_id ) || ! is_array( $rule_data ) || empty( $rule_data ) ) {
            return new WP_Error( 'no_id', __( 'No rule id found', 'dokan' ), [ 'status' => 404 ] );
        }

        $params                         = $request->get_params();
        $params['apply_on_all_product'] = ( 'true' === $params['apply_on_all_product'] || '1' === $params['apply_on_all_product'] ) ? 1 : 0;
        $params['hide_price']           = (int) $params['hide_price'];
        $rule_contents['product_ids']   = [];
        $rule_contents['category_ids']  = [];
        if ( ! empty( $params['apply_on_all_product'] ) || 1 !== $params['apply_on_all_product'] ) {
            $rule_contents['product_ids']  = ! empty( $params['product_ids'] ) ? array_map( 'absint', array_unique( $params['product_ids'] ) ) : [];
            $rule_contents['category_ids'] = ! empty( $params['category_ids'] ) ? array_map( 'absint', array_unique( $params['category_ids'] ) ) : [];
        }

        $rule_contents['selected_user_role'] = ! empty( $params['selected_user_role'] ) ? $params['selected_user_role'] : [];
        $params['rule_contents']             = maybe_serialize( $rule_contents );

        if ( empty( $params['selected_user_role'] ) ) {
            return new WP_Error( 'select-no-user-role', __( 'You must select at least one role.', 'dokan' ), [ 'status' => 400 ] );
        }

        $request_quote = Helper::update_quote_rule( $rule_id, array_merge( $rule_data, $params ) );

        if ( is_wp_error( $request_quote ) ) {
            return new WP_Error( $request_quote->get_error_code(), $request_quote->get_error_message(), [ 'status' => 404 ] );
        }

        $data = $this->prepare_response_for_rule_object( (object) $this->get_quote_rule_object_by_id( (int) $rule_id ), $request );

        return rest_ensure_response( $data );
    }

    /**
     * Delete request_quote
     *
     * @since 3.6.0
     *
     * @return array|\WP_Error
     */
    public function delete_quote_rule( $request ) {
        $request_quote = $this->get_quote_rule_object_by_id( $request['id'] );

        if ( is_wp_error( $request_quote ) ) {
            return $request_quote;
        }

        $id       = $request_quote->id;
        $force    = ! empty( $request['force'] ) ? (bool) $request['force'] : false;
        $previous = $this->prepare_response_for_rule_object( $request_quote, $request );

        // If we're forcing, then delete permanently.
        $result = Helper::delete( 'quote_rules', $id, 'id', $force );

        if ( ! $result ) {
            return new WP_Error( 'dokan_rest_cannot_delete', __( 'The quote cannot be deleted.', 'dokan' ), [ 'status' => 500 ] );
        }

        return rest_ensure_response( $previous );
    }

    /**
     * Trash, delete and restore bulk action
     *
     * JSON data format for sending to API
     *     {
     *         "trash" : [
     *             "1", "9", "7"
     *         ],
     *         "delete" : [
     *             "2"
     *         ],
     *         "restore" : [
     *             "4"
     *         ]
     *     }
     *
     * @since 3.6.0
     *
     * @return bool|\WP_Error
     */
    public function quote_rule_batch_items( $request ) {
        $params = $request->get_params();

        if ( empty( $params ) ) {
            return new WP_Error( 'no_item_found', __( 'No items found for bulk updating', 'dokan' ), [ 'status' => 404 ] );
        }

        $allowed_status = [ 'trash', 'delete', 'restore' ];

        foreach ( $params as $status => $value ) {
            if ( ! in_array( $status, $allowed_status, true ) ) {
                continue;
            }

            switch ( $status ) {
                case 'delete':
                    foreach ( $value as $rule_id ) {
                        Helper::delete( 'quote_rules', $rule_id, 'id', true );
                    }
                    break;
                case 'trash':
                    foreach ( $value as $rule_id ) {
                        Helper::delete( 'quote_rules', $rule_id, 'id' );
                    }
                    break;
                case 'restore':
                    foreach ( $value as $rule_id ) {
                        Helper::change_status( 'dokan_request_quote_rules', $rule_id, 'draft' );
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Restore request_quote
     *
     * @since 3.6.0
     *
     * @return array|\WP_Error
     */
    public function restore_quote_rule( $request ) {
        if ( empty( trim( $request['id'] ) ) ) {
            return new WP_Error( 'no_id', __( 'No quote id found', 'dokan' ), [ 'status' => 404 ] );
        }

        $request_quote = $this->get_quote_rule_object_by_id( $request['id'] );

        if ( is_wp_error( $request_quote ) ) {
            return $request_quote;
        }

        $update = Helper::change_status( 'dokan_request_quote_rules', $request['id'], 'draft' );

        if ( ! $update ) {
            return new WP_Error( 'dokan_rest_cannot_delete', __( 'The quote not updated.', 'dokan' ), [ 'status' => 500 ] );
        }

        return $this->prepare_response_for_rule_object( $request_quote, $request );
    }

}
