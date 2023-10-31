(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	var constructor = Input.IncomingUrlTest = Plugin.Base.extend({

		defaults: {
			inputElement: 'input',
			url: null,
			site: null,
			modalWidth: 650,
			modalHeight: 500,

			lang: {},
			langPrefix: 'YANDEX_MARKET_UI_USER_FIELD_INCOMING_URL_'
		},

		activate: function() {
			var dialog = this.createDialog();

			dialog.Show();
		},

		createDialog: function() {
			return new BX.CAdminDialog({
				'title': this.getLang('MODAL_TITLE'),
				'draggable': true,
				'resizable': true,
				'buttons': [BX.CAdminDialog.btnClose],
				'width': this.options.modalWidth,
				'height': this.options.modalHeight,
				'content_url': this.options.url,
				'content_post': this.getDialogData(),
			});
		},

		getDialogData: function() {
			return {
				url: this.getTestingUrl(),
				site: this.options.site,
			};
		},

		getTestingUrl: function() {
			var input = this.getElement('input', this.$el, 'siblings');

			return input.val();
		},

	}, {
		dataName: 'uiInputIncomingUrlTest'
	});

})(BX, jQuery, window);