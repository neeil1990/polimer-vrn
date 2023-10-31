(function(BX, $, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var OrderView = BX.namespace('YandexMarket.OrderView');

	var constructor = OrderView.ShipmentCollection = Reference.Collection.extend({

		defaults: {
			itemElement: '.js-yamarket-shipment'
		},

		validate: function() {
			this.callItemList('validate');
		},

		getItemById: function(id) {
			var result;

			this.callItemList(function(instance) {
				if (instance.getId() == id) {
					result = instance;
				}
			});

			return result;
		},

		getItemPlugin: function() {
			return OrderView.Shipment;
		},

		refreshBoxNumber: function(offsetShipment) {
			var isOffsetFound = false;

			this.callItemList(function(instance) {
				if (isOffsetFound) {
					instance.getBoxCollection().refreshBoxNumber();
				} else if (instance === offsetShipment) {
					isOffsetFound = true;
				}
			});
		},

		toggleSizes: function(dir) {
			this.callItemList('toggleSizes', [dir]);
		},

		getOrder: function() {
			return this.getParentField();
		},

	}, {
		dataName: 'orderViewShipmentCollection',
		pluginName: 'YandexMarket.OrderView.ShipmentCollection',
	});

})(BX, jQuery, window);