import SummarySkeleton from "./summaryskeleton";
import {fillValues, replaceTemplateVariables} from "../utils";

export default class Cis extends SummarySkeleton {

	static defaults = Object.assign({}, SummarySkeleton.defaults, {
		copyElement: '.yamarket-item-summary__copy',
		inputElement: 'input, select',
		title: '',
		total: 0,
		markingGroup: false,
		markingType: null,
		instances: [],
		instanceTypes: [],
		internalInstances: [],
	})

	bind() {
		super.bind();
		this.handleCopyClick(true);
	}

	handleCopyClick(dir) {
		const copy = this.getElement('copy');

		if (copy == null) { return; }

		copy[dir ? 'addEventListener' : 'removeEventListener']('click', this.onCopyClick);
	}

	onCopyClick = (evt) => {
		this.copyInternal();
		evt.preventDefault();
	}

	updateTotal(count) {
		this.options.total = count;
		this.reflowStatus();
		this.reflowForm();
	}

	build() {
		if (this.requiredTypes().length === 0) { return '&mdash;'; }

		const internalCises = this.optionValue('internal');

		return `<div class="yamarket-item-summary">
			${this.buildStatus()}
			${this.emptyValues(internalCises) ? '' : this.buildCopyIcon()}
			<div class="yamarket-item-summary__modal" hidden>
				${this.buildForm()}
			</div>
		</div>`;
	}

	buildCopyIcon() {
		return `<button class="yamarket-item-summary__copy" type="button" title="${this.getMessage('COPY')}">
			${this.getMessage('COPY')}
		</button>`;
	}

	buildForm(useFormValue = false) {
		const total = parseInt(this.options.total) || 0;
		const iterator = (new Array(total)).fill(null);
		const value = useFormValue ? this.formValue() : this.optionValue();
		const types = this.requiredTypes();

		return `<table class="ui-form yamarket-cis-table">
			<thead class="yamarket-cis-table__head">
				<td></td>
				${types.map((type) => `<td>${this.getMessage('HEAD_' + type)}</td>`).join('')}
			</thead>
			
			${iterator.map((dummy, index) => {
				return `<tr>
					<td class="yamarket-cis-table__number">
						&numero;${index + 1}
					</td>
					${types.map((type) => {
						const name = `ITEMS[${index}][${type}]`;
						const one = value[name] ?? '';
						const placeholder = this.hasMessage('PLACEHOLDER_' + type) ? this.getMessage('PLACEHOLDER_' + type) : '';
						
						return `
							<td class="yamarket-cis-table__control type-count--${types.length}">
								<div class="ui-ctl ui-ctl-sm ui-ctl-textbox ui-ctl-w100">
									<input class="ui-ctl-element" type="text" name="${this.inputName(name)}" value="${BX.util.htmlspecialchars(one)}" data-name="${name}" placeholder="${placeholder}" />
								</div>
							</td>
						`;
					}).join('')}
				</tr>`;
			}).join('')}
			${types.includes('CIS') && this.fixedCisType() == null ? 
				`<tr>
					<td></td>
					<td class="yamarket-cis-table__type">
						<div class="yamarket-cis-table__type-label">
							<div class="ui-ctl-label-text">${this.getMessage('FORMAT')}</div>
						</div>
						<div class="yamarket-cis-table__type-value">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" name="${this.inputName('TYPE')}" data-name="TYPE">
									${['CIS', 'UIN'].map((typeVariant) => {
										return `<option value="${typeVariant}" ${typeVariant === value['TYPE'] ? 'selected' : ''}>${this.getMessage(typeVariant)}</option>`;
									}).join('')}
								</select>
							</div>
						</div>
					</td>
				</tr>`
			 : ''}
		</table>`;
	}

	getStatus(value) {
		let result;

		if (this.filledCount(value) >= this.options.total) {
			result = SummarySkeleton.STATUS_READY;
		} else {
			result = SummarySkeleton.STATUS_WAIT;
		}

		return result;
	}

