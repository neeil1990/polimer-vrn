(function(BX, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var SkuField = BX.namespace('YandexMarket.Field.SkuField');

	var constructor = SkuField.Table = Reference.Collection.extend({

		defaults: {
			itemElement: '.js-sku-field-row',
			addElement: '.js-sku-field__add',

			persistent: true,
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
			this.handleAddClick(true);
		},

		unbind: function() {
			this.handleAddClick(false);
		},

		handleAddClick: function(dir) {
			var addButton = this.getElement('add');

			addButton[dir ? 'on' : 'off']('click', $.proxy(this.onAddClick, this));
		},

		onAddClick: function(evt) {
			this.addItem();
			evt.preventDefault();
		},

		getItemPlugin: function() {
			return SkuField.Row;
		}

	}, {
		dataName: 'FieldSkuFieldTable',
		pluginName: 'YandexMarket.Field.SkuField.Table'
	});

})(BX, window);