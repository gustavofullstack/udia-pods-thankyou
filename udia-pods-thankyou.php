<?php
/**
 * Plugin Name: Udia Pods Pós-Checkout Experience
 * Description: Plugin proprietário da Udia Pods para páginas de obrigado/tutoriais pós-checkout no WordPress, Elementor e WooCommerce com identidade visual própria.
 * Author: Udia Pods
 * Version: 1.0.11
 * Text Domain: udia-pods-thankyou
 * GitHub Plugin URI: https://github.com/gustavofullstack/udia-pods-thankyou
 * GitHub Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Udia_Pods_Thankyou {
	const VERSION   = '1.0.0';
	const HANDLE    = 'udia-pods-thankyou';
	const SHORTCODE = 'udia_pods_thankyou';

	/**
	 * Singleton bootstrap.
	 */
	public static function init(): void {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}
	}

	/**
	 * Hook everything.
	 */
	private function __construct() {
		$this->init_auto_updater();
		add_action( 'plugins_loaded', [ $this, 'init_gateway' ], 11 );
		add_action( 'init', [ $this, 'register_assets' ] );
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
		add_action( 'woocommerce_thankyou', [ $this, 'render_hook' ], 5 );
		add_action( 'wp', [ $this, 'maybe_override_thankyou_content' ] );
	}

	/**
	 * Initialize payment gateway after WooCommerce loads.
	 */
	public function init_gateway(): void {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Load gateway classes
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-gateway-woovi-pix.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-woovi-webhook-handler.php';

		// Register gateway with WooCommerce
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_woovi_gateway' ] );
	}

	/**
	 * Add Woovi gateway to WooCommerce.
	 *
	 * @param array $gateways Payment gateways.
	 * @return array
	 */
	public function add_woovi_gateway( array $gateways ): array {
		if ( class_exists( 'WC_Gateway_Woovi_Pix' ) ) {
			$gateways[] = 'WC_Gateway_Woovi_Pix';
		}
		return $gateways;
	}

	/**
	 * Initialize the update checker.
	 */
	private function init_auto_updater(): void {
		if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
		}

		if ( class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
			$myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				'https://github.com/gustavofullstack/udia-pods-thankyou',
				__FILE__,
				'udia-pods-thankyou'
			);

			// Use GitHub Releases for updates
			$myUpdateChecker->getVcsApi()->enableReleaseAssets();
			
			// Set branch
			$myUpdateChecker->setBranch( 'main' );

			// Add custom update message for better UX
			add_filter(
				'puc_manual_check_message-' . $myUpdateChecker->slug,
				function( $message, $status ) {
					if ( 'no_update' === $status ) {
						return '<strong>🎉 Você está usando a versão mais recente!</strong>';
					}
					return $message;
				},
				10,
				2
			);
		}
	}

	/**
	 * Register CSS/JS so they can be enqueued on demand.
	 */
	public function register_assets(): void {
		$url = plugin_dir_url( __FILE__ );

		wp_register_style(
			self::HANDLE,
			$url . 'assets/css/thankyou.css',
			[],
			self::VERSION
		);

		wp_register_script(
			self::HANDLE,
			$url . 'assets/js/thankyou.js',
			[],
			self::VERSION,
			true
		);
	}

	/**
	 * Shortcode output used em páginas criadas com Gutenberg/Elementor/etc.
	 */
	public function render_shortcode( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'order_id'        => 0,
				'show_tutorial'   => 'yes',
				'highlight_cta'   => '',
				'show_progress'   => 'yes',
				'show_chat_widget'=> 'yes',
			],
			$atts,
			self::SHORTCODE
		);

		$order = $this->resolve_order( absint( $atts['order_id'] ) );

		return $this->render_template( $order, $atts );
	}

	/**
	 * Output via hook direto na página de obrigado nativa do WooCommerce.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function render_hook( int $order_id ): void {
		echo $this->render_template( $this->resolve_order( $order_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Força o layout mesmo quando o tema sobrescreve o template padrão.
	 */
	public function maybe_override_thankyou_content(): void {
		if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
			return;
		}

		if ( ! is_checkout() || ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		add_filter( 'the_content', [ $this, 'filter_thankyou_content' ], 999 );
	}

	/**
	 * Substitui o conteúdo da página de obrigado por completo.
	 *
	 * @param string $content Original content.
	 * @return string
	 */
	public function filter_thankyou_content( string $content ): string {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$order_id = absint( get_query_var( 'order-received' ) );

		return $this->render_template( $this->resolve_order( $order_id ) );
	}

	/**
	 * Try recovering an order para mostrar na página.
	 *
	 * @param int $order_id Optional order passed via shortcode.
	 * @return WC_Order|null
	 */
	private function resolve_order( int $order_id = 0 ) {
		if ( $order_id ) {
			return wc_get_order( $order_id );
		}

		if ( function_exists( 'wc_get_customer_last_order' ) && is_user_logged_in() ) {
			return wc_get_customer_last_order( get_current_user_id(), 'any' );
		}

		return null;
	}

	/**
	 * Prepare base data so JS também possa reutilizar.
	 *
	 * @param WC_Order|null $order Order instance.
	 * @param array         $atts  Shortcode attributes.
	 */
	private function enqueue_assets( $order, array $atts ): void {
		if ( did_action( 'wp_enqueue_scripts' ) ) {
			wp_enqueue_style( self::HANDLE );
			wp_enqueue_script( self::HANDLE );
		}

		wp_localize_script(
			self::HANDLE,
			'UdiaPodsThankyou',
			[
				'orderId'    => $order ? $order->get_order_number() : null,
				'status'     => $order ? $order->get_status() : null,
				'billing'    => $order ? $order->get_formatted_billing_full_name() : '',
				'highlightCta' => sanitize_text_field( $atts['highlight_cta'] ?? '' ),
			]
		);
	}

	/**
	 * Shared renderer used pelo shortcode e action.
	 *
	 * @param WC_Order|null $order Order.
	 * @param array         $atts  Attributes/options.
	 */
	private function render_template( $order, array $atts = [] ): string {
		$this->enqueue_assets( $order, $atts );

		ob_start();
		?>
		<section class="utp-wrapper" data-order-status="<?php echo esc_attr( $order ? $order->get_status() : 'pending' ); ?>">
			<?php $this->render_header( $order ); ?>
			
			<?php
			$is_woovi_pix_pending = $order && 'woovi_pix' === $order->get_payment_method() && $order->has_status( 'on-hold' );
			
			if ( $is_woovi_pix_pending ) {
				$this->render_unified_pix_card( $order );
			} else {
				?>
				<div class="utp-grid">
					<?php $this->render_order_summary( $order ); ?>
					<?php $this->render_knowledge_card( $atts ); ?>
				</div>
				<?php
			}
			?>
			
			<?php $this->render_tutorial( $atts ); ?>
			<?php $this->render_support_block( $atts ); ?>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Hero com mensagem principal.
	 *
	 * @param WC_Order|null $order Order instance.
	 */
	private function render_header( $order ): void {
		$first_name = $order ? $order->get_billing_first_name() : __( 'cliente', 'udia-pods-thankyou' );
		$order_no   = $order ? $order->get_order_number() : __( 'em processamento', 'udia-pods-thankyou' );
		?>
		<header class="utp-hero">
			<p class="utp-eyebrow"><?php esc_html_e( 'Pedido confirmado', 'udia-pods-thankyou' ); ?></p>
			<h1><?php printf( esc_html__( 'Obrigado, %s!', 'udia-pods-thankyou' ), esc_html( $first_name ) ); ?></h1>
			<p><?php esc_html_e( 'Enviamos um resumo do pedido e os próximos passos por e-mail. Guarde o número abaixo para qualquer suporte.', 'udia-pods-thankyou' ); ?></p>
			<div class="utp-order-code">
				<span id="utp-order-id" data-copy-value="<?php echo esc_attr( $order_no ); ?>">
					<?php printf( esc_html__( 'Pedido #%s', 'udia-pods-thankyou' ), esc_html( $order_no ) ); ?>
				</span>
				<button class="utp-copy" data-copy-target="#utp-order-id"><?php esc_html_e( 'Copiar', 'udia-pods-thankyou' ); ?></button>
			</div>
		</header>
		<?php
	}

	/**
	 * Box com itens e totais.
	 *
	 * @param WC_Order|null $order Order instance.
	 */
	private function render_order_summary( $order ): void {
		?>
		<section class="utp-card utp-order-card">
			<h2><?php esc_html_e( 'Resumo do pedido', 'udia-pods-thankyou' ); ?></h2>
			<?php if ( ! $order ) : ?>
				<p><?php esc_html_e( 'Ainda não encontramos um pedido associado. Quando o pagamento for confirmado, atualizaremos aqui.', 'udia-pods-thankyou' ); ?></p>
				<?php
				return;
			endif;
			?>
			<ul class="utp-order-items">
				<?php foreach ( $order->get_items() as $item ) : ?>
					<li>
						<div>
							<strong><?php echo esc_html( $item->get_name() ); ?></strong>
							<span><?php echo esc_html( sprintf( 'x%s', $item->get_quantity() ) ); ?></span>
						</div>
						<span class="utp-price"><?php echo wp_kses_post( wc_price( $item->get_total(), [ 'currency' => $order->get_currency() ] ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="utp-totals">
				<div>
					<span><?php esc_html_e( 'Subtotal', 'udia-pods-thankyou' ); ?></span>
					<strong><?php echo wp_kses_post( wc_price( $order->get_subtotal(), [ 'currency' => $order->get_currency() ] ) ); ?></strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Frete', 'udia-pods-thankyou' ); ?></span>
					<strong><?php echo wp_kses_post( wc_price( $order->get_shipping_total(), [ 'currency' => $order->get_currency() ] ) ); ?></strong>
				</div>
				<div class="utp-total">
					<span><?php esc_html_e( 'Total', 'udia-pods-thankyou' ); ?></span>
					<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
				</div>
			</div>
			<div class="utp-payment-status">
				<span class="status-dot status-<?php echo esc_attr( $order->get_status() ); ?>"></span>
				<span><?php printf( esc_html__( 'Status: %s', 'udia-pods-thankyou' ), esc_html( wc_get_order_status_name( $order->get_status() ) ) ); ?></span>
			</div>
		</section>
		<?php
	}

	/**
	 * Card com CTA/tutorial em destaque.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	private function render_knowledge_card( array $atts ): void {
		$cta_url  = esc_url( $atts['highlight_cta'] ?? '#' );
		$cta_text = $cta_url ? __( 'Acessar tutorial completo', 'udia-pods-thankyou' ) : '';
		?>
		<section class="utp-card utp-cta-card">
			<h2><?php esc_html_e( 'Como acessar seu produto', 'udia-pods-thankyou' ); ?></h2>
			<p><?php esc_html_e( 'Siga o passo a passo abaixo para liberar o conteúdo ou serviço adquirido. Se tiver dúvidas, fale com nosso time.', 'udia-pods-thankyou' ); ?></p>
			<?php if ( $cta_url ) : ?>
				<a class="utp-cta" href="<?php echo $cta_url; ?>" target="_blank" rel="noreferrer">
					<?php echo esc_html( $cta_text ); ?>
				</a>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Tutorial timeline.
	 *
	 * @param array $atts Attributes.
	 */
	private function render_tutorial( array $atts ): void {
		if ( isset( $atts['show_tutorial'] ) && 'no' === $atts['show_tutorial'] ) {
			return;
		}

		$steps = apply_filters(
			'udia_pods_thankyou_steps',
			[
				[
					'title' => __( 'Confirme seu e-mail', 'udia-pods-thankyou' ),
					'text'  => __( 'Enviamos um link de confirmação para liberar seu conteúdo. Cheque também a caixa de spam.', 'udia-pods-thankyou' ),
				],
				[
					'title' => __( 'Acesse a área do cliente', 'udia-pods-thankyou' ),
					'text'  => __( 'Use o e-mail e a senha cadastrados para acompanhar o progresso do pedido e baixar materiais.', 'udia-pods-thankyou' ),
				],
				[
					'title' => __( 'Comece o tutorial guiado', 'udia-pods-thankyou' ),
					'text'  => __( 'Clique no botão acima para assistir o passo a passo completo com dicas práticas.', 'udia-pods-thankyou' ),
				],
			],
			$atts
		);
		?>
		<section class="utp-card utp-timeline">
			<h2><?php esc_html_e( 'Próximos passos', 'udia-pods-thankyou' ); ?></h2>
			<ol>
				<?php foreach ( $steps as $index => $step ) : ?>
					<li>
						<div class="utp-step-index"><?php echo esc_html( $index + 1 ); ?></div>
						<div>
							<strong><?php echo esc_html( $step['title'] ); ?></strong>
							<p><?php echo esc_html( $step['text'] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
		<?php
	}

	/**
	 * Bloco de suporte e integrações (chat/WhatsApp).
	 *
	 * @param array $atts Attributes.
	 */
	private function render_support_block( array $atts ): void {
		if ( isset( $atts['show_chat_widget'] ) && 'no' === $atts['show_chat_widget'] ) {
			return;
		}
		?>
		<section class="utp-support">
			<div>
				<h3><?php esc_html_e( 'Precisa de ajuda agora?', 'udia-pods-thankyou' ); ?></h3>
				<p><?php esc_html_e( 'Nosso time responde em poucos minutos pelo WhatsApp ou chat ao vivo.', 'udia-pods-thankyou' ); ?></p>
			</div>
			<div class="utp-support-actions">
				<a class="utp-btn ghost" href="https://wa.me/" target="_blank" rel="noreferrer"><?php esc_html_e( 'WhatsApp', 'udia-pods-thankyou' ); ?></a>
				<button class="utp-btn solid" data-open-chat><?php esc_html_e( 'Abrir chat', 'udia-pods-thankyou' ); ?></button>
			</div>
		</section>
		<?php
	}

	/**
	 * Render Unified PIX Card (Order Summary + Payment Details)
	 *
	 * @param WC_Order $order Order object.
	 */
	private function render_unified_pix_card( $order ): void {
		$qr_code_image = $order->get_meta( '_woovi_qr_code_image' );
		$br_code       = $order->get_meta( '_woovi_br_code' );
		$expires_date  = $order->get_meta( '_woovi_expires_date' );
		
		if ( ! $qr_code_image || ! $br_code ) {
			// Fallback if data is missing
			$this->render_order_summary( $order );
			return;
		}

		$expires_timestamp = $expires_date ? strtotime( $expires_date ) : null;
		?>
		<section class="utp-card utp-unified-card">
			<div class="utp-unified-grid">
				<!-- Left Column: Order Details -->
				<div class="utp-order-details-col">
					<div class="utp-card-header">
						<h2><?php esc_html_e( 'Resumo do Pedido', 'udia-pods-thankyou' ); ?></h2>
						<div class="utp-order-number">
							#<?php echo esc_html( $order->get_order_number() ); ?>
						</div>
					</div>

					<div class="utp-scrollable-list">
						<ul class="utp-order-items">
							<?php foreach ( $order->get_items() as $item ) : 
								$product = $item->get_product();
								$img_url = '';
								
								if ( $product ) {
									$image_id = $product->get_image_id();
									// Fallback to parent image if variation has no image
									if ( ! $image_id && $product->is_type( 'variation' ) ) {
										$image_id = $product->get_parent_id();
									}
									$img_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();
								}
							?>
								<li class="utp-item-row">
									<?php if ( $img_url ) : ?>
										<div class="utp-item-thumb">
											<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $item->get_name() ); ?>">
										</div>
									<?php endif; ?>
									<div class="utp-item-info">
										<strong><?php echo esc_html( $item->get_name() ); ?></strong>
										<?php 
										$formatted_meta_data = $item->get_formatted_meta_data();
										if ( $formatted_meta_data ) : ?>
											<div class="utp-item-meta">
												<?php foreach ( $formatted_meta_data as $meta ) : ?>
													<span><?php echo wp_kses_post( $meta->display_value ); ?></span>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
										<span class="utp-item-qty">x<?php echo esc_html( $item->get_quantity() ); ?></span>
									</div>
									<span class="utp-price"><?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<div class="utp-totals-compact">
						<div class="utp-total-row">
							<span><?php esc_html_e( 'Subtotal:', 'udia-pods-thankyou' ); ?></span>
							<span><?php echo wp_kses_post( $order->get_subtotal_to_display() ); ?></span>
						</div>
						<div class="utp-total-row">
							<span><?php esc_html_e( 'Frete:', 'udia-pods-thankyou' ); ?></span>
							<span><?php echo wp_kses_post( $order->get_shipping_to_display() ); ?></span>
						</div>
						<div class="utp-total-row main-total">
							<strong><?php esc_html_e( 'Total:', 'udia-pods-thankyou' ); ?></strong>
							<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
						</div>
					</div>
				</div>

				<!-- Right Column: Payment Details -->
				<div class="utp-payment-details-col">
					<div class="utp-pix-highlight-box">
						<div class="utp-pix-header-compact">
							<h3><?php esc_html_e( 'Pague com PIX', 'udia-pods-thankyou' ); ?></h3>
							<?php if ( $expires_timestamp && $expires_timestamp > time() ) : ?>
								<div class="utp-pix-timer compact" data-expires="<?php echo esc_attr( $expires_timestamp ); ?>">
									<svg width="14" height="14" viewBox="0 0 16 16" fill="none">
										<path d="M8 4V8L10.5 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
										<circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.5"/>
									</svg>
									<span class="timer-text"><?php esc_html_e( 'Calculando...', 'udia-pods-thankyou' ); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<div class="utp-instruction-text">
							<?php esc_html_e( 'Abra o app do seu banco e escaneie o código ou use o copia e cola:', 'udia-pods-thankyou' ); ?>
						</div>

						<div class="utp-qr-layout">
							<div class="utp-qr-wrapper-compact">
								<img src="<?php echo esc_attr( $qr_code_image ); ?>" alt="QR Code PIX" class="utp-qr-img">
							</div>
							
							<div class="utp-pix-actions">
								<div class="utp-copy-group">
									<input type="text" id="utp-pix-code-unified" readonly value="<?php echo esc_attr( $br_code ); ?>" class="utp-pix-input">
									<button class="utp-copy-btn utp-copy" data-copy-target="#utp-pix-code-unified" title="<?php esc_attr_e( 'Copiar', 'udia-pods-thankyou' ); ?>">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
											<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
										</svg>
										<span class="copy-text"><?php esc_html_e( 'Copiar código', 'udia-pods-thankyou' ); ?></span>
									</button>
								</div>
							</div>
						</div>
						
						<div class="utp-auto-confirm-note">
							<span class="pulsing-dot"></span>
							<?php esc_html_e( 'Aprovação automática em instantes', 'udia-pods-thankyou' ); ?>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}

Udia_Pods_Thankyou::init();
