<h3><?php _e( 'Stripe Connect', 'dokan' ); ?></h3>
<p><?php _e( 'Stripe works by adding credit card fields on the checkout and then sending the details to Stripe for verification.', 'dokan' ); ?></p>
<p>
    <?php
    // Collect test mode endpoint if user on test mode.
    $stripe_env = \WeDevs\DokanPro\Modules\Stripe\Helper::is_test_mode() ? '/test' : '';

    if ( method_exists( 'Dokan_WPML', 'remove_url_translation' ) ) {
        \Dokan_WPML::remove_url_translation();
    }
    echo wp_kses(
        sprintf(
            __( 'Set your authorize redirect uri <code>%s</code><span class="dokan-copy-to-clipboard" data-copy="%s"></span>in your Stripe <a href="%s" target="_blank">application settings</a> for Redirects.', 'dokan' ),
            dokan_get_navigation_url( 'settings/payment-manage-dokan-stripe-connect' ),
            dokan_get_navigation_url( 'settings/payment-manage-dokan-stripe-connect' ),
            "https://dashboard.stripe.com{$stripe_env}/settings/connect/onboarding-options/oauth"
        ),
        [
            'a'    => [
                'href'   => true,
                'target' => true,
            ],
            'code' => [],
            'span' => [
                'class' => true,
                'data-copy' => true,
            ],
        ]
    );
    if ( method_exists( 'Dokan_WPML', 'restore_url_translation' ) ) {
        \Dokan_WPML::restore_url_translation();
    }
    ?>
</p>
<p>
    <?php
    if ( method_exists( 'Dokan_WPML', 'remove_url_translation' ) ) {
        \Dokan_WPML::remove_url_translation();
    }
    echo wp_kses(
        sprintf(
            __( 'Recurring subscription requires webhooks to be configured. Go to <a href="%1$s" target="_blank">webhook</a> and set your webhook url <code>%2$s</code><span class="dokan-copy-to-clipboard" data-copy="%3$s"></span> (if not automatically set). Otherwise recurring payment will not work automatically.', 'dokan' ),
            "https://dashboard.stripe.com{$stripe_env}/webhooks",
            home_url( 'wc-api/dokan_stripe' ),
            home_url( 'wc-api/dokan_stripe' )
        ),
        [
            'a'    => [
                'href'   => true,
                'target' => true,
            ],
            'code' => [],
            'span' => [
                'class' => true,
                'data-copy' => true,
            ],
        ]
    );
    if ( method_exists( 'Dokan_WPML', 'restore_url_translation' ) ) {
        \Dokan_WPML::restore_url_translation();
    }
    ?>
</p>
<table class="form-table">
    <?php $gateway->generate_settings_html(); ?>
</table>
