(function(BX, $, window) {

	const Root = BX.namespace('YandexMarket');
	const Plugin = BX.namespace('YandexMarket.Plugin');
	const Ui = BX.namespace('YandexMarket.Ui');

	const constructor = Ui.ModalForm = Plugin.Base.extend({

		defaults: {
			url: null,
			data: null,
			title: null,
			saveTitle: null,
			width: 400,
			height: 250,
			buttons: null,
		},

		initVars: function() {
			this.callParent('initVars', constructor);
			this._modal = null;
			this._handled = {};
			this._activateDeferred = null;
		},

		handleFormSubmit: function(dir) {
			if (!!this._handled['formSubmit'] === dir) { return; }

			if (this.hasModal()) {
				const contentElement = this.getModal().GetContent();

				this._handled['formSubmit'] = dir;
				$(contentElement).on('submit', $.proxy(this.onFormSubmit, this));
			} else if (!dir) {
				this._handled['formSubmit'] = false;
			}
		},

		handleFormSave: function(dir) {
			if (!!this._handled['formSave'] === dir) { return; }

			this._handled['formSave'] = dir;
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('yamarketFormSave', BX.proxy(this.onFormSave, this));
		},

		handlePostAction: function(dir) {
			if (!!this._handled['postAction'] === dir) { return; }

			if (this.hasModal()) {
				const modal = this.getModal();
				const buttonsContainer = modal.PARTS.BUTTONS_CONTAINER;

				this._handled['postAction'] = dir;
				$(buttonsContainer).on('click', '[data-post-action]', $.proxy(this.onPostAction, this));
			} else if (!dir) {
				this._handled['postAction'] = false;
			}
		},

		handleError: function(dir) {
			if (!!this._handled['error'] === dir) { return; }

			this._handled['error'] = dir;
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](this.getModal(), 'onWindowError', BX.proxy(this.onError, this));
		},

		handleClose: function(dir) {
			if (!!this._handled['close'] === dir) { return; }

			this._handled['close'] = dir;
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](this.getModal(), 'onWindowClose', BX.proxy(this.onClose, this));
		},

		onFormSubmit: function(evt) {
			const formElement = this.getModal().GetForm();
			const form = $(formElement);

			this.prepareAjaxForm(form);
		},

		onFormSave: function(data) {
			this.activateEnd(data);

			this.getModal().Close();
			BX.closeWait();
		},

		onPostAction: function(evt) {
			const modal = this.getModal();
			const button = evt.currentTarget;
			const action = button.dataset.postAction;

			if (!button.type) { button.type = 'button'; } // hack for showWait
			modal.showWait(button);

			this.postAction(action);

			evt.preventDefault();
		},

		onError: function() {
			if (!this.hasModal()) { return; }

			this.getModal().closeWait();
		},

		onClose: function() {
			this.handleFormSubmit(false);
			this.handleFormSave(false);
			this.handlePostAction(false);
			this.handleError(false);
			this.handleClose(false);

			this.activateStop();
		},

		postAction: function(action) {
			const modal = this.getModal();
			const form = modal.GetForm();

			if (!form) { throw new Error('post action form element required'); }

			const postActionInput = $('<input type="hidden" name="postAction" value="' + action + '" />');

			postActionInput.appendTo(form);
			modal.Submit();
			setTimeout(() => { postActionInput.remove() }, 100);
		},

		getFormData: function() {
			const modal = this.getModal();
			const form = modal.GetForm();

			if (!form) { return []; }

			return $(form).serializeArray();
		},

		activate: function() {
			this.getModal().Show();

			this.handleFormSubmit(true);
			this.handleFormSave(true);
			this.handlePostAction(true);
			this.handleError(true);
			this.handleClose(true);

			return (this._activateDeferred = new $.Deferred());
		},

		activateStop: function() {
			if (this._activateDeferred == null) { return; }

			this._activateDeferred.reject();
			this._activateDeferred = null;
		},

		activateEnd: function(data) {
			if (this._activateDeferred == null) { return; }

			this._activateDeferred.resolve(data);
			this._activateDeferred = null;
		},

		hasModal: function() {
			return this._modal != null;
		},

		getModal: function() {
			if (this._modal == null) {
				this._modal = this.createModal();
			}

			return this._modal;
		},

		createModal: function() {
			const options = this.getModalOptions();

			return new Root.Dialog(options);
		},

		getModalOptions: function() {
			return {
				title: this.options.title,
				width: this.options.width,
				height: this.options.height,
				content_url: this.makeModalUrl(),
				content_post: this.options.data,
				draggable: true,
				resizable: true,
				buttons: this.getModalButtons(),
			};
		},

		getModalButtons: function() {
			return this.options.buttons != null ? this.options.buttons : this.getDefaultButtons();
		},

		getDefaultButtons: function() {
			let saveBtn = BX.CAdminDialog.btnSave;

			if (this.options.saveTitle) {
				saveBtn = Object.assign({}, saveBtn, {
					title: this.options.saveTitle,
				});
			}

			return [
				saveBtn,
				BX.CAdminDialog.btnCancel,
			];
		},

		makeModalUrl: function() {
			const url = this.options.url || '';

			return url
				+ (url.indexOf('?') === -1 ? '?' : '&')
				+ 'view=dialog';
		},

		prepareAjaxForm: function(form) {
			if (form.find('input[name="ajaxForm"]').length > 0) { return; }

			form.append('<input type="hidden" name="ajaxForm" value="Y" />');
		},

	}, {
		pluginName: 'YandexMarket.OrderView.ModalForm',
	});

})(BX, jQuery, window);