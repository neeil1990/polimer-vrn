(function(BX, $, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var OrderView = BX.namespace('YandexMarket.OrderView');

	var constructor = OrderView.BoxCollection = Reference.Collection.extend({

		defaults: {
			itemElement: '.js-yamarket-box',
			itemAddElement: '.js-yamarket-box__add',
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
			this.handleItemAddClick(true);
		},

		unbind: function() {
			this.handleItemAddClick(false);
		},

		handleItemAddClick: function(dir) {
			var addButton = this.getElement('itemAdd');

			addButton[dir ? 'on' : 'off']('click', $.proxy(this.onItemAddClick, this));
		},

		onItemAddClick: function(evt) {
			this.addItem();
			evt.preventDefault();
		},

		validate: function() {
			this.callItemList(function(box) {
				try {
					box.validate()
				} catch (e) {
					var title = box.getTitle();
					var message = (title ? title + ': ' : '') + e.message;

					throw new Error(message);
				}
			});
		},

		addItem: function(source, context, method, isCopy) {
			var result = this.callParent('addItem', [source, context, method, isCopy], constructor);

			this.refreshSiblingsBoxNumber();

			return result;
		},

		deleteItem: function(item, silent) {
			this.callParent('deleteItem', [item, silent], constructor);
			this.refreshSiblingsBoxNumber();
		},

		refreshSiblingsBoxNumber: function() {
			var shipment = this.getShipment();

			shipment.getCollection().refreshBoxNumber(shipment);
		},

		refreshBoxNumber: function() {
			this.callItemList('refreshNumber');
		},

		toggleSizes: function(dir) {
			this.callItemList('toggleSizes', [dir]);
		},

		getShipment: function() {
			return this.getParentField();
		},

		getItemPlugin: function() {
			return OrderView.Box;
		}

	}, {
		dataName: 'orderViewBoxCollection',
		pluginName: 'YandexMarket.OrderView.BoxCollection',
	});

})(BX, jQuery, window);