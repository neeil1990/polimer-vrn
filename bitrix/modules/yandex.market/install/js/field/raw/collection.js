(function(BX, $, window) {

	const FieldRaw = BX.namespace('YandexMarket.Field.Raw');
	const FieldReference = BX.namespace('YandexMarket.Field.Reference');

	const constructor = FieldRaw.Collection = FieldReference.Collection.extend({

		defaults: {
			itemElement: '.js-input-collection__item',
			itemDeleteElement: '.js-input-collection__delete',
			addButtonElement: '.js-input-collection__add',
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
			this.handleAddButtonClick(true);
			this.handleItemDeleteClick(true);
		},

		unbind: function() {
			this.handleAddButtonClick(false);
			this.handleItemDeleteClick(false);
		},

		handleAddButtonClick: function(dir) {
			const addButton = this.getElement('addButton');

			addButton[dir ? 'on' : 'off']('click', $.proxy(this.onAddButtonClick, this));
		},

		handleItemDeleteClick: function(dir) {
			const itemDeleteSelector = this.getElementSelector('itemDelete');

			this.$el[dir ? 'on' : 'off']('click', itemDeleteSelector, $.proxy(this.onItemDeleteClick, this));
		},

		onAddButtonClick: function(evt) {
			const instance = this.addItem();

			instance.initEdit();

			evt.preventDefault();
		},

		onItemDeleteClick: function(evt) {
			const deleteButton = $(evt.target);
			const item = this.getElement('item', deleteButton, 'closest');

			this.deleteItem(item);

			evt.preventDefault();
		},

		getItemPlugin: function() { 
			return FieldRaw.Item;
		},

	}, {
		dataName: 'FieldRawCollection',
	});

})(BX, jQuery, window);