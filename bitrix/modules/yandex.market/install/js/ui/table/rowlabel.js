(function(BX, $, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var UiTable = BX.namespace('YandexMarket.Ui.Table');

	UiTable.RowLabel = constructor = Plugin.Base.extend({

		defaults: {
			inputElement: 'input[type="checkbox"]'
		},

		isActive: function(input) {
			if (input == null) { input = this.getElement('input'); }

			return !!input.prop('checked');
		},

		activate: function(dir) {
			var input = this.getElement('input');

			if (input != null) {
				if (dir == null) { dir = !this.isActive(input); }

				input.prop('checked', dir);
			}
		}

	}, {
		dataName: 'uiTableRowLabel'
	});

})(BX, jQuery, window);