(function($, BX, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	Input.OrderPropertyRefresh = constructor = Plugin.Base.extend({

		defaults: {
			type: null,
			refreshUrl: null,

			personTypeId: null,
			personTypeElement: 'select[name="PERSON_TYPE"]',

			inputElement: 'select',
			optionElement: 'option',

			lang: {},
			langPrefix: 'YANDEX_MARKET_USER_FIELD_ORDER_PROPERTY_'
		},

		initialize: function() {
			this.bind();
		},

		destroy: function() {
			this.unbind();
		},

		bind: function() {
			this.handlePersonTypeChange(true);
		},

		unbind: function() {
			this.handlePersonTypeChange(false);
		},

		handlePersonTypeChange: function(dir) {
			var input = this.getFormInput('personType');

			if (input) {
				input[dir ? 'on' : 'off']('change', $.proxy(this.onPersonTypeChange, this));
			}
		},

		onPersonTypeChange: function() {
			this.refresh();
		},

		refresh: function() {
			var personTypeId = this.getPersonTypeId();
			var query;
			var isPrimary = false;

			if (personTypeId == null || personTypeId === '') {
				this.refreshInput([]);
			} else {
				query = constructor.getRefreshQuery(personTypeId);

				if (!query) {
					isPrimary = true;
					query = $.ajax({
						url: this.options.refreshUrl,
						type: 'POST',
						data: {
							PERSON_TYPE_ID: personTypeId,
						},
						dataType: 'json',
					});

					constructor.setRefreshQuery(personTypeId, query);
				}

				query.then($.proxy(this.refreshEnd, this, personTypeId, isPrimary));
			}
		},

		refreshEnd: function(personTypeId, isPrimary, response) {
			if (response && response.status === 'ok') {
				this.refreshInput(response.enum);
			} else if (isPrimary) {
				alert(this.getLang('REFRESH_FAIL', { 'MESSAGE': response ? response.message : '' }));
			}

			constructor.releaseRefreshQuery(personTypeId);
		},

		refreshInput: function(values) {
			this.deleteInputOptions();
			this.insertInputOptions(values);
		},

		deleteInputOptions: function() {
			var options = this.getElement('option');
			var firstOption = options.eq(0);

			if (firstOption.val() === '') {
				options = options.not(firstOption);
			}

			options.remove();
		},

		insertInputOptions: function(values) {
			var valueIndex;
			var value;
			var option;

			for (valueIndex = 0; valueIndex < values.length; valueIndex++) {
				value = values[valueIndex];
				option = this.createInputOption(this.$el, value);

				if (this.options.type != null && this.options.type === value.TYPE) {
					this.selectOption(this.$el, option);
				}
			}
		},

		createInputOption: function(input, option) {
			var result = document.createElement('option');

			result.value = option.ID;
			result.textContent = option.VALUE;

			input.append(result);

			return result;
		},

		selectOption: function(input, option) {
			option.selected = true;
		},

		getForm: function() {
			return $(this.el.form);
		},

		getFormInput: function(name) {
			var form = this.getForm();

			return this.getElement(name, form);
		},

		getPersonTypeId: function() {
			var input = this.getFormInput('personType');
			var result;

			if (input.length > 0) {
				result = input.val();
			} else {
				result = this.options.personTypeId;
			}

			return result;
		},

		isEmptyValue: function(value) {
			return !value;
		}

	}, {
		dataName: 'uiInputOrderPropertyRefresh',

		refreshQueries: {},

		getRefreshQuery: function(personTypeId) {
			return this.refreshQueries[personTypeId];
		},

		setRefreshQuery: function(personTypeId, query) {
			this.refreshQueries[personTypeId] = query;
		},

		releaseRefreshQuery: function(personTypeId) {
			if (this.refreshQueries[personTypeId] != null) {
				this.refreshQueries[personTypeId] = null;
			}
		},

	});

})(jQuery, BX, window);