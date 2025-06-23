import _ from 'lodash'

(function (window, $) {
	$(document).on('click', '.club-install-theme', function (e) {
		e.preventDefault()
		let button = $(this),
			theme = button.closest('.theme')

		if (button.hasClass('disabled') || button.hasClass('updating-message')) {
			return
		}

		let download_url = button.data('url')
		let slug = button.data('slug')
		let sku = button.data('sku')
		let isLTTheme = button.data('is-lt-theme') === 'yes' ? 'yes' : ''

		// Clear any previous errors.
		button.closest('.theme').find('.theme-install-error').remove()

		// Mark this theme as being installing - this way we can style things more sanely.
		theme.addClass('installed')

		// Disable the Install button, add the loading class and change the text.
		button.removeClass('button-primary')
		button.addClass('disabled')
		button.addClass('updating-message')
		button.text('Installing...')

		// Ajax to install the theme.
		$.ajax({
			url: pixcare.wpRest.endpoint.installTheme.url,
			method: pixcare.wpRest.endpoint.installTheme.method,
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', pixcare.wpRest.nonce)
			},
			data: {
				download_url: download_url,
				slug: slug,
				sku: sku,
				is_lt_theme: isLTTheme,
				pixcare_nonce: pixcare.wpRest.pixcare_nonce
			},
			success: function (response) {
				if (!_.isUndefined(response.code) && 'success' === response.code) {
					button.addClass('button-primary')
					button.removeClass('disabled')
					button.removeClass('club-install-theme')
					button.removeClass('updating-message')
					button.addClass('club-activate-theme')
					button.text('Activate')
				} else if (!_.isEmpty(response.data.errors)) {
					button.removeClass('disabled')
					button.removeClass('updating-message')
					button.text('Install')

					theme.removeClass('installed')

					let errorMessage = ''
					// Determine the message error
					switch (Object.keys(response.data.errors)[0]) {
						case 'mkdir_failed_destination':
							errorMessage = 'The theme could not be installed. Please check if your current user has write permissions on the themes folder of your WordPress installation.'
							break
						case 'no_package':
							errorMessage = response.data.errors.errors['no_package']
							break
						case 'download_failed':
							errorMessage = response.data.errors.errors['download_failed']
							break
						default:
							errorMessage = 'An unexpected error occurred. Please contact us at help@pixelgrade.com'
							break
					}

					// Add an error div.
					button.closest('.theme').find('.theme-screenshot').prepend('<div class="error theme-install-error">' + errorMessage + '</div>')
				}
			}
		})
	})

	$(document).on('click', '.club-activate-theme', function (e) {
		e.preventDefault()

		let button = $(this)
		let slug = button.data('slug')
		let sku = button.data('sku')
		let isLTTheme = button.data('is-lt-theme') === 'yes' ? 'yes' : ''

		button.removeClass('button-primary')
		button.addClass('disabled')
		button.addClass('updating-message')
		button.text('Activating...')

		// Ajax to activate the theme
		$.ajax({
			url: pixcare.wpRest.endpoint.activateTheme.url,
			method: pixcare.wpRest.endpoint.activateTheme.method,
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', pixcare.wpRest.nonce)
			},
			data: {
				slug: slug,
				sku: sku,
				is_lt_theme: isLTTheme,
				pixcare_nonce: pixcare.wpRest.pixcare_nonce
			},
			success: function (response) {
				if ('success' === response.code) {
					// Redirect to the Appearance > Themes WordPress dashboard page
					window.location.href = pixcare.themesUrl
				} else {
					button.addClass('button-primary')
					button.removeClass('disabled')
					button.removeClass('updating-message')
					button.text('Activate')

					// Add an error div
					button.closest('.theme').find('.theme-screenshot').prepend('<div class="error theme-install-error">' + response.message + '</div>')
				}
			}
		})
	})
})(window, jQuery)
