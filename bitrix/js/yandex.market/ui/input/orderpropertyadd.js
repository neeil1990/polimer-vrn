(function($, BX, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const Input = BX.namespace('YandexMarket.Ui.Input');
	const Utils = BX.namespace('YandexMarket.Utils');

	const constructor = Input.OrderPropertyAdd = Plugin.Base.extend({

		defaults: {
			url: null,

			serviceCode: null,

			personTypeId: null,
			personTypeElement: 'select[name="PERSON_TYPE"]',

			inputElement: 'select',
			optionTemplate: '<option value="#ID#">#VALUE#</option>',

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
			this.handleClick(true);
		},

		unbind: function() {
			this.handleClick(false);
		},

		handleClick: function(dir) {
			this.$el[dir ? 'on' : 'off']('click', $.proxy(this.onClick, this));
		},

		onClick: function() {
			this.activate();
		},

		activate: function() {
			BX.showWait();

			return this.makeQuery().then(
				$.proxy(this.activateEnd, this),
				$.proxy(this.activateStop, this)
			)
		},

		makeQuery: function() {
			return $.ajax({
				url: this.options.url,
				type: 'POST',
				dataType: 'json',
				data: {
					SERVICE_CODE: this.options.serviceCode,
					PERSON_TYPE_ID: this.getPersonTypeId(),
				},
			});
		},

		activateStop: function(xhr, reason) {
			alert(this.getLang('ADD_FAIL', { 'MESSAGE': reason }));
			BX.closeWait();
		},

		activateEnd: function(response) {
			if (response && response.status === 'ok') {
				this.createOption(response.option);

				if (response.option['EDIT_URL']) {
					this.openEditWindow(response.option['EDIT_URL']);
				}
			} else {
				alert(this.getLang('ADD_FAIL', { 'MESSAGE': response ? response.message : '' }));
			}

			BX.closeWait();
		},

		createOption: function(option) {
			const input = this.getElement('input', this.$el, 'siblings');
			const inputGroup = input.find('optgroup');
			const template = this.getTemplate('option');
			const optionHtml = Utils.compileTemplate(template, option);
			const optionElement = $(optionHtml);

			optionElement.appendTo(inputGroup.length > 0 ? inputGroup : input);
			optionElement.prop('selected', true);
		},

		openEditWindow: function(url) {
			const editWindow = window.open(url, '_blank');

			editWindow.focus();
		},

		getForm: function() {
			return $(this.el.form);
		},

		getFormInput: function(name) {
			const form = this.getForm();

			return this.getElement(name, form);
		},

		getPersonTypeId: function() {
			const input = this.getFormInput('personType');
			let result;

			if (input.length > 0) {
				result = input.val();
			} else {
				result = this.options.personTypeId;
			}

			return result;
		}

	}, {
		dataName: 'uiInputOrderPropertyAdd'
	});

})(jQuery, BX, window);