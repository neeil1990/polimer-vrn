import ReferenceField from "../reference/field";
import {htmlToElement} from "../utils";

export default class Field extends ReferenceField {
	static messages = {}
	static defaults = {
		broadcastElement: '#YAMARKET_ORDER_VIEW',
	}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	useTitle() {
		return true;
	}

	bind() {
		this.handleActivityEnd(true);
	}

	unbind() {
		this.handleActivityEnd(false);
	}

	handleActivityEnd(dir) {
		if (this._activityType == null) { return; }

		const broadcast = this.getElement('broadcast');

		BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](broadcast, 'yamarketActivitySubmitEnd', this.onActivityEnd);
	}

	onActivityEnd = (type) => {
		if (!this.isMatchActivity(type)) { return; }

		this._editor.reload();
	}

	isMatchActivity(type) {
		if (type == null) { return false; }

		return (type === this._activityType || type.indexOf(this._activityType + '|') === 0);
	}

	forget() {
		if (!this.el) { return; }

		this.unbind();
	}

	render(payload) {
		const html = this.build(payload);

		this._activityType = payload?.ACTIVITY_ACTION?.TYPE;

		this.el = htmlToElement(html);
		this.bind();

		this._wrapper.appendChild(this.el);
	}

	build(payload) {
		const activity = payload['ACTIVITY_ACTION'];

		return `<div class="ui-entity-editor-content-block">
			<div class="ui-entity-editor-content-block-text">
				${BX.util.htmlspecialchars(payload['VALUE'])}
				${activity ? this.buildActivity(activity, payload) : ''}
			</div>
		</div>`;
	}

	buildActivity(activity, property) {
		const title = activity['TEXT'] !== property['NAME'] ? activity['TEXT'] : this.getMessage('ACTIVITY_APPLY');
		const onclick = this.makeActivityMethod(activity);

		return `<small>
			<a href="#" onclick='${onclick}; return false'>${title}</a>
		</small>`;
	}

	makeActivityMethod(activity) {
		let result;

		if (activity['MENU'] != null) {
			const items = activity['MENU'].map((action) => {
				return {
					text: action['TEXT'],
					onclick: action['METHOD'],
				};
			});

			result = `BX.PopupMenu.show("${activity['TYPE']}", this, ${JSON.stringify(items)}, { angle: { offset: 50 } })`;
		} else {
			result = activity['METHOD'];
		}

		return result;
	}
}