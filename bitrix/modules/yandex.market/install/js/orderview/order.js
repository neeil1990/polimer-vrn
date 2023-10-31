(function(BX, $, window) {

	const FieldReference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.Order = FieldReference.Complex.extend({

		defaults: {
			childElement: '.js-yamarket-order__field',
			inputElement: '.js-yamarket-order__input',
			areaElement: '.js-yamarket-order__area',
			refreshUrl: null,
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
			this.handleActivityEnd(true);
		},

		unbind: function() {
			this.handleActivityEnd(false);
		},

		handleActivityEnd: function(dir) {
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](this.el, 'yamarketActivitySubmitEnd', BX.proxy(this.onActivityEnd, this));
		},

		onActivityEnd: function() {
			this.refresh();
		},

		getId: function() {
			const input = this.getInput('ID');

			return input && input.val();
		},

		confirm: function() {
			const basket = this.getChildField('BASKET');
			const basketConfirm = this.getChildField('BASKET_CONFIRM');

			if (!basket || !basketConfirm) { return; }

			return basketConfirm.confirm(basket);
		},

		validate: function() {
			this.callChildList('validate');
		},

		commit: function() {
			const basket = this.getChildField('BASKET');

			basket && basket.commit();
		},

		refresh: function() {
			$.ajax({ url: this.options.refreshUrl })
				.then($.proxy(this.updateArea, this));
		},

		updateArea: function(html) {
			const contents = $(html);
			const newAreas = this.getElement('area', contents);
			const newMap = this.mapAreas(newAreas);
			const existsAreas = this.getElement('area');
			const existsMap = this.mapAreas(existsAreas);

			for (const type in newMap) {
				if (!newMap.hasOwnProperty(type)) { continue; }
				if (!existsMap.hasOwnProperty(type)) { continue; }

				const newArea = newMap[type];
				const existsArea = existsMap[type];

				existsArea.replaceWith(newArea);

				BX.onCustomEvent(newArea[0], 'onYaMarketContentUpdate', [
					{ target: newArea[0] }
				]);
			}
		},

		mapAreas: function(areas) {
			const result = {};

			for (let index = 0; index < areas.length; ++index) {
				const area = areas.eq(index);
				const type = area.data('type');

				if (!type) { continue; }

				result[type] = area;
			}

			return result;
		}


	}, {
		dataName: 'orderViewOrder',
		pluginName: 'YandexMarket.OrderView.Order',
	});

})(BX, jQuery, window);