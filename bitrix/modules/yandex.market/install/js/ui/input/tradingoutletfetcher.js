(function($, BX, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	Input.TradingOutletFetcher = constructor = Plugin.Base.extend({

		initVars: function() {
			this._cache = {};
		},

		load: function(url, formData, sign) {
			return this.makeLoadQuery(url, formData)
				.then($.proxy(this.loadEnd, this), $.proxy(this.loadStop, this))
				.then($.proxy(this.store, this, sign));
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
				? response
				: (new $.Deferred()).reject(response ? response.message : 'Unknown error');
		},

		store: function(sign, values) {
			this._cache[sign] = values;

			return values;
		},

		has: function(sign) {
			return this._cache[sign] != null;
		},

		get: function(sign) {
			return this._cache[sign];
		},

	}, {
		dataName: 'uiInputTradingOutletFetcher'
	});

})(jQuery, BX, window);