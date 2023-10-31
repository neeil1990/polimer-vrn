(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const OrderView = BX.namespace('YandexMarket.OrderView');
	const Utils = BX.namespace('YandexMarket.Utils');
	const Ui = BX.namespace('YandexMarket.Ui');

	const constructor = OrderView.BoxSizePackSelect = Plugin.Base.extend({

		defaults: {
			optionElement: 'option',
			optionTemplate: '<option value="#PRIMARY#">#NAME# (#WIDTH#x#HEIGHT#x#DEPTH#)</option>',

			saveElement: '.js-yamarket-box-pack__save',
			saveTemplate:
				'<span class="yamarket-#STATE#-icon"></span>' +
				' <span class="yamarket-transparent-btn__reveal">#TEXT#</span>',
			saveUrl: 'yamarket_trading_pack_edit.php',

			field: null,

			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BOX_PACK_'
		},

		initVars: function() {
			this.callParent('initVars', constructor);
			this._customSizes = null;
			this._isCustomSelected = false;
			this._isGlobalBoxUpdateDisabled = false;
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.bind();
			this.resolveCustomSelected();
		},

		destroy: function() {
			this.unbind();
			this.callParent('destroy', constructor);
		},

		bind: function() {
			this.handleChange(true);
			this.handleSizeChange(true);
			this.handleSaveClick(true);
			this.handleOptionUpdate(true);
		},

		unbind: function() {
			this.handleChange(true);
			this.handleSizeChange(false);
			this.handleSaveClick(false);
			this.handleOptionUpdate(false);
		},

		handleChange: function(dir) {
			this.$el[dir ? 'on' : 'off']('change', $.proxy(this.onChange, this));
		},

		handleSizeChange: function(dir) {
			const inputs = this.getSizeInputs();

			for (let size in inputs) {
				if (!inputs.hasOwnProperty(size)) { continue; }

				inputs[size][dir ? 'on' : 'off']('change', $.proxy(this.onSizeChange, this));
			}
		},

		handleSaveClick: function(dir) {
			const button = this.getSaveButton();

			button[dir ? 'on' : 'off']('click', $.proxy(this.onSaveClick, this));
		},

		handleOptionUpdate: function(dir) {
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('yamarketBoxSizeUpdate', BX.proxy(this.onBoxSizeUpdate, this));
		},

		onChange: function() {
			const option = this.getSelectedOption();
			const sizes = this.getOptionSizes(option);

			if (option.data('custom')) {
				this._isCustomSelected = true;
				this.restoreCustomSizes();
				this.syncSaveButton();
				return;
			}

			if (this._isCustomSelected) {
				this._isCustomSelected = false;
				this.fillCustomSizes();
				this.syncSaveButton();
			}

			this.update(sizes);
		},

		onSizeChange: function() {
			const storedState = this._isCustomSelected;

			this.sync();

			if (storedState !== this._isCustomSelected) {
				this.syncSaveButton();
			}
		},

		onSaveClick: function() {
			this.save();
		},

		onBoxSizeUpdate: function(evt) {
			if (this._isGlobalBoxUpdateDisabled) { return; }

			const action = evt.action;
			const primary = evt.primary;
			const data = evt.data;

			if (action === 'delete') {
				this.deleteOption(primary);
			} else if (action === 'save') {
				this.renderOption(primary, data);
			}
		},

		resolveCustomSelected: function() {
			const option = this.getSelectedOption();

			this._isCustomSelected = !!option.data('custom');
		},

		fillCustomSizes: function() {
			this._customSizes = this.getFilledValues();
		},

		restoreCustomSizes: function() {
			if (this._customSizes == null) { return; }

			this.update(this._customSizes);
		},

		sync: function() {
			const values = this.getFilledValues();
			const options = this.getElement('option');
			let hasMatched = false;
			let customOption;

			for (let index = options.length - 1; index >= 0; --index) {
				let option = options.eq(index);

				if (option.data('custom')) {
					customOption = option;
					continue;
				}

				let optionSizes = this.getOptionSizes(option);
				let isMatched = this.isMatchSizes(optionSizes, values, [ 'WEIGHT' ]);

				option.prop('selected', isMatched);

				if (isMatched) {
					hasMatched = true;
				}
			}

			if (customOption != null) {
				customOption.prop('selected', !hasMatched);
				this._isCustomSelected = !hasMatched;
			}
		},

		update: function(sizes) {
			const field = this.getField();
			const values = $.extend({}, field.getValue(), sizes);

			field.setValue(values);
		},

		save: function() {
			const option = this.getSelectedOption();
			const form = new Ui.ModalForm(this.$el, {
				title: this.getSaveTitle(option),
				url: this.getSaveUrl(option),
				data: this.getSaveData(option),
			});

			form.activate()
				.then((response) => this.saveEnd(response));
		},

		saveEnd: function(response) {
			const action = response.action;

			if (action === 'delete') {
				this.deleteOption(response.primary);
			} else if (action === 'save') {
				const newOption = this.renderOption(response.primary, response.data);

				this._isCustomSelected = false;
				newOption.prop('selected', true);

				this.update(this.getOptionSizes(newOption));
				this.syncSaveButton();
			}

			this._isGlobalBoxUpdateDisabled = true;
			BX.onCustomEvent('yamarketBoxSizeUpdate', [response]);
			this._isGlobalBoxUpdateDisabled = false;
		},

		getSaveTitle: function(option) {
			return option.data('custom')
				? this.getLang('MODAL_ADD')
				: this.getLang('MODAL_EDIT');
		},

		getSaveUrl: function(option) {
			let result = this.options.saveUrl;

			if (!option.data('custom')) {
				result +=
					(result.indexOf('?') ? '?' : '&')
					+ 'id=' + option.val();
			}

			return result;
		},

		getSaveData: function(option) {
			return option.data('custom')
				? $.extend(this.getFilledValues(), {
					sessid: BX.bitrix_sessid(),
					stepAction: 0,
				})
				: null;
		},

		isMatchSizes: function(first, second, ignore) {
			let result = true;

			for (let size in first) {
				if (!first.hasOwnProperty(size)) { continue; }
				if (ignore != null && ignore.indexOf(size) !== -1) { continue; }

				let firstValue = parseInt(first[size]);
				let secondValue = parseInt(second[size]);

				if (firstValue !== secondValue) {
					result = false;
					break;
				}
			}

			return result;
		},

		getFilledValues: function() {
			const field = this.getField();
			const values = field.getValue();
			const result = {};

			for (let size of this.getSizes()) {
				result[size] = values[size];
			}

			return result;
		},

		getSizeInputs: function() {
			const field = this.getField();
			const result = {};

			for (let size of this.getSizes()) {
				let input = field.getInput(size);

				if (!input) { continue; }

				result[size] = input;
			}

			return result;
		},

		deleteOption: function(id) {
			const matchedOption = this.searchOption(id);

			if (matchedOption != null) {
				matchedOption.remove();
			}
		},

		renderOption: function(id, data) {
			const newOption = this.createOption(id, data);
			const matchedOption = this.searchOption(id);

			if (matchedOption != null) {
				matchedOption.replaceWith(newOption);
				newOption.prop('selected', matchedOption.prop('selected'));
			} else {
				newOption.insertBefore(this.getCustomOption());
			}

			return newOption;
		},

		searchOption: function(id) {
			const searchValue = '' + id;
			const options = this.getElement('option');
			let result;

			for (let index = options.length - 1; index >= 0; --index) {
				let option = options[index];

				if (option.value === searchValue) {
					result = $(option);
					break;
				}
			}

			return result;
		},

		createOption: function(primary, data) {
			const template = this.getTemplate('option');
			const html = Utils.compileTemplate(template, $.extend({}, data, { 'PRIMARY': primary }));
			const result = $(html);

			for (const size of this.getSizes()) {
				result.attr('data-' + size.toLowerCase(), data[size]); // use attribute for copy
			}

			return result;
		},

		getCustomOption: function() {
			return this.getElement('option').filter('[data-custom]');
		},

		getSelectedOption: function() {
			return this.getElement('option').filter(':selected');
		},

		getOptionSizes: function(option) {
			const result = {};

			for (let size of this.getSizes()) {
				let sizeName = size.toLowerCase();
				let sizeValue = option.data(sizeName);

				if (sizeValue != null) {
					result[size] = sizeValue;
				}
			}

			return result;
		},

		getSizes: function() {
			return [
				'WIDTH',
				'HEIGHT',
				'DEPTH',
				'WEIGHT',
			];
		},

		getField: function() {
			return this.options.field;
		},

		syncSaveButton: function() {
			const state = this._isCustomSelected ? 'save' : 'edit';

			this.renderSaveButton(state);
		},

		renderSaveButton: function(state) {
			const button = this.getSaveButton();
			const template = this.getTemplate('save');
			const html = Utils.compileTemplate(template, {
				STATE: state,
				TEXT: this.getLang(state.toUpperCase()),
			});

			button.html(html);
		},

		getSaveButton: function() {
			return this.getElement('save', this.$el, 'siblings');
		},

	}, {
		dataName: 'orderViewBoxSizePackSelect',
		pluginName: 'YandexMarket.OrderView.BoxSizePackSelect',
	});

})(BX, jQuery, window);