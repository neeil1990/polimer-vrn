import ItemView from './itemview';
import ItemEdit from './itemedit';
import { htmlToElement } from "../utils";

export default class Collection {
	static defaults = {
		name: null,
		messages: {},
		boxProperties: {},
		boxDimensions: {},
		useDimensions: false,
		fulfilmentBase: null,
		onChange: null,
	}

	constructor(options) {
		this.options = Object.assign({}, this.constructor.defaults, options);
	}

	getMessage(key) {
		return this.options.messages[key] || key;
	}

	handleItemRemove() {
		this.el.addEventListener('yamarketBoxDelete', this.onItemRemove);
	}

	handleAddClick(button) {
		button.addEventListener('click', this.onAddClick);
	}

	onItemRemove = (evt) => {
		const item = evt.detail.item;

		this.releaseItem(item);
		this.renewNumber();
		this.fireChange();
	}

	onAddClick = (evt) => {
		this.addItem();
		this.fireChange();

		evt.preventDefault();
	}

	toggleUseDimensions(enabled) {
		this.options.useDimensions = enabled;

		if (this.items == null) { return; }

		for (const item of this.items) {
			item.toggleUseDimensions(enabled);
		}
	}

	render(value) {
		this.renderSelf();
		this.renderItems(value);
		this.renderAddButton();
	}

	renderSelf() {
		this.el = htmlToElement(`<div class="yamarket-boxes"></div>`);
	}

	renderItems(value) {
		const items = [];
		let index = 0;

		for (const one of value) {
			const box = this.createItem(index);

			box.render(one);
			box.mount(this.el);

			items.push(box);

			++index;
		}

		this.items = items;
		this.nextIndex = index;

		this.handleItemRemove();
	}

	createItem(index) {
		const options = {
			name: this.options.name + `[${index}]`,
			messages: this.options.messages,
			boxProperties: this.options.boxProperties,
			boxDimensions: this.options.boxDimensions,
			useDimensions: this.options.useDimensions,
			fulfilmentBase: this.options.fulfilmentBase,
			onChange: this.options.onChange,
		};

		if (this.options.mode === BX.UI.EntityEditorMode.edit) {
			return new ItemEdit(options);
		}

		return new ItemView(options);
	}

	releaseItem(item) {
		const index = this.items.indexOf(item);

		if (index === -1) { return; }

		this.items.splice(index, 1);
	}

	renewNumber() {
		let num = 1;

		for (const box of this.items) {
			box.updateNumber(num);
			++num;
		}
	}

	addItem() {
		const number = this.items.length + 1;
		const box = this.createItem(this.nextIndex);
		const last = this.items[this.items.length - 1]?.el;

		box.render({
			'FULFILMENT_ID': this.options.fulfilmentBase + number,
			'NUMBER': number,
		});
		box.mount(this.el, last);

		this.items.push(box);
		++this.nextIndex;
	}

	renderAddButton() {
		if (this.options.mode !== BX.UI.EntityEditorMode.edit) { return ''; }

		const addButton = htmlToElement(`<span class="yamarket-boxes__add ui-entity-editor-content-create-lnk">${this.getMessage('BOX_ADD')}</span>`);

		this.el.appendChild(addButton);
		this.handleAddClick(addButton);
	}

	fireChange() {
		this.options.onChange && this.options.onChange();
	}

	mount(point) {
		point.appendChild(this.el);
	}
}