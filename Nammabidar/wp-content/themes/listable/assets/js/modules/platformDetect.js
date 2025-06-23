function platformDetect() {

	var	isIE = typeof (is_ie) !== "undefined" || (!(window.ActiveXObject) && "ActiveXObject" in window),
		isiele10 = ua.match(/msie (9|([1-9][0-9]))/i),
		isie9 = ua.match(/msie (9)/i);

	iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

	if (isIE) {
		$html.addClass('is--ie');
	}

	if (isiele10) {
		$html.addClass('is--iele10');
	}

	if (isie9) {
		$html.addClass('is--ie9');
	}

	if (/Edge\/12./i.test(navigator.userAgent)){
		$html.addClass('is--edge');
	}

	if( iOS ) {
		$html.addClass('is--ios');
	}
}