<?php
/**
 * Vendor Questions List filters.
 *
 * @since 3.11.0
 *
 * @var int    $product_id    Product id.
 * @var int    $vendor_id     Vendor id.
 * @var string $product_title Product title.
 *
 * @var array  $filters          Question filter args.
 */

use WeDevs\DokanPro\Modules\ProductQA\Vendor;

?>
<form action='' method='get' class='dokan-form-inline dokan-w12 dokan-product-qa-filter-form'>
    <?php do_action( 'dokan_product_qa_list_filter_form_start', $filters ); ?>

    <div class='dokan-form-group dokan-product-qa-product-search-form-group'>
        <select name='product_id' class='dokan-form-control dokan-product-search dokan-product-qa-product-search' data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'dokan' ); ?>" data-action='dokan_json_search_products_and_variations' data-user_ids='<?php echo dokan_get_current_user_id(); ?>'>
	        <?php if ( ! empty( $product_id ) ) : ?>
                <option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo esc_html( $product_title ); ?></option>
	        <?php else : ?>
                <option value="" selected="selected"><?php esc_html_e( 'Select an option', 'dokan' ); ?></option>
	        <?php endif; ?>
        </select>
    </div>

    <input type="submit" value="<?php esc_attr_e( 'Filter', 'dokan' ); ?>" class="dokan-btn">
    <a class="dokan-btn" href="<?php echo esc_url( dokan_get_navigation_url( Vendor::QUERY_VAR ) ); ?>"><?php esc_attr_e( 'Reset', 'dokan' ); ?> </a>

    <?php do_action( 'dokan_product_qa_list_filter_from_end', $filters ); ?>
</form>
