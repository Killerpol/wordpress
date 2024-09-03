<?php
    use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;
?>
<table class='shop_table shop_table_responsive cart order_details quote_details dokan-table order-items'>
    <thead>
    <tr>
        <th class='product-thumbnail'>&nbsp;</th>
        <th class='product-name'><?php esc_html_e( 'Product', 'dokan' ); ?></th>
            <th class="product-price"><?php esc_html_e( 'Price', 'dokan' ); ?></th>
            <th class="product-price"><?php esc_html_e( 'Offered Price', 'dokan' ); ?></th>
        <th class="product-quantity"><?php esc_html_e( 'Quantity', 'dokan' ); ?></th>
            <th class="product-subtotal"><?php esc_html_e( 'Subtotal', 'dokan' ); ?></th>
            <th class="product-subtotal"><?php esc_html_e( 'Offered Subtotal', 'dokan' ); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $quote_subtotal = 0;
    $offered_total  = 0;
    foreach ( $quote_details as $quote_item ) {
        $_product          = wc_get_product( $quote_item->product_id );
        $price             = $_product->get_price();
        $offer_price       = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
        $product_permalink = $_product->is_visible() ? $_product->get_permalink() : '';
        ?>
        <tr>

            <td class='product-thumbnail'>
                <?php
                $thumbnail = $_product->get_image();

                if ( ! $product_permalink ) {
                    echo wp_kses_post( $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput
                } else {
                    printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) ); // phpcs:ignore WordPress.Security.EscapeOutput
                }
                ?>
            </td>

            <td data-title="<?php esc_attr_e( 'Product', 'dokan' ); ?>">
                <?php
                if ( ! $product_permalink ) {
                    echo wp_kses_post( $_product->get_name() . '&nbsp;' );
                } else {
                    echo wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ) );
                }
                ?>
                <br>
                <?php
                echo '<div><strong>' . esc_html__( 'SKU:', 'dokan' ) . '</strong> ' . esc_html( $_product->get_sku() ) . '</div>';

                $product_meta['product_id'] = $quote_item->product_id;
                $product_meta['data']       = $_product;
                if ( $_product->is_type( 'variation' ) ) {
                    $product_meta['variation'] = $_product->get_formatted_name();
                }
                // Meta data.
                echo wp_kses_post( wc_get_formatted_cart_item_data( $product_meta ) ); // phpcs:ignore WordPress.Security.EscapeOutput

                // Backorder notification.
                if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $quote_item->quantity ) ) {
                    echo wp_kses_post( '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'dokan' ) . '</p>' );
                }
                ?>
            </td>

                <td data-title="<?php esc_attr_e( 'Price', 'dokan' ); ?>">
                    <?php echo wp_kses_post( wc_price( $price ) ); ?>
                </td>
                <td data-title="<?php esc_attr_e( 'Offered Price', 'dokan' ); ?>">
                    <?php
                    if ( 'converted' === $quote->status ) :
                        echo wp_kses_post( wc_price( $offer_price ) );
                    else :
                        ?>
                        <input type='number' class='input-text offered-price-input text' step='any' name="offer_price[<?php echo esc_attr( $quote_item->product_id ); ?>]" value="<?php echo esc_attr( $offer_price ); ?>">
						<?php
                    endif;
                    ?>
                </td>
            <td data-title="<?php esc_attr_e( 'Quantity', 'dokan' ); ?>">
                <?php
                $qty_display = $quote_item->quantity;
                if ( 'converted' === $quote->status ) :
                    echo wp_kses_post( ' <strong class="product-quantity">' . sprintf( '&nbsp;%s', $qty_display ) . '</strong>' );
                else :
                    if ( $_product->is_sold_individually() ) {
                        $product_quantity = sprintf( '<input type="hidden" name="quote_qty[%s]" value="1" />', $quote_item->id );
                    } else {
                        woocommerce_quantity_input(
                            [
                                'input_name'   => "quote_qty[{$quote_item->product_id}]",
                                'input_value'  => $quote_item->quantity,
                                'max_value'    => $_product->get_max_purchase_quantity(),
                                'min_value'    => '0',
                                'product_name' => $_product->get_name(),
                            ],
                            $_product,
                            true
                        );
                    }
                endif;
                ?>
            </td>
                <td data-title="<?php esc_attr_e( 'Subtotal', 'dokan' ); ?>">
                    <?php
                    echo wp_kses_post( wc_price( $price * $qty_display ) );
                    $quote_subtotal += ( $price * $qty_display );
                    ?>
                </td>
                <td data-title="<?php esc_attr_e( 'Offered Subtotal', 'dokan' ); ?>">
                    <?php
                    echo wp_kses_post( wc_price( $offer_price * $qty_display ) );
                    $offered_total += ( $offer_price * $qty_display );
                    ?>
                </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>
<?php
if ( 'converted' !== $quote->status && 'approve' !== $quote->status ) :
    ?>
    <div style="float: right">
        <input name='updated_by' value='<?php echo esc_attr( $updated_by ); ?>' type='hidden'>
        <button type="submit" value="<?php echo intval( $quote->id ); ?>" name="dokan_update_quote" class="button button-secondary button-large">
            <?php echo esc_html__( 'Update quote', 'dokan' ); ?>
        </button>
    </div>
<?php endif; ?>
<div class='cart-collaterals'>
    <div class="cart_totals">
        <h2><?php esc_html_e( 'Quote totals', 'dokan' ); ?></h2>
        <table class='shop_table shop_table_responsive table_quote_totals dokan-table order-items'>
                <tr class="cart-subtotal">
                    <th><?php esc_html_e( 'Subtotal (standard)', 'dokan' ); ?></th>
                    <td data-title="<?php esc_attr_e( 'Subtotal (standard)', 'dokan' ); ?>"><?php echo wp_kses_post( wc_price( $quote_subtotal ) ); ?></td>
                </tr>
                <tr class="cart-subtotal offered">
                    <th><?php esc_html_e( 'Offered Price Subtotal', 'dokan' ); ?></th>
                    <td data-title="<?php esc_attr_e( 'Offered Price Subtotal', 'dokan' ); ?>"><?php echo wp_kses_post( wc_price( $offered_total ) ); ?></td>
                </tr>
        </table>
    </div>
    <?php
    if ( ! empty( $approved_by_vendor ) && 'approve' !== $quote->status && 'converted' !== $quote->status ) :
        ?>
        <div class='dokan_convert_to_order_button'>
            <input name='approved_by_vendor' value='approve' type='hidden'>
            <button type="submit" value="<?php echo intval( $quote->id ); ?>" name="approved_by_vendor_button" class="button button-secondary button-large">
                <?php echo esc_html__( 'Approve this quote', 'dokan' ); ?>
            </button>
        </div>
    <?php endif; ?>
    <?php
    $page_id = dokan_get_option( 'dashboard', 'dokan_pages' );
    $convert_to_order_button = false;
    if ( apply_filters( 'dokan_get_dashboard_page_id', (int) $page_id ) === get_queried_object_id() && 'approve' === $quote->status ) {
        $convert_to_order_button = true;
    } elseif ( SettingsHelper::is_convert_to_order_enabled() && 'approve' === $quote->status ) {
        $convert_to_order_button = true;
    }

    if ( $convert_to_order_button ) :
        ?>
    <div class="dokan_convert_to_order_button">
        <input name='converted_by' value='<?php echo isset( $converted_by ) ? esc_attr( $converted_by ) : 'Customer'; ?>' type='hidden'>
        <button type="submit" value="<?php echo intval( $quote->id ); ?>" name="dokan_convert_to_order_customer" class="button button-primary button-large">
            <?php echo esc_html__( 'Convert to Order', 'dokan' ); ?>
        </button>
    </div>
    <?php endif; ?>
    <?php wp_nonce_field( 'save_dokan_quote_action', 'dokan_quote_nonce' ); ?>
</div>
