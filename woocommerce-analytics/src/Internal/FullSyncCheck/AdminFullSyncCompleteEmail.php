<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Analytics\Internal\FullSyncCheck;

defined( 'ABSPATH' ) || exit;

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Email', false ) && function_exists( 'WC' ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-wc-emails.php';
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/emails/class-wc-email.php';
}

/**
 * Class AdminFullSyncCompleteEmail
 *
 * @package Automattic\WooCommerce\Admin\Internal\FullSyncCheck
 */
class AdminFullSyncCompleteEmail extends \WC_Email {

	/**
	 * Email key used in WooCommerce emails registry.
	 *
	 * @var string
	 */
	const EMAIL_KEY = 'WC_Email_Admin_Full_Sync_Complete';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'admin_full_sync_complete';
		$this->title          = __( 'Order Attribution Report Ready', 'woocommerce-analytics' );
		$this->description    = __( 'Email sent to admin when the order attribution report is ready.', 'woocommerce-analytics' );
		$this->template_html  = 'admin-full-sync-email.php';
		$this->template_plain = 'admin-full-sync-email-plain.php';
		$this->template_base  = __DIR__ . '/templates/';

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Your order attribution report is ready!', 'woocommerce-analytics' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Your order attribution report is ready!', 'woocommerce-analytics' );
	}

	/**
	 * Get the greeting message for the email.
	 *
	 * @return string The greeting message with the admin's name or fallback.
	 */
	private function get_message_greeting() {
		$admin_email = $this->get_recipient();
		$admin_user  = get_user_by( 'email', $admin_email );

		if ( $admin_user ) {
			/* translators: %s: admin user's name */
			return sprintf( __( 'Hi %s,', 'woocommerce-analytics' ), esc_html( $admin_user->display_name ) );
		}

		return __( 'Hi there,', 'woocommerce-analytics' );
	}

	/**
	 * Get the email content based on format.
	 *
	 * @param bool $is_html Whether to return HTML content.
	 * @return string
	 */
	private function get_email_content( bool $is_html ) {
		$line_break        = $is_html ? '<br/>' : "\n";
		$double_line_break = $line_break . $line_break;
		$strong_open       = $is_html ? '<strong>' : '';
		$strong_close      = $is_html ? '</strong>' : '';

		$link_to_report = '';
		if ( $is_html ) {
			$link_to_report = sprintf(
				/* translators: %1$s is an open anchor tag (<a>) and %2$s is a close link tag and line breaks (</a><br/><br/>). */
				__( '%1$sView my report%2$s', 'woocommerce-analytics' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-admin&path=/analytics/order-attribution' ) ) . '">',
				'</a>' . $double_line_break
			);
		} else {
			$link_to_report = sprintf(
				/* translators: %s: url to order attribution report */
				__( 'View my report: %s', 'woocommerce-analytics' ),
				admin_url( 'admin.php?page=wc-admin&path=/analytics/order-attribution' )
			) . $double_line_break;
		}

		$content_lines = array(
			$this->get_message_greeting() . $double_line_break,
			sprintf(
				/* translators: %1$s: open strong tag, %2$s: close strong tag, %3$s: line break */
				__( 'Great news! We\'ve processed your data, and your %1$sorder attribution report%2$s is now ready to view.%3$s', 'woocommerce-analytics' ),
				$strong_open,
				$strong_close,
				$double_line_break
			),
			$link_to_report,
			/* translators: %s: line breaks */
			sprintf( __( 'Use this data to:%s', 'woocommerce-analytics' ), $double_line_break ),
			/* translators: %s: line break */
			sprintf( __( '- See which marketing channels drive your best sales.%s', 'woocommerce-analytics' ), $line_break ),
			/* translators: %s: line break */
			sprintf( __( '- Track your customer journey from first click to purchase.%s', 'woocommerce-analytics' ), $line_break ),
			/* translators: %s: line breaks */
			sprintf( __( '- Gain a deeper understanding of your store\'s performance.%s', 'woocommerce-analytics' ), $double_line_break ),
			/* translators: %s: line breaks */
			sprintf( __( 'We hope this info helps you continue to grow your business.%s', 'woocommerce-analytics' ), $double_line_break ),
			/* translators: %s: line break */
			sprintf( __( 'Happy selling,%s', 'woocommerce-analytics' ), $line_break ),
			sprintf(
				/* translators: %1$s: open strong tag, %2$s: close strong tag and line breaks */
				__( '%1$sThe Woo Team%2$s', 'woocommerce-analytics' ),
				$strong_open,
				$strong_close . $double_line_break
			),
		);

		return wc_get_template_html(
			$is_html ? $this->template_html : $this->template_plain,
			array(
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => ! $is_html,
				'email'         => $this,
				'content'       => implode( '', $content_lines ),
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return $this->get_email_content( true );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return $this->get_email_content( false );
	}

	/**
	 * Send the email notification.
	 */
	public static function send_email_notification(): void {
		/** @var AdminFullSyncCompleteEmail $email */
		$email = WC()->mailer()->emails[ self::EMAIL_KEY ];
		$email->trigger();
	}

	/**
	 * Trigger the sending of this email.
	 */
	public function trigger() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}
}
