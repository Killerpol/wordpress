<?php

use WeDevs\Dokan\ProductCategory\Helper;
use WeDevs\Dokan\Walkers\TaxonomyDropdown;
global $post, $product;

$post_id        = ! empty( $post->ID ) ? $post->ID : 0;
$seller_id      = dokan_get_current_user_id();
$new_product    = false;
$is_proxy_ac_on = get_option( 'simple_auctions_proxy_auction_on', 'no' );

if ( isset( $_GET['product_id'] ) ) {
    $post_id = intval( $_GET['product_id'] );

    if ( empty( $post_id ) ) {
        $product = new WC_Product_Auction( new WC_Product() );
        $product->set_status( 'auto-draft' );
        $product->set_name( '' );
        $product->update_meta_data( '_auction_proxy', $is_proxy_ac_on );
        $post_id = $product->save();
        $new_product = true;
        wp_update_post(
            [
                'ID'          => $post_id,
                'post_author' => dokan_get_current_user_id(),
            ]
        );
    }

    $post        = get_post( $post_id );
    $product     = wc_get_product( $post_id );
    $new_product = 'auto-draft' === $product->get_status();

    // Set product default status.
    $post_status = dokan_get_default_product_status( dokan_get_current_user_id() );
    $post_status = $product->get_status() === 'auto-draft' ? $post_status : $product->get_status();
}

// bail out if not author
if ( $post->post_author != $seller_id ) {
    wp_die( __( 'Access Denied', 'dokan' ) );
}

$_regular_price                = get_post_meta( $post_id, '_regular_price', true );
$_featured                     = get_post_meta( $post_id, '_featured', true );
$_stock                        = get_post_meta( $post_id, '_stock', true  );
$_auction_item_condition       = get_post_meta( $post_id, '_auction_item_condition', true );
$_auction_type                 = get_post_meta( $post_id, '_auction_type', true );

$_auction_proxy                = get_post_meta( $post_id, '_auction_proxy', true );
$_auction_sealed               = get_post_meta( $post_id, '_auction_sealed', true );
$_auction_start_price          = get_post_meta( $post_id, '_auction_start_price', true );
$_auction_bid_increment        = get_post_meta( $post_id, '_auction_bid_increment', true );
$_auction_reserved_price       = get_post_meta( $post_id, '_auction_reserved_price', true );
$_auction_dates_from           = get_post_meta( $post_id, '_auction_dates_from', true );
$_auction_dates_to             = get_post_meta( $post_id, '_auction_dates_to', true );
$_auction_dates_to_timestamp   = $_auction_dates_to ? dokan_current_datetime()->modify( $_auction_dates_to ) : '';
$_auction_dates_to_timestamp   = $_auction_dates_to_timestamp ? $_auction_dates_to_timestamp->getTimestamp() : '';

$_auction_automatic_relist     = get_post_meta( $post_id, '_auction_automatic_relist', true );
$_auction_relist_fail_time     = get_post_meta( $post_id, '_auction_relist_fail_time', true );
$_auction_relist_not_paid_time = get_post_meta( $post_id, '_auction_relist_not_paid_time', true );
$_auction_relist_duration      = get_post_meta( $post_id, '_auction_relist_duration', true );
$_visibility                   = ( version_compare( WC_VERSION, '2.7', '>' ) ) ? $product->get_catalog_visibility() : get_post_meta( $post_id, '_visibility', true );
$visibility_options            = dokan_get_product_visibility_options();
?>

<?php do_action( 'dokan_dashboard_wrap_start' ); ?>

