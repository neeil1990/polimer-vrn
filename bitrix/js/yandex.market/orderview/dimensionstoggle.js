(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.DimensionsToggle = Plugin.Base.extend({

		defaults: {
			shipmentElement: null,
		},

		initialize: function() {
			this.bind();
		},

		destroy: function() {
			this.unbind();
		},

		bind: function() {
			this.handleChange(true);
		},

		unbind: function() {
			this.handleChange(false);
		},

		handleChange: function(dir) {
			this.$el[dir ? 'on' : 'off']('change', $.proxy(this.onChange, this));
		},

		onChange: function(evt) {
			const isChecked = evt.target.checked;

			this.toggleShipment(isChecked);
		},

		toggleShipment: function(dir) {
			const shipmentElement = this.getElement('shipment');
			const shipment = Plugin.manager.getInstance(shipmentElement);

			shipment.toggleSizes(dir);
		},

	}, {
		dataName: 'orderViewDimensionsToggle',
		pluginName: 'YandexMarket.OrderView.DimensionsToggle',
	});

})(BX, jQuery, window);