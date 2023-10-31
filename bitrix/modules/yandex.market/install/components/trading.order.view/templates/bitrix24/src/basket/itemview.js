import Cis from "./cis";
import Digital from "./digital";
import { htmlToElement, pascalCase, kebabCase } from "../utils";

export default class ItemView {
	static defaults = {
		messages: {},
		name: null,
		title: null,
		actions: [],
		onChange: null,
	}

	constructor(options) {
		this.options = Object.assign({}, this.constructor.defaults, options);
		this._wires = [];
	}

	destroy() {
		this.forgetWires();
		this.options = {};
	}

	getMessage(key) {
		return this.options.messages[key] || key;
	}

	getTitle() {
		return this.options.title;
	}

	extendOptions(item) {
		this.options = Object.assign(this.options, {
			title: item['NAME'],
		});
	}

	render(item, basket) {
		const columns = Object.keys(basket.COLUMNS);

		columns.unshift('INDEX');

		this.forgetWires();
		this.extendOptions(item);

		this.el = htmlToElement(`<tr>
			${columns.map((key) => this.renderColumn(item, key)).join('')}
			${this.renderActions()}
		</tr>`, 'tbody');

		this.setupWires();
	}

	mount(point) {
		point.appendChild(this.el);
	}

	renderColumn(item, key) {
		const method = 'column' + pascalCase(key);

		return (
			method in this
				? this[method](item, key)
				: this.columnDefault(item, key)
		);
	}

	columnIndex(item, key) {
		return `<td class="for--${kebabCase(key)}">
			<input type="hidden" name="${this.getName('ID')}" value="${this.value(item, 'ID')}" />
			${this.valueFormatted(item, key)}
		</td>`;
	}

	columnCis(item, key) {
		const cis = new Cis({
			messages: this.options.messages,
			name: this.getName('IDENTIFIERS'),
			title: this.value(item, 'NAME'),
			markingType: this.value(item, 'MARKING_TYPE'),
			markingGroup: !!this.value(item, 'MARKING_GROUP'),
			instanceTypes: this.value(item, 'INSTANCE_TYPES'),
			total: this.value(item, 'COUNT'),
			instances: this.value(item, 'INSTANCES'),
			internalInstances: this.value(item, 'INTERNAL_INSTANCES'),
			onChange: this.options.onChange,
		});

		this.wire(key, cis);

		return `<td class="for--${kebabCase(key)}" data-wire="${key}">${cis.build()}</td>`;
	}

	columnDigital(item, key) {
		const digital = new Digital({
			messages: this.options.messages,
			name: this.getName('DIGITAL'),
			total: this.value(item, 'COUNT'),
			items: this.value(item, 'DIGITAL'),
			onChange: this.options.onChange,
		});

		this.wire(key, digital);

		return `<td class="for--${kebabCase(key)}" data-wire="${key}">${digital.build()}</td>`;
	}

	columnSubsidy(item, key) {
		const promos = this.value(item, 'PROMOS');
		let content = this.valueFormatted(item, key);

		if (promos != null && Array.isArray(promos)) {
			content += promos.map((promo) => `<div>${promo}</div>`).join('');
		}

		return `<td class="for--${kebabCase(key)}">${content}</td>`;
	}

	columnCount(item, key) {
		const value = this.value(item, key);
		const valueSanitized = parseFloat(value) || '';

		return `<td class="for--${kebabCase(key)}">
			<input type="hidden" name="${this.getName('INITIAL_COUNT')}" value="${valueSanitized}" />
			<input type="hidden" name="${this.getName('COUNT')}" value="${valueSanitized}" />
			${this.valueFormatted(item, key)} ${this.getMessage('ITEM_UNIT')}
		</td>`;
	}

	columnDefault(item, key) {
		return `<td class="for--${kebabCase(key)}">${this.valueFormatted(item, key)}</td>`;
	}

	renderActions() {
		return '';
	}

	wire(key, instance) {
		this._wires[key] = instance;
	}

	forgetWires() {
		this._wires = {};
	}

	setupWires() {
		for (const [key, instance] of Object.entries(this._wires)) {
			const column = this.el.querySelector(`[data-wire="${key}"]`);
			const element = column.firstElementChild;

			if (!element) { continue; }

			instance.setup(element);
		}
	}

	callWires(method, args = []) {
		for (const [, instance] of Object.entries(this._wires)) {
			if (typeof instance[method] !== 'function') { return; }

			instance[method].apply(instance, args);
		}
	}

	valueFormatted(item, key) {
		const formattedKey = key + '_FORMATTED';
		let result = '';

		if (item[formattedKey] != null) {
			result = item[formattedKey];
		} else if (item[key] != null) {
			result = item[key];
		}

		return result !== '' ? result : '&mdash;';
	}

	value(item, key) {
		return item[key];
	}

	getName(field) {
		return this.options.name + '[' + field + ']';
	}

	hasAction(type) {
		return this.options.actions.indexOf(type) !== -1;
	}
}