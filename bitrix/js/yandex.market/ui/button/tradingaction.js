(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const Button = BX.namespace('YandexMarket.Ui.Button');

	const constructor = Button.TradingAction = Plugin.Base.extend({

		defaults: {
			url: null,
			setup: null,
			path: null,
			payload: {},

			lang: {},
			langPrefix: 'YANDEX_MARKET_UI_BUTTON_TRADING_ACTION_',
		},

		activate: function() {
			BX.showWait();

			return this.query().then(
				$.proxy(this.activateEnd, this),
				$.proxy(this.activateStop, this)
			);
		},

		query: function() {
			return $.ajax({
				url: this.options.url,
				method: 'POST',
				dataType: 'json',
				data: {
					setup: this.options.setup,
					path: this.options.path,
					payload: this.options.payload,
				},
			});
		},

		activateStop: function(xhr, reason) {
			alert(this.getLang('FAIL', { 'MESSAGE': reason }));
			BX.closeWait();
		},

		activateEnd: function(response) {
			if (response && response.status === 'ok') {
				alert(this.getLang('SUCCESS'));
			} else {
				alert(this.getLang('FAIL', { 'MESSAGE': response ? response.message : '' }));
			}

			BX.closeWait();
		},

	}, {
		dataName: 'uiButtonTradingAction',
	});

})(BX, jQuery, window);