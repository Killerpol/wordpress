<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce">
    <?php do_action( 'dokan_before_quote_table' ); ?>
    <form method='post'>
        <?php do_action( 'dokan_my_account_request_quote_heading', (object) $quote ); ?>
        <h2><?php echo esc_html__( 'Quote Details', 'dokan' ); ?></h2>
        <?php do_action( 'dokan_my_account_request_quote_details', (object) $quote_details, (object) $quote ); ?>
    </form>
</div>
