import ReferenceField from "../reference/field";
import './notification.css';

export default class Field extends ReferenceField {

	static defaults = {}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	render(value) {
		if (!Array.isArray(value)) { return; }

		const html = value.map((one) => `<div class="ui-alert ui-alert-${one.type}">${one.text}</div>`).join('');

		this._wrapper.insertAdjacentHTML('beforeend', html);
	}

}