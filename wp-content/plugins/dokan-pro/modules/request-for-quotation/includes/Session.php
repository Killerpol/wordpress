<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

class Session {

    /**
     * @var $quote_session_storage
     */
    private static $quote_session_storage = false;

    /**
     * Session Constructor.
     *
     * @since 3.6.0
     *
     * @return void
     */
    private function __construct() {}

    /**
     * Load session storage.
     *
     * @since 3.6.0
     *
     * @return bool|\WeDevs\DokanPro\Storage\Session
     */
    public static function init() {
        if ( ! self::$quote_session_storage ) {
            self::$quote_session_storage = new \WeDevs\DokanPro\Storage\Session( DOKAN_SESSION_QUOTE_KEY );
        }

        return self::$quote_session_storage;
    }

}
