## 🎨 LAYOUT PREMIUM PIX - CÓDIGO COMPLETO PRONTO

Este arquivo contém a função PHP completa para substituir no arquivo `udia-pods-thankyou.php`

### 📍 Localização: Linha ~426 em `udia-pods-thankyou.php`

### 🔧 Como Aplicar:

1. Abra `udia-pods-thankyou.php`
2. Procure pela função `render_pix_payment_details`
3. SUBSTITUA TODA a função pelo código abaixo
4. Salve o arquivo

---

### 📝 CÓDIGO COMPLETO:

```php
/**
 * Render PIX payment details - PREMIUM LAYOUT
 *
 * @param WC_Order|null $order Order object.
 */
private function render_pix_payment_details( $order ): void {
	if ( ! $order || 'woovi_pix' !== $order->get_payment_method() ) {
		return;
	}

	if ( ! $order->has_status( 'on-hold' ) ) {
		return;
	}

	$qr_code_image = $order->get_meta( '_woovi_qr_code_image' );
	$br_code       = $order->get_meta( '_woovi_br_code' );
	$expires_date  = $order->get_meta( '_woovi_expires_date' );

	if ( ! $qr_code_image || ! $br_code ) {
		return;
	}

	$expires_timestamp = $expires_date ? strtotime( $expires_date ) : null;
	$items = $order->get_items();
	?>
	<section class="utp-pix-payment-slip">
		<!-- Header do Pedido -->
		<div class="utp-pix-slip-header">
			<div class="utp-slip-title">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
					<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					<path d="M9 12h6m-6 4h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
				<div>
					<h2><?php esc_html_e( 'Pagamento via PIX', 'udia-pods-thankyou' ); ?></h2>
					<span class="utp-order-number">Pedido #<?php echo esc_html( $order->get_order_number() ); ?></span>
				</div>
			</div>
			<div class="utp-payment-status">
				<span class="status-dot status-pending"></span>
				<span><?php esc_html_e( 'Aguardando pagamento', 'udia-pods-thankyou' ); ?></span>
			</div>
		</div>

		<!-- Resumo do Pedido -->
		<div class="utp-order-summary-pix">
			<h3><?php esc_html_e( 'Resumo do pedido', 'udia-pods-thankyou' ); ?></h3>
			
			<div class="utp-order-items-list">
				<?php foreach ( $items as $item_id => $item ) : ?>
					<?php
					$product = $item->get_product();
					if ( ! $product ) continue;
					
					$image_id = $product->get_image_id();
					$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();
					?>
					<div class="utp-order-item-row">
						<div class="utp-item-image">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
						</div>
						<div class="utp-item-details">
							<div class="utp-item-name"><?php echo esc_html( $product->get_name() ); ?></div>
							<?php
							if ( $item->get_variation_id() ) {
								$variation_data = wc_get_formatted_cart_item_data( $item, true );
								if ( $variation_data ) :
							?>
								<div class="utp-item-variation"><?php echo wp_kses_post( $variation_data ); ?></div>
							<?php 
								endif;
							}
							?>
							<div class="utp-item-qty">x<?php echo esc_html( $item->get_quantity() ); ?></div>
						</div>
						<div class="utp-item-price">
							<?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Totais -->
			<div class="utp-order-totals-pix">
				<div class="utp-total-row">
					<span><?php esc_html_e( 'Subtotal', 'udia-pods-thankyou' ); ?></span>
					<span><?php echo wp_kses_post( wc_price( $order->get_subtotal() ) ); ?></span>
				</div>
				
				<?php if ( $order->get_shipping_total() > 0 ) : ?>
					<div class="utp-total-row">
						<span><?php esc_html_e( 'Entrega', 'udia-pods-thankyou' ); ?></span>
						<span><?php echo wp_kses_post( wc_price( $order->get_shipping_total() ) ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php if ( $order->get_total_discount() > 0 ) : ?>
					<div class="utp-total-row utp-discount">
						<span><?php esc_html_e( 'Desconto', 'udia-pods-thankyou' ); ?></span>
						<span>-<?php echo wp_kses_post( wc_price( $order->get_total_discount() ) ); ?></span>
					</div>
				<?php endif; ?>
				
				<div class="utp-total-row utp-total-final">
					<span><?php esc_html_e( 'Total', 'udia-pods-thankyou' ); ?></span>
					<strong><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></strong>
				</div>
			</div>
		</div>

		<!-- QR Code e Código PIX -->
		<div class="utp-pix-payment-section">
			<div class="utp-qr-code-container">
				<div class="utp-qr-code-box">
					<img 
						src="<?php echo esc_attr( $qr_code_image ); ?>" 
						alt="<?php esc_attr_e( 'QR Code PIX', 'udia-pods-thankyou' ); ?>"
						class="utp-qr-code-img"
					/>
				</div>
				<?php if ( $expires_timestamp && $expires_timestamp > time() ) : ?>
					<div class="utp-timer-badge" data-expires="<?php echo esc_attr( $expires_timestamp ); ?>">
						<svg width="14" height="14" viewBox="0 0 16 16" fill="none">
							<path d="M8 4V8L10.5 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
							<circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.5"/>
						</svg>
						<span class="timer-text"><?php esc_html_e( 'Calculando...', 'udia-pods-thankyou' ); ?></span>
					</div>
				<?php endif; ?>
			</div>

			<!-- Código Copia e Cola -->
			<div class="utp-pix-code-section">
				<label><?php esc_html_e( 'Código PIX (Copia e Cola)', 'udia-pods-thankyou' ); ?></label>
				<div class="utp-code-display">
					<input 
						type="text" 
						id="utp-pix-code-input" 
						value="<?php echo esc_attr( $br_code ); ?>" 
						readonly
						class="utp-pix-code-input"
					/>
					<button 
						class="utp-copy-btn" 
						data-copy-target="#utp-pix-code-input"
						type="button"
					>
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none">
							<rect x="7" y="7" width="10" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
							<path d="M3 13V4C3 3.44772 3.44772 3 4 3H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
						</svg>
						<span class="copy-text"><?php esc_html_e( 'Copiar código', 'udia-pods-thankyou' ); ?></span>
					</button>
				</div>
			</div>
		</div>

		<!-- Instruções -->
		<div class="utp-payment-instructions">
			<h4><?php esc_html_e( 'Como pagar:', 'udia-pods-thankyou' ); ?></h4>
			<ol>
				<li><?php esc_html_e( 'Abra o app do seu banco', 'udia-pods-thankyou' ); ?></li>
				<li><?php esc_html_e( 'Escolha pagar via PIX', 'udia-pods-thankyou' ); ?></li>
				<li><?php esc_html_e( 'Escaneie o QR Code ou cole o código acima', 'udia-pods-thankyou' ); ?></li>
				<li><?php esc_html_e( 'Confirme o pagamento de ', 'udia-pods-thankyou' ); ?><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></li>
			</ol>
			
			<div class="utp-payment-note">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
					<circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
					<path d="M8 4V8M8 11V12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
				<p><?php esc_html_e( 'O pagamento é confirmado automaticamente em poucos segundos. Esta página será atualizada.', 'udia-pods-thankyou' ); ?></p>
			</div>
		</div>
	</section>
	<?php
}
```

---

## ✅ CHECKLIST

Depois de aplicar, verifique:

- [ ] Substitui a função `render_pix_payment_details` completa
- [ ] Registrou `pix-payment-slip.css` no `register_assets()`
- [ ] Fez enqueue do CSS
- [ ] Atualizou `.utp-pix-timer` para `.utp-timer-badge` no JS
- [ ] Testou no site

---

**PRONTO! Código completo acima. Copie e cole no lugar da função antiga!** 🚀
