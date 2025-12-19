# Udia Pods Pós-Checkout Experience (WordPress/WooCommerce)

Plugin proprietário que entrega a página de obrigado e tutorial pós-checkout da Udia Pods com o visual 333333/222222, neon verde e bege. Compatível com a página nativa do WooCommerce (hook `woocommerce_thankyou`) e com qualquer construtor que aceite shortcodes (Elementor, Gutenberg, Bricks, etc.).

## Estrutura do plugin

```
udia-pods-thankyou/
├─ udia-pods-thankyou.php        # Bootstrap do plugin e shortcode
└─ assets/
   ├─ css/thankyou.css           # Layout com paleta Udia Pods
   └─ js/thankyou.js             # Copiar código do pedido, animações, chat event
```

## Empacotamento para produção

1. Garanta que a pasta `udia-pods-thankyou` contenha apenas os arquivos acima.
2. No terminal, gere o zip que será enviado ao WordPress:
   ```
   zip -r udia-pods-thankyou.zip udia-pods-thankyou
   ```
3. No painel do site, vá em **Plugins > Adicionar novo > Enviar plugin**, selecione o zip e ative.

## Instalação e requisitos

- WordPress 6.x+
- WooCommerce 8.x+
- PHP 7.4+

Após subir o zip:

1. Ative o plugin **Udia Pods Pós-Checkout Experience**.
2. Se usar a página padrão `/?thankyou`, nada mais é necessário: o conteúdo será substituído automaticamente.
3. Se usar uma página personalizada, siga as instruções de shortcode abaixo.

## Shortcode para Elementor/Gutenberg

Use o shortcode `[udia_pods_thankyou]` em qualquer página. Parâmetros adicionais:

| Atributo | Padrão | Descrição |
| --- | --- | --- |
| `order_id` | vazio | Força um pedido específico (útil em páginas estáticas com ID via query string). |
| `show_tutorial` | `yes` | Use `no` para esconder a timeline de próximos passos. |
| `highlight_cta` | vazio | URL do tutorial/área do cliente (define o botão neon). |
| `show_chat_widget` | `yes` | `no` oculta o bloco de suporte/WhatsApp. |

Exemplo Elementor:

```
[udia_pods_thankyou highlight_cta="https://udia.pods.br/tutoriais"]
```

Depois de publicar a página, mapeie-a em **WooCommerce > Configurações > Avançado > Página de agradecimento** ou redirecione o checkout para ela.

## Personalizações rápidas

- **Paleta 333333/222222 + neon/bege**: edite `assets/css/thankyou.css`. Os tons principais estão declarados como custom properties dentro de `.utp-wrapper`.
- **Passos do tutorial**: use o filtro `udia_pods_thankyou_steps`:

```php
add_filter( 'udia_pods_thankyou_steps', function( $steps ) {
	$steps[] = [
		'title' => 'Agende sua mentoria exclusiva',
		'text'  => 'Escolha um horário disponível para acelerar o uso dos pods.',
	];
	return $steps;
});
```

- **Dados expostos no front**: o objeto JS global `UdiaPodsThankyou` (registrado via `wp_localize_script`) traz `orderId`, `status`, `billing` e `highlightCta` para integrações adicionais.

## Eventos front-end

- Botões com `data-open-chat` disparam `window.dispatchEvent(new CustomEvent('utp:chat:open'))` — conecte ao provedor de chat que preferir.
- Botões `.utp-copy` usam a Clipboard API para copiar o número do pedido e alteram o texto para “Copiado!”.

## Licenciamento

Este plugin é distribuído **apenas** para uso interno da Udia Pods conforme a licença proprietária incluída em `LICENSE.md`. Não redistribua, não sublicencie e não publique o código em repositórios públicos sem autorização da diretoria.
