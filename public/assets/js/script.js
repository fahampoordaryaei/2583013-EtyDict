(() => {
	const baseUrl = window.etydictBaseUrl ?? '/';
	const apiUrl = baseUrl + 'api/tts.php';
	const dictionaryUrl = baseUrl + 'dictionary/?w=';
	const autocompleteUrl = baseUrl + 'api/autocomplete.php';
	const checkUsernameUrl = baseUrl + 'api/check-username.php';
	const userApiUrl = baseUrl + 'api/user.php';
	const ttsBtn = document.getElementById('ipa-tts-btn');
	const ipaElement = document.getElementById('ipa-text');
	const suggestionsBox = document.getElementById('autocomplete-suggestions');
	const searchInput = document.querySelector('#search-form input[name="w"]');
	const favoriteButton = document.getElementById('fav-btn');
	const favoriteAuthModal = document.getElementById('favorite-auth-modal');
	const audioPlayer = new Audio();
	let favoriteAuthModalInstance;

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

	const playPronunciation = () => {
		if (!ttsBtn || !ipaElement) {
			return;
		}

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
	};

	const renderForms = (forms) => {
		if (!forms) {
			return '';
		}
		const parts = (forms + '').split(',');
		return parts.map((form, index) => '<span class="fst-italic">' + form + '</span>' + (index < parts.length - 1 ? ' / ' : '')).join('');
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

	const passwordCriteriaCheckers = {
		length: (val) => val.length >= 8,
		uppercase: (val) => /[A-Z]/.test(val),
		lowercase: (val) => /[a-z]/.test(val),
		number: (val) => /[0-9]/.test(val),
		special: (val) => /[^A-Za-z0-9]/.test(val),
	};

	const applyPasswordCriteriaClasses = (item, isValid) => {
		item.classList.remove('text-danger', 'text-success');
		item.classList.add(isValid ? 'text-success' : 'text-danger');
	};

	const updatePasswordCriteria = (value, items) => {
		const candidate = value;
		if (!candidate) {
			items.forEach((item) => {
				item.classList.remove('text-danger', 'text-success');
			});
			return;
		}
		items.forEach((item) => {
			const key = item.dataset.criteria;
			const check = passwordCriteriaCheckers[key];
			const met = check(candidate);
			applyPasswordCriteriaClasses(item, met);
		});
	};

	const passwordMeetsRequirements = (value) => {
		if (!value) {
			return false;
		}
		return Object.values(passwordCriteriaCheckers).every((check) => check(value));
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
		.then((response) => response.ok ? response.json() : Promise.reject(new Error('Autocomplete request failed')))
		.then(renderMatches)
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
		const resetPasswordForm = document.getElementById('reset-password-form');
		const profilePasswordForm = document.getElementById('password-form');
		const usernameInput = document.getElementById('username');
		const usernameLengthError = document.getElementById('username-length-error');
		const usernameUniqueError = document.getElementById('username-unique-error');
		const registerPasswordInput = document.getElementById('register-password');
		const profilePasswordInput = document.getElementById('profile-password');
		const passwordError = document.getElementById('password-error');
		const resetConfirmInput = document.getElementById('confirm-password');
		const resetConfirmError = document.getElementById('confirm-password-match-error');
		const profileConfirmInput = document.getElementById('profile-confirm-password');
		const profileConfirmError = document.getElementById('profile-password-match-error');
		const resetTokenInput = document.getElementById('reset-token');
		const criteriaItems = document.querySelectorAll('#password-criteria li[data-criteria]');

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
			if (usernameValue.length < 8 || usernameValue.length > 15) {
				highlightLengthError();
				return;
			}
			if (!registerPasswordInput || !passwordMeetsRequirements(registerPasswordInput.value)) {
				togglePasswordError(true);
				return;
			}
			togglePasswordError(false);
			const exists = await checkUsernameExists(usernameValue);
			if (exists) {
				showUniqueUsernameError();
				return;
			}
			registerForm.submit();
		};

		const passwordInputs = [registerPasswordInput, profilePasswordInput].filter(Boolean);
		passwordInputs.forEach((input) => {
			input.addEventListener('input', (event) => {
				evaluatePassword(event.target.value);
				togglePasswordError(false);
				if (input === profilePasswordInput) {
					toggleConfirmError(resetConfirmInput, resetConfirmError, false);
					toggleConfirmError(profileConfirmInput, profileConfirmError, false);
				}
			});
		});
		[resetConfirmInput, profileConfirmInput].forEach((inputEl, index) => {
			if (!inputEl) {
				return;
			}
			const errorEl = index === 0 ? resetConfirmError : profileConfirmError;
			inputEl.addEventListener('input', () => {
				toggleConfirmError(inputEl, errorEl, false);
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
				window.alert(result.error ?? 'Unable to reset password right now.');
			} catch (error) {
				console.error(error);
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
					window.location.href = baseUrl + 'account/logout/?redirect=account/login/';
					return;
				}
				window.alert(result.error);
			} catch (error) {
				console.error(error);
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

	const updateFavoriteIcon = (favorited) => {
		if (!favoriteButton) {
			return;
		}
		favoriteButton.dataset.favorited = favorited ? 'true' : 'false';
		const icon = favoriteButton.querySelector('img');
		if (icon) {
			icon.src = baseUrl + 'assets/img/' + (favorited ? 'star_on.svg' : 'star_off.svg');
			icon.alt = favorited ? 'starred' : 'not starred';
		}
	};

	const toggleFavorite = async () => {
		if (!favoriteButton) {
			return;
		}
		const word = favoriteButton.dataset.word;
		if (!word) {
			return;
		}

		try {
			const response = await fetch(userApiUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'toggleFavorite', word }),
			});
			if (!response.ok) {
				throw new Error('Unable to toggle favorite');
			}
			const payload = await response.json();
			if (typeof payload.favorited === 'boolean') {
				updateFavoriteIcon(payload.favorited);
			}
		} catch (error) {
			console.error(error);
			window.alert('Unable to update favorites right now.');
		}
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
			console.error(error);
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
				console.error(error);
			}
			link.classList.toggle('active', active);
		});
	};

	const initProfileForms = () => {
		const forms = document.querySelectorAll('#username-form, #email-form, #password-form, #verification-form');
		if (!forms.length) {
			return;
		}
		const actionButtons = document.querySelectorAll('[data-profile-target]');
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
				const targetId = button.dataset.profileTarget;
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
		const passwordForm = document.getElementById('password-form');
		const verificationForm = document.getElementById('verification-form');
		const isUsernameValid = (value) => value.length >= 8 && value.length <= 15;

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
					}
				});
				usernameInput.addEventListener('input', () => {
					usernameInput.setCustomValidity('');
				});
			}
		}

		if (emailForm) {
			const emailInput = emailForm.querySelector('input[name="editEmail"]');
			if (emailInput) {
				emailForm.addEventListener('submit', (event) => {
					emailInput.setCustomValidity('');
					if (!emailInput.checkValidity()) {
						event.preventDefault();
						emailInput.reportValidity();
					}
				});
			}
		}

		if (passwordForm) {
			const passwordInput = passwordForm.querySelector('input[name="editPassword"]');
			if (passwordInput) {
				passwordForm.addEventListener('submit', (event) => {
					passwordInput.setCustomValidity('');
					if (!passwordMeetsRequirements(passwordInput.value)) {
						event.preventDefault();
						passwordInput.setCustomValidity('Password must meet all listed criteria');
						passwordInput.reportValidity();
					}
				});
				passwordInput.addEventListener('input', () => {
					passwordInput.setCustomValidity('');
				});
			}
		}

		if (verificationForm) {
			verificationForm.addEventListener('submit', (event) => {
				const subjectInput = verificationForm.querySelector('input[name="subject"]');
				const messageInput = verificationForm.querySelector('textarea[name="message"]');
				if (subjectInput && !subjectInput.checkValidity()) {
					event.preventDefault();
					subjectInput.reportValidity();
					return;
				}
				if (messageInput && !messageInput.checkValidity()) {
					event.preventDefault();
					messageInput.reportValidity();
				}
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
			const baseTarget = button.dataset.target;
			button.addEventListener('click', async () => {
				if (!baseTarget) {
					return;
				}
				try {
					const response = await fetch(luckyEndpoint, { credentials: 'same-origin' });
					if (!response.ok) {
						throw new Error('Request failed');
					}
					const data = await response.json();
					window.location.href = baseTarget + encodeURIComponent(data.word);
				} catch (error) {
					console.error(error);
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
				icon: baseUrl + 'assets/img/search-dict.webp',
				alt: 'Dictionary search',
			},
			etymology: {
				action: baseUrl + 'etymology/',
				placeholder: 'Search etymology',
				icon: baseUrl + 'assets/img/search-ety.webp',
				alt: 'Etymology search',
			},
		};
		const setMode = (mode) => {
			const config = modes[mode] ?? modes.dictionary;
			navSearchMode.dataset.mode = mode in modes ? mode : 'dictionary';
			navSearchForm.action = config.action;
			navSearchInput.placeholder = config.placeholder;
			navSearchInput.setAttribute('aria-label', config.placeholder);
			navSearchMode.setAttribute('aria-label', config.alt);
			navSearchMode.title = config.alt;
			if (modeImage) {
				modeImage.src = config.icon;
				modeImage.alt = config.alt;
			}
		};
		const initialMode = navSearchMode.dataset.mode === 'etymology' ? 'etymology' : 'dictionary';
		setMode(initialMode);
		const toggleMode = () => {
			const currentMode = navSearchMode.dataset.mode === 'etymology' ? 'etymology' : 'dictionary';
			const nextMode = currentMode === 'dictionary' ? 'etymology' : 'dictionary';
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
		let blurTimer;
		navSearch.addEventListener('focusin', () => {
			clearTimeout(blurTimer);
			setActive(true);
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


	if (ttsBtn && ipaElement) {
		ttsBtn.addEventListener('click', playPronunciation);
	}

	if (favoriteButton) {
		favoriteButton.addEventListener('click', async (event) => {
			event.preventDefault();
			const authenticated = await checkAuthStatus();
			if (!authenticated) {
				showFavoriteAuthModal();
				return;
			}
			toggleFavorite();
		});
	}

	passwordToggle();
	RegisterValidate();
	activateNavLinks();
	activateNavLinks('#user-nav');
	initLuckyButtons();
	initNavSearch();
	initProfileForms();
	initProfileValidation();
})();
