import { htmlToElement } from "../utils";
import './box.css';

export default class ItemView {
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

	toggleUseDimensions(enabled) {
		this.options.useDimensions = enabled;
	}

	render(value) {
		this.el = htmlToElement(`<div class="yamarket-box">
			${this.buildDefined(value)}
			${this.buildHeader(value)}
			${this.buildBody(value)}
		</div>`);
	}

	buildDefined(value) {
		const keys = [
			'FULFILMENT_ID',
		];
		let result = '';

		for (const key of keys) {
			result += `<input type="hidden" name="${this.options.name}[${key}]" value="${BX.util.htmlspecialchars(value[key] || '')}" />`;
		}

		return result;
	}

	buildSizes(value) {
		if (!this.options.useDimensions) { return ''; }

		return `${Object.keys(this.options.boxDimensions).map((name) => {
			const inputName = this.getName('DIMENSIONS') + `[${name}]`;
			const inputValue = value?.DIMENSIONS?.[name]?.['VALUE'] || '';
			
			if (inputValue === '') { return ''; }

			return `<input type="hidden" name="${inputName}" value="${BX.util.htmlspecialchars(inputValue)}" data-name="${name}" />`;
		}).join('')}`;
	}

	buildHeader(value) {
		return `<div class="yamarket-box__header">
			<div class="yamarket-box__title">
				${this.getMessage('BOX')}
				&numero;${value['NUMBER']}
			</div>
			${this.buildProperties(value)}
			${this.buildActions(value)}
		</div>`;
	}

	buildProperties(value) {
		const disabled = this.disabledProperties();

		return `<div class="yamarket-box__properties">
			${Object.entries(this.options.boxProperties).map(([name,property]) => {
				const propertyValue = value?.PROPERTIES?.[name] || '';
				
				if (propertyValue === '' || disabled.indexOf(name) !== -1) { return ''; }
				
				return `<div class="yamarket-box__property">
					${property['NAME']}: ${propertyValue} ${property['UNIT_FORMATTED'] || ''}
				</div>`;
			}).join('')}
		</div>`
	}

	disabledProperties() {
		return [];
	}

	buildActions(value) {
		return '';
	}

	buildBody(value) {
		return this.buildSizes(value);
	}

	mount(point, after) {
		if (after != null) {
			after.after(this.el);
		} else {
			point.appendChild(this.el);
		}
	}

	updateNumber(number) {
		const title = this.el.querySelector('.yamarket-box__title');
		const fulfilmentId = this.el.querySelector('input[name$="[FULFILMENT_ID]"]');

		title.innerHTML = `${this.getMessage('BOX')} &numero;${number}`;
		fulfilmentId.value = this.options.fulfilmentBase + number;
	}

	getName(field) {
		return this.options.name + '[' + field + ']';
	}

	fireChange() {
		this.options.onChange && this.options.onChange();
	}
}