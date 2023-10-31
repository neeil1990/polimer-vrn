(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var OrderView = BX.namespace('YandexMarket.OrderView');

	var constructor = OrderView.Shipment = Reference.Complex.extend({

		defaults: {
			id: null,
			childElement: '.js-yamarket-shipment__child',
			inputElement: '.js-yamarket-shipment__input'
		},

		validate: function() {
			this.getBoxCollection().validate();
		},

		getCollection: function() {
			return this.getParentField();
		},

		getBoxCollection: function() {
			return this.getChildField('BOX');
		},

		getId: function() {
			return this.options.id;
		},

		getBoxCount: function() {
			return this.getBoxCollection().getActiveItems().length;
		},

		getBoxOffset: function() {
			var collection = this.getCollection();
			var siblingShipments = collection.getActiveItems();
			var siblingShipmentElement;
			var siblingShipment;
			var i;
			var result = 0;

			for (i = 0; i < siblingShipments.length; i++) {
				siblingShipmentElement = siblingShipments[i];

				if (this.el === siblingShipmentElement) {
					break;
				} else {
					siblingShipment = collection.getItemInstance(siblingShipmentElement);
					result += siblingShipment.getBoxCount();
				}
			}

			return result;
		},

		toggleSizes: function(dir) {
			const field = this.getChildField('BOX');

			field.toggleSizes(dir);
		},

	}, {
		dataName: 'orderViewShipment',
		pluginName: 'YandexMarket.OrderView.Shipment',
	});

})(BX, jQuery, window);