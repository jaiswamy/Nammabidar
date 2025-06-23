var Carousel = (function() {

	var offset, $container, $images, $prev, $next, lastScroll, totalWidth, $arrow, $currentImg;

	function init() {

		if ( ! $('.entry-featured-gallery').length ) {
			return;
		}

		offset 		= $('.entry-header').offset().left;
		$container 	= $('.entry-featured-gallery');
		$arrow 		= $('.arrow-icon-svg');
		$prev 		= $('<div class="gallery-arrow gallery-arrow-prev">' + $arrow.html() + '</div>');
		$next 		= $('<div class="gallery-arrow gallery-arrow-next">' + $arrow.html() + '</div>');
		lastScroll 	= 0;
		totalWidth  = 0;

		var isRtl = $body.hasClass( 'rtl' );

		if ( isRtl ) {
			$container.children().each( function( i, obj ) {
				$container.prepend( obj )
			} );
		}

		$images = $container.find('.entry-featured-image');

		if ( $container.length && $images.length ) {

			$prev.add($next).appendTo($container.parent());

			var zeroWidth = $images.last().width();

			$currentImg = $images.first();

			$images.each(function(i, obj) {
				var $item 		= $(obj),
					itemWidth 	= $item.width(),
					itemOffset 	= $item.offset().left,
					marginRight = parseInt($item.css('marginRight'), 10);

				totalWidth = totalWidth + itemWidth + marginRight;

				$item.data('index', i);
				$item.data('offset', itemOffset);
				$item.data('width', itemWidth);
			});

			if ( totalWidth < windowWidth ) {
				$container.parent().addClass('is--at-start is--at-end').addClass('carousel-center');
			}

			lastScroll 	= zeroWidth - offset;
			$images 	= $container.children();

			onScroll();
			$container.on('scroll', onScroll);
			$('.gallery-arrow-prev').on('click', goToPrev);
			$('.gallery-arrow-next').on('click', goToNext);
			$prev.add($next).addClass('is--ready');
		}

		if ( isRtl ) {
			$container.scrollLeft( $container[0].scrollWidth );
		}
	}

	function onScroll() {
		lastScroll = $container.scrollLeft();
		$container.parent()
			.toggleClass('is--at-start', lastScroll <= 10)
			.toggleClass('is--at-end', lastScroll >= totalWidth - windowWidth - 10);
	}

	function goToPrev() {
		var $to;
		$images.each(function(i, obj) {
			var $image = $(obj);
			if ($image.data('offset') < lastScroll) {
				$to = $image;
			}
		});

		if (typeof $to !== "undefined") {
			setCurrent($to);
		}
	}

	function goToNext() {
		var $to;
		$images.each(function(i, obj) {
			var $image = $(obj);

			if ($image.data('offset') + $image.data('width') > lastScroll + windowWidth) {

				if ( $image.attr('src') == $currentImg.attr('src') ) {
					$image = $image.next();
				}

				$to = $image;
				return false;
			}
		});

		if (typeof $to !== "undefined") {
			setCurrent($to);
		}
	}

	function setCurrent($current) {
		$currentImg = $current;

		TweenLite.to($container, .3, {
			scrollTo: {
				x: $current.data('offset') - offset
			},
			ease: Power2.easeOut
		});
	}

	return {
		init: init
	}
})();
