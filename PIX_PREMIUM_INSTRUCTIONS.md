# 🎨 Layout Premium PIX - Arquivos Prontos

## ✅ CSS Criado
`assets/css/pix-payment-slip.css` - **JÁ NO GITHUB**

## 📝 Próximo Passo: Aplicar o Layout

Criei o CSS completo. Agora você precisa aplicar o HTML.

### Opção 1: **Automática** (Recomendado) ⚡

Vou gerar um patch que você aplica automaticamente.

### Opção 2: **Manual** 

Edite manualmente os arquivos seguindo as instruções abaixo.

---

## 🔧 Instruções Manuais

### 1. Registrar novo CSS

Em `udia-pods-thankyou.php` linha ~105, ADICIONE:

```php
// Depois de registrar thankyou.css
wp_register_style(
    'utp-pix-slip',
    $url . 'assets/css/pix-payment-slip.css',
    [self::HANDLE],
    self::VERSION
);
```

E na linha ~113 (quando faz enqueue):

```php
wp_enqueue_style( self::HANDLE );
wp_enqueue_style( 'utp-pix-slip' ); // ADICIONE ESTA LINHA
```

### 2. Atualizar JavaScript Timer

Em `assets/js/thankyou.js` linha ~48, MUDE:

```javascript
// DE:
const pixTimer = document.querySelector('.utp-pix-timer');

// PARA:
const pixTimer = document.querySelector('.utp-timer-badge');
```

### 3. Substituir HTML da Função PIX

A parte mais complexa. Vou criar um arquivo separado com a função completa.

---

**Quer que eu gere um PATCH automático ou prefere editar manualmente?**

Responda e eu finalizo! 🚀
