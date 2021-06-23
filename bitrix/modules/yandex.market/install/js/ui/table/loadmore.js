(function(BX, $, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var UiTable = BX.namespace('YandexMarket.Ui.Table');

	UiTable.LoadMore = constructor = Plugin.Base.extend({

		defaults: {
			url: null,
			loaderTemplate: '<span class="yamarket-loader-dots"><span class="yamarket-loader-dots__content">...</span></span>',
		},

		initVars: function() {
			this.callParent('initVars', constructor);
			this._isLoading = false;
			this._loader = null;
		},

		activate: function() {
			if (this._isLoading) { return; }

			this._isLoading = true;

			this.showLoader();
			this.query(this.loadEnd.bind(this), this.loadStop.bind(this));
		},

		query: function(resolve, reject) {
			BX.ajax({
				url: this.options.url,
				dataType: 'html',
				onsuccess: resolve,
				onfailure: reject
			});
		},

		loadStop: function(reason, message) {
			this._isLoading = false;
			this.hideLoader();
			this.showMessage(reason + ': ' + message);
		},

		loadEnd: function(html) {
			this._isLoading = false;
			this.hideLoader();
			this.insertPage(html);
		},

		insertPage: function(html) {
			var parent = this.el.parentNode;

			this.el.insertAdjacentHTML('afterEnd', html);
			parent.removeChild(this.el);

			Plugin.manager.initializeContext(parent);

			this.destroy();
		},

		showMessage: function(message) {
			alert(message);
		},

		showLoader: function() {
			if (this._loader != null) { return; }

			this._loader = this.el.insertAdjacentHTML('beforeEnd', this.options.loaderTemplate);
		},

		hideLoader: function() {
			if (this._loader == null) { return; }

			this._loader.parentElement.removeChild(this._loader);
			this._loader = null;
		},

	}, {
		dataName: 'uiTableLoadMore'
	});

})(BX, jQuery, window);
