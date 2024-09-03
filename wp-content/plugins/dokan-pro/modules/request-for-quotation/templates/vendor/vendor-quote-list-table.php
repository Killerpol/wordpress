<?php
defined( 'ABSPATH' ) || exit;

do_action( 'dokan_dashboard_wrap_start' );

?>

<div class="dokan-dashboard-wrap">
    <?php
    do_action( 'dokan_dashboard_content_before' );
    ?>
    <div class="dokan-dashboard-content">
        <h3 class="entry-title"><?php esc_html_e( 'Request Quotes', 'dokan' ); ?></h3>

        <article class="dashboard-content-area">
            <?php
            if ( ! empty( $vendor_all_quotes ) ) :
                do_action( 'dokan_request_quote_list', (object) $vendor_all_quotes, $vendor_endpoint, $pagination_html );
                ?>
            <?php else : ?>

                <div class="woocommerce-MyAccount-content">
                    <div class="woocommerce-notices-wrapper"></div>
                    <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
                        <a class="woocommerce-Button button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php echo esc_html__( 'Go to shop', 'dokan' ); ?></a><?php echo esc_html__( 'No quote has been made yet.', 'dokan' ); ?></div>
                </div>
            <?php
            endif;
            ?>
        </article>
    </div>
</div>

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>
