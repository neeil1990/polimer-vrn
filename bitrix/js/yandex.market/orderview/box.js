(function(BX, $, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var OrderView = BX.namespace('YandexMarket.OrderView');

	var constructor = OrderView.Box = Reference.Complex.extend({

		defaults: {
			titleElement: '.js-yamarket-box__title',
			numberElement: '.js-yamarket-box__number',
			propertyElement: '.js-yamarket-box__property',
			propertyValueElement: '.js-yamarket-box__property-value',
			inputElement: '.js-yamarket-box__input',
			childElement: '.js-yamarket-box__child',
			deleteElement: '.js-yamarket-box__delete',
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
			this.handleDeleteClick(true);
		},

		unbind: function() {
			this.handleDeleteClick(false);
		},

		handleDeleteClick: function(dir) {
			var deleteButton = this.getElement('delete');

			deleteButton[dir ? 'on' : 'off']('click', $.proxy(this.onDeleteClick, this));
		},

		onDeleteClick: function(evt) {
			var parentField = this.getParentField();

			if (parentField != null) {
				parentField.deleteItem(this.$el);
			}

			evt.preventDefault();
		},

		validate: function() {
			var sizes = this.getSizes();

			if (sizes && !sizes.$el.hasClass('is--disabled')) {
				sizes.validate();
			}
		},

		getTitle: function() {
			var title = this.getElement('title');

			return title && title.text().replace(/\s+/g, ' ').trim();
		},

		clear: function() {
			this.callParent('clear', constructor);
			this.clearProperties();
		},

		clearProperties: function() {
			this.getElement('property').addClass('is--hidden');
			this.getElement('propertyValue').text('');
		},

		setIndex: function(index) {
			this.callParent('setIndex', [index], constructor);
			this.updateNumber(index);
		},

		refreshNumber: function() {
			var index = this.getIndex();
			this.updateNumber(index);
		},

		updateNumber: function(index) {
			var boxCollection = this.getCollection();
			var shipment = boxCollection && boxCollection.getShipment();
			var orderId;
			var shipmentOffset;
			var boxNumber;
			var element = this.getElement('number');

			if (shipment) {
				orderId = shipment.getCollection().getOrder().getId();
				shipmentOffset = shipment.getBoxOffset();
				boxNumber = shipmentOffset + index + 1;

				element.html('&numero;' + boxNumber);

				this.setPartiallyValue({
					FULFILMENT_ID: orderId + '-' + boxNumber
				});
			}
		},

		toggleSizes: function(dir) {
			const field = this.getSizes();

			field.$el.toggleClass('is--disabled', !dir);
		},

		setPartiallyValue: function(values) {
			var name;
			var input;
			var value;

			for (name in values) {
				if (!values.hasOwnProperty(name)) { continue; }

				value = values[name];
				input = this.getInput(name);

				input && input.val(value || '');
			}
		},

		getSizes: function() {
			return this.getChildField('DIMENSIONS');
		},

		getItemCollection: function() {
			return this.getChildField('ITEM');
		},

		getCollection: function() {
			return this.getParentField();
		}

	}, {
		dataName: 'orderViewBox',
		pluginName: 'YandexMarket.OrderView.Box',
	});

})(BX, jQuery, window);