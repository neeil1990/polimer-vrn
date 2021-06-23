(function(BX, $, window) {

	const FieldReference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.BasketItem = FieldReference.Complex.extend({

		defaults: {
			id: null,

			childElement: '.js-yamarket-basket-item__field',
			inputElement: '.js-yamarket-basket-item__data',
		},

		getTitle: function() {
			const name = this.getInput('NAME');

			return name && name.text();
		},

		validate: function() {
			const cis = this.getCis();

			cis && cis.validate();
		},

		getCis: function() {
			return this.getChildField('CIS');
		},

	}, {
		dataName: 'orderViewBasketItem',
		pluginName: 'YandexMarket.OrderView.BasketItem',
	});

})(BX, jQuery, window);