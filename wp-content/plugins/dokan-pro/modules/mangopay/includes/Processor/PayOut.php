<?php

namespace WeDevs\DokanPro\Modules\MangoPay\Processor;

use MangoPay\PayOutEligibilityRequest;
use WP_Error;
use Exception;
use MangoPay\Money;
use MangoPay\PayOut as MangoPayOut;
use MangoPay\PayOutPaymentDetailsBankWire;
use WeDevs\DokanPro\Modules\MangoPay\Support\Meta;
use WeDevs\DokanPro\Modules\MangoPay\Support\Helper;
use WeDevs\DokanPro\Modules\MangoPay\Support\Settings;
use WeDevs\DokanPro\Modules\MangoPay\Support\Processor;
use WeDevs\DokanPro\Modules\MangoPay\Support\Validation;

/**
 * Class for processing payouts.
 *
 * @since 3.5.0
 */
class PayOut extends Processor {

    /**
     * Retrieve info about an existing (past) payout.
     *
     * @since 3.5.0
     *
     * @param int $payout_id Payout ID
     *
     * @return array|MangoPayOut|false
     */
    public static function get( $payout_id ) {
        try {
            $payout = static::config()->mangopay_api->PayOuts->Get( $payout_id );
        } catch( Exception $e ) {
            Helper::log( 'Could not fetch info. Message: ' . $e->getMessage(), 'PayOut' );
        }

        return $payout;
    }

