(function ($) {
	wp.customize.bind('ready', function () {
		var iframeContents = null

		wp.customize.previewer.bind('synced', function () {
			let iframe = $('#customize-preview iframe')
			iframeContents = iframe.contents()

			iframeContents.find('[data-customize-partial-type="widget"]').each(function (index, widget) {
				let $widget = $(widget)
				if (!$widget.find('.widget__outline').length) {
					$widget.prepend('<div class="widget__outline"></div>')
				}
			})
		})

		wp.customize.previewer.bind('highlight-widget-control', function (widgetId) {
			showWidgetName(widgetId)
		})

		wp.customize.control.bind('change', function (widget) {
			let widgetIdArray = widget.selector.split('_')
			widgetIdArray.splice(0, 1)
			let widgetId = widgetIdArray.join('_')

			onControlChange(widget.selector, widgetId)
		})

		function showWidgetName (widgetId) {
			if (!iframeContents) { return }
			let $widget = iframeContents.find('[data-customize-partial-id="widget[' + widgetId + ']"]')
			if ( !$widget.length ) { return }
			let name = $widget.data('customize-widget-name')
			let $button = $widget.find('.customize-partial-edit-shortcut-button')

			$button.find('span').remove()
			$button.append('<span><strong>Edit</strong>: ' + name + '</span>')
			if (!$widget.find('.widget__outline').length) {
				$widget.prepend('<div class="widget__outline"></div>')
			}
		}

		function onControlChange (selector, widgetId) {
			$(selector).on('click', function () {
				let $widget = iframeContents.find('[data-customize-widget-id=' + widgetId + ']')
				if ( !$widget.length ) { return }

				let position = $widget.offset().top
				position = position - 150
				iframeContents.find('html, body').animate({
					'scrollTop': position
				}, 500)
			})
		}
	})
})(jQuery)
