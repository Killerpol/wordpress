<table class='shop_table order_details quote_details dokan-table order-items'>
    <tr>
        <th class='quote-number'><?php esc_html_e( 'Quote #', 'dokan' ); ?></th>
        <td class="quote-number"><?php echo esc_html( $quote->id ); ?> </td>
    </tr>
    <tr>
        <th class="customer-name"><?php esc_html_e( 'Customer name', 'dokan' ); ?></th>
        <td class="customer-name"><?php echo esc_html( $customer_name ); ?> </td>
    </tr>
    <tr>
        <th class="customer-email"><?php esc_html_e( 'Customer email', 'dokan' ); ?></th>
        <td class="customer-email"><?php echo esc_html( $customer_email ); ?> </td>
    </tr>
    <tr>
        <th class="quote-date"><?php esc_html_e( 'Quote Date', 'dokan' ); ?></th>
        <td class="quote-date"><?php echo esc_attr( dokan_format_datetime( $quote->created_at ) ); ?> </td>
    </tr>
    <tr>
        <th class="quote-status"><?php esc_html_e( 'Current Status', 'dokan' ); ?></th>
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
    <?php if ( 'converted' === $quote->status ) : ?>
        <tr>
            <th class="quote-converter"><?php esc_html_e( 'Converted by', 'dokan' ); ?></th>
            <td class="quote-converter"><?php echo esc_html( $quote->converted_by ); ?> </td>
        </tr>
    <?php endif; ?>
</table>
