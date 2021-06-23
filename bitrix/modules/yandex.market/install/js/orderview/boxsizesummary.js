(function(BX, $, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var OrderView = BX.namespace('YandexMarket.OrderView');

	var constructor = OrderView.BoxSizeSummary = Reference.Summary.extend({

		defaults: {
			propertyElement: '.js-yamarket-box-size__property',
			propertyValueElement: '.js-yamarket-box-size__property-value',
			fieldElement: '.js-yamarket-box-size__field',
			editElement: '.js-yamarket-box-size__edit',
			modalElement: '.js-yamarket-box-size__modal',
			modalWidth: 500,
			modalHeight: 300,

			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BOX_SIZE_'
		},

		initVars: function() {
			this.callParent('initVars', constructor);
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
			this.handleEditClick(true);
		},

		unbind: function() {
			this.handleEditClick(false);
		},

		handleEditClick: function(dir) {
			var editButton = this.getElement('edit');

			editButton[dir ? 'on' : 'off']('click', $.proxy(this.onEditClick, this));
		},

		onEditClick: function(evt) {
			this.openEditModal();
			evt.preventDefault();
		},

		clear: function() {
			this.callField('clear');
			this.clearSummary();
		},

		clearSummary: function() {
			this.getElement('property').addClass('is--hidden');
			this.getElement('propertyValue').text('');
		},

		refreshSummary: function() {
			var valueList = this.getDisplayValue();
			var propertyValues = this.makePropertyValueList(valueList);
			var propertyValue;
			var propertyElements = this.getElement('property');
			var propertyIndex;
			var propertyElement;
			var propertyName;
			var propertyValueElement;

			for (propertyIndex = 0; propertyIndex < propertyElements.length; propertyIndex++) {
				propertyElement = propertyElements.eq(propertyIndex);
				propertyName = propertyElement.data('name');
				
				if (propertyName != null && propertyValues.hasOwnProperty(propertyName)) {
					propertyValue = propertyValues[propertyName];
					propertyValueElement = this.getElement('propertyValue', propertyElement);

					propertyValueElement.text(propertyValue.value);
					propertyElement.toggleClass('is--hidden', !!propertyValue.hidden);
				}
			}
		},

		makePropertyValueList: function(valueList) {
			return {
				'SIZE': this.makePropertyValueSize(valueList),
				'WEIGHT': this.makePropertyValueWeight(valueList)
			};
		},

		makePropertyValueSize: function(valueList) {
			var values = [
				parseFloat(valueList['WIDTH']),
				parseFloat(valueList['HEIGHT']),
				parseFloat(valueList['DEPTH']),
			];

			values = values.filter(function(value) { return !isNaN(value) });

			return {
				value: values.join('x'),
				hidden: values.length === 0
			};
		},

		makePropertyValueWeight: function(valueList) {
			var value = parseFloat(valueList['WEIGHT']);
			var isValid = !isNaN(value);

			return {
				value: isValid ? value : '',
				hidden: !isValid
			};
		},

		getFieldPlugin: function() {
			return OrderView.BoxSize;
		},

	}, {
		dataName: 'orderViewBoxSize',
		pluginName: 'YandexMarket.OrderView.BoxSize',
	});

})(BX, jQuery, window);