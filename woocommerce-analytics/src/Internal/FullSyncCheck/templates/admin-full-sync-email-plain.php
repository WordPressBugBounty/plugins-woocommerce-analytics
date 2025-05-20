<?php
/**
 * Admin full sync complete email (plain text)
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show email content.
 */
if ( $content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * @hooked WC_Emails::get_email_footer_text() Get the email footer text
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
