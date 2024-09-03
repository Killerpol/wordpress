<?php

namespace WeDevs\DokanPro\Modules\VendorAnalytics;

use Google\Auth\FetchAuthTokenInterface;

class Token implements FetchAuthTokenInterface {

    /**
     * @var \Dokan_Client
     */
    protected $auth;

    public function __construct( $auth = null ) {
        $this->auth = $auth;
    }

    /**
	 * @inheritDoc
	 */
	public function fetchAuthToken( callable $httpHandler = null ) {
        return json_decode( $this->auth->getAccessToken(), true );
	}

	/**
	 * @inheritDoc
	 */
	public function getCacheKey() {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getLastReceivedToken() {
		return json_decode( $this->auth->getAccessToken(), true );
	}
}
