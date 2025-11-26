(() => {
	const baseUrl = window.etydictBaseUrl ?? '/';
	const apiUrl = baseUrl + 'api/tts.php';
	const dictionaryUrl = baseUrl + 'dictionary/?w=';
	const autocompleteUrl = baseUrl + 'api/autocomplete.php';
	const checkUsernameUrl = baseUrl + 'api/check-username.php';
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

	const getSuggestions = (word) => fetch(autocompleteUrl + '?query=' + encodeURIComponent(word))
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

	const RegisterValidate = () => {
		const registerForm = document.getElementById('register-form');
		const usernameInput = document.getElementById('username');
		const usernameLengthError = document.getElementById('username-length-error');
		const usernameUniqueError = document.getElementById('username-unique-error');
		const passwordInput = document.getElementById('password');
		const passwordError = document.getElementById('password-error');
		const criteriaItems = document.querySelectorAll('#password-criteria li[data-criteria]');

		const criteriaCheckers = {
			length: (val) => val.length >= 8,
			uppercase: (val) => /[A-Z]/.test(val),
			lowercase: (val) => /[a-z]/.test(val),
			number: (val) => /[0-9]/.test(val),
			special: (val) => /[^A-Za-z0-9]/.test(val),
		};


		const applyCriteriaClasses = (item, isValid) => {
			item.classList.remove('text-danger', 'text-success');
			item.classList.add(isValid ? 'text-success' : 'text-danger');
		};

		const evaluatePassword = () => {
			const value = passwordInput.value;
			if (!value) {
				criteriaItems.forEach((item) => {
					item.classList.remove('text-danger', 'text-success');
				});
				return;
			}
			criteriaItems.forEach((item) => {
				const key = item.dataset.criteria;
				const check = criteriaCheckers[key];
				const met = check(value);
				applyCriteriaClasses(item, met);
			});
		};

		const highlightLengthError = () => {
			if (usernameLengthError) {
				usernameLengthError.classList.add('text-danger');
			}
			if (usernameInput) {
				usernameInput.classList.add('is-invalid');
			}
		};

		const showUniqueUsernameError = () => {
			if (usernameInput) {
				usernameInput.classList.add('is-invalid');
			}
			if (usernameUniqueError) {
				usernameUniqueError.classList.remove('d-none');
			}
		};

		const hideUniqueUsernameError = () => {
			if (usernameInput) {
				usernameInput.classList.remove('is-invalid');
			}
			if (usernameUniqueError) {
				usernameUniqueError.classList.add('d-none');
			}
		};

		const showPasswordError = () => {
			if (passwordInput) {
				passwordInput.classList.add('is-invalid');
			}
			if (passwordError) {
				passwordError.classList.remove('d-none');
			}
		};

		const hidePasswordError = () => {
			if (passwordInput) {
				passwordInput.classList.remove('is-invalid');
			}
			if (passwordError) {
				passwordError.classList.add('d-none');
			}
		};

		const allCriteriaMet = (value) => {
			if (!value) {
				return false;
			}

			if (!criteriaCheckers.length(value)) {
				return false;
			}
			if (!criteriaCheckers.uppercase(value)) {
				return false;
			}
			if (!criteriaCheckers.lowercase(value)) {
				return false;
			}
			if (!criteriaCheckers.number(value)) {
				return false;
			}
			if (!criteriaCheckers.special(value)) {
				return false;
			}
			return true;
		};

		const checkUsernameExists = async (username) => {
			try {
				const response = await fetch(checkUsernameUrl + '?username=' + encodeURIComponent(username),
					{
						headers: { 'Accept': 'application/json' },
					});
				if (!response.ok) {
					return false;
				}
				const payload = await response.json();
				return payload.exists === true;
			} catch (error) {
				console.error(error);
				return false;
			}
		};

		const SubmitRegister = async (event) => {
			if (event) {
				event.preventDefault();
			}
			hideUniqueUsernameError();
			const usernameValue = (usernameInput.value).trim();
			if (usernameValue.length < 8) {
				highlightLengthError();
				return;
			}
			if (!allCriteriaMet(passwordInput.value)) {
				showPasswordError();
				return;
			}
			hidePasswordError();
			const exists = await checkUsernameExists(usernameValue);
			if (exists) {
				showUniqueUsernameError();
				return;
			}
			registerForm.submit();
		};

		if (passwordInput) {
			passwordInput.addEventListener('input', () => {
				evaluatePassword();
				hidePasswordError();
			});
		}
		if (registerForm) {
			registerForm.addEventListener('submit', SubmitRegister);
		}
		if (usernameInput) {
			usernameInput.addEventListener('input', () => {
				if (usernameInput) {
					usernameInput.classList.remove('is-invalid');
				}
				if (usernameLengthError) {
					usernameLengthError.classList.remove('text-danger');
				}
				hideUniqueUsernameError();
			});
		}
	};

	const passwordToggle = () => {
		const buttons = document.querySelectorAll('[data-password-toggle]');
		buttons.forEach((button) => {
			const targetId = button.getAttribute('data-password-toggle');
			const targetInput = document.getElementById(targetId);
			if (!targetInput) {
				return;
			}
			const icon = button.querySelector('span');
			const updateIcon = (visible) => {
				if (!icon) {
					return;
				}
				const src = baseUrl + 'assets/img/' + (visible ? 'eye_open.svg' : 'eye_close.svg');
				const alt = visible ? 'Hide Password' : 'Show Password';
				icon.innerHTML = '<img src="' + src + '" alt="' + alt + '" width="20" height="20">';
			};
			let isVisible = false;
			updateIcon(isVisible);
			button.addEventListener('click', () => {
				isVisible = !isVisible;
				if (targetInput.type === 'password') {
					targetInput.type = 'text';
				} else {
					targetInput.type = 'password';
				}
				updateIcon(isVisible);
			});
		});
	};

	passwordToggle();
	RegisterValidate();

})();
