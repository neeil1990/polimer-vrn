(function(BX, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	Input.ProfileEdit = constructor = Plugin.Base.extend({

		defaults: {
			editUrl: null,
			refreshUrl: null,
			refreshTimeout: 20000,

			personTypeId: null,
			personTypeElement: 'select[name="PERSON_TYPE"]',

			userId: null,

			inputElement: 'select',
			optionElement: 'option',

			lang: {},
			langPrefix: 'YANDEX_MARKET_USER_FIELD_BUYER_PROFILE_'
		},

		initVars: function() {
			this.callParent('initVars', constructor);
			this._refreshInternval = null;
			this._refreshInternvalActive = false;
		},

		initialize: function() {
			this.bind();
		},

		destroy: function() {
			this.unbind();
		},

		bind: function() {
			this.handleClick(true);
			this.handlePersonTypeChange(true);
		},

		unbind: function() {
			this.handleClick(false);
			this.handlePersonTypeChange(false);
		},

		handleClick: function(dir) {
			this.$el[dir ? 'on' : 'off']('click', $.proxy(this.onClick, this));
		},

		handlePersonTypeChange: function(dir) {
			var input = this.getFormInput('personType');

			if (input) {
				input[dir ? 'on' : 'off']('change', $.proxy(this.onPersonTypeChange, this));
			}
		},

		handleEditWindowUnload: function(dir, window) {
			$(window)[dir ? 'on' : 'off']('unload', $.proxy(this.onEditWindowUnload, this));
		},

		onClick: function() {
			var editWindow = this.openEditWindow();

			this.handleEditWindowUnload(true, editWindow);
			this.refreshInterval();
		},

		onPersonTypeChange: function() {
			this.refresh();
		},

		onEditWindowUnload: function(evt) {
			var editWindow = evt.currentTarget;

			setTimeout(this.onEditWindowUnloadDelayed.bind(this, editWindow), 1500);
		},

		onEditWindowUnloadDelayed: function(editWindow) {
			this.refresh();

			if (editWindow && editWindow.closed) {
				this.refreshIntervalStop();
				this.handleEditWindowUnload(false, editWindow);
			}
		},

		openEditWindow: function() {
			var editUrl = this.getEditUrl();

			return this.openWindow(editUrl);
		},

		getEditUrl: function() {
			var url = this.options.editUrl;
			var query = this.getEditQuery();

			if (query !== '') {
				url +=
					(url.indexOf('?') === -1 ? '?' : '&')
					+ query;
			}

			return url;
		},

		getEditQuery: function() {
			var input = this.getInput();
			var inputValue = input.val();
			var personTypeId = this.getPersonTypeId();
			var userId = this.getUserId();
			var query = [];

			query.push('personType=' + encodeURIComponent(personTypeId));
			query.push('userId=' + encodeURIComponent(userId));

			if (!this.isEmptyValue(inputValue)) {
				query.push('id=' + encodeURIComponent(inputValue));
			}

			return query.join('&');
		},

		refreshInterval: function() {
			this._refreshInternvalActive = true;
			this._refreshInternval = setTimeout(
				$.proxy(this.refresh, this),
				this.options.refreshTimeout
			);
		},

		refreshIntervalStop: function() {
			this._refreshInternvalActive = false;
			this.refreshIntervalClear();
		},

		refreshIntervalClear: function() {
			clearTimeout(this._refreshInternval);
		},

		refresh: function() {
			var personTypeId = this.getPersonTypeId();

			if (personTypeId == null || personTypeId === '') {
				this.refreshInput([]);
			} else {
				this.refreshIntervalClear();

				BX.ajax({
					url: this.options.refreshUrl,
					method: 'POST',
					data: {
						PERSON_TYPE_ID: personTypeId,
						USER_ID: this.getUserId(),
					},
					dataType: 'json',
					onsuccess: $.proxy(this.refreshEnd, this)
				});
			}
		},

		refreshEnd: function(response) {
			if (!response || response.status !== 'ok') {
				alert(this.getLang('REFRESH_FAIL', { 'MESSAGE': response ? response.message : '' }));
			} else {
				this.refreshInput(response.enum);
			}

			if (this._refreshInternvalActive) {
				this.refreshInterval();
			}
		},

		refreshInput: function(values) {
			var input = this.getInput();
			var optionList = this.getElement('option', input);
			var optionIndex;
			var option;
			var valueIndex;
			var value;
			var existValueIds = [];

			// create new

			for (valueIndex = 0; valueIndex < values.length; valueIndex++) {
				value = values[valueIndex];
				option = this.searchTokenOption(optionList, value.ID);

				if (option == null) {
					option = this.createProfileOption(input, value);
				} else if (option.textContent !== value.VALUE) {
					option.textContent = value.VALUE;
				}

				if (valueIndex === 0 && this.isEmptyValue(input.val())) {
					this.selectOption(input, option);
				}

				existValueIds.push(value.ID);
			}

			// delete non-exists

			for (optionIndex = 0; optionIndex < optionList.length; optionIndex++) {
				option = optionList[optionIndex];

				if (existValueIds.indexOf(option.value) === -1 && !this.isEmptyValue(option.value)) {
					option.parentElement.removeChild(option);
				}
			}
		},

		searchTokenOption: function(optionList, tokenId) {
			var option;
			var optionValue;
			var i;
			var result = null;

			tokenId = ('' + tokenId);

			for (i = 0; i < optionList.length; i++) {
				option = optionList[i];
				optionValue = ('' + option.value);

				if (optionValue === tokenId) {
					result = option;
					break;
				}
			}

			return result;
		},

		createProfileOption: function(input, profile) {
			var option = document.createElement('option');

			option.value = profile.ID;
			option.textContent = profile.VALUE;

			input.append(option);

			return option;
		},

		selectOption: function(input, option) {
			option.selected = true;
		},

		getInput: function() {
			var parent = this.$el.parent();

			return this.getElement('input', parent);
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

		getUserId: function() {
			return this.options.userId;
		},

		openWindow: function(url) {
			var result = window.open(url, '_blank');

			result.focus();

			return result;
		},

		isEmptyValue: function(value) {
			return !value;
		}

	}, {
		dataName: 'uiInputProfileEdit'
	});

})(BX, window);