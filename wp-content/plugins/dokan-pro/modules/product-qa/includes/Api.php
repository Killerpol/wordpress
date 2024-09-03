<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\REST\AnswersApi;
use WeDevs\DokanPro\Modules\ProductQA\REST\QuestionsApi;

defined( 'ABSPATH' ) || exit;

/**
 * Rest API related class.
 *
 * @since 3.11.0
 */
class Api {

    /**
     * Constructor.
     */
    public function __construct() {
        // set action hooks
        add_filter( 'dokan_rest_api_class_map', [ $this, 'register_class_map' ] );
    }

    /**
     * Rest api class map.
     *
     * @since 3.11.0
     *
     * @param array $classes API Classes.
     *
     * @return array
     */
    public function register_class_map( $classes ): array {
        $classes[ DOKAN_PRODUCT_QA_INC . '/REST/QuestionsApi.php' ] = QuestionsApi::class;
        $classes[ DOKAN_PRODUCT_QA_INC . '/REST/AnswersApi.php' ]   = AnswersApi::class;

        return $classes;
    }
}
