(function(BX, window) {

	var YandexMarket = BX.namespace('YandexMarket');

	// constructor

	YandexMarket.PrintDialog = function(arParams) {
		YandexMarket.PrintDialog.superclass.constructor.apply(this, arguments);
	};

	BX.extend(YandexMarket.PrintDialog, YandexMarket.Dialog);

	// buttons

	YandexMarket.PrintDialog.prototype.btnSave = YandexMarket.PrintDialog.btnSave = {
		title: BX.message('YANDEX_MARKET_PRINT_DIALOG_SUBMIT'),
		id: 'savebtn',
		name: 'savebtn',
		className: 'adm-btn-save yamarket-dialog-btn',
		action: function () {
			this.parentWindow.GetForm().submit();
			this.parentWindow.Close();
		}
	};

	YandexMarket.PrintDialog.btnCancel = YandexMarket.PrintDialog.superclass.btnCancel;
	YandexMarket.PrintDialog.btnClose = YandexMarket.PrintDialog.superclass.btnClose;

})(BX, window);