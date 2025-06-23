$('.js-widget-gallery').magnificPopup({
	delegate: '.listing-gallery__item', // child items selector, by clicking on it popup will open
	type: 'image',
	image: {
		titleSrc: function (item) {
			var output = '';

			output += item.el.find('img').attr('caption');
			output += '<span class="mfp-description">' + item.el.find('img').attr('description') + '</span>';

			return output;
		}
	},
	gallery: {
		enabled: true,
		tCounter: '<span class="mfp-counter">%curr%/%total%</span>',
		arrowMarkup: '<div class="gallery-arrow  gallery-arrow-%dir%  is--ready">' + $('.arrow-icon-svg').html() + '</div>'
	}
});

$('.listing-gallery__all').on( 'click', function(e) {
	e.preventDefault();
	$('.js-widget-gallery').magnificPopup('open');
} );

if ( typeof listable_params.login_url !== "undefined" && listable_params.login_url.indexOf( 'action=logout' ) === -1 ) {
	$('a.iframe-login-link').magnificPopup({
		mainClass: "mfp-bg-transparent  mfp-login-modal",
		type: 'iframe',
		src: listable_params.login_url,
		iframe: {
			markup: '<div class="mfp-iframe-scaler  mfp-wp-login">'+
			'<div class="mfp-close"></div>'+
			'<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>'+
			'</div>' // HTML markup of popup, `mfp-close` will be replaced by the close button
		},
		callbacks: {
			open: function() {
				if ( ! listableDocumentCookies.hasItem('listable_login_modal') )  {
					listableDocumentCookies.setItem('listable_login_modal', 'opened', null, '/');
				}

				closeMenu();

				$('body').addClass('overlay-is-open');
				$('body').width($('body').width());
				$('body').css('overflow', 'hidden');
			},
			close: function() {
				listableDocumentCookies.removeItem('listable_login_modal', '/');

				$('body').removeClass('overlay-is-open');
				$('body').removeAttr('style');
			}
		}
	});
}