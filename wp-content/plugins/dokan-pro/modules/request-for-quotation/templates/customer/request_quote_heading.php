<?php
    use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;
?>
<table class='shop_table shop_table_responsive table_quote_totals dokan-table order-items'>
    <tr>
        <th><?php esc_html_e( 'Quote #', 'dokan' ); ?></th>
        <td><?php echo esc_html( $quote->id ); ?> </td>
    </tr>
    <tr>
        <th><?php esc_html_e( 'Quote Date', 'dokan' ); ?></th>
        <td><?php echo esc_attr( dokan_format_datetime( $quote->created_at ) ); ?> </td>
    </tr>
    <tr>
        <th><?php esc_html_e( 'Current Status', 'dokan' ); ?></th>
        <td>
            <?php
            $quote_status = isset( $quote->status ) ? esc_html( $quote->status ) : __( 'Pending', 'dokan' );
            if ( 'approve' === $quote->status ) {
                $quote_status = esc_html__( 'Approved', 'dokan' );
            }
            echo ucfirst( $quote_status );
            ?>
        </td>
    </tr>

    <?php
    if ( SettingsHelper::is_quote_converter_display_enabled() && 'converted' === $quote->status ) :
        ?>
        <tr>
            <th><?php esc_html_e( 'Converted by', 'dokan' ); ?></th>
            <td><?php echo esc_html( $quote->converted_by ); ?> </td>
        </tr>
		<?php
    endif;
    $order_url = '';
    if ( ! empty( $quote->order_id ) ) :
        $quote_order = wc_get_order( $quote->order_id );
        $order_url = $quote_order->get_view_order_url();
		?>
    <tr>
        <td>
            <a href="<?php echo esc_url( $order_url ); ?>" >
                <?php esc_html_e( 'View Order', 'dokan' ); ?>
            </a>
        </td>
    </tr>
		<?php
    endif;
    ?>
</table>
