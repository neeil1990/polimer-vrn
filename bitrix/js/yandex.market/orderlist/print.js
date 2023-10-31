(function(BX, window) {

	const YandexMarket = BX.namespace('YandexMarket');
	const OrderList = BX.namespace('YandexMarket.OrderList');

	const constructor = OrderList.Print = OrderList.DialogAction.extend({

		openDialog: function(type, orderIds) {
			const item = this.getItem(type);
			const url = this.buildUrl(item, orderIds);
			const dialog = this.createDialog(url, item);

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

	}, {
		dataName: 'orderListPrint',
		pluginName: 'YandexMarket.OrderList.Print',
	});

})(BX, window);