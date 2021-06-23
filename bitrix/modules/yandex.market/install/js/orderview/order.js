(function(BX, $, window) {

	const FieldReference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.Order = FieldReference.Complex.extend({

		defaults: {
			childElement: '.js-yamarket-order__field',
			inputElement: '.js-yamarket-order__input',
		},

		getId: function() {
			const input = this.getInput('ID');

			return input && input.val();
		},

		validate: function() {
			this.callChildList('validate');
		},

	}, {
		dataName: 'orderViewOrder',
		pluginName: 'YandexMarket.OrderView.Order',
	});

})(BX, jQuery, window);