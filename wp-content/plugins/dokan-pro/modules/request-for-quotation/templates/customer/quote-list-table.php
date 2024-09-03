<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $vendor_all_quotes ) ) :
    do_action( 'dokan_request_quote_list', (object) $vendor_all_quotes, $account_endpoint, $pagination_html );

    ?>

<?php else : ?>

	<div class="woocommerce-MyAccount-content">
		<div class="woocommerce-notices-wrapper"></div>
		<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
			<a class="woocommerce-Button button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php echo esc_html__( 'Go to shop', 'dokan' ); ?></a><?php echo esc_html__( 'No quote has been made yet.', 'dokan' ); ?></div>
	</div>
	<?php
endif;
