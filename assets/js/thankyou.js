(() => {
	const copyButtons = document.querySelectorAll('.utp-copy');

	copyButtons.forEach((button) => {
		button.addEventListener('click', async () => {
			const targetSelector = button.dataset.copyTarget;
			const target = targetSelector ? document.querySelector(targetSelector) : null;
			const value = target?.dataset.copyValue || target?.textContent?.trim();

			if (!value) {
				return;
			}

			try {
				await navigator.clipboard.writeText(value);
				button.textContent = 'Copiado!';
				setTimeout(() => (button.textContent = 'Copiar'), 1800);
			} catch (error) {
				console.error('Clipboard API indisponível', error);
			}
		});
	});

	const chatButtons = document.querySelectorAll('[data-open-chat]');
	chatButtons.forEach((button) => {
		button.addEventListener('click', () => {
			window.dispatchEvent(new CustomEvent('utp:chat:open'));
			button.textContent = 'Aguardando atendimento...';
			button.disabled = true;
		});
	});

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
})();
