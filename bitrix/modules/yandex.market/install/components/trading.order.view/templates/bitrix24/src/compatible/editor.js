export default class Editor {

	start() {
		this.storeOriginDefault();
		this.handleEditorInit();
	}

	handleEditorInit() {
		BX.addCustomEvent(window, 'BX.UI.EntityEditor:onInit', this.onEditorInit);
	}

	onEditorInit = (editor, data) => {
		if (data.id !== 'yamarket_order_tab') { return; }

		this.overrideEditor(editor);
		this.overrideEditorAjax(editor);
		this.restoreOriginDefault();
	}

	storeOriginDefault() {
		this._originDefault = BX?.UI?.EntityEditor?.getDefault();
	}

	restoreOriginDefault() {
		if (this._originDefault == null) { return; }

		setTimeout(() => {
			BX?.UI?.EntityEditor?.setDefault(this._originDefault);
		});
	}

	overrideEditor(editor) {
		const createAjaxForm = editor.createAjaxForm;

		Object.assign(editor, {
			validate: this.editorValidate.bind(this, editor),
			createAjaxForm: this.editorCreateAjaxForm.bind(this, editor, createAjaxForm),
		});
	}

	editorValidate(editor, result) {
		const validator = BX.UI.EntityAsyncValidator.create();

		for (const control of editor._activeControls) {
			validator.addResult(control.validate(result));
		}

		if (this._userFieldManager) {
			validator.addResult(this._userFieldManager.validate(result));
		}

		return Promise.resolve(validator.validate());
	}

	editorCreateAjaxForm(editor, parentMethod, options, callbacks) {
		const result = parentMethod.call(editor, options, callbacks);

		this.overrideAjaxForm(result);

		return result;
	}

	overrideEditorAjax(editor) {
		this.overrideAjaxForm(editor._ajaxForm);
	}

	overrideAjaxForm(ajaxForm) {
		if (!ajaxForm || !BX?.UI?.ComponentAjax || !(ajaxForm instanceof BX.UI.ComponentAjax)) { return; }

		Object.assign(ajaxForm, {
			doSubmit: this.ajaxFormDoSubmit,
		});
	}

	ajaxFormDoSubmit(options) {
		const formData = this._elementNode
			? BX.ajax.prepareForm(this._elementNode)
			: {data : BX.clone(this._formData), filesCount : 0};

		if (BX.type.isPlainObject(options.data)) {
			for (const i in options.data) {
				if (!options.data.hasOwnProperty(i)) { continue; }

				formData.data[i] = options.data[i];
			}
		}

		const resultData = formData.filesCount > 0 ? this.makeFormData(formData) : formData;

		BX.ajax.runComponentAction(this._className, this._actionName, {
			mode: 'ajax',
			signedParameters: this._signedParameters,
			data: resultData,
			getParameters: this._getParameters
		})
			.then((response) => {
				const callback = BX.prop.getFunction(this._callbacks, "onSuccess", null);

				if (!callback) { return; }

				BX.onCustomEvent(
					window,
					"BX.UI.EntityEditorAjax:onSubmit",
					[ response["data"]["ENTITY_DATA"], response ]
				);
				callback(response["data"]);
			})
			.catch((response) => {
				const callback = BX.prop.getFunction(this._callbacks, "onFailure", null);

				if (!callback) { return; }

				var messages = [];
				var errors = response["errors"];
				for(var i = 0, length = errors.length; i < length; i++) {
					messages.push(errors[i]["message"]);
				}

				BX.onCustomEvent(
					window,
					"BX.UI.EntityEditorAjax:onSubmitFailure",
					[ response["errors"] ]
				);

				callback({ "ERRORS": messages });
			});
	}

}