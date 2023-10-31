(function(BX, window) {

	const Reference = BX.namespace('YandexMarket.Field.Reference');
	const Fieldset = BX.namespace('YandexMarket.Field.Fieldset');

	const constructor = Fieldset.Collection = Reference.Collection.extend({

		defaults: {
			elementDefault: '.js-fieldset',
			elementNamespace: null,

			headerElement: '.js-fieldset-collection__header',
			itemElement: '.js-fieldset-collection__item',
			itemAddElement: '.js-fieldset-collection__item-add',
			itemDeleteElement: '.js-fieldset-collection__item-delete',

			lang: {},
			langPrefix: 'YANDEX_MARKET_FIELD_FIELDSET_'
		},

		setOptions: function(options) {
			this.callParent('setOptions', [options], constructor);

			if (
				this.options.elementNamespace != null
				&& this.options.elementNamespace !== this.options.elementDefault
			) {
				this.overrideElementOptions(
					this.options.elementDefault,
					this.options.elementNamespace
				);
			}
		},

		overrideElementOptions: function(from, to) {
			var key;

			for (key in this.options) {
				if (!this.options.hasOwnProperty(key)) { continue; }
				if (key.indexOf('Element') === -1) { continue; }
				if (this.options[key] == null) { continue; }

				this.options[key] = this.options[key].replace(from, to);
			}
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
			this.handleItemAddClick(true);
			this.handleItemDeleteClick(true);
		},

		unbind: function() {
			this.handleItemAddClick(false);
			this.handleItemDeleteClick(false);
		},

		handleItemAddClick: function(dir) {
			const addButton = this.getItemAddButton();

			addButton[dir ? 'on' : 'off']('click', $.proxy(this.onItemAddClick, this));
		},

		handleItemDeleteClick: function(dir) {
			const deleteSelector = this.getElementSelector('itemDelete');

			this.$el[dir ? 'on' : 'off']('click', deleteSelector, $.proxy(this.onItemDeleteClick, this));
		},

		handleModalSave: function(childInstance, dir) {
			childInstance.$el[dir ? 'on' : 'off']('uiModalSave', $.proxy(this.onModalSave, this));
		},

		handleModalClose: function(childInstance, dir) {
			childInstance.$el[dir ? 'on' : 'off']('uiModalClose', $.proxy(this.onModalClose, this));
		},

		onItemAddClick: function(evt) {
			const instance = this.addItem();

			instance.initEdit();
			instance.applyDefaults();

			evt.preventDefault();
		},

		onItemDeleteClick: function(evt) {
			const deleteButton = $(evt.target);
			const item = this.getElement('item', deleteButton, 'closest');

			this.deleteItem(item);

			evt.preventDefault();
		},

		getItemAddButton: function() {
			return this.getElement('itemAdd', this.$el, 'next');
		},

		getItemPlugin: function() {
			return Fieldset.Row;
		},

		addItem: function(source, context, method, isCopy) {
			const result = this.callParent('addItem', [source, context, method, isCopy], constructor);

			this.refreshEmptyState(true);

			return result;
		},

		deleteItem: function(item, silent) {
			this.callParent('deleteItem', [item, silent], constructor);
			this.refreshEmptyState();
		},

		refreshEmptyState: function(state) {
			if (state == null) { state = !this.isEmpty(); }

			this.getElement('header').toggleClass('is--hidden', !state);
		},

	}, {
		dataName: 'FieldFieldsetCollection',
		pluginName: 'YandexMarket.Field.Fieldset.Collection'
	});

})(BX, window);