<div class="dokan-dashboard-wrap">
<?php
do_action( 'dokan_dashboard_content_before' );
do_action( 'dokan_edit_auction_product_content_before' );
?>
<!--  -->
<div class="dokan-dashboard-content dokan-product-edit">
<?php
if ( $new_product ) {
    do_action( 'dokan_new_product_before_product_area' );
    do_action( 'dokan_auction_content_inside_before' );
} else {

    /**
     *  dokan_edit_auction_product_content_inside_before hook
     *
     *  @since 2.4
     */
    do_action( 'dokan_edit_auction_product_content_inside_before' );
}
    ?>
    <header class="dokan-dashboard-header dokan-clearfix">
        <h1 class="entry-title">
            <?php
            if ( $new_product ) {
                esc_html_e( 'Add New Auction Product', 'dokan' );
            } else {
                esc_html_e( 'Edit Auction Products', 'dokan' );
            }
            ?>
            <span class="dokan-label <?php echo dokan_get_post_status_label_class( $post->post_status ); ?> dokan-product-status-label">
                <?php echo dokan_get_post_status( $post->post_status ); ?>
            </span>

            <?php if ( $_visibility == 'hidden' ) { ?>
            <span class="dokan-label dokan-label-default"><?php _e( 'Hidden', 'dokan' ); ?></span>
            <?php } ?>

            <?php if ( $post->post_status == 'publish' ) { ?>
            <span class="dokan-right">
                <a class="view-product dokan-btn dokan-btn-sm" href="<?php echo get_permalink( $post->ID ); ?>" target="_blank"><?php _e( 'View Product', 'dokan' ); ?></a>
            </span>
            <?php } ?>
        </h1>
    </header>

    <div class="dokan-new-product-area">
        <?php wc_print_notices(); ?>
        <?php if ( isset( $_GET['message'] ) && $_GET['message'] == 'success') { ?>
            <div class="dokan-message">
                <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
                <strong><?php _e( 'Success!', 'dokan' ); ?></strong> <?php _e( 'The product has been updated successfully.', 'dokan' ); ?>

                <?php if ( $post->post_status == 'publish' ) { ?>
                <a href="<?php echo get_permalink( $post_id ); ?>" target="_blank"><?php _e( 'View Product &rarr;', 'dokan' ); ?></a>
                <?php } ?>
            </div>
        <?php } ?>

        <form class="dokan-form-container dokan-auction-product-form" role="form" method="post">
            <?php wp_nonce_field( 'dokan_edit_auction_product', 'dokan_edit_auction_product_nonce' ); ?>
            <div class="product-edit-container dokan-clearfix">

                <div id="edit-product">

                    <?php do_action( 'dokan_product_edit_before_main' ); ?>

                    <div class="product-edit-container dokan-clearfix">
                        <div class="content-half-part featured-image">
                            <div class="dokan-feat-image-upload">
                                <?php
                                $wrap_class        = ' dokan-hide';
                                $instruction_class = '';
                                $feat_image_id     = 0;

                                if ( has_post_thumbnail( $post_id ) ) {
                                    $wrap_class        = '';
                                    $instruction_class = ' dokan-hide';
                                    $feat_image_id     = get_post_thumbnail_id( $post_id );
                                }
                                ?>

                                <div class="instruction-inside<?php echo $instruction_class; ?>">
                                    <input type="hidden" name="feat_image_id" class="dokan-feat-image-id" value="<?php echo $feat_image_id; ?>">

                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <a href="#" class="dokan-feat-image-btn btn btn-sm"><?php _e( 'Upload a product cover image', 'dokan' ); ?></a>
                                </div>

                                <div class="image-wrap<?php echo $wrap_class; ?>">
                                    <a class="close dokan-remove-feat-image">&times;</a>
                                    <?php if ( $feat_image_id ) { ?>
                                    <?php echo get_the_post_thumbnail( $post_id, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ), array( 'height' => '', 'width' => '' ) ); ?>
                                    <?php } else { ?>
                                    <img height="" width="" src="" alt="">
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="dokan-product-gallery">
                                    <div class="dokan-side-body" id="dokan-product-images">
                                        <div id="product_images_container">
                                            <ul class="product_images dokan-clearfix">
                                                <?php
                                                $product_images = get_post_meta( $post_id, '_product_image_gallery', true );
                                                $gallery = explode( ',', $product_images );

                                                if ( $gallery ) {
                                                    foreach ($gallery as $image_id) {
                                                        if ( empty( $image_id ) ) {
                                                            continue;
                                                        }

                                                        $attachment_image = wp_get_attachment_image_src( $image_id, 'thumbnail' );
                                                        ?>
                                                        <li class="image" data-attachment_id="<?php echo $image_id; ?>">
                                                            <img src="<?php echo $attachment_image[0]; ?>" alt="">
                                                            <a href="#" class="action-delete" title="<?php esc_attr_e( 'Delete image', 'dokan' ); ?>">&times;</a>
                                                        </li>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                                <li class="add-image add-product-images tips" data-title="<?php _e( 'Add gallery image', 'dokan' ); ?>">
                                                    <a href="#" class="add-product-images"><i class="fas fa-plus" aria-hidden="true"></i></a>
                                                </li>
                                            </ul>

                                            <input type="hidden" id="product_image_gallery" name="product_image_gallery" value="<?php echo esc_attr( $product_images ); ?>">
                                        </div>
                                    </div>
                                </div> <!-- .product-gallery -->
                        </div>
                        <div class="content-half-part dokan-product-meta">

                            <div class="dokan-form-group dokan-auction-post-title">
                                <input type="hidden" name="dokan_product_id" value="<?php echo esc_attr( $post_id ); ?>">
                                <?php
                                // Render auction product title field.
                                $post_title = ! empty( $post->post_title ) ? esc_html( $post->post_title ) : '';
                                dokan_post_input_box(
                                    $post_id,
                                    'post_title',
                                    array(
                                        'placeholder' => __( 'Product name..', 'dokan' ),
                                        'value'       => ! $new_product ? $post_title : '',
                                    )
                                );
                                ?>
                            </div>

                            <div class="dokan-form-group dokan-auction-post-excerpt">
                                <?php
                                // Render auction product excerpt field.
                                $excerpt = ! empty( $post->post_excerpt ) ? esc_html( $post->post_excerpt ) : '';
                                dokan_post_input_box(
                                    $post_id,
                                    'post_excerpt',
                                    array(
                                        'placeholder' => __( 'Short description about the product...', 'dokan' ),
                                        'value'       => ! $new_product ? $excerpt : '',
                                    ),
                                    'textarea'
                                );
                                ?>
                            </div>
                            <div class="dokan-form-group dokan-auction-category">
                                <?php
                                    $data = Helper::get_saved_products_category( $post_id );
                                    $data['from'] = 'edit_booking_product';

                                    dokan_get_template_part('products/dokan-category-header-ui', '', $data );
                                ?>
                            </div>

                            <div class="dokan-form-group dokan-auction-tags">
                                <label for="product_tag" class="form-label"><?php _e( 'Tags', 'dokan' ); ?></label>
                                <?php
                                require_once DOKAN_LIB_DIR.'/class.taxonomy-walker.php';
                                $term = wp_get_post_terms( $post_id, 'product_tag', array( 'fields' => 'ids') );
                                $selected = ( $term ) ? $term : array();
                                $drop_down_tags = wp_dropdown_categories( array(
                                    'show_option_none' => __( '', 'dokan' ),
                                    'hierarchical'     => 1,
                                    'hide_empty'       => 0,
                                    'name'             => 'product_tag[]',
                                    'id'               => 'product_tag',
                                    'taxonomy'         => 'product_tag',
                                    'title_li'         => '',
                                    'class'            => 'product_tags dokan-form-control dokan-select2',
                                    'exclude'          => '',
                                    'selected'         => $selected,
                                    'echo'             => 0,
                                    'walker'           => new TaxonomyDropdown( $post_id )
                                ) );

                                echo str_replace( '<select', '<select data-placeholder="'.__( 'Select product tags', 'dokan' ).'" multiple="multiple" ', $drop_down_tags );

                                ?>
                            </div>
                            <?php do_action( 'dokan_auction_before_general_options', $post_id ); ?>
                        </div>
                    </div>

                    <div class="product-edit-new-container">
                        <div class="dokan-edit-row dokan-auction-general-sections dokan-clearfix">
                            <div class="dokan-section-heading" data-togglehandler="dokan_product_inventory">
                                <h2><i class="fas fa-cubes" aria-hidden="true"></i> <?php _e( 'General Options', 'dokan' ) ?></h2>
                                <p><?php _e( 'Manage your auction product data', 'dokan' ); ?></p>
                                <div class="dokan-clearfix"></div>
                            </div>

                            <div class="dokan-section-content">
                                <div class="content-half-part dokan-auction-item-condition">
                                    <div class="dokan-form-group">
                                        <label class="dokan-control-label" for="_auction_item_condition"><?php _e( 'Item condition', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <select name="_auction_item_condition" class="dokan-form-control" id="_auction_item_condition">
                                                <option value="new" <?php echo ( $_auction_item_condition == 'new' ) ? 'selected' : '' ?>><?php _e( 'New', 'dokan' ) ?></option>
                                                <option value="used" <?php echo ( $_auction_item_condition == 'used' ) ? 'selected' : '' ?>><?php _e( 'Used', 'dokan' ) ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="content-half-part dokan-auction-type">
                                    <div class="dokan-form-group">
                                        <label class="dokan-control-label" for="_auction_type"><?php _e( 'Auction type', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <select name="_auction_type" class="dokan-form-control" id="_auction_type">
                                                <option value="normal" <?php echo ( $_auction_type == 'normal' ) ? 'selected' : '' ?>><?php _e( 'Normal', 'dokan' ) ?></option>
                                                <option value="reverse" <?php echo ( $_auction_type == 'reverse' ) ? 'selected' : '' ?>><?php _e( 'Reverse', 'dokan' ) ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="dokan-clearfix"></div>

                                <div class="dokan-form-group dokan-auction-proxy-bid">
                                    <div class="checkbox">
                                        <label for="_auction_proxy">
                                            <input type="checkbox" name="_auction_proxy" value="yes" id="_auction_proxy" <?php checked( $_auction_proxy, 'yes' ); ?>>
                                            <?php _e( 'Enable proxy bidding for this auction product', 'dokan' );?>
                                        </label>
                                    </div>
                                </div>

                                <?php if( get_option( 'simple_auctions_sealed_on', 'no' ) == 'yes') : ?>
                                    <div class="dokan-form-group dokan-auction-sealed-bid">
                                        <div class="checkbox">
                                            <label for="_auction_sealed">
                                                <input type="checkbox" name="_auction_sealed" value="yes" id="_auction_sealed" <?php checked( $_auction_sealed, 'yes' ); ?>>
                                                <?php _e( 'Enable sealed bidding for this auction product', 'dokan' );?>
                                                <i class="fas fa-question-circle tips" data-title="<?php _e( 'In this type of auction all bidders simultaneously submit sealed bids so that no bidder knows the bid of any other participant. The highest bidder pays the price they submitted. If two bids with same value are placed for auction the one which was placed first wins the auction.', 'dokan' ); ?>"></i>
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="content-half-part dokan-auction-start-price">
                                    <div class="dokan-form-group">
                                        <label class="dokan-control-label" for="_auction_start_price"><?php _e( 'Start Price', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input class="wc_input_price dokan-form-control" name="_auction_start_price" id="_auction_start_price" type="text" placeholder="<?php echo wc_format_localized_price('9.99'); ?>" value="<?php echo wc_format_localized_price( $_auction_start_price ); ?>" style="width: 97%;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="content-half-part dokan-auction-bid-increment">
                                    <div class="dokan-form-group">
                                        <label class="dokan-control-label" for="_auction_bid_increment"><?php _e( 'Bid increment', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input class="wc_input_price dokan-form-control" name="_auction_bid_increment" id="_auction_bid_increment" type="text" placeholder="<?php echo wc_format_localized_price('9.99') ?>" value="<?php echo wc_format_localized_price( $_auction_bid_increment ); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="dokan-clearfix"></div>

                                <div class="content-half-part dokan-auction-reserved-price">
                                    <div class="dokan-form-group">
                                        <label class="dokan-control-label" for="_auction_reserved_price"><?php _e( 'Reserved price', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input class="wc_input_price dokan-form-control" name="_auction_reserved_price" id="_auction_reserved_price" type="text" placeholder="<?php echo wc_format_localized_price('9.99'); ?>" value="<?php echo wc_format_localized_price( $_auction_reserved_price ); ?>" style="width: 97%;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="content-half-part dokan-auction-regular-price">
                                    <label class="dokan-control-label" for="_regular_price"><?php _e( 'Buy it now price', 'dokan' ); ?></label>
                                    <div class="dokan-form-group">
                                        <div class="dokan-input-group">
                                            <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                            <input class="wc_input_price dokan-form-control" name="_regular_price" id="_regular_price" type="text" placeholder="<?php echo wc_format_localized_price('9.99') ?>" value="<?php echo wc_format_localized_price( $_regular_price ); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="dokan-auction-date">
                                    <div class="content-half-part dokan-auction-dates-from">
                                        <label class="dokan-control-label" for="_auction_dates_from"><?php _e( 'Auction Start date', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <input class="dokan-form-control auction-datepicker" name="_auction_dates_from" id="_auction_dates_from" type="text" value="<?php echo esc_attr( $_auction_dates_from ); ?>" style="width: 97%;" readonly>
                                        </div>
                                    </div>

                                    <div class="content-half-part dokan-auction-dates-to">
                                        <label class="dokan-control-label" for="_auction_dates_to"><?php _e( 'Auction End date', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <input class="dokan-form-control auction-datepicker" name="_auction_dates_to" id="_auction_dates_to" type="text" value="<?php echo esc_attr( $_auction_dates_to ); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="dokan-clearfix"></div>

                                <?php if ( $_auction_dates_to_timestamp && ( time() > $_auction_dates_to_timestamp ) ) : ?>
                                <div class="dokan-auction-date-relist">
                                    <div class="content-half-part dokan-auction-dates-from">
                                        <label class="dokan-control-label" for="_relist_auction_dates_from"><?php esc_html_e( 'Relist Auction Start date', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <input class="dokan-form-control auction-datepicker" name="_relist_auction_dates_from" id="_relist_auction_dates_from" type="text" value="<?php echo esc_attr( $_auction_dates_from ); ?>" style="width: 97%;" readonly>
                                        </div>
                                    </div>

                                    <div class="content-half-part dokan-auction-dates-to">
                                        <label class="dokan-control-label" for="_relist_auction_dates_to"><?php esc_html_e( 'Relist Auction End date', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <input class="dokan-form-control auction-datepicker" name="_relist_auction_dates_to" id="_relist_auction_dates_to" type="text" value="<?php echo esc_attr( $_auction_dates_to ); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="auction_relist_section">
                                    <div class="dokan-form-group dokan-auction-automatic-relist">
                                        <div class="checkbox">
                                            <label for="_auction_automatic_relist">
                                                <input type="hidden" name="_auction_automatic_relist" value="no">
                                                <input type="checkbox" name="_auction_automatic_relist" id="_auction_automatic_relist" value="yes" <?php checked( $_auction_automatic_relist, 'yes' ) ?>>
                                                <?php _e( 'Enable automatic relisting for this auction', 'dokan' ); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php if ( $_auction_dates_to_timestamp && ( time() > $_auction_dates_to_timestamp ) ) : ?>
                                    <div class="dokan-form-group dokan-auction-automatic-relist">
                                        <button class="dokan-auction-relist-button">Relist</button>
                                    </div>
                                    <?php endif; ?>

                                    <div class="relist_options" style="display: none">
                                        <div class="dokan-w3 dokan-auction-relist-fail-time">
                                            <label class="dokan-control-label" for="_auction_relist_fail_time"><?php _e( 'Relist if fail after n hours', 'dokan' ); ?></label>
                                            <div class="dokan-form-group">
                                                <input class="dokan-form-control" name="_auction_relist_fail_time" id="_auction_relist_fail_time" type="number" value="<?php echo $_auction_relist_fail_time ?>">
                                            </div>
                                        </div>
                                        <div class="dokan-w3 dokan-auction-relist-not-paid-time">
                                            <label class="dokan-control-label" for="_auction_relist_not_paid_time"><?php _e( 'Relist if not paid after n hours', 'dokan' ); ?></label>
                                            <div class="dokan-form-group">
                                                <input class="dokan-form-control" name="_auction_relist_not_paid_time" id="_auction_relist_not_paid_time" type="number" value="<?php echo $_auction_relist_not_paid_time ?>">
                                            </div>
                                        </div>
                                        <div class="dokan-w3 dokan-auction-relist-duration">
                                            <label class="dokan-control-label" for="_auction_relist_duration"><?php _e( 'Relist auction duration in h', 'dokan' ); ?></label>
                                            <div class="dokan-form-group">
                                                <input class="dokan-form-control" name="_auction_relist_duration" id="_auction_relist_duration" type="number" value="<?php echo $_auction_relist_duration ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <?php do_action( 'dokan_auction_after_general_options', $post_id ); ?>

                        <div class="dokan-edit-row dokan-auction-other-sections dokan-clearfix">
                            <div class="dokan-section-heading" data-togglehandler="dokan_other_options">
                                <h2><i class="fas fa-cog" aria-hidden="true"></i> <?php _e( 'Other Options', 'dokan' ); ?></h2>
                                <p><?php _e( 'Set your extra product options', 'dokan' ); ?></p>
                                <div class="dokan-clearfix"></div>
                            </div>

                            <div class="dokan-section-content">
                                <div class="dokan-form-group content-half-part dokan-auction-product-status">
                                    <label for="post_status" class="form-label"><?php _e( 'Product Status', 'dokan' ); ?></label>
                                    <?php $post_statuses = dokan_get_available_post_status( $post->ID ); ?>
                                    <select id="post_status" class="dokan-form-control" name="post_status">
                                        <?php foreach ( $post_statuses as $status => $label ) { ?>
                                            <option value="<?php echo $status; ?>"<?php selected( $post_status, $status ); ?>><?php echo $label; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="dokan-form-group content-half-part dokan-auction-product-visibility">
                                    <label for="_visibility" class="form-label"><?php _e( 'Visibility', 'dokan' ); ?></label>
                                    <select name="_visibility" id="_visibility" class="dokan-form-control">
                                        <?php foreach ( $visibility_options as $name => $label ): ?>
                                            <option value="<?php echo $name; ?>" <?php selected( $_visibility, $name ); ?>><?php echo $label; ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="dokan-clearfix"></div>

                                <div class="dokan-form-group dokan-auction-post-content">
                                    <label for="post_content" class="form-label"><?php _e( 'Description', 'dokan' ); ?></label>
                                    <?php wp_editor( esc_textarea( $post->post_content ), 'post_content', array('editor_height' => 50, 'quicktags' => false, 'media_buttons' => false, 'teeny' => true, 'editor_class' => 'post_content') ); ?>
                                </div>
                            </div>
                        </div><!-- .dokan-other-options -->
                        <?php do_action( 'dokan_product_edit_after_options', $post_id ); ?>
                        <?php do_action( 'dokan_product_edit_after_main', $post, $post_id ); ?>
                    </div>
                    <input type='hidden' name='dokan_new_product_id' id='dokan_product_id' value="<?php echo esc_attr( $post_id ); ?>"/>
                    <input type="hidden" name="dokan_product_id" id="dokan-edit-product-id" value="<?php echo $post_id; ?>"/>
                    <input type="hidden" name="product-type" value="auction">
                    <input type="submit" name="update_auction_product" class="dokan-btn dokan-btn-theme dokan-btn-lg dokan-right" value="<?php esc_attr_e( 'Save Product', 'dokan' ); ?>"/>

                    <div class="dokan-clearfix"></div>
                </div>
            </div>
        </form>

    </div>
    <?php

    /**
     *  dokan_edit_auction_product_inside_after hook
     *
     *  @since 2.4
     */
    do_action( 'dokan_edit_auction_product_inside_after' );
    ?>
</div>

<?php
/**
 *  dokan_dashboard_content_after hook
 *  dokan_withdraw_content_after hook
 *
 *  @since 2.4
 */
do_action( 'dokan_dashboard_content_after' );
do_action( 'dokan_edit_auction_product_content_after' );
wp_reset_postdata();
wp_reset_query();
?>
</div><!-- .dokan-dashboard-wrap -->

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>

<style>
    .show_if_variable {
        display: none !important;
    }
</style>

<script>
;(function($){
    $(document).ready(function(){
        let requiredInputs = [
            $('input[name="post_title"]'),
            $('input[name="_auction_start_price"]'),
            $('input[name="_auction_bid_increment"]'),
            $('input[name="_auction_dates_from"]'),
            $('input[name="_auction_dates_to"]'),
        ];

        $('.dokan-form-container.dokan-auction-product-form').submit( function (e) {
            let validated = true;

            $.each( requiredInputs, (index, item) => {
                validated && ! item.val() ? validated = false : '';
                item.val() ? item.css( 'border', '0px solid transparent' ) : item.css( 'border', '1px solid red' );
                item.attr( 'required', 'required' );
            } )

            if ( validated ) {
                e.target.submit();
            }

            return validated;
        });

        $('.auction-datepicker').datetimepicker({
            dateFormat : 'yy-mm-dd',
            currentText: dokan.datepicker.now,
            closeText: dokan.datepicker.done,
            timeText: dokan.datepicker.time,
            hourText: dokan.datepicker.hour,
            minuteText: dokan.datepicker.minute
        });

        if($('#_auction_automatic_relist').prop('checked')){
            $('.relist_options').show();
        }else{
            $('.relist_options').hide();
        }

        $('#_auction_automatic_relist').on( 'change', function(){
            if($(this).prop('checked')){
                $('.relist_options').show();
            }else{
                $('.relist_options').hide();
            }
        });

        $('.dokan-auction-proxy-bid').on('change', 'input#_auction_proxy', function() {
            if( $(this).prop('checked') ) {
                $('.dokan-auction-sealed-bid').hide();
            } else {
                $('.dokan-auction-sealed-bid').show();
            }
        });

        $('.dokan-auction-sealed-bid').on('change', 'input#_auction_sealed', function() {
            if ( $(this).prop('checked') ) {
                $('.dokan-auction-proxy-bid').hide();
            } else {
                $('.dokan-auction-proxy-bid').show();
            }
        });
        $('input#_auction_proxy').trigger('change');
        $('input#_auction_sealed').trigger('change');

        $('.dokan-auction-relist-button').on('click', function(e) {
            e.preventDefault();

            $('.dokan-auction-date-relist').show();
            $(".dokan-auction-date").find('input').removeAttr('name');
            $(this).hide();
        });
    });
})(jQuery)

</script>
