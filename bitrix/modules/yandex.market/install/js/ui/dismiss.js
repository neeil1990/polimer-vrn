(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Ui = BX.namespace('YandexMarket.Ui');

	var constructor = Ui.Dismiss = Plugin.Base.extend({

		defaults: {
			targetElement: null,
			cookie: null,
		},

		activate: function() {
			this.setCookie();
			this.removeTarget();
		},

		getTarget: function() {
			var selector = this.getElementSelector('target');
			var result;

			if (selector) {
				result = this.getElement('target', this.$el, 'closest');
			} else {
				result = this.$el.parent();
			}

			return result;
		},

		removeTarget: function() {
			this.getTarget().remove();
		},

		setCookie: function() {
			var name = this.options.cookie;

			if (name != null) {
				BX.setCookie(name, 'Y', { expires: 86400 * 31 * 12 });
			}
		},

	}, {
		dataName: 'uiDismiss'
	});

})(BX, jQuery, window);