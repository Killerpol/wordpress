<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Emails;

use WC_Email;
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;

/**
 * New Quote Email.
 *
 * An email sent to the admin, vendor and customer when a new quote is created.
 *
 * @class       NewQuote
 * @version     3.6.0
 * @package     Dokan/Modules/RequestAQuote/Emails
 * @author      weDevs
 * @extends     WC_Email
 */
class NewQuote extends WC_Email {

    /**
     * @var int $quote_id ID for the quote
     */
    public $quote_id;

    /**
     * @var mixed $request_quote Request quote object
     */
    public $request_quote;

    /**
     * @var mixed $quote_details Quote details
     */
    public $quote_details;

    /**
     * @var mixed $customer_info Customer info
     */
    public $customer_info;

    /**
     * @var string $sending_to Sending to whom
     */
    public $sending_to;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_request_new_quote';
        $this->title          = __( 'Dokan Request New Quote', 'dokan' );
        $this->description    = __( 'New quote emails are sent to chosen recipient(s) when a new quote is created.', 'dokan' );
        $this->template_html  = 'emails/request-new-quote-email.php';
        $this->template_plain = 'emails/plain/request-new-quote-email.php';
        $this->template_base  = DOKAN_RAQ_TEMPLATE_PATH;
        // Triggers for this email.
        add_action( 'after_dokan_request_quote_inserted', [ $this, 'trigger' ] );
        // Call parent constructor.
        parent::__construct();

        // Other settings.
        $this->recipient = 'vendor@ofthe.product,customer@ofthe.quote';
    }

    /**
     * Get email subject.
     *
     * @since  3.6.0
     * @return string
     */
    public function get_default_subject() {
        return __( 'New request quote #{quote_number} on {site_title} - {quote_date}', 'dokan' );
    }

    /**
     * Get email heading.
     *
     * @since  3.6.0
     * @return string
     */
    public function get_default_heading() {
        return __( 'New Request Quote: #{quote_number}', 'dokan' );
    }

    /**
     * Set email heading for customer.
     *
     * @since  3.6.0
     * @return string
     */
    public function set_customer_email_heading() {
        return __( 'Thank you for your Quote request', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @since 3.6.0
     *
     * @param int $quote_id
     *
     * @return void
     */
    public function trigger( $quote_id ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $this->setup_locale();
        if ( ! $quote_id ) {
            return;
        }

        $this->quote_id      = $quote_id;
        $this->request_quote = Helper::get_request_quote_by_id( $quote_id );
        $this->quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );
        $this->customer_info = maybe_unserialize( $this->request_quote->customer_info );
        $this->placeholders  = [
            '{site_title}'   => $this->get_blogname(),
            '{quote_date}'   => dokan_format_date( $this->request_quote->created_at ),
            '{quote_number}' => $quote_id,
        ];

        $seller_email = '';
        foreach ( $this->quote_details as $quote_detail ) {
            $product = wc_get_product( (int) $quote_detail->product_id );
            if ( ! is_a( $product, 'WC_Product' ) ) {
                return;
            }

            $seller_info = dokan_get_vendor_by_product( $product );
            if ( ! $seller_info ) {
                return;
            }
            $seller_email = $seller_info->get_email();
            break;
        }

        $this->sending_to = '';
        if ( ! empty( $seller_email ) ) {
            $this->sending_to = 'seller';
            $this->send( $seller_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        if ( ! empty( $this->customer_info['email_field'] ) ) {
            add_filter( 'woocommerce_email_heading_' . $this->id, [ $this, 'set_customer_email_heading' ] );
            $this->sending_to = 'customer';
            $this->send( $this->customer_info['email_field'], $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        ob_start();
        wc_get_template(
            $this->template_html, [
				'quote_id'      => $this->quote_id,
				'request_quote' => $this->request_quote,
				'customer_info' => $this->customer_info,
				'quote_details' => $this->quote_details,
				'email_heading' => $this->get_heading(),
				'email'         => $this,
				'sending_to'    => $this->sending_to,
			], 'dokan/', $this->template_base
        );

        return ob_get_clean();
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        ob_start();
        wc_get_template(
            $this->template_plain, [
				'quote_id'      => $this->quote_id,
				'request_quote' => $this->request_quote,
				'customer_info' => $this->customer_info,
				'quote_details' => $this->quote_details,
				'email_heading' => $this->get_heading(),
				'email'         => $this,
				'sending_to'    => $this->sending_to,
			], 'dokan/', $this->template_base
        );

        return ob_get_clean();
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled'    => [
                'title'   => __( 'Enable/Disable', 'dokan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'dokan' ),
                'default' => 'yes',
            ],
            'subject'    => [
                'title'       => __( 'Subject', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                /* translators: %s: list of placeholders */
                'description' => sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>{site_title}, {quote_date}, {quote_number}</code>' ),
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading'    => [
                'title'       => __( 'Email heading', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                /* translators: %s: list of placeholders */
                'description' => sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>{site_title}, {quote_date}, {quote_number}</code>' ),
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ],
            'email_type' => [
                'title'       => __( 'Email type', 'dokan' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'dokan' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ],
        ];
    }

}
