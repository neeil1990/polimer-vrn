import SummarySkeleton from "./summaryskeleton";
import {collectValues, replaceTemplateVariables} from "../utils";
import {BracketChain} from "../bracketchain";

export default class Digital extends SummarySkeleton {

	static defaults = Object.assign({}, SummarySkeleton.defaults, {
		items: [],
		total: 0,
	})

	updateTotal(count) {
		this.options.total = count;
		this.reflowStatus();
		this.reflowForm();
	}

	buildForm(useFormValue = false) {
		const total = parseInt(this.options.total) || 0;
		const formValue = useFormValue ? this.formValue() : this.optionValue();

		return `<div class="ui-form">
			${this.buildFormCodes(total, formValue)}
			${this.buildFormAdditional(total, formValue)}
		</div>`;
	}

	buildFormCodes(total, formValue) {
		const hasFew = (total > 1);
		const iterator = (new Array(total)).fill(null);

		return iterator.map((dummy, index) => {
			const oneValue = formValue?.ITEM?.[index] || {};

			let title = '';
			let fieldset = `<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">${this.getMessage('CODE')}</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input class="ui-ctl-element" type="text" name="${this.options.name}[ITEM][${index}][CODE]" value="${BX.util.htmlspecialchars(oneValue['CODE'] || '')}" />
					</div>
				</div>
			</div>
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">${this.getMessage('ACTIVATE_TILL')}</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-datetime ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
						<input class="ui-ctl-element" type="text" name="${this.options.name}[ITEM][${index}][ACTIVATE_TILL]" value="${BX.util.htmlspecialchars(oneValue['ACTIVATE_TILL'] || '')}" onclick="BX.calendar({node: this, field: this, bTime: false, bHideTime: true})" />
					</div>
				</div>
			</div>`;

			if (hasFew) {
				title = `<div class="yamarket-form-group-title">${this.getMessage('GROUP', { 'NUMBER': index + 1 })}</div>`;
			}

			return title + fieldset;
		}).join('');
	}

	buildFormAdditional(total, formValue) {
		const hasFew = (total > 1);
		let title = '';
		let fieldset = `<div class="ui-form-row">
			<div class="ui-form-label">
				<div class="ui-ctl-label-text">${this.getMessage('SLIP')}</div>
			</div>
			<div class="ui-form-content">
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
					<textarea class="ui-entity-editor-field-textarea" name="${this.options.name}[SLIP]" rows="5" required>${BX.util.htmlspecialchars(formValue['SLIP'] || '')}</textarea>
				</div>
			</div>
		</div>`;

		if (hasFew) {
			title = `<div class="yamarket-form-group-title">${this.getMessage('ADDITIONAL')}</div>`;
		}

		return title + fieldset;
	}

	getStatus(value) {
		const filledItems = this.filterFilledItems(value.ITEM);
		const isItemsFilled = filledItems.length >= this.options.total;
		const isSlipFilled = (value['SLIP'] != null && value['SLIP'].trim() !== '');

		return (
			isItemsFilled && isSlipFilled
				? SummarySkeleton.STATUS_READY
				: SummarySkeleton.STATUS_WAIT
		);
	}

	filterFilledItems(items) {
		const result = [];

		for (const item of items) {
			if (item['CODE'] == null || item['CODE'].trim() === '') { continue; }
			if (item['ACTIVATE_TILL'] == null || item['ACTIVATE_TILL'].trim() === '') { continue; }

			result.push(item);
		}

		return result;
	}

	optionValue() {
		const items = Array.isArray(this.options.items) ? this.options.items : [];
		let slip = '';

		for (const item of items) {
			if (item['SLIP'] != null && item['SLIP'] !== '') {
				slip = item['SLIP'];
				break;
			}
		}

		return {
			'ITEM': items,
			'SLIP': slip,
		};
	}

	formValue() {
		const rawValues = collectValues(this.el);
		const values = BracketChain.toTree(rawValues, this.options.name);
		const defaults = {
			'ITEM': [],
			'SLIP': '',
		};

		return Object.assign(defaults, values);
	}

	getMessage(key, replaces = null) {
		const keyWithPrefix = 'ITEM_DIGITAL_' + key;
		const option = this.options.messages[keyWithPrefix];

		if (option != null) {
			return replaceTemplateVariables(option, replaces);
		}

		return super.getMessage(key, replaces);
	}
}