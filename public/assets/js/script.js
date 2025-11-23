(() => {
	const baseUrl = window.etydictBaseUrl ?? '/';
	const apiUrl = baseUrl + 'api/tts.php';
	const dictionaryUrl = baseUrl + 'dictionary/?w=';
	const ttsBtn = document.getElementById('ipa-tts-btn');
	const ipaElement = document.getElementById('ipa-text');
	const suggestionsBox = document.getElementById('autocomplete-suggestions');
	const searchInput = document.querySelector('input[name="w"]');
	const audioPlayer = new Audio();

	const iconText = '🔊';
	const toggleBtn = (enabled) => {
		ttsBtn.disabled = !enabled;
		if (enabled) {
			ttsBtn.classList.remove('spinner-border');
			ttsBtn.textContent = iconText;
		} else {
			ttsBtn.classList.add('spinner-border');
			ttsBtn.textContent = '';
		}
	};

	if (ttsBtn && ipaElement) {
		ttsBtn.addEventListener('click', () => {
			if (ttsBtn.disabled) {
				return;
			}

			const ipa = ipaElement.textContent.trim();
			if (!ipa) {
				return;
			}

			toggleBtn(false);

			fetch(apiUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', },
				body: JSON.stringify({ ipa }),
			})
				.then((response) => {
					if (!response.ok) {
						throw new Error('Polly request failed');
					}
					return response.blob();
				})
				.then((blob) => {
					const objectUrl = URL.createObjectURL(blob);
					audioPlayer.src = objectUrl;
					audioPlayer.onended = () => {
						URL.revokeObjectURL(objectUrl);
					};
					return audioPlayer.play();
				})
				.catch((error) => {
					console.error(error);
					window.alert('Unable to play pronunciation right now.');
				})
				.finally(() => {
					toggleBtn(true);
				});
		});
	}

	const renderForms = (forms) => {
		if (!forms) {
			return '';
		}
		let parts = (forms + '').split(',');
		return parts.map((form, index) => '<span class="fst-italic">' + form + '</span>' + (index < parts.length - 1 ? ' / ' : ''));
	};

	const toggleSuggestions = (show) => {
		if (!suggestionsBox) {
			return;
		}

		if (show) {
			suggestionsBox.classList.remove('d-none');
		} else {
			suggestionsBox.innerHTML = '';
			suggestionsBox.classList.add('d-none');
		}
	};

	const renderMatches = (matches) => {
		if (!suggestionsBox) {
			return;
		}

		if (!matches.length) {
			toggleSuggestions(false);
			return;
		}

		const rows = matches.map((match, index) => {
			const forms = renderForms(match.forms);
			const extraClasses = [];
			if (index === 0) {
				extraClasses.push('rounded-top');
			}
			if (index === matches.length - 1) {
				extraClasses.push('rounded-bottom');
			}
			return '<div class="autocomplete-row fs-5 p-2 px-3 ' + extraClasses.join(' ') + '" data-word="' + match.word + '">' +
				'<a class="text-decoration-none text-dark" href="' + dictionaryUrl + encodeURIComponent(match.word) + '">' +
				'<div class="d-flex gap-3 align-items-center">' +
				'<div>' +
				'<span class="fw-semibold">' + match.word + '</span>' +
				'</div>' +
				(forms ? '<div class="text-muted gap-1">' + forms + '</div>' : '') +
				'</div>' +
				'</a>' +
				'</div>';
		}).join('');

		suggestionsBox.innerHTML = '<div class="autocomplete w-100 rounded">' + rows + '</div>';

		toggleSuggestions(true);
	};

	const getSuggestions = (word) => fetch(baseUrl + 'api/autocomplete.php?query=' + encodeURIComponent(word))
		.then((response) => {
			if (!response.ok) {
				throw new Error('Autocomplete request failed');
			}
			return response.json();
		})
		.then((suggestions) => {
			renderMatches(suggestions);
		})
		.catch((error) => {
			console.error(error);
			renderMatches([]);
		});

	if (suggestionsBox && searchInput) {
		let timeout;
		toggleSuggestions(false);
		searchInput.addEventListener('input', () => {
			const query = searchInput.value.trim();
			if (!query) {
				toggleSuggestions(false);
				return;
			}
			clearTimeout(timeout);
			timeout = setTimeout(() => {
				getSuggestions(query);
			}, 220);
		});
	}
})();