    /**
     * Makes a transaction for Mangopay payout to user
     *
     * @since 3.5.0
     *
     * @see: https://github.com/Mangopay/mangopay2-php-sdk/blob/master/demos/workflow/scripts/payout.php
     *
     * @param int       $wp_user_id User ID
     * @param int       $order_id   Order ID
     * @param string    $currency   Currency code
     * @param float     $amount     Amount to payout
     * @param float     $fees       Fees to payout
     *
     * @return MangoPayOut|WP_Error
     */
    public static function create( $wp_user_id, $order_id, $currency, $amount, $fees ) {
        $mp_user_id = Meta::get_mangopay_account_id( $wp_user_id );
        $mp_user    = ! empty( $mp_user_id ) ? User::get( $mp_user_id ) : false;
        if ( ! $mp_user ) {
            return new WP_Error(
                'dokan-mangopay-payout-error',
                sprintf(
                    __( 'Could not do the Payout to user: %s. No mangopay user found.', 'dokan' ),
                    $wp_user_id
                )
            );
        }

        $wallet = Wallet::create( $mp_user_id );
        if ( is_wp_error( $wallet ) ) {
            return new WP_Error(
                'dokan-mangopay-payout-error',
                sprintf(
                    __( 'Could not do the Payout to user: %s. No wallet found.', 'dokan' ),
                    $wp_user_id
                )
            );
        }

        $order = \wc_get_order( $order_id );
        if ( ! $order instanceof \WC_Order ) {
            return new \WP_Error(
                'dokan-mangopay-payout-error',
                sprintf(
                    __( 'Could not do the Payout to user: %s. No valid order found.', 'dokan' ),
                    $wp_user_id
                )
            );
        }

        $parent_order = false;
        if ( $order->get_parent_id() ) {
            $parent_order = wc_get_order( $order->get_parent_id() );
        }

        if ( ! static::is_user_eligible( $wp_user_id ) ) {
            $order->add_order_note(
                sprintf(
                    __( '[%1$s] Could not do the Payout of amount %2$s to user: %3$s. Payout settings or verification of the user is incomplete.', 'dokan' ),
                    Helper::get_gateway_title(), $amount, $wp_user_id
                )
            );
            if ( $parent_order ) {
                $parent_order->add_order_note(
                    sprintf(
                        __( '[%1$s] Could not do the Payout of amount %2$s to user: %3$s. Payout settings or verification of the user is incomplete.', 'dokan' ),
                        Helper::get_gateway_title(), $amount, $wp_user_id
                    )
                );
            }
            return new \WP_Error(
                'dokan-mangopay-payout-error',
                sprintf(
                    __( 'Could not do the Payout to user: %s. Payout settings are incomplete for the user.', 'dokan' ),
                    $wp_user_id
                )
            );
        }

        $bank_account_id = Meta::get_active_bank_account( $wp_user_id );
        if ( empty( $bank_account_id ) ) {
            $order->add_order_note(
                sprintf(
                    __( '[%1$s] Could not do the Payout to user: %2$s. No active bank account found for the user.', 'dokan' ),
                    Helper::get_gateway_title(), $wp_user_id
                )
            );
            if ( $parent_order ) {
                $parent_order->add_order_note(
                    sprintf(
                        __( '[%1$s] Could not do the Payout to user: %2$s. No active bank account found for the user.', 'dokan' ),
                        Helper::get_gateway_title(), $wp_user_id
                    )
                );

            }
            return new \WP_Error(
                'dokan-mangopay-payout-error',
                sprintf(
                    __( 'Could not do the Payout to user: %s. No active bank account found for the user.', 'dokan' ),
                    $wp_user_id
                )
            );
        }

        // Create a new payout object
        $payout										 = new MangoPayOut();
        $payout->Tag								 = "Earning from WC Order #$order_id"; // string (Max. length: 255 characters)
        $payout->AuthorId							 = $mp_user_id; // required
        $payout->DebitedWalletId					 = $wallet->Id; // required, string, The unique identifier of the debited wallet.
        $payout->DebitedFunds						 = new Money(); // required
        $payout->DebitedFunds->Currency				 = $currency; // required, string
        $payout->DebitedFunds->Amount				 = round( $amount * 100 ); // required, integer
        $payout->Fees								 = new Money();
        $payout->Fees->Currency						 = $currency;
        $payout->Fees->Amount						 = round( $fees * 100 );
        $payout->BankAccountId                       = $bank_account_id; // required, string, The unique identifier of the bank account.
        $payout->PayoutPaymentDetails				 = new PayOutPaymentDetailsBankWire();
        $payout->PayoutPaymentDetails->BankAccountId = $bank_account_id;
        $payout->PayoutPaymentDetails->BankWireRef	 = "WC Order: $order_id";
        $payout->MeanOfPaymentDetails                = $payout->PayoutPaymentDetails;
        $payout->PaymentType                         = \MangoPay\PayOutPaymentType::BankWire;

        // Verify optional reference for bank wire, string (Max. length: 12 characters; only alphanumeric and spaces)
        $bank_wire_ref = "Order: $order_id";
        if( strlen( $bank_wire_ref ) <= 12 ){
            $payout->BankWireRef    = "Order: $order_id";
        } else {
            $payout->BankWireRef    = '';
        }

        // Create eligibility request object
        $eligibility                            = new PayOutEligibilityRequest();
        $eligibility->AuthorId                  = $payout->AuthorId;
        $eligibility->DebitedWalletId           = $payout->DebitedWalletId;
        $eligibility->DebitedFunds              = $payout->DebitedFunds;
        $eligibility->Fees                      = $payout->Fees;
        $eligibility->BankAccountId             = $payout->MeanOfPaymentDetails->BankAccountId;
        $eligibility->BankWireRef               = $payout->BankWireRef;
        $eligibility->PayoutModeRequested       = 'INSTANT_PAYMENT';

        try {
            // Check if instant payout is enabled and the payout is eligible for instant payout.
            if ( Settings::is_instant_payout_enabled() ) {
                // Check if the payout is eligible for instant payout
                $is_instant_payout_eligible = static::config()->mangopay_api->PayOuts->CheckInstantPayoutEligibility( $eligibility );
                Helper::log( 'PayOutEligibilityResponse:' . print_r( $is_instant_payout_eligible, true ), 'PayOut' );

                // Set payout request mode to "instant_payment" when it is enabled from settings.
                $payout->PayoutModeRequested = 'INSTANT_PAYMENT';
            }
        } catch( Exception $e ) {
            $error_details = array();
            if ( method_exists( $e, 'GetErrorDetails' ) ) {
                $error_details = $e->GetErrorDetails();
            }

            Helper::log( sprintf( 'Could not verify instant payout eligibility. Message: %s. %s', $e->getMessage(), print_r( $error_details, true ) ), 'PayOut' );
            $order->add_order_note(
                sprintf(
                    __( '[%1$s] While the instant payout option is enabled in the settings, you are currently not eligible for it based on your account status. As a result, the standard payout request process will apply for now.', 'dokan' ),
                    Helper::get_gateway_title()
                )
            );
        }

        try {
            $payout = static::config()->mangopay_api->PayOuts->Create( $payout );

            // Log the payout details
            if ( isset( $payout->PayoutModeRequested ) && $payout->PayoutModeRequested === 'INSTANT_PAYMENT' ) {
                $payout_mode = __( 'Instant', 'dokan' );
            } else {
                $payout_mode = __( 'Standard', 'dokan' );
            }
            $order->add_order_note(
                sprintf(
                    __( '[%1$s] %2$s Payout of amount %3$s successful to user: %4$s.', 'dokan' ),
                    Helper::get_gateway_title(), $payout_mode, $amount, $wp_user_id
                )
            );
        } catch ( Exception $e ) {
            $error_details = array();
            if ( method_exists( $e, 'GetErrorDetails' ) ) {
                $error_details = $e->GetErrorDetails();
            }

            Helper::log( sprintf( 'Could not do the Payout to user: %s. Message: %s. %s', $wp_user_id, $e->getMessage(), print_r( $error_details, true ) ), 'PayOut' );
            Helper::log( 'Object:' . print_r( $payout, true ), 'PayOut' );
            $order->add_order_note(
                sprintf(
                    __( '[%1$s] Payout of amount %2$s unsuccessful to user: %3$s.', 'dokan' ),
                    Helper::get_gateway_title(), $amount, $wp_user_id
                )
            );

            return new WP_Error(
                'dokan-mangopay-payout-error',
                sprintf( __( 'Could not do the Payout to user: %s. Message: %s.', 'dokan' ), $wp_user_id, $e->getMessage() )
            );
        }

        return $payout;
    }

