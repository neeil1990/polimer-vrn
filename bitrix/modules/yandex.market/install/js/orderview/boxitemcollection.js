(function(BX, $, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var OrderView = BX.namespace('YandexMarket.OrderView');

	var constructor = OrderView.BoxItemCollection = Reference.Collection.extend({

		defaults: {
			emptyElement: '.js-yamarket-box-item-collection__empty',
			itemElement: '.js-yamarket-box-item',
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

		getItemIdList: function() {
			var result = [];

			this.callItemList(function(boxItem) {
				var itemId = boxItem.getId();

				if (result.indexOf(itemId) === -1) {
					result.push(itemId);
				}
			});

			return result;
		},

		addItem: function(source, context, method, isCopy) {
			var result = this.callParent('addItem', [source, context, method, isCopy], constructor);

			this.refreshEmptyState(true);

			return result;
		},

		deleteItem: function(item, silent) {
			this.callParent('deleteItem', [item, silent], constructor);
			this.refreshEmptyState();
		},

		refreshEmptyState: function(state) {
			var element = this.getElement('empty');

			if (state == null) { state = !this.isEmpty(); }

			element.toggleClass('is--hidden', state);
		},

		getItemPlugin: function() {
			return OrderView.BoxItem;
		}

	}, {
		dataName: 'orderViewBoxItemCollection',
		pluginName: 'YandexMarket.OrderView.BoxItemCollection',
	});

})(BX, jQuery, window);