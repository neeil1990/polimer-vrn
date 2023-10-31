import {replaceTemplateVariables} from "../utils";

export default class Field extends BX.UI.EntityEditorField {
	constructor() {
		super();
		this._hasLayout = false;
		this.options = this.constructor.defaults != null
			? Object.assign({}, this.constructor.defaults)
			: {};
	}

	getMessage(key, replaces = null) {
		let result = this.constructor.messages[key] || super.getMessage(key);

		if (replaces != null) {
			result = replaceTemplateVariables(result, replaces);
		}

		return result;
	}

	clearLayout() {
		if (!this._hasLayout) { return; }

		this.forget();

		this._wrapper.innerHTML = '';
		this._hasLayout = false;
	}

	layout(options) {
		if (this._hasLayout) { return; }

		this.ensureWrapperCreated({});
		this.adjustWrapper();

		if (!this.isNeedToDisplay()) {
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		this.forget();

		this._wrapper.innerHTML = '';

		if (this.isDragEnabled()) {
			this._wrapper.appendChild(this.createDragButton());
		}

		if (this.useTitle()) {
			const title = this.getTitle();
			this._wrapper.appendChild(this.createTitleNode(title));
		}

		this.render(this.getValue());

		if (this.isContextMenuEnabled()) {
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if (this.isDragEnabled()) {
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	}

	forget() {}

	render(payload) {}

	useTitle() {
		return false;
	}

	fewElements(key) {
		const selector = this.getElementSelector(key);
		let result;

		if (selector.indexOf('#') === 0) {
			result = document.querySelectorAll(selector);
		} else {
			result = this.el.querySelectorAll(selector);
		}

		return result;
	}

	getElement(key) {
		const selector = this.getElementSelector(key);
		let result;

		if (selector.indexOf('#') === 0) {
			result = document.querySelector(selector);
		} else {
			result = this.el.querySelector(selector);
		}

		return result;
	}

	getElementSelector(key) {
		return this.options[key + 'Element'];
	}
}