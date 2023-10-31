(function(BX, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	Input.TokenRefresh = constructor = Plugin.Base.extend({

		defaults: {
			authUrl: null,
			refreshUrl: null,
			exchangeUrl: null,
			popupWidth: 600,
			popupHeight: 600,

			authMethod: 'yaMarketAuth',
			scope: null,

			oauthClientIdElement: 'input[name="OAUTH_CLIENT_ID"]',
			oauthClientPasswordElement: 'input[name="OAUTH_CLIENT_PASSWORD"]',

			inputElement: 'select',
			optionElement: 'option',

			lang: {},
			langPrefix: 'YANDEX_MARKET_USER_FIELD_TOKEN_'
		},

		initVars: function() {
			this._handledWindowMessage = false;
		},

		initialize: function() {
			this.bind();
		},

		destroy: function() {
			this.unbind()
		},

		bind: function() {
			this.handleClick(true);
			this.handleClientIdChange(true);
		},

		unbind: function() {
			this.handleClick(false);
			this.handleClientIdChange(false);
			this.handleWindowMessage(false);
		},

		handleClick: function(dir) {
			this.$el[dir ? 'on' : 'off']('click', $.proxy(this.onClick, this));
		},

		handleClientIdChange: function(dir) {
			var input = this.getFormInput('oauthClientId');

			if (input) {
				input[dir ? 'on' : 'off']('change', $.proxy(this.onClientIdChange, this));
			}
		},

		handleWindowMessage: function(dir) {
			if (this._handledWindowMessage === dir) { return; }

			this._handledWindowMessage = dir;
			$(window)[dir ? 'on' : 'off']('message', $.proxy(this.onWindowMessage, this));
		},

		onClick: function() {
			this.handleWindowMessage(true);
			this.openAuthWindow();
		},

		onClientIdChange: function() {
			this.refresh();
		},

		onWindowMessage: function(proxyEvent) {
			var evt = proxyEvent.originalEvent;
			var response = evt.data;

			if (typeof response === 'object' && response != null && response.method === this.options.authMethod) {
				if (response.result) {
					this.exchangeCode(response.code);
				}

				this.handleWindowMessage(false);
			}
		},

		openAuthWindow: function() {
			var authUrl = this.getAuthUrl();

			return BX.util.popup(authUrl, this.options.popupWidth, this.options.popupHeight);
		},

		getAuthUrl: function() {
			var url = this.options.authUrl;

			url = url.replace('OAUTH_CLIENT_ID_HOLDER', this.getOauthClientId());
			url = url.replace('OAUTH_SCOPE_HOLDER', this.options.scope);

			return url;
		},

		refresh: function() {
			var clientId = this.getOauthClientId();

			if (clientId == null || clientId === '') {
				this.refreshInput([]);
			} else {
				BX.ajax({
					url: this.options.refreshUrl,
					method: 'POST',
					data: {
						CLIENT_ID: clientId,
						SCOPE: this.options.scope
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
		},

		exchangeCode: function(code) {
			BX.ajax({
				url: this.options.exchangeUrl,
				method: 'POST',
				data: {
					CODE: code,
					SCOPE: this.options.scope,
					CLIENT_ID: this.getOauthClientId(),
					CLIENT_PASSWORD: this.getOauthClientPassword()
				},
				dataType: 'json',
				onsuccess: $.proxy(this.exchangeCodeEnd, this),
				onfailure: $.proxy(this.exchangeCodeStop, this)
			});
		},

		exchangeCodeStop: function(status) {
			alert(this.getLang('EXCHANGE_CODE_FAIL', { 'STATUS': status }));
		},

		exchangeCodeEnd: function(response) {
			if (response && response.status === 'ok') {
				this.updateInput(response.token);
			} else {
				alert(response && response.message || this.getLang('UNDEFINED_ERROR'));
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
					option = this.createTokenOption(input, value);
				}

				if (valueIndex === 0 && input.val() === '') {
					this.selectOption(input, option);
				}

				existValueIds.push(value.ID);
			}

			// delete non-exists

			for (optionIndex = 0; optionIndex < optionList.length; optionIndex++) {
				option = optionList[optionIndex];

				if (option.value !== '' && existValueIds.indexOf(option.value) === -1) {
					option.parentElement.removeChild(option);
				}
			}
		},

		updateInput: function(token) {
			var input = this.getInput();
			var optionList = this.getElement('option', input);
			var option = this.searchTokenOption(optionList, token.ID);

			if (option == null) {
				option = this.createTokenOption(input, token);
			}

			this.selectOption(input, option);
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

		createTokenOption: function(input, token) {
			var option = document.createElement('option');

			option.value = token.ID;
			option.innerText = token.VALUE;

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

		getOauthClientId: function() {
			var input = this.getFormInput('oauthClientId');

			return input.val();
		},

		getOauthClientPassword: function() {
			var input = this.getFormInput('oauthClientPassword');

			return input.val();
		}

	}, {
		dataName: 'uiInputTokenRefresh'
	});

})(BX, window);