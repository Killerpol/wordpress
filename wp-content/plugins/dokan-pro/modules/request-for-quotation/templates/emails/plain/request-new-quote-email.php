<?php
/**
 * New Product Email ( plain text )
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_New_Product
 * @since       3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo '= ' . esc_attr( $email_heading ) . " =\n\n";
?>

<?php esc_attr_e( 'Summary of the Quote:', 'dokan' ); ?>
<?php echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n"; ?>

<?php esc_html_e( 'Quote #: ', 'dokan' ); ?><?php echo esc_html( $quote_id ); ?>
<?php esc_html_e( 'Quote Date: ', 'dokan' ); ?><?php echo esc_attr( dokan_format_datetime() ); ?>
<?php esc_html_e( 'Quote Status: ', 'dokan' ); ?><?php echo esc_html( 'Pending' ); ?>
<?php
if ( 'seller' === $sending_to ) :
    ?>
    <?php esc_html_e( 'Customer name: ', 'dokan' ); ?><?php echo esc_html( $customer_info['name_field'] ); ?>
    <?php esc_html_e( 'Customer email: ', 'dokan' ); ?><?php echo esc_html( $customer_info['email_field'] ); ?>
	<?php
endif;
?>
<?php echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n"; ?>
<?php
$offered_total = 0;
foreach ( $quote_details as $quote_item ) {
    $_product    = wc_get_product( $quote_item->product_id );
    $price       = $_product->get_price();
    $offer_price = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
    $qty_display = $quote_item->quantity;
    ?>
    <?php
    // translators: %s is product name.
    echo "\n" . sprintf( esc_html__( 'Product: %s', 'dokan' ), $_product->get_name() );
    echo "\n" . esc_html__( 'SKU:', 'dokan' ) . '</strong> ' . esc_html( $_product->get_sku() );

    echo "\n";
    ?>
    <?php
    // translators: %s is price.
    echo sprintf( esc_html__( 'Offered Price: %s', 'dokan' ), $offer_price );
    echo "\n";
    ?>
    <?php
    // translators: %s is quantity.
    echo sprintf( esc_html__( 'Quantity: %s', 'dokan' ), $qty_display );
    echo "\n";
    ?>
    <?php
    // translators: %s is price.
    echo sprintf( esc_html__( 'Offered Subtotal: %s', 'dokan' ), ( $offer_price * $qty_display ) );
    $offered_total += ( $offer_price * $qty_display );
    ?>

    <?php
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}
// translators: %s is price.
echo sprintf( esc_attr__( 'Total Offered Price: %s', 'dokan' ), $offered_total );
echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";


echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
