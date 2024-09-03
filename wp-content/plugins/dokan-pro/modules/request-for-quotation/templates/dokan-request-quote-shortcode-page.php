<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $quotes ) ) :
    ?>
    <div class="woocommerce">
        <div class="woocommerce-notices-wrapper">
        </div>
        <form class="woocommerce-cart-form dokan-quote-form" method="post" enctype="multipart/form-data">

            <?php
            dokan_get_template_part(
                'quote-table', '', [
                    'quotes'              => $quotes,
                    'request_quote_table' => true,
                ]
            );
            ?>
                <div class="cart-collaterals">
                    <div class="cart_totals">
                        <h2><?php esc_html_e( 'Quote totals', 'dokan' ); ?></h2>
                        <?php
                        dokan_get_template_part(
                            'quote-totals-table', '', [
                                'quote_totals'        => $quote_totals,
                                'request_quote_table' => true,
                            ]
                        );
                        ?>
                    </div>
                </div>
            <div style='float: right' class="quote_fields">
                <?php
                dokan_get_template_part(
                    'quote-fields', '', [
                        'request_quote_table' => true,
                    ]
                );
                ?>
                <div class="form_row">
                    <input name='dokan_quote_save_action' type='hidden' value='dokan_quote_save_action' />
                    <?php wp_nonce_field( 'save_dokan_quote_action', 'dokan_quote_nonce' ); ?>
                    <button type="submit" name="dokan_checkout_place_quote" class="button alt dokan_checkout_place_quote"><?php echo esc_html__( 'Place Quote', 'dokan' ); ?></button>
                </div>

            </div>
        </form>
    </div>

<?php else : ?>

    <div class="dokan_quote">
        <p class="cart-empty"><?php echo esc_html__( 'Your quote is currently empty.', 'dokan' ); ?></p>
        <p class="return-to-shop"><a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="button wc-backward"><?php echo esc_html__( 'Return To Shop', 'dokan' ); ?></a>
        </p>
    </div>

    <?php
endif;