	emptyValues(values) {
		const types = this.requiredTypes();
		let empty = true;

		for (let index = 0; index < this.options.total; ++index) {
			for (const type of types) {
				const name = `ITEMS[${index}][${type}]`;
				const value = (values[name] ?? '').trim();

				if (value !== '') {
					empty = false;
					break;
				}
			}

			if (!empty) { break; }
		}

		return empty;
	}

	filledCount(values) {
		const types = this.requiredTypes();
		let filled = 0;

		for (let index = 0; index < this.options.total; ++index) {
			let itemFilled = true;

			for (const type of types) {
				const name = `ITEMS[${index}][${type}]`;
				const value = (values[name] ?? '').trim();

				if (value === '') {
					itemFilled = false;
					break;
				}
			}

			if (itemFilled) {
				++filled;
			}
		}

		return filled;
	}

	copyInternal(container = this.el) {
		const values = Object.assign({}, this.formValue(), this.optionValue('internal'));
		const isChanged = fillValues(container, this.makeInputValues(values));

		if (!isChanged) { return; }

		this.options.onChange && this.options.onChange();
		this.reflowStatus();
	}

	makeInputValues(values) {
		const result = {};

		for (const [key, value] of Object.entries(values)) {
			result[this.inputName(key)] = value;
		}

		return result;
	}

	inputName(selfName: string) : string {
		const bracketPosition = selfName.indexOf('[');
		let result;

		if (bracketPosition > 0) {
			result =
				this.options.name
				+ `[${selfName.substring(0, bracketPosition)}]`
				+ `${selfName.substring(bracketPosition)}`
		} else {
			result = this.options.name + `[${selfName}]`;
		}

		return result;
	}

	formValue() {
		const result = {};

		for (const input of this.fewElements('input')) {
			const name = String(input.dataset.name).trim();
			const value = input.value.trim();

			if (name === '' || value === '') { continue; }

			result[name] = value;
		}

		return result;
	}

	optionValue(optionKey = null) {
		const instances = optionKey != null ? this.options[optionKey + 'Instances'] : this.options.instances;
		const types = this.requiredTypes();
		const result = {};
		const fixedType = this.fixedCisType();
		const cisTypes = this.cisTypes();
		let index = 0;

		if (!Array.isArray(instances)) { return result; }

		for (const instance of instances) {
			for (const type of types) {
				let value = (instance[type] ?? '').trim();
				let valueType = type;

				if (value === '' && type === 'CIS' && fixedType == null) {
					value = (instance['UIN'] ?? '').trim();
					valueType = 'UIN';
				}

				if (value !== '') {
					result[`ITEMS[${index}][${type}]`] = value;

					if (cisTypes.includes(valueType)) {
						result['TYPE'] = valueType;
					}
				}
			}

			++index;
		}

		if (fixedType == null && result['TYPE'] == null && this.options.markingType != null) {
			result['TYPE'] = this.options.markingType;
		}

		return result;
	}

	cisTypes() {
		return [ 'UIN', 'CIS' ];
	}

	fixedCisType() {
		const option = (this.options.instanceTypes || []);

		for (const type of this.cisTypes()) {
			if (option.includes(type)) {
				return type;
			}
		}

		return null;
	}

	requiredTypes() : Array {
		const result = (this.options.instanceTypes || []).slice();
		let foundCis = false;

		for (const type of this.cisTypes()) {
			if (result.includes(type)) {
				foundCis = true;
				break;
			}
		}

		if (!foundCis && this.options.markingGroup) {
			result.unshift('CIS');
		}

		return result;
	}

	dialogOptions() {
		return {
			title: this.options.title,
			minWidth: Math.max(400, Math.min(1200, this.requiredTypes().length * 400))
		};
	}

	getMessage(key, replaces = null) {
		const keyWithPrefix = 'ITEM_CIS_' + key;
		const option = this.options.messages[keyWithPrefix];

		if (option != null) {
			return replaceTemplateVariables(option, replaces);
		}

		return super.getMessage(key, replaces);
	}

	hasMessage(key) {
		return this.getMessage(key) !== key;
	}
}