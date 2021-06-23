(function(BX, window) {

	var YandexMarket = BX.namespace('YandexMarket');
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var OrderList = BX.namespace('YandexMarket.OrderList');

	var constructor = OrderList.Print = Plugin.Base.extend({

		defaults: {
			url: null,
			width: null,
			height: null,
			minWidth: 400,
			minHeight: 300,
			maxWidth: 600,
			maxHeight: 600,
			items: [],
			lang: {},
			langPrefix: 'YANDEX_MARKET_UI_TRADING_ORDER_LIST_PRINT_'
		},

		openGroupDialog: function(type, adminList) {
			var orderIds = this.getAdminListOrderIds(adminList);

			if (orderIds.length > 0) {
				this.openDialog(type, orderIds);
			} else {
				alert(this.getLang('REQUIRE_SELECT_ORDERS'));
			}
		},

		getAdminListOrderIds: function(adminList) {
			var result;

			if (BX.adminUiList != null && adminList instanceof BX.adminUiList) {
				result = this.getAdminGridSelectedRows(adminList);
			} else {
				result = this.getAdminListSelectedCheckboxes(adminList);
			}

			return result;
		},

		getAdminGridSelectedRows: function(adminList) {
			var gridInstance = BX.Main.gridManager.getById(adminList.gridId).instance;

			return gridInstance.getRows().getSelectedIds();
		},

		getAdminListSelectedCheckboxes: function(adminList) {
			var checkboxes = adminList.CHECKBOX;
			var checkbox;
			var checkboxIndex;
			var result = [];

			for (checkboxIndex = 0; checkboxIndex < checkboxes.length; checkboxIndex++) {
				checkbox = checkboxes[checkboxIndex];

				if (checkbox.checked && !checkbox.disabled) {
					result.push(checkbox.value);
				}
			}

			return result;
		},

		openDialog: function(type, orderIds) {
			var item = this.getItem(type);
			var url = this.buildUrl(item, orderIds);
			var dialog = this.createDialog(url, item);

			dialog.Show();
		},

		createDialog: function(url, item) {
			return new YandexMarket.PrintDialog({
				title: item.DIALOG_TITLE || item.TITLE,
				content_url: url,
				width: item.WIDTH || this.options.width || this.options.minWidth,
				height: item.HEIGHT || this.options.height || this.options.minHeight,
				min_width: this.options.minWidth,
				min_height: this.options.minHeight,
				max_width: this.options.maxWidth,
				max_height: this.options.maxHeight,
				buttons: [
					YandexMarket.PrintDialog.btnSave,
					YandexMarket.PrintDialog.btnCancel
				]
			});
		},

		buildUrl: function(item, orderIds) {
			var result = this.options.url;
			var orderId;
			var orderIndex;

			result +=
				(result.indexOf('?') === -1 ? '?' : '&')
				+ 'type=' + item.TYPE;

			if (orderIds == null) {
				// nothing
			} else if (Array.isArray(orderIds)) {
				for (orderIndex = 0; orderIndex < orderIds.length; orderIndex++) {
					orderId = orderIds[orderIndex];

					result += '&id[]=' + encodeURIComponent(orderId);
				}
			} else {
				result += '&id=' + encodeURIComponent(orderIds);
			}

			return result;
		},

		getItem: function(type) {
			var result;
			var items = this.options.items;
			var itemIndex;
			var item;

			for (itemIndex = 0; itemIndex < items.length; itemIndex++) {
				item = items[itemIndex];

				if (item.TYPE === type) {
					result = item;
					break;
				}
			}

			return result;
		},

	}, {
		dataName: 'orderListPrint',
		pluginName: 'YandexMarket.OrderList.Print',
	});

})(BX, window);