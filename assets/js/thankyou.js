(() => {
	// Copy functionality for all copy buttons
	const copyButtons = document.querySelectorAll('.utp-copy, .utp-btn-copy-icon');

	copyButtons.forEach((button) => {
		button.addEventListener('click', async (e) => {
			e.preventDefault();
			const targetSelector = button.dataset.copyTarget;
			const target = targetSelector ? document.querySelector(targetSelector) : null;

			// Fallback: try to find input in same container if target not found
			const valueToCopy = target?.value || target?.innerText || button.parentElement.querySelector('input')?.value;

			if (!valueToCopy) {
				console.warn('Udia Pods: No content to copy found');
				return;
			}

			try {
				await navigator.clipboard.writeText(valueToCopy);

				// Visual feedback
				const originalText = button.innerHTML;
				const copySpan = button.querySelector('.copy-text');

				button.classList.add('copied');

				if (copySpan) {
					copySpan.textContent = 'Copiado!';
				} else {
					button.textContent = 'Copiado!';
				}

				setTimeout(() => {
					button.classList.remove('copied');
					if (copySpan) {
						button.innerHTML = originalText;
					} else {
						button.textContent = originalText; // If it was plain text
					}
				}, 2000);
			} catch (error) {
				console.error('Clipboard API failed', error);
				// Fallback for older browsers
				if (target && target.select) {
					target.select();
					document.execCommand('copy');
					alert('Código copiado!');
				}
			}
		});
	});

	// Chat button functionality
	const chatButtons = document.querySelectorAll('[data-open-chat]');
	chatButtons.forEach((button) => {
		button.addEventListener('click', () => {
			window.dispatchEvent(new CustomEvent('utp:chat:open'));
			button.textContent = 'Aguardando atendimento...';
			button.disabled = true;
		});
	});

	// Timeline animation
	const timeline = document.querySelector('.utp-timeline');
	if (timeline) {
		const observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						timeline.classList.add('utp-visible');
					}
				});
			},
			{ threshold: 0.3 }
		);
		observer.observe(timeline);
	}

	// PIX expiration timer
	const pixTimer = document.querySelector('.utp-pix-timer');
	if (pixTimer) {
		const expiresTimestamp = parseInt(pixTimer.dataset.expires, 10);
		const timerText = pixTimer.querySelector('.timer-text');

		if (expiresTimestamp && timerText) {
			const updateTimer = () => {
				const now = Math.floor(Date.now() / 1000);
				const remaining = expiresTimestamp - now;

				if (remaining <= 0) {
					timerText.textContent = 'Expirado';
					pixTimer.style.color = '#ff5f5f';
					return;
				}

				const hours = Math.floor(remaining / 3600);
				const minutes = Math.floor((remaining % 3600) / 60);
				const seconds = remaining % 60;

				if (hours > 0) {
					timerText.textContent = `Expira em ${hours}h ${minutes}m`;
				} else if (minutes > 0) {
					timerText.textContent = `Expira em ${minutes}m ${seconds}s`;
				} else {
					timerText.textContent = `Expira em ${seconds}s`;
				}

				setTimeout(updateTimer, 1000);
			};

			updateTimer();
		}
	}

	// Optional: Auto-refresh page when payment is confirmed 
	// Check order status every 5 seconds if still pending
	const wrapper = document.querySelector('.utp-wrapper');
	if (wrapper && wrapper.dataset.orderStatus === 'on-hold') {
		const orderId = window.UdiaPodsThankyou?.orderId;

		if (orderId) {
			const checkPaymentStatus = setInterval(() => {
				// Reload page to check if status changed
				// In a production environment, you might want to use AJAX instead
				fetch(window.location.href, { cache: 'no-cache' })
					.then(response => response.text())
					.then(html => {
						const parser = new DOMParser();
						const doc = parser.parseFromString(html, 'text/html');
						const newStatus = doc.querySelector('.utp-wrapper')?.dataset.orderStatus;

						if (newStatus && newStatus !== 'on-hold') {
							// Payment confirmed! Reload page to show updated status
							window.location.reload();
						}
					})
					.catch(error => console.log('Status check failed:', error));
			}, 5000); // Check every 5 seconds

			// Stop checking after 10 minutes (payment timeout)
			setTimeout(() => clearInterval(checkPaymentStatus), 600000);
		}
	}
})();
