<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

use WC_Product;

/**
 * OrderMinMax Module Helper.
 *
 * @since 3.7.13
 */
class Helper {

    /**
     * Min-max meta key.
     *
     * @since 3.7.13
     *
     * @var string
     */
    const MIN_MAX_META_KEY = '_dokan_min_max_meta';

    /**
     * Is min-max-qty is enabled for dokan.
     *
     * @since 3.7.13
     *
     * @return boolean
     */
    public static function is_enabled_min_max_qty() {
        return 'on' === dokan_get_option( 'enable_min_max_quantity', 'dokan_selling', 'off' );
    }

    /**
     * Is min-max-amount is enabled for dokan.
     *
     * @since 3.7.13
     *
     * @return boolean
     */
    public static function is_enabled_min_max_amount() {
        return 'on' === dokan_get_option( 'enable_min_max_amount', 'dokan_selling', 'off' );
    }

    /**
     * Is min-max qty and amount is enabled or not.
     *
     * @since 3.7.13
     *
     * @return boolean
     */
    public static function is_enabled_min_max_qty_or_amount() {
        return self::is_enabled_min_max_qty() || self::is_enabled_min_max_amount();
    }

    /**
     * Get product min-max meta.
     *
     * @since 3.7.13
     *
     * @param WC_Product $product
     *
     * @return array|null
     */
    public static function get_product_min_max_meta( $product ) {
        return $product->get_meta( self::MIN_MAX_META_KEY );
    }

    /**
     * Set product min-max meta.
     *
     * @since 3.7.13
     *
     * @param WC_Product $product
     * @param array      $meta    Order min max meta data.
     *
     * @return void
     */
    public static function set_product_min_max_meta( $product, $meta = [] ) {
        $product->update_meta_data( self::MIN_MAX_META_KEY, $meta );
        $product->save();
    }

    /**
     * Check product is applicable with min-max criteria.
     *
     * @since 3.10.3
     *
     * @param int $product_id
     *
     * @return bool
     */
    public static function is_min_max_rules_valid_for_product( $product_id ) {

        /**
         * Determines if this product meets the specified minimum-maximum criteria.
         *
         * This filter allows for extending or modifying the validation logic,
         * enabling custom rules to be applied for determining the
         * product's compliance with the min-max criteria.
         *
         * @since 3.10.3
         *
         * @param int $product_id The ID of the product to check.
         *
         * @return bool True if the product meets the min-max criteria, false otherwise. Defaults to true.
         */
        return apply_filters( 'dokan_validate_min_max_rules_for_product', true, (int) $product_id );
    }
}