    /**
     * Test if vendor is eligible for payout
     *
     * The vendor is valid if the vendor has his data up to date,
     * has a valid KYC, also for business account type, UBO and
     * company number will be checked.
     *
     * @since 3.5.0
     *
     * @param int|string $wp_user_id User ID
     *
     * @return boolean
     */
    public static function is_user_eligible( $wp_user_id ) {
        // Check if user has signed up for mangopay
        $mp_user_id = Meta::get_mangopay_account_id( $wp_user_id );
        if ( empty( $mp_user_id ) ) {
            return false;
        }

        // Check if user has a mangopay account
        $mp_user = User::get( $mp_user_id );
        if ( ! $mp_user ) {
            return false;
        }

        // Test if vendor has KYC valid
        // if ( ! Meta::is_user_regular_kyc_verified( $wp_user_id ) ) {
        //     return false;
        // }

        // Double check KYC verification in Mangopay end
        if ( ! Kyc::is_valid( $mp_user_id ) ) {
            return false;
        }

        // Test if vendor has active bank account
        $bank_account_id = Meta::get_active_bank_account( $wp_user_id );
        if ( empty( $bank_account_id )  ) {
            return false;
        }

        // Check the bank account in Mangopay end
        $bank_account = BankAccount::get( $mp_user_id, $bank_account_id );
        if ( empty( $bank_account ) || ! is_object( $bank_account ) ) {
            return false;
        }

        // If not business (legal) type user, no further test is required
        if ( 'LEGAL' !== $mp_user->PersonType ) {
            return true;
        }

        // If legal person type is not `business`, no further test is required
        if ( 'BUSINESS' !== $mp_user->LegalPersonType ) {
            return true;
        }

        // test if vendor has company number valid (if necessary)
        if ( empty( $mp_user->CompanyNumber ) || ! Validation::check_company_number_pattern( $mp_user->CompanyNumber ) ) {
            return false;
        }

        // test if vendor has UBO valid (if necessary)
        if ( ! Ubo::is_user_eligible( $wp_user_id ) ) {
            return false;
        }

        return true;
    }
}
