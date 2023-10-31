(function(BX, window) {

	const Ui = BX.namespace('YandexMarket.Ui');
	const Plugin = BX.namespace('YandexMarket.Plugin');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.Activity = Plugin.Base.extend({

		defaults: {
			url: null,
			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_',
		},

		openView: function(type, data) {
			this.openDialog(type, data, {
				buttons: [ BX.CDialog.btnClose ],
			});
		},

		openForm: function(type, data) {
			this.openDialog(type, data);
		},

		openDialog: function(type, data, options) {
			const url = this.buildUrl(type);
			const form = this.createForm(url, data, options);

			form.activate()
				.then(() => this.reloadTab(type));
		},

		createForm: function(url, data, options) {
			if (data == null) { data = {}; }

			return new Ui.ModalForm(this.$el, $.extend({
				title: data.DIALOG_TITLE || data.TITLE,
				saveTitle: this.getLang('ACTIVITY_SUBMIT'),
				url: url,
			}, options));
		},

		executeCommand: function(type) {
			const url = this.buildUrl(type);

			this.showLoading();

			this.sendCommand(url)
				.then((response) => this.parseCommandResponse(response))
				.then(() => this.reloadTab(type))
				.catch((error) => {
					this.hideLoading();
					this.showError(error);
				});
		},

		sendCommand: function(url) {
			return new Promise(function(resolve, reject) {
				BX.ajax({
					url: url,
					method: 'POST',
					data: {
						command: 'Y',
						sessid: BX.bitrix_sessid(),
					},
					dataType: 'json',
					onsuccess: resolve,
					onfailure: reject,
				});
			});
		},

		parseCommandResponse: function(response) {
			if (response == null || typeof response !== 'object') {
				throw new Error('ajax response missing');
			}

			if (response.status == null) {
				throw new Error('ajax response status missing');
			}

			if (response.status === 'error') {
				throw new Error(response.message);
			}
		},

		buildUrl: function(type) {
			let result = this.options.url;

			result +=
				(result.indexOf('?') === -1 ? '?' : '&')
				+ 'type=' + type;

			return result;
		},

		showLoading: function() {
			BX.showWait(this.el);
		},

		hideLoading: function() {
			BX.closeWait(this.el);
		},

		reloadTab: function(type) {
			BX.onCustomEvent(this.el, 'yamarketActivitySubmitEnd', [type]);
		},

		showError: function(error) {
			const message = error instanceof Error ? error.message : error;
			const SaleAdmin = BX.namespace('Sale.Admin');
			const Dialogs = BX.namespace('UI.Dialogs');

			if (SaleAdmin.OrderEditPage != null) {
				SaleAdmin.OrderEditPage.showDialog(message);
			} else if (Dialogs.MessageBox != null) {
				Dialogs.MessageBox.show({
					title: this.getLang('ACTIVITY_ERROR'),
					message: message,
					buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
				});
			} else {
				alert(message);
			}
		},

	}, {
		dataName: 'orderListActivity',
		pluginName: 'YandexMarket.OrderList.Activity',
	});

})(BX, window);