(function($, BX, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	Input.TradingOutletFetcher = constructor = Plugin.Base.extend({

		initVars: function() {
			this._enum = null;
			this._sign = null;
		},

		load: function(url, formData, sign) {
			return this.makeLoadQuery(url, formData)
				.then($.proxy(this.loadEnd, this), $.proxy(this.loadStop, this))
				.then($.proxy(this.storeEnum, this, sign));
		},

		makeLoadQuery: function(url, formData) {
			return $.ajax({
				url: url,
				type: 'POST',
				data: formData,
				dataType: 'json'
			});
		},

		loadStop: function(xhr, message) {
			return (new $.Deferred()).reject(message);
		},

		loadEnd: function(response) {
			return (response && response.status === 'ok')
				? response.enum
				: (new $.Deferred()).reject(response ? response.message : 'Unknown error');
		},

		storeEnum: function(sign, values) {
			this._sign = sign;
			this._enum = values;

			return values;
		},

		hasEnum: function() {
			return this._enum != null;
		},

		getEnum: function() {
			return this._enum;
		},

		getSign: function() {
			return this._sign;
		}

	}, {
		dataName: 'uiInputTradingOutletFetcher'
	});

})(jQuery, BX, window);