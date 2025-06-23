(function ($) {
	$(document).ready(function () {
		let $pixelgradeReviewModal = $('.pxg-review-modal'),
			$pixelgradeReviewModalButton = $('.pxg-review-modal .btn'),
			$pixelgradeReviewModalForm = $('.pxg-review-modal-container'),
			$pixelgradeReviewModalBody = $('.pxg-review-modal__body'),
			scrollPosition

		$(document).on('wp-theme-update-success', runPixelgradeReviewModal)

		window.addEventListener('updatedTheme', runPixelgradeReviewModal)

		function runPixelgradeReviewModal (event, response) {
			if (!response && event.detail) {
				response = event.detail
			}

			// We will only show the notice for our own active theme.
			if (!response.slug || response.slug !== pxgReviewNotice.activeThemeSlug) {
				return
			}

			// We will wait a little before opening the modal to avoid too much stuff happening at once.
			setTimeout(function () {
				openPixelgradeReviewModal()
			}, 1200)

			// Bind to the external link click.
			$pixelgradeReviewModalButton.on('click', removePixelgradeReviewModal)
		}

		function scrollContentModal () {
			let scroll = $(this)[0].scrollTop,
				isBottom = $pixelgradeReviewModalBody[0].scrollTop + $pixelgradeReviewModalBody[0].offsetHeight === $pixelgradeReviewModalBody[0].scrollHeight

			if (scroll > scrollPosition) {
				// When we are scrolling down, add a shadow on top of the content
				$pixelgradeReviewModalBody.addClass('pxg-review-modal__body--top-shadow')

				if (isBottom) {
					// If we hit bottom, remove bottom shadow, because we don't need it
					$pixelgradeReviewModalBody.removeClass('pxg-review-modal__body--bottom-shadow')
				}

			} else {
				// When we are scrolling up, add a shadow at the bottom of the content
				$pixelgradeReviewModalBody.addClass('pxg-review-modal__body--bottom-shadow')

				if (scroll === 0) {
					// If we are on top of the content, remove top shadow, because we don't need it
					$pixelgradeReviewModalBody.removeClass('pxg-review-modal__body--top-shadow')
				}
			}

			scrollPosition = scroll

		}

		function openPixelgradeReviewModal () {
			// Make sure that the theme details overlays is not active, in case we are in the Appearance > Themes page.
			if ($('div.theme-overlay > .theme-overlay.active').length) {
				wp.themes.Run.view.trigger('theme:close')

				$('html, body').animate({scrollTop: 0}, 'fast')
			}

			// Open our modal.
			setTimeout(function () {
				// Initialize the scroll position.
				scrollPosition = $pixelgradeReviewModalBody[0].scrollTop

				$pixelgradeReviewModal.removeClass('hidden').addClass('is-open')
				$pixelgradeReviewModalForm.addClass('slide-in-bottom')

				let isScrollable = $pixelgradeReviewModalBody[0].scrollHeight > $pixelgradeReviewModalBody[0].offsetHeight

				// Add shadows only when content is overflowing
				if (isScrollable) {
					$pixelgradeReviewModal.addClass('pxg-review-modal--is-scrollable')
					$pixelgradeReviewModalBody.addClass('pxg-review-modal__body--bottom-shadow')
					$pixelgradeReviewModalBody.on('scroll', scrollContentModal)
				}
			}, 300)

		}

		function removePixelgradeReviewModal () {
			$pixelgradeReviewModalForm.addClass('slide-out-top')
			setTimeout(function () {
				$pixelgradeReviewModal.remove()
			}, 300)
		}
	})

})(jQuery)
