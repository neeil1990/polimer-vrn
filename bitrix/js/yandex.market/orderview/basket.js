(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const FieldReference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.Basket = FieldReference.Collection.extend({

		defaults: {
			itemElement: '.js-yamarket-basket-item'
		},

		validate: function() {
			this.callItemList((basketItem) => {
				try {
					basketItem.validate()
				} catch (e) {
					const title = basketItem.getTitle();
					const message = (title ? title + ': ' : '') + e.message;

					throw new Error(message);
				}
			});
		},

		commit: function() {
			const order = this.getParentField();
			let hasChanges = false;

			this.callItemList((basketItem) => {
				let count = basketItem.getCount();

				if (count <= 0 || basketItem.needDelete()) {
					hasChanges = true;
					this.deleteItem(basketItem.$el);
				} else if (count !== basketItem.getInitialCount()) {
					hasChanges = true;
					basketItem.setInitialCount(count);
				}
			});

			if (hasChanges && order) {
				order.refresh();
			}
		},

		getCountChanges: function() {
			const result = [];

			this.callItemList((basketItem) => {
				let diff = basketItem.getCountDiff();

				if (diff <= 0) { return; }

				result.push({
					name: basketItem.getTitle(),
					diff: diff,
				});
			});

			return result;
		},

		getItemPlugin: function() {
			return OrderView.BasketItem;
		}

	}, {
		dataName: 'orderViewBasket',
		pluginName: 'YandexMarket.OrderView.Basket',
	});

})(BX, jQuery, window);