(function(BX, $, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var OrderView = BX.namespace('YandexMarket.OrderView');

	var constructor = OrderView.BoxItem = Reference.Base.extend({

		defaults: {
			id: null,
			inputElement: '.js-yamarket-box-item__data',
			deleteElement: '.js-yamarket-box-item__delete',
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
			this.handleDeleteClick(true);
			this.handleCountChange(true);
		},

		unbind: function() {
			this.handleDeleteClick(false);
			this.handleCountChange(false);
		},

		handleDeleteClick: function(dir) {
			var deleteButton = this.getElement('delete');

			deleteButton[dir ? 'on' : 'off']('click', $.proxy(this.onDeleteClick, this));
		},

		handleCountChange: function(dir) {
			var countInput = this.getInput('COUNT');

			countInput[dir ? 'on' : 'off']('change', $.proxy(this.onCountChange, this));
		},

		onDeleteClick: function(evt) {
			var parentField = this.getParentField();

			if (parentField != null) {
				parentField.deleteItem(this.$el);
			}

			evt.preventDefault();
		},

		onCountChange: function(evt) {
			BX.onCustomEvent(this.el, 'yamarket' + this.getStatic('dataName') + 'CountChange', [this]);
		},

		setValue: function(valueList) {
			var countInput = this.getInput('COUNT');

			this.callParent('setValue', [valueList], constructor);

			if (countInput) {
				countInput.attr('max', valueList['COUNT_TOTAL']);
			}
		},

		clear: function() {
			this.callParent('clear', constructor);
			this.options.id = null;
		},

		setId: function(id) {
			this.options.id = id;
		},

		getId: function() {
			return this.options.id;
		},

		getCount: function() {
			var countInput = this.getInput('COUNT');

			return countInput != null && parseFloat(countInput.val()) || 0;
		},

	}, {
		dataName: 'orderViewBoxItem',
		pluginName: 'YandexMarket.OrderView.BoxItem',
	});

})(BX, jQuery, window);