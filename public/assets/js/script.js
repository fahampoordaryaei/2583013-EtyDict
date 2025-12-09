(() => {
	const baseUrl = window.etydictBaseUrl ?? '/';
	const apiUrl = baseUrl + 'api/tts.php';
	const dictionaryUrl = baseUrl + 'dictionary/?w=';
	const etyUrl = baseUrl + 'etymology/?w=';
	const defaultAutocompleteUrl = baseUrl + 'api/autocomplete.php';
	const etyAutocompleteUrl = baseUrl + 'api/ety_autocomplete.php';
	const contactApiUrl = baseUrl + 'api/contact.php';
	const logEventUrl = baseUrl + 'api/log_event.php';
	let autocompleteUrl = defaultAutocompleteUrl;
	let suggestionBaseUrl = dictionaryUrl;
	const userApiUrl = baseUrl + 'api/user.php';
	const changeEmailApiUrl = baseUrl + 'api/change_email.php';
	const recaptchaSiteKey = '6LdtfCEsAAAAAPoqdfwDkqJ0PxQ5e9M8fadPxdYs';

	const getCsrfToken = () => {
		const tokenInput = document.querySelector('input[name="csrf_token"]');
		return tokenInput ? tokenInput.value : '';
	};

	const logError = (error, context = 'client_script') => {
		const formData = new FormData();
		formData.append('event', 'client_error');
		formData.append('code', 500);
		formData.append('message', 'Context:' + context + '. Error: ' + (error instanceof Error ? error.message : error));

		fetch(logEventUrl, {
			method: 'POST',
			body: formData
		}).catch(() => { });
	};

	const ttsBtn = document.getElementById('ipa-tts-btn');
	const ipaElement = document.getElementById('ipa-text');
	const suggestionsBox = document.getElementById('autocomplete-suggestions');
	const searchInput = document.querySelector('#search-form input[name="w"]');
	const favoriteButtons = document.querySelectorAll('.toggle-fav-btn');
	const favoriteAuthModal = document.getElementById('favorite-auth-modal');

	const audioPlayer = new Audio();
	let favoriteAuthModalInstance;

	const speakerIcon = '<img src="' + baseUrl + 'assets/img/speaker.svg' + '" class="m-0 d-block" alt="Listen to pronunciation" width="24" height="24">';
	const setPronunciationButtonState = (button, ready) => {
		if (!button) {
			return;
		}
		button.disabled = !ready;
		button.classList.toggle('spinner-border', !ready);
		button.innerHTML = ready ? speakerIcon : '';
	};

	const requestPronunciation = (ipa, button) => {
		if (!ipa || !button) {
			return;
		}
		setPronunciationButtonState(button, false);
		fetch(apiUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
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
				audioPlayer.onended = () => URL.revokeObjectURL(objectUrl);
				return audioPlayer.play();
			})
			.catch((error) => {
				logError(error, 'requestPronunciation');
				window.alert('Unable to play pronunciation right now.');
			})
			.finally(() => {
				setPronunciationButtonState(button, true);
			});
	};

	const playPronunciation = () => {
		const ipa = ipaElement.textContent.trim();
		requestPronunciation(ipa, ttsBtn);
	};

	const initFavoritesIpaButtons = () => {
		const buttons = document.querySelectorAll('.favorites-ipa-btn');
		buttons.forEach((button) => {
			setPronunciationButtonState(button, true);
			button.addEventListener('click', () => {
				const ipa = (button.value ?? '').toString().trim();
				requestPronunciation(ipa, button);
			});
		});
	};

	const escapeHtml = (text) => {
		if (!text) return '';
		return String(text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	};

	const renderForms = (forms) => {
		if (!forms) return '';
		return (forms + '').split(',')
			.map(form => `<span class="fst-italic">${escapeHtml(form)}</span>`)
			.join(' / ');
	};

	const toggleSuggestions = (show) => {
		if (!show) suggestionsBox.innerHTML = '';
		suggestionsBox.classList.toggle('d-none', !show);
	};

	const passwordCriteria = {
		length: (val) => val.length >= 8,
		uppercase: (val) => /[A-Z]/.test(val),
		lowercase: (val) => /[a-z]/.test(val),
		number: (val) => /[0-9]/.test(val),
		special: (val) => /[^A-Za-z0-9]/.test(val),
	};

	const applyPasswordCriteria = (item, isValid) => {
		item.classList.toggle('text-success', isValid);
		item.classList.toggle('text-danger', !isValid);
	};

	const getCriteriaItems = () => {
		return Array.from(document.querySelectorAll('#password-criteria .password-criteria-item')).map((item) => {
			const match = Array.from(item.classList).find((className) => className.startsWith('criteria-'));
			const key = match ? match.replace('criteria-', '') : '';
			return { element: item, key };
		}).filter(({ key }) => Boolean(key) && typeof passwordCriteria[key] === 'function');
	};

	const updatePasswordCriteria = (value, items) => {
		if (!value) {
			items.forEach(({ element }) => element.classList.remove('text-danger', 'text-success'));
			return;
		}
		items.forEach(({ element, key }) => applyPasswordCriteria(element, passwordCriteria[key](value)));
	};

	const passwordMeetsRequirements = (value) => {
		if (!value) {
			return false;
		}
		return Object.values(passwordCriteria).every((check) => check(value));
	};

	const renderMatches = (matches, hrefBase = dictionaryUrl) => {
		if (searchInput && document.activeElement !== searchInput) {
			toggleSuggestions(false);
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
			return `<div class="autocomplete-row fs-5 p-2 px-3 ${extraClasses.join(' ')}">
				<a class="text-decoration-none text-dark" href="${hrefBase}${encodeURIComponent(match.word)}">
					<div class="d-flex gap-3 align-items-center">
						<div><span class="fw-semibold">${escapeHtml(match.word)}</span></div>
						${forms ? `<div class="text-muted gap-1">${forms}</div>` : ''}
					</div>
				</a>
			</div>`;
		}).join('');

		suggestionsBox.innerHTML = '<div class="autocomplete w-100 rounded">' + rows + '</div>';
		toggleSuggestions(true);
	};

	const getSuggestions = (word, hrefBase) => fetch(autocompleteUrl + '?query=' + encodeURIComponent(word))
		.then((response) => response.ok ? response.json() : Promise.reject(new Error('Autocomplete request failed')))
		.then((matches) => renderMatches(matches, hrefBase))
		.catch((error) => {
			logError(error, 'getSuggestions');
			renderMatches([], hrefBase);
		});

	if (suggestionsBox && searchInput) {
		const formAction = searchInput.form?.action ?? '';
		if (formAction.includes('/etymology/')) {
			autocompleteUrl = etyAutocompleteUrl;
			suggestionBaseUrl = etyUrl;
		} else {
			autocompleteUrl = defaultAutocompleteUrl;
			suggestionBaseUrl = dictionaryUrl;
		}
		let timeout;
		toggleSuggestions(false);

		searchInput.addEventListener('focus', () => {
			const query = searchInput.value.trim();
			if (query) {
				getSuggestions(query, suggestionBaseUrl);
			}
		});

		searchInput.addEventListener('blur', () => {
			setTimeout(() => {
				toggleSuggestions(false);
			}, 200);
		});

		searchInput.addEventListener('input', () => {
			const query = searchInput.value.trim();
			if (!query) {
				toggleSuggestions(false);
				return;
			}
			clearTimeout(timeout);
			timeout = setTimeout(() => {
				getSuggestions(query, suggestionBaseUrl);
			}, 220);
		});
	}

	const initAuthForms = () => {
		const registerForm = document.getElementById('register-form');
		const resetPasswordForm = document.getElementById('reset-password-form');
		const profilePasswordForm = document.getElementById('password-form');
		const usernameInput = document.getElementById('username');
		const usernameLengthError = document.getElementById('username-length-error');
		const usernameUniqueError = document.getElementById('username-unique-error');
		const registerPasswordInput = document.getElementById('register-password');
		const registerConfirmInput = document.getElementById('register-confirm-password');
		const registerConfirmError = document.getElementById('register-password-match-error');
		const profilePasswordInput = document.getElementById('profile-password');
		const passwordError = document.getElementById('password-error');
		const resetConfirmInput = document.getElementById('confirm-password');
		const resetConfirmError = document.getElementById('confirm-password-match-error');
		const profileConfirmInput = document.getElementById('profile-confirm-password');
		const profileConfirmError = document.getElementById('profile-password-match-error');
		const resetTokenInput = document.getElementById('reset-token');
		const criteriaItems = getCriteriaItems();

		const evaluatePassword = (value) => {
			updatePasswordCriteria(value, criteriaItems);
		};

		const highlightLengthError = () => {
			if (usernameLengthError) {
				usernameLengthError.classList.add('text-danger');
				usernameLengthError.textContent = 'Username must be 8 to 15 characters long';
			}
			if (usernameInput) {
				usernameInput.classList.add('is-invalid');
			}
		};

		const passwordFields = [registerPasswordInput, profilePasswordInput].filter(Boolean);

		const togglePasswordError = (show) => {
			passwordFields.forEach((input) => {
				input.classList.toggle('is-invalid', show);
			});
			if (passwordError) {
				passwordError.classList.toggle('d-none', !show);
			}
		};

		const toggleConfirmError = (inputEl, errorEl, show) => {
			if (!inputEl) {
				return;
			}
			inputEl.classList.toggle('is-invalid', show);
			if (errorEl) {
				errorEl.classList.toggle('d-none', !show);
			}
		};

		const showResetSuccessModal = () => {
			const modalElement = document.getElementById('reset-success-modal');
			if (!modalElement || !window.bootstrap || typeof window.bootstrap.Modal !== 'function') {
				return;
			}
			const modal = new bootstrap.Modal(modalElement);
			modal.show();
		};

		const SubmitRegister = (event) => {
			event.preventDefault();
			const usernameValue = (usernameInput.value).trim();
			if (usernameValue.length < 8 || usernameValue.length > 15) {
				highlightLengthError();
				return;
			}
			if (!registerPasswordInput || !passwordMeetsRequirements(registerPasswordInput.value)) {
				togglePasswordError(true);
				return;
			}
			togglePasswordError(false);

			if (registerConfirmInput && registerPasswordInput.value !== registerConfirmInput.value) {
				toggleConfirmError(registerConfirmInput, registerConfirmError, true);
				return;
			}
			toggleConfirmError(registerConfirmInput, registerConfirmError, false);

			if (typeof grecaptcha !== 'undefined') {
				grecaptcha.ready(() => {
					grecaptcha.execute(recaptchaSiteKey, { action: 'register' }).then((token) => {
						const recaptchaInput = document.getElementById('g-recaptcha-response');
						if (recaptchaInput) {
							recaptchaInput.value = token;
						}
						registerForm.submit();
					});
				});
			} else {
				registerForm.submit();
			}
		};

		const passwordInputs = [registerPasswordInput, profilePasswordInput].filter(Boolean);
		passwordInputs.forEach((input) => {
			input.addEventListener('input', () => {
				evaluatePassword(input.value);
				togglePasswordError(false);
			});
		});

		const confirmInputs = [
			{ input: resetConfirmInput, error: resetConfirmError },
			{ input: profileConfirmInput, error: profileConfirmError },
			{ input: registerConfirmInput, error: registerConfirmError }
		];

		confirmInputs.forEach(({ input, error }) => {
			if (!input) {
				return;
			}
			input.addEventListener('input', () => {
				toggleConfirmError(input, error, false);
			});
		});

		const SubmitResetPassword = async (event) => {
			if (event) {
				event.preventDefault();
			}
			togglePasswordError(false);
			toggleConfirmError(resetConfirmInput, resetConfirmError, false);
			const passwordValue = profilePasswordInput?.value;
			if (!passwordValue || !passwordMeetsRequirements(passwordValue)) {
				togglePasswordError(true);
				return;
			}
			if (!resetConfirmInput || passwordValue !== resetConfirmInput.value) {
				toggleConfirmError(resetConfirmInput, resetConfirmError, true);
				return;
			}
			const payload = new URLSearchParams();
			payload.append('csrf_token', getCsrfToken());
			payload.append('action', 'resetPassword');
			payload.append('Password', passwordValue);
			const tokenValue = resetTokenInput?.value?.trim();
			if (tokenValue) {
				payload.append('token', tokenValue);
			}
			try {
				const response = await fetch(userApiUrl, {
					method: 'POST',
					body: payload,
					credentials: 'include',
				});
				const result = await response.json().catch(() => ({}));
				if (response.ok && result.success) {
					showResetSuccessModal();
					return;
				}
				window.alert('Unable to reset password right now.');
			} catch (error) {
				logError(error, 'SubmitResetPassword');
				window.alert('Unable to reset password right now.');
			}
			return;
		};
		if (registerForm) {
			registerForm.addEventListener('submit', SubmitRegister);
		}
		if (resetPasswordForm) {
			resetPasswordForm.addEventListener('submit', SubmitResetPassword);
		}
		const SubmitProfilePassword = async (event) => {
			if (event) {
				event.preventDefault();
			}
			togglePasswordError(false);
			toggleConfirmError(profileConfirmInput, profileConfirmError, false);
			const passwordValue = profilePasswordInput?.value;
			if (!passwordValue || !passwordMeetsRequirements(passwordValue)) {
				togglePasswordError(true);
				return;
			}
			if (!profileConfirmInput || passwordValue !== profileConfirmInput.value) {
				toggleConfirmError(profileConfirmInput, profileConfirmError, true);
				return;
			}
			if (!userApiUrl) {
				profilePasswordForm.submit();
				return;
			}
			const payload = new URLSearchParams();
			payload.append('csrf_token', getCsrfToken());
			payload.append('action', 'changePassword');
			payload.append('changePassword', passwordValue);
			try {
				const response = await fetch(userApiUrl, {
					method: 'POST',
					body: payload,
					credentials: 'include',
				});
				const result = await response.json().catch(() => ({}));
				if (response.ok && result.success) {
					window.location.href = baseUrl + 'account/logout/?redirect=account/login/%3FchangePass%3D1';
					return;
				}
				window.alert(result.error);
			} catch (error) {
				logError(error, 'SubmitProfilePassword');
				window.alert('Unable to change password right now.');
			}
		};
		if (profilePasswordForm) {
			profilePasswordForm.addEventListener('submit', SubmitProfilePassword);
		}
		if (usernameInput) {
			usernameInput.addEventListener('input', () => {
				if (usernameInput.value.length > 15) {
					usernameInput.value = usernameInput.value.slice(0, 15);
				}
				if (usernameInput) {
					usernameInput.classList.remove('is-invalid');
				}
				if (usernameLengthError) {
					usernameLengthError.classList.remove('text-danger');
				}
			});
		}
	};

	const passwordToggle = () => {
		const buttons = document.querySelectorAll('.password-toggle-btn');
		buttons.forEach((button) => {
			const targetId = button.getAttribute('aria-controls');
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

	const updateFavoriteIcon = (button, favorited) => {
		button.classList.toggle('is-favorited', favorited);
		button.setAttribute('aria-pressed', favorited ? 'true' : 'false');
		const icon = button.querySelector('img');
		if (icon) {
			icon.src = baseUrl + 'assets/img/' + (favorited ? 'star_on.svg' : 'star_off.svg');
			icon.alt = favorited ? 'starred' : 'not starred';
		}
	};

	const toggleFavorite = async (button) => {
		const word = (button.value ?? '').trim();
		if (!word) {
			return;
		}
		const favoriteType = button.classList.contains('favorite-etymology') ? 'etymology' : 'dictionary';
		const action = favoriteType === 'etymology' ? 'toggleEtyFavorite' : 'toggleFavorite';

		try {
			const form = new FormData();
			form.append('csrf_token', getCsrfToken());
			form.append('action', action);
			form.append('word', word);
			const response = await fetch(userApiUrl, {
				method: 'POST',
				body: form,
				credentials: 'include',
			});
			if (!response.ok) {
				throw new Error('Unable to toggle favorite');
			}
			const payload = await response.json();
			if (typeof payload.favorited === 'boolean') {
				updateFavoriteIcon(button, payload.favorited);
			}
		} catch (error) {
			logError(error, 'toggleFavorite');
			window.alert('Unable to update favorites right now.');
		}
	};

	const initFavoriteButtons = () => {
		if (!favoriteButtons.length) {
			return;
		}
		favoriteButtons.forEach((button) => {
			button.addEventListener('click', async (event) => {
				event.preventDefault();
				const authenticated = await checkAuthStatus();
				if (!authenticated) {
					showFavoriteAuthModal();
					return;
				}
				toggleFavorite(button);
			});
		});
	};

	const getFavoriteAuthModal = () => {
		if (!favoriteAuthModal) {
			return null;
		}
		if (!favoriteAuthModalInstance && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
			favoriteAuthModalInstance = new window.bootstrap.Modal(favoriteAuthModal);
		}
		return favoriteAuthModalInstance ?? null;
	};

	const showFavoriteAuthModal = () => {
		const modal = getFavoriteAuthModal();
		if (modal) {
			modal.show();
		}
	};

	const checkAuthStatus = async () => {
		try {
			const response = await fetch(userApiUrl + '?filename=script.js', {
				headers: { 'Accept': 'application/json' },
			});
			if (!response.ok) {
				return false;
			}
			const payload = await response.json();
			return payload.authenticated === true;
		} catch (error) {
			logError(error, 'checkAuthStatus');
			return false;
		}
	};

	const activateNavLinks = (selector = '#nav-menu') => {
		const nav = document.querySelector(selector);
		if (!nav) {
			return;
		}
		const navLinks = nav.querySelectorAll('.nav-link');
		if (!navLinks.length) {
			return;
		}
		const currentPath = window.location.pathname;
		navLinks.forEach((link) => {
			let active = false;
			try {
				const linkPath = new URL(link.href).pathname;
				if (linkPath === '/') {
					active = currentPath === '/';
				} else if (currentPath === linkPath || currentPath.startsWith(linkPath + '/')) {
					active = true;
				}
			} catch (error) {
				logError(error, 'activateNavLinks');
			}
			link.classList.toggle('active', active);
		});
	};

	const initProfileForms = () => {
		const forms = document.querySelectorAll('#username-form, #email-form, #password-form, #verification-form');
		if (!forms.length) {
			return;
		}
		const actionButtons = document.querySelectorAll('.profile-target-btn');
		const hideAll = () => {
			forms.forEach((form) => {
				form.hidden = true;
			});
		};
		hideAll();
		actionButtons.forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();
				hideAll();
				const targetId = button.getAttribute('aria-controls');
				const targetForm = document.getElementById(targetId);
				if (targetForm) {
					targetForm.hidden = false;
					targetForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
			});
		});
	};

	const initProfileValidation = () => {
		const usernameForm = document.getElementById('username-form');
		const emailForm = document.getElementById('email-form');
		const isUsernameValid = (value) => value.length >= 8 && value.length <= 15;

		const handleProfileSubmit = async (event, form) => {
			event.preventDefault();
			const submitBtn = form.querySelector('button[type="submit"]');
			const responseContainer = document.getElementById('edit-response-container');
			const responseText = document.getElementById('edit-response');
			try {
				const payload = new FormData(form);
				const response = await fetch(userApiUrl, {
					method: 'POST',
					body: payload,
					credentials: 'include',
				});
				const result = await response.json().catch(() => ({}));
				if (!response.ok || !result.success) {
					throw new Error('Update failed.');
				}

				const action = payload.get('action');
				let successMsg = 'Update successful!';
				if (action === 'editUsername') successMsg = 'Username updated successfully!';
				if (action === 'changeEmail') successMsg = 'An email has been sent to verify your new address.';

				responseContainer.hidden = false;
				responseContainer.classList.add('alert-success');
				responseText.textContent = successMsg;

				setTimeout(() => {
					window.location.reload();
				}, 2000);
			} catch (error) {
				logError(error, 'handleProfileSubmit');
				responseContainer.hidden = false;
				responseContainer.classList.add('alert-danger');
				responseText.textContent = 'Update failed.';
			}
		};

		if (usernameForm) {
			const usernameInput = usernameForm.querySelector('input[name="editUsername"]');
			if (usernameInput) {
				usernameForm.addEventListener('submit', (event) => {
					usernameInput.setCustomValidity('');
					const value = usernameInput.value.trim();
					if (!isUsernameValid(value)) {
						event.preventDefault();
						usernameInput.setCustomValidity('Username must be 8 to 15 characters long');
						usernameInput.reportValidity();
						return;
					}
					handleProfileSubmit(event, usernameForm);
				});
				usernameInput.addEventListener('input', () => {
					usernameInput.setCustomValidity('');
				});
			}
		}

		if (emailForm) {
			const emailInput = emailForm.querySelector('input[name="changeEmail"]');
			if (emailInput) {
				emailForm.addEventListener('submit', async (event) => {
					event.preventDefault();
					emailInput.setCustomValidity('');
					if (!emailInput.checkValidity()) {
						emailInput.reportValidity();
						return;
					}
					const responseContainer = document.getElementById('edit-response-container');
					const responseText = document.getElementById('edit-response');
					try {
						let recaptchaToken = '';
						if (typeof grecaptcha !== 'undefined') {
							recaptchaToken = await new Promise((resolve) => {
								grecaptcha.ready(() => {
									grecaptcha.execute(recaptchaSiteKey, { action: 'change_email' }).then(resolve);
								});
							});
						}
						const payload = new FormData();
						payload.append('email', emailInput.value.trim());
						payload.append('g-recaptcha-response', recaptchaToken);
						const response = await fetch(changeEmailApiUrl, {
							method: 'POST',
							body: payload,
							credentials: 'include',
						});
						const result = await response.json().catch(() => ({}));
						if (!response.ok || !result.success) {
							throw new Error(result.error ?? 'Update failed.');
						}
						if (result.download) {
							const content = atob(result.download.content);
							const blob = new Blob([content], { type: 'text/html' });
							const url = URL.createObjectURL(blob);
							const a = document.createElement('a');
							a.href = url;
							a.download = result.download.filename;
							document.body.appendChild(a);
							a.click();
							document.body.removeChild(a);
							URL.revokeObjectURL(url);
						}
						responseContainer.hidden = false;
						responseContainer.classList.remove('alert-danger');
						responseContainer.classList.add('alert-success');
						responseText.textContent = 'An email has been sent to verify your new address.';
					} catch (error) {
						logError(error, 'handleEmailChange');
						responseContainer.hidden = false;
						responseContainer.classList.remove('alert-success');
						responseContainer.classList.add('alert-danger');
						responseText.textContent = error.message ?? 'Update failed.';
					}
				});
			}
		}
	};

	const initDeactivateAccount = () => {
		const modalElement = document.getElementById('deactivate-account-modal');
		const confirmButton = document.getElementById('deactivate-confirm-btn');
		const deactivateBtn = document.getElementById('deactivate-account-btn');
		let modalInstance;
		let submitting = false;
		const setConfirmState = (busy) => {
			submitting = busy;
			if (confirmButton) {
				confirmButton.disabled = busy;
			}
		};
		const getModalInstance = () => {
			if (!modalElement || !window.bootstrap || typeof window.bootstrap.Modal !== 'function') {
				return null;
			}
			if (!modalInstance) {
				modalInstance = new window.bootstrap.Modal(modalElement);
			}
			return modalInstance;
		};
		const fallbackMessage = 'Are you sure?\nYou will not be able to reactivate your account.';
		const requestDeactivation = async () => {
			if (submitting) {
				return;
			}
			setConfirmState(true);
			const payload = new URLSearchParams();
			payload.append('csrf_token', getCsrfToken());
			payload.append('action', 'deactivateUser');
			try {
				const response = await fetch(userApiUrl, {
					method: 'POST',
					body: payload,
					credentials: 'include',
				});
				const result = await response.json().catch(() => ({}));
				if (!response.ok || !result.success) {
					throw new Error(result.error ?? 'Unable to deactivate account right now.');
				}
				const modal = getModalInstance();
				if (modal) {
					modal.hide();
				}
				window.location.href = baseUrl + 'account/logout/?redirect=account/login/';
			} catch (error) {
				logError(error, 'requestDeactivation');
				window.alert(error.message ?? 'Unable to deactivate account right now.');
				setConfirmState(false);
			}
		};
		const showConfirmation = () => {
			const modal = getModalInstance();
			if (modal) {
				modal.show();
				return;
			}
			if (window.confirm(fallbackMessage)) {
				requestDeactivation();
			}
		};
		if (confirmButton) {
			confirmButton.addEventListener('click', (event) => {
				event.preventDefault();
				requestDeactivation();
			});
		}
		if (modalElement) {
			modalElement.addEventListener('hidden.bs.modal', () => {
				setConfirmState(false);
			});
		}
		if (deactivateBtn) {
			deactivateBtn.addEventListener('click', (event) => {
				event.preventDefault();
				showConfirmation();
			});
		}
	};

	const initLuckyButtons = () => {
		const luckyButtons = document.querySelectorAll('.js-lucky-btn');
		if (!luckyButtons.length) {
			return;
		}
		const luckyEndpoint = baseUrl + 'api/search.php?action=feelingLucky';
		luckyButtons.forEach((button) => {
			const baseTarget = (button.value ?? '').trim();
			button.addEventListener('click', async () => {
				if (!baseTarget) {
					return;
				}
				try {
					const response = await fetch(luckyEndpoint + (baseTarget.includes('etymology') ? '&mode=etymology' : ''), { credentials: 'same-origin' });
					if (!response.ok) {
						throw new Error('Request failed');
					}
					const data = await response.json();
					window.location.href = baseTarget + encodeURIComponent(data.word);
				} catch (error) {
					logError(error, 'initLuckyButtons');
				}
			});
		});
	};

	const initNavSearch = () => {
		const navSearch = document.getElementById('nav-search');
		const navSearchForm = document.getElementById('nav-search-form');
		const navSearchInput = document.getElementById('nav-search-input');
		const navSearchMode = document.getElementById('nav-search-mode');
		const navSearchSubmit = document.getElementById('nav-search-submit');
		if (!navSearch || !navSearchForm || !navSearchInput || !navSearchMode) {
			return;
		}
		const modeImage = navSearchMode.querySelector('img');
		const modes = {
			dictionary: {
				action: baseUrl + 'dictionary/',
				placeholder: 'Search dictionary',
				icon: baseUrl + 'assets/img/dict-logo.webp',
				alt: 'Dictionary search',
			},
			etymology: {
				action: baseUrl + 'etymology/',
				placeholder: 'Search etymology',
				icon: baseUrl + 'assets/img/ety-logo.webp',
				alt: 'Etymology search',
			},
		};
		let navSearchModeState = navSearchMode.classList.contains('nav-mode-etymology') ? 'etymology' : 'dictionary';
		const setMode = (mode) => {
			const config = modes[mode] ?? modes.dictionary;
			navSearchModeState = mode in modes ? mode : 'dictionary';
			navSearchForm.action = config.action;
			navSearchInput.placeholder = config.placeholder;
			navSearchInput.setAttribute('aria-label', config.placeholder);
			navSearchMode.setAttribute('aria-label', config.alt);
			navSearchMode.title = config.alt;
			navSearchMode.classList.toggle('nav-mode-etymology', navSearchModeState === 'etymology');
			navSearchMode.classList.toggle('nav-mode-dictionary', navSearchModeState !== 'etymology');
			if (modeImage) {
				modeImage.src = config.icon;
				modeImage.alt = config.alt;
			}
		};
		const initialMode = navSearchModeState;
		setMode(initialMode);
		const toggleMode = () => {
			const nextMode = navSearchModeState === 'dictionary' ? 'etymology' : 'dictionary';
			setMode(nextMode);
		};
		navSearchMode.addEventListener('click', (event) => {
			event.preventDefault();
			toggleMode();
			navSearchInput.focus();
		});
		const expand = () => {
			navSearch.classList.add('nav-search-expanded');
		};
		const collapse = () => {
			if (navSearchInput.value.trim() || navSearch.contains(document.activeElement)) {
				return;
			}
			navSearch.classList.remove('nav-search-expanded');
		};
		const setActive = (active) => {
			navSearch.classList.toggle('nav-search-active', active);
			if (active) {
				expand();
			}
		};
		const activateNavSearch = () => {
			setActive(true);
			navSearchInput.focus();
		};
		const shouldSkipNavSearchActivation = (target) => {
			return (navSearchSubmit && (navSearchSubmit === target || navSearchSubmit.contains(target))) ||
				(navSearchMode && (navSearchMode === target || navSearchMode.contains(target)));
		};
		let blurTimer;
		navSearch.addEventListener('focusin', () => {
			clearTimeout(blurTimer);
			setActive(true);
		});
		navSearch.addEventListener('click', (event) => {
			if (shouldSkipNavSearchActivation(event.target)) {
				return;
			}
			activateNavSearch();
		});
		navSearch.addEventListener('touchstart', (event) => {
			if (shouldSkipNavSearchActivation(event.target)) {
				return;
			}
			activateNavSearch();
		});
		navSearch.addEventListener('focusout', () => {
			blurTimer = window.setTimeout(() => {
				const stillFocused = navSearch.contains(document.activeElement);
				setActive(stillFocused);
				if (!stillFocused) {
					collapse();
				}
			}, 80);
		});
		navSearch.addEventListener('mouseenter', () => {
			expand();
		});
		navSearch.addEventListener('mouseleave', () => {
			if (!navSearch.classList.contains('nav-search-active')) {
				collapse();
			}
		});
		navSearchInput.addEventListener('input', () => {
			if (navSearchInput.value.trim()) {
				expand();
				return;
			}
			if (!navSearch.classList.contains('nav-search-active')) {
				collapse();
			}
		});
		if (navSearchSubmit) {
			navSearchSubmit.addEventListener('focus', () => {
				setActive(true);
			});
		}
		navSearchForm.addEventListener('submit', (event) => {
			const trimmed = navSearchInput.value.trim();
			if (!trimmed) {
				event.preventDefault();
				navSearchInput.focus();
				return;
			}
			navSearchInput.value = trimmed;
		});
		if (navSearchInput.value.trim()) {
			expand();
		}
	};

	const initContactForm = () => {
		const form = document.getElementById('contact-form');
		if (!form) {
			return;
		}
		const nameInput = form.querySelector('input[name="name"]');
		const emailInput = form.querySelector('input[name="email"]');
		const messageInput = document.getElementById('contact-message');
		const counterEl = document.getElementById('contact-message-count');
		const statusBox = document.getElementById('contact-status');
		const submitBtn = document.getElementById('contact-submit');
		const defaultName = nameInput ? nameInput.value : '';
		const defaultEmail = emailInput ? emailInput.value : '';
		const maxChars = messageInput?.getAttribute('maxlength') ? parseInt(messageInput.getAttribute('maxlength'), 10) : 1000;

		const updateCounter = () => {
			if (!messageInput || !counterEl) {
				return;
			}
			const length = [...messageInput.value].length;
			const limit = Number.isFinite(maxChars) ? maxChars : 1000;
			counterEl.textContent = length + ' / ' + limit;
		};

		const setStatus = (text, success = true) => {
			if (!statusBox) {
				return;
			}
			statusBox.textContent = text || '';
			statusBox.classList.remove('d-none', 'alert-success', 'alert-danger');
			if (!text) {
				statusBox.classList.add('d-none');
				return;
			}
			statusBox.classList.add(success ? 'alert-success' : 'alert-danger');
		};

		const setSubmitting = (busy) => {
			if (submitBtn) {
				submitBtn.disabled = busy;
			}
			form.classList.toggle('is-submitting', busy);
		};

		if (messageInput) {
			messageInput.addEventListener('input', updateCounter);
			updateCounter();
		}

		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			setStatus('', true);
			if (!form.checkValidity()) {
				form.reportValidity();
				return;
			}

			const recaptchaInput = document.getElementById('contact-recaptcha-response');
			if (typeof grecaptcha !== 'undefined' && recaptchaInput) {
				try {
					await new Promise((resolve) => {
						grecaptcha.ready(async () => {
							const token = await grecaptcha.execute('6LdtfCEsAAAAAPoqdfwDkqJ0PxQ5e9M8fadPxdYs', { action: 'contact' });
							recaptchaInput.value = token;
							resolve();
						});
					});
				} catch (err) {
					setStatus('Security verification failed. Please try again.', false);
					return;
				}
			}

			setSubmitting(true);
			try {
				const payload = new FormData(form);
				const response = await fetch(contactApiUrl, {
					method: 'POST',
					body: payload,
					credentials: 'include',
				});
				const result = await response.json().catch(() => ({}));
				if (!response.ok || result.status !== 'success') {
					throw new Error(result.error ?? 'Unable to send message right now.');
				}
				setStatus('Thank you for contacting us. We will get back to you by email.', true);
				form.reset();
				if (nameInput && defaultName) {
					nameInput.value = defaultName;
				}
				if (emailInput && defaultEmail) {
					emailInput.value = defaultEmail;
				}
				form.classList.add('d-none');
				updateCounter();
			} catch (error) {
				logError(error, 'handleContactSubmit');
				setStatus(error.message ?? 'Unable to send message right now.', false);
			} finally {
				setSubmitting(false);
			}
		});
	};

	const initResetPasswordForm = () => {
		const emailForm = document.getElementById('email-form');
		const successDiv = document.getElementById('submit-success');
		const recaptchaInput = document.getElementById('g-recaptcha-response');
		const resetPasswordApiUrl = baseUrl + 'api/reset_password.php';

		if (!emailForm || !successDiv) {
			return;
		}

		emailForm.addEventListener('submit', async (event) => {
			event.preventDefault();

			if (typeof grecaptcha !== 'undefined' && recaptchaInput) {
				try {
					await new Promise((resolve) => {
						grecaptcha.ready(async () => {
							const token = await grecaptcha.execute(recaptchaSiteKey, { action: 'reset_password' });
							recaptchaInput.value = token;
							resolve();
						});
					});
				} catch (err) {
					logError(err, 'initResetPasswordForm_recaptcha');
					return;
				}
			}

			const formData = new FormData(emailForm);

			try {
				const response = await fetch(resetPasswordApiUrl, {
					method: 'POST',
					body: formData
				});

				if (response.ok) {
					const result = await response.json();
					successDiv.hidden = false;

					if (result.download) {
						const content = atob(result.download.content);
						const blob = new Blob([content], { type: 'text/html' });
						const url = URL.createObjectURL(blob);
						const a = document.createElement('a');
						a.href = url;
						a.download = result.download.filename;
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						URL.revokeObjectURL(url);
					}
				}
			} catch (error) {
				logError(error, 'initResetPasswordForm_submit');
			}
		});
	};


	if (ttsBtn && ipaElement) {
		ttsBtn.addEventListener('click', playPronunciation);
	}

	initFavoritesIpaButtons();
	initFavoriteButtons();

	passwordToggle();
	initAuthForms();
	activateNavLinks();
	activateNavLinks('#user-nav');
	initLuckyButtons();
	initNavSearch();
	initProfileForms();
	initProfileValidation();
	initDeactivateAccount();
	initContactForm();
	initResetPasswordForm();
})();
