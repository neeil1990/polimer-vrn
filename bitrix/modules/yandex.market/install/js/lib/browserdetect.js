(function() {

	var userAgent = navigator && navigator.userAgent;
	var variants = [
		'Opera',
		'Chrome',
		'Safari',
		'Firefox',
		'Edge',
		'MSIE'
	];
	var variantIndex;
	var variant;
	var browser;

	// browser select

	if (userAgent) {
		for (variantIndex = 0; variantIndex < variants.length; variantIndex++) {
			variant = variants[variantIndex];

			if (userAgent.indexOf(variant) !== -1) {
				browser = variant;
				break;
			}
		}
	}

	// add root className

	if (browser != null) {
		document.documentElement.className += ' yamarket-' + browser.toLowerCase();
	}

})();