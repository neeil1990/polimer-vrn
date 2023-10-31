const BX = window.BX;

export default class Factory {

	static defaults = {
		name: 'yamarket',
		map: {},
	}

	constructor(options = {}) {
		this.options = Object.assign({}, this.constructor.defaults, options);
	}

	register() {
		if (this.isEditorInitialized()) {
			this.registerEditorMethod();
		} else {
			this.handleEditorInit(true);
		}
	}

	isEditorInitialized() {
		return BX.UI?.EntityEditorControlFactory?.initialized;
	}

	registerEditorMethod() {
		BX.UI.EntityEditorControlFactory.registerFactoryMethod(this.options.name, this.build);
	}

	handleEditorInit(dir) {
		BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](window, 'BX.UI.EntityEditorControlFactory:onInitialize', this.onEditorInit);
	}

	onEditorInit = (factory, event) => {
		event.methods[this.options.name] = this.build;
		this.handleEditorInit(false);
	}

	build = (type, controlId, settings) => {
		const prefix = this.options.name + '_';

		if (type.indexOf(prefix) !== 0) { return null; }

		const internalType = type.substr(prefix.length);

		if (!this.options.map.hasOwnProperty(internalType)) { return null; }

		const fieldClass = this.options.map[internalType];

		return fieldClass.create(controlId, settings);
	}
}