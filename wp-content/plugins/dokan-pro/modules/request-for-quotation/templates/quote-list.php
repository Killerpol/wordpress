<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $vendor_all_quotes ) ) {
    ?>
	<table class="shop_table shop_table_responsive cart my_account_orders my_account_quotes">
		<thead>
			<tr>
				<th ><?php echo esc_html__( 'Quote #', 'dokan' ); ?></th>
				<th ><?php echo esc_html__( 'Quote Name', 'dokan' ); ?></th>
				<th><?php echo esc_html__( 'Status', 'dokan' ); ?></th>
				<th><?php echo esc_html__( 'Date', 'dokan' ); ?></th>
				<th><?php echo esc_html__( 'Action', 'dokan' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $vendor_all_quotes as $key => $quote ) :
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $quote->id ) ); ?>">
							<?php echo esc_html__( 'Quote', 'dokan' ) . ' ' . intval( $quote->id ); ?>
						</a>
					</td>
					<td>
                        <?php echo esc_html( $quote->quote_title ); ?>
                    </td>
					<td>
						<?php
                        $quote_status = esc_html( $quote->status );
                        if ( 'approve' === $quote->status ) {
                            $quote_status = esc_html__( 'Approved', 'dokan' );
                        }
                        echo isset( $quote_status ) ? ucfirst( $quote_status ) : __( 'Pending', 'dokan' );
                        ?>
					</td>
					<td>
						<time datetime="<?php echo esc_attr( dokan_format_date( $quote->created_at ) ); ?>" title="<?php echo esc_attr( dokan_format_date( $quote->created_at ) ); ?>"><?php echo esc_attr( dokan_format_date( $quote->created_at ) ); ?></time>
					</td>
					<td>
						<a href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $quote->id ) ); ?>" class="woocommerce-button button view">
							<?php echo esc_html__( 'View', 'dokan' ); ?>
						</a>

					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
    <?php echo $pagination_html; ?>

	<?php
} else {
    echo '<p class="woocommerce-noreviews">' . esc_html__( 'You have no quotes yet.', 'dokan' ) . '</p>';
}
