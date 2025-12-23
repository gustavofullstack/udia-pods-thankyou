<?php
/**
 * Woovi PIX Payment Gateway for WooCommerce
 *
 * @package Udia_Pods_Thankyou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Woovi_Pix Class
 *
 * Handles PIX payments via Woovi/OpenPix API
 */
class WC_Gateway_Woovi_Pix extends WC_Payment_Gateway {

	/**
	 * API endpoint base URL
	 */
	const API_BASE_URL = 'https://api.woovi.com/api/v1';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'woovi_pix';
		$this->icon               = ''; // Optional: Add PIX icon URL
		$this->has_fields         = false;
		$this->method_title       = __( 'Woovi PIX', 'udia-pods-thankyou' );
		$this->method_description = __( 'Aceite pagamentos via PIX com QR Code usando a plataforma Woovi/OpenPix', 'udia-pods-thankyou' );

		// Load settings
		$this->init_form_fields();
		$this->init_settings();

		// Get settings
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->testmode    = 'yes' === $this->get_option( 'testmode' );
		$this->app_id      = $this->testmode ? $this->get_option( 'test_app_id' ) : $this->get_option( 'app_id' );
		$this->expires_in  = absint( $this->get_option( 'expires_in', 86400 ) );

		// Hooks
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialize form fields for admin settings
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Ativar/Desativar', 'udia-pods-thankyou' ),
				'type'    => 'checkbox',
				'label'   => __( 'Ativar Woovi PIX', 'udia-pods-thankyou' ),
				'default' => 'no',
			),
			'title'        => array(
				'title'       => __( 'Título', 'udia-pods-thankyou' ),
				'type'        => 'text',
				'description' => __( 'Título que o cliente vê durante o checkout', 'udia-pods-thankyou' ),
				'default'     => __( 'PIX (QR Code)', 'udia-pods-thankyou' ),
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Descrição', 'udia-pods-thankyou' ),
				'type'        => 'textarea',
				'description' => __( 'Descrição que o cliente vê durante o checkout', 'udia-pods-thankyou' ),
				'default'     => __( 'Pague instantaneamente com PIX escaneando o QR Code', 'udia-pods-thankyou' ),
				'desc_tip'    => true,
			),
			'testmode'     => array(
				'title'       => __( 'Modo de Teste', 'udia-pods-thankyou' ),
				'type'        => 'checkbox',
				'label'       => __( 'Ativar modo de teste', 'udia-pods-thankyou' ),
				'default'     => 'yes',
				'description' => __( 'Use o AppID de teste para desenvolvimento', 'udia-pods-thankyou' ),
			),
			'app_id'       => array(
				'title'       => __( 'AppID (Produção)', 'udia-pods-thankyou' ),
				'type'        => 'password',
				'description' => __( 'AppID de produção obtido no painel Woovi/OpenPix', 'udia-pods-thankyou' ),
				'desc_tip'    => true,
			),
			'test_app_id'  => array(
				'title'       => __( 'AppID (Teste)', 'udia-pods-thankyou' ),
				'type'        => 'password',
				'description' => __( 'AppID de teste obtido no painel Woovi/OpenPix', 'udia-pods-thankyou' ),
				'desc_tip'    => true,
			),
			'expires_in'   => array(
				'title'       => __( 'Expiração (segundos)', 'udia-pods-thankyou' ),
				'type'        => 'number',
				'description' => __( 'Tempo em segundos até a cobrança PIX expirar (padrão: 86400 = 24 horas)', 'udia-pods-thankyou' ),
				'default'     => '86400',
				'desc_tip'    => true,
			),
			'webhook_url'  => array(
				'title'       => __( 'Webhook URL', 'udia-pods-thankyou' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: %s: webhook URL */
					__( 'Configure esta URL no painel Woovi: <code>%s</code>', 'udia-pods-thankyou' ),
					home_url( '/wc-api/woovi_pix' )
				),
			),
		);
	}

	/**
	 * Process payment
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Erro ao processar pedido', 'udia-pods-thankyou' ), 'error' );
			return;
		}

		// Prepare charge data
		$charge_data = $this->prepare_charge_data( $order );

		// Create charge via API
		$response = $this->create_charge( $charge_data );

		if ( is_wp_error( $response ) ) {
			wc_add_notice( $response->get_error_message(), 'error' );
			return;
		}

		// Save charge metadata
		$this->save_charge_metadata( $order, $response );

		// Set order status to on-hold
		$order->update_status(
			'on-hold',
			__( 'Aguardando confirmação de pagamento PIX via Woovi', 'udia-pods-thankyou' )
		);

		// Reduce stock
		wc_reduce_stock_levels( $order_id );

		// Empty cart
		WC()->cart->empty_cart();

		// Return success
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Prepare charge data for API request
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function prepare_charge_data( $order ) {
		$total_cents = absint( $order->get_total() * 100 );

		$data = array(
			'correlationID' => (string) $order->get_id(),
			'value'         => $total_cents,
			'comment'       => sprintf(
				/* translators: 1: order number, 2: site name */
				__( 'Pedido #%1$s - %2$s', 'udia-pods-thankyou' ),
				$order->get_order_number(),
				get_bloginfo( 'name' )
			),
		);

		// Add expiration if configured
		if ( $this->expires_in > 0 ) {
			$data['expiresIn'] = $this->expires_in;
		}

		// Add customer information
		$customer_info = array();

		if ( $order->get_billing_first_name() || $order->get_billing_last_name() ) {
			$customer_info[] = array(
				'key'   => __( 'Nome', 'udia-pods-thankyou' ),
				'value' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			);
		}

		if ( $order->get_billing_email() ) {
			$customer_info[] = array(
				'key'   => __( 'Email', 'udia-pods-thankyou' ),
				'value' => $order->get_billing_email(),
			);
		}

		if ( $order->get_billing_phone() ) {
			$customer_info[] = array(
				'key'   => __( 'Telefone', 'udia-pods-thankyou' ),
				'value' => $order->get_billing_phone(),
			);
		}

		if ( ! empty( $customer_info ) ) {
			$data['additionalInfo'] = $customer_info;
		}

		// Add customer object for Woovi API
		// Try to get CPF/CNPJ from common Brazilian plugins
		$tax_id = $order->get_meta( '_billing_cpf' );
		if ( empty( $tax_id ) ) {
			$tax_id = $order->get_meta( '_billing_cnpj' );
		}
		if ( empty( $tax_id ) ) {
			$tax_id = $order->get_meta( 'billing_cpf' );
		}
		if ( empty( $tax_id ) ) {
			$tax_id = $order->get_meta( 'billing_cnpj' );
		}

		// Clean tax ID (remove formatting)
		if ( ! empty( $tax_id ) ) {
			$tax_id = preg_replace( '/[^0-9]/', '', $tax_id );
		}

		// Woovi may require customer object
		if ( ! empty( $tax_id ) ) {
			$data['customer'] = array(
				'name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'email' => $order->get_billing_email(),
				'phone' => $order->get_billing_phone(),
				'taxID' => $tax_id,
			);
		}

		return apply_filters( 'woovi_pix_charge_data', $data, $order );
	}

	/**
	 * Create charge via Woovi API
	 *
	 * @param array $charge_data Charge data.
	 * @return array|WP_Error
	 */
	private function create_charge( $charge_data ) {
		$endpoint = self::API_BASE_URL . '/charge';

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => trim( $this->app_id ),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $charge_data ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'API Error: ' . $response->get_error_message() );
			return new WP_Error(
				'api_error',
				__( 'Erro ao conectar com Woovi. Tente novamente.', 'udia-pods-thankyou' )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		$this->log( sprintf( 'API Response [%d]: %s', $status_code, substr( $body, 0, 500 ) ) );
		$this->log( sprintf( 'Request payload: %s', wp_json_encode( $charge_data ) ) );

		// Handle errors
		if ( 200 !== $status_code && 201 !== $status_code ) {
			if ( 401 === $status_code ) {
				return new WP_Error(
					'invalid_appid',
					__( 'AppID inválido. Verifique as configurações do gateway.', 'udia-pods-thankyou' )
				);
			}

			$error_message = isset( $data['errors'][0]['message'] )
				? $data['errors'][0]['message']
				: __( 'Erro desconhecido ao criar cobrança', 'udia-pods-thankyou' );

			return new WP_Error( 'charge_error', $error_message );
		}

		// Validate response structure
		if ( ! isset( $data['charge'] ) ) {
			return new WP_Error(
				'invalid_response',
				__( 'Resposta inválida da API Woovi', 'udia-pods-thankyou' )
			);
		}

		return $data;
	}

	/**
	 * Save charge metadata to order
	 *
	 * @param WC_Order $order    Order object.
	 * @param array    $response API response.
	 */
	private function save_charge_metadata( $order, $response ) {
		$charge = $response['charge'];

		// Save correlation ID
		if ( isset( $charge['correlationID'] ) ) {
			$order->update_meta_data( '_woovi_correlation_id', $charge['correlationID'] );
		}

		// Save transaction ID
		if ( isset( $charge['transactionID'] ) ) {
			$order->set_transaction_id( $charge['transactionID'] );
			$order->update_meta_data( '_woovi_transaction_id', $charge['transactionID'] );
		}

		// Save QR Code data
		if ( isset( $charge['brCode'] ) ) {
			$order->update_meta_data( '_woovi_br_code', $charge['brCode'] );
		}

		if ( isset( $charge['qrCodeImage'] ) ) {
			$order->update_meta_data( '_woovi_qr_code_image', $charge['qrCodeImage'] );
		}

		// Save expiration date
		if ( isset( $charge['expiresDate'] ) ) {
			$order->update_meta_data( '_woovi_expires_date', $charge['expiresDate'] );
		}

		// Save charge status
		if ( isset( $charge['status'] ) ) {
			$order->update_meta_data( '_woovi_charge_status', $charge['status'] );
		}

		$order->save();

		$order->add_order_note(
			sprintf(
				/* translators: %s: transaction ID */
				__( 'Cobrança PIX criada via Woovi. ID da transação: %s', 'udia-pods-thankyou' ),
				isset( $charge['transactionID'] ) ? $charge['transactionID'] : 'N/A'
			)
		);
	}

	/**
	 * Output for the thank you page
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		// This will be handled by the main plugin class for better visual integration
		// See udia-pods-thankyou.php render_pix_payment_details() method
	}

	/**
	 * Add content to the WC emails
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text    Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			$br_code = $order->get_meta( '_woovi_br_code' );

			if ( $plain_text ) {
				echo "\n\n" . esc_html__( 'INSTRUÇÕES DE PAGAMENTO PIX:', 'udia-pods-thankyou' ) . "\n";
				echo esc_html__( 'Copie e cole o código abaixo no seu aplicativo bancário:', 'udia-pods-thankyou' ) . "\n\n";
				echo esc_html( $br_code ) . "\n\n";
			} else {
				echo '<h2>' . esc_html__( 'Pagamento via PIX', 'udia-pods-thankyou' ) . '</h2>';
				echo '<p>' . esc_html__( 'Use o QR Code ou copie o código PIX abaixo:', 'udia-pods-thankyou' ) . '</p>';
				echo '<p style="font-family: monospace; font-size: 12px; background: #f5f5f5; padding: 10px; word-break: break-all;">' . esc_html( $br_code ) . '</p>';
			}
		}
	}

	/**
	 * Log messages
	 *
	 * @param string $message Log message.
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Woovi PIX] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
