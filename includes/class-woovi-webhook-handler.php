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
	 * Event types mapping
	 */
	const EVENT_CHARGE_COMPLETED = 'OPENPIX:CHARGE_COMPLETED';
	const EVENT_CHARGE_EXPIRED   = 'OPENPIX:CHARGE_EXPIRED';
	const EVENT_CHARGE_CREATED   = 'OPENPIX:CHARGE_CREATED';

	/**
	 * Initialize webhook handler
	 */
	public static function init() {
		add_action( 'woocommerce_api_woovi_pix', array( __CLASS__, 'handle_webhook' ) );
	}

	/**
	 * Get gateway settings
	 *
	 * @return array
	 */
	private static function get_gateway_settings() {
		$settings = get_option( 'woocommerce_woovi_pix_settings', array() );
		return $settings;
	}

	/**
	 * Validate HMAC signature
	 *
	 * @param string $payload   Raw request body.
	 * @param string $signature Signature from header.
	 * @return bool
	 */
	private static function validate_hmac_signature( $payload, $signature ) {
		$settings = self::get_gateway_settings();
		$secret   = isset( $settings['hmac_secret'] ) ? $settings['hmac_secret'] : '';

		// If no secret configured, skip validation (log warning)
		if ( empty( $secret ) ) {
			self::log( 'WARNING: HMAC Secret Key not configured. Skipping signature validation.' );
			return true;
		}

		// If no signature provided in request, reject
		if ( empty( $signature ) ) {
			self::log( 'ERROR: No X-OpenPix-Signature header provided in request.' );
			return false;
		}

		// Calculate expected signature using SHA1
		$expected = base64_encode( hash_hmac( 'sha1', $payload, $secret, true ) );

		// Compare signatures
		$valid = hash_equals( $expected, $signature );

		if ( ! $valid ) {
			self::log( sprintf( 'HMAC validation failed. Expected: %s, Got: %s', $expected, $signature ) );
		} else {
			self::log( 'HMAC signature validated successfully.' );
		}

		return $valid;
	}

	/**
	 * Handle incoming webhook
	 */
	public static function handle_webhook() {
		// Get raw POST data
		$raw_post = file_get_contents( 'php://input' );

		self::log( 'Webhook received: ' . substr( $raw_post, 0, 500 ) );

		// Get HMAC signature from header
		$signature = isset( $_SERVER['HTTP_X_OPENPIX_SIGNATURE'] ) 
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_OPENPIX_SIGNATURE'] ) ) 
			: '';

		// Validate HMAC signature
		if ( ! self::validate_hmac_signature( $raw_post, $signature ) ) {
			self::log( 'Invalid HMAC signature - rejecting webhook' );
			status_header( 401 );
			die( 'Invalid signature' );
		}

		// Decode JSON payload
		$payload = json_decode( $raw_post, true );

		if ( ! $payload ) {
			self::log( 'Invalid JSON payload' );
			status_header( 400 );
			die( 'Invalid JSON' );
		}

		// Check for test webhook (OpenPix sends test webhooks on creation)
		if ( isset( $payload['data_criacao'] ) && isset( $payload['evento'] ) && 'teste_webhook' === $payload['evento'] ) {
			self::log( 'Test webhook received - responding OK' );
			status_header( 200 );
			die( 'OK' );
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
		// Get event type from OpenPix format
		$event = isset( $payload['event'] ) ? $payload['event'] : null;

		self::log( sprintf( 'Processing event: %s', $event ) );

		// Get charge data
		$charge = isset( $payload['charge'] ) ? $payload['charge'] : null;

		if ( ! $charge ) {
			// Try to get from pix object
			if ( isset( $payload['pix']['charge'] ) ) {
				$charge = $payload['pix']['charge'];
			} else {
				return new WP_Error( 'missing_charge', 'No charge data in webhook' );
			}
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
		$new_transaction_id      = isset( $charge['transactionID'] ) ? $charge['transactionID'] : null;

		if ( $existing_transaction_id && $new_transaction_id && $existing_transaction_id === $new_transaction_id ) {
			$order_status = $order->get_status();
			if ( in_array( $order_status, array( 'processing', 'completed' ), true ) ) {
				self::log( sprintf( 'Duplicate webhook for order #%s (already processed)', $order->get_id() ) );
				return true; // Already processed, but return success
			}
		}

		self::log( sprintf( 'Processing webhook for order #%s, event: %s', $order->get_id(), $event ) );

		// Process based on event type (OpenPix format)
		switch ( $event ) {
			case self::EVENT_CHARGE_COMPLETED:
			case 'OPENPIX:CHARGE_COMPLETED_NOT_SAME_CUSTOMER_PAYER':
				self::handle_payment_completed( $order, $charge, $payload );
				break;

			case self::EVENT_CHARGE_CREATED:
				self::handle_payment_active( $order, $charge );
				break;

			case self::EVENT_CHARGE_EXPIRED:
				self::handle_payment_expired( $order, $charge );
				break;

			default:
				// Try fallback to charge status
				$charge_status = isset( $charge['status'] ) ? strtoupper( $charge['status'] ) : null;
				self::log( sprintf( 'Unknown event, trying charge status: %s', $charge_status ) );
				
				switch ( $charge_status ) {
					case 'COMPLETED':
					case 'CONCLUIDA':
						self::handle_payment_completed( $order, $charge, $payload );
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
						self::log( sprintf( 'Unknown event: %s, status: %s', $event, $charge_status ) );
						break;
				}
				break;
		}

		return true;
	}

	/**
	 * Handle completed payment
	 *
	 * @param WC_Order $order   Order object.
	 * @param array    $charge  Charge data.
	 * @param array    $payload Full webhook payload.
	 */
	private static function handle_payment_completed( $order, $charge, $payload = array() ) {
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
		$payer = isset( $payload['payer'] ) ? $payload['payer'] : ( isset( $charge['payer'] ) ? $charge['payer'] : null );
		if ( $payer ) {
			$order->update_meta_data( '_woovi_payer_info', wp_json_encode( $payer ) );
		}

		// Save pix transaction data if available
		if ( isset( $payload['pix'] ) ) {
			$pix = $payload['pix'];
			
			if ( isset( $pix['endToEndId'] ) ) {
				$order->update_meta_data( '_woovi_end_to_end_id', $pix['endToEndId'] );
			}
			
			if ( isset( $pix['time'] ) ) {
				$order->update_meta_data( '_woovi_paid_at', $pix['time'] );
				$order->set_date_paid( strtotime( $pix['time'] ) );
			}
		}

		// Fallback to charge paidAt
		if ( isset( $charge['paidAt'] ) ) {
			$order->update_meta_data( '_woovi_paid_at', $charge['paidAt'] );
			$order->set_date_paid( strtotime( $charge['paidAt'] ) );
		}

		$order->save();

		// Add order note
		$note = __( '✅ Pagamento PIX confirmado via OpenPix/Woovi.', 'udia-pods-thankyou' );

		if ( isset( $charge['transactionID'] ) ) {
			$note .= ' ' . sprintf(
				/* translators: %s: transaction ID */
				__( 'ID da transação: %s', 'udia-pods-thankyou' ),
				$charge['transactionID']
			);
		}

		if ( isset( $payload['pix']['endToEndId'] ) ) {
			$note .= ' ' . sprintf(
				/* translators: %s: end to end ID */
				__( 'End-to-End ID: %s', 'udia-pods-thankyou' ),
				$payload['pix']['endToEndId']
			);
		}

		$order->add_order_note( $note );

		// Complete payment - this changes status from on-hold to processing
		$order->payment_complete( isset( $charge['transactionID'] ) ? $charge['transactionID'] : '' );

		self::log( sprintf( '✅ Payment completed for order #%s - Status changed to processing', $order->get_id() ) );
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
			__( '❌ Cobrança PIX expirou. Pedido cancelado automaticamente.', 'udia-pods-thankyou' )
		);

		self::log( sprintf( 'Charge expired for order #%s - Order cancelled', $order->get_id() ) );
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
