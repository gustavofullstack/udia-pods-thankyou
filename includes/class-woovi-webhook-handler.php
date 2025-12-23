<?php
/**
 * Woovi Webhook Handler
 *
 * @package Udia_Pods_Thankyou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Woovi_Webhook_Handler Class
 *
 * Handles webhook notifications from Woovi/OpenPix
 */
class Woovi_Webhook_Handler {

	/**
	 * Initialize webhook handler
	 */
	public static function init() {
		add_action( 'woocommerce_api_woovi_pix', array( __CLASS__, 'handle_webhook' ) );
	}

	/**
	 * Handle incoming webhook
	 */
	public static function handle_webhook() {
		// Get raw POST data
		$raw_post = file_get_contents( 'php://input' );

		self::log( 'Webhook received: ' . $raw_post );

		// Decode JSON payload
		$payload = json_decode( $raw_post, true );

		if ( ! $payload ) {
			self::log( 'Invalid JSON payload' );
			status_header( 400 );
			die( 'Invalid JSON' );
		}

		// Process the webhook
		$result = self::process_webhook( $payload );

		if ( is_wp_error( $result ) ) {
			self::log( 'Webhook processing error: ' . $result->get_error_message() );
			status_header( 400 );
			die( $result->get_error_message() );
		}

		// Return success
		status_header( 200 );
		die( 'OK' );
	}

	/**
	 * Process webhook payload
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|WP_Error
	 */
	private static function process_webhook( $payload ) {
		// Woovi webhook structure varies by event type
		// Common events: charge.completed, charge.refund, pix.received
		
		$event = isset( $payload['event'] ) ? $payload['event'] : null;
		$charge = isset( $payload['charge'] ) ? $payload['charge'] : ( isset( $payload['pix'] ) ? $payload['pix'] : null );

		if ( ! $charge ) {
			return new WP_Error( 'missing_charge', 'No charge data in webhook' );
		}

		// Get correlation ID to find the order
		$correlation_id = isset( $charge['correlationID'] ) ? $charge['correlationID'] : null;

		if ( ! $correlation_id ) {
			return new WP_Error( 'missing_correlation_id', 'No correlationID in webhook' );
		}

		// Find order by ID (we used order ID as correlationID)
		$order = wc_get_order( absint( $correlation_id ) );

		if ( ! $order ) {
			return new WP_Error( 'order_not_found', sprintf( 'Order #%s not found', $correlation_id ) );
		}

		// Verify this order is using Woovi PIX payment method
		if ( 'woovi_pix' !== $order->get_payment_method() ) {
			return new WP_Error( 'invalid_payment_method', 'Order is not using Woovi PIX' );
		}

		// Check if already processed (prevent duplicate webhooks)
		$existing_transaction_id = $order->get_transaction_id();
		$new_transaction_id = isset( $charge['transactionID'] ) ? $charge['transactionID'] : null;

		if ( $existing_transaction_id && $new_transaction_id && $existing_transaction_id === $new_transaction_id ) {
			$order_status = $order->get_status();
			if ( in_array( $order_status, array( 'processing', 'completed' ), true ) ) {
				self::log( sprintf( 'Duplicate webhook for order #%s (already processed)', $order->get_id() ) );
				return true; // Already processed, but return success
			}
		}

		// Process based on charge status
		$charge_status = isset( $charge['status'] ) ? strtoupper( $charge['status'] ) : null;

		self::log( sprintf( 'Processing webhook for order #%s, status: %s', $order->get_id(), $charge_status ) );

		switch ( $charge_status ) {
			case 'COMPLETED':
			case 'CONCLUIDA':
				self::handle_payment_completed( $order, $charge );
				break;

			case 'ACTIVE':
			case 'ATIVA':
				self::handle_payment_active( $order, $charge );
				break;

			case 'EXPIRED':
			case 'EXPIRADA':
				self::handle_payment_expired( $order, $charge );
				break;

			default:
				self::log( sprintf( 'Unknown charge status: %s', $charge_status ) );
				break;
		}

		return true;
	}

	/**
	 * Handle completed payment
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $charge Charge data.
	 */
	private static function handle_payment_completed( $order, $charge ) {
		// Check if order is already completed/processing
		if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
			self::log( sprintf( 'Order #%s already completed', $order->get_id() ) );
			return;
		}

		// Update transaction ID if available
		if ( isset( $charge['transactionID'] ) ) {
			$order->set_transaction_id( $charge['transactionID'] );
		}

		// Update charge status metadata
		$order->update_meta_data( '_woovi_charge_status', 'COMPLETED' );
		
		// Save payer information if available
		if ( isset( $charge['payer'] ) ) {
			$order->update_meta_data( '_woovi_payer_info', wp_json_encode( $charge['payer'] ) );
		}

		// Save payment date
		if ( isset( $charge['paidAt'] ) ) {
			$order->update_meta_data( '_woovi_paid_at', $charge['paidAt'] );
			$order->set_date_paid( strtotime( $charge['paidAt'] ) );
		}

		$order->save();

		// Add order note
		$note = __( 'Pagamento PIX confirmado via Woovi.', 'udia-pods-thankyou' );
		
		if ( isset( $charge['transactionID'] ) ) {
			$note .= ' ' . sprintf(
				/* translators: %s: transaction ID */
				__( 'ID da transação: %s', 'udia-pods-thankyou' ),
				$charge['transactionID']
			);
		}

		if ( isset( $charge['paidAt'] ) ) {
			$note .= ' ' . sprintf(
				/* translators: %s: payment date */
				__( 'Pago em: %s', 'udia-pods-thankyou' ),
				date_i18n( 'd/m/Y H:i:s', strtotime( $charge['paidAt'] ) )
			);
		}

		$order->add_order_note( $note );

		// Complete payment
		$order->payment_complete( isset( $charge['transactionID'] ) ? $charge['transactionID'] : '' );

		self::log( sprintf( 'Payment completed for order #%s', $order->get_id() ) );
	}

	/**
	 * Handle active payment (charge created but not paid yet)
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $charge Charge data.
	 */
	private static function handle_payment_active( $order, $charge ) {
		// Update metadata
		$order->update_meta_data( '_woovi_charge_status', 'ACTIVE' );
		$order->save();

		$order->add_order_note( __( 'Cobrança PIX ativa, aguardando pagamento.', 'udia-pods-thankyou' ) );

		self::log( sprintf( 'Charge active for order #%s', $order->get_id() ) );
	}

	/**
	 * Handle expired payment
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $charge Charge data.
	 */
	private static function handle_payment_expired( $order, $charge ) {
		// Only process if order is still on-hold
		if ( ! $order->has_status( 'on-hold' ) ) {
			return;
		}

		// Update metadata
		$order->update_meta_data( '_woovi_charge_status', 'EXPIRED' );
		$order->save();

		// Cancel the order
		$order->update_status(
			'cancelled',
			__( 'Cobrança PIX expirou. Pedido cancelado automaticamente.', 'udia-pods-thankyou' )
		);

		self::log( sprintf( 'Charge expired for order #%s', $order->get_id() ) );
	}

	/**
	 * Log webhook events
	 *
	 * @param string $message Log message.
	 */
	private static function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Woovi Webhook] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		// Also save to WooCommerce logs if available
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->info( $message, array( 'source' => 'woovi-webhook' ) );
		}
	}
}

// Initialize webhook handler
Woovi_Webhook_Handler::init();
