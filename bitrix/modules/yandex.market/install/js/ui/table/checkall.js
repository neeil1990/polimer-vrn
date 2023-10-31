(function(BX, $, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var UiTable = BX.namespace('YandexMarket.Ui.Table');

	UiTable.CheckAll = constructor = Plugin.Base.extend({

		defaults: {
			tableElement: null,
			inputElement: 'input[type="checkbox"]'
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.bind();
		},

		destroy: function() {
			this.unbind();
			this.callParent('destroy', constructor);
		},

		bind: function() {
			this.handleChange(true);
		},

		unbind: function() {
			this.handleChange(false);
		},

		handleChange: function(dir) {
			this.$el[dir ? 'on' : 'off']('change', $.proxy(this.onChange, this));
		},

		onChange: function() {
			this.toggle();
		},

		isActive: function() {
			return !!this.el.checked;
		},

		toggle: function(dir) {
			var table = this.getTable();
			var inputList = this.getElement('input', table);
			var input;
			var inputIndex;

			if (dir == null) { dir = this.isActive(); }

			for (inputIndex = 0; inputIndex < inputList.length; inputIndex++) {
				input = inputList[inputIndex];
				input.checked = !!dir;
			}
		},

		getTable: function() {
			var result;

			if (this.getElementSelector('table') != null) {
				result = this.getElement('table');
			} else {
				result = this.$el.closest('table');
			}

			return result;
		}

	}, {
		dataName: 'uiTableCheckAll'
	});

})(BX, jQuery, window);