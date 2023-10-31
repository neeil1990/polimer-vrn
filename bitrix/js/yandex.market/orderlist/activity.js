(function(BX, window) {

	const Ui = BX.namespace('YandexMarket.Ui');
	const OrderList = BX.namespace('YandexMarket.OrderList');

	const constructor = OrderList.Activity = OrderList.DialogAction.extend({

		action: function(type, orderIds, adminList) {
			const item = this.getItem(type);
			const behavior = item['BEHAVIOR'];

			if (behavior === 'command') {
				this.executeCommand(type, orderIds, adminList);
			} else if (behavior === 'form') {
				this.openForm(type, orderIds, adminList);
			} else if (behavior === 'view') {
				this.openView(type, orderIds, adminList);
			} else {
				this.showError('unknown activity behavior type');
			}
		},

		groupAction(type, adminList, dropdownName) {
			try {
				const targetType = this.resolveGroupType(type, adminList, dropdownName);
				const orderIds = this.selectedRows(adminList);

				this.action(targetType, orderIds, adminList);
			} catch (error) {
				this.showError(error.message);
			}
		},

		resolveGroupType(type, adminList, dropdownName) {
			if (dropdownName == null) { return type; }

			const values = this.actionValues(adminList);

			if (values[dropdownName] == null) {
				throw new Error(this.getLang('ACTIVITY_CHOOSE_DROPDOWN'));
			}

			return values[dropdownName];
		},

		openView: function(type, orderIds) {
			this.openDialog(type, orderIds, {
				buttons: [ BX.CDialog.btnClose ],
			});
		},

		openForm: function(type, orderIds) {
			this.openDialog(type, orderIds);
		},

		openDialog: function(type, orderIds, options) {
			const item = this.getItem(type);
			const url = this.buildUrl(item, orderIds);
			const form = this.createForm(url, item, options);

			form.activate();
		},

		createForm: function(url, item, options) {
			return new Ui.ModalForm(this.$el, $.extend({
				title: item.DIALOG_TITLE || item.TITLE,
				saveTitle: this.getLang('ACTIVITY_SUBMIT'),
				url: url,
			}, options));
		},

		executeCommand: function(type, orderIds, adminList) {
			const item = this.getItem(type);
			const url = this.buildUrl(item, orderIds);

			this.showLoading(adminList);

			this.sendCommand(url)
				.then((response) => this.parseCommandResponse(response))
				.then(() => this.reloadGrid(adminList))
				.catch((error) => {
					this.hideLoading(adminList);
					this.showError(adminList, error);
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

	}, {
		dataName: 'orderListActivity',
		pluginName: 'YandexMarket.OrderList.Activity',
	});

})(BX, window);