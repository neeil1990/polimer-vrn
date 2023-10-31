(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const OrderView = BX.namespace('YandexMarket.OrderView');
	const utils = BX.namespace('YandexMarket.Utils');

	const constructor = OrderView.ShipmentSubmit = Plugin.Base.extend({

		defaults: {
			url: 'yamarket_trading_shipment_submit.php',

			messageElement: '.js-yamarket-shipment-submit__message',
			messageRowTemplate: '<div class="yamarket-shipment-submit__result-row" data-status="#STATUS#">#TEXT#</div>',

			orderElement: '.js-yamarket-order',

			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT_SUBMIT_'
		},

		initVars: function() {
			this.callParent('initVars', constructor);
			this._handleBoxCollectionChange = false;
		},

		destroy: function() {
			this.unbind();
			this.callParent('initialize', constructor);
		},

		unbind: function() {
			this.handleShipmentChange(false);
			this.handleBoxCollectionChange(false);
		},

		handleShipmentChange: function(dir) {
			const orderElement = this.getOrderElement();

			orderElement[dir ? 'on' : 'off']('change', $.proxy(this.onOrderChange, this));
		},

		handleBoxCollectionChange: function(dir) {
			if (this._handleBoxCollectionChange === dir) { return; }

			this._handleBoxCollectionChange = dir;

			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('yamarketOrderViewBoxCollectionAddItem', BX.proxy(this.onBoxCollectionModify, this));
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('yamarketOrderViewBoxCollectionDeleteItem', BX.proxy(this.onBoxCollectionModify, this));
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('yamarketOrderViewBoxItemCollectionAddItem', BX.proxy(this.onBoxCollectionModify, this));
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('yamarketOrderViewBoxItemCollectionDeleteItem', BX.proxy(this.onBoxCollectionModify, this));
		},

		onOrderChange: function() {
			this.handleShipmentChange(false);
			this.handleBoxCollectionChange(false);
			this.clear();
		},

		onBoxCollectionModify: function(instance) {
			const orderElement = this.getOrderElement();

			if ($.contains(orderElement[0], instance.el)) {
				this.handleShipmentChange(false);
				this.handleBoxCollectionChange(false);
				this.clear();
			}
		},

		clear: function() {
			this.showMessage('', '');
		},

		activate: function() {
			if (this.el.disabled) { return; }
			if (!this.validate()) { return; }

			$.when(this.confirm())
				.then(() => {
					this.el.disabled = true;

					this.clear();
					this.query().then(
						$.proxy(this.activateEnd, this),
						$.proxy(this.activateStop, this)
					);
				});
		},

		activateStop: function(xhr, reason) {
			const message = this.getLang('FAIL', { 'REASON': reason });

			this.el.disabled = false;
			this.showMessage(message, 'error');

			this.handleShipmentChange(true);
			this.handleBoxCollectionChange(true);
		},

		activateEnd: function(data) {
			let status;

			this.el.disabled = false;

			if (typeof data !== 'object' || data.status == null) {
				status = 'error';
				this.showMessage(this.getLang('DATA_INVALID'), 'error');
			} else {
				status = data.status;

				if (data.messages != null) {
					this.showMessages(data.messages);
				} else {
					this.showMessage(data.message, status);
				}
			}

			if (status === 'ok') {
				this.commit();
			}

			this.handleShipmentChange(true);
			this.handleBoxCollectionChange(true);

			BX.onCustomEvent(this.el, 'yamarketShipmentSubmitEnd', [status]);
		},

		validate: function() {
			let result = true;
			let confirmMessage;

			try {
				this.getOrder().validate();
			} catch (e) {
				confirmMessage = this.getLang('VALIDATION_CONFIRM', { MESSAGE: e.message });
				result = confirm(confirmMessage || e.message);
			}

			return result;
		},

		confirm: function() {
			return this.getOrder().confirm();
		},

		commit: function() {
			this.getOrder().commit();
		},

		query: function() {
			return $.ajax({
				url: this.options.url,
				type: 'POST',
				data: this.getQueryData(),
				dataType: 'json'
			});
		},

		getQueryData: function() {
			const data = this.getFormData();

			data.push({
				name: 'sessid',
				value: BX.bitrix_sessid()
			});

			return data;
		},

		showMessages: function(messages) {
			const element = this.getElement('message', this.$el, 'siblings');
			const rowTemplate = this.getTemplate('messageRow');
			let html = '';

			for (let message of messages) {
				html += utils.compileTemplate(rowTemplate, {
					'TEXT': message.text,
					'STATUS': message.status
				});
			}

			element.attr('data-status', '');
			element.html(html);
		},

		showMessage: function(message, status) {
			const element = this.getElement('message', this.$el, 'siblings');

			element.attr('data-status', status);
			element.html(message || '');
		},

		getFormData: function() {
			return this.getOrderElement().find('input, select, textarea').serializeArray();
		},

		getOrderElement: function() {
			return this.getElement('order', this.$el, 'closest');
		},

		getOrder: function() {
			const node = this.getOrderElement();

			return Plugin.manager.getInstance(node);
		},

	}, {
		dataName: 'orderViewShipmentCollection',
		pluginName: 'YandexMarket.OrderView.ShipmentCollection',
	});

})(BX, jQuery, window